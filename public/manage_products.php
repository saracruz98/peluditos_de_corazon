<?php
// public/manage_products.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'product';

    // ── Registrar baño ───────────────────────────────────────────────────────
    if ($action === 'bath') {
        $breed = trim($_POST['breed'] ?? '');
        $qty   = (int)($_POST['qty'] ?? 1);
        $price = (float)($_POST['price'] ?? 0);
        if ($breed === '') {
            $msg = ['type' => 'danger', 'text' => 'La raza del perro es obligatoria.'];
        } else {
            $pdo->prepare('INSERT INTO bath_records (breed, qty, price) VALUES (:b,:q,:p)')
                ->execute([':b' => $breed, ':q' => $qty, ':p' => $price]);
            $msg = ['type' => 'success', 'text' => "✅ Baño registrado: $qty perro(s) raza \"$breed\" — \$$price c/u."];
        }

    // ── Crear / editar producto ──────────────────────────────────────────────
    } else {
        $sku      = trim($_POST['sku'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $category = ($_POST['category'] ?? 'general') === 'bano' ? 'bano' : 'general';
        $stock    = $category === 'bano' ? 0 : (int)($_POST['stock'] ?? 0);
        $pp       = $category === 'bano' ? 0 : (float)($_POST['purchase_price'] ?? 0);
        $sp       = (float)($_POST['sale_price'] ?? 0);
        $id       = $_POST['id'] ?? null;

        if ($name === '') {
            $msg = ['type' => 'danger', 'text' => 'El nombre es obligatorio.'];
        } else {
            try {
                if ($id) {
                    $pdo->prepare('UPDATE products SET sku=:sku,name=:name,category=:cat,stock=:stk,purchase_price=:pp,sale_price=:sp WHERE id=:id')
                        ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp,':id'=>$id]);
                    $msg = ['type' => 'success', 'text' => 'Producto actualizado correctamente.'];
                } else {
                    $pdo->prepare('INSERT INTO products (sku,name,category,stock,purchase_price,sale_price) VALUES (:sku,:name,:cat,:stk,:pp,:sp)')
                        ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp]);
                    $msg = ['type' => 'success', 'text' => 'Producto creado correctamente.'];
                }
            } catch (PDOException $e) {
                $msg = ['type' => 'danger', 'text' => 'Error al guardar: ' . $e->getMessage()];
            }
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

