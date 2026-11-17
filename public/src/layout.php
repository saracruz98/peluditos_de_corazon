<?php
// public/src/layout.php  — navbar y estilos compartidos para todas las páginas
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta name="theme-color" content="#2e7d32">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>🐾 Pet-Store <?= isset($pageTitle) ? "| $pageTitle" : '' ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { padding-bottom: 80px; font-size: 16px; }

    /* ── Navbar ── */
    .navbar-brand { font-size: 1.3rem; }
    .navbar-toggler { border-color: rgba(255,255,255,.5); }
    .navbar-toggler-icon { filter: invert(1); }

    /* ── Bottom nav (solo móvil) ── */
    .bottom-nav {
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
      background: #fff; border-top: 2px solid #2e7d32;
      display: flex; justify-content: space-around; align-items: center;
      padding: 6px 0 8px;
    }
    .bottom-nav a {
      display: flex; flex-direction: column; align-items: center;
      text-decoration: none; color: #555; font-size: 0.65rem;
      flex: 1; padding: 2px 0;
    }
    .bottom-nav a .icon { font-size: 1.5rem; line-height: 1.2; }
    .bottom-nav a.active { color: #2e7d32; font-weight: 700; }
    .bottom-nav a.active .icon { filter: drop-shadow(0 0 2px #2e7d3280); }

    /* ── Cards y formularios ── */
    .card { border-radius: 12px; }
    .card-header { border-radius: 12px 12px 0 0 !important; font-size: 1rem; }
    .form-control, .form-select { font-size: 1rem; min-height: 48px; }
    .btn-lg { min-height: 52px; font-size: 1.1rem; border-radius: 10px; }
    .btn { border-radius: 8px; }

    /* ── Tablas responsive ── */
    .table-responsive { border-radius: 10px; overflow: hidden; }
    .table td, .table th { vertical-align: middle; white-space: nowrap; }

    /* ── Desktop: ocultar bottom nav ── */
    @media (min-width: 768px) {
      .bottom-nav { display: none; }
      body { padding-bottom: 20px; }
    }

    /* ── Badge stock ── */
    .badge { font-size: 0.75rem; }
  </style>
</head>
<body class="bg-light">

<!-- ── Navbar top ── -->
<nav class="navbar navbar-dark bg-success">
  <div class="container-fluid px-3">
    <a class="navbar-brand fw-bold" href="manage_products.php">🐾 Pet-Store</a>
    <!-- Desktop links (ocultos en móvil) -->
    <div class="d-none d-md-flex gap-2">
      <a href="manage_products.php" class="btn btn-sm <?= $currentPage==='manage_products.php'?'btn-warning':'btn-light' ?>">📦 Productos</a>
      <a href="record_sale.php"     class="btn btn-sm <?= $currentPage==='record_sale.php'?'btn-warning':'btn-light' ?>">💰 Venta</a>
      <a href="purchase_stock.php"  class="btn btn-sm <?= $currentPage==='purchase_stock.php'?'btn-warning':'btn-light' ?>">🛒 Compra</a>
      <a href="report_excel.php"    class="btn btn-sm btn-warning">📊 Excel</a>
    </div>
  </div>
</nav>

<!-- ── Bottom nav (solo móvil) ── -->
<nav class="bottom-nav d-md-none">
  <a href="manage_products.php" class="<?= $currentPage==='manage_products.php'?'active':'' ?>">
    <span class="icon">📦</span>Productos
  </a>
  <a href="record_sale.php" class="<?= $currentPage==='record_sale.php'?'active':'' ?>">
    <span class="icon">💰</span>Venta
  </a>
  <a href="purchase_stock.php" class="<?= $currentPage==='purchase_stock.php'?'active':'' ?>">
    <span class="icon">🛒</span>Compra
  </a>
  <a href="report_excel.php" class="<?= $currentPage==='report_excel.php'?'active':'' ?>">
    <span class="icon">📊</span>Excel
  </a>
</nav>

<div class="container-fluid px-3 py-3" style="max-width:800px; margin:auto">
