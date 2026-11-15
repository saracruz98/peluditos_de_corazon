<?php
// public/report.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
initDB($pdo);

$sales     = getSalesSummary($pdo);
$bathCount = getBathCount($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pet-Store | Reporte</title>
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
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Descargar Excel</a>
    </div>
  </div>
</nav>
<div class="container">
<h4 class="mb-3">📊 Reporte general — Baños realizados: <span class="badge bg-success"><?= $bathCount ?></span></h4>
<div class="table-responsive">
<table class="table table-hover table-bordered bg-white shadow-sm">
  <thead class="table-success">
    <tr><th>SKU</th><th>Nombre</th><th>Categoría</th><th>Vendidos</th><th>Stock restante</th><th>¿Reponer?</th></tr>
  </thead>
  <tbody>
  <?php foreach ($sales as $p): ?>
    <tr class="<?= $p['stock'] <= 0 ? 'table-danger' : ($p['stock'] <= 5 ? 'table-warning' : '') ?>">
      <td><?= htmlspecialchars($p['sku']) ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= $p['category'] === 'bano' ? '🛁 Baño' : '🛒 General' ?></td>
      <td><?= $p['sold'] ?></td>
      <td><strong><?= $p['stock'] ?></strong><?= $p['stock'] <= 0 ? ' <span class="badge bg-danger">Agotado</span>' : ($p['stock'] <= 5 ? ' <span class="badge bg-warning text-dark">Poco</span>' : '') ?></td>
      <td><?= $p['stock'] <= 0 ? '<span class="text-danger fw-bold">SÍ - URGENTE</span>' : ($p['stock'] <= 5 ? '<span class="text-warning fw-bold">Sí - Poco</span>' : 'No') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
