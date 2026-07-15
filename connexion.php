<?php

// ── Configuration ─────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'goma_school');
define('DB_USER',    'root');   // utilisateur XAMPP par défaut
define('DB_PASS',    '');       // mot de passe vide par défaut XAMPP
define('DB_CHARSET', 'utf8mb4');

// ── Connexion PDO ──────────────────────────────────────────
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    // En production, ne jamais afficher le message d'erreur réel
    error_log('[GOMA SCHOOL] Erreur BDD : ' . $e->getMessage());
    die(json_encode([
        'erreur' => 'Connexion à la base de données impossible. Contactez l\'administrateur.'
    ]));
}
