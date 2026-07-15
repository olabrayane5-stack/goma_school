<?php
// ============================================================
//   GOMA SCHOOL INT'L — reset_mot_de_passe.php
//   Étape 2 : L'utilisateur saisit son nouveau mot de passe
// ============================================================

session_start();
require 'connexion.php';

$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$erreur  = '';
$succes  = false;
$valide  = false;
$user    = null;

// Vérifier le token
if (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.nom, u.email
        FROM reset_mdp r
        JOIN utilisateurs u ON u.id = r.id_user
        WHERE r.token = ?
          AND r.utilise = 0
          AND r.expire_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valide = true;
        $user   = $reset;
    } else {
        $erreur = "Ce lien est invalide ou a expiré. Faites une nouvelle demande.";
    }
}

// Traitement du nouveau mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valide) {
    $mdp  = $_POST['mot_de_passe']  ?? '';
    $conf = $_POST['confirmation']  ?? '';

    if (empty($mdp)) {
        $erreur = "Veuillez saisir un nouveau mot de passe.";
    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mdp !== $conf) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // Mettre à jour le mot de passe
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?")
            ->execute([$hash, $user['id_user']]);

        // Invalider le token
        $pdo->prepare("UPDATE reset_mdp SET utilise = 1 WHERE token = ?")
            ->execute([$token]);

        $succes = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Nouveau mot de passe — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.16);--white:#fff;
  --mist:rgba(255,255,255,0.07);--mist2:rgba(255,255,255,0.12);
  --border:rgba(255,255,255,0.13);--r:14px;--r-sm:10px;
  --shadow:0 40px 120px rgba(0,0,0,0.65);
}
body{font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px;position:relative;overflow:hidden}
.bg{position:fixed;inset:0;z-index:0;background:url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1800&q=85') center 30%/cover no-repeat}
.bg-darken{position:fixed;inset:0;z-index:1;background:linear-gradient(160deg,rgba(8,5,2,0.82) 0%,rgba(12,8,4,0.68) 40%,rgba(8,5,2,0.78) 100%)}
.bg-vignette{position:fixed;inset:0;z-index:2;background:radial-gradient(ellipse at center,transparent 30%,rgba(0,0,0,0.55) 100%);pointer-events:none}
.bg-glow{position:fixed;inset:0;z-index:2;background:radial-gradient(ellipse 60% 50% at 15% 80%,rgba(200,146,42,0.12) 0%,transparent 60%);pointer-events:none}
.top-nav{position:fixed;top:22px;left:50%;transform:translateX(-50%);z-index:20;display:flex;align-items:center;gap:10px;background:rgba(15,13,10,0.55);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid var(--border);border-radius:50px;padding:7px 22px 7px 8px;white-space:nowrap}
.nav-dot{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-lt));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:var(--ink)}
.nav-name{font-family:'DM Serif Display',serif;font-size:14px;color:rgba(255,255,255,0.88)}
.nav-name em{color:var(--gold-lt);font-style:normal}
.card{position:relative;z-index:10;width:100%;max-width:420px;background:rgba(10,8,5,0.58);backdrop-filter:blur(40px) saturate(160%);-webkit-backdrop-filter:blur(40px) saturate(160%);border:1px solid var(--border);border-radius:var(--r);padding:42px 36px 36px;box-shadow:var(--shadow),inset 0 1px 0 rgba(255,255,255,0.09);animation:cardIn .55s cubic-bezier(.22,1,.36,1) both}
@keyframes cardIn{from{opacity:0;transform:translateY(26px) scale(.975)}to{opacity:1;transform:translateY(0) scale(1)}}
.card::before{content:'';position:absolute;top:0;left:32px;right:32px;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);border-radius:50%}
.card-icon{width:56px;height:56px;border-radius:14px;background:var(--gold-dim);border:1px solid rgba(200,146,42,0.22);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 20px}
.card-head{text-align:center;margin-bottom:26px}
.card-head h1{font-family:'DM Serif Display',serif;font-size:26px;font-weight:400;color:var(--white);letter-spacing:-.2px;margin-bottom:6px}
.card-head h1 span{color:var(--gold-lt);font-style:italic}
.card-head p{font-size:13px;color:rgba(255,255,255,0.35)}
.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 14px;border-radius:var(--r-sm);margin-bottom:20px;font-size:13px;font-weight:500;line-height:1.55}
.alert-err{background:rgba(224,82,82,0.14);border:1px solid rgba(224,82,82,0.28);color:#FF9090}
.alert-ok{background:rgba(76,175,125,0.14);border:1px solid rgba(76,175,125,0.28);color:#7DEEAB}
.grp{margin-bottom:14px}
.grp label{display:block;font-size:10.5px;font-weight:600;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.9px;margin-bottom:6px}
/* Indicateur force mot de passe */
.force-mdp{margin-top:7px;height:4px;border-radius:2px;background:rgba(255,255,255,0.08);overflow:hidden}
.force-barre{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s}
.force-txt{font-size:10px;color:rgba(255,255,255,0.3);margin-top:4px}
.ib{position:relative}
.ib input{padding-right:44px}
input{width:100%;padding:12px 14px;background:var(--mist);border:1px solid var(--border);border-radius:var(--r-sm);font-size:14px;color:var(--white);outline:none;transition:all .18s;font-family:'DM Sans',sans-serif;-webkit-appearance:none}
input::placeholder{color:rgba(255,255,255,0.2)}
input:focus{background:var(--mist2);border-color:rgba(200,146,42,0.5);box-shadow:0 0 0 3px rgba(200,146,42,0.10)}
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.22);display:flex;align-items:center;transition:color .18s;padding:4px}
.eye-btn:hover{color:rgba(255,255,255,0.6)}
.btn-cta{width:100%;padding:13.5px;background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);border:none;border-radius:var(--r-sm);font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:6px;transition:all .22s;box-shadow:0 6px 24px rgba(200,146,42,0.30)}
.btn-cta:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(200,146,42,0.40)}
.btn-cta:active{transform:translateY(0)}
.succes-wrap{text-align:center;padding:10px 0}
.succes-wrap .check{font-size:52px;margin-bottom:16px;display:block;animation:pop .4s cubic-bezier(.22,1,.36,1)}
@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
.succes-wrap h2{font-family:'DM Serif Display',serif;font-size:22px;color:var(--white);margin-bottom:8px}
.succes-wrap p{font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:24px}
.btn-login{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);border:none;border-radius:var(--r-sm);font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;transition:all .22s;box-shadow:0 6px 24px rgba(200,146,42,0.30)}
.btn-login:hover{transform:translateY(-2px)}
.lnk-bas{text-align:center;margin-top:18px;font-size:13px;color:rgba(255,255,255,0.3)}
.lnk-bas a{color:rgba(255,255,255,0.6);font-weight:600;text-decoration:none;border-bottom:1px solid rgba(255,255,255,0.15);padding-bottom:1px;transition:all .18s}
.lnk-bas a:hover{color:var(--gold-lt);border-bottom-color:var(--gold-lt)}
@media(max-width:460px){.card{padding:32px 20px 28px}}
</style>
</head>
<body>
<div class="bg"></div>
<div class="bg-darken"></div>
<div class="bg-vignette"></div>
<div class="bg-glow"></div>

