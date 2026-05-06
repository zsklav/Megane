<?php
require_once __DIR__ . '/auth.php';
require_auth();
$current = current_user();
$is_admin = $current['role'] === 'admin';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        http_response_code(403);
        die('Forbidden — admin access required to modify inventory.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name      = trim($_POST['name'] ?? '');
        $type      = $_POST['type'] ?? 'Frame';
        $brand     = trim($_POST['brand'] ?? '');
        $style     = trim($_POST['style'] ?? '');
        $material  = trim($_POST['material'] ?? '');
        $price     = (float)($_POST['price'] ?? 0);
        $stock     = (int)($_POST['stock'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');

        if ($name === '' || !in_array($type, ['Frame', 'Lens'], true) || $price < 0 || $stock < 0) {
            $flash = 'Invalid input. Please check the form.';
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (name, type, brand, style, material, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $brand ?: null, $style ?: null, $material ?: null, $price, $stock, $image_url ?: null]);
            header('Location: inventory.php?msg=added');
            exit;
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE products SET name=?, type=?, brand=?, style=?, material=?, price=?, stock=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $type, $brand ?: null, $style ?: null, $material ?: null, $price, $stock, $image_url ?: null, $id]);
            header('Location: inventory.php?msg=updated');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: inventory.php?msg=deleted');
        exit;
    }
}

$msg_map = ['added' => 'Product added.', 'updated' => 'Product updated.', 'deleted' => 'Product deleted.'];
$flash = $flash ?: ($msg_map[$_GET['msg'] ?? ''] ?? '');

$filter_type = $_GET['type'] ?? 'all';
$search      = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($filter_type === 'Frame' || $filter_type === 'Lens') {
    $where[] = 'type = ?';
    $params[] = $filter_type;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR brand LIKE ? OR style LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM products {$where_sql} ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_product = $stmt->fetch() ?: null;
}

$page_title = 'Inventory';
$active = 'inventory';
include __DIR__ . '/header.php';
?>

<section class="page-header">
  <h1>Inventory</h1>
  <p class="muted">Manage frames and lenses — add, edit, and track stock.</p>
</section>

<?php if ($flash): ?>
  <div class="flash"><?= h($flash) ?></div>
<?php endif; ?>

<?php if ($is_admin): ?>
<section class="card">
  <h2><?= $edit_product ? 'Edit Product' : 'Add Product' ?></h2>
  <form method="POST" class="form-grid" data-validate>
    <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
    <?php if ($edit_product): ?>
      <input type="hidden" name="id" value="<?= (int)$edit_product['id'] ?>">
    <?php endif; ?>

    <label>
      <span>Name *</span>
      <input type="text" name="name" required value="<?= h($edit_product['name'] ?? '') ?>">
    </label>
    <label>
      <span>Type *</span>
      <select name="type" required>
        <option value="Frame" <?= ($edit_product['type'] ?? '') === 'Frame' ? 'selected' : '' ?>>Frame</option>
        <option value="Lens"  <?= ($edit_product['type'] ?? '') === 'Lens'  ? 'selected' : '' ?>>Lens</option>
      </select>
    </label>
    <label>
      <span>Brand</span>
      <input type="text" name="brand" value="<?= h($edit_product['brand'] ?? '') ?>">
    </label>
    <label>
      <span>Style</span>
      <input type="text" name="style" placeholder="e.g. Aviator, Wayfarer, Progressive" value="<?= h($edit_product['style'] ?? '') ?>">
    </label>
    <label>
      <span>Material</span>
      <input type="text" name="material" placeholder="e.g. Titanium, Acetate" value="<?= h($edit_product['material'] ?? '') ?>">
    </label>
    <label>
      <span>Price (¥) *</span>
      <input type="number" name="price" min="0" step="0.01" required value="<?= h($edit_product['price'] ?? '') ?>">
    </label>
    <label>
      <span>Stock *</span>
      <input type="number" name="stock" min="0" required value="<?= h($edit_product['stock'] ?? '0') ?>">
    </label>
    <label class="span-2">
      <span>Image URL</span>
      <input type="url" name="image_url" value="<?= h($edit_product['image_url'] ?? '') ?>">
    </label>
    <div class="form-actions span-2">
      <button type="submit" class="btn btn-primary"><?= $edit_product ? 'Save Changes' : 'Add Product' ?></button>
      <?php if ($edit_product): ?>
        <a href="inventory.php" class="btn btn-ghost">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <div class="card-header">
    <h2>Products (<?= count($products) ?>)</h2>
    <form method="GET" class="filter-bar">
      <input type="search" name="q" placeholder="Search name, brand, style…" value="<?= h($search) ?>">
      <select name="type" onchange="this.form.submit()">
        <option value="all"   <?= $filter_type === 'all'   ? 'selected' : '' ?>>All Types</option>
        <option value="Frame" <?= $filter_type === 'Frame' ? 'selected' : '' ?>>Frames</option>
        <option value="Lens"  <?= $filter_type === 'Lens'  ? 'selected' : '' ?>>Lenses</option>
      </select>
      <button type="submit" class="btn btn-ghost">Filter</button>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th></th>
        <th>Name</th>
        <th>Type</th>
        <th>Brand</th>
        <th>Style</th>
        <th class="num">Price</th>
        <th class="num">Stock</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
        <tr><td colspan="8" class="muted">No products match your filters.</td></tr>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <?php if (!empty($p['image_url'])): ?>
                <img class="thumb" src="<?= h($p['image_url']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <div class="thumb thumb-placeholder">◐</div>
              <?php endif; ?>
            </td>
            <td><?= h($p['name']) ?></td>
            <td><span class="tag tag-<?= strtolower($p['type']) ?>"><?= h($p['type']) ?></span></td>
            <td><?= h($p['brand'] ?? '—') ?></td>
            <td><?= h($p['style'] ?? '—') ?></td>
            <td class="num"><?= fmt_currency($p['price']) ?></td>
            <td class="num">
              <span class="<?= $p['stock'] < 15 ? 'stock-low' : '' ?>"><?= (int)$p['stock'] ?></span>
            </td>
            <td class="row-actions">
              <?php if ($is_admin): ?>
                <a href="inventory.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
                <form method="POST" class="inline" data-confirm="Delete this product?">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php else: ?>
                <span class="muted">view-only</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/footer.php'; ?>
