<?php
// ============================================================
//   GOMA SCHOOL INT'L — paiement.php
//   Gère le paiement d'une inscription + génération du reçu
//   Auteur : Voltaire SEGNIBO
// ============================================================

session_start();
require 'connexion.php';
require 'securite.php';

auth_requise('eleve');
session_securiser();

$id_eleve      = $_SESSION['id'];
$nom_eleve     = $_SESSION['nom'];
$id_inscription= intval($_GET['id'] ?? 0);
$erreur        = '';
$succes        = false;
$recu          = null;
$paiement_info = null;

// Vérifier que l'inscription appartient à cet élève
$stmt = $pdo->prepare("
    SELECT i.*, p.id_paiement, p.montant, p.statut AS statut_paiement,
           p.mode_paiement, p.date_paiement
    FROM inscriptions i
    LEFT JOIN paiements p ON p.id_inscription = i.id_inscription
    WHERE i.id_inscription = ? AND i.id_eleve = ?
");
$stmt->execute([$id_inscription, $id_eleve]);
$inscription = $stmt->fetch();

if (!$inscription) {
    header('Location: dashboard_eleve.php');
    exit;
}

// Déjà payé → récupérer le reçu
if ($inscription['statut_paiement'] === 'confirme') {
    $stmt = $pdo->prepare("
        SELECT r.* FROM recus r
        JOIN paiements p ON p.id_paiement = r.id_paiement
        WHERE p.id_inscription = ?
    ");
    $stmt->execute([$id_inscription]);
    $recu          = $stmt->fetch();
    $paiement_info = $inscription;
    $succes        = true;
}

// Traitement du paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$succes) {
    $montant       = floatval($_POST['montant']       ?? 0);
    $mode_paiement = trim($_POST['mode_paiement']     ?? '');
    $telephone_mm  = trim($_POST['telephone_mm']      ?? '');

    if (!valider_montant($montant)) {
        $erreur = "Le montant saisi est invalide.";
    } elseif (empty($mode_paiement)) {
        $erreur = "Veuillez choisir un mode de paiement.";
    } elseif ($mode_paiement === 'mobile_money' && empty($telephone_mm)) {
        $erreur = "Veuillez saisir votre numéro Mobile Money.";
    } else {
        // Enregistrer le paiement
        $stmt = $pdo->prepare("
            INSERT INTO paiements
                (montant, date_paiement, mode_paiement, telephone_mm, statut, id_eleve, id_inscription)
            VALUES (?, CURDATE(), ?, ?, 'confirme', ?, ?)
        ");
        $stmt->execute([$montant, $mode_paiement, $telephone_mm ?: null, $id_eleve, $id_inscription]);
        $id_paiement = $pdo->lastInsertId();

        // Générer numéro de reçu
        $numero_recu = generer_numero_recu($id_paiement);
        $fichier_pdf = 'recus/' . $numero_recu . '.pdf';

        // Enregistrer le reçu
        $pdo->prepare("
            INSERT INTO recus (numero_recu, fichier_pdf, id_paiement)
            VALUES (?, ?, ?)
        ")->execute([$numero_recu, $fichier_pdf, $id_paiement]);

        // Récupérer le reçu créé
        $stmt = $pdo->prepare("SELECT * FROM recus WHERE id_paiement = ?");
        $stmt->execute([$id_paiement]);
        $recu = $stmt->fetch();

        $paiement_info = [
            'montant'       => $montant,
            'mode_paiement' => $mode_paiement,
            'date_paiement' => date('Y-m-d'),
            'domaine_choisi'=> $inscription['domaine_choisi'],
        ];

        journaliser("Paiement confirmé — Élève:{$id_eleve} Inscription:{$id_inscription} Montant:{$montant}");
        $succes = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Paiement — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.12);--white:#fff;
  --surface:#F7F6F3;--surface2:#EFEDE8;
  --border:#E4E0D8;--texte:#1A1714;--muted:#8A8378;
  --green:#3A9E6F;--red:#C0392B;
  --r:12px;--r-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,0.07);
  --shadow-md:0 8px 32px rgba(0,0,0,0.10);
}

body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;background:var(--surface);
  display:flex;flex-direction:column;
}

/* ── NAVBAR ── */
.navbar{
  background:var(--ink);height:60px;padding:0 28px;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 2px 16px rgba(0,0,0,0.25);flex-shrink:0;
}
.navbar-brand{font-family:'DM Serif Display',serif;font-size:18px;color:#fff}
.navbar-brand em{color:var(--gold-lt);font-style:normal}
.btn-retour{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);
  color:rgba(255,255,255,0.75);padding:7px 14px;border-radius:var(--r-sm);
  font-size:12px;font-weight:600;text-decoration:none;transition:all .18s;
}
.btn-retour:hover{background:rgba(255,255,255,0.14);color:#fff}

/* ── CONTENU ── */
.page{
  flex:1;display:flex;align-items:center;
  justify-content:center;padding:32px 16px;
}

.card{
  background:#fff;border-radius:var(--r);
  border:1px solid var(--border);
  box-shadow:var(--shadow-md);
  width:100%;max-width:480px;
  overflow:hidden;
  animation:cardIn .45s cubic-bezier(.22,1,.36,1) both;
}

@keyframes cardIn{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}

/* Ligne dorée top */
.card-topline{
  height:3px;
  background:linear-gradient(90deg,var(--gold),var(--gold-lt),var(--gold));
}

.card-body{padding:32px}

/* ── EN-TÊTE ── */
.card-head{text-align:center;margin-bottom:26px}
.card-head h1{
  font-family:'DM Serif Display',serif;
  font-size:24px;font-weight:400;color:var(--texte);
  margin-bottom:5px;
}
.card-head h1 span{color:var(--gold);font-style:italic}
.card-head p{font-size:13px;color:var(--muted)}

/* ── RÉSUMÉ ── */
.resume{
  background:var(--surface);border-radius:var(--r-sm);
  padding:14px 16px;margin-bottom:22px;
  border-left:3px solid var(--gold);
}
.resume-titre{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:10px}
.resume-ligne{
  display:flex;justify-content:space-between;
  font-size:13px;color:var(--muted);padding:3px 0;
}
.resume-ligne strong{color:var(--texte)}

/* ── ALERTE ── */
.alert{
  padding:12px 14px;border-radius:var(--r-sm);
  margin-bottom:18px;font-size:13px;font-weight:500;
  display:flex;align-items:center;gap:9px;
}
.alert-err{background:#FEECEC;color:var(--red);border:1px solid #F5C0C0}

/* ── GROUPES ── */
.grp{margin-bottom:15px}
.grp label{
  display:block;font-size:11px;font-weight:700;
  color:var(--muted);text-transform:uppercase;letter-spacing:.6px;
  margin-bottom:6px;
}
.req{color:var(--gold)}

input[type=number],input[type=tel]{
  width:100%;padding:11px 13px;
  border:1.5px solid var(--border);border-radius:var(--r-sm);
  font-size:14px;color:var(--texte);background:#fff;outline:none;
  transition:border-color .18s,box-shadow .18s;
  font-family:'DM Sans',sans-serif;-webkit-appearance:none;
}
input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(200,146,42,0.10)}

/* ── MODE PAIEMENT ── */
.modes{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.mode-card{
  border:1.5px solid var(--border);border-radius:var(--r-sm);
  padding:14px 12px;cursor:pointer;
  transition:all .2s;text-align:center;
  background:#fff;user-select:none;
}
.mode-card:hover{border-color:var(--gold);background:#FFFBF3}
.mode-card.on{border-color:var(--gold);background:#FFFBF3;box-shadow:0 0 0 3px rgba(200,146,42,0.10)}
.mode-card input{display:none}
.mode-icon{font-size:26px;margin-bottom:6px}
.mode-name{font-size:12.5px;font-weight:700;color:var(--texte)}
.mode-desc{font-size:11px;color:var(--muted);margin-top:2px}

/* Champ MM conditionnel */
#champ-mm{display:none}

/* ── BOUTON ── */
.btn-pay{
  width:100%;padding:14px;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);border:none;border-radius:var(--r-sm);
  font-size:14px;font-weight:700;cursor:pointer;
  font-family:'DM Sans',sans-serif;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:8px;transition:all .22s;
  box-shadow:0 6px 20px rgba(200,146,42,0.28);
}
.btn-pay:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(200,146,42,0.38)}
.btn-pay:active{transform:translateY(0)}

/* ══ REÇU ══ */
.recu{padding:32px}

.recu-check{
  text-align:center;margin-bottom:24px;
  padding-bottom:24px;border-bottom:1px dashed var(--border);
}

.check-icon{
  font-size:52px;display:block;
  margin-bottom:12px;
  animation:pop .4s cubic-bezier(.22,1,.36,1);
}
@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}

.recu-check h2{
  font-family:'DM Serif Display',serif;
  font-size:22px;font-weight:400;color:var(--green);
  margin-bottom:4px;
}
.recu-check p{font-size:13px;color:var(--muted)}

.recu-numero{
  text-align:center;margin-bottom:20px;
  padding:10px;background:var(--surface);
  border-radius:var(--r-sm);
}
.recu-numero span{
  font-size:11px;color:var(--muted);
  text-transform:uppercase;letter-spacing:.8px;
}
.recu-numero strong{
  display:block;font-size:16px;font-weight:700;
  color:var(--texte);margin-top:3px;letter-spacing:1px;
}

.recu-bloc{
  background:var(--surface);border-radius:var(--r-sm);
  padding:16px;margin-bottom:14px;
}
.recu-bloc-titre{
  font-size:10.5px;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.8px;
  margin-bottom:12px;padding-bottom:8px;
  border-bottom:1px solid var(--border);
}
.recu-ligne{
  display:flex;justify-content:space-between;
  font-size:13px;color:var(--muted);padding:4px 0;
}
.recu-ligne strong{color:var(--texte)}

.recu-total{
  background:var(--ink);border-radius:var(--r-sm);
  padding:16px 20px;margin-bottom:20px;
  display:flex;justify-content:space-between;align-items:center;
}
.recu-total span:first-child{
  font-size:13px;color:rgba(255,255,255,0.5);font-weight:600;
}
.recu-total span:last-child{
  font-family:'DM Serif Display',serif;
  font-size:24px;color:var(--gold-lt);
}

.recu-actions{display:flex;flex-direction:column;gap:10px}

.btn-print{
  width:100%;padding:13px;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);border:none;border-radius:var(--r-sm);
  font-size:13.5px;font-weight:700;cursor:pointer;
  font-family:'DM Sans',sans-serif;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:all .22s;box-shadow:0 6px 20px rgba(200,146,42,0.28);
}
.btn-print:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(200,146,42,0.38)}

