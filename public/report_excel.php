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

// Separar productos por categoría
$generales = array_filter($sales, fn($p) => $p['category'] !== 'bano');
$banos     = array_filter($sales, fn($p) => $p['category'] === 'bano');

// Calcular totales
$totalVendidosGeneral = array_sum(array_column($generales, 'sold'));
$totalStockGeneral    = array_sum(array_column($generales, 'stock'));
$totalVendidosBano    = array_sum(array_column($banos, 'sold'));
$totalStockBano       = array_sum(array_column($banos, 'stock'));

$w = new SimpleXLSXWriter();

// ── Hoja 1: Resumen completo separado ────────────────────────────────────────
$header = ['SKU', 'Producto', 'Categoría', 'Stock inicial', 'Vendidos', 'Stock restante', '¿Reponer?'];
$rows = [];

// --- Sección GENERAL ---
$rows[] = [['v'=>'🛒 PRODUCTOS GENERALES','s'=>'header'],'','','','','',''];
$rows[] = $header;
foreach ($generales as $p) {
    $initial   = $p['stock'] + $p['sold'];
    $remaining = $p['stock'];
    $rows[] = [
        $p['sku'],
        $p['name'],
        'General',
        (int)$initial,
        (int)$p['sold'],
        (int)$remaining,
        $remaining <= 0 ? 'SÍ - URGENTE' : ($remaining <= 5 ? 'Sí - Poco' : 'No'),
    ];
}
// Fila de totales General
$rows[] = [
    '', 'TOTAL GENERAL', '',
    '', // stock inicial no suma con sentido
    (int)$totalVendidosGeneral,
    (int)$totalStockGeneral,
    '',
];
$rows[] = []; // fila vacía separadora

// --- Sección BAÑOS ---
$rows[] = [['v'=>'🛁 PRODUCTOS DE BAÑO','s'=>'header'],'','','','','',''];
$rows[] = $header;
foreach ($banos as $p) {
    $initial   = $p['stock'] + $p['sold'];
    $remaining = $p['stock'];
    $rows[] = [
        $p['sku'],
        $p['name'],
        'Baño',
        (int)$initial,
        (int)$p['sold'],
        (int)$remaining,
        $remaining <= 0 ? 'SÍ - URGENTE' : ($remaining <= 5 ? 'Sí - Poco' : 'No'),
    ];
}
// Fila de totales Baños
$rows[] = [
    '', 'TOTAL BAÑOS', '',
    '',
    (int)$totalVendidosBano,
    (int)$totalStockBano,
    '',
];
$rows[] = [];

// --- Resumen global ---
$rows[] = [['v'=>'📊 RESUMEN GLOBAL','s'=>'header'],'','','','','',''];
$rows[] = ['', 'Unidades vendidas (General)',  '', '', (int)$totalVendidosGeneral, '', ''];
$rows[] = ['', 'Unidades vendidas (Baños)',    '', '', (int)$totalVendidosBano,   '', ''];
$rows[] = ['', 'TOTAL VENDIDOS',               '', '', (int)($totalVendidosGeneral + $totalVendidosBano), '', ''];
$rows[] = ['', 'Baños realizados (servicios)', '', '', (int)$bathCount, '', ''];

$w->addSheet('Inventario', $rows, [12, 28, 12, 14, 12, 14, 14]);

// ── Hoja 2: Ventas diarias ────────────────────────────────────────────────────
$rows2 = [['Fecha', 'Unidades vendidas']];
foreach ($dailySales as $d) {
    $rows2[] = [$d['day'], (int)$d['total']];
}
$w->addSheet('Ventas Diarias', $rows2, [15, 20]);

// ── Hoja 3: Baños diarios ─────────────────────────────────────────────────────
$rows3 = [['Fecha', 'Baños realizados']];
foreach ($dailyBaths as $b) {
    $rows3[] = [$b['day'], (int)$b['baths']];
}
$w->addSheet('Baños Diarios', $rows3, [15, 20]);

// Guardar en /tmp y enviar al navegador
$tmpFile  = '/tmp/reporte_peluditos_' . date('Ymd_His') . '.xlsx';
$filename = 'reporte_peluditos_' . date('Ymd_His') . '.xlsx';

$w->save($tmpFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache');
readfile($tmpFile);
@unlink($tmpFile);
exit;
