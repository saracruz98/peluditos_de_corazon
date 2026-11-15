<?php
// public/purchase_stock.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = (int)($_POST['qty'] ?? 0);

    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id'=>$product_id]);
    $product = $s->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $msg = ['type'=>'danger','text'=>'Producto no encontrado.'];
    } elseif ($qty <= 0) {
        $msg = ['type'=>'danger','text'=>'La cantidad debe ser mayor que 0.'];
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO purchases (product_id,qty) VALUES (:pid,:qty)')
            ->execute([':pid'=>$product_id,':qty'=>$qty]);
        $pdo->prepare('UPDATE products SET stock=stock+:qty WHERE id=:id')
            ->execute([':qty'=>$qty,':id'=>$product_id]);
        $pdo->commit();
        $nuevo = $product['stock'] + $qty;
        $msg = ['type'=>'success','text'=>"✅ Compra registrada: {$qty} × {$product['name']}. Stock nuevo: {$nuevo}."];
    }
}

$products = getProductList($pdo);
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
  <div class="card-header bg-info text-white">📦 Registrar compra (reabastecimiento)</div>
  <div class="card-body">
    <?php if ($msg): ?>
      <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Producto</label>
        <select name="product_id" class="form-select" required>
          <option value="">Selecciona un producto...</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= htmlspecialchars($p['name']) ?> (<?= $p['category']==='bano'?'Baño':'General' ?>) — Stock actual: <?= $p['stock'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Cantidad comprada</label>
        <input name="qty" type="number" min="1" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-info w-100 text-white">Registrar compra</button>
    </form>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
