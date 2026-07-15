<?php
session_start();
require 'connexion.php';
define('PIN_PROFESSEUR', 'TDB$127');
$erreur = '';
$succes = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom=$_POST['nom']??''; $email=$_POST['email']??'';
    $tel=$_POST['telephone']??''; $mdp=$_POST['mot_de_passe']??'';
    $conf=$_POST['confirmation']??''; $dob=$_POST['date_naissance']??'';
    $role=$_POST['role']??''; $domaine=$_POST['domaine']??'';
    $niveau=$_POST['niveau']??''; $spec=$_POST['specialite']??'';
    $pin=$_POST['code_pin']??'';
    if(empty($nom)||empty($email)||empty($mdp)||empty($role))
        $erreur="Veuillez remplir tous les champs obligatoires.";
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))
        $erreur="Adresse email invalide.";
    elseif($mdp!==$conf)
        $erreur="Les mots de passe ne correspondent pas.";
    elseif(strlen($mdp)<6)
        $erreur="Le mot de passe doit contenir au moins 6 caractères.";
    elseif($role==='professeur'&&$pin!==PIN_PROFESSEUR)
        $erreur="Code PIN incorrect. Accès refusé.";
    else {
        $st=$pdo->prepare("SELECT id FROM utilisateurs WHERE email=?");
        $st->execute([$email]);
        if($st->rowCount()>0) $erreur="Cet email est déjà utilisé.";
        else {
            $h=password_hash($mdp,PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO utilisateurs(nom,email,telephone,mot_de_passe,date_naissance,role)VALUES(?,?,?,?,?,?)")
                ->execute([$nom,$email,$tel,$h,$dob,$role]);
            $id=$pdo->lastInsertId();
            if($role==='eleve')
                $pdo->prepare("INSERT INTO eleves(id,domaine_apprentissage,niveau_actuel)VALUES(?,?,?)")
                    ->execute([$id,$domaine,$niveau]);
            else
                $pdo->prepare("INSERT INTO professeurs(id,specialite)VALUES(?,?)")
                    ->execute([$id,$spec]);
            $succes="Compte créé avec succès !";
        }
    }
}
$rp = htmlspecialchars($_POST['role']??'');
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Inscription — GOMA School Int'l</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ── RESET & TOKENS ── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
  --ink:      #0F0D0A;
  --gold:     #C8922A;
  --gold-lt:  #E8B455;
  --gold-dim: rgba(200,146,42,0.18);
  --white:    #FFFFFF;
  --mist:     rgba(255,255,255,0.07);
  --mist2:    rgba(255,255,255,0.12);
  --border:   rgba(255,255,255,0.13);
  --danger:   #E05252;
  --safe:     #4CAF7D;
  --blue:     #4A8FD4;
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
  overflow-x: hidden;
}

/* ══ ARRIÈRE-PLAN AFRICAIN MUSICAL ══ */
.bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  /* Photo de musicien africain jouant du djembé / kora */
  background:
    url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1800&q=85')
    center 30% / cover no-repeat;
}

/* Assombrissement riche et chaud */
.bg-darken {
  position: fixed;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(
      160deg,
      rgba(8,5,2,0.82) 0%,
      rgba(12,8,4,0.68) 40%,
      rgba(8,5,2,0.78) 100%
    );
}

/* Vignette bords */
.bg-vignette {
  position: fixed;
  inset: 0;
  z-index: 2;
  background: radial-gradient(
    ellipse at center,
    transparent 30%,
    rgba(0,0,0,0.55) 100%
  );
  pointer-events: none;
}

/* Touche de chaleur dorée */
.bg-glow {
  position: fixed;
  inset: 0;
  z-index: 2;
  background:
    radial-gradient(ellipse 60% 50% at 15% 80%, rgba(200,146,42,0.12) 0%, transparent 60%),
    radial-gradient(ellipse 40% 40% at 85% 20%, rgba(200,146,42,0.08) 0%, transparent 60%);
  pointer-events: none;
}

