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

    // ── Crear / editar producto general ─────────────────────────────
    } else {
        $name     = trim($_POST['name'] ?? '');
        $sku      = trim($_POST['sku'] ?? '');
        $stock    = (int)($_POST['stock'] ?? 0);
        $pp       = (int)($_POST['purchase_price'] ?? 0);
        $sp       = (int)($_POST['sale_price'] ?? 0);
        $id       = $_POST['id'] ?? null;

        if ($name === '') {
            $msg = ['type' => 'danger', 'text' => '⚠️ El nombre es obligatorio.'];
        } else {
            try {
                if ($id) {
                    $pdo->prepare('UPDATE products SET sku=:sku,name=:name,category=\'general\',stock=:stk,purchase_price=:pp,sale_price=:sp WHERE id=:id')
                        ->execute([':sku'=>$sku,':name'=>$name,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp,':id'=>$id]);
                    $msg = ['type' => 'success', 'text' => '✅ Producto actualizado correctamente.'];
                } else {
                    $pdo->prepare('INSERT INTO products (sku,name,category,stock,purchase_price,sale_price) VALUES (:sku,:name,\'general\',:stk,:pp,:sp)')
                        ->execute([':sku'=>$sku,':name'=>$name,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp]);
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

// Solo productos GENERALES en inventario
$products = array_filter(getProductList($pdo), fn($p) => $p['category'] !== 'bano');

// Últimos baños registrados
$bathList = $pdo->query('SELECT * FROM bath_records ORDER BY recorded_at DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🐾 Peluditos | Productos</title>
<?php require_once __DIR__ . '/src/pwa_head.php'; ?>
<style>
  .section-title { color: #198754; border-bottom: 2px solid #198754; padding-bottom: 6px; margin-bottom: 16px; }
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

<!-- Header mobile -->
<div class="bg-success text-white text-center py-2 d-md-none fw-bold fs-5">🐾 Peluditos de Corazón</div>

<div class="container mt-3">

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
    <?= $msg['text'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- ══════════════ PESTAÑAS: GENERAL / BAÑO ══════════════ -->
<ul class="nav nav-tabs mb-3" id="mainTab">
  <li class="nav-item">
    <button class="nav-link <?= (!$edit || $edit['category']==='general') ? 'active' : '' ?> fw-bold"
            id="tab-general" data-bs-toggle="tab" data-bs-target="#panel-general" type="button">
      🛒 Producto General
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= ($edit && $edit['category']==='bano') ? 'active' : '' ?> fw-bold"
            id="tab-bano" data-bs-toggle="tab" data-bs-target="#panel-bano" type="button">
      🛁 Registrar Baño
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ── PANEL GENERAL ─────────────────────────────────────────────── -->
  <div class="tab-pane fade <?= (!$edit || $edit['category']==='general') ? 'show active' : '' ?>" id="panel-general">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-success text-white fw-bold">
        <?= $edit ? '✏️ Editar producto' : '➕ Nuevo producto general' ?>
      </div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <?php if ($edit && $edit['category']==='general'): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
          <?php endif; ?>
          <input type="hidden" name="action" value="product">

          <div class="col-12 col-md-4">
            <label class="form-label">Nombre del producto</label>
            <input name="name" class="form-control form-control-lg"
                   placeholder="Ej: Alimento para perro"
                   value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">SKU (opcional)</label>
            <input name="sku" class="form-control form-control-lg"
                   placeholder="Ej: ALIM-001"
                   value="<?= htmlspecialchars($edit['sku'] ?? '') ?>">
          </div>
          <div class="col-6 col-md-1">
            <label class="form-label">Stock</label>
            <input name="stock" type="number" min="0" step="1" class="form-control form-control-lg"
                   value="<?= (int)($edit['stock'] ?? 0) ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">P. Compra ($)</label>
            <input name="purchase_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= (int)($edit['purchase_price'] ?? 0) ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">P. Venta ($)</label>
            <input name="sale_price" type="number" step="1" min="0" class="form-control form-control-lg"
                   placeholder="0" value="<?= (int)($edit['sale_price'] ?? 0) ?>">
          </div>
          <div class="col-12 d-grid gap-2 d-md-flex">
            <button type="submit" class="btn btn-success btn-lg flex-grow-1">
              <?= $edit ? '💾 Actualizar producto' : '✅ Crear producto' ?>
            </button>
            <?php if ($edit): ?>
              <a href="manage_products.php" class="btn btn-secondary btn-lg">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Inventario solo productos generales -->
    <h5 class="section-title">📦 Inventario actual</h5>
    <div class="table-wrap mb-5">
      <table class="table table-hover table-bordered bg-white shadow-sm">
        <thead class="table-success">
          <tr>
            <th>Nombre</th>
            <th>SKU</th>
            <th>Stock</th>
            <th>P. Compra</th>
            <th>P. Venta</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
          <tr class="<?= $p['stock'] <= 0 ? 'table-danger' : ($p['stock'] <= 5 ? 'table-warning' : '') ?>">
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td class="text-muted small"><?= htmlspecialchars($p['sku']) ?></td>
            <td>
              <strong><?= $p['stock'] ?></strong>
              <?= $p['stock'] <= 0 ? '<span class="badge bg-danger ms-1">Agotado</span>' : ($p['stock'] <= 5 ? '<span class="badge bg-warning text-dark ms-1">Poco</span>' : '') ?>
            </td>
            <td>$<?= number_format((int)$p['purchase_price'], 0, ',', '.') ?></td>
            <td>$<?= number_format((int)$p['sale_price'], 0, ',', '.') ?></td>
            <td><a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">✏️ Editar</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">No hay productos creados aún.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div><!-- /panel-general -->

  <!-- ── PANEL BAÑO ─────────────────────────────────────────────────── -->
  <div class="tab-pane fade <?= ($edit && $edit['category']==='bano') ? 'show active' : '' ?>" id="panel-bano">
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
            <button type="submit" class="btn btn-lg" style="background:#0dcaf0; color:#000; font-weight:bold;">
              🛁 Registrar baño
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Historial de baños -->
    <h5 class="mb-3" style="color:#0dcaf0; border-bottom: 2px solid #0dcaf0; padding-bottom:6px;">
      📋 Historial de baños
    </h5>
    <div class="table-wrap mb-5">
      <table class="table table-hover table-bordered bg-white shadow-sm">
        <thead style="background:#0dcaf0;">
          <tr>
            <th>Raza</th>
            <th>Cantidad</th>
            <th>Precio c/u</th>
            <th>Total</th>
            <th>Fecha</th>
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

</div><!-- /tab-content -->
</div><!-- /container -->

<!-- Banner instalación PWA -->
<div id="install-banner" style="align-items:center; gap:10px; display:none;">
  <span style="font-size:28px;">🐾</span>
  <div style="flex:1">
    <strong>Instalar app</strong><br>
    <small class="text-muted">Agregar Peluditos a tu pantalla de inicio</small>
  </div>
  <button id="btn-install-pwa" class="btn btn-success btn-sm">Instalar</button>
  <button id="btn-dismiss-pwa" class="btn btn-outline-secondary btn-sm">✕</button>
</div>

<!-- Navegación inferior (mobile) -->
<nav class="bottom-nav d-md-none">
  <a href="manage_products.php" class="active">
    <span class="nav-icon">📦</span>Productos
  </a>
  <a href="record_sale.php">
    <span class="nav-icon">💰</span>Venta
  </a>
  <a href="purchase_stock.php">
    <span class="nav-icon">🛒</span>Compra
  </a>
  <a href="report_excel.php">
    <span class="nav-icon">📊</span>Excel
  </a>
</nav>

<?php require_once __DIR__ . '/src/pwa_scripts.php'; ?>
</body>
</html>
