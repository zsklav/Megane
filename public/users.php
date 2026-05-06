<?php
require_once __DIR__ . '/auth.php';
require_auth();
require_admin();

$current = current_user();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'staff';

        if ($username === '' || strlen($password) < 6 || !in_array($role, ['admin','staff'], true)) {
            $flash = 'Invalid input. Username required, password ≥ 6 chars, valid role.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $full_name ?: null, $email ?: null, $role]);
                header('Location: users.php?msg=added');
                exit;
            } catch (PDOException $e) {
                $flash = 'Username already exists.';
            }
        }
    } elseif ($action === 'edit') {
        $id        = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'staff';
        $password  = $_POST['password'] ?? '';

        if (!in_array($role, ['admin','staff'], true)) {
            $flash = 'Invalid role.';
        } else {
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $flash = 'Password must be at least 6 characters.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, role=?, password_hash=? WHERE id=?");
                    $stmt->execute([$full_name ?: null, $email ?: null, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                    header('Location: users.php?msg=updated');
                    exit;
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
                $stmt->execute([$full_name ?: null, $email ?: null, $role, $id]);
                header('Location: users.php?msg=updated');
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$current['id']) {
            $flash = 'You cannot delete your own account.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: users.php?msg=deleted');
            exit;
        }
    }
}

$msg_map = ['added' => 'User created.', 'updated' => 'User updated.', 'deleted' => 'User deleted.'];
$flash = $flash ?: ($msg_map[$_GET['msg'] ?? ''] ?? '');

$users = $pdo->query("SELECT id, username, full_name, email, role, created_at, last_login FROM users ORDER BY created_at DESC")->fetchAll();

$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_user = $stmt->fetch() ?: null;
}

$page_title = 'Users';
$active = 'users';
include __DIR__ . '/header.php';
?>

<section class="page-header">
  <h1>User Management</h1>
  <p class="muted">Admin-only — create accounts, manage roles, reset passwords.</p>
</section>

<?php if ($flash): ?>
  <div class="flash"><?= h($flash) ?></div>
<?php endif; ?>

<section class="card">
  <h2><?= $edit_user ? 'Edit User: ' . h($edit_user['username']) : 'Add User' ?></h2>
  <form method="POST" class="form-grid" data-validate>
    <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'add' ?>">
    <?php if ($edit_user): ?>
      <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">
    <?php endif; ?>

    <?php if (!$edit_user): ?>
    <label>
      <span>Username *</span>
      <input type="text" name="username" required autocomplete="off">
    </label>
    <?php else: ?>
    <label>
      <span>Username</span>
      <input type="text" value="<?= h($edit_user['username']) ?>" disabled>
    </label>
    <?php endif; ?>

    <label>
      <span><?= $edit_user ? 'New Password (leave blank to keep)' : 'Password *' ?></span>
      <input type="password" name="password" <?= $edit_user ? '' : 'required minlength=6' ?> autocomplete="new-password">
    </label>
    <label>
      <span>Full Name</span>
      <input type="text" name="full_name" value="<?= h($edit_user['full_name'] ?? '') ?>">
    </label>
    <label>
      <span>Email</span>
      <input type="email" name="email" value="<?= h($edit_user['email'] ?? '') ?>">
    </label>
    <label>
      <span>Role *</span>
      <select name="role" required>
        <option value="staff" <?= ($edit_user['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff (read + sales)</option>
        <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (full access)</option>
      </select>
    </label>
    <div class="form-actions span-2">
      <button type="submit" class="btn btn-primary"><?= $edit_user ? 'Save Changes' : 'Add User' ?></button>
      <?php if ($edit_user): ?>
        <a href="users.php" class="btn btn-ghost">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="card">
  <h2>All Users (<?= count($users) ?>)</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Last Login</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= h($u['username']) ?></strong> <?= $u['id'] === $current['id'] ? '<span class="muted">(you)</span>' : '' ?></td>
          <td><?= h($u['full_name'] ?? '—') ?></td>
          <td><?= h($u['email'] ?? '—') ?></td>
          <td><span class="role-badge role-<?= h($u['role']) ?>"><?= h(ucfirst($u['role'])) ?></span></td>
          <td><?= $u['last_login'] ? h(date('Y-m-d H:i', strtotime($u['last_login']))) : '<span class="muted">never</span>' ?></td>
          <td class="row-actions">
            <a href="users.php?edit=<?= (int)$u['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
            <?php if ($u['id'] !== $current['id']): ?>
              <form method="POST" class="inline" data-confirm="Delete user <?= h($u['username']) ?>?">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/footer.php'; ?>
