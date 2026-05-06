<?php
require_once __DIR__ . '/auth.php';
require_auth();

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $product_id  = (int)($_POST['product_id'] ?? 0);
        $quantity    = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare("SELECT id, price, stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $flash = 'Product not found.';
        } elseif ((int)$product['stock'] < $quantity) {
            $flash = 'Insufficient stock for this order.';
        } else {
            $total = (float)$product['price'] * $quantity;

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO orders (customer_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$customer_id, $product_id, $quantity, $total]);

                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);

                $pdo->commit();
                header('Location: sales.php?msg=created');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = 'Failed to record sale: ' . htmlspecialchars($e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT product_id, quantity FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->execute([(int)$order['quantity'], (int)$order['product_id']]);

                $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                header('Location: sales.php?msg=deleted');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = 'Failed to delete order.';
            }
        }
    }
}

$msg_map = ['created' => 'Sale recorded.', 'deleted' => 'Order deleted; stock restored.'];
$flash = $flash ?: ($msg_map[$_GET['msg'] ?? ''] ?? '');

$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$products  = $pdo->query("SELECT id, name, type, price, stock FROM products WHERE stock > 0 ORDER BY name")->fetchAll();

$orders = $pdo->query("
    SELECT o.id, o.quantity, o.total_price, o.order_date,
           c.name AS customer_name,
           p.name AS product_name, p.type AS product_type, p.price AS product_price
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p  ON o.product_id  = p.id
    ORDER BY o.order_date DESC
")->fetchAll();

$page_title = 'Sales';
$active = 'sales';
include __DIR__ . '/header.php';
?>

<section class="page-header">
  <h1>Sales</h1>
  <p class="muted">Record a new sale or browse order history. Stock auto-adjusts on each transaction.</p>
</section>

<?php if ($flash): ?>
  <div class="flash"><?= h($flash) ?></div>
<?php endif; ?>

<section class="card">
  <h2>Record New Sale</h2>
  <form method="POST" class="form-grid" data-validate>
    <input type="hidden" name="action" value="add">

    <label>
      <span>Customer *</span>
      <select name="customer_id" required>
        <option value="">— Select customer —</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Product *</span>
      <select name="product_id" id="product-select" required data-products='<?= h(json_encode(array_map(fn($p) => [
          "id" => (int)$p["id"], "name" => $p["name"], "type" => $p["type"],
          "price" => (float)$p["price"], "stock" => (int)$p["stock"]
      ], $products))) ?>'>
        <option value="">— Select product —</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>" data-price="<?= (float)$p['price'] ?>" data-stock="<?= (int)$p['stock'] ?>">
            <?= h($p['name']) ?> (<?= h($p['type']) ?>) — <?= fmt_currency($p['price']) ?> · <?= (int)$p['stock'] ?> in stock
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Quantity *</span>
      <input type="number" name="quantity" id="quantity-input" min="1" value="1" required>
    </label>
    <label>
      <span>Estimated Total</span>
      <input type="text" id="total-preview" readonly placeholder="¥0">
    </label>

    <div class="form-actions span-2">
      <button type="submit" class="btn btn-primary">Record Sale</button>
    </div>
  </form>
  <?php if (empty($customers) || empty($products)): ?>
    <p class="muted">
      <?php if (empty($customers)): ?>Add a customer first.<?php endif; ?>
      <?php if (empty($products)): ?>No products with stock available.<?php endif; ?>
    </p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Order History (<?= count($orders) ?>)</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Customer</th>
        <th>Product</th>
        <th>Type</th>
        <th class="num">Unit Price</th>
        <th class="num">Qty</th>
        <th class="num">Total</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="muted">No orders recorded.</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= h(date('Y-m-d', strtotime($o['order_date']))) ?></td>
            <td><?= h($o['customer_name']) ?></td>
            <td><?= h($o['product_name']) ?></td>
            <td><span class="tag tag-<?= strtolower($o['product_type']) ?>"><?= h($o['product_type']) ?></span></td>
            <td class="num"><?= fmt_currency($o['product_price']) ?></td>
            <td class="num"><?= (int)$o['quantity'] ?></td>
            <td class="num"><?= fmt_currency($o['total_price']) ?></td>
            <td class="row-actions">
              <form method="POST" class="inline" data-confirm="Delete this order? Stock will be restored.">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
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
