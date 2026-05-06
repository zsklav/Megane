<?php
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'eyewear_db';
$user = getenv('DB_USER') ?: 'eyewear_user';
$pass = getenv('DB_PASS') ?: 'eyewear_pass';

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

$max_attempts = 30;
$attempt = 0;
$pdo = null;

while ($attempt < $max_attempts) {
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        break;
    } catch (PDOException $e) {
        $attempt++;
        if ($attempt >= $max_attempts) {
            http_response_code(500);
            die("Database connection failed after {$max_attempts} attempts: " . htmlspecialchars($e->getMessage()));
        }
        sleep(1);
    }
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function fmt_currency($n) {
    return '¥' . number_format((float)$n, 0);
}
