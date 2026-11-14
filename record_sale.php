<?php
// record_sale.php – registra la venta de un producto
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/db_connect.php';

$pdo = getPDO();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = $_POST['sku'] ?? '';
    $qty = (int)($_POST['qty'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, stock, name FROM products WHERE sku = :sku');
    $stmt->execute([':sku' => $sku]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $msg = "❌ Producto con SKU $sku no existe.";
    } elseif ($qty <= 0) {
        $msg = "❌ La cantidad debe ser mayor que 0.";
    } elseif ($product['stock'] < $qty) {
        $msg = "❌ Stock insuficiente (disponible: {$product['stock']}).";
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO sales (product_id, qty) VALUES (:pid, :qty)')
            ->execute([':pid' => $product['id'], ':qty' => $qty]);
        $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :pid')
            ->execute([':qty' => $qty, ':pid' => $product['id']]);
        $pdo->commit();
        $msg = "✅ Vendido $qty × {$product['name']}. Stock restante: " . ($product['stock'] - $qty);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar venta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <h1>Registrar venta</h1>
    <?php if ($msg): ?>
        <div class="alert <?= strpos($msg, '✅') !== false ? 'alert-success' : 'alert-danger' ?>"><?=$msg?></div>
    <?php endif; ?>
    <form method="post" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">SKU del producto</label>
            <input name="sku" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Cantidad</label>
            <input name="qty" type="number" min="1" class="form-control" required>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Vender</button>
        </div>
    </form>
    <hr>
    <a href="report.php" class="btn btn-outline-secondary">📊 Generar reporte Excel</a>
</body>
</html>