<nav class="top-nav">
  <div class="nav-dot">♪</div>
  <span class="nav-name">GOMA <em>School</em> Int'l</span>
</nav>

<main class="card">

  <?php if($succes): ?>
  <!-- ── SUCCÈS ── -->
  <div class="succes-wrap">
    <span class="check">✅</span>
    <h2>Mot de passe mis à jour !</h2>
    <p>Votre nouveau mot de passe a été enregistré avec succès.<br>Vous pouvez maintenant vous connecter.</p>
    <a href="login.php" class="btn-login">
      Se connecter
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
  </div>

  <?php elseif(!$valide): ?>
  <!-- ── TOKEN INVALIDE ── -->
  <div class="card-icon">⚠️</div>
  <div class="card-head">
    <h1>Lien <span>invalide</span></h1>
    <p>Ce lien a expiré ou a déjà été utilisé</p>
  </div>
  <div class="alert alert-err"><?=htmlspecialchars($erreur)?></div>
  <p class="lnk-bas"><a href="mot_de_passe_oublie.php">← Faire une nouvelle demande</a></p>

  <?php else: ?>
  <!-- ── FORMULAIRE ── -->
  <div class="card-icon">🔒</div>
  <div class="card-head">
    <h1>Nouveau <span>mot de passe</span></h1>
    <p>Bonjour <strong style="color:rgba(255,255,255,0.7)"><?=htmlspecialchars($user['nom'])?></strong> — choisissez un mot de passe sécurisé</p>
  </div>

  <?php if($erreur):?>
  <div class="alert alert-err">⚠ <?=htmlspecialchars($erreur)?></div>
  <?php endif;?>

  <form method="POST" action="reset_mot_de_passe.php" novalidate>
    <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">

    <div class="grp">
      <label for="p1">Nouveau mot de passe</label>
      <div class="ib">
        <input type="password" id="p1" name="mot_de_passe"
               placeholder="Min. 6 caractères"
               oninput="forceMdp(this.value)"
               autocomplete="new-password" required>
        <button type="button" class="eye-btn" onclick="tog('p1')" aria-label="Afficher/masquer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <!-- Indicateur de force -->
      <div class="force-mdp"><div class="force-barre" id="force-barre"></div></div>
      <div class="force-txt" id="force-txt"></div>
    </div>

    <div class="grp">
      <label for="p2">Confirmer le mot de passe</label>
      <div class="ib">
        <input type="password" id="p2" name="confirmation"
               placeholder="Répétez le mot de passe"
               autocomplete="new-password" required>
        <button type="button" class="eye-btn" onclick="tog('p2')" aria-label="Afficher/masquer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-cta">
      Enregistrer le nouveau mot de passe
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
  </form>

  <p class="lnk-bas"><a href="login.php">← Retour à la connexion</a></p>
  <?php endif;?>

</main>

<script>
function tog(id) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

function forceMdp(mdp) {
  var barre = document.getElementById('force-barre');
  var txt   = document.getElementById('force-txt');
  var score = 0;
  if (mdp.length >= 6)                    score++;
  if (mdp.length >= 10)                   score++;
  if (/[A-Z]/.test(mdp))                  score++;
  if (/[0-9]/.test(mdp))                  score++;
  if (/[^A-Za-z0-9]/.test(mdp))           score++;

  var niveaux = [
    { pct:'20%', bg:'#E05252', label:'Très faible' },
    { pct:'40%', bg:'#E07832', label:'Faible'      },
    { pct:'60%', bg:'#E0B832', label:'Moyen'       },
    { pct:'80%', bg:'#7DB832', label:'Fort'        },
    { pct:'100%',bg:'#4CAF7D', label:'Très fort'   },
  ];

  if (mdp.length === 0) {
    barre.style.width = '0'; txt.textContent = '';
  } else {
    var n = niveaux[Math.min(score - 1, 4)];
    barre.style.width      = n.pct;
    barre.style.background = n.bg;
    txt.textContent        = n.label;
    txt.style.color        = n.bg;
  }
}
</script>
</body>
</html>
