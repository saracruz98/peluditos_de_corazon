<?php
require_once __DIR__ . '/partials/header.php';
$title = 'Gestión de productos';
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/inventory.php';

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku   = trim($_POST['sku'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $category = strtolower(trim($_POST['category'] ?? 'general')) === 'baño' ? 'baño' : 'general';
    $stock = (int)($_POST['stock'] ?? 0);
    $purchase = (float)$_POST['purchase_price'] ?? 0;
    $sale = (float)$_POST['sale_price'] ?? 0;
    $id = $_POST['id'] ?? null;
    if ($sku === '' || $name === '') {
        $msg = '❌ SKU y Nombre son obligatorios.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE products SET sku=:sku, name=:name, category=:cat, stock=:stk, purchase_price=:pp, sale_price=:sp WHERE id=:id');
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':cat'=>$category, ':stk'=>$stock, ':pp'=>$purchase, ':sp'=>$sale, ':id'=>$id]);
            $msg = "✅ Producto actualizado.";
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (sku, name, category, stock, purchase_price, sale_price) VALUES (:sku, :name, :cat, :stk, :pp, :sp)');
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':cat'=>$category, ':stk'=>$stock, ':pp'=>$purchase, ':sp'=>$sale]);
            $msg = "✅ Producto creado.";
        }
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id'=>$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}
$products = getProductList($pdo);
?>
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <?= $editProduct ? '<i class="bi bi-pencil-square"></i> Editar producto' : '<i class="bi bi-plus-square"></i> Nuevo producto' ?>
    </div>
    <div class="card-body">
        <?php if ($msg): ?>
            <div class="alert <?= strpos($msg,'✅')!==false ? 'alert-success' : 'alert-danger' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>
        <form method="post" class="row g-3">
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
                <button type="submit" class="btn btn-success w-100"><?= $editProduct ? 'Actualizar' : 'Crear' ?></button>
                <?php if ($editProduct): ?>
                    <a href="manage_products.php" class="btn btn-outline-secondary mt-2 w-100">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<h2 class="mb-3"><i class="bi bi-list-ul"></i> Listado de productos</h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>SKU</th><th>Nombre</th><th>Categ.</th><th>Stock</th><th>Precio compra</th><th>Precio venta</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['sku']) ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= ucfirst($p['category']) ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td><?= number_format($p['purchase_price'],2) ?></td>
                    <td><?= number_format($p['sale_price'],2) ?></td>
                    <td><a href="manage_products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="d-flex flex-wrap gap-2 mt-4">
    <a href="record_sale.php" class="btn btn-success"><i class="bi bi-cash-coin"></i> Registrar venta</a>
    <a href="purchase_stock.php" class="btn btn-info"><i class="bi bi-box-seam"></i> Registrar compra</a>
    <a href="report.php" class="btn btn-secondary"><i class="bi bi-file-earmark-csv"></i> CSV</a>
    <a href="report_excel.php" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
</div>
<?php
require_once __DIR__ . '/partials/footer.php';
?>