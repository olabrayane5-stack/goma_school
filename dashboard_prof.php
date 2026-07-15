<?php


session_start();
require 'connexion.php';
require 'securite.php';

auth_requise('professeur');
session_securiser();

$id_prof  = $_SESSION['id'];
$nom_prof = $_SESSION['nom'];

// ── Traitement POST ───────────────────────────────────────

// Ajouter une note
if (isset($_POST['action']) && $_POST['action'] === 'ajouter_note') {
    $valeur        = floatval($_POST['valeur']        ?? 0);
    $commentaire   = trim($_POST['commentaire']       ?? '');
    $id_eleve      = intval($_POST['id_eleve']        ?? 0);
    $id_inscription= intval($_POST['id_inscription']  ?? 0);

    if (valider_note($valeur) && $id_eleve > 0 && $id_inscription > 0) {
        $pdo->prepare("
            INSERT INTO notes (valeur, commentaire, id_eleve, id_professeur, id_inscription)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$valeur, $commentaire, $id_eleve, $id_prof, $id_inscription]);

        $id_note = $pdo->lastInsertId();

        // Nom élève pour la notification
        $nom_el = $pdo->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
        $nom_el->execute([$id_eleve]);
        $nom_eleve = $nom_el->fetchColumn();

        // Notification
        $msg = "Nouvelle note : {$valeur}/20" . ($commentaire ? " — {$commentaire}" : '');
        $pdo->prepare("INSERT INTO notifications (message, id_eleve, id_note) VALUES (?,?,?)")
            ->execute([$msg, $id_eleve, $id_note]);

        $flash = ['type' => 'ok', 'msg' => "Note {$valeur}/20 ajoutée pour {$nom_eleve}."];
    } else {
        $flash = ['type' => 'err', 'msg' => "Valeur de note invalide (0 à 20)."];
    }
}

// Ajouter un cours
if (isset($_POST['action']) && $_POST['action'] === 'ajouter_cours') {
    $intitule    = trim($_POST['intitule']    ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($intitule)) {
        $pdo->prepare("INSERT INTO cours (intitule, description, id_professeur) VALUES (?,?,?)")
            ->execute([$intitule, $description, $id_prof]);
        $flash = ['type' => 'ok', 'msg' => "Cours « {$intitule} » ajouté."];
    }
}

// Ajouter une séance
if (isset($_POST['action']) && $_POST['action'] === 'ajouter_planning') {
    $site       = trim($_POST['site']       ?? '');
    $date_heure = trim($_POST['date_heure'] ?? '');
    $id_cours   = intval($_POST['id_cours'] ?? 0);

    if (!empty($site) && !empty($date_heure)) {
        $pdo->prepare("
            INSERT INTO emplois_du_temps (site_de_formation, date_heure, id_professeur, id_cours)
            VALUES (?,?,?,?)
        ")->execute([$site, $date_heure, $id_prof, $id_cours ?: null]);
        $flash = ['type' => 'ok', 'msg' => "Séance planifiée avec succès."];
    }
}

// ── Récupérations ─────────────────────────────────────────

// Infos professeur
$stmt = $pdo->prepare("
    SELECT u.*, p.specialite
    FROM utilisateurs u JOIN professeurs p ON p.id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id_prof]);
$prof = $stmt->fetch();