/* ══ BRAND FLOTTANT ══ */
.top-nav {
  position: fixed;
  top: 22px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(15,13,10,0.55);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--border);
  border-radius: 50px;
  padding: 7px 22px 7px 8px;
  white-space: nowrap;
}

.nav-dot {
  width: 30px; height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 900; color: var(--ink);
  flex-shrink: 0;
}

.nav-name {
  font-family: 'DM Serif Display', serif;
  font-size: 14px;
  color: rgba(255,255,255,0.88);
  letter-spacing: 0.2px;
}

.nav-name em {
  color: var(--gold-lt);
  font-style: normal;
}

/* ══ CARTE PRINCIPALE ══ */
.card {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 468px;
  background: rgba(10,8,5,0.58);
  backdrop-filter: blur(40px) saturate(160%);
  -webkit-backdrop-filter: blur(40px) saturate(160%);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 40px 36px 34px;
  box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,0.09);
  animation: cardIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
}

@keyframes cardIn {
  from { opacity:0; transform:translateY(28px) scale(0.975); }
  to   { opacity:1; transform:translateY(0)    scale(1); }
}

/* Ligne top dorée */
.card::before {
  content: '';
  position: absolute;
  top: 0; left: 32px; right: 32px; height: 1px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  border-radius: 50%;
}

/* ── EN-TÊTE ── */
.card-head {
  margin-bottom: 26px;
}

.card-head h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 28px;
  font-weight: 400;
  color: var(--white);
  letter-spacing: -0.2px;
  line-height: 1.2;
  margin-bottom: 5px;
}

.card-head h1 span {
  color: var(--gold-lt);
  font-style: italic;
}

.card-head p {
  font-size: 13px;
  color: rgba(255,255,255,0.38);
  font-weight: 400;
}

/* ── ALERTES ── */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: var(--r-sm);
  margin-bottom: 18px;
  font-size: 13px;
  font-weight: 500;
  backdrop-filter: blur(8px);
}

.alert-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

.alert-err {
  background: rgba(224,82,82,0.14);
  border: 1px solid rgba(224,82,82,0.28);
  color: #FF9090;
}

.alert-ok {
  background: rgba(76,175,125,0.14);
  border: 1px solid rgba(76,175,125,0.28);
  color: #7DEEAB;
}

.alert-ok a { color: #7DEEAB; font-weight:700; text-decoration:underline; }

/* ── GROUPES ── */
.grp { margin-bottom: 13px; }

.grp label {
  display: block;
  font-size: 10.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.4);
  text-transform: uppercase;
  letter-spacing: 0.9px;
  margin-bottom: 6px;
}

.req { color: var(--gold); }

/* ── INPUTS ── */
.ib { position: relative; }
.ib input { padding-right: 40px; }

input, select {
  width: 100%;
  padding: 11px 14px;
  background: var(--mist);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  font-size: 13.5px;
  color: var(--white);
  outline: none;
  transition: background 0.18s, border-color 0.18s, box-shadow 0.18s;
  font-family: 'DM Sans', sans-serif;
  font-weight: 400;
  -webkit-appearance: none;
}

input::placeholder { color: rgba(255,255,255,0.2); }

input:focus, select:focus {
  background: var(--mist2);
  border-color: rgba(200,146,42,0.5);
  box-shadow: 0 0 0 3px rgba(200,146,42,0.1);
}

select option { background: #1a1108; color: #fff; }

input[type=date]::-webkit-calendar-picker-indicator {
  filter: invert(0.4) sepia(1) saturate(2) hue-rotate(10deg);
  cursor: pointer;
}

.eye-btn {
  position: absolute;
  right: 12px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  cursor: pointer;
  color: rgba(255,255,255,0.22);
  font-size: 16px;
  transition: color 0.18s;
  padding: 4px;
  display: flex;
  align-items: center;
}
.eye-btn:hover { color: rgba(255,255,255,0.6); }

/* ── GRILLE 2 COL ── */
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; }

