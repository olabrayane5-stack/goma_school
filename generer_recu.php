<?php
// ============================================================
//   GOMA SCHOOL INT'L — generer_recu.php
//   Génère et affiche le reçu de paiement (HTML imprimable)
//   Compatible PDF via impression navigateur ou mPDF
//   Auteur : Voltaire SEGNIBO
// ============================================================

session_start();
require 'connexion.php';
require 'securite.php';

auth_requise('eleve');
session_securiser();

$id_eleve = $_SESSION['id'];
$id_recu  = intval($_GET['id'] ?? 0);

if (!$id_recu) {
    header('Location: dashboard_eleve.php');
    exit;
}

// Récupérer toutes les infos du reçu
$stmt = $pdo->prepare("
    SELECT
        r.id_recu,
        r.numero_recu,
        r.date_generation,
        p.montant,
        p.date_paiement,
        p.mode_paiement,
        p.telephone_mm,
        p.statut,
        i.domaine_choisi,
        i.date_inscription,
        u.nom         AS nom_eleve,
        u.email       AS email_eleve,
        u.telephone   AS tel_eleve,
        prof.nom      AS nom_prof,
        pr.specialite AS specialite_prof
    FROM recus r
    JOIN paiements p     ON p.id_paiement    = r.id_paiement
    JOIN inscriptions i  ON i.id_inscription = p.id_inscription
    JOIN utilisateurs u  ON u.id             = p.id_eleve
    LEFT JOIN notes n    ON n.id_inscription = i.id_inscription
    LEFT JOIN utilisateurs prof ON prof.id   = n.id_professeur
    LEFT JOIN professeurs pr    ON pr.id     = n.id_professeur
    WHERE r.id_recu = ? AND p.id_eleve = ?
    LIMIT 1
");
$stmt->execute([$id_recu, $id_eleve]);
$data = $stmt->fetch();

if (!$data) {
    header('Location: dashboard_eleve.php');
    exit;
}

// Formatages
$numero      = xss($data['numero_recu']);
$montant_fmt = number_format($data['montant'], 0, ',', ' ') . ' FCFA';
$date_paie   = date('d/m/Y', strtotime($data['date_paiement']));
$date_gen    = date('d/m/Y à H:i', strtotime($data['date_generation']));
$mode_label  = $data['mode_paiement'] === 'mobile_money' ? 'Mobile Money' : 'Espèces';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reçu <?=$numero?> — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.10);--white:#fff;
  --surface:#F7F6F3;--surface2:#EFEDE8;
  --border:#E4E0D8;--texte:#1A1714;--muted:#8A8378;
  --green:#3A9E6F;--r:12px;--r-sm:8px;
}

body{
  font-family:'DM Sans',sans-serif;
  background:var(--surface);
  min-height:100vh;
  display:flex;flex-direction:column;
}

