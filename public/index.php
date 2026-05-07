<?php
require_once __DIR__ . '/auth.php';
require_auth();

$total_products  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_frames    = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE type = 'Frame'")->fetchColumn();
$total_lenses    = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE type = 'Lens'")->fetchColumn();
$total_customers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_orders    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue   = (float)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn();
$total_stock     = (int)$pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();

$low_stock = $pdo->query("SELECT id, name, type, stock FROM products WHERE stock < 15 ORDER BY stock ASC LIMIT 5")->fetchAll();

$recent_orders = $pdo->query("
    SELECT o.id, o.quantity, o.total_price, o.order_date,
           c.name AS customer_name,
           p.name AS product_name, p.type AS product_type
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.order_date DESC
    LIMIT 5
")->fetchAll();

$sales_by_day = $pdo->query("
    SELECT DATE(order_date) AS day, SUM(total_price) AS revenue, COUNT(*) AS orders
    FROM orders
    WHERE order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
    GROUP BY DATE(order_date)
    ORDER BY day ASC
")->fetchAll();

$sales_by_type = $pdo->query("
    SELECT p.type, COALESCE(SUM(o.total_price), 0) AS revenue, COALESCE(SUM(o.quantity), 0) AS units
    FROM products p
    LEFT JOIN orders o ON o.product_id = p.id
    GROUP BY p.type
")->fetchAll();

$top_products = $pdo->query("
    SELECT p.name, p.type, COALESCE(SUM(o.quantity), 0) AS units_sold, COALESCE(SUM(o.total_price), 0) AS revenue
    FROM products p
    LEFT JOIN orders o ON o.product_id = p.id
    GROUP BY p.id, p.name, p.type
    HAVING units_sold > 0
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

$page_title = 'Dashboard';
$active = 'dashboard';
include __DIR__ . '/header.php';
?>

<section class="page-header">
  <h1>Dashboard</h1>
  <p class="muted">Overview of inventory, customers, and sales — welcome back, <?= h(current_user()['full_name'] ?: current_user()['username']) ?>.</p>
</section>

<section class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Products</div>
    <div class="stat-value"><?= $total_products ?></div>
    <div class="stat-meta"><?= $total_frames ?> frames · <?= $total_lenses ?> lenses</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Customers</div>
    <div class="stat-value"><?= $total_customers ?></div>
    <div class="stat-meta">Registered</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Orders</div>
    <div class="stat-value"><?= $total_orders ?></div>
    <div class="stat-meta">All-time</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Revenue</div>
    <div class="stat-value"><?= fmt_currency($total_revenue) ?></div>
    <div class="stat-meta">All-time gross</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Stock Units</div>
    <div class="stat-value"><?= $total_stock ?></div>
    <div class="stat-meta">Across all SKUs</div>
  </div>
</section>

<section class="grid-2">
  <div class="card">
    <div class="card-header">
      <h2>Sales Trend (Last 14 Days)</h2>
    </div>
    <div class="chart-box">
      <canvas id="salesChart"
              data-labels='<?= h(json_encode(array_map(fn($r) => date('M j', strtotime($r['day'])), $sales_by_day))) ?>'
              data-revenue='<?= h(json_encode(array_map(fn($r) => (float)$r['revenue'], $sales_by_day))) ?>'
              data-orders='<?= h(json_encode(array_map(fn($r) => (int)$r['orders'], $sales_by_day))) ?>'></canvas>
    </div>
    <?php if (empty($sales_by_day)): ?>
      <p class="muted center">No sales in the last 14 days.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>Revenue by Product Type</h2>
    </div>
    <div class="chart-box chart-box--doughnut">
      <canvas id="typeChart"
              data-labels='<?= h(json_encode(array_map(fn($r) => $r['type'], $sales_by_type))) ?>'
              data-revenue='<?= h(json_encode(array_map(fn($r) => (float)$r['revenue'], $sales_by_type))) ?>'></canvas>
    </div>
  </div>
</section>

<section class="grid-2">
  <div class="card">
    <h2>Top-Selling Products</h2>
    <?php if (empty($top_products)): ?>
      <p class="muted">No sales yet.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>Product</th><th>Type</th><th class="num">Units</th><th class="num">Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($top_products as $tp): ?>
          <tr>
            <td><?= h($tp['name']) ?></td>
            <td><span class="tag tag-<?= strtolower($tp['type']) ?>"><?= h($tp['type']) ?></span></td>
            <td class="num"><?= (int)$tp['units_sold'] ?></td>
            <td class="num"><?= fmt_currency($tp['revenue']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Low-Stock Alerts</h2>
    <?php if (empty($low_stock)): ?>
      <p class="muted">All products well-stocked.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>Product</th><th>Type</th><th class="num">Stock</th></tr></thead>
        <tbody>
        <?php foreach ($low_stock as $p): ?>
          <tr>
            <td><?= h($p['name']) ?></td>
            <td><span class="tag tag-<?= strtolower($p['type']) ?>"><?= h($p['type']) ?></span></td>
            <td class="num"><span class="stock-low"><?= (int)$p['stock'] ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<section class="card">
  <h2>Recent Orders</h2>
  <?php if (empty($recent_orders)): ?>
    <p class="muted">No orders yet.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Customer</th><th>Product</th><th>Type</th><th class="num">Qty</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php foreach ($recent_orders as $o): ?>
        <tr>
          <td><?= h(date('Y-m-d', strtotime($o['order_date']))) ?></td>
          <td><?= h($o['customer_name']) ?></td>
          <td><?= h($o['product_name']) ?></td>
          <td><span class="tag tag-<?= strtolower($o['product_type']) ?>"><?= h($o['product_type']) ?></span></td>
          <td class="num"><?= (int)$o['quantity'] ?></td>
          <td class="num"><?= fmt_currency($o['total_price']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/footer.php'; ?>