.btn-back{
  width:100%;padding:12px;
  background:var(--surface);color:var(--texte);
  border:1px solid var(--border);border-radius:var(--r-sm);
  font-size:13px;font-weight:600;cursor:pointer;
  font-family:'DM Sans',sans-serif;text-decoration:none;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:all .18s;
}
.btn-back:hover{background:var(--surface2)}

/* Print */
@media print{
  .navbar,.btn-print,.btn-back{display:none!important}
  body{background:#fff}
  .card{box-shadow:none;border:none;max-width:100%}
  .card-topline{display:none}
}

@media(max-width:520px){
  .card-body,.recu{padding:24px 18px}
  .modes{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-brand">GOMA <em>School</em> Int'l</div>
  <a href="dashboard_eleve.php" class="btn-retour">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Tableau de bord
  </a>
</nav>

<div class="page">
  <div class="card">
    <div class="card-topline"></div>

    <?php if(!$succes): ?>
    <!-- ══ FORMULAIRE ══ -->
    <div class="card-body">

      <div class="card-head">
        <h1>Effectuer un <span>paiement</span></h1>
        <p>Inscription à <?=xss($inscription['domaine_choisi'])?></p>
      </div>

      <!-- Résumé -->
      <div class="resume">
        <div class="resume-titre">Récapitulatif</div>
        <div class="resume-ligne">
          <span>Élève</span>
          <strong><?=xss($nom_eleve)?></strong>
        </div>
        <div class="resume-ligne">
          <span>Domaine</span>
          <strong><?=xss($inscription['domaine_choisi'])?></strong>
        </div>
        <div class="resume-ligne">
          <span>Date inscription</span>
          <strong><?=date('d/m/Y', strtotime($inscription['date_inscription']))?></strong>
        </div>
      </div>

      <?php if($erreur):?>
      <div class="alert alert-err">⚠ <?=xss($erreur)?></div>
      <?php endif;?>

      <form method="POST" action="paiement.php?id=<?=$id_inscription?>">

        <!-- Montant -->
        <div class="grp">
          <label>Montant (FCFA) <span class="req">*</span></label>
          <input type="number" name="montant" min="100" step="500"
                 placeholder="Ex : 15000"
                 value="<?=htmlspecialchars($_POST['montant']??'')?>" required>
        </div>

        <!-- Mode paiement -->
        <div class="grp">
          <label>Mode de paiement <span class="req">*</span></label>
          <div class="modes">
            <label class="mode-card" id="mc-mm" onclick="choisirMode('mobile_money',this)">
              <input type="radio" name="mode_paiement" value="mobile_money">
              <div class="mode-icon">📱</div>
              <div class="mode-name">Mobile Money</div>
              <div class="mode-desc">MTN · Moov</div>
            </label>
            <label class="mode-card" id="mc-esp" onclick="choisirMode('especes',this)">
              <input type="radio" name="mode_paiement" value="especes">
              <div class="mode-icon">💵</div>
              <div class="mode-name">Espèces</div>
              <div class="mode-desc">Paiement direct</div>
            </label>
          </div>
        </div>

        <!-- Numéro MM -->
        <div class="grp" id="champ-mm">
          <label>Numéro Mobile Money <span class="req">*</span></label>
          <input type="tel" name="telephone_mm"
                 placeholder="+229 ..."
                 value="<?=htmlspecialchars($_POST['telephone_mm']??'')?>">
        </div>

        <button type="submit" class="btn-pay">
          Confirmer le paiement
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>

      </form>
    </div>

    <?php else: ?>
    <!-- ══ REÇU ══ -->
    <div class="recu" id="recu-zone">

      <div class="recu-check">
        <span class="check-icon">✅</span>
        <h2>Paiement confirmé !</h2>
        <p><?=date('d/m/Y à H:i', strtotime($recu['date_generation'] ?? 'now'))?></p>
      </div>

      <!-- Numéro reçu -->
      <div class="recu-numero">
        <span>Numéro de reçu</span>
        <strong><?=xss($recu['numero_recu'] ?? '')?></strong>
      </div>

      <!-- Infos élève -->
      <div class="recu-bloc">
        <div class="recu-bloc-titre">Informations élève</div>
        <div class="recu-ligne"><span>Nom</span><strong><?=xss($nom_eleve)?></strong></div>
        <div class="recu-ligne"><span>Domaine</span><strong><?=xss($inscription['domaine_choisi'])?></strong></div>
        <div class="recu-ligne">
          <span>Date inscription</span>
          <strong><?=date('d/m/Y', strtotime($inscription['date_inscription']))?></strong>
        </div>
      </div>

      <!-- Infos paiement -->
      <div class="recu-bloc">
        <div class="recu-bloc-titre">Détails du paiement</div>
        <?php
          $mode = $paiement_info['mode_paiement'] ?? $inscription['mode_paiement'] ?? '';
          $date_p = $paiement_info['date_paiement'] ?? $inscription['date_paiement'] ?? date('Y-m-d');
        ?>
        <div class="recu-ligne">
          <span>Mode</span>
          <strong><?=$mode === 'mobile_money' ? '📱 Mobile Money' : '💵 Espèces'?></strong>
        </div>
        <div class="recu-ligne">
          <span>Date paiement</span>
          <strong><?=date('d/m/Y', strtotime($date_p))?></strong>
        </div>
        <div class="recu-ligne">
          <span>Statut</span>
          <strong style="color:var(--green)">✓ Confirmé</strong>
        </div>
      </div>

      <!-- Total -->
      <div class="recu-total">
        <span>Montant payé</span>
        <span><?=number_format($inscription['montant'] ?? $paiement_info['montant'] ?? 0, 0, ',', ' ')?> FCFA</span>
      </div>

      <!-- École -->
      <div style="text-align:center;margin-bottom:20px">
        <div style="font-size:11px;color:var(--muted);line-height:1.8">
          <strong style="color:var(--texte);font-family:'DM Serif Display',serif;font-size:14px">GOMA School Int'l</strong><br>
          École de musique — République du Bénin<br>
          Misséreté · Donoukin · Tokpota
        </div>
      </div>

      <!-- Actions -->
      <div class="recu-actions">
        <button class="btn-print" onclick="window.print()">
          🖨 Imprimer / Télécharger le reçu
        </button>
        <a href="dashboard_eleve.php" class="btn-back">
          ← Retour au tableau de bord
        </a>
      </div>

    </div>
    <?php endif;?>

  </div>
</div>

<script>
function choisirMode(mode, el) {
  document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('on'));
  el.classList.add('on');
  el.querySelector('input[type=radio]').checked = true;
  document.getElementById('champ-mm').style.display =
    mode === 'mobile_money' ? 'block' : 'none';
}

// Restaurer sélection après erreur
window.onload = function() {
  var mode = '<?=htmlspecialchars($_POST['mode_paiement']??'')?>';
  if (mode === 'mobile_money') {
    document.getElementById('mc-mm').classList.add('on');
    document.getElementById('champ-mm').style.display = 'block';
  } else if (mode === 'especes') {
    document.getElementById('mc-esp').classList.add('on');
  }
};
</script>

</body>
</html>