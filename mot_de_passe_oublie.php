<?php
// ============================================================
//   GOMA SCHOOL INT'L — mot_de_passe_oublie.php
//   Étape 1 : L'utilisateur saisit son email
//   Auteur : Voltaire SEGNIBO
// ============================================================

session_start();
require 'connexion.php';

// Config SMTP — À mettre à jour avec tes vrais identifiants
define('SMTP_HOST',     'smtp.gmail.com');      // ou smtp.gomaschool.bj
define('SMTP_PORT',     587);
define('SMTP_USER',     'voltairesegnibo@gmail.com'); // ton email pro : admin@gomaschool.bj
define('SMTP_PASS',     'tcdqswgcfzypzybx');    // mot de passe app Gmail
define('SMTP_FROM',     'voltairesegnibo@gmail.com');
define('SMTP_FROM_NOM', 'GOMA School Int\'l');
define('APP_URL',       ''); // Optionnel : ex. https://gomaschool.bj. Vide = auto.

$message = '';
$type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Veuillez saisir une adresse email valide.";
        $type    = 'err';
    } else {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT id, nom FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Sécurité : on ne révèle pas si l'email existe ou non
            $message = "Si cet email existe dans notre système, vous recevrez un lien de réinitialisation.";
            $type    = 'ok';
        } else {
            // Générer un token sécurisé
            $token    = bin2hex(random_bytes(32));
            $expire   = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Supprimer les anciens tokens de cet utilisateur
            $pdo->prepare("DELETE FROM reset_mdp WHERE id_user = ?")
                ->execute([$user['id']]);

            // Enregistrer le token
            $pdo->prepare("
                INSERT INTO reset_mdp (id_user, token, expire_at)
                VALUES (?, ?, ?)
            ")->execute([$user['id'], $token, $expire]);

            // Construire le lien de reset
            $lien = app_base_url() . '/reset_mot_de_passe.php?token=' . urlencode($token);

            // Envoyer l'email via PHPMailer (ou fallback mail())
            $envoye = envoyer_email_reset($email, $user['nom'], $lien);

            if ($envoye) {
                $message = "Un lien de réinitialisation a été envoyé à <strong class=\"email-inline\">" . htmlspecialchars($email) . "</strong>. Vérifiez votre boîte mail et vos spams.";
                $type    = 'ok';
            } else {
                // Fallback : afficher le lien directement (mode développement)
                $message = "Email non envoyé (config SMTP à finaliser).<br>
                            <strong>Lien de reset (dev uniquement) :</strong><br>
                            <a href='{$lien}' style='color:#7DEEAB;word-break:break-all'>{$lien}</a>";
                $type    = 'warn';
            }
        }
    }
}

function app_base_url(): string {
    if (defined('APP_URL') && trim(APP_URL) !== '') {
        return rtrim(APP_URL, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return $scheme . '://' . $host . ($dir === '' ? '' : $dir);
}

// ── Fonction d'envoi email ────────────────────────────────
function envoyer_email_reset(string $email, string $nom, string $lien): bool {
    // Vérifie si PHPMailer est installé (composer require phpmailer/phpmailer)
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
        try {
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return envoyer_email_smtp($email, $nom, $lien);
            }

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NOM);
            $mail->addAddress($email, $nom);
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe — GOMA School';
            $mail->Body    = email_html($nom, $lien);
            $mail->AltBody = "Bonjour {$nom},\n\nCliquez ici pour réinitialiser votre mot de passe :\n{$lien}\n\nCe lien expire dans 1 heure.\n\nGOMA School Int'l";

            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log("Erreur email : " . $e->getMessage());
            return envoyer_email_smtp($email, $nom, $lien);
        }
    }

    return envoyer_email_smtp($email, $nom, $lien);
}

