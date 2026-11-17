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
    $unit_price = (int)($_POST['purchase_price'] ?? 0);
    $total      = $qty * $unit_price;

    $s = $pdo->prepare('SELECT * FROM products WHERE id=:id');
    $s->execute([':id' => $product_id]);
    $product = $s->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $msg = ['type' => 'danger', 'text' => '⚠️ Producto no encontrado.'];
    } elseif ($qty <= 0) {
        $msg = ['type' => 'danger', 'text' => '⚠️ La cantidad debe ser mayor que 0.'];
    } elseif ($unit_price <= 0) {
        $msg = ['type' => 'danger', 'text' => '⚠️ El precio de compra debe ser mayor que 0.'];
    } else {
        $pdo->beginTransaction();

        // Guardar la compra CON el precio unitario pagado (histórico acumulable)
        $pdo->prepare('INSERT INTO purchases (product_id, qty, unit_price) VALUES (:pid, :qty, :up)')
            ->execute([':pid' => $product_id, ':qty' => $qty, ':up' => $unit_price]);

        // Solo aumentar el stock — NO sobreescribir purchase_price del producto
        // El purchase_price del producto es solo referencia inicial
        $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id')
            ->execute([':qty' => $qty, ':id' => $product_id]);

        $pdo->commit();

        $nuevo_stock = $product['stock'] + $qty;

        // Calcular totales acumulados de este producto
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';
        $resumen = $pdo->prepare("
            SELECT $ifnull(SUM(qty),0) AS total_unidades,
                   $ifnull(SUM(qty * unit_price),0) AS total_invertido
            FROM purchases WHERE product_id = :pid
        ");
        $resumen->execute([':pid' => $product_id]);
        $r = $resumen->fetch(PDO::FETCH_ASSOC);

        $msg = [
            'type' => 'success',
            'text' => "✅ Compra registrada exitosamente:
                       <br>📦 <strong>" . htmlspecialchars($product['name']) . "</strong>
                       <br>🔢 Esta compra: <strong>{$qty} unidades</strong> × <strong>\$" . number_format($unit_price, 0, ',', '.') . " c/u</strong>
                       <br>💰 Pagado hoy: <strong>\$" . number_format($total, 0, ',', '.') . "</strong>
                       <br>📊 Stock actual: <strong>{$nuevo_stock} unidades</strong>
                       <hr class='my-2'>
                       <small class='text-muted'>
                       📈 Total comprado históricamente: <strong>" . (int)$r['total_unidades'] . " unidades</strong><br>
                       💵 Total invertido acumulado: <strong>\$" . number_format((float)$r['total_invertido'], 0, ',', '.') . "</strong>
                       </small>"
        ];
    }
}

// Productos generales Y accesorios separados
$allProducts        = array_filter(getProductList($pdo), fn($p) => $p['category'] !== 'bano');
$productsGeneral    = array_filter($allProducts, fn($p) => $p['category'] === 'general');
$productsAccesorios = array_filter($allProducts, fn($p) => $p['category'] === 'accesorios');

