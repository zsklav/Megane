<?php
require_once __DIR__ . '/auth.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            $upd = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([(int)$user['id']]);

            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Lens &amp; Frame Co.</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="brand-mark">◉</span>
      <span class="brand-name">Lens &amp; Frame Co.</span>
    </div>
    <h1>Sign in</h1>
    <p class="muted">Eyewear Inventory &amp; Sales Management</p>

    <?php if ($error): ?>
      <div class="auth-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <label>
        <span>Username</span>
        <input type="text" name="username" autofocus required value="<?= h($username) ?>" autocomplete="username">
      </label>
      <label>
        <span>Password</span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <div class="auth-hint">
      <strong>Demo credentials</strong>
      <div class="auth-hint-row"><code>admin</code> / <code>admin123</code> <span class="muted">(full access)</span></div>
      <div class="auth-hint-row"><code>staff</code> / <code>staff123</code> <span class="muted">(read + sales)</span></div>
    </div>
  </div>
  <p class="auth-footer">&copy; <?= date('Y') ?> Lens &amp; Frame Co.</p>
</div>
</body>
</html>