// Élèves inscrits avec paiements
$stmt = $pdo->prepare("
    SELECT u.id, u.nom, u.email, u.telephone,
           e.domaine_apprentissage, e.niveau_actuel,
           i.id_inscription, i.domaine_choisi, i.date_inscription,
           p.statut AS statut_paiement, p.montant
    FROM utilisateurs u
    JOIN eleves e ON e.id = u.id
    LEFT JOIN inscriptions i ON i.id_eleve = u.id
    LEFT JOIN paiements p    ON p.id_inscription = i.id_inscription
    ORDER BY u.nom ASC
");
$stmt->execute();
$eleves = $stmt->fetchAll();

// Cours du professeur
$stmt = $pdo->prepare("SELECT * FROM cours WHERE id_professeur = ? ORDER BY created_at DESC");
$stmt->execute([$id_prof]);
$mes_cours = $stmt->fetchAll();

// Planning
$stmt = $pdo->prepare("
    SELECT edt.*, c.intitule AS cours
    FROM emplois_du_temps edt
    LEFT JOIN cours c ON c.id_cours = edt.id_cours
    WHERE edt.id_professeur = ?
    ORDER BY edt.date_heure ASC
");
$stmt->execute([$id_prof]);
$planning = $stmt->fetchAll();

// Dernières notes données
$stmt = $pdo->prepare("
    SELECT n.*, u.nom AS nom_eleve, i.domaine_choisi
    FROM notes n
    JOIN utilisateurs u ON u.id = n.id_eleve
    JOIN inscriptions i ON i.id_inscription = n.id_inscription
    WHERE n.id_professeur = ?
    ORDER BY n.created_at DESC
    LIMIT 15
");
$stmt->execute([$id_prof]);
$notes_donnees = $stmt->fetchAll();

// Stats
$nb_eleves  = count(array_unique(array_column($eleves, 'id')));
$nb_cours   = count($mes_cours);
$nb_seances = count($planning);
$nb_notes   = count($notes_donnees);

$mois_fr = ['Jan','Fév','Mar','Avr','Mai','Jun',
            'Jul','Aoû','Sep','Oct','Nov','Déc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Espace Professeur — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.12);--white:#fff;
  --surface:#F7F6F3;--surface2:#EFEDE8;
  --border:#E4E0D8;--texte:#1A1714;--muted:#8A8378;
  --blue:#3D7FCC;--green:#3A9E6F;--red:#C0392B;
  --purple:#7C5CBF;
  --r:12px;--r-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,0.07);
  --shadow-md:0 8px 32px rgba(0,0,0,0.10);
}

body{font-family:'DM Sans',sans-serif;background:var(--surface);color:var(--texte);min-height:100vh}

/* ── NAVBAR ── */
.navbar{
  background:var(--ink);height:60px;padding:0 28px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 16px rgba(0,0,0,0.25);
}
.navbar-brand{font-family:'DM Serif Display',serif;font-size:18px;color:#fff;letter-spacing:.2px}
.navbar-brand em{color:var(--gold-lt);font-style:normal}
.navbar-right{display:flex;align-items:center;gap:12px}
.prof-badge{
  background:var(--gold-dim);border:1px solid rgba(200,146,42,0.25);
  color:var(--gold-lt);font-size:11px;font-weight:700;
  padding:4px 12px;border-radius:20px;letter-spacing:.3px;
}
.avatar{
  width:34px;height:34px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);font-weight:700;font-size:14px;
  display:flex;align-items:center;justify-content:center;
}
.btn-deco{
  background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);
  color:rgba(255,255,255,0.75);padding:6px 14px;border-radius:var(--r-sm);
  font-size:12px;font-weight:600;text-decoration:none;transition:all .18s;
  font-family:'DM Sans',sans-serif;cursor:pointer;
}
.btn-deco:hover{background:rgba(255,255,255,0.14);color:#fff}

/* ── ONGLETS ── */
.tabs-bar{
  background:#fff;border-bottom:1px solid var(--border);
  display:flex;padding:0 28px;gap:2px;
  position:sticky;top:60px;z-index:90;
  box-shadow:0 2px 8px rgba(0,0,0,0.04);
  overflow-x:auto;
}
.tab{
  padding:15px 18px;font-size:13px;font-weight:600;
  color:var(--muted);cursor:pointer;white-space:nowrap;
  border-bottom:2px solid transparent;
  transition:all .18s;user-select:none;
}
.tab:hover{color:var(--texte)}
.tab.on{color:var(--gold);border-bottom-color:var(--gold)}

/* ── PAGE ── */
.page{max-width:1080px;margin:0 auto;padding:28px 20px}

/* ── BIENVENUE ── */
.bienvenue{
  background:linear-gradient(135deg,var(--ink) 0%,#1e1a14 100%);
  border-radius:var(--r);padding:26px 32px;
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:24px;box-shadow:var(--shadow-md);
  position:relative;overflow:hidden;
}
.bienvenue::before{
  content:'𝄞';position:absolute;right:110px;top:50%;
  transform:translateY(-50%);font-size:140px;
  color:rgba(255,255,255,0.03);font-family:serif;
  pointer-events:none;line-height:1;
}
.bvn-h{font-family:'DM Serif Display',serif;font-size:22px;font-weight:400;color:#fff;margin-bottom:5px}
.bvn-h em{color:var(--gold-lt);font-style:italic}
.bvn-p{font-size:13px;color:rgba(255,255,255,0.4)}
.bvn-icon{font-size:46px;opacity:.9}

/* ── FLASH ── */
.flash{
  padding:12px 16px;border-radius:var(--r-sm);
  margin-bottom:18px;font-size:13px;font-weight:500;
  display:flex;align-items:center;gap:10px;
}
.flash-ok {background:#EDFAF4;color:#2A7A52;border:1px solid #B8E9D0}
.flash-err{background:#FEECEC;color:var(--red);border:1px solid #F5C0C0}

/* ── STATS ── */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.stat{
  background:#fff;border-radius:var(--r);padding:20px;
  border:1px solid var(--border);box-shadow:var(--shadow);
  transition:transform .18s,box-shadow .18s;
}
.stat:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-ico{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:12px}
.stat-val{font-size:26px;font-weight:700;color:var(--texte);line-height:1}
.stat-lbl{font-size:12px;color:var(--muted);margin-top:3px}

/* ── PANNEAUX ── */
.panneau{display:none}
.panneau.on{display:block}

/* ── SECTION ── */
.section{
  background:#fff;border-radius:var(--r);
  border:1px solid var(--border);box-shadow:var(--shadow);
  overflow:hidden;margin-bottom:18px;
}
.section-head{
  padding:16px 20px;border-bottom:1px solid var(--surface2);
  display:flex;align-items:center;justify-content:space-between;
}
.section-head h3{font-size:14px;font-weight:700;color:var(--texte)}

/* ── TABLEAU ── */
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{
  text-align:left;padding:10px 16px;background:var(--surface);
  color:var(--muted);font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.6px;
}
.tbl td{padding:11px 16px;border-bottom:1px solid var(--surface);color:#444;vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#FAFAF8}

/* Badges */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.badge-ok {background:#EDFAF4;color:#2A7A52}
.badge-att{background:#FEF9E7;color:#8A6A00}
.badge-err{background:#FEECEC;color:var(--red)}

/* Note badge */
.note-badge{
  display:inline-flex;align-items:center;justify-content:center;
  background:var(--gold-dim);border:1px solid rgba(200,146,42,0.25);
  color:var(--gold);font-weight:700;font-size:13px;
  padding:3px 10px;border-radius:20px;
  font-family:'DM Serif Display',serif;
}

/* ── FORMULAIRES ── */
.form-grille{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-grille .full{grid-column:1/-1}
.grp{margin-bottom:14px}
.grp label{display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
input[type=text],input[type=number],input[type=datetime-local],select,textarea{
  width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--r-sm);
  font-size:13.5px;color:var(--texte);background:#fff;outline:none;
  transition:border-color .18s,box-shadow .18s;font-family:'DM Sans',sans-serif;-webkit-appearance:none;
}
input:focus,select:focus,textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(200,146,42,0.10)}
textarea{resize:vertical;min-height:80px}

/* ── BOUTONS ── */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  padding:10px 20px;border-radius:var(--r-sm);font-size:13px;font-weight:700;
  cursor:pointer;border:none;font-family:'DM Sans',sans-serif;transition:all .18s;
  text-decoration:none;
}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);box-shadow:0 4px 12px rgba(200,146,42,0.25)}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(200,146,42,0.35)}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:6px}
.btn-eval{background:var(--gold-dim);border:1px solid rgba(200,146,42,0.25);color:var(--gold)}
.btn-eval:hover{background:rgba(200,146,42,0.2)}
.btn-outline{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-outline:hover{background:var(--surface);color:var(--texte)}

/* ── PLANNING ── */
.plan-item{
  display:flex;align-items:center;gap:14px;
  padding:12px 20px;border-bottom:1px solid var(--surface2);
  transition:background .18s;
}
.plan-item:last-child{border-bottom:none}
.plan-item:hover{background:var(--surface)}
.plan-date{
  background:var(--ink);color:#fff;border-radius:var(--r-sm);
  padding:8px 10px;text-align:center;min-width:52px;flex-shrink:0;
}
.plan-date .j{font-size:18px;font-weight:700;line-height:1}
.plan-date .m{font-size:10px;text-transform:uppercase;opacity:.6;margin-top:2px}
.plan-info h4{font-size:13px;font-weight:600}
.plan-info p{font-size:11.5px;color:var(--muted);margin-top:2px}
.plan-site{margin-left:auto;flex-shrink:0;background:var(--gold-dim);border:1px solid rgba(200,146,42,0.22);color:var(--gold);padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}

/* ── MODAL ── */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,0.45);z-index:300;
  align-items:center;justify-content:center;padding:20px;
  backdrop-filter:blur(4px);
}
.modal-overlay.open{display:flex;animation:fadeIn .2s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{
  background:#fff;border-radius:var(--r);
  padding:30px;width:100%;max-width:440px;
  box-shadow:var(--shadow-md);
  animation:slideUp .25s cubic-bezier(.22,1,.36,1);
}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
.modal-head h3{font-size:17px;font-weight:700;color:var(--texte)}
.modal-close{cursor:pointer;color:var(--muted);font-size:22px;line-height:1;background:none;border:none;transition:color .18s}
.modal-close:hover{color:var(--texte)}

.vide{padding:36px 20px;text-align:center;color:var(--muted);font-size:13px}
.vide-icon{font-size:30px;margin-bottom:8px;display:block}

/* ── RESPONSIVE ── */
@media(max-width:900px){.stats{grid-template-columns:1fr 1fr}}
@media(max-width:600px){
  .stats{grid-template-columns:1fr 1fr}
  .bienvenue{flex-direction:column;gap:12px;padding:22px}
  .form-grille{grid-template-columns:1fr}
  .page{padding:20px 14px}
  .navbar{padding:0 16px}
  .tabs-bar{padding:0 14px}
}
</style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <div class="navbar-brand">GOMA <em>School</em> Int'l</div>
  <div class="navbar-right">
    <span class="prof-badge">Professeur</span>
    <div class="avatar"><?=strtoupper(substr($nom_prof,0,1))?></div>
    <a href="deconnexion.php" class="btn-deco">Déconnexion</a>
  </div>
</nav>

<!-- ── ONGLETS ── -->
<div class="tabs-bar">
  <div class="tab on"    onclick="tab(this,'accueil')">🏠 Accueil</div>
  <div class="tab"       onclick="tab(this,'eleves')">👥 Élèves</div>
  <div class="tab"       onclick="tab(this,'cours')">📚 Cours</div>
  <div class="tab"       onclick="tab(this,'planning')">📅 Planning</div>
  <div class="tab"       onclick="tab(this,'evaluations')">⭐ Évaluations</div>
</div>

<!-- ── PAGE ── -->
<div class="page">

  <!-- Bienvenue -->
  <div class="bienvenue">
    <div>
      <div class="bvn-h">Bonjour, Prof. <em><?=xss($nom_prof)?></em> 👋</div>
      <div class="bvn-p">Spécialité : <?=xss($prof['specialite'] ?? 'Non définie')?></div>
    </div>
    <div class="bvn-icon">🎼</div>
  </div>

  <!-- Flash message -->
  <?php if(isset($flash)):?>
  <div class="flash flash-<?=$flash['type']?>">
    <?=$flash['type']==='ok'?'✓':'⚠'?> <?=xss($flash['msg'])?>
  </div>
  <?php endif;?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-ico" style="background:#F0F4FF">👥</div>
      <div class="stat-val"><?=$nb_eleves?></div>
      <div class="stat-lbl">Élève(s)</div>
    </div>
    <div class="stat">
      <div class="stat-ico" style="background:#FEF9E7">📚</div>
      <div class="stat-val"><?=$nb_cours?></div>
      <div class="stat-lbl">Cours défini(s)</div>
    </div>
    <div class="stat">
      <div class="stat-ico" style="background:#EDFAF4">📅</div>
      <div class="stat-val"><?=$nb_seances?></div>
      <div class="stat-lbl">Séance(s)</div>
    </div>
    <div class="stat">
      <div class="stat-ico" style="background:var(--gold-dim)">⭐</div>
      <div class="stat-val"><?=$nb_notes?></div>
      <div class="stat-lbl">Note(s) donnée(s)</div>
    </div>
  </div>

  <!-- ══ ACCUEIL ══ -->
  <div class="panneau on" id="p-accueil">
    <div class="section">
      <div class="section-head"><h3>⭐ Dernières évaluations</h3></div>
      <?php if(count($notes_donnees) > 0):?>
      <table class="tbl">
        <thead><tr><th>Élève</th><th>Domaine</th><th>Note</th><th>Commentaire</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach(array_slice($notes_donnees,0,8) as $n):?>
          <tr>
            <td><strong><?=xss($n['nom_eleve'])?></strong></td>
            <td><?=xss($n['domaine_choisi'])?></td>
            <td><span class="note-badge"><?=$n['valeur']?>/20</span></td>
            <td style="color:var(--muted);font-style:italic"><?=xss($n['commentaire'] ?: '—')?></td>
            <td style="color:var(--muted);font-size:12px"><?=date('d/m/Y', strtotime($n['created_at']))?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php else:?>
        <div class="vide"><span class="vide-icon">⭐</span>Aucune évaluation donnée</div>
      <?php endif;?>
    </div>
  </div>

  <!-- ══ ÉLÈVES ══ -->
  <div class="panneau" id="p-eleves">
    <div class="section">
      <div class="section-head"><h3>👥 Liste des élèves</h3></div>
      <?php if(count($eleves) > 0):?>
      <table class="tbl">
        <thead><tr><th>Nom</th><th>Instrument</th><th>Niveau</th><th>Paiement</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($eleves as $el):?>
          <tr>
            <td>
              <strong><?=xss($el['nom'])?></strong><br>
              <span style="font-size:11px;color:var(--muted)"><?=xss($el['email'])?></span>
            </td>
            <td><?=xss($el['domaine_apprentissage'] ?? '—')?></td>
            <td><?=xss($el['niveau_actuel'] ?? '—')?></td>
            <td>
              <?php
                $s   = $el['statut_paiement'] ?? '';
                $cls = $s === 'confirme' ? 'badge-ok' : ($s === 'echoue' ? 'badge-err' : 'badge-att');
                $lbl = $s === 'confirme' ? 'Payé' : ($s === 'echoue' ? 'Échoué' : 'Non payé');
              ?>
              <span class="badge <?=$cls?>"><?=$lbl?></span>
            </td>
            <td>
              <?php if($el['id_inscription']):?>
              <button class="btn btn-sm btn-eval"
                onclick="ouvrirModal(<?=$el['id']?>,'<?=addslashes(xss($el['nom']))?>', <?=$el['id_inscription']?>)">
                Évaluer
              </button>
              <?php else:?>
                <span style="font-size:11px;color:var(--muted)">Pas inscrit</span>
              <?php endif;?>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php else:?>
        <div class="vide"><span class="vide-icon">👥</span>Aucun élève enregistré</div>
      <?php endif;?>
    </div>
  </div>

  <!-- ══ COURS ══ -->
  <div class="panneau" id="p-cours">
    <!-- Formulaire ajout cours -->
    <div class="section">
      <div class="section-head"><h3>➕ Ajouter un cours</h3></div>
      <div style="padding:20px">
        <form method="POST">
          <input type="hidden" name="action" value="ajouter_cours">
          <div class="form-grille">
            <div class="grp">
              <label>Intitulé <span style="color:var(--gold)">*</span></label>
              <input type="text" name="intitule" placeholder="Ex : Piano avancé" required>
            </div>
            <div class="grp full">
              <label>Description</label>
              <textarea name="description" placeholder="Décrivez le contenu du cours..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-gold">Ajouter le cours</button>
        </form>
      </div>
    </div>

    <!-- Liste cours -->
    <div class="section">
      <div class="section-head"><h3>📚 Mes cours</h3></div>
      <?php if(count($mes_cours) > 0):?>
      <table class="tbl">
        <thead><tr><th>#</th><th>Intitulé</th><th>Description</th><th>Créé le</th></tr></thead>
        <tbody>
          <?php foreach($mes_cours as $c):?>
          <tr>
            <td style="color:var(--muted);font-size:12px"><?=$c['id_cours']?></td>
            <td><strong><?=xss($c['intitule'])?></strong></td>
            <td style="color:var(--muted)"><?=xss($c['description'] ?: '—')?></td>
            <td style="color:var(--muted);font-size:12px"><?=date('d/m/Y', strtotime($c['created_at']))?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php else:?>
        <div class="vide"><span class="vide-icon">📚</span>Aucun cours défini</div>
      <?php endif;?>
    </div>
  </div>

  <!-- ══ PLANNING ══ -->
  <div class="panneau" id="p-planning">
    <!-- Formulaire -->
    <div class="section">
      <div class="section-head"><h3>➕ Planifier une séance</h3></div>
      <div style="padding:20px">
        <form method="POST">
          <input type="hidden" name="action" value="ajouter_planning">
          <div class="form-grille">
            <div class="grp">
              <label>Site <span style="color:var(--gold)">*</span></label>
              <select name="site" required>
                <option value="">— Choisir —</option>
                <option value="Misséreté">Misséreté</option>
                <option value="Donoukin">Donoukin</option>
                <option value="Tokpota">Tokpota</option>
              </select>
            </div>
            <div class="grp">
              <label>Date et heure <span style="color:var(--gold)">*</span></label>
              <input type="datetime-local" name="date_heure" required>
            </div>
            <div class="grp">
              <label>Cours associé</label>
              <select name="id_cours">
                <option value="">— Choisir un cours —</option>
                <?php foreach($mes_cours as $c):?>
                  <option value="<?=$c['id_cours']?>"><?=xss($c['intitule'])?></option>
                <?php endforeach;?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-gold">Planifier</button>
        </form>
      </div>
    </div>

    <!-- Liste planning -->
    <div class="section">
      <div class="section-head"><h3>📅 Toutes les séances</h3></div>
      <?php if(count($planning) > 0):?>
        <?php foreach($planning as $s):
          $dt = new DateTime($s['date_heure']);
        ?>
        <div class="plan-item">
          <div class="plan-date">
            <div class="j"><?=$dt->format('d')?></div>
            <div class="m"><?=$mois_fr[(int)$dt->format('m')-1]?></div>
          </div>
          <div class="plan-info">
            <h4><?=xss($s['cours'] ?? 'Séance libre')?></h4>
            <p><?=$dt->format('H\hi')?></p>
          </div>
          <div class="plan-site"><?=xss($s['site_de_formation'])?></div>
        </div>
        <?php endforeach;?>
      <?php else:?>
        <div class="vide"><span class="vide-icon">📅</span>Aucune séance planifiée</div>
      <?php endif;?>
    </div>
  </div>

  <!-- ══ ÉVALUATIONS ══ -->
  <div class="panneau" id="p-evaluations">
    <div class="section">
      <div class="section-head">
        <h3>⭐ Historique des évaluations</h3>
        <span style="font-size:12px;color:var(--muted)"><?=$nb_notes?> note(s)</span>
      </div>
      <?php if(count($notes_donnees) > 0):?>
      <table class="tbl">
        <thead><tr><th>Élève</th><th>Domaine</th><th>Note</th><th>Commentaire</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach($notes_donnees as $n):?>
          <tr>
            <td><strong><?=xss($n['nom_eleve'])?></strong></td>
            <td><?=xss($n['domaine_choisi'])?></td>
            <td><span class="note-badge"><?=$n['valeur']?>/20</span></td>
            <td style="color:var(--muted);font-style:italic"><?=xss($n['commentaire'] ?: '—')?></td>
            <td style="color:var(--muted);font-size:12px"><?=date('d/m/Y', strtotime($n['created_at']))?></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php else:?>
        <div class="vide"><span class="vide-icon">⭐</span>Aucune évaluation donnée</div>
      <?php endif;?>
    </div>
  </div>

</div><!-- fin page -->

<!-- ══ MODAL ÉVALUATION ══ -->
<div class="modal-overlay" id="modal-overlay">
  <div class="modal">
    <div class="modal-head">
      <h3>⭐ Évaluer : <span id="modal-nom" style="color:var(--gold)"></span></h3>
      <button class="modal-close" onclick="fermerModal()">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action"          value="ajouter_note">
      <input type="hidden" name="id_eleve"        id="m-id-eleve">
      <input type="hidden" name="id_inscription"  id="m-id-inscription">

      <div class="grp">
        <label>Note sur 20 <span style="color:var(--gold)">*</span></label>
        <input type="number" name="valeur" min="0" max="20" step="0.5"
               placeholder="Ex : 15.5" required>
      </div>
      <div class="grp">
        <label>Commentaire <span style="color:var(--muted);font-weight:400;text-transform:none">(optionnel)</span></label>
        <textarea name="commentaire" placeholder="Retour sur la prestation de l'élève..."></textarea>
      </div>
      <button type="submit" class="btn btn-gold" style="width:100%">
        Enregistrer la note
      </button>
    </form>
  </div>
</div>

<script>
// Onglets
function tab(el, id) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
  document.querySelectorAll('.panneau').forEach(p => p.classList.remove('on'));
  el.classList.add('on');
  document.getElementById('p-' + id).classList.add('on');
}

// Modal évaluation
function ouvrirModal(idEleve, nom, idInscription) {
  document.getElementById('modal-nom').textContent       = nom;
  document.getElementById('m-id-eleve').value            = idEleve;
  document.getElementById('m-id-inscription').value      = idInscription;
  document.getElementById('modal-overlay').classList.add('open');
}

function fermerModal() {
  document.getElementById('modal-overlay').classList.remove('open');
}

document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) fermerModal();
});

// Restaurer onglet après rechargement (POST)
<?php if(isset($flash)):?>
window.addEventListener('load', function() {
  var action = '<?=$_POST["action"] ?? ""?>';
  var map = {
    'ajouter_note':    'eleves',
    'ajouter_cours':   'cours',
    'ajouter_planning':'planning'
  };
  if (map[action]) {
    var tabs = document.querySelectorAll('.tab');
    var panneaux = document.querySelectorAll('.panneau');
    tabs.forEach(t => t.classList.remove('on'));
    panneaux.forEach(p => p.classList.remove('on'));
    tabs.forEach(t => { if(t.getAttribute('onclick').includes(map[action])) t.classList.add('on'); });
    document.getElementById('p-' + map[action]).classList.add('on');
  }
});
<?php endif;?>
</script>

</body>
</html>
