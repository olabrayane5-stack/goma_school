<?php
$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($nom === '' || $email === '' || $message === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Contact - GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--ink:#0F0D0A;--ink2:#17130D;--gold:#C8922A;--gold-lt:#E8B455;--gold-dim:rgba(200,146,42,.16);--white:#fff;--muted:rgba(255,255,255,.58);--border:rgba(255,255,255,.13);--line:rgba(255,255,255,.09);--green:#7DEEAB;--danger:#E05252;--r:14px;--r-sm:10px;--shadow:0 40px 120px rgba(0,0,0,.62);--max:1180px}
body{font-family:'DM Sans',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden}a{text-decoration:none;color:inherit}
.hero-bg{position:fixed;inset:0;z-index:0;background:url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=1900&q=85') center 38%/cover no-repeat}.hero-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(145deg,rgba(8,5,2,.92),rgba(12,8,4,.82) 46%,rgba(8,5,2,.9))}
.vignette{position:fixed;inset:0;z-index:1;background:radial-gradient(ellipse at 72% 18%,rgba(200,146,42,.22),transparent 34%),radial-gradient(ellipse at center,transparent 32%,rgba(0,0,0,.58) 100%);pointer-events:none}.page{position:relative;z-index:2}
.nav{position:sticky;top:0;z-index:30;background:rgba(15,13,10,.58);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);border-bottom:1px solid var(--border)}.nav-in{width:min(var(--max),calc(100% - 40px));height:78px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:flex;align-items:center;gap:10px}.brand-dot{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-lt));display:grid;place-items:center;color:var(--ink);font-weight:900}.brand-name{font-family:'DM Serif Display',serif;font-size:18px;white-space:nowrap}.brand-name em{font-style:normal;color:var(--gold-lt)}
.menu{display:flex;gap:24px;font-size:12px;font-weight:800;color:rgba(255,255,255,.62)}.menu a:hover,.menu a.active{color:var(--gold-lt)}.actions{display:flex;gap:10px}.btn{min-height:42px;padding:0 16px;border-radius:var(--r-sm);display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;border:1px solid transparent;white-space:nowrap;transition:.2s;cursor:pointer;font-family:inherit}.btn:hover{transform:translateY(-2px)}.btn-primary{background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);box-shadow:0 12px 34px rgba(200,146,42,.28)}.btn-ghost{background:rgba(255,255,255,.07);color:rgba(255,255,255,.78);border-color:var(--border)}
.page-hero{width:min(var(--max),calc(100% - 40px));margin:auto;padding:58px 0 42px}.eyebrow{display:inline-flex;align-items:center;gap:9px;padding:8px 12px;border:1px solid rgba(200,146,42,.28);border-radius:999px;background:rgba(200,146,42,.1);color:var(--gold-lt);font-size:12px;font-weight:900;margin-bottom:18px}.eyebrow::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 18px rgba(125,238,171,.65)}
h1{font-family:'DM Serif Display',serif;font-size:62px;font-weight:400;line-height:.98;max-width:760px}h1 span{color:var(--gold-lt);font-style:italic}.lead{max-width:650px;margin-top:20px;color:rgba(255,255,255,.68);font-size:16px;line-height:1.8}
.section{position:relative;z-index:3;padding:42px 0 82px}.wrap{width:min(var(--max),calc(100% - 40px));margin:auto}.contact-grid{display:grid;grid-template-columns:410px 1fr;gap:24px;align-items:start}.panel{background:rgba(10,8,5,.62);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow),inset 0 1px 0 rgba(255,255,255,.08);backdrop-filter:blur(30px);padding:24px}.panel h2,.panel h3{font-family:'DM Serif Display',serif;font-weight:400;color:var(--white)}.panel h2{font-size:34px}.panel h3{font-size:24px;margin-bottom:14px}.panel p{color:var(--muted);line-height:1.75;font-size:14px}
.site-list{display:grid;gap:12px;margin-top:20px}.site{padding:14px;border-radius:12px;background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.09)}.site b{display:block;color:var(--gold-lt);font-size:13px}.site span{display:block;color:rgba(255,255,255,.52);font-size:12px;line-height:1.6;margin-top:4px}
.form{display:grid;gap:14px;margin-top:18px}.form input,.form textarea{width:100%;border:1px solid var(--border);border-radius:var(--r-sm);padding:13px 14px;background:rgba(255,255,255,.07);color:var(--white);font:inherit;outline:none}.form input::placeholder,.form textarea::placeholder{color:rgba(255,255,255,.28)}.form input:focus,.form textarea:focus{background:rgba(255,255,255,.11);border-color:rgba(200,146,42,.55);box-shadow:0 0 0 3px rgba(200,146,42,.10)}.form textarea{min-height:160px;resize:vertical}.alert{border-radius:12px;padding:13px 14px;margin-top:14px;font-size:13px;font-weight:800;line-height:1.55}.alert-ok{background:rgba(76,175,125,.14);border:1px solid rgba(76,175,125,.28);color:var(--green)}.alert-err{background:rgba(224,82,82,.14);border:1px solid rgba(224,82,82,.28);color:#FF9090}
.quick-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:24px}.quick{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.09);border-radius:12px;padding:16px}.quick b{display:block;color:var(--gold-lt);font-size:13px}.quick span{display:block;margin-top:5px;color:rgba(255,255,255,.52);font-size:12px;line-height:1.6}
.footer{position:relative;z-index:3;background:#0A0805;border-top:1px solid var(--line);padding:42px 0;color:rgba(255,255,255,.55)}.foot-grid{display:grid;grid-template-columns:1.35fr repeat(3,1fr);gap:28px}.footer h4{font-size:13px;color:var(--gold-lt);margin-bottom:12px}.footer a,.footer p{display:block;font-size:13px;line-height:1.95;color:rgba(255,255,255,.55)}
@media(max-width:980px){.menu{display:none}.contact-grid{grid-template-columns:1fr}.quick-grid,.foot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}h1{font-size:54px}}
@media(max-width:560px){.nav-in,.page-hero,.wrap{width:min(100% - 28px,var(--max))}.nav-in{height:auto;padding:14px 0;align-items:flex-start}.actions .btn-ghost{display:none}.brand-name{font-size:15px}h1{font-size:40px}.lead{font-size:14px}.quick-grid,.foot-grid{grid-template-columns:1fr}.section{padding-bottom:60px}}
</style>
</head>
<body>
<div class="hero-bg"></div>
<div class="vignette"></div>
<div class="page">
  <nav class="nav" aria-label="Navigation principale">
    <div class="nav-in">
      <a class="brand" href="index.php"><span class="brand-dot">♪</span><span class="brand-name">GOMA <em>School</em> Int'l</span></a>
      <div class="menu"><a href="index.php">Accueil</a><a href="apropos.php">À propos</a><a href="index.php#cours">Cours</a><a href="index.php#programmes">Programmes</a><a href="index.php#articles">Articles</a><a class="active" href="contact.php">Contact</a></div>
      <div class="actions"><a class="btn btn-ghost" href="inscription.php">Inscription</a><a class="btn btn-primary" href="login.php">Connexion</a></div>
    </div>
  </nav>

  <header class="page-hero">
    <div class="eyebrow">Contact · inscriptions · renseignements</div>
    <h1>Parlons de votre <span>parcours musical</span>.</h1>
    <p class="lead">Une question sur les cours, les horaires, l'inscription ou le suivi dans l'application ? L'équipe GOMA School Int'l vous répond avec plaisir.</p>
  </header>