/* ── NAVBAR (masquée à l'impression) ── */
.navbar{
  background:var(--ink);height:60px;padding:0 28px;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 2px 16px rgba(0,0,0,0.25);
}
.navbar-brand{font-family:'DM Serif Display',serif;font-size:18px;color:#fff}
.navbar-brand em{color:var(--gold-lt);font-style:normal}
.nav-actions{display:flex;gap:10px;align-items:center}

.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:var(--r-sm);
  font-size:12.5px;font-weight:700;cursor:pointer;
  border:none;font-family:'DM Sans',sans-serif;
  text-decoration:none;transition:all .18s;
}
.btn-gold{
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);box-shadow:0 4px 12px rgba(200,146,42,0.25);
}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(200,146,42,0.35)}
.btn-outline{
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  color:rgba(255,255,255,0.75);
}
.btn-outline:hover{background:rgba(255,255,255,0.14);color:#fff}

/* ── PAGE ── */
.page{
  flex:1;display:flex;align-items:flex-start;
  justify-content:center;padding:32px 16px;
}

/* ── REÇU ── */
.recu{
  width:100%;max-width:620px;
  background:#fff;
  border-radius:var(--r);
  border:1px solid var(--border);
  box-shadow:0 8px 40px rgba(0,0,0,0.10);
  overflow:hidden;
  animation:cardIn .45s cubic-bezier(.22,1,.36,1) both;
}

@keyframes cardIn{
  from{opacity:0;transform:translateY(16px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── EN-TÊTE DU REÇU ── */
.recu-header{
  background:var(--ink);
  padding:32px 36px 28px;
  position:relative;
  overflow:hidden;
}

.recu-header::before{
  content:'𝄞';
  position:absolute;right:24px;top:50%;
  transform:translateY(-50%);
  font-size:120px;color:rgba(255,255,255,0.04);
  font-family:serif;line-height:1;pointer-events:none;
}

.header-top{
  display:flex;justify-content:space-between;
  align-items:flex-start;
}

.header-marque h1{
  font-family:'DM Serif Display',serif;
  font-size:24px;font-weight:400;color:#fff;
  margin-bottom:4px;
}
.header-marque h1 em{color:var(--gold-lt);font-style:normal}
.header-marque p{font-size:12px;color:rgba(255,255,255,0.38);line-height:1.6}

.header-recu{text-align:right}
.header-recu .label{
  font-size:10px;font-weight:700;
  color:rgba(255,255,255,0.35);
  text-transform:uppercase;letter-spacing:1.2px;
  margin-bottom:5px;
}
.header-recu .numero{
  font-family:'DM Serif Display',serif;
  font-size:18px;color:var(--gold-lt);
  letter-spacing:0.5px;
}
.header-recu .date-gen{
  font-size:11px;color:rgba(255,255,255,0.28);
  margin-top:4px;
}

/* Bannière statut */
.statut-banner{
  background:linear-gradient(135deg,#2A7A52,#3A9E6F);
  padding:10px 36px;
  display:flex;align-items:center;gap:10px;
  font-size:13px;font-weight:700;color:#fff;
  letter-spacing:0.5px;
}

/* ── CORPS DU REÇU ── */
.recu-body{padding:32px 36px}

/* Blocs info */
.info-bloc{
  background:var(--surface);
  border-radius:var(--r-sm);
  padding:18px;
  margin-bottom:16px;
}

.bloc-titre{
  font-size:10px;font-weight:700;
  color:var(--muted);text-transform:uppercase;
  letter-spacing:1px;margin-bottom:12px;
  padding-bottom:8px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:7px;
}

.info-ligne{
  display:flex;justify-content:space-between;
  align-items:center;
  font-size:13px;padding:5px 0;
  border-bottom:1px solid rgba(228,224,216,0.5);
  color:var(--muted);
}
.info-ligne:last-child{border-bottom:none}
.info-ligne strong{color:var(--texte);font-weight:600}

/* Grille 2 col */
.deux-cols{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}

/* Total */
.total-bloc{
  background:var(--ink);
  border-radius:var(--r-sm);
  padding:20px 24px;
  display:flex;justify-content:space-between;
  align-items:center;
  margin-bottom:24px;
}
.total-label{
  font-size:13px;color:rgba(255,255,255,0.45);
  font-weight:600;
}
.total-label small{
  display:block;font-size:10px;
  color:rgba(255,255,255,0.22);
  margin-top:2px;font-weight:400;
}
.total-montant{
  font-family:'DM Serif Display',serif;
  font-size:28px;color:var(--gold-lt);
  letter-spacing:-0.3px;
}

/* Signature / cachet */
.recu-footer{
  border-top:1px dashed var(--border);
  padding-top:20px;
  display:flex;justify-content:space-between;
  align-items:center;
}

.footer-infos p{
  font-size:11px;color:var(--muted);
  line-height:1.7;
}
.footer-infos strong{color:var(--texte)}

.cachet{
  width:88px;height:88px;
  border:2.5px solid var(--gold);
  border-radius:50%;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  text-align:center;
  flex-shrink:0;
}
.cachet-txt{
  font-size:9px;font-weight:700;
  color:var(--gold);text-transform:uppercase;
  letter-spacing:0.5px;line-height:1.4;
}
.cachet-check{
  font-size:18px;margin-top:3px;
  color:var(--gold);font-family:'DM Serif Display',serif;
}

/* Note légale */
.note-legale{
  margin-top:20px;padding:12px 14px;
  background:#FFFBF3;border-radius:var(--r-sm);
  border-left:3px solid var(--gold);
  font-size:11px;color:var(--muted);line-height:1.65;
}
.note-legale strong{color:var(--texte)}

/* ── ACTIONS (masquées print) ── */
.actions-bar{
  background:#fff;border-top:1px solid var(--border);
  padding:16px 36px;
  display:flex;gap:10px;justify-content:flex-end;
}

/* ── IMPRESSION ── */
@media print{
  .navbar,.actions-bar{display:none!important}
  body{background:#fff}
  .page{padding:0;background:#fff}
  .recu{
    box-shadow:none;border:none;
    border-radius:0;max-width:100%;
    animation:none;
  }
  .recu-body{padding:24px 28px}
  .recu-header{padding:24px 28px 20px}
}

@media(max-width:520px){
  .recu-header{padding:24px 20px 20px}
  .header-top{flex-direction:column;gap:14px}
  .header-recu{text-align:left}
  .recu-body{padding:22px 18px}
  .deux-cols{grid-template-columns:1fr}
  .recu-footer{flex-direction:column;gap:16px;text-align:center}
  .actions-bar{padding:14px 18px;flex-direction:column}
  .actions-bar .btn{justify-content:center}
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-brand">GOMA <em>School</em> Int'l</div>
  <div class="nav-actions">
    <button class="btn btn-gold" onclick="window.print()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Imprimer
    </button>
    <a href="dashboard_eleve.php" class="btn btn-outline">← Tableau de bord</a>
  </div>
</nav>

<div class="page">
  <div class="recu">

    <!-- ── EN-TÊTE ── -->
    <div class="recu-header">
      <div class="header-top">
        <div class="header-marque">
          <h1>GOMA <em>School</em> Int'l</h1>
          <p>École de musique — République du Bénin<br>
             Misséreté · Donoukin · Tokpota</p>
        </div>
        <div class="header-recu">
          <div class="label">Reçu de paiement</div>
          <div class="numero"><?=$numero?></div>
          <div class="date-gen">Émis le <?=$date_gen?></div>
        </div>
      </div>
    </div>

    <!-- Statut -->
    <div class="statut-banner">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
      PAIEMENT CONFIRMÉ — Reçu officiel
    </div>

    <!-- ── CORPS ── -->
    <div class="recu-body">

      <!-- Infos élève + paiement côte à côte -->
      <div class="deux-cols">

        <div class="info-bloc">
          <div class="bloc-titre">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Informations élève
          </div>
          <div class="info-ligne">
            <span>Nom</span>
            <strong><?=xss($data['nom_eleve'])?></strong>
          </div>
          <div class="info-ligne">
            <span>Email</span>
            <strong style="font-size:12px"><?=xss($data['email_eleve'])?></strong>
          </div>
          <div class="info-ligne">
            <span>Téléphone</span>
            <strong><?=xss($data['tel_eleve'] ?: '—')?></strong>
          </div>
        </div>

        <div class="info-bloc">
          <div class="bloc-titre">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Détails paiement
          </div>
          <div class="info-ligne">
            <span>Date</span>
            <strong><?=$date_paie?></strong>
          </div>
          <div class="info-ligne">
            <span>Mode</span>
            <strong><?=$mode_label?></strong>
          </div>
          <?php if($data['telephone_mm']):?>
          <div class="info-ligne">
            <span>Numéro MM</span>
            <strong><?=xss($data['telephone_mm'])?></strong>
          </div>
          <?php endif;?>
        </div>

      </div>

      <!-- Infos inscription -->
      <div class="info-bloc">
        <div class="bloc-titre">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Détails de l'inscription
        </div>
        <div class="info-ligne">
          <span>Domaine choisi</span>
          <strong><?=xss($data['domaine_choisi'])?></strong>
        </div>
        <div class="info-ligne">
          <span>Date d'inscription</span>
          <strong><?=date('d/m/Y', strtotime($data['date_inscription']))?></strong>
        </div>
        <div class="info-ligne">
          <span>Statut</span>
          <strong style="color:var(--green)">✓ Confirmée et payée</strong>
        </div>
      </div>

      <!-- TOTAL -->
      <div class="total-bloc">
        <div class="total-label">
          Montant total payé
          <small>Franc CFA Ouest-Africain (XOF)</small>
        </div>
        <div class="total-montant"><?=$montant_fmt?></div>
      </div>

      <!-- Pied de reçu -->
      <div class="recu-footer">
        <div class="footer-infos">
          <p>
            <strong>GOMA School Int'l</strong><br>
            École de musique — République du Bénin<br>
            N° reçu : <strong><?=$numero?></strong><br>
            Émis le : <strong><?=$date_gen?></strong>
          </p>
          <p style="margin-top:8px;font-size:10px;color:#bbb">
            Document généré automatiquement par le système GOMA School.
          </p>
        </div>
        <div class="cachet">
          <div class="cachet-txt">GOMA<br>School<br>Int'l</div>
          <div class="cachet-check">✓</div>
        </div>
      </div>

      <!-- Note légale -->
      <div class="note-legale">
        <strong>Règles importantes :</strong> Ce reçu constitue la preuve officielle
        de votre paiement à GOMA School Int'l. Conservez-le précieusement.
        Un élève doit effectuer son paiement avant d'accéder à certaines ressources.
        Pour toute réclamation, contactez l'administration muni de ce document.
      </div>

    </div><!-- fin recu-body -->

    <!-- Actions -->
    <div class="actions-bar">
      <a href="dashboard_eleve.php" class="btn btn-outline" style="color:var(--muted);border-color:var(--border)">
        ← Retour
      </a>
      <button class="btn btn-gold" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimer / Télécharger PDF
      </button>
    </div>

  </div>
</div>

</body>
</html>