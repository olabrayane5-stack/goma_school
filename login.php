<?php
session_start();
require 'connexion.php';

if (isset($_SESSION['id'])) {
    header('Location: ' . ($_SESSION['role']==='eleve' ? 'dashboard_eleve.php' : 'dashboard_prof.php'));
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');

    if (empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $erreur = "Email ou mot de passe incorrect.";
        } else {
            $_SESSION['id']    = $user['id'];
            $_SESSION['nom']   = $user['nom'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role']  = $user['role'];
            header('Location: ' . ($user['role']==='eleve' ? 'dashboard_eleve.php' : 'dashboard_prof.php'));
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Connexion — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
  --ink:      #0F0D0A;
  --gold:     #C8922A;
  --gold-lt:  #E8B455;
  --gold-dim: rgba(200,146,42,0.16);
  --white:    #FFFFFF;
  --mist:     rgba(255,255,255,0.07);
  --mist2:    rgba(255,255,255,0.12);
  --border:   rgba(255,255,255,0.13);
  --danger:   #E05252;
  --r:        14px;
  --r-sm:     10px;
  --shadow:   0 40px 120px rgba(0,0,0,0.65);
}

body {
  font-family: 'DM Sans', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  position: relative;
  overflow: hidden;
}

/* ══ FOND AFRICAIN MUSICAL ══ */
.bg {
  position: fixed; inset: 0; z-index: 0;
  background:
    url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=1800&q=85')
    center 40% / cover no-repeat;
}

.bg-darken {
  position: fixed; inset: 0; z-index: 1;
  background: linear-gradient(
    150deg,
    rgba(8,5,2,0.80) 0%,
    rgba(12,8,4,0.65) 45%,
    rgba(8,5,2,0.82) 100%
  );
}

.bg-vignette {
  position: fixed; inset: 0; z-index: 2;
  background: radial-gradient(ellipse at center, transparent 30%, rgba(0,0,0,0.5) 100%);
  pointer-events: none;
}

.bg-glow {
  position: fixed; inset: 0; z-index: 2;
  background:
    radial-gradient(ellipse 55% 45% at 20% 75%, rgba(200,146,42,0.11) 0%, transparent 60%),
    radial-gradient(ellipse 40% 35% at 80% 25%, rgba(200,146,42,0.07) 0%, transparent 60%);
  pointer-events: none;
}

/* ══ BRAND ══ */
.top-nav {
  position: fixed;
  top: 22px; left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  display: flex; align-items: center; gap: 10px;
  background: rgba(15,13,10,0.55);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--border);
  border-radius: 50px;
  padding: 7px 22px 7px 8px;
  white-space: nowrap;
}

.nav-dot {
  width: 30px; height: 30px; border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 900; color: var(--ink); flex-shrink: 0;
}

.nav-name {
  font-family: 'DM Serif Display', serif;
  font-size: 14px; color: rgba(255,255,255,0.88); letter-spacing: 0.2px;
}

.nav-name em { color: var(--gold-lt); font-style: normal; }

/* ══ CARTE ══ */
.card {
  position: relative; z-index: 10;
  width: 100%; max-width: 420px;
  background: rgba(10,8,5,0.58);
  backdrop-filter: blur(40px) saturate(160%);
  -webkit-backdrop-filter: blur(40px) saturate(160%);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 44px 36px 38px;
  box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,0.09);
  animation: cardIn 0.55s cubic-bezier(0.22,1,0.36,1) both;
}

@keyframes cardIn {
  from { opacity:0; transform:translateY(26px) scale(0.975); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

.card::before {
  content: '';
  position: absolute;
  top: 0; left: 32px; right: 32px; height: 1px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  border-radius: 50%;
}

/* Icône musicale centrée */
.card-icon {
  width: 56px; height: 56px;
  border-radius: 14px;
  background: var(--gold-dim);
  border: 1px solid rgba(200,146,42,0.22);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
  margin: 0 auto 20px;
}

/* En-tête */
.card-head {
  text-align: center;
  margin-bottom: 28px;
}

.card-head h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 28px; font-weight: 400;
  color: var(--white); letter-spacing: -0.2px;
  line-height: 1.2; margin-bottom: 6px;
}

.card-head h1 span { color: var(--gold-lt); font-style: italic; }

.card-head p {
  font-size: 13px; color: rgba(255,255,255,0.35); font-weight: 400;
}

/* Alerte */
.alert {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 14px; border-radius: var(--r-sm);
  margin-bottom: 18px; font-size: 13px; font-weight: 500;
}

.alert-err {
  background: rgba(224,82,82,0.14);
  border: 1px solid rgba(224,82,82,0.28);
  color: #FF9090;
}

/* Groupes */
.grp { margin-bottom: 14px; }

.grp label {
  display: block;
  font-size: 10.5px; font-weight: 600;
  color: rgba(255,255,255,0.4);
  text-transform: uppercase; letter-spacing: 0.9px;
  margin-bottom: 6px;
}

.ib { position: relative; }
.ib input { padding-right: 44px; }

input {
  width: 100%;
  padding: 12px 14px;
  background: var(--mist);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  font-size: 14px; color: var(--white);
  outline: none;
  transition: background 0.18s, border-color 0.18s, box-shadow 0.18s;
  font-family: 'DM Sans', sans-serif; font-weight: 400;
  -webkit-appearance: none;
}

input::placeholder { color: rgba(255,255,255,0.2); }

input:focus {
  background: var(--mist2);
  border-color: rgba(200,146,42,0.5);
  box-shadow: 0 0 0 3px rgba(200,146,42,0.10);
}

.eye-btn {
  position: absolute; right: 12px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: rgba(255,255,255,0.22); font-size: 16px;
  transition: color 0.18s; padding: 4px;
  display: flex; align-items: center;
}
.eye-btn:hover { color: rgba(255,255,255,0.6); }

/* Lien mot de passe oublié */
.mdp-oublie {
  display: flex; justify-content: flex-end;
  margin-top: -8px; margin-bottom: 14px;
}

.mdp-oublie a {
  font-size: 11.5px; color: rgba(255,255,255,0.32);
  text-decoration: none; font-weight: 500;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  padding-bottom: 1px;
  transition: all 0.18s;
}

.mdp-oublie a:hover {
  color: var(--gold-lt);
  border-bottom-color: var(--gold-lt);
}

/* Bouton */
.btn-cta {
  width: 100%; padding: 14px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-lt) 100%);
  color: var(--ink); border: none; border-radius: var(--r-sm);
  font-size: 14px; font-weight: 700; cursor: pointer;
  font-family: 'DM Sans', sans-serif; letter-spacing: 0.2px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  margin-top: 6px;
  transition: all 0.22s;
  box-shadow: 0 6px 24px rgba(200,146,42,0.30);
  position: relative; overflow: hidden;
}

