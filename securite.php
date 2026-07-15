<?php
// ============================================================
//   GOMA SCHOOL INT'L — securite.php
//   Fonctions de sécurité globales
//   À inclure après connexion.php dans tous les fichiers
// ============================================================

// ── 1. HEADERS DE SÉCURITÉ HTTP ───────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; img-src 'self' data: https://images.unsplash.com;");

// ── 2. PROTECTION XSS ─────────────────────────────────────
// À utiliser sur toute valeur affichée dans le HTML
function xss(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Nettoyer un tableau entier (ex: $_POST)
function xss_array(array $data): array {
    return array_map(fn($v) => is_string($v) ? xss($v) : $v, $data);
}

// ── 3. PROTECTION CSRF ────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Champ caché à insérer dans chaque formulaire
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Vérifier le token à la soumission du formulaire
function csrf_verifier(): void {
    $token_recu    = $_POST['csrf_token']    ?? '';
    $token_session = $_SESSION['csrf_token'] ?? '';

    if (empty($token_recu) || !hash_equals($token_session, $token_recu)) {
        http_response_code(403);
        error_log('[GOMA SCHOOL] CSRF détecté — IP : ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        die('Requête non autorisée.');
    }

    // Renouveler après usage
    unset($_SESSION['csrf_token']);
}

// ── 4. PROTECTION FORCE BRUTE (login) ─────────────────────
// Max 5 tentatives en 15 minutes par email
function tentative_ok(string $email): bool {
    $key = 'tentatives_' . md5($email);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['nb' => 0, 'depuis' => time()];
    }
    $s = &$_SESSION[$key];
    if (time() - $s['depuis'] > 900) {          // reset après 15 min
        $s = ['nb' => 0, 'depuis' => time()];
    }
    return $s['nb'] < 5;
}

function tentative_incrementer(string $email): void {
    $key = 'tentatives_' . md5($email);
    $_SESSION[$key]['nb']++;
}

function tentative_reset(string $email): void {
    unset($_SESSION['tentatives_' . md5($email)]);
}

function tentatives_restantes(string $email): int {
    $key = 'tentatives_' . md5($email);
    return max(0, 5 - ($_SESSION[$key]['nb'] ?? 0));
}

// ── 5. SÉCURITÉ DES SESSIONS ──────────────────────────────
function session_securiser(): void {
    if (!isset($_SESSION['_init'])) {
        session_regenerate_id(true);
        $_SESSION['_init'] = true;
        $_SESSION['_ip']   = $_SERVER['REMOTE_ADDR']     ?? '';
        $_SESSION['_ua']   = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // Vérifier l'IP et le navigateur
    if (
        ($_SESSION['_ip'] !== ($_SERVER['REMOTE_ADDR']     ?? '')) ||
        ($_SESSION['_ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))
    ) {
        session_destroy();
        header('Location: login.php?err=session');
        exit;
    }

    // Expiration après 30 min d'inactivité
    if (isset($_SESSION['_actif']) && time() - $_SESSION['_actif'] > 1800) {
        session_destroy();
        header('Location: login.php?err=expire');
        exit;
    }

    $_SESSION['_actif'] = time();
}

// ── 6. PROTECTION DES PAGES (rôle requis) ─────────────────
function auth_requise(string $role = ''): void {
    if (!isset($_SESSION['id'])) {
        header('Location: login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: login.php?err=acces');
        exit;
    }
}

// ── 7. VALIDATION ─────────────────────────────────────────
function valider_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valider_telephone(string $tel): bool {
    return (bool) preg_match('/^[\+0-9\s\-]{8,15}$/', $tel);
}

function valider_montant(float $m): bool {
    return $m > 0 && $m <= 10_000_000;
}

function valider_note(float $n): bool {
    return $n >= 0 && $n <= 20;
}

// Force mot de passe : retourne 0 (faible) à 5 (très fort)
function force_mdp(string $mdp): int {
    $score = 0;
    if (strlen($mdp) >= 6)              $score++;
    if (strlen($mdp) >= 10)             $score++;
    if (preg_match('/[A-Z]/', $mdp))    $score++;
    if (preg_match('/[0-9]/', $mdp))    $score++;
    if (preg_match('/[^A-Za-z0-9]/', $mdp)) $score++;
    return $score;
}

// ── 8. JOURNALISATION ─────────────────────────────────────
function journaliser(string $msg, string $niveau = 'INFO'): void {
    $dir = __DIR__ . '/logs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
    $user = $_SESSION['id'] ?? 'non connecté';
    $line = sprintf(
        "[%s] [%s] [IP:%s] [User:%s] %s\n",
        date('Y-m-d H:i:s'), $niveau, $ip, $user, $msg
    );

    file_put_contents(
        $dir . 'goma_' . date('Y-m-d') . '.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}

// ── 9. GÉNÉRATION NUMÉRO DE REÇU ──────────────────────────
function generer_numero_recu(int $id_paiement): string {
    return 'GOMA-' . date('Ymd') . '-' . str_pad($id_paiement, 4, '0', STR_PAD_LEFT);
}
