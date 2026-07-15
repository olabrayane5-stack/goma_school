<?php
session_start();

if (isset($_SESSION['id'], $_SESSION['role'])) {
    header('Location: ' . ($_SESSION['role'] === 'eleve' ? 'dashboard_eleve.php' : 'dashboard_prof.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>GOMA School Int'l - École de musique</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --ink:#0F0D0A;--ink2:#17130D;--gold:#C8922A;--gold-lt:#E8B455;
  --gold-dim:rgba(200,146,42,.16);--white:#fff;--muted:rgba(255,255,255,.58);
  --soft:rgba(255,255,255,.07);--soft2:rgba(255,255,255,.12);
  --border:rgba(255,255,255,.13);--line:rgba(255,255,255,.09);
  --green:#7DEEAB;--danger:#E05252;--r:14px;--r-sm:10px;
  --shadow:0 40px 120px rgba(0,0,0,.62);--max:1180px;
}
body{font-family:'DM Sans',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
.hero-bg{position:fixed;inset:0;z-index:0;background:url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=1900&q=85') center 38%/cover no-repeat}
.hero-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(145deg,rgba(8,5,2,.90) 0%,rgba(12,8,4,.78) 42%,rgba(8,5,2,.88) 100%)}
.vignette{position:fixed;inset:0;z-index:1;background:radial-gradient(ellipse at 68% 24%,rgba(200,146,42,.22),transparent 34%),radial-gradient(ellipse at center,transparent 32%,rgba(0,0,0,.58) 100%);pointer-events:none}
.page{position:relative;z-index:2;min-height:100vh}
.nav{position:sticky;top:0;z-index:30;background:rgba(15,13,10,.58);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);border-bottom:1px solid var(--border)}
.nav-in{width:min(var(--max),calc(100% - 40px));height:78px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:flex;align-items:center;gap:10px;min-width:0}
.brand-dot{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-lt));display:grid;place-items:center;color:var(--ink);font-size:15px;font-weight:900;flex:0 0 auto}
.brand-name{font-family:'DM Serif Display',serif;font-size:18px;white-space:nowrap}
.brand-name em{font-style:normal;color:var(--gold-lt)}
.menu{display:flex;align-items:center;gap:24px;font-size:12px;font-weight:800;color:rgba(255,255,255,.62)}
.menu a{transition:color .18s}
.menu a:hover,.menu a.active{color:var(--gold-lt)}
.actions{display:flex;align-items:center;gap:10px}
.btn{min-height:42px;padding:0 16px;border-radius:var(--r-sm);display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:900;border:1px solid transparent;white-space:nowrap;transition:transform .2s,box-shadow .2s,border-color .2s,background .2s}
.btn:hover{transform:translateY(-2px)}
.btn-primary{background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);box-shadow:0 12px 34px rgba(200,146,42,.28)}
.btn-ghost{background:rgba(255,255,255,.07);color:rgba(255,255,255,.78);border-color:var(--border)}
.hero{width:min(var(--max),calc(100% - 40px));margin:auto;display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:54px;align-items:center;min-height:calc(100vh - 78px);padding:46px 0 82px}
.eyebrow{display:inline-flex;align-items:center;gap:9px;padding:8px 12px;border:1px solid rgba(200,146,42,.28);border-radius:999px;background:rgba(200,146,42,.10);color:var(--gold-lt);font-size:12px;font-weight:900;margin-bottom:18px}
.eyebrow::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 18px rgba(125,238,171,.65)}
h1{font-family:'DM Serif Display',serif;font-size:70px;font-weight:400;line-height:.98;max-width:760px}
h1 span{color:var(--gold-lt);font-style:italic}
.lead{max-width:650px;margin-top:20px;color:rgba(255,255,255,.68);font-size:16px;line-height:1.8}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}
.hero-actions .btn{min-height:48px;padding:0 20px;font-size:14px}
.hero-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;max-width:660px;margin-top:36px}
.meta{border-left:1px solid rgba(232,180,85,.45);padding-left:14px}
.meta strong{display:block;font-size:25px}.meta span{display:block;margin-top:3px;font-size:12px;color:rgba(255,255,255,.48);font-weight:700}
.showcase{position:relative}
.showcase-main{position:relative;background:rgba(10,8,5,.58);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow),inset 0 1px 0 rgba(255,255,255,.08);backdrop-filter:blur(34px) saturate(150%);padding:18px;overflow:hidden}
.showcase-main::before{content:"";position:absolute;top:0;left:30px;right:30px;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.showcase-main img{border-radius:12px;background:rgba(200,146,42,.14)}
.floating{position:absolute;left:-24px;bottom:30px;background:rgba(15,13,10,.78);border:1px solid rgba(200,146,42,.28);border-radius:12px;padding:14px 16px;box-shadow:0 22px 60px rgba(0,0,0,.42);backdrop-filter:blur(20px)}
.floating span{display:block;font-size:11px;color:rgba(255,255,255,.45);font-weight:800}.floating b{display:block;margin-top:2px;color:var(--gold-lt);font-size:16px}
.quick{position:absolute;right:-20px;top:42px;width:154px;background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);border-radius:12px;padding:14px;box-shadow:0 22px 60px rgba(0,0,0,.35);font-weight:900;font-size:13px}
.section{position:relative;z-index:3;background:var(--ink);padding:82px 0;border-top:1px solid var(--line)}
.section.tint{background:linear-gradient(180deg,#151009,#0F0D0A)}
.wrap{width:min(var(--max),calc(100% - 40px));margin:auto}
.section-head{text-align:center;margin:0 auto 34px;max-width:720px}
.kicker{font-size:12px;font-weight:900;color:var(--gold-lt);margin-bottom:8px}.section-title{font-family:'DM Serif Display',serif;font-size:42px;font-weight:400;line-height:1.08}.section-title span{color:var(--gold-lt);font-style:italic}.section-sub{margin-top:12px;color:var(--muted);line-height:1.75;font-size:14px}
.hours{margin-top:-42px;position:relative;z-index:4}
.hours-panel{width:min(var(--max),calc(100% - 40px));margin:auto;background:rgba(10,8,5,.72);border:1px solid rgba(200,146,42,.24);border-radius:var(--r);box-shadow:var(--shadow);backdrop-filter:blur(28px);display:grid;grid-template-columns:1fr auto;gap:22px;align-items:center;padding:22px 26px}
.hours-panel h2{font-size:15px;margin-bottom:12px}.hour-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.hour-grid span{display:block;font-size:11px;color:rgba(255,255,255,.38);font-weight:800}.hour-grid strong{font-size:13px;color:rgba(255,255,255,.88)}
.about-grid{display:grid;grid-template-columns:440px minmax(0,1fr);gap:58px;align-items:center}.visual-frame{background:rgba(255,255,255,.055);border:1px solid var(--border);border-radius:var(--r);padding:16px;box-shadow:0 24px 70px rgba(0,0,0,.32)}.visual-frame img{border-radius:12px;background:rgba(200,146,42,.12)}
.checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:26px}.check{display:flex;gap:10px;align-items:flex-start;padding:14px;border:1px solid rgba(255,255,255,.09);border-radius:12px;background:rgba(255,255,255,.045)}.check i{width:20px;height:20px;border-radius:50%;background:var(--gold-dim);color:var(--gold-lt);display:grid;place-items:center;font-style:normal;font-size:11px;flex:0 0 auto}.check b{display:block;font-size:13px}.check span{font-size:12px;color:rgba(255,255,255,.52);line-height:1.6}
.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.card{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.10);border-radius:var(--r);padding:24px;box-shadow:0 22px 60px rgba(0,0,0,.20)}.card-icon{width:46px;height:46px;border-radius:12px;background:var(--gold-dim);border:1px solid rgba(200,146,42,.25);display:grid;place-items:center;color:var(--gold-lt);font-size:21px;margin-bottom:16px}.card h3{font-size:18px;margin-bottom:8px}.card p{color:var(--muted);font-size:13px;line-height:1.7}
.numbers{background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);padding:38px 0}.num-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.num{text-align:center}.num strong{display:block;font-size:34px}.num span{font-size:12px;font-weight:900;color:rgba(15,13,10,.62)}
.programs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.program{display:grid;grid-template-columns:1fr 1.05fr;overflow:hidden;background:rgba(255,255,255,.055);border:1px solid var(--border);border-radius:var(--r)}.program-copy{padding:24px}.program-copy h3{font-size:22px;color:var(--gold-lt);margin-bottom:10px}.program-copy p{font-size:13px;color:var(--muted);line-height:1.7}.program img{height:100%;min-height:220px;object-fit:cover;background:rgba(200,146,42,.12)}
.facilities,.teachers,.articles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.facility,.teacher,.article{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.10);border-radius:var(--r);overflow:hidden}.facility img,.teacher img,.article img{height:210px;width:100%;object-fit:cover;background:rgba(200,146,42,.14)}.facility div,.teacher div,.article div{padding:18px}.facility h3,.teacher h3,.article h3{font-size:17px;color:var(--white)}.facility p,.teacher p,.article p{font-size:13px;color:var(--muted);line-height:1.65;margin-top:7px}
.cta{background:linear-gradient(135deg,rgba(200,146,42,.22),rgba(232,180,85,.08)),var(--ink2)}.cta-panel{display:grid;grid-template-columns:1fr auto;gap:26px;align-items:center;border:1px solid rgba(200,146,42,.25);border-radius:var(--r);padding:32px;background:rgba(10,8,5,.45);box-shadow:var(--shadow)}.cta h2{font-family:'DM Serif Display',serif;font-size:38px;font-weight:400}.cta p{color:var(--muted);line-height:1.7;margin-top:10px;max-width:650px}
.footer{position:relative;z-index:3;background:#0A0805;border-top:1px solid var(--line);padding:42px 0;color:rgba(255,255,255,.55)}.foot-grid{display:grid;grid-template-columns:1.35fr repeat(3,1fr);gap:28px}.footer h4{font-size:13px;color:var(--gold-lt);margin-bottom:12px}.footer a,.footer p{display:block;font-size:13px;line-height:1.95;color:rgba(255,255,255,.55)}.footer a:hover{color:var(--gold-lt)}
@media(max-width:980px){
  .menu{display:none}.hero{grid-template-columns:1fr;min-height:auto;padding:54px 0 90px}.showcase{max-width:520px}.hero-meta,.hour-grid,.cards,.num-grid,.facilities,.teachers,.articles,.foot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.about-grid,.programs{grid-template-columns:1fr}.program{grid-template-columns:1fr}.cta-panel,.hours-panel{grid-template-columns:1fr}.section-title{font-size:36px}h1{font-size:58px}
}
@media(max-width:560px){
  .nav-in,.hero,.wrap,.hours-panel{width:min(100% - 28px,var(--max))}.nav-in{height:auto;padding:14px 0;align-items:flex-start}.actions .btn-ghost{display:none}.brand-name{font-size:15px}h1{font-size:40px}.lead{font-size:14px}.hero-actions .btn,.hours-panel .btn{width:100%}.hero-meta,.hour-grid,.cards,.num-grid,.facilities,.teachers,.articles,.foot-grid,.checks{grid-template-columns:1fr}.section{padding:60px 0}.quick,.floating{position:static;margin-top:12px}.showcase-main{padding:12px}.cta h2{font-size:30px}
}
</style>
</head>
<body>
<div class="hero-bg"></div>
<div class="vignette"></div>

<div class="page">
  <nav class="nav" aria-label="Navigation principale">
    <div class="nav-in">
      <a class="brand" href="index.php">
        <span class="brand-dot">♪</span>
        <span class="brand-name">GOMA <em>School</em> Int'l</span>
      </a>
      <div class="menu">
        <a class="active" href="index.php">Accueil</a>
        <a href="apropos.php">À propos</a>
        <a href="#cours">Cours</a>
        <a href="#programmes">Programmes</a>
        <a href="#articles">Articles</a>
        <a href="contact.php">Contact</a>
      </div>
      <div class="actions">
        <a class="btn btn-ghost" href="inscription.php">Inscription</a>
        <a class="btn btn-primary" href="login.php">Connexion</a>
      </div>
    </div>
  </nav>

  <header class="hero">
    <section>
      <div class="eyebrow">École de musique · Misséreté · Donoukin · Tokpota</div>
      <h1>Entrez dans le <span>monde de la musique</span> avec GOMA School.</h1>
      <p class="lead">Une plateforme élégante pour apprendre, suivre les cours, gérer les inscriptions, consulter les notes et garder les paiements organisés. L'expérience élève et professeur, réunie dans un seul espace.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="inscription.php">Commencer l'inscription</a>
        <a class="btn btn-ghost" href="#cours">Explorer les cours</a>
      </div>
      <div class="hero-meta">
        <div class="meta"><strong>3</strong><span>sites de formation</span></div>
        <div class="meta"><strong>6</strong><span>disciplines musicales</span></div>
        <div class="meta"><strong>24h</strong><span>accès à l'espace</span></div>
      </div>
    </section>

    <aside class="showcase" aria-label="Aperçu GOMA School">
      <div class="showcase-main">
        <img src="assets/images/hero-student.svg" alt="Élève GOMA School jouant de la guitare">
      </div>
      <div class="quick">Suivi clair des cours, notes et paiements</div>
      <div class="floating"><span>Prochaine rentrée</span><b>Inscriptions ouvertes</b></div>
    </aside>
  </header>
</div>

<section class="hours" aria-label="Horaires">
  <div class="hours-panel">
    <div>
      <h2>Nos horaires d'ouverture</h2>
      <div class="hour-grid">
        <div><span>Lundi - Vendredi</span><strong>08:00 - 18:00</strong></div>
        <div><span>Samedi</span><strong>09:00 - 15:00</strong></div>
        <div><span>Dimanche</span><strong>Sur rendez-vous</strong></div>
      </div>
    </div>
    <a class="btn btn-primary" href="contact.php">Nous contacter</a>
  </div>
</section>

<main>
  <section class="section" id="apropos">
    <div class="wrap about-grid">
      <div class="visual-frame"><img src="assets/images/about-class.svg" alt="Cours de musique à GOMA School"></div>
      <div>
        <p class="kicker">Notre mission</p>
        <h2 class="section-title">Révéler le <span>talent musical</span> avec méthode.</h2>
        <p class="section-sub">GOMA School Int'l accompagne les élèves dans une progression sérieuse, humaine et mesurable. Notre application garde le lien entre les familles, les professeurs et l'administration.</p>
        <div class="checks">
          <div class="check"><i>✓</i><span><b>Suivi élève</b>Notes, cours, inscriptions et reçus dans un espace clair.</span></div>
          <div class="check"><i>✓</i><span><b>Professeurs dédiés</b>Un accompagnement attentif, orienté pratique.</span></div>
          <div class="check"><i>✓</i><span><b>Culture musicale</b>Des rythmes locaux aux techniques modernes.</span></div>
          <div class="check"><i>✓</i><span><b>Gestion simple</b>Planning, paiements et reçus centralisés.</span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section tint" id="cours">
    <div class="wrap">
      <div class="section-head">
        <p class="kicker">Nos cours</p>
        <h2 class="section-title">Explorez les instruments, <span>trouvez votre voix</span>.</h2>
        <p class="section-sub">Chaque cours est pensé pour construire des bases solides, développer l'écoute et préparer l'élève à jouer avec confiance.</p>
      </div>
      <div class="cards">
        <article class="card"><div class="card-icon">♬</div><h3>Piano</h3><p>Lecture, harmonie, technique, accompagnement et répertoire progressif.</p></article>
        <article class="card"><div class="card-icon">♪</div><h3>Guitare</h3><p>Accords, arpèges, rythme, improvisation et jeu en ensemble.</p></article>
        <article class="card"><div class="card-icon">♫</div><h3>Chant</h3><p>Respiration, justesse, présence scénique et interprétation.</p></article>
        <article class="card"><div class="card-icon">♩</div><h3>Percussions</h3><p>Rythmes, coordination, écoute collective et énergie de groupe.</p></article>
        <article class="card"><div class="card-icon">♭</div><h3>Saxophone</h3><p>Souffle, sonorité, phrasé, jazz, gospel et musiques modernes.</p></article>
        <article class="card"><div class="card-icon">♮</div><h3>Théorie musicale</h3><p>Solfège, harmonie, composition et compréhension du langage musical.</p></article>
      </div>
    </div>
  </section>

  <section class="numbers">
    <div class="wrap num-grid">
      <div class="num"><strong>812+</strong><span>heures de pratique</span></div>
      <div class="num"><strong>621+</strong><span>séances suivies</span></div>
      <div class="num"><strong>678+</strong><span>notes enregistrées</span></div>
      <div class="num"><strong>67+</strong><span>reçus générés</span></div>
    </div>
  </section>

  <section class="section" id="programmes">
    <div class="wrap">
      <div class="section-head">
        <p class="kicker">Programmes</p>
        <h2 class="section-title">Des parcours adaptés aux <span>élèves</span>.</h2>
      </div>
      <div class="programs">
        <article class="program">
          <div class="program-copy"><h3>Classe enfants</h3><p>Une initiation douce par le rythme, le chant, l'écoute et les premiers gestes instrumentaux.</p></div>
          <img src="assets/images/about-class.svg" alt="Classe enfants">
        </article>
        <article class="program">
          <div class="program-copy"><h3>Jeunes & adultes</h3><p>Un parcours structuré pour développer technique, répertoire, discipline et confiance scénique.</p></div>
          <img src="assets/images/teacher.svg" alt="Classe jeunes et adultes">
        </article>
      </div>
    </div>
  </section>

  <section class="section tint">
    <div class="wrap">
      <div class="section-head">
        <p class="kicker">Installations</p>
        <h2 class="section-title">Un cadre confortable pour <span>apprendre</span>.</h2>
      </div>
      <div class="facilities">
        <article class="facility"><img src="assets/images/studio-facility.svg" alt="Studio de pratique"><div><h3>Studio de pratique</h3><p>Un espace calme pour répéter, écouter et corriger les bases.</p></div></article>
        <article class="facility"><img src="assets/images/about-class.svg" alt="Salle interactive"><div><h3>Salle interactive</h3><p>Des séances collectives pour apprendre avec énergie.</p></div></article>
        <article class="facility"><img src="assets/images/hero-student.svg" alt="Préparation scène"><div><h3>Préparation scène</h3><p>Posture, assurance, présence et expression musicale.</p></div></article>
      </div>
    </div>
  </section>

  <section class="section cta">
    <div class="wrap cta-panel">
      <div>
        <p class="kicker">Rejoignez GOMA School</p>
        <h2>Découvrez votre talent, commencez votre parcours musical.</h2>
        <p>Créez un compte, choisissez votre domaine d'apprentissage et suivez votre progression depuis votre espace personnel.</p>
      </div>
      <a class="btn btn-primary" href="inscription.php">Créer mon compte</a>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div class="section-head">
        <p class="kicker">Professeurs</p>
        <h2 class="section-title">Une équipe passionnée par la <span>transmission</span>.</h2>
      </div>
      <div class="teachers">
        <article class="teacher"><img src="assets/images/teacher.svg" alt="Professeur piano"><div><h3>Jean ADANLE</h3><p>Piano & théorie musicale</p></div></article>
        <article class="teacher"><img src="assets/images/about-class.svg" alt="Professeur guitare"><div><h3>Équipe GOMA</h3><p>Guitare, chant et ateliers</p></div></article>
        <article class="teacher"><img src="assets/images/studio-facility.svg" alt="Atelier percussion"><div><h3>Ateliers invités</h3><p>Percussions, scène et ensemble</p></div></article>
      </div>
    </div>
  </section>

  <section class="section tint" id="articles">
    <div class="wrap">
      <div class="section-head">
        <p class="kicker">Conseils</p>
        <h2 class="section-title">Quelques repères pour <span>mieux pratiquer</span>.</h2>
      </div>
      <div class="articles">
        <article class="article"><img src="assets/images/hero-student.svg" alt="Conseil guitare"><div><h3>Bien commencer la guitare</h3><p>Les gestes simples à répéter pour construire une bonne base.</p></div></article>
        <article class="article"><img src="assets/images/studio-facility.svg" alt="Conseil répétition"><div><h3>Organiser ses répétitions</h3><p>Un planning régulier vaut mieux qu'une longue séance isolée.</p></div></article>
        <article class="article"><img src="assets/images/about-class.svg" alt="Conseil scène"><div><h3>Préparer une prestation</h3><p>Respiration, écoute et posture : la scène se prépare en cours.</p></div></article>
      </div>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="wrap foot-grid">
    <div>
      <a class="brand" href="index.php"><span class="brand-dot">♪</span><span class="brand-name">GOMA <em>School</em> Int'l</span></a>
      <p>Former, accompagner et révéler les talents musicaux au Bénin.</p>
    </div>
    <div><h4>Menu</h4><a href="index.php">Accueil</a><a href="apropos.php">À propos</a><a href="#cours">Cours</a></div>
    <div><h4>Espaces</h4><a href="login.php">Connexion</a><a href="inscription.php">Inscription</a><a href="mot_de_passe_oublie.php">Mot de passe oublié</a></div>
    <div><h4>Contact</h4><p>Misséreté · Donoukin · Tokpota</p><a href="contact.php">Écrire à l'école</a></div>
  </div>
</footer>
</body>
</html>
