<?php
// public/purchase_stock.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = (int)($_POST['qty'] ?? 0);
    $price      = (int)($_POST['purchase_price'] ?? 0);
    $total      = $qty * $price;

    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id' => $product_id]);
    $product = $s->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $msg = ['type' => 'danger', 'text' => '⚠️ Producto no encontrado.'];
    } elseif ($qty <= 0) {
        $msg = ['type' => 'danger', 'text' => '⚠️ La cantidad debe ser mayor que 0.'];
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO purchases (product_id,qty) VALUES (:pid,:qty)')
            ->execute([':pid' => $product_id, ':qty' => $qty]);
        $pdo->prepare('UPDATE products SET stock=stock+:qty, purchase_price=:pp WHERE id=:id')
            ->execute([':qty' => $qty, ':pp' => $price, ':id' => $product_id]);
        $pdo->commit();
        $nuevo = $product['stock'] + $qty;
        $msg = [
            'type' => 'success',
            'text' => "✅ Compra registrada:
                       <br>📦 <strong>{$product['name']}</strong>
                       <br>🔢 Cantidad: <strong>{$qty} unidades</strong> × <strong>\$" . number_format($price, 0, ',', '.') . " c/u</strong>
                       <br>💰 Total pagado: <strong>\$" . number_format($total, 0, ',', '.') . "</strong>
                       <br>📊 Stock nuevo: <strong>{$nuevo} unidades</strong>"
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
<title>🐾 Peluditos | Registrar Compra</title>
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
      <a href="record_sale.php"     class="btn btn-sm btn-light">💰 Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm btn-light fw-bold">🛒 Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>

<!-- Header mobile -->
<div class="bg-success text-white text-center py-2 d-md-none fw-bold fs-5">🐾 Peluditos de Corazón</div>

<div class="container mt-3">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white fw-bold fs-5">🛒 Registrar compra de stock</div>
    <div class="card-body">

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
          <?= $msg['text'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" id="purchase-form">
        <div class="mb-3">
          <label class="form-label">📦 Producto</label>
          <select name="product_id" class="form-select form-select-lg" required>
            <option value="">Selecciona un producto...</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['name']) ?> — Stock actual: <?= $p['stock'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">🔢 Cantidad comprada</label>
          <input name="qty" id="qty" type="number" min="1" step="1"
                 class="form-control form-control-lg" placeholder="0" required oninput="calcTotal()">
        </div>
        <div class="mb-3">
          <label class="form-label">💵 Precio de compra unitario (COP $)</label>
          <input name="purchase_price" id="unit-price" type="number" step="1" min="0"
                 class="form-control form-control-lg" placeholder="Ej: 15000" required oninput="calcTotal()">
        </div>

        <!-- Total calculado en tiempo real -->
        <div class="alert alert-warning py-2 mb-3" id="total-box" style="display:none">
          💰 Total a pagar: <strong id="total-display">$0</strong>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success btn-lg">🛒 Registrar compra</button>
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
  <a href="record_sale.php">
    <span class="nav-icon">💰</span>Venta
  </a>
  <a href="purchase_stock.php" class="active">
    <span class="nav-icon">🛒</span>Compra
  </a>
  <a href="report_excel.php">
    <span class="nav-icon">📊</span>Excel
  </a>
</nav>

<?php require_once __DIR__ . '/src/pwa_scripts.php'; ?>
</body>
</html>
