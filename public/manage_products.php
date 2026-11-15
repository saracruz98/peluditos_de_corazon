<?php
// public/manage_products.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku      = trim($_POST['sku'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $category = ($_POST['category'] ?? 'general') === 'bano' ? 'bano' : 'general';
    $stock    = (int)($_POST['stock'] ?? 0);
    $pp       = (float)($_POST['purchase_price'] ?? 0);
    $sp       = (float)($_POST['sale_price'] ?? 0);
    $id       = $_POST['id'] ?? null;

    if ($sku === '' || $name === '') {
        $msg = ['type' => 'danger', 'text' => 'SKU y Nombre son obligatorios.'];
    } else {
        if ($id) {
            $pdo->prepare('UPDATE products SET sku=:sku,name=:name,category=:cat,stock=:stk,purchase_price=:pp,sale_price=:sp WHERE id=:id')
                ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp,':id'=>$id]);
            $msg = ['type' => 'success', 'text' => 'Producto actualizado.'];
        } else {
            $pdo->prepare('INSERT INTO products (sku,name,category,stock,purchase_price,sale_price) VALUES (:sku,:name,:cat,:stk,:pp,:sp)')
                ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp]);
            $msg = ['type' => 'success', 'text' => 'Producto creado.'];
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id' => $_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}
$products = getProductList($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pet-Store | Productos</title>
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

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
    <?= $msg['text'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
  <div class="card-header bg-success text-white">
    <?= $edit ? '✏️ Editar producto' : '➕ Nuevo producto' ?>
  </div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
      <div class="col-md-2">
        <label class="form-label">SKU</label>
        <input name="sku" class="form-control" required value="<?= htmlspecialchars($edit['sku'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Nombre</label>
        <input name="name" class="form-control" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Categoría</label>
        <select name="category" class="form-select">
          <option value="general" <?= (!$edit || $edit['category']==='general') ? 'selected' : '' ?>>General</option>
          <option value="bano"    <?= ($edit && $edit['category']==='bano') ? 'selected' : '' ?>>Baño</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Stock</label>
        <input name="stock" type="number" min="0" class="form-control" value="<?= $edit['stock'] ?? 0 ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">P. Compra</label>
        <input name="purchase_price" type="number" step="0.01" min="0" class="form-control" value="<?= $edit['purchase_price'] ?? 0 ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">P. Venta</label>
        <input name="sale_price" type="number" step="0.01" min="0" class="form-control" value="<?= $edit['sale_price'] ?? 0 ?>">
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-success"><?= $edit ? 'Actualizar' : 'Crear producto' ?></button>
        <?php if ($edit): ?><a href="manage_products.php" class="btn btn-secondary ms-2">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<h4 class="mb-3">📦 Inventario actual</h4>
<div class="table-responsive">
<table class="table table-hover table-bordered bg-white shadow-sm">
  <thead class="table-success">
    <tr><th>SKU</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>P. Compra</th><th>P. Venta</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr class="<?= $p['stock'] <= 0 ? 'table-danger' : ($p['stock'] <= 5 ? 'table-warning' : '') ?>">
      <td><?= htmlspecialchars($p['sku']) ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= $p['category'] === 'bano' ? '🛁 Baño' : '🛒 General' ?></td>
      <td><strong><?= $p['stock'] ?></strong><?= $p['stock'] <= 0 ? ' <span class="badge bg-danger">Agotado</span>' : ($p['stock'] <= 5 ? ' <span class="badge bg-warning text-dark">Poco</span>' : '') ?></td>
      <td>$<?= number_format($p['purchase_price'],2) ?></td>
      <td>$<?= number_format($p['sale_price'],2) ?></td>
      <td><a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
