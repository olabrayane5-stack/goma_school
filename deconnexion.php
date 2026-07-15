<?php
// ============================================================
//   GOMA SCHOOL INT'L — deconnexion.php
//   Gère la déconnexion et destruction propre de la session
// ============================================================

session_start();

// Récupérer le nom avant destruction
$nom  = $_SESSION['nom']  ?? 'Utilisateur';
$role = $_SESSION['role'] ?? '';

// 1. Vider toutes les variables de session
$_SESSION = [];

// 2. Détruire le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Détruire la session
session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="refresh" content="4;url=login.php">
<title>Déconnexion — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

:root{
  --ink:#0F0D0A;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,0.16);--white:#fff;
  --mist:rgba(255,255,255,0.07);--border:rgba(255,255,255,0.13);
  --r:14px;--r-sm:10px;--shadow:0 40px 120px rgba(0,0,0,0.65);
}

body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px 16px;
  position:relative;
  overflow:hidden;
}

/* ── FOND ── */
.bg{
  position:fixed;inset:0;z-index:0;
  background:url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1800&q=85')
  center 30%/cover no-repeat;
}
.bg-darken{
  position:fixed;inset:0;z-index:1;
  background:linear-gradient(160deg,rgba(8,5,2,0.85) 0%,rgba(12,8,4,0.72) 40%,rgba(8,5,2,0.85) 100%);
}
.bg-vignette{
  position:fixed;inset:0;z-index:2;
  background:radial-gradient(ellipse at center,transparent 30%,rgba(0,0,0,0.55) 100%);
  pointer-events:none;
}
.bg-glow{
  position:fixed;inset:0;z-index:2;
  background:radial-gradient(ellipse 60% 50% at 15% 80%,rgba(200,146,42,0.10) 0%,transparent 60%);
  pointer-events:none;
}

/* ── BRAND ── */
.top-nav{
  position:fixed;top:22px;left:50%;transform:translateX(-50%);
  z-index:20;display:flex;align-items:center;gap:10px;
  background:rgba(15,13,10,0.55);backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);border:1px solid var(--border);
  border-radius:50px;padding:7px 22px 7px 8px;white-space:nowrap;
}
.nav-dot{
  width:30px;height:30px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:900;color:var(--ink);
}
.nav-name{
  font-family:'DM Serif Display',serif;
  font-size:14px;color:rgba(255,255,255,0.88);
}
.nav-name em{color:var(--gold-lt);font-style:normal}

/* ── CARTE ── */
.card{
  position:relative;z-index:10;
  width:100%;max-width:400px;
  background:rgba(10,8,5,0.60);
  backdrop-filter:blur(40px) saturate(160%);
  -webkit-backdrop-filter:blur(40px) saturate(160%);
  border:1px solid var(--border);
  border-radius:var(--r);
  padding:44px 36px 38px;
  box-shadow:var(--shadow),inset 0 1px 0 rgba(255,255,255,0.09);
  text-align:center;
  animation:cardIn .55s cubic-bezier(.22,1,.36,1) both;
}

@keyframes cardIn{
  from{opacity:0;transform:translateY(26px) scale(.975)}
  to{opacity:1;transform:translateY(0) scale(1)}
}

.card::before{
  content:'';position:absolute;
  top:0;left:32px;right:32px;height:1px;
  background:linear-gradient(90deg,transparent,var(--gold),transparent);
  border-radius:50%;
}

/* ── ICÔNE ── */
.bye-icon{
  font-size:52px;
  display:block;
  margin:0 auto 20px;
  animation:wave 1.2s ease-in-out;
}

@keyframes wave{
  0%,100%{transform:rotate(0deg)}
  20%{transform:rotate(-18deg)}
  40%{transform:rotate(18deg)}
  60%{transform:rotate(-12deg)}
  80%{transform:rotate(10deg)}
}

/* ── TEXTE ── */
.titre{
  font-family:'DM Serif Display',serif;
  font-size:24px;font-weight:400;
  color:var(--white);
  letter-spacing:-.2px;
  margin-bottom:8px;
}

.titre span{color:var(--gold-lt);font-style:italic}

.sous-titre{
  font-size:13px;
  color:rgba(255,255,255,0.38);
  margin-bottom:28px;
  line-height:1.6;
}

/* ── BARRE DE PROGRESSION ── */
.progress-wrap{
  background:rgba(255,255,255,0.07);
  border-radius:50px;
  height:4px;
  overflow:hidden;
  margin-bottom:10px;
}

.progress-bar{
  height:100%;
  background:linear-gradient(90deg,var(--gold),var(--gold-lt));
  border-radius:50px;
  width:0%;
  animation:remplir 4s linear forwards;
}

@keyframes remplir{
  from{width:0%}
  to{width:100%}
}

.redirect-txt{
  font-size:12px;
  color:rgba(255,255,255,0.25);
  margin-bottom:28px;
}

.redirect-txt strong{
  color:var(--gold-lt);
}

/* ── BOUTON ── */
.btn-login{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  width:100%;
  padding:13px;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:var(--ink);
  border:none;border-radius:var(--r-sm);
  font-size:14px;font-weight:700;
  cursor:pointer;font-family:'DM Sans',sans-serif;
  text-decoration:none;
  transition:all .22s;
  box-shadow:0 6px 24px rgba(200,146,42,0.30);
}

.btn-login:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 32px rgba(200,146,42,0.40);
}

.btn-login:active{transform:translateY(0)}

/* ── SITES ── */
.sites{
  display:flex;justify-content:center;
  gap:6px;margin-top:22px;
}
.site-badge{
  font-size:10px;font-weight:600;
  color:rgba(255,255,255,0.2);
  background:var(--mist);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:20px;padding:4px 10px;
}

@media(max-width:460px){
  .card{padding:36px 20px 30px}
}
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

  <span class="bye-icon">👋</span>

  <h1 class="titre">
    Au revoir, <span><?= htmlspecialchars($nom) ?></span> !
  </h1>

  <p class="sous-titre">
    Vous avez été déconnecté(e) avec succès.<br>
    À bientôt sur GOMA School Int'l.
  </p>

  <!-- Barre de progression -->
  <div class="progress-wrap">
    <div class="progress-bar"></div>
  </div>

  <p class="redirect-txt">
    Redirection dans <strong id="timer">4</strong> seconde(s)...
  </p>

  <a href="login.php" class="btn-login">
    Se reconnecter
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
      <path d="M5 12h14M12 5l7 7-7 7"/>
    </svg>
  </a>

  <div class="sites">
    <span class="site-badge">Misséreté</span>
    <span class="site-badge">Donoukin</span>
    <span class="site-badge">Tokpota</span>
  </div>

</main>

<script>
  var s = 4;
  var t = document.getElementById('timer');
  var iv = setInterval(function() {
    s--;
    t.textContent = s;
    if (s <= 0) {
      clearInterval(iv);
      window.location.href = 'login.php';
    }
  }, 1000);
</script>

</body>
</html>