// Resumen financiero general
$financials = getFinancialSummary($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🐾 Peluditos | Registrar Compra</title>
<?php require_once __DIR__ . '/src/pwa_head.php'; ?>
<style>
  #total-box { font-size: 1.1rem; }
  .financial-card { border-left: 4px solid #198754; }
  .financial-card.warning { border-left-color: #ffc107; }
  .stat-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-value { font-size: 1.1rem; font-weight: bold; }
  .ganancia-positiva { color: #198754; }
  .ganancia-negativa { color: #dc3545; }
</style>
</head>
<body>

<!-- Navbar desktop -->
<nav class="navbar navbar-dark bg-success mb-3 d-none d-md-flex">
  <div class="container">
    <span class="navbar-brand fw-bold">🐾 Peluditos de Corazón</span>
    <div class="d-flex gap-2">
      <a href="manage_products.php" class="btn btn-sm btn-light">📦 Productos</a>
      <a href="record_sale.php"     class="btn btn-sm btn-light">💰 Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm btn-light fw-bold">🛒 Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>

<div class="bg-success text-white text-center py-2 d-md-none fw-bold fs-5">🐾 Peluditos de Corazón</div>

<div class="container mt-3">

  <!-- ══ FORMULARIO COMPRA ══════════════════════════════════════════ -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white fw-bold fs-5">🛒 Registrar compra de stock</div>
    <div class="card-body">

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
          <?= $msg['text'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" id="purchase-form">
        <div class="mb-3">
          <label class="form-label">📦 Producto</label>
          <select name="product_id" id="product-select" class="form-select form-select-lg" required onchange="showProductInfo()">
            <option value="">Selecciona un producto...</option>
            <?php if (!empty($productsGeneral)): ?>
              <optgroup label="🛒 Productos Generales">
                <?php foreach ($productsGeneral as $p): ?>
                  <option value="<?= $p['id'] ?>"
                          data-stock="<?= $p['stock'] ?>"
                          data-price-ref="<?= (int)$p['purchase_price'] ?>">
                    <?= htmlspecialchars($p['name']) ?> — Stock: <?= $p['stock'] ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
            <?php if (!empty($productsAccesorios)): ?>
              <optgroup label="🎀 Accesorios">
                <?php foreach ($productsAccesorios as $p): ?>
                  <option value="<?= $p['id'] ?>"
                          data-stock="<?= $p['stock'] ?>"
                          data-price-ref="<?= (int)$p['purchase_price'] ?>">
                    <?= htmlspecialchars($p['name']) ?> — Stock: <?= $p['stock'] ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
          </select>
        </div>

        <!-- Info del producto seleccionado -->
        <div id="product-info" class="alert alert-secondary py-2 mb-3" style="display:none">
          📦 Stock actual: <strong id="info-stock">0</strong> unidades
        </div>

        <div class="mb-3">
          <label class="form-label">🔢 Cantidad comprada</label>
          <input name="qty" id="qty" type="number" min="1" step="1"
                 class="form-control form-control-lg" placeholder="¿Cuántas unidades?" required oninput="calcTotal()">
        </div>
        <div class="mb-3">
          <label class="form-label">💵 Precio de compra por unidad (COP $)</label>
          <input name="purchase_price" id="unit-price" type="number" step="1" min="1"
                 class="form-control form-control-lg" placeholder="¿Cuánto pagó por cada unidad?" required oninput="calcTotal()">
        </div>

        <!-- Total en tiempo real -->
        <div class="alert alert-warning py-3 mb-3" id="total-box" style="display:none">
          <div class="row text-center g-2">
            <div class="col-6">
              <div class="stat-label">Unidades</div>
              <div class="stat-value" id="disp-qty">0</div>
            </div>
            <div class="col-6">
              <div class="stat-label">Precio c/u</div>
              <div class="stat-value" id="disp-price">$0</div>
            </div>
            <div class="col-12 border-top pt-2 mt-1">
              <div class="stat-label">💰 Total a pagar</div>
              <div style="font-size:1.5rem; font-weight:900; color:#856404;" id="total-display">$0</div>
            </div>
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success btn-lg">🛒 Registrar compra</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ══ RESUMEN FINANCIERO POR PRODUCTO ═══════════════════════════ -->
  <?php if (!empty($financials)): ?>
  <h5 class="mb-3" style="color:#198754; border-bottom:2px solid #198754; padding-bottom:6px;">
    📊 Resumen financiero de productos
  </h5>
  <div class="table-wrap mb-4">
    <table class="table table-hover table-bordered bg-white shadow-sm">
      <thead class="table-success">
        <tr>
          <th>Producto</th>
          <th class="text-center">Stock</th>
          <th class="text-end">Total invertido<br><small class="fw-normal">(compras acumuladas)</small></th>
          <th class="text-center">Vendidas</th>
          <th class="text-end">Ingreso ventas</th>
          <th class="text-end">Ganancia</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $gran_invertido = 0;
        $gran_ingresos  = 0;
        $gran_ganancia  = 0;
        foreach ($financials as $f):
          $invertido  = (float)$f['total_invertido'];
          $ingresos   = (float)$f['ingreso_ventas'];
          $ganancia   = $ingresos - $invertido;
          $gran_invertido += $invertido;
          $gran_ingresos  += $ingresos;
          $gran_ganancia  += $ganancia;
      ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($f['name']) ?></strong>
            <?php if ($f['sku']): ?>
              <br><small class="text-muted"><?= htmlspecialchars($f['sku']) ?></small>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?= $f['stock_actual'] ?>
            <?php if ((int)$f['stock_actual'] <= 0): ?>
              <span class="badge bg-danger ms-1">Agotado</span>
            <?php elseif ((int)$f['stock_actual'] <= 5): ?>
              <span class="badge bg-warning text-dark ms-1">Poco</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if ($invertido > 0): ?>
              $<?= number_format($invertido, 0, ',', '.') ?>
              <br><small class="text-muted"><?= (int)$f['unidades_compradas'] ?> u. compradas</small>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= (int)$f['unidades_vendidas'] ?> u.</td>
          <td class="text-end">
            <?php if ($ingresos > 0): ?>
              $<?= number_format($ingresos, 0, ',', '.') ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-bold <?= $ganancia >= 0 ? 'ganancia-positiva' : 'ganancia-negativa' ?>">
            <?php if ($invertido > 0 && $ingresos > 0): ?>
              <?= $ganancia >= 0 ? '+' : '' ?>$<?= number_format(abs($ganancia), 0, ',', '.') ?>
            <?php else: ?>
              <span class="text-muted small">Sin datos</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <td colspan="2"><strong>TOTALES</strong></td>
          <td class="text-end"><strong>$<?= number_format($gran_invertido, 0, ',', '.') ?></strong></td>
          <td></td>
          <td class="text-end"><strong>$<?= number_format($gran_ingresos, 0, ',', '.') ?></strong></td>
          <td class="text-end fw-bold <?= $gran_ganancia >= 0 ? 'ganancia-positiva' : 'ganancia-negativa' ?>">
            <strong><?= $gran_ganancia >= 0 ? '+' : '' ?>$<?= number_format(abs($gran_ganancia), 0, ',', '.') ?></strong>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- Banner instalación PWA -->
<div id="install-banner" style="align-items:center; gap:10px; display:none;">
  <span style="font-size:28px;">🐾</span>
  <div style="flex:1"><strong>Instalar app</strong><br><small class="text-muted">Agregar Peluditos a tu pantalla de inicio</small></div>
  <button id="btn-install-pwa" class="btn btn-success btn-sm">Instalar</button>
  <button id="btn-dismiss-pwa" class="btn btn-outline-secondary btn-sm">✕</button>
</div>

<!-- Navegación inferior (mobile) -->
<nav class="bottom-nav d-md-none">
  <a href="manage_products.php"><span class="nav-icon">📦</span>Productos</a>
  <a href="record_sale.php"><span class="nav-icon">💰</span>Venta</a>
  <a href="purchase_stock.php" class="active"><span class="nav-icon">🛒</span>Compra</a>
  <a href="report_excel.php"><span class="nav-icon">📊</span>Excel</a>
</nav>

<script>
function formatCOP(n) {
    return '$' + Math.round(n).toLocaleString('es-CO');
}
function showProductInfo() {
    const sel = document.getElementById('product-select');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('product-info');
    if (opt.value) {
        document.getElementById('info-stock').textContent = opt.dataset.stock || 0;
        info.style.display = '';
    } else {
        info.style.display = 'none';
    }
    calcTotal();
}
function calcTotal() {
    const qty   = parseInt(document.getElementById('qty').value) || 0;
    const price = parseInt(document.getElementById('unit-price').value) || 0;
    const total = qty * price;
    const box   = document.getElementById('total-box');
    if (qty > 0 && price > 0) {
        document.getElementById('disp-qty').textContent   = qty;
        document.getElementById('disp-price').textContent = formatCOP(price);
        document.getElementById('total-display').textContent = formatCOP(total);
        box.style.display = '';
    } else {
        box.style.display = 'none';
    }
}
</script>
<?php require_once __DIR__ . '/src/pwa_scripts.php'; ?>
</body>
</html>