</div>

<main class="section">
  <div class="wrap contact-grid">
    <aside class="panel">
      <h3>Nos sites</h3>
      <p>Retrouvez GOMA School Int'l sur nos trois sites de formation. Chaque lieu accueille les élèves selon les cours, les ateliers et les disponibilités.</p>
      <div class="site-list">
        <div class="site"><b>Misséreté</b><span>Accueil, inscriptions et suivi général.</span></div>
        <div class="site"><b>Donoukin</b><span>Cours pratiques et ateliers collectifs.</span></div>
        <div class="site"><b>Tokpota</b><span>Répétitions, chant et préparation scène.</span></div>
      </div>
      <div class="quick-grid">
        <div class="quick"><b>Horaires</b><span>Lun - Ven · 08:00 - 18:00</span></div>
        <div class="quick"><b>Samedi</b><span>09:00 - 15:00</span></div>
        <div class="quick"><b>Dimanche</b><span>Sur rendez-vous</span></div>
      </div>
    </aside>

    <section class="panel">
      <h2>Envoyer un message</h2>
      <p>Complétez le formulaire. Pour l'instant, le message est validé côté application ; l'envoi SMTP réel pourra être branché ensuite.</p>
      <?php if($sent): ?>
        <div class="alert alert-ok">Votre message a été préparé avec succès. L'envoi réel pourra être connecté au SMTP de l'école.</div>
      <?php elseif($error): ?>
        <div class="alert alert-err"><?=htmlspecialchars($error)?></div>
      <?php endif; ?>
      <form class="form" method="POST" action="contact.php">
        <input type="text" name="nom" placeholder="Votre nom" value="<?=htmlspecialchars($_POST['nom'] ?? '')?>" required>
        <input type="email" name="email" placeholder="Votre email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" required>
        <textarea name="message" placeholder="Votre message" required><?=htmlspecialchars($_POST['message'] ?? '')?></textarea>
        <button class="btn btn-primary" type="submit">Envoyer le message</button>
      </form>
    </section>
  </div>
</main>

<footer class="footer">
  <div class="wrap foot-grid">
    <div><a class="brand" href="index.php"><span class="brand-dot">♪</span><span class="brand-name">GOMA <em>School</em> Int'l</span></a><p>Former, accompagner et révéler les talents musicaux au Bénin.</p></div>
    <div><h4>Menu</h4><a href="index.php">Accueil</a><a href="apropos.php">À propos</a><a href="contact.php">Contact</a></div>
    <div><h4>Espaces</h4><a href="login.php">Connexion</a><a href="inscription.php">Inscription</a><a href="mot_de_passe_oublie.php">Mot de passe oublié</a></div>
    <div><h4>Sites</h4><p>Misséreté · Donoukin · Tokpota</p></div>
  </div>
</footer>
</body>
</html>