/* ── SÉPARATEUR ── */
.sep {
  display: flex; align-items: center; gap: 14px;
  margin: 16px 0;
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

/* ── SÉLECTION RÔLE ── */
.roles {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}

.role-card {
  position: relative;
  background: var(--mist);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 16px 12px 14px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex; flex-direction: column;
  align-items: center; gap: 6px;
  user-select: none;
}

.role-card input { display: none; }

.role-card:hover {
  background: var(--mist2);
  border-color: rgba(255,255,255,0.22);
}

.role-card.on-e {
  background: rgba(74,143,212,0.14);
  border-color: rgba(74,143,212,0.45);
  box-shadow: 0 0 0 3px rgba(74,143,212,0.08);
}

.role-card.on-p {
  background: var(--gold-dim);
  border-color: rgba(200,146,42,0.45);
  box-shadow: 0 0 0 3px rgba(200,146,42,0.08);
}

.rc-check {
  display: none;
  position: absolute; top: 8px; right: 8px;
  width: 17px; height: 17px;
  border-radius: 50%;
  align-items: center; justify-content: center;
  font-size: 9px; font-weight: 800; color: var(--white);
}
.on-e .rc-check-e,
.on-p .rc-check-p { display: flex; }
.rc-check-e { background: var(--blue); }
.rc-check-p { background: var(--gold); }

.rc-icon {
  width: 42px; height: 42px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  transition: all 0.2s;
}
.ic-e { background: rgba(74,143,212,0.16); }
.ic-p { background: var(--gold-dim); }
.on-e .ic-e { background: rgba(74,143,212,0.28); }
.on-p .ic-p { background: rgba(200,146,42,0.28); }

.rc-name {
  font-size: 12.5px; font-weight: 600;
  color: rgba(255,255,255,0.82);
}
.rc-sub {
  font-size: 10.5px; color: rgba(255,255,255,0.3);
  text-align: center;
}

/* ── CHAMPS CONDITIONNELS ── */
.cond { display: none; }

/* ── BLOC PIN ── */
.pin-block {
  background: rgba(200,146,42,0.09);
  border: 1px solid rgba(200,146,42,0.26);
  border-radius: var(--r-sm);
  padding: 14px 14px 14px 17px;
  margin-bottom: 13px;
  position: relative;
  overflow: hidden;
}

.pin-block::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0; width: 3px;
  background: linear-gradient(180deg, var(--gold), var(--gold-lt));
}

.pin-badge {
  display: inline-flex; align-items: center; gap: 6px;
  margin-bottom: 10px;
}

.pin-dot {
  width: 6px; height: 6px;
  background: var(--gold); border-radius: 50%;
}

.pin-label-txt {
  font-size: 10px; font-weight: 700;
  color: var(--gold); text-transform: uppercase; letter-spacing: 1px;
}

.pin-block label {
  display: block;
  font-size: 10.5px; font-weight: 600;
  color: rgba(255,255,255,0.4);
  text-transform: uppercase; letter-spacing: 0.8px;
  margin-bottom: 6px;
}

.pin-block input {
  letter-spacing: 5px; font-weight: 700; font-size: 15px;
  border-color: rgba(200,146,42,0.28);
}

.pin-block input:focus {
  border-color: rgba(200,146,42,0.6);
  box-shadow: 0 0 0 3px rgba(200,146,42,0.10);
}

/* ── BOUTON PRINCIPAL ── */
.btn-cta {
  width: 100%;
  padding: 13.5px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-lt) 100%);
  color: var(--ink);
  border: none;
  border-radius: var(--r-sm);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  letter-spacing: 0.2px;
  display: flex; align-items: center;
  justify-content: center; gap: 8px;
  margin-top: 8px;
  transition: all 0.22s;
  box-shadow: 0 6px 24px rgba(200,146,42,0.32);
  position: relative; overflow: hidden;
}

