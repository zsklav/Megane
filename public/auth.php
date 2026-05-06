<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function seed_default_users() {
    global $pdo;
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count > 0) return;

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Vanshikha Sri', 'admin@lensandframe.co', 'admin']);
    $stmt->execute(['staff', password_hash('staff123', PASSWORD_DEFAULT), 'Staff User',     'staff@lensandframe.co', 'staff']);
}

seed_default_users();

function current_user() {
    static $user = null;
    if ($user !== null) return $user;
    if (empty($_SESSION['user_id'])) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function require_auth() {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        echo '<h1>Forbidden</h1><p>Admin access required. <a href="index.php">Back to dashboard</a></p>';
        exit;
    }
}

function user_initial($u) {
    $name = $u['full_name'] ?: $u['username'];
    return strtoupper(mb_substr($name, 0, 1));
}
