<?php
// public/partials/header.php – plantilla de cabecera con navbar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Pet‑Store' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid container-lg">
        <a class="navbar-brand" href="index.php"><i class="bi bi-paw-fill"></i> Pet‑Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="manage_products.php"><i class="bi bi-card-list"></i> Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="record_sale.php"><i class="bi bi-cash-coin"></i> Ventas</a></li>
                <li class="nav-item"><a class="nav-link" href="purchase_stock.php"><i class="bi bi-box-seam"></i> Compras</a></li>
                <li class="nav-item"><a class="nav-link" href="report_excel.php"><i class="bi bi-file-earmark-excel"></i> Excel</a></li>
                <li class="nav-item"><a class="nav-link" href="report.php"><i class="bi bi-file-earmark-csv"></i> CSV</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-lg">
