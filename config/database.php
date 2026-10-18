<?php
/**
 * StoreBridge database connection.
 *
 * Supports InfinityFree via config/database.local.php and environment-based
 * configuration for Docker or other hosts. Never commit database.local.php.
 */

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

$host = $host ?? (getenv('DB_HOST') ?: 'localhost');
$port = $port ?? (getenv('DB_PORT') ?: '3306');
$db   = $db   ?? (getenv('DB_NAME') ?: 'catalogue');
$user = $user ?? (getenv('DB_USER') ?: 'catalogue');
$pass = $pass ?? (getenv('DB_PASSWORD') ?: '');

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $db . ';charset=utf8mb4',
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    exit('Database unavailable.');
}
