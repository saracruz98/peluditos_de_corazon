<?php
// public/manage_products.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'product';

    // ── Registrar baño ───────────────────────────────────────────────
    if ($action === 'bath') {
        $breed = trim($_POST['breed'] ?? '');
        $qty   = (int)($_POST['bath_qty'] ?? 1);
        $price = (int)($_POST['price'] ?? 0);
        if ($breed === '') {
            $msg = ['type' => 'danger', 'text' => '⚠️ La raza del perro es obligatoria.'];
        } else {
            $pdo->prepare('INSERT INTO bath_records (breed, qty, price) VALUES (:b,:q,:p)')
                ->execute([':b' => $breed, ':q' => $qty, ':p' => $price]);
            $precioFmt = '$' . number_format($price, 0, ',', '.');
            $msg = ['type' => 'success', 'text' => "✅ Baño registrado: <strong>$qty</strong> perro(s) raza <strong>$breed</strong> — {$precioFmt} c/u."];
        }

    // ── Crear / editar producto general o accesorio ──────────────────
    } else {
        $name     = trim($_POST['name'] ?? '');
        $sku      = trim($_POST['sku'] ?? '');
        $category = in_array($_POST['category'] ?? '', ['general','accesorios']) ? $_POST['category'] : 'general';
        $stock    = (int)($_POST['stock'] ?? 0);
        $pp       = (int)($_POST['purchase_price'] ?? 0);
        $sp       = (int)($_POST['sale_price'] ?? 0);
        $id       = $_POST['id'] ?? null;

        if ($name === '') {
            $msg = ['type' => 'danger', 'text' => '⚠️ El nombre es obligatorio.'];
        } else {
            try {
                if ($id) {
                    $pdo->prepare('UPDATE products SET sku=:sku,name=:name,category=:cat,stock=:stk,purchase_price=:pp,sale_price=:sp WHERE id=:id')
                        ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp,':id'=>$id]);
                    $msg = ['type' => 'success', 'text' => '✅ Producto actualizado correctamente.'];
                } else {
                    $pdo->prepare('INSERT INTO products (sku,name,category,stock,purchase_price,sale_price) VALUES (:sku,:name,:cat,:stk,:pp,:sp)')
                        ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp]);
                    $msg = ['type' => 'success', 'text' => '✅ Producto creado correctamente.'];
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

// Función auxiliar: consulta de productos por categoría con total invertido
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';