.btn-cta::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, transparent 60%);
}

.btn-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(200,146,42,0.42);
}

.btn-cta:active { transform: translateY(0); }

.btn-cta svg { flex-shrink: 0; }

/* ── LIEN CONNEXION ── */
.lnk-bas {
  text-align: center;
  margin-top: 16px;
  font-size: 13px;
  color: rgba(255,255,255,0.3);
}

.lnk-bas a {
  color: rgba(255,255,255,0.65);
  font-weight: 600;
  text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.18);
  padding-bottom: 1px;
  transition: all 0.18s;
}

.lnk-bas a:hover {
  color: var(--gold-lt);
  border-bottom-color: var(--gold-lt);
}

/* ── RESPONSIVE ── */
@media (max-width: 520px) {
  .card { padding: 32px 20px 28px; }
  .g2 { grid-template-columns: 1fr; gap: 0; }
  .roles { grid-template-columns: 1fr 1fr; }
  .top-nav { padding: 6px 16px 6px 7px; }
  .nav-name { font-size: 12px; }
}

@media (max-width: 360px) {
  .roles { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="bg"></div>
<div class="bg-darken"></div>
<div class="bg-vignette"></div>
<div class="bg-glow"></div>

<!-- Brand -->
<nav class="top-nav" aria-label="Navigation">
  <div class="nav-dot" aria-hidden="true">♪</div>
  <span class="nav-name">GOMA <em>School</em> Int'l</span>
</nav>

<!-- Carte -->
<main class="card" role="main">

  <div class="card-head">
    <h1>Créer <span>votre compte</span></h1>
    <p>Rejoignez l'école de musique GOMA — Bénin</p>
  </div>

  <?php if($erreur):?>
  <div class="alert alert-err" role="alert">
    <span class="alert-icon" aria-hidden="true">⚠</span>
    <span><?=htmlspecialchars($erreur)?></span>
  </div>
  <?php endif;?>

  <?php if($succes):?>
  <div class="alert alert-ok" role="status">
    <span class="alert-icon" aria-hidden="true">✓</span>
    <span>Compte créé ! <a href="login.php">Se connecter →</a></span>
  </div>
  <?php endif;?>

  <form method="POST" action="inscription.php" novalidate>

    <!-- Nom -->
    <div class="grp">
      <label for="nom">Nom complet <span class="req" aria-label="requis">*</span></label>
      <input type="text" id="nom" name="nom"
             placeholder="Jean AHOYO"
             value="<?=htmlspecialchars($_POST['nom']??'')?>"
             autocomplete="name" required>
    </div>

    <!-- Email -->
    <div class="grp">
      <label for="email">Adresse email <span class="req">*</span></label>
      <input type="email" id="email" name="email"
             placeholder="jean@exemple.com"
             value="<?=htmlspecialchars($_POST['email']??'')?>"
             autocomplete="email" required>
    </div>

    <!-- Tél + DOB -->
    <div class="g2">
      <div class="grp">
        <label for="tel">Téléphone</label>
        <input type="tel" id="tel" name="telephone"
               placeholder="+229 ..."
               value="<?=htmlspecialchars($_POST['telephone']??'')?>"
               autocomplete="tel">
      </div>
      <div class="grp">
        <label for="dob">Date de naissance</label>
        <input type="date" id="dob" name="date_naissance"
               value="<?=htmlspecialchars($_POST['date_naissance']??'')?>">
      </div>
    </div>

    <!-- MDP -->
    <div class="g2">
      <div class="grp">
        <label for="p1">Mot de passe <span class="req">*</span></label>
        <div class="ib">
          <input type="password" id="p1" name="mot_de_passe"
                 placeholder="Min. 6 caractères"
                 autocomplete="new-password" required>
          <button type="button" class="eye-btn"
                  onclick="tog('p1')" aria-label="Afficher/masquer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="grp">
        <label for="p2">Confirmation <span class="req">*</span></label>
        <div class="ib">
          <input type="password" id="p2" name="confirmation"
                 placeholder="Répétez"
                 autocomplete="new-password" required>
          <button type="button" class="eye-btn"
                  onclick="tog('p2')" aria-label="Afficher/masquer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="sep"><span>Vous êtes</span></div>

    <!-- Rôle -->
    <div class="roles" role="radiogroup" aria-label="Choisir un rôle">
      <label class="role-card" id="rce" onclick="pick('eleve')">
        <input type="radio" name="role" value="eleve"
               <?=$rp==='eleve'?'checked':''?>>
        <span class="rc-check rc-check-e" aria-hidden="true">✓</span>
        <div class="rc-icon ic-e" aria-hidden="true">🎓</div>
        <div class="rc-name">Élève</div>
        <div class="rc-sub">Apprendre un instrument</div>
      </label>

      <label class="role-card" id="rcp" onclick="pick('professeur')">
        <input type="radio" name="role" value="professeur"
               <?=$rp==='professeur'?'checked':''?>>
        <span class="rc-check rc-check-p" aria-hidden="true">✓</span>
        <div class="rc-icon ic-p" aria-hidden="true">🎼</div>
        <div class="rc-name">Professeur</div>
        <div class="rc-sub">Enseigner la musique</div>
      </label>
    </div>

    <!-- Champs Élève -->
    <div class="cond" id="ce">
      <div class="g2">
        <div class="grp">
          <label for="dom">Instrument</label>
          <select id="dom" name="domaine">
            <option value="">Choisir...</option>
            <option>Piano</option><option>Guitare</option>
            <option>Chant</option><option>Batterie</option>
            <option>Violon</option><option>Saxophone</option>
            <option>Trompette</option>
          </select>
        </div>
        <div class="grp">
          <label for="niv">Niveau</label>
          <select id="niv" name="niveau">
            <option value="">Choisir...</option>
            <option>Débutant</option>
            <option>Intermédiaire</option>
            <option>Avancé</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Champs Professeur -->
    <div class="cond" id="cp">
      <div class="grp">
        <label for="spec">Spécialité</label>
        <input type="text" id="spec" name="specialite"
               placeholder="Ex : Piano et Théorie musicale"
               value="<?=htmlspecialchars($_POST['specialite']??'')?>">
      </div>
      <div class="pin-block">
        <div class="pin-badge">
          <div class="pin-dot"></div>
          <span class="pin-label-txt">Accès restreint — Code PIN requis</span>
        </div>
        <label for="pin">Code PIN professeur <span class="req">*</span></label>
        <div class="ib">
          <input type="password" id="pin" name="code_pin"
                 placeholder="••••••••" autocomplete="off">
          <button type="button" class="eye-btn"
                  onclick="tog('pin')" aria-label="Afficher/masquer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-cta">
      Créer mon compte
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <path d="M5 12h14M12 5l7 7-7 7"/>
      </svg>
    </button>

  </form>

  <p class="lnk-bas">
    Déjà un compte ? <a href="login.php">Se connecter</a>
  </p>

</main>

<script>
function pick(r) {
  document.getElementById('rce').className = 'role-card' + (r==='eleve' ? ' on-e' : '');
  document.getElementById('rcp').className = 'role-card' + (r==='professeur' ? ' on-p' : '');
  document.getElementById('ce').style.display = r==='eleve' ? 'block' : 'none';
  document.getElementById('cp').style.display = r==='professeur' ? 'block' : 'none';
  document.querySelector('input[value="'+r+'"]').checked = true;
}
function tog(id) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
window.onload = function() {
  var r = '<?=$rp?>';
  if (r) pick(r);
};
</script>
</body>
</html>
