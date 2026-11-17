<?php
// public/record_sale.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = (int)($_POST['qty'] ?? 0);

    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id' => $product_id]);
    $product = $s->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $msg = ['type' => 'danger', 'text' => '⚠️ Producto no encontrado.'];
    } elseif ($qty <= 0) {
        $msg = ['type' => 'danger', 'text' => '⚠️ La cantidad debe ser mayor que 0.'];
    } elseif ($product['stock'] < $qty) {
        $msg = ['type' => 'danger', 'text' => "⚠️ Stock insuficiente. Disponible: {$product['stock']} unidades."];
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO sales (product_id,qty) VALUES (:pid,:qty)')
            ->execute([':pid' => $product_id, ':qty' => $qty]);
        $pdo->prepare('UPDATE products SET stock=stock-:qty WHERE id=:id')
            ->execute([':qty' => $qty, ':id' => $product_id]);
        $pdo->commit();
        $restante  = $product['stock'] - $qty;
        $totalVenta = (int)$product['sale_price'] * $qty;
        $msg = [
            'type' => 'success',
            'text' => "✅ Venta registrada:
                       <br>📦 <strong>{$product['name']}</strong>
                       <br>🔢 Cantidad vendida: <strong>{$qty} unidades</strong> × <strong>\$" . number_format((int)$product['sale_price'], 0, ',', '.') . " c/u</strong>
                       <br>💰 Total recibido: <strong>\$" . number_format($totalVenta, 0, ',', '.') . "</strong>
                       <br>📊 Stock restante: <strong>{$restante} unidades</strong>"
        ];
    }
}

// Solo productos generales
$products = array_filter(getProductList($pdo), fn($p) => $p['category'] !== 'bano');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🐾 Peluditos | Registrar Venta</title>
<?php require_once __DIR__ . '/src/pwa_head.php'; ?>
<style>
  #total-box { font-size: 1.2rem; }
</style>
</head>
<body>

<!-- Navbar desktop -->
<nav class="navbar navbar-dark bg-success mb-3 d-none d-md-flex">
  <div class="container">
    <span class="navbar-brand fw-bold">🐾 Peluditos de Corazón</span>
    <div class="d-flex gap-2">
      <a href="manage_products.php" class="btn btn-sm btn-light">📦 Productos</a>
      <a href="record_sale.php"     class="btn btn-sm btn-light fw-bold">💰 Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm btn-light">🛒 Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>

<!-- Header mobile -->
<div class="bg-success text-white text-center py-2 d-md-none fw-bold fs-5">🐾 Peluditos de Corazón</div>

<div class="container mt-3">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white fw-bold fs-5">💰 Registrar venta</div>
    <div class="card-body">

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
          <?= $msg['text'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" id="sale-form">
        <div class="mb-3">
          <label class="form-label">📦 Producto</label>
          <select name="product_id" id="product-select" class="form-select form-select-lg" required onchange="updatePrice()">
            <option value="">Selecciona un producto...</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>"
                      data-price="<?= (int)$p['sale_price'] ?>"
                      data-stock="<?= $p['stock'] ?>">
                <?= htmlspecialchars($p['name']) ?> — Stock: <?= $p['stock'] ?>
                (P. Venta: $<?= number_format((int)$p['sale_price'], 0, ',', '.') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Precio de venta del producto seleccionado -->
        <div class="alert alert-secondary py-2 mb-3" id="price-info" style="display:none">
          🏷️ Precio de venta unitario: <strong id="unit-price-display">$0</strong>
        </div>

        <div class="mb-3">
          <label class="form-label">🔢 Cantidad vendida</label>
          <input name="qty" id="qty" type="number" min="1" class="form-control form-control-lg"
                 placeholder="0" required oninput="calcTotal()">
        </div>

        <!-- Total en tiempo real -->
        <div class="alert alert-success py-2 mb-3" id="total-box" style="display:none">
          💰 Total a cobrar: <strong id="total-display">$0</strong>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success btn-lg">✅ Registrar venta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Banner instalación PWA -->
<div id="install-banner" style="align-items:center; gap:10px; display:none;">
  <span style="font-size:28px;">🐾</span>
  <div style="flex:1"><strong>Instalar app</strong><br><small class="text-muted">Agregar Peluditos a tu pantalla de inicio</small></div>
  <button id="btn-install-pwa" class="btn btn-success btn-sm">Instalar</button>
  <button id="btn-dismiss-pwa" class="btn btn-outline-secondary btn-sm">✕</button>
</div>

<!-- Navegación inferior (mobile) -->
<nav class="bottom-nav d-md-none">
  <a href="manage_products.php">
    <span class="nav-icon">📦</span>Productos
  </a>
  <a href="record_sale.php" class="active">
    <span class="nav-icon">💰</span>Venta
  </a>
  <a href="purchase_stock.php">
    <span class="nav-icon">🛒</span>Compra
  </a>
  <a href="report_excel.php">
    <span class="nav-icon">📊</span>Excel
  </a>
</nav>

<?php require_once __DIR__ . '/src/pwa_scripts.php'; ?>
</body>
</html>