function getProductsWithTotal(PDO $pdo, string $category, string $ifnull): array {
    $stmt = $pdo->prepare("
        SELECT p.*,
               p.purchase_price + $ifnull(SUM(pu.qty * pu.unit_price), 0) AS total_invertido
        FROM products p
        LEFT JOIN purchases pu ON pu.product_id = p.id
        WHERE p.category = :cat
        GROUP BY p.id, p.sku, p.name, p.category, p.stock, p.purchase_price, p.sale_price
        ORDER BY p.name
    ");
    $stmt->execute([':cat' => $category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$productsGeneral    = getProductsWithTotal($pdo, 'general', $ifnull);
$productsAccesorios = getProductsWithTotal($pdo, 'accesorios', $ifnull);

// Últimos baños registrados
$bathList = $pdo->query('SELECT * FROM bath_records ORDER BY recorded_at DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);

// Pestaña activa según edición
$activeTab = 'general';
if ($edit) {
    if ($edit['category'] === 'bano')       $activeTab = 'bano';
    elseif ($edit['category'] === 'accesorios') $activeTab = 'accesorios';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🐾 Peluditos | Productos</title>
<?php require_once __DIR__ . '/src/pwa_head.php'; ?>
<style>
  .section-title { border-bottom: 2px solid; padding-bottom: 6px; margin-bottom: 16px; }
  .section-title.green  { color: #198754; border-color: #198754; }
  .section-title.purple { color: #6f42c1; border-color: #6f42c1; }
  .nav-tabs .nav-link { font-size: 14px; }
</style>
</head>
<body>

<!-- Navbar desktop -->
<nav class="navbar navbar-dark bg-success mb-3 d-none d-md-flex">
  <div class="container">
    <span class="navbar-brand fw-bold">🐾 Peluditos de Corazón</span>
    <div class="d-flex gap-2">
      <a href="manage_products.php" class="btn btn-sm btn-light fw-bold">📦 Productos</a>
      <a href="record_sale.php"     class="btn btn-sm btn-light">💰 Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm btn-light">🛒 Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>

<div class="bg-success text-white text-center py-2 d-md-none fw-bold fs-5">🐾 Peluditos de Corazón</div>

<div class="container mt-3">

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
    <?= $msg['text'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- ══════════ PESTAÑAS: GENERAL / BAÑO / ACCESORIOS ══════════ -->
<ul class="nav nav-tabs mb-3" id="mainTab">
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='general' ? 'active fw-bold' : '' ?>"
            id="tab-general" data-bs-toggle="tab" data-bs-target="#panel-general" type="button">
      🛒 Producto General
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='bano' ? 'active fw-bold' : '' ?>"
            id="tab-bano" data-bs-toggle="tab" data-bs-target="#panel-bano" type="button">
      🛁 Registrar Baño
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='accesorios' ? 'active fw-bold' : '' ?>"
            id="tab-accesorios" data-bs-toggle="tab" data-bs-target="#panel-accesorios" type="button">
      🎀 Accesorios
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ══ PANEL GENERAL ══════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab==='general' ? 'show active' : '' ?>" id="panel-general">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-success text-white fw-bold">
        <?= ($edit && $edit['category']==='general') ? '✏️ Editar producto' : '➕ Nuevo producto general' ?>
      </div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?php if ($edit && $edit['category']==='general'): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
          <?php endif; ?>
          <input type="hidden" name="action" value="product">
          <input type="hidden" name="category" value="general">

          <div class="col-12 col-md-4">
            <label class="form-label">Nombre del producto</label>
            <input name="name" class="form-control form-control-lg"
                   placeholder="Ej: Alimento para perro"
                   value="<?= ($edit && $edit['category']==='general') ? htmlspecialchars($edit['name']) : '' ?>" required>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">SKU (opcional)</label>
            <input name="sku" class="form-control form-control-lg"
                   placeholder="Ej: ALIM-001"
                   value="<?= ($edit && $edit['category']==='general') ? htmlspecialchars($edit['sku']) : '' ?>">
          </div>
          <div class="col-6 col-md-1">
            <label class="form-label">Stock</label>
            <input name="stock" type="number" min="0" step="1" class="form-control form-control-lg"
                   value="<?= ($edit && $edit['category']==='general') ? (int)$edit['stock'] : 0 ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Inversión inicial ($)
              <span class="fw-normal text-muted" style="font-size:11px;"><br>Lo que costó la compra inicial</span>
            </label>
            <input name="purchase_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= ($edit && $edit['category']==='general') ? (int)$edit['purchase_price'] : 0 ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">P. Venta ($)</label>
            <input name="sale_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= ($edit && $edit['category']==='general') ? (int)$edit['sale_price'] : 0 ?>">
          </div>
          <div class="col-12 d-grid gap-2 d-md-flex">
            <button type="submit" class="btn btn-success btn-lg flex-grow-1">
              <?= ($edit && $edit['category']==='general') ? '💾 Actualizar producto' : '✅ Crear producto' ?>
            </button>
            <?php if ($edit && $edit['category']==='general'): ?>
              <a href="manage_products.php" class="btn btn-secondary btn-lg">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Inventario General -->
    <h5 class="section-title green">📦 Inventario actual — General</h5>
    <?php renderInventoryTable($productsGeneral, 'general'); ?>
  </div><!-- /panel-general -->


  <!-- ══ PANEL BAÑO ═════════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab==='bano' ? 'show active' : '' ?>" id="panel-bano">
    <div class="card shadow-sm mb-4" style="border-top: 4px solid #0dcaf0;">
      <div class="card-header fw-bold" style="background:#0dcaf0; color:#000;">
        🛁 Registrar servicio de baño
      </div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="bath">

          <div class="col-12 col-md-4">
            <label class="form-label">🐕 Raza del perro</label>
            <input name="breed" class="form-control form-control-lg"
                   placeholder="Ej: Bulldog, Poodle, Labrador…" required>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Cantidad de perros</label>
            <input name="bath_qty" type="number" min="1" value="1" class="form-control form-control-lg" required>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">💰 Precio del baño (COP $)</label>
            <input name="price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="Ej: 25000" required>
          </div>
          <div class="col-12 d-grid">
            <button type="submit" class="btn btn-lg fw-bold" style="background:#0dcaf0; color:#000;">
              🛁 Registrar baño
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Historial de baños -->
    <h5 class="mb-3" style="color:#0dcaf0; border-bottom:2px solid #0dcaf0; padding-bottom:6px;">
      📋 Historial de baños
    </h5>
    <div class="table-wrap mb-5">
      <table class="table table-hover table-bordered bg-white shadow-sm">
        <thead style="background:#0dcaf0;">
          <tr>
            <th>Raza</th><th>Cantidad</th><th>Precio c/u</th><th>Total</th><th>Fecha</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($bathList as $b): ?>
          <tr>
            <td><strong><?= htmlspecialchars($b['breed']) ?></strong></td>
            <td><?= $b['qty'] ?></td>
            <td>$<?= number_format((int)$b['price'], 0, ',', '.') ?></td>
            <td>$<?= number_format((int)$b['price'] * (int)$b['qty'], 0, ',', '.') ?></td>
            <td class="text-muted small"><?= $b['recorded_at'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($bathList)): ?>
          <tr><td colspan="5" class="text-center text-muted py-3">No hay baños registrados aún.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div><!-- /panel-bano -->


  <!-- ══ PANEL ACCESORIOS ═══════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab==='accesorios' ? 'show active' : '' ?>" id="panel-accesorios">
    <div class="card shadow-sm mb-4" style="border-top: 4px solid #6f42c1;">
      <div class="card-header fw-bold" style="background:#6f42c1; color:#fff;">
        <?= ($edit && $edit['category']==='accesorios') ? '✏️ Editar accesorio' : '🎀 Nuevo accesorio / producto especial' ?>
      </div>
      <div class="card-body">
        <div class="alert alert-secondary py-2 mb-3" style="font-size:13px;">
          🎀 Aquí registra: <strong>collares, snacks, desparasitantes, shampú, juguetes</strong> y cualquier accesorio para mascotas.
        </div>
        <form method="post" class="row g-3">
          <?php if ($edit && $edit['category']==='accesorios'): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
          <?php endif; ?>
          <input type="hidden" name="action" value="product">
          <input type="hidden" name="category" value="accesorios">

          <div class="col-12 col-md-4">
            <label class="form-label">Nombre del accesorio</label>
            <input name="name" class="form-control form-control-lg"
                   placeholder="Ej: Collar talla M, Snack dental, Antipulgas…"
                   value="<?= ($edit && $edit['category']==='accesorios') ? htmlspecialchars($edit['name']) : '' ?>" required>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">SKU (opcional)</label>
            <input name="sku" class="form-control form-control-lg"
                   placeholder="Ej: COL-M-001"
                   value="<?= ($edit && $edit['category']==='accesorios') ? htmlspecialchars($edit['sku']) : '' ?>">
          </div>
          <div class="col-6 col-md-1">
            <label class="form-label">Stock</label>
            <input name="stock" type="number" min="0" step="1" class="form-control form-control-lg"
                   value="<?= ($edit && $edit['category']==='accesorios') ? (int)$edit['stock'] : 0 ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Inversión inicial ($)
              <span class="fw-normal text-muted" style="font-size:11px;"><br>Lo que costó la compra inicial</span>
            </label>
            <input name="purchase_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= ($edit && $edit['category']==='accesorios') ? (int)$edit['purchase_price'] : 0 ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">P. Venta ($)</label>
            <input name="sale_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= ($edit && $edit['category']==='accesorios') ? (int)$edit['sale_price'] : 0 ?>">
          </div>
          <div class="col-12 d-grid gap-2 d-md-flex">
            <button type="submit" class="btn btn-lg flex-grow-1 fw-bold" style="background:#6f42c1; color:#fff;">
              <?= ($edit && $edit['category']==='accesorios') ? '💾 Actualizar accesorio' : '✅ Agregar accesorio' ?>
            </button>
            <?php if ($edit && $edit['category']==='accesorios'): ?>
              <a href="manage_products.php" class="btn btn-secondary btn-lg">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Inventario Accesorios -->
    <h5 class="section-title purple">🎀 Inventario de Accesorios</h5>
    <?php renderInventoryTable($productsAccesorios, 'accesorios'); ?>
  </div><!-- /panel-accesorios -->

</div><!-- /tab-content -->
</div><!-- /container -->

<?php
// Función para renderizar la tabla de inventario (reutilizable)
function renderInventoryTable(array $products, string $cat): void {
    $color = $cat === 'accesorios' ? '#6f42c1' : '#198754';
    echo '<div class="table-wrap mb-5">';
    echo '<table class="table table-hover table-bordered bg-white shadow-sm">';
    echo '<thead style="background:' . ($cat==='accesorios'?'#e9d5ff':'') . '" class="' . ($cat==='general'?'table-success':'') . '">';
    echo '<tr>
            <th>Nombre</th>
            <th>SKU</th>
            <th>Stock</th>
            <th>Total invertido<br><small class="fw-normal text-muted">(compras acumuladas)</small></th>
            <th>P. Venta</th>
            <th></th>
          </tr>';
    echo '</thead><tbody>';
    if (empty($products)) {
        echo '<tr><td colspan="6" class="text-center text-muted py-3">No hay productos creados aún.</td></tr>';
    }
    foreach ($products as $p) {
        $rowClass = $p['stock'] <= 0 ? 'table-danger' : ($p['stock'] <= 5 ? 'table-warning' : '');
        echo '<tr class="' . $rowClass . '">';
        echo '<td><strong>' . htmlspecialchars($p['name']) . '</strong></td>';
        echo '<td class="text-muted small">' . htmlspecialchars($p['sku']) . '</td>';
        $badges = '';
        if ($p['stock'] <= 0) $badges = '<span class="badge bg-danger ms-1">Agotado</span>';
        elseif ($p['stock'] <= 5) $badges = '<span class="badge bg-warning text-dark ms-1">Poco</span>';
        echo '<td><strong>' . $p['stock'] . '</strong>' . $badges . '</td>';
        $inv = (float)$p['total_invertido'];
        $ini = (float)$p['purchase_price'];
        $extra = $inv > $ini
            ? '<br><small class="text-muted">Inicial: $' . number_format($ini,0,',','.') . ' + compras: $' . number_format($inv-$ini,0,',','.') . '</small>'
            : '';
        echo '<td><strong>$' . number_format($inv,0,',','.') . '</strong>' . $extra . '</td>';
        echo '<td>$' . number_format((int)$p['sale_price'],0,',','.') . '</td>';
        echo '<td><a href="?edit=' . $p['id'] . '" class="btn btn-sm btn-outline-primary">✏️ Editar</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
?>

<!-- Banner instalación PWA -->
<div id="install-banner" style="align-items:center; gap:10px; display:none;">
  <span style="font-size:28px;">🐾</span>
  <div style="flex:1"><strong>Instalar app</strong><br><small class="text-muted">Agregar Peluditos a tu pantalla de inicio</small></div>
  <button id="btn-install-pwa" class="btn btn-success btn-sm">Instalar</button>
  <button id="btn-dismiss-pwa" class="btn btn-outline-secondary btn-sm">✕</button>
</div>

<!-- Navegación inferior (mobile) -->
<nav class="bottom-nav d-md-none">
  <a href="manage_products.php" class="active"><span class="nav-icon">📦</span>Productos</a>
  <a href="record_sale.php"><span class="nav-icon">💰</span>Venta</a>
  <a href="purchase_stock.php"><span class="nav-icon">🛒</span>Compra</a>
  <a href="report_excel.php"><span class="nav-icon">📊</span>Excel</a>
</nav>

<?php require_once __DIR__ . '/src/pwa_scripts.php'; ?>
</body>
</html>
