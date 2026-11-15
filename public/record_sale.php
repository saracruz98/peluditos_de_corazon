<?php
require_once __DIR__ . '/partials/header.php';
$title = 'Registrar venta';
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/inventory.php';

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
<div class="card">
    <div class="card-header bg-success text-white">
        <i class="bi bi-cash-coin"></i> Registrar venta
    </div>
    <div class="card-body">
        <?php if ($msg): ?>
            <div class="alert <?= strpos($msg,'✅')!==false ? 'alert-success' : 'alert-danger' ?>"><?= $msg ?></div>
        <?php endif; ?>
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <select name="sku" class="form-select" required>
                    <option value="">Selecciona…</option>
                    <?php
                    $products = getProductList($pdo);
                    foreach ($products as $p) {
                        echo "<option value='" . htmlspecialchars($p['sku']) . "'>" . htmlspecialchars($p['name']) . " (" . $p['category'] . ")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad</label>
                <input name="qty" type="number" min="1" class="form-control" required>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-cart-check"></i> Vender</button>
            </div>
        </form>
    </div>
</div>
<div class="mt-4">
    <a href="manage_products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver a productos</a>
</div>
<?php
require_once __DIR__ . '/partials/footer.php';
?>