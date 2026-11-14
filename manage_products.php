<?php
// manage_products.php – UI para crear/editar productos con precios de compra y venta
require_once __DIR__ . '/inventory.php';

$pdo = new PDO('sqlite:' . __DIR__ . '/petstore.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$msg = '';
// Si llega POST, procesamos creación o actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku   = trim($_POST['sku'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $category = strtolower(trim($_POST['category'] ?? 'general')) === 'baño' ? 'baño' : 'general';
    $stock = (int)($_POST['stock'] ?? 0);
    $purchase = (float)($_POST['purchase_price'] ?? 0);
    $sale = (float)($_POST['sale_price'] ?? 0);
    $id = $_POST['id'] ?? null; // si viene id, es edición

    if ($sku === '' || $name === '') {
        $msg = '❌ SKU y Nombre son obligatorios.';
    } else {
        if ($id) {
            // actualización
            $stmt = $pdo->prepare('UPDATE products SET sku=:sku, name=:name, category=:cat, stock=:stk, purchase_price=:pp, sale_price=:sp WHERE id=:id');
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':cat'=>$category, ':stk'=>$stock, ':pp'=>$purchase, ':sp'=>$sale, ':id'=>$id]);
            $msg = "✅ Producto actualizado.";
        } else {
            // inserción
            $stmt = $pdo->prepare('INSERT INTO products (sku, name, category, stock, purchase_price, sale_price) VALUES (:sku, :name, :cat, :stk, :pp, :sp)');
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':cat'=>$category, ':stk'=>$stock, ':pp'=>$purchase, ':sp'=>$sale]);
            $msg = "✅ Producto creado.";
        }
    }
}

// Si hay query param id, cargar datos para edición
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id'=>$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

$products = getProductList($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de productos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <h1>Gestión de productos</h1>
    <?php if ($msg): ?>
        <div class="alert <?= strpos($msg,'✅')!==false ? 'alert-success' : 'alert-danger' ?>"><?=$msg?></div>
    <?php endif; ?>
    <form method="post" class="row g-3 mb-4">
        <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
        <?php endif; ?>
        <div class="col-md-2">
            <label class="form-label">SKU</label>
            <input name="sku" class="form-control" required value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Nombre</label>
            <input name="name" class="form-control" required value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Categoría</label>
            <select name="category" class="form-select">
                <option value="general" <?= (isset($editProduct['category']) && $editProduct['category']=='general') ? 'selected' : '' ?>>General</option>
                <option value="baño" <?= (isset($editProduct['category']) && $editProduct['category']=='baño') ? 'selected' : '' ?>>Baño</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label">Stock</label>
            <input name="stock" type="number" min="0" class="form-control" value="<?= $editProduct['stock'] ?? 0 ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Precio compra</label>
            <input name="purchase_price" type="number" step="0.01" min="0" class="form-control" value="<?= $editProduct['purchase_price'] ?? 0 ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Precio venta</label>
            <input name="sale_price" type="number" step="0.01" min="0" class="form-control" value="<?= $editProduct['sale_price'] ?? 0 ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary"><?= $editProduct ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($editProduct): ?>
                <a href="manage_products.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Listado de productos</h2>
    <table class="table table-sm table-striped">
        <thead><tr><th>SKU</th><th>Nombre</th><th>Categ.</th><th>Stock</th><th>Precio compra</th><th>Precio venta</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['sku']) ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= ucfirst($p['category']) ?></td>
                <td><?= $p['stock'] ?></td>
                <td><?= number_format($p['purchase_price'],2) ?></td>
                <td><?= number_format($p['sale_price'],2) ?></td>
                <td>
                    <a href="manage_products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <a href="record_sale.php" class="btn btn-outline-success">➡️ Registrar venta</a>
    <a href="report.php" class="btn btn-outline-secondary ms-2">📊 Generar reporte CSV</a>
</body>
</html>
