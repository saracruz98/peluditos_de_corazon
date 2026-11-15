<?php
// public/report_excel.php – genera reporte Excel bonito con ventas y baños diarios
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/../xlsx.php'; // SimpleXLSXWriter

$pdo = getPDO();
require_once __DIR__ . '/src/inventory.php';

// Resúmenes básicos
$sales = getSalesSummary($pdo);
$bathCount = getBathCount($pdo);

// Resúmenes diarios
$dailySales = getDailySales($pdo);
$dailyBaths = getDailyBaths($pdo);

$filename = 'reporte_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer = new SimpleXLSXWriter();

// ---------------------------------------------------
// Hoja 1 – Resumen de productos
$rows = [];
$rows[] = ['SKU','Producto','Categoría','Stock inicial','Vendidos','Stock restante','Reordenar?'];
foreach ($sales as $p) {
    $initial = $p['stock'] + $p['sold'];
    $remaining = $p['stock'];
    $reorder = $remaining <= 0 ? 'Sí' : 'No';
    $rows[] = [
        $p['sku'],
        $p['name'],
        ucfirst($p['category']),
        $initial,
        $p['sold'],
        $remaining,
        $reorder,
    ];
}
// Total baños al final
$rows[] = [];
$rows[] = ['Total baños realizados', $bathCount];
$writer->addSheet('Resumen Productos', $rows);

// ---------------------------------------------------
// Hoja 2 – Ventas diarias
$rows = [];
$rows[] = ['Fecha','Total unidades vendidas'];
foreach ($dailySales as $d) {
    $rows[] = [$d['day'], $d['total_sold']];
}
$writer->addSheet('Ventas Diarias', $rows);

// ---------------------------------------------------
// Hoja 3 – Baños diarios
$rows = [];
$rows[] = ['Fecha','Baños realizados'];
foreach ($dailyBaths as $b) {
    $rows[] = [$b['day'], $b['baths_done']];
}
$writer->addSheet('Baños Diarios', $rows);

$writer->writeToStdOut();
?>