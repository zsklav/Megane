<?php
require_once __DIR__ . '/auth.php';
require_auth();

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '') {
            $flash = 'Customer name is required.';
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email ?: null, $phone ?: null, $address ?: null]);
            header('Location: customers.php?msg=added');
            exit;
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$name, $email ?: null, $phone ?: null, $address ?: null, $id]);
            header('Location: customers.php?msg=updated');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: customers.php?msg=deleted');
        exit;
    }
}

$msg_map = ['added' => 'Customer added.', 'updated' => 'Customer updated.', 'deleted' => 'Customer deleted.'];
$flash = $flash ?: ($msg_map[$_GET['msg'] ?? ''] ?? '');

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_price), 0) AS total_spent
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id
        WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $like = "%{$search}%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_price), 0) AS total_spent
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
}
$customers = $stmt->fetchAll();

$edit_customer = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_customer = $stmt->fetch() ?: null;
}

$page_title = 'Customers';
$active = 'customers';
include __DIR__ . '/header.php';
?>

<section class="page-header">
  <h1>Customers</h1>
  <p class="muted">Customer directory with order history and lifetime value.</p>
</section>

<?php if ($flash): ?>
  <div class="flash"><?= h($flash) ?></div>
<?php endif; ?>

<section class="card">
  <h2><?= $edit_customer ? 'Edit Customer' : 'Add Customer' ?></h2>
  <form method="POST" class="form-grid" data-validate>
    <input type="hidden" name="action" value="<?= $edit_customer ? 'edit' : 'add' ?>">
    <?php if ($edit_customer): ?>
      <input type="hidden" name="id" value="<?= (int)$edit_customer['id'] ?>">
    <?php endif; ?>

    <label>
      <span>Name *</span>
      <input type="text" name="name" required value="<?= h($edit_customer['name'] ?? '') ?>">
    </label>
    <label>
      <span>Email</span>
      <input type="email" name="email" value="<?= h($edit_customer['email'] ?? '') ?>">
    </label>
    <label>
      <span>Phone</span>
      <input type="tel" name="phone" value="<?= h($edit_customer['phone'] ?? '') ?>">
    </label>
    <label class="span-2">
      <span>Address</span>
      <input type="text" name="address" value="<?= h($edit_customer['address'] ?? '') ?>">
    </label>
    <div class="form-actions span-2">
      <button type="submit" class="btn btn-primary"><?= $edit_customer ? 'Save Changes' : 'Add Customer' ?></button>
      <?php if ($edit_customer): ?>
        <a href="customers.php" class="btn btn-ghost">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="card">
  <div class="card-header">
    <h2>Customers (<?= count($customers) ?>)</h2>
    <form method="GET" class="filter-bar">
      <input type="search" name="q" placeholder="Search name, email, phone…" value="<?= h($search) ?>">
      <button type="submit" class="btn btn-ghost">Search</button>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th class="num">Orders</th>
        <th class="num">Total Spent</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="7" class="muted">No customers yet.</td></tr>
      <?php else: ?>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td><?= h($c['name']) ?></td>
            <td><?= h($c['email'] ?? '—') ?></td>
            <td><?= h($c['phone'] ?? '—') ?></td>
            <td><?= h($c['address'] ?? '—') ?></td>
            <td class="num"><?= (int)$c['order_count'] ?></td>
            <td class="num"><?= fmt_currency($c['total_spent']) ?></td>
            <td class="row-actions">
              <a href="customers.php?edit=<?= (int)$c['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
              <form method="POST" class="inline" data-confirm="Delete this customer? Their orders will also be removed.">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/footer.php'; ?>