function envoyer_email_smtp(string $email, string $nom, string $lien): bool {
    if (!extension_loaded('openssl')) {
        return envoyer_email_mail_php($email, $nom, $lien);
    }

    $socket = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        error_log("Erreur SMTP connexion : {$errno} {$errstr}");
        return envoyer_email_mail_php($email, $nom, $lien);
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_attendre($socket, [220]);
        smtp_commande($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        smtp_commande($socket, 'STARTTLS', [220]);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Impossible d activer TLS pour SMTP.');
        }

        smtp_commande($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        smtp_commande($socket, 'AUTH LOGIN', [334]);
        smtp_commande($socket, base64_encode(SMTP_USER), [334]);
        smtp_commande($socket, base64_encode(SMTP_PASS), [235]);
        smtp_commande($socket, 'MAIL FROM:<' . SMTP_FROM . '>', [250]);
        smtp_commande($socket, 'RCPT TO:<' . $email . '>', [250, 251]);
        smtp_commande($socket, 'DATA', [354]);

        $message = construire_message_email($email, $nom, $lien);
        fwrite($socket, str_replace("\n.", "\n..", $message) . "\r\n.\r\n");
        smtp_attendre($socket, [250]);
        smtp_commande($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        fclose($socket);
        error_log('Erreur SMTP : ' . $e->getMessage());
        return envoyer_email_mail_php($email, $nom, $lien);
    }
}

function smtp_commande($socket, string $commande, array $codes): string {
    fwrite($socket, $commande . "\r\n");
    return smtp_attendre($socket, $codes);
}

function smtp_attendre($socket, array $codes): string {
    $reponse = '';

    while (($ligne = fgets($socket, 515)) !== false) {
        $reponse .= $ligne;
        if (isset($ligne[3]) && $ligne[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($reponse, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('Réponse SMTP inattendue : ' . trim($reponse));
    }

    return $reponse;
}

function construire_message_email(string $email, string $nom, string $lien): string {
    $sujet = '=?UTF-8?B?' . base64_encode('Réinitialisation de votre mot de passe - GOMA School') . '?=';
    $html = email_html($nom, $lien);
    $texte = "Bonjour {$nom},\r\n\r\nCliquez ici pour réinitialiser votre mot de passe :\r\n{$lien}\r\n\r\nCe lien expire dans 1 heure.\r\n\r\nGOMA School Int'l";
    $boundary = '=_goma_' . bin2hex(random_bytes(12));

    $headers = [
        'From: ' . SMTP_FROM_NOM . ' <' . SMTP_FROM . '>',
        'To: ' . $nom . ' <' . $email . '>',
        'Subject: ' . $sujet,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    return implode("\r\n", $headers)
        . "\r\n\r\n--{$boundary}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $texte
        . "\r\n\r\n--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $html
        . "\r\n\r\n--{$boundary}--";
}

function envoyer_email_mail_php(string $email, string $nom, string $lien): bool {
    // Fallback : mail() natif PHP (fonctionne si SMTP configuré dans php.ini)
    $sujet  = '=?UTF-8?B?' . base64_encode('Réinitialisation mot de passe — GOMA School') . '?=';
    $corps  = "Bonjour {$nom},\n\nCliquez sur ce lien pour réinitialiser votre mot de passe :\n{$lien}\n\nCe lien expire dans 1 heure.\n\nGOMA School Int'l";
    $headers = "From: " . SMTP_FROM_NOM . " <" . SMTP_FROM . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $sujet, $corps, $headers);
}

// ── Template HTML de l'email ──────────────────────────────
function email_html(string $nom, string $lien): string {
    return '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
  .head{background:#0F0D0A;padding:32px;text-align:center}
  .head h1{color:#fff;font-size:22px;margin:0}
  .head h1 span{color:#E8B455}
  .body{padding:36px 32px}
  .body p{color:#444;font-size:15px;line-height:1.7;margin-bottom:16px}
  .btn{display:block;width:fit-content;margin:24px auto;padding:14px 36px;background:linear-gradient(135deg,#C8922A,#E8B455);color:#0F0D0A;font-size:15px;font-weight:700;text-decoration:none;border-radius:8px}
  .note{font-size:12px;color:#999;text-align:center;margin-top:24px}
  .foot{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#aaa;border-top:1px solid #eee}
</style></head>
<body>
<div class="wrap">
  <div class="head"><h1>GOMA <span>School</span> Int\'l</h1></div>
  <div class="body">
    <p>Bonjour <strong>' . htmlspecialchars($nom) . '</strong>,</p>
    <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en créer un nouveau :</p>
    <a href="' . htmlspecialchars($lien) . '" class="btn">Réinitialiser mon mot de passe</a>
    <p>Ce lien est valable pendant <strong>1 heure</strong>. Après ce délai, vous devrez faire une nouvelle demande.</p>
    <p>Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe reste inchangé.</p>
    <p class="note">Lien direct : <a href="' . htmlspecialchars($lien) . '" style="color:#C8922A">' . htmlspecialchars($lien) . '</a></p>
  </div>
  <div class="foot">GOMA School Int\'l — Misséreté · Donoukin · Tokpota, Bénin</div>
</div>
</body></html>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mot de passe oublié — GOMA School Int'l</title>
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
body{font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:96px 16px 32px;position:relative;overflow-x:hidden}
.bg{position:fixed;inset:0;z-index:0;background:url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1800&q=85') center 30%/cover no-repeat}
.bg-darken{position:fixed;inset:0;z-index:1;background:linear-gradient(160deg,rgba(8,5,2,0.82) 0%,rgba(12,8,4,0.68) 40%,rgba(8,5,2,0.78) 100%)}
.bg-vignette{position:fixed;inset:0;z-index:2;background:radial-gradient(ellipse at center,transparent 30%,rgba(0,0,0,0.55) 100%);pointer-events:none}
.bg-glow{position:fixed;inset:0;z-index:2;background:radial-gradient(ellipse 60% 50% at 15% 80%,rgba(200,146,42,0.12) 0%,transparent 60%);pointer-events:none}
.top-nav{position:fixed;top:22px;left:50%;transform:translateX(-50%);z-index:20;display:flex;align-items:center;gap:10px;background:rgba(15,13,10,0.55);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid var(--border);border-radius:50px;padding:7px 22px 7px 8px;white-space:nowrap}
.nav-dot{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-lt));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:var(--ink)}
.nav-name{font-family:'DM Serif Display',serif;font-size:14px;color:rgba(255,255,255,0.88)}
.nav-name em{color:var(--gold-lt);font-style:normal}
.card{position:relative;z-index:10;width:min(100%,460px);background:rgba(10,8,5,0.68);backdrop-filter:blur(40px) saturate(160%);-webkit-backdrop-filter:blur(40px) saturate(160%);border:1px solid var(--border);border-radius:var(--r);padding:42px 36px 36px;box-shadow:var(--shadow),inset 0 1px 0 rgba(255,255,255,0.09);animation:cardIn .55s cubic-bezier(.22,1,.36,1) both}
@keyframes cardIn{from{opacity:0;transform:translateY(26px) scale(.975)}to{opacity:1;transform:translateY(0) scale(1)}}
.card::before{content:'';position:absolute;top:0;left:32px;right:32px;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);border-radius:50%}
.card-icon{width:56px;height:56px;border-radius:14px;background:var(--gold-dim);border:1px solid rgba(200,146,42,0.22);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 20px}
.card-head{text-align:center;margin-bottom:26px}
.card-head h1{font-family:'DM Serif Display',serif;font-size:26px;font-weight:400;color:var(--white);letter-spacing:-.2px;margin-bottom:6px}
.card-head h1 span{color:var(--gold-lt);font-style:italic}
.card-head p{max-width:300px;margin:0 auto;font-size:13px;line-height:1.55;color:rgba(255,255,255,0.48)}
.alert{display:block;width:100%;padding:14px 16px;border-radius:var(--r-sm);margin-bottom:20px;font-size:13.5px;font-weight:500;line-height:1.65;text-align:left;overflow-wrap:anywhere}
.alert strong{font-weight:800}
.email-inline{display:inline;color:#A8F7C5;white-space:normal}
.alert-err{background:rgba(224,82,82,0.14);border:1px solid rgba(224,82,82,0.28);color:#FF9090}
.alert-ok{background:rgba(76,175,125,0.14);border:1px solid rgba(76,175,125,0.28);color:#7DEEAB}
.alert-warn{background:rgba(200,146,42,0.14);border:1px solid rgba(200,146,42,0.28);color:var(--gold-lt)}
.alert a{color:inherit;font-weight:800}
.grp{margin-bottom:14px}
.grp label{display:block;font-size:10.5px;font-weight:600;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.9px;margin-bottom:6px}
input{width:100%;padding:12px 14px;background:var(--mist);border:1px solid var(--border);border-radius:var(--r-sm);font-size:14px;color:var(--white);outline:none;transition:all .18s;font-family:'DM Sans',sans-serif;-webkit-appearance:none}
input::placeholder{color:rgba(255,255,255,0.2)}
input:focus{background:var(--mist2);border-color:rgba(200,146,42,0.5);box-shadow:0 0 0 3px rgba(200,146,42,0.10)}
.btn-cta{width:100%;padding:13.5px;background:linear-gradient(135deg,var(--gold),var(--gold-lt));color:var(--ink);border:none;border-radius:var(--r-sm);font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:6px;transition:all .22s;box-shadow:0 6px 24px rgba(200,146,42,0.30)}
.btn-cta:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(200,146,42,0.40)}
.btn-cta:active{transform:translateY(0)}
.lnk-bas{text-align:center;margin-top:18px;font-size:13px;color:rgba(255,255,255,0.3)}
.lnk-bas a{color:rgba(255,255,255,0.6);font-weight:600;text-decoration:none;border-bottom:1px solid rgba(255,255,255,0.15);padding-bottom:1px;transition:all .18s}
.lnk-bas a:hover{color:var(--gold-lt);border-bottom-color:var(--gold-lt)}
@media(max-width:460px){body{padding:88px 14px 24px}.card{padding:32px 20px 28px}.card-head h1{font-size:24px}.alert{font-size:13px}}
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
  <div class="card-icon">🔑</div>
  <div class="card-head">
    <h1>Mot de passe <span>oublié ?</span></h1>
    <p>Saisissez votre email pour recevoir un lien de réinitialisation</p>
  </div>

  <?php if($message):?>
  <div class="alert alert-<?=$type?>"><?=$message?></div>
  <?php endif;?>

  <?php if($type !== 'ok'):?>
  <form method="POST" action="mot_de_passe_oublie.php" novalidate>
    <div class="grp">
      <label for="email">Adresse email</label>
      <input type="email" id="email" name="email"
             placeholder="jean@exemple.com"
             value="<?=htmlspecialchars($_POST['email']??'')?>"
             autocomplete="email" autofocus required>
    </div>
    <button type="submit" class="btn-cta">
      Envoyer le lien
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
  </form>
  <?php endif;?>

  <p class="lnk-bas"><a href="login.php">← Retour à la connexion</a></p>
</main>
</body>
</html>
