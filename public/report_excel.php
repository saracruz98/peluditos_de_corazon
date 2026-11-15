<?php
// public/report_excel.php – genera reporte en formato Excel (XLSX)
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/../xlsx.php'; // SimpleXLSXWriter class

$pdo = getPDO();
require_once __DIR__ . '/src/inventory.php';

// Obtener datos de ventas y conteo de baños
$sales = getSalesSummary($pdo);
$bathCount = getBathCount($pdo);

$filename = 'reporte_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer = new SimpleXLSXWriter();

$rows = [];
// Cabecera
$rows[] = ['SKU', 'Producto', 'Categoría', 'Stock inicial', 'Vendidos', 'Stock restante', 'Reordenar?'];

foreach ($sales as $p) {
    $initial = $p['stock'] + $p['sold']; // stock antes de ventas
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

// Línea en blanco
$rows[] = [];
// Total de baños realizados
$rows[] = ['Total baños realizados', $bathCount];

$writer->addSheet('Reporte', $rows);
$writer->writeToStdOut();
?>