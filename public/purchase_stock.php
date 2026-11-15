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
    $price      = (float)($_POST['purchase_price'] ?? 0);
    $total      = $qty * $price;

    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id' => $product_id]);
    $product = $s->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $msg = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
    } elseif ($qty <= 0) {
        $msg = ['type' => 'danger', 'text' => 'La cantidad debe ser mayor que 0.'];
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
                       <br>🔢 Cantidad: <strong>{$qty} unidades</strong> × <strong>$" . number_format($price, 0, ",", ".") . " c/u</strong>
                       <br>💰 Total pagado: <strong>$" . number_format($total, 0, ",", ".") . "</strong>
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
<title>Pet-Store | Registrar Compra</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <span class="navbar-brand fw-bold">🐾 Pet-Store</span>
    <div class="d-flex gap-2">
      <a href="manage_products.php" class="btn btn-sm btn-light">Productos</a>
      <a href="record_sale.php"     class="btn btn-sm btn-light">Registrar Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm btn-light">Registrar Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>
<div class="container">
<div class="card shadow-sm" style="max-width:600px">
  <div class="card-header bg-success text-white fw-bold">🛒 Registrar compra de stock</div>
  <div class="card-body">
    <?php if ($msg): ?>
      <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>
    <form method="post" id="purchase-form">
      <div class="mb-3">
        <label class="form-label">Producto</label>
        <select name="product_id" class="form-select" required>
          <option value="">Selecciona un producto...</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= htmlspecialchars($p['name']) ?> — Stock actual: <?= $p['stock'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Cantidad comprada</label>
        <input name="qty" id="qty" type="number" min="1" class="form-control" required oninput="calcTotal()">
      </div>
      <div class="mb-3">
        <label class="form-label">Precio de compra unitario (COP $)</label>
        <input name="purchase_price" id="unit-price" type="number" step="1" min="0" class="form-control" required oninput="calcTotal()">
      </div>

      <!-- Total calculado en tiempo real -->
      <div class="alert alert-secondary py-2 mb-3" id="total-box" style="display:none">
        💰 Total a pagar: <strong id="total-display">$0.00</strong>
      </div>

      <button type="submit" class="btn btn-success w-100">Registrar compra</button>
    </form>
  </div>
</div>
</div>

<script>
function formatCOP(amount) {
    // Formato peso colombiano: $ 3.000
    return '$ ' + Math.round(amount).toLocaleString('es-CO');
}
function calcTotal() {
    const qty   = parseFloat(document.getElementById('qty').value) || 0;
    const price = parseFloat(document.getElementById('unit-price').value) || 0;
    const total = qty * price;
    const box   = document.getElementById('total-box');
    const disp  = document.getElementById('total-display');
    if (qty > 0 && price > 0) {
        disp.textContent = formatCOP(total);
        box.style.display = '';
    } else {
        box.style.display = 'none';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
