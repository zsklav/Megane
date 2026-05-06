<?php
require_once __DIR__ . '/auth.php';
require_auth();
$user = current_user();
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' — ' : '' ?>Lens &amp; Frame Co.</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <a href="index.php" class="brand">
      <span class="brand-mark">◉</span>
      <span class="brand-name">Lens &amp; Frame Co.</span>
    </a>
    <nav class="nav">
      <a href="index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="inventory.php" class="<?= $active === 'inventory' ? 'active' : '' ?>">Inventory</a>
      <a href="customers.php" class="<?= $active === 'customers' ? 'active' : '' ?>">Customers</a>
      <a href="sales.php" class="<?= $active === 'sales' ? 'active' : '' ?>">Sales</a>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="users.php" class="<?= $active === 'users' ? 'active' : '' ?>">Users</a>
      <?php endif; ?>
    </nav>
    <div class="user-menu" data-menu>
      <button class="user-btn" data-menu-trigger>
        <span class="user-avatar"><?= h(user_initial($user)) ?></span>
        <span class="user-name"><?= h($user['full_name'] ?: $user['username']) ?></span>
        <span class="user-caret">▾</span>
      </button>
      <div class="user-dropdown" data-menu-panel hidden>
        <div class="user-dropdown-header">
          <div class="user-dropdown-name"><?= h($user['full_name'] ?: $user['username']) ?></div>
          <div class="user-dropdown-meta"><?= h($user['email'] ?? '—') ?></div>
          <span class="role-badge role-<?= h($user['role']) ?>"><?= h(ucfirst($user['role'])) ?></span>
        </div>
        <a href="logout.php" class="user-dropdown-item">Sign out</a>
      </div>
    </div>
  </div>
</header>
<main class="container main">
