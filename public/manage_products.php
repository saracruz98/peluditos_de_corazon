<?php
// public/manage_products.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'product';

    if ($action === 'bath') {
        $breed = trim($_POST['breed'] ?? '');
        $qty   = (int)($_POST['bath_qty'] ?? 1);
        $price = (int)($_POST['price'] ?? 0);
        if ($breed === '') {
            $msg = ['type' => 'danger', 'text' => '⚠️ La raza del perro es obligatoria.'];
        } else {
            $pdo->prepare('INSERT INTO bath_records (breed, qty, price) VALUES (:b,:q,:p)')
                ->execute([':b' => $breed, ':q' => $qty, ':p' => $price]);
            $msg = ['type' => 'success', 'text' => '✅ Baño registrado: <strong>' . $qty . '</strong> perro(s) raza <strong>' . htmlspecialchars($breed) . '</strong> — $' . number_format($price, 0, ',', '.') . ' c/u.'];
        }

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
                    $msg = ['type' => 'success', 'text' => '✅ Producto actualizado.'];
                } else {
                    $pdo->prepare('INSERT INTO products (sku,name,category,stock,purchase_price,sale_price) VALUES (:sku,:name,:cat,:stk,:pp,:sp)')
                        ->execute([':sku'=>$sku,':name'=>$name,':cat'=>$category,':stk'=>$stock,':pp'=>$pp,':sp'=>$sp]);
                    $msg = ['type' => 'success', 'text' => '✅ Producto creado correctamente.'];
                }
            } catch (PDOException $e) {
                $msg = ['type' => 'danger', 'text' => 'Error: ' . $e->getMessage()];
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
$bathList = $pdo->query('SELECT * FROM bath_records ORDER BY recorded_at DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);

$activeTab = 'general';
if ($edit) {
    if ($edit['category'] === 'bano') $activeTab = 'bano';
    elseif ($edit['category'] === 'accesorios') $activeTab = 'accesorios';
}

// Helper: valor del campo en edición
function val(array|null $edit, string $field, string $cat, $default = ''): mixed {
    return ($edit && $edit['category'] === $cat) ? $edit[$field] : $default;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🐾 Peluditos | Productos</title>
<?php require_once __DIR__ . '/src/pwa_head.php'; ?>
<style>
/* ── Pestañas ── */
.nav-tabs { flex-wrap: nowrap; overflow-x: auto; }
.nav-tabs .nav-link { white-space: nowrap; font-size: 13px; padding: 8px 14px; }
.nav-tabs .nav-link.active { font-weight: 700; }

/* ── Formularios ── */
.field-row { display: flex; flex-direction: column; gap: 14px; }
.field-group { display: flex; flex-direction: column; gap: 4px; }
.field-group label { font-weight: 600; font-size: 13px; color: #444; }
.field-group input, .field-group select {
  border-radius: 10px;
  font-size: 16px; /* evita zoom en iOS */
  padding: 10px 14px;
  border: 1.5px solid #ced4da;
  width: 100%;
  background: #fff;
}
.field-group input:focus, .field-group select:focus {
  border-color: #198754;
  outline: none;
  box-shadow: 0 0 0 3px rgba(25,135,84,.15);
}
/* En desktop: 2 campos por fila */
@media (min-width: 576px) {
  .field-row-2 { flex-direction: row; }
  .field-row-2 .field-group { flex: 1; }
  .field-row-3 { flex-direction: row; }
  .field-row-3 .field-group { flex: 1; }
}

/* ── Tarjetas inventario ── */
.inv-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #dee2e6;
  margin-bottom: 10px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.inv-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid #f0f0f0;
}
.inv-card-name { font-weight: 700; font-size: 15px; }
.inv-card-sku  { font-size: 11px; color: #888; }
.inv-card-body { padding: 10px 14px; display: flex; gap: 8px; flex-wrap: wrap; }
.inv-stat {
  flex: 1; min-width: 80px;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 8px 10px;
  text-align: center;
}
.inv-stat-label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: .5px; }
.inv-stat-value { font-size: 14px; font-weight: 700; color: #222; margin-top: 2px; }
.inv-stat-value.green { color: #198754; }
.inv-stat-value.purple{ color: #6f42c1; }

/* Colores por categoría */
.hdr-general { background: #198754; color: #fff; }
.hdr-bano    { background: #0dcaf0; color: #000; }
.hdr-accs    { background: #6f42c1; color: #fff; }

.btn-submit-general { background:#198754; color:#fff; border:none; border-radius:10px; padding:12px; font-size:16px; font-weight:700; width:100%; cursor:pointer; }
.btn-submit-general:active { background:#145c38; }
.btn-submit-bano    { background:#0dcaf0; color:#000; border:none; border-radius:10px; padding:12px; font-size:16px; font-weight:700; width:100%; cursor:pointer; }
.btn-submit-bano:active    { background:#0aaac8; }
.btn-submit-accs    { background:#6f42c1; color:#fff; border:none; border-radius:10px; padding:12px; font-size:16px; font-weight:700; width:100%; cursor:pointer; }
.btn-submit-accs:active    { background:#59369d; }

.section-sep { border:none; border-top:2px solid #f0f0f0; margin:14px 0; }
.inv-empty { text-align:center; color:#aaa; padding:30px 0; font-size:14px; }

.badge-agotado { background:#dc3545; color:#fff; border-radius:6px; font-size:11px; padding:2px 7px; }
.badge-poco    { background:#ffc107; color:#000; border-radius:6px; font-size:11px; padding:2px 7px; }

/* Acumulado en gris pequeño */
.inv-sub { font-size:11px; color:#888; margin-top:2px; }
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
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show mb-3">
    <?= $msg['text'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- PESTAÑAS -->
<ul class="nav nav-tabs mb-3" id="mainTab">
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='general'    ? 'active' : '' ?>"
            data-bs-toggle="tab" data-bs-target="#panel-general" type="button">
      🛒 General
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='bano'       ? 'active' : '' ?>"
            data-bs-toggle="tab" data-bs-target="#panel-bano" type="button">
      🛁 Baños
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab==='accesorios' ? 'active' : '' ?>"
            data-bs-toggle="tab" data-bs-target="#panel-accesorios" type="button">
      🎀 Accesorios
    </button>
  </li>
</ul>

<div class="tab-content">

<!-- ══════════════════════ PANEL GENERAL ══════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='general' ? 'show active' : '' ?>" id="panel-general">
  <div class="card shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header hdr-general fw-bold">
      <?= ($edit && $edit['category']==='general') ? '✏️ Editar producto' : '➕ Nuevo producto general' ?>
    </div>
    <div class="card-body p-3">
      <form method="post" autocomplete="off">
        <?php if ($edit && $edit['category']==='general'): ?>
          <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="action"   value="product">
        <input type="hidden" name="category" value="general">

        <div class="field-row mb-3">
          <div class="field-group">
            <label>Nombre del producto *</label>
            <input name="name" placeholder="Ej: Alimento para perro"
                   value="<?= htmlspecialchars((string)val($edit,'name','general','')) ?>" required>
          </div>
        </div>

        <div class="field-row field-row-2 mb-3">
          <div class="field-group">
            <label>SKU <span style="font-weight:400;color:#999;">(opcional)</span></label>
            <input name="sku" placeholder="Ej: ALIM-001"
                   value="<?= htmlspecialchars((string)val($edit,'sku','general','')) ?>">
          </div>
          <div class="field-group">
            <label>Stock inicial</label>
            <input name="stock" type="number" min="0" step="1" placeholder="0"
                   value="<?= (int)val($edit,'stock','general',0) ?>">
          </div>
        </div>

        <div class="field-row field-row-2 mb-3">
          <div class="field-group">
            <label>💵 Inversión inicial ($)</label>
            <input name="purchase_price" type="number" step="1" min="0" placeholder="Lo que costó la compra inicial"
                   value="<?= (int)val($edit,'purchase_price','general',0) ?>">
          </div>
          <div class="field-group">
            <label>🏷️ Precio de venta ($)</label>
            <input name="sale_price" type="number" step="1" min="0" placeholder="A cuánto se vende"
                   value="<?= (int)val($edit,'sale_price','general',0) ?>">
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-submit-general flex-grow-1">
            <?= ($edit && $edit['category']==='general') ? '💾 Actualizar' : '✅ Crear producto' ?>
          </button>
          <?php if ($edit && $edit['category']==='general'): ?>
            <a href="manage_products.php" class="btn btn-secondary" style="border-radius:10px;padding:12px 18px;">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Inventario General -->
  <p class="fw-bold mb-2" style="color:#198754; font-size:15px;">📦 Inventario General</p>
  <?php renderCards($productsGeneral, 'general'); ?>
</div><!-- /panel-general -->


<!-- ══════════════════════ PANEL BAÑO ══════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='bano' ? 'show active' : '' ?>" id="panel-bano">
  <div class="card shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header hdr-bano fw-bold">🛁 Registrar servicio de baño</div>
    <div class="card-body p-3">
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="bath">

        <div class="field-row mb-3">
          <div class="field-group">
            <label>🐕 Raza del perro *</label>
            <input name="breed" placeholder="Ej: Bulldog, Poodle, Labrador…" required>
          </div>
        </div>

        <div class="field-row field-row-2 mb-3">
          <div class="field-group">
            <label>Cantidad de perros</label>
            <input name="bath_qty" type="number" min="1" value="1">
          </div>
          <div class="field-group">
            <label>💰 Precio del baño ($)</label>
            <input name="price" type="number" step="1" min="0" placeholder="Ej: 25000" required>
          </div>
        </div>

        <button type="submit" class="btn-submit-bano">🛁 Registrar baño</button>
      </form>
    </div>
  </div>

  <!-- Historial baños -->
  <p class="fw-bold mb-2" style="color:#0987a0; font-size:15px;">📋 Historial de baños</p>
  <?php if (empty($bathList)): ?>
    <div class="inv-empty">No hay baños registrados aún.</div>
  <?php else: ?>
    <?php foreach ($bathList as $b): ?>
      <div class="inv-card">
        <div class="inv-card-header" style="border-left:4px solid #0dcaf0;">
          <div>
            <div class="inv-card-name"><?= htmlspecialchars($b['breed']) ?></div>
            <div class="inv-card-sku"><?= date('d/m/Y H:i', strtotime($b['recorded_at'])) ?></div>
          </div>
          <span style="font-size:22px;">🛁</span>
        </div>
        <div class="inv-card-body">
          <div class="inv-stat">
            <div class="inv-stat-label">Cantidad</div>
            <div class="inv-stat-value"><?= $b['qty'] ?></div>
          </div>
          <div class="inv-stat">
            <div class="inv-stat-label">Precio c/u</div>
            <div class="inv-stat-value">$<?= number_format((int)$b['price'], 0, ',', '.') ?></div>
          </div>
          <div class="inv-stat">
            <div class="inv-stat-label">Total</div>
            <div class="inv-stat-value green">$<?= number_format((int)$b['price'] * (int)$b['qty'], 0, ',', '.') ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div><!-- /panel-bano -->


<!-- ══════════════════════ PANEL ACCESORIOS ══════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='accesorios' ? 'show active' : '' ?>" id="panel-accesorios">
  <div class="card shadow-sm mb-4" style="border-radius:14px; overflow:hidden;">
    <div class="card-header hdr-accs fw-bold">
      <?= ($edit && $edit['category']==='accesorios') ? '✏️ Editar accesorio' : '🎀 Nuevo accesorio' ?>
    </div>
    <div class="card-body p-3">
      <div class="alert alert-secondary py-2 mb-3" style="font-size:12px; border-radius:8px;">
        Collares · Snacks · Desparasitantes · Shampú · Juguetes · Ropa para mascotas
      </div>
      <form method="post" autocomplete="off">
        <?php if ($edit && $edit['category']==='accesorios'): ?>
          <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="action"   value="product">
        <input type="hidden" name="category" value="accesorios">

        <div class="field-row mb-3">
          <div class="field-group">
            <label>Nombre del accesorio *</label>
            <input name="name" placeholder="Ej: Collar talla M, Snack dental, Antipulgas…"
                   value="<?= htmlspecialchars((string)val($edit,'name','accesorios','')) ?>" required>
          </div>
        </div>

        <div class="field-row field-row-2 mb-3">
          <div class="field-group">
            <label>SKU <span style="font-weight:400;color:#999;">(opcional)</span></label>
            <input name="sku" placeholder="Ej: COL-M-001"
                   value="<?= htmlspecialchars((string)val($edit,'sku','accesorios','')) ?>">
          </div>
          <div class="field-group">
            <label>Stock inicial</label>
            <input name="stock" type="number" min="0" step="1" placeholder="0"
                   value="<?= (int)val($edit,'stock','accesorios',0) ?>">
          </div>
        </div>

        <div class="field-row field-row-2 mb-3">
          <div class="field-group">
            <label>💵 Inversión inicial ($)</label>
            <input name="purchase_price" type="number" step="1" min="0" placeholder="Lo que costó"
                   value="<?= (int)val($edit,'purchase_price','accesorios',0) ?>">
          </div>
          <div class="field-group">
            <label>🏷️ Precio de venta ($)</label>
            <input name="sale_price" type="number" step="1" min="0" placeholder="A cuánto se vende"
                   value="<?= (int)val($edit,'sale_price','accesorios',0) ?>">
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-submit-accs flex-grow-1">
            <?= ($edit && $edit['category']==='accesorios') ? '💾 Actualizar' : '✅ Agregar accesorio' ?>
          </button>
          <?php if ($edit && $edit['category']==='accesorios'): ?>
            <a href="manage_products.php" class="btn btn-secondary" style="border-radius:10px;padding:12px 18px;">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Inventario Accesorios -->
  <p class="fw-bold mb-2" style="color:#6f42c1; font-size:15px;">🎀 Inventario de Accesorios</p>
  <?php renderCards($productsAccesorios, 'accesorios'); ?>
</div><!-- /panel-accesorios -->

</div><!-- /tab-content -->
</div><!-- /container -->

<?php
function renderCards(array $products, string $cat): void {
    $accentColor = $cat === 'accesorios' ? '#6f42c1' : '#198754';
    if (empty($products)) {
        echo '<div class="inv-empty">No hay productos creados aún.</div>';
        return;
    }
    foreach ($products as $p) {
        $stock = (int)$p['stock'];
        $inv   = (float)$p['total_invertido'];
        $ini   = (float)$p['purchase_price'];
        $venta = (int)$p['sale_price'];

        // Color de fondo según stock
        $rowBg = $stock <= 0 ? '#fff5f5' : ($stock <= 5 ? '#fffbf0' : '#fff');
        $borderColor = $accentColor;

        echo '<div class="inv-card" style="background:' . $rowBg . '">';
        // Header
        echo '<div class="inv-card-header" style="border-left:4px solid ' . $borderColor . '">';
        echo '<div>';
        echo '<div class="inv-card-name">' . htmlspecialchars($p['name']) . '</div>';
        if ($p['sku']) echo '<div class="inv-card-sku">SKU: ' . htmlspecialchars($p['sku']) . '</div>';
        echo '</div>';
        echo '<div class="d-flex align-items-center gap-2">';
        if ($stock <= 0)   echo '<span class="badge-agotado">Agotado</span>';
        elseif ($stock <= 5) echo '<span class="badge-poco">Poco</span>';
        echo '<a href="?edit=' . $p['id'] . '" style="font-size:20px; text-decoration:none;">✏️</a>';
        echo '</div>';
        echo '</div>';
        // Body
        echo '<div class="inv-card-body">';
        echo '<div class="inv-stat">';
        echo '<div class="inv-stat-label">Stock</div>';
        echo '<div class="inv-stat-value">' . $stock . '</div>';
        echo '</div>';
        echo '<div class="inv-stat">';
        echo '<div class="inv-stat-label">Total invertido</div>';
        echo '<div class="inv-stat-value" style="color:' . $borderColor . '">$' . number_format($inv, 0, ',', '.') . '</div>';
        if ($inv > $ini) {
            echo '<div class="inv-sub">+$' . number_format($inv - $ini, 0, ',', '.') . ' compras</div>';
        }
        echo '</div>';
        echo '<div class="inv-stat">';
        echo '<div class="inv-stat-label">P. Venta</div>';
        echo '<div class="inv-stat-value green">$' . number_format($venta, 0, ',', '.') . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
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