// Últimos baños registrados
$bathList = $pdo->query('SELECT * FROM bath_records ORDER BY recorded_at DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
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

<!-- ══════════════════ FORMULARIO PRODUCTO / BAÑO ══════════════════ -->
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-success text-white fw-bold" id="form-title">
    <?= $edit ? '✏️ Editar producto' : '➕ Nuevo producto / servicio' ?>
  </div>
  <div class="card-body">
    <form method="post" class="row g-3" id="main-form">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
      <input type="hidden" name="action" value="product" id="form-action">

      <!-- Categoría (siempre visible primero) -->
      <div class="col-md-2">
        <label class="form-label fw-bold">Categoría</label>
        <select name="category" id="category" class="form-select" onchange="toggleForm(this.value)">
          <option value="general" <?= (!$edit || $edit['category']==='general') ? 'selected' : '' ?>>🛒 General</option>
          <option value="bano"    <?= ($edit && $edit['category']==='bano')    ? 'selected' : '' ?>>🛁 Baño</option>
        </select>
      </div>

      <!-- ── Campos GENERAL ───────────────────────────────────────── -->
      <div id="fields-general" class="col-12 row g-3 mb-0">
        <div class="col-md-2">
          <label class="form-label">SKU</label>
          <input name="sku" id="sku" class="form-control" value="<?= htmlspecialchars($edit['sku'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Nombre</label>
          <input name="name" id="name-general" class="form-control" value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
        </div>
        <div class="col-md-1">
          <label class="form-label">Stock</label>
          <input name="stock" type="number" min="0" class="form-control" value="<?= $edit['stock'] ?? 0 ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">P. Compra (COP)</label>
          <input name="purchase_price" type="number" step="1" min="0" class="form-control" value="<?= (int)($edit['purchase_price'] ?? 0) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">P. Venta (COP)</label>
          <input name="sale_price" type="number" step="1" min="0" class="form-control" value="<?= (int)($edit['sale_price'] ?? 0) ?>">
        </div>
      </div>

      <!-- ── Campos BAÑO ──────────────────────────────────────────── -->
      <div id="fields-bano" class="col-12 row g-3 mb-0" style="display:none">
        <div class="col-md-3">
          <label class="form-label">Nombre del servicio</label>
          <input name="name_bano" id="name-bano" class="form-control" placeholder="Ej: Baño básico, Baño completo…" value="<?= ($edit && $edit['category']==='bano') ? htmlspecialchars($edit['name']) : '' ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">🐕 Raza del perro</label>
          <input name="breed" id="breed" class="form-control" placeholder="Ej: Bulldog, Poodle…">
        </div>
        <div class="col-md-1">
          <label class="form-label">Cantidad</label>
          <input name="bath_qty" type="number" min="1" value="1" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">💰 Precio del baño (COP)</label>
          <input name="price" type="number" step="1" min="0" class="form-control" placeholder="Ej: 20000">
        </div>
        <div class="col-md-12">
          <div class="alert alert-info py-2 mb-0">
            🛁 Al guardar se registrará el baño directamente en el historial.
          </div>
        </div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-success" id="btn-submit">
          <?= $edit ? 'Actualizar' : 'Crear producto' ?>
        </button>
        <?php if ($edit): ?><a href="manage_products.php" class="btn btn-secondary ms-2">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════ INVENTARIO ══════════════════ -->
<h4 class="mb-3">📦 Inventario de productos</h4>
<div class="table-responsive mb-5">
<table class="table table-hover table-bordered bg-white shadow-sm">
  <thead class="table-success">
    <tr><th>SKU</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>P. Compra</th><th>P. Venta</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr class="<?= $p['stock'] <= 0 && $p['category']!=='bano' ? 'table-danger' : ($p['stock'] <= 5 && $p['category']!=='bano' ? 'table-warning' : '') ?>">
      <td><?= htmlspecialchars($p['sku']) ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= $p['category'] === 'bano' ? '🛁 Baño' : '🛒 General' ?></td>
      <td><?= $p['category'] === 'bano' ? '<span class="badge bg-info">Servicio</span>' : '<strong>'.$p['stock'].'</strong>'.($p['stock']<=0?' <span class="badge bg-danger">Agotado</span>':($p['stock']<=5?' <span class="badge bg-warning text-dark">Poco</span>':'')) ?></td>
      <td><?= $p['category']==='bano' ? '—' : '$'.number_format($p['purchase_price'], 0, ',', '.') ?></td>
      <td>$<?= number_format($p['sale_price'], 0, ',', '.') ?></td>
      <td><a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- ══════════════════ HISTORIAL DE BAÑOS ══════════════════ -->
<?php if ($bathList): ?>
<h4 class="mb-3">🛁 Últimos baños registrados</h4>
<div class="table-responsive">
<table class="table table-hover table-bordered bg-white shadow-sm">
  <thead class="table-info">
    <tr><th>Raza</th><th>Cantidad</th><th>Precio</th><th>Fecha</th></tr>
  </thead>
  <tbody>
  <?php foreach ($bathList as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['breed']) ?></td>
      <td><?= $b['qty'] ?></td>
      <td>$<?= number_format($b['price'], 0, ",", ".") ?></td>
      <td><?= $b['recorded_at'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

</div><!-- /container -->

<script>
function toggleForm(cat) {
    const isB = cat === 'bano';
    document.getElementById('fields-general').style.display = isB ? 'none' : '';
    document.getElementById('fields-bano').style.display    = isB ? ''     : 'none';
    document.getElementById('form-action').value            = isB ? 'bath' : 'product';
    document.getElementById('btn-submit').textContent       = isB ? '🛁 Registrar baño' : (document.querySelector('input[name=id]') ? 'Actualizar' : 'Crear producto');
    document.getElementById('form-title').textContent       = isB ? '🛁 Registrar baño de mascota' : '➕ Nuevo producto';
}
// Al cargar, si ya viene con categoría baño (modo edición) ajustar vista
document.addEventListener('DOMContentLoaded', function() {
    toggleForm(document.getElementById('category').value);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
