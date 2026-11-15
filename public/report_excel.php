<?php
// public/report_excel.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';
require_once __DIR__ . '/xlsx.php';

$pdo = getPDO();
initDB($pdo);

$sales      = getSalesSummary($pdo);
$bathCount  = getBathCount($pdo);
$dailySales = getDailySales($pdo);
$dailyBaths = getDailyBaths($pdo);

$w = new SimpleXLSXWriter();

// Hoja 1: Resumen Inventario
$rows = [['SKU','Producto','Categoría','Stock inicial','Vendidos','Stock restante','¿Reponer?']];
foreach ($sales as $p) {
    $initial   = $p['stock'] + $p['sold'];
    $remaining = $p['stock'];
    $rows[] = [
        $p['sku'],
        $p['name'],
        $p['category'] === 'bano' ? 'Baño' : 'General',
        (int)$initial,
        (int)$p['sold'],
        (int)$remaining,
        $remaining <= 0 ? 'SÍ - URGENTE' : ($remaining <= 5 ? 'Sí - Poco' : 'No'),
    ];
}
$rows[] = [];
$rows[] = ['Total baños realizados', (int)$bathCount];
$w->addSheet('Inventario', $rows, [10,30,12,14,12,14,14]);

// Hoja 2: Ventas diarias
$rows2 = [['Fecha','Unidades vendidas']];
foreach ($dailySales as $d) {
    $rows2[] = [$d['day'], (int)$d['total']];
}
$w->addSheet('Ventas Diarias', $rows2, [15,20]);

// Hoja 3: Baños diarios
$rows3 = [['Fecha','Baños realizados']];
foreach ($dailyBaths as $b) {
    $rows3[] = [$b['day'], (int)$b['baths']];
}
$w->addSheet('Baños Diarios', $rows3, [15,20]);

// Guardar en /tmp y enviar al navegador
$tmpFile  = '/tmp/reporte_peluditos_' . date('Ymd_His') . '.xlsx';
$filename = 'reporte_peluditos_' . date('Ymd_His') . '.xlsx';

$w->save($tmpFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache');
readfile($tmpFile);
@unlink($tmpFile); // borrar el temporal después de enviarlo
exit;
