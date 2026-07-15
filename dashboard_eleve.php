<?php
// ============================================================
//   GOMA SCHOOL INT'L — dashboard_eleve.php
//   Tableau de bord de l'élève
// ============================================================

session_start();
require 'connexion.php';
require 'securite.php';

// Protection : élève connecté uniquement
auth_requise('eleve');
session_securiser();

$id_eleve  = $_SESSION['id'];
$nom_eleve = $_SESSION['nom'];

// ── Infos élève ───────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT u.*, e.domaine_apprentissage, e.niveau_actuel
    FROM utilisateurs u
    JOIN eleves e ON e.id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id_eleve]);
$eleve = $stmt->fetch();

// ── Inscriptions + paiements ──────────────────────────────
$stmt = $pdo->prepare("
    SELECT i.*,
           p.id_paiement, p.montant, p.statut AS statut_paiement,
           p.mode_paiement, p.date_paiement,
           r.id_recu, r.numero_recu
    FROM inscriptions i
    LEFT JOIN paiements p ON p.id_inscription = i.id_inscription
    LEFT JOIN recus r     ON r.id_paiement    = p.id_paiement
    WHERE i.id_eleve = ?
    ORDER BY i.date_inscription DESC
");
$stmt->execute([$id_eleve]);
$inscriptions = $stmt->fetchAll();

// ── Notes ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT n.*, u.nom AS nom_professeur, i.domaine_choisi
    FROM notes n
    JOIN utilisateurs u  ON u.id               = n.id_professeur
    JOIN inscriptions i  ON i.id_inscription   = n.id_inscription
    WHERE n.id_eleve = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$id_eleve]);
$notes = $stmt->fetchAll();

// ── Emploi du temps ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT edt.*, c.intitule AS cours, u.nom AS professeur
    FROM emplois_du_temps edt
    JOIN cours c         ON c.id_cours     = edt.id_cours
    JOIN utilisateurs u  ON u.id           = edt.id_professeur
    WHERE edt.date_heure >= NOW()
    ORDER BY edt.date_heure ASC
    LIMIT 10
");
$stmt->execute();
$planning = $stmt->fetchAll();

// ── Notifications non lues ────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE id_eleve = ? AND lu = 0
    ORDER BY created_at DESC
");
$stmt->execute([$id_eleve]);
$notifications = $stmt->fetchAll();
$nb_notifs     = count($notifications);

// Marquer comme lues
if ($nb_notifs > 0) {
    $pdo->prepare("UPDATE notifications SET lu = 1 WHERE id_eleve = ?")
        ->execute([$id_eleve]);
}

// ── Moyenne générale ──────────────────────────────────────
$moyenne = 0;
if (count($notes) > 0) {
    $moyenne = round(
        array_sum(array_column($notes, 'valeur')) / count($notes),
        2
    );
}

// ── Mois FR ───────────────────────────────────────────────
$mois_fr = ['Jan','Fév','Mar','Avr','Mai','Jun',
            'Jul','Aoû','Sep','Oct','Nov','Déc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mon Espace — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.12);--white:#fff;
  --surface:#F7F6F3;--surface2:#EFEDE8;
  --border:#E4E0D8;--texte:#1A1714;--muted:#8A8378;
  --blue:#3D7FCC;--green:#3A9E6F;--red:#C0392B;
  --r:12px;--r-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,0.07);
  --shadow-md:0 8px 32px rgba(0,0,0,0.10);
}

body{font-family:'DM Sans',sans-serif;background:var(--surface);color:var(--texte);min-height:100vh}

/* ── NAVBAR ── */
.navbar{
  background:var(--ink);
  height:60px;padding:0 28px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 16px rgba(0,0,0,0.25);
}

.navbar-brand{
  font-family:'DM Serif Display',serif;
  font-size:18px;color:var(--white);letter-spacing:.2px;
}
.navbar-brand em{color:var(--gold-lt);font-style:normal}

.navbar-right{display:flex;align-items:center;gap:14px}

.notif-btn{
  position:relative;cursor:pointer;
  width:36px;height:36px;border-radius:50%;
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  display:flex;align-items:center;justify-content:center;
  transition:background .18s;
}
.notif-btn:hover{background:rgba(255,255,255,0.14)}
.notif-btn svg{width:18px;height:18px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round}

.notif-badge{
  position:absolute;top:-3px;right:-3px;
  width:17px;height:17px;border-radius:50%;
  background:#E05252;color:#fff;
  font-size:9px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  border:2px solid var(--ink);
}

.avatar{
  width:34px;height:34px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);font-weight:700;font-size:14px;
  display:flex;align-items:center;justify-content:center;
}

.btn-deco{
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  color:rgba(255,255,255,0.75);
  padding:6px 14px;border-radius:var(--r-sm);
  font-size:12px;font-weight:600;
  text-decoration:none;transition:all .18s;
  font-family:'DM Sans',sans-serif;cursor:pointer;
}
.btn-deco:hover{background:rgba(255,255,255,0.14);color:#fff}

/* ── POPUP NOTIFS ── */
.notif-popup{
  display:none;position:fixed;
  top:68px;right:20px;z-index:200;
  background:#fff;border-radius:var(--r);
  border:1px solid var(--border);
  box-shadow:var(--shadow-md);
  width:300px;overflow:hidden;
}
.notif-popup.open{display:block;animation:slideDown .2s ease}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.notif-head{
  background:var(--ink);padding:12px 16px;
  font-size:13px;font-weight:600;color:#fff;
}
.notif-item{
  padding:12px 16px;border-bottom:1px solid var(--surface);
  font-size:12.5px;color:var(--texte);line-height:1.5;
}
.notif-item:last-child{border-bottom:none}
.notif-vide{padding:20px;text-align:center;color:var(--muted);font-size:13px}

/* ── CONTENU ── */
.page{max-width:1080px;margin:0 auto;padding:28px 20px}

/* ── BIENVENUE ── */
.bienvenue{
  background:linear-gradient(135deg,var(--ink) 0%,#1e1a14 100%);
  border-radius:var(--r);padding:28px 32px;
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:24px;
  box-shadow:var(--shadow-md);
  position:relative;overflow:hidden;
}
.bienvenue::before{
  content:'𝄞';
  position:absolute;right:120px;top:50%;transform:translateY(-50%);
  font-size:140px;color:rgba(255,255,255,0.03);
  font-family:serif;pointer-events:none;line-height:1;
}
.bvn-texte h2{
  font-family:'DM Serif Display',serif;
  font-size:22px;font-weight:400;color:#fff;margin-bottom:6px;
}
.bvn-texte h2 span{color:var(--gold-lt);font-style:italic}
.bvn-texte p{font-size:13px;color:rgba(255,255,255,0.42)}
.bvn-tags{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.bvn-tag{
  background:rgba(255,255,255,0.07);
  border:1px solid rgba(255,255,255,0.12);
  color:rgba(255,255,255,0.6);
  font-size:11px;font-weight:600;
  padding:4px 12px;border-radius:20px;
}
.bvn-tag.gold{background:var(--gold-dim);border-color:rgba(200,146,42,0.3);color:var(--gold-lt)}
.bvn-icon{font-size:48px;opacity:.9}

/* ── STATS ── */
.stats{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:14px;margin-bottom:24px;
}
.stat{
  background:#fff;border-radius:var(--r);
  padding:20px;border:1px solid var(--border);
  box-shadow:var(--shadow);
  transition:transform .18s,box-shadow .18s;
}
.stat:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.stat-ico{
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:20px;
}
.stat-val{font-size:26px;font-weight:700;color:var(--texte);line-height:1}
.stat-lbl{font-size:12px;color:var(--muted);margin-top:3px}

/* ── GRILLE ── */
.grille{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}

/* ── SECTION ── */
.section{
  background:#fff;border-radius:var(--r);
  border:1px solid var(--border);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.section-head{
  padding:16px 20px;
  border-bottom:1px solid var(--surface2);
  display:flex;align-items:center;justify-content:space-between;
}
.section-head h3{
  font-size:14px;font-weight:700;color:var(--texte);
  display:flex;align-items:center;gap:8px;
}
.section-body{padding:0}

/* ── TABLEAU ── */
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{
  text-align:left;padding:10px 16px;
  background:var(--surface);color:var(--muted);
  font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.6px;
}
.tbl td{
  padding:11px 16px;
  border-bottom:1px solid var(--surface);
  color:#444;vertical-align:middle;
}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#FAFAF8}

/* Badges statut */
.badge{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;
  font-size:11px;font-weight:700;white-space:nowrap;
}
.badge-ok  {background:#EDFAF4;color:#2A7A52}
.badge-att {background:#FEF9E7;color:#8A6A00}
.badge-err {background:#FEECEC;color:var(--red)}

/* ── NOTES ── */
.note-item{
  padding:14px 20px;
  border-bottom:1px solid var(--surface2);
}
.note-item:last-child{border-bottom:none}
.note-row{display:flex;align-items:center;justify-content:space-between}
.note-info h4{font-size:13.5px;font-weight:600;color:var(--texte)}
.note-info p{font-size:11.5px;color:var(--muted);margin-top:2px}
.note-val{
  font-family:'DM Serif Display',serif;
  font-size:22px;font-weight:400;color:var(--gold);
}
.note-val span{font-size:13px;color:var(--muted);font-family:'DM Sans',sans-serif}
.note-comment{
  margin-top:8px;padding:8px 12px;
  background:var(--surface);border-radius:var(--r-sm);
  border-left:3px solid var(--gold);
  font-size:12px;color:var(--muted);font-style:italic;
  line-height:1.5;
}

/* ── PLANNING ── */
.planning-item{
  display:flex;align-items:center;gap:14px;
  padding:12px 20px;
  border-bottom:1px solid var(--surface2);
  transition:background .18s;
}
.planning-item:last-child{border-bottom:none}
.planning-item:hover{background:var(--surface)}
.plan-date{
  background:var(--ink);color:#fff;
  border-radius:var(--r-sm);
  padding:8px 10px;text-align:center;
  min-width:52px;flex-shrink:0;
}
.plan-date .j{font-size:18px;font-weight:700;line-height:1}
.plan-date .m{font-size:10px;text-transform:uppercase;opacity:.6;margin-top:2px}
.plan-info h4{font-size:13px;font-weight:600}
.plan-info p{font-size:11.5px;color:var(--muted);margin-top:2px}
.plan-site{
  margin-left:auto;flex-shrink:0;
  background:var(--gold-dim);
  border:1px solid rgba(200,146,42,0.22);
  color:var(--gold);
  padding:4px 10px;border-radius:20px;
  font-size:11px;font-weight:700;
}

/* ── BOUTONS ── */
.btn-sm{
  display:inline-flex;align-items:center;gap:5px;
  padding:6px 12px;border-radius:var(--r-sm);
  font-size:12px;font-weight:600;
  text-decoration:none;cursor:pointer;border:none;
  font-family:'DM Sans',sans-serif;transition:all .18s;
}
.btn-gold{
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);box-shadow:0 3px 10px rgba(200,146,42,0.25);
}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(200,146,42,0.35)}
.btn-outline{
  background:transparent;color:var(--muted);
  border:1px solid var(--border);
}
.btn-outline:hover{background:var(--surface);color:var(--texte)}

.vide{
  padding:40px 20px;text-align:center;
  color:var(--muted);font-size:13px;
}
.vide-icon{font-size:32px;margin-bottom:8px;display:block}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .stats{grid-template-columns:1fr 1fr}
  .grille{grid-template-columns:1fr}
}
@media(max-width:560px){
  .stats{grid-template-columns:1fr 1fr}
  .bienvenue{flex-direction:column;gap:14px;padding:22px}
  .page{padding:20px 14px}
  .navbar{padding:0 16px}
}
</style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <div class="navbar-brand">GOMA <em>School</em> Int'l</div>
  <div class="navbar-right">

    <!-- Cloche notifications -->
    <div class="notif-btn" onclick="toggleNotifs()" aria-label="Notifications">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <?php if($nb_notifs > 0):?>
        <span class="notif-badge"><?=$nb_notifs?></span>
      <?php endif;?>
    </div>

    <div class="avatar"><?=strtoupper(substr($nom_eleve,0,1))?></div>
    <a href="deconnexion.php" class="btn-deco">Déconnexion</a>
  </div>
</nav>

<!-- Popup notifications -->
<div class="notif-popup" id="notif-popup">
  <div class="notif-head">🔔 Notifications</div>
  <?php if(count($notifications) > 0):?>
    <?php foreach($notifications as $n):?>
      <div class="notif-item"><?=xss($n['message'])?></div>
    <?php endforeach;?>
  <?php else:?>
    <div class="notif-vide">Aucune nouvelle notification</div>
  <?php endif;?>
</div>

<!-- ── PAGE ── -->
<div class="page">

  <!-- Bienvenue -->
  <div class="bienvenue">
    <div class="bvn-texte">
      <h2>Bonjour, <span><?=xss($nom_eleve)?></span> 👋</h2>
      <p>Bienvenue dans votre espace GOMA School Int'l</p>
      <div class="bvn-tags">
        <span class="bvn-tag gold">🎵 <?=xss($eleve['domaine_apprentissage'] ?? 'Non défini')?></span>
        <span class="bvn-tag">📊 <?=xss($eleve['niveau_actuel'] ?? 'Non défini')?></span>
      </div>
    </div>
    <div class="bvn-icon">🎹</div>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-top">
        <div class="stat-ico" style="background:#F0F4FF">📋</div>
      </div>
      <div class="stat-val"><?=count($inscriptions)?></div>
      <div class="stat-lbl">Inscription(s)</div>
    </div>
    <div class="stat">
      <div class="stat-top">
        <div class="stat-ico" style="background:#FEF9E7">⭐</div>
      </div>
      <div class="stat-val"><?=$moyenne > 0 ? $moyenne : '—'?></div>
      <div class="stat-lbl">Moyenne / 20</div>
    </div>
    <div class="stat">
      <div class="stat-top">
        <div class="stat-ico" style="background:#EDFAF4">📅</div>
      </div>
      <div class="stat-val"><?=count($planning)?></div>
      <div class="stat-lbl">Séance(s) à venir</div>
    </div>
    <div class="stat">
      <div class="stat-top">
        <div class="stat-ico" style="background:#FEECEC">🔔</div>
      </div>
      <div class="stat-val"><?=$nb_notifs?></div>
      <div class="stat-lbl">Notification(s)</div>
    </div>
  </div>

  <!-- Grille principale -->
  <div class="grille">

    <!-- Inscriptions & Paiements -->
    <div class="section">
      <div class="section-head">
        <h3>📋 Inscriptions & Paiements</h3>
      </div>
      <div class="section-body">
        <?php if(count($inscriptions) > 0):?>
        <table class="tbl">
          <thead>
            <tr>
              <th>Domaine</th>
              <th>Montant</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($inscriptions as $ins):?>
            <tr>
              <td><strong><?=xss($ins['domaine_choisi'])?></strong><br>
                <span style="font-size:11px;color:var(--muted)">
                  <?=date('d/m/Y', strtotime($ins['date_inscription']))?>
                </span>
              </td>
              <td>
                <?php if($ins['montant']):?>
                  <strong><?=number_format($ins['montant'],0,',',' ')?></strong>
                  <span style="font-size:11px;color:var(--muted)"> FCFA</span>
                <?php else:?>
                  <span style="color:var(--muted);font-size:12px">—</span>
                <?php endif;?>
              </td>
              <td>
                <?php
                  $s = $ins['statut_paiement'] ?? '';
                  $cls = $s === 'confirme' ? 'badge-ok' : ($s === 'echoue' ? 'badge-err' : 'badge-att');
                  $lbl = $s === 'confirme' ? 'Payé' : ($s === 'echoue' ? 'Échoué' : 'Non payé');
                ?>
                <span class="badge <?=$cls?>"><?=$lbl?></span>
              </td>
              <td>
                <?php if($ins['statut_paiement'] !== 'confirme'):?>
                  <a href="paiement.php?id=<?=$ins['id_inscription']?>" class="btn-sm btn-gold">Payer</a>
                <?php elseif($ins['id_recu']):?>
                  <a href="generer_recu.php?id=<?=$ins['id_recu']?>" class="btn-sm btn-outline">Reçu</a>
                <?php endif;?>
              </td>
            </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        <?php else:?>
          <div class="vide"><span class="vide-icon">📋</span>Aucune inscription</div>
        <?php endif;?>
      </div>
    </div>

    <!-- Notes -->
    <div class="section">
      <div class="section-head">
        <h3>⭐ Mes notes</h3>
        <?php if($moyenne > 0):?>
          <span style="font-size:12px;color:var(--muted)">Moy. : <strong style="color:var(--gold)"><?=$moyenne?>/20</strong></span>
        <?php endif;?>
      </div>
      <div class="section-body">
        <?php if(count($notes) > 0):?>
          <?php foreach($notes as $note):?>
          <div class="note-item">
            <div class="note-row">
              <div class="note-info">
                <h4><?=xss($note['domaine_choisi'])?></h4>
                <p>Prof. <?=xss($note['nom_professeur'])?> · <?=date('d/m/Y', strtotime($note['created_at']))?></p>
              </div>
              <div class="note-val"><?=$note['valeur']?><span>/20</span></div>
            </div>
            <?php if($note['commentaire']):?>
              <div class="note-comment">💬 <?=xss($note['commentaire'])?></div>
            <?php endif;?>
          </div>
          <?php endforeach;?>
        <?php else:?>
          <div class="vide"><span class="vide-icon">⭐</span>Aucune note disponible</div>
        <?php endif;?>
      </div>
    </div>

  </div>

  <!-- Emploi du temps -->
  <div class="section">
    <div class="section-head">
      <h3>📅 Emploi du temps à venir</h3>
    </div>
    <div class="section-body">
      <?php if(count($planning) > 0):?>
        <?php foreach($planning as $s):
          $dt = new DateTime($s['date_heure']);
        ?>
        <div class="planning-item">
          <div class="plan-date">
            <div class="j"><?=$dt->format('d')?></div>
            <div class="m"><?=$mois_fr[(int)$dt->format('m')-1]?></div>
          </div>
          <div class="plan-info">
            <h4><?=xss($s['cours'])?></h4>
            <p><?=$dt->format('H\hi')?> · Prof. <?=xss($s['professeur'])?></p>
          </div>
          <div class="plan-site"><?=xss($s['site_de_formation'])?></div>
        </div>
        <?php endforeach;?>
      <?php else:?>
        <div class="vide"><span class="vide-icon">📅</span>Aucune séance planifiée</div>
      <?php endif;?>
    </div>
  </div>

</div><!-- fin page -->

<script>
function toggleNotifs() {
  var p = document.getElementById('notif-popup');
  p.classList.toggle('open');
}
document.addEventListener('click', function(e) {
  var p = document.getElementById('notif-popup');
  var b = document.querySelector('.notif-btn');
  if (!p.contains(e.target) && !b.contains(e.target)) {
    p.classList.remove('open');
  }
});
</script>

</body>
</html>