.btn-cta::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, transparent 60%);
}

.btn-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(200,146,42,0.40);
}

.btn-cta:active { transform: translateY(0); }

/* Séparateur */
.sep {
  display: flex; align-items: center; gap: 14px;
  margin: 20px 0;
}
.sep::before, .sep::after {
  content: ''; flex: 1; height: 1px;
  background: rgba(255,255,255,0.08);
}
.sep span {
  font-size: 10px; font-weight: 600;
  color: rgba(255,255,255,0.22);
  text-transform: uppercase; letter-spacing: 1.2px;
  white-space: nowrap;
}

/* Lien inscription */
.lnk-bas {
  text-align: center; font-size: 13px;
  color: rgba(255,255,255,0.3);
}

.lnk-bas a {
  color: rgba(255,255,255,0.65); font-weight: 600;
  text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.18);
  padding-bottom: 1px;
  transition: all 0.18s;
}

.lnk-bas a:hover {
  color: var(--gold-lt);
  border-bottom-color: var(--gold-lt);
}

/* Sites badges */
.sites {
  display: flex; justify-content: center;
  gap: 6px; margin-top: 20px;
}

.site-badge {
  font-size: 10px; font-weight: 600;
  color: rgba(255,255,255,0.22);
  background: var(--mist);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 4px 10px;
  letter-spacing: 0.3px;
}

/* Responsive */
@media (max-width: 460px) {
  .card { padding: 36px 20px 30px; }
  .top-nav { padding: 6px 14px 6px 7px; }
  .nav-name { font-size: 12px; }
}
</style>
</head>
<body>

<div class="bg"></div>
<div class="bg-darken"></div>
<div class="bg-vignette"></div>
<div class="bg-glow"></div>

<nav class="top-nav" aria-label="Navigation">
  <div class="nav-dot" aria-hidden="true">♪</div>
  <span class="nav-name">GOMA <em>School</em> Int'l</span>
</nav>

<main class="card" role="main">

  <div class="card-icon" aria-hidden="true">🎵</div>

  <div class="card-head">
    <h1>Bon retour <span>parmi nous</span></h1>
    <p>Connectez-vous à votre espace GOMA School</p>
  </div>

  <?php if($erreur):?>
  <div class="alert alert-err" role="alert">
    <span aria-hidden="true">⚠</span>
    <?=htmlspecialchars($erreur)?>
  </div>
  <?php endif;?>

  <form method="POST" action="login.php" novalidate>

    <div class="grp">
      <label for="email">Adresse email</label>
      <input type="email" id="email" name="email"
             placeholder="jean@exemple.com"
             value="<?=htmlspecialchars($_POST['email']??'')?>"
             autocomplete="email" autofocus required>
    </div>

    <div class="grp">
      <label for="mdp">Mot de passe</label>
      <div class="ib">
        <input type="password" id="mdp" name="mot_de_passe"
               placeholder="Votre mot de passe"
               autocomplete="current-password" required>
        <button type="button" class="eye-btn"
                onclick="tog('mdp')" aria-label="Afficher/masquer le mot de passe">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="mdp-oublie">
      <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
    </div>

    <button type="submit" class="btn-cta">
      Se connecter
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <path d="M5 12h14M12 5l7 7-7 7"/>
      </svg>
    </button>

  </form>

  <div class="sep"><span>Nouveau sur GOMA School ?</span></div>

  <p class="lnk-bas">
    <a href="inscription.php">Créer un compte gratuitement</a>
  </p>

  <div class="sites" aria-label="Nos sites">
    <span class="site-badge">Misséreté</span>
    <span class="site-badge">Donoukin</span>
    <span class="site-badge">Tokpota</span>
  </div>

</main>

<script>
function tog(id) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
