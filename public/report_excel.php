<?php
// public/report_excel.php
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';
require_once __DIR__ . '/xlsx.php';

$pdo = getPDO();
initDB($pdo);

$sales      = getSalesSummary($pdo);
$financials = getFinancialSummary($pdo);
$bathCount  = getBathCount($pdo);
$dailySales = getDailySales($pdo);
$dailyBaths = getDailyBaths($pdo);
$bathRecs   = getBathRecords($pdo);

// Separar por categoría
$generales = array_filter($sales, fn($p) => $p['category'] !== 'bano');

$totalVendidosGeneral = array_sum(array_column($generales, 'sold'));
$totalStockGeneral    = array_sum(array_column($generales, 'stock'));

$w = new SimpleXLSXWriter();

// ── Hoja 1: Inventario General ───────────────────────────────────────────────
$rows = [['SKU','Producto','Stock actual','Vendidos','Reponer?']];
foreach ($generales as $p) {
    $remaining = (int)$p['stock'];
    $rows[] = [
        $p['sku'], $p['name'],
        $remaining, (int)$p['sold'],
        $remaining <= 0 ? 'SÍ - URGENTE' : ($remaining <= 5 ? 'Sí - Poco' : 'No'),
    ];
}
$rows[] = [];
$rows[] = ['', 'TOTAL', (int)$totalStockGeneral, (int)$totalVendidosGeneral, ''];
$w->addSheet('Inventario General', $rows, [12,30,14,12,16]);

// ── Hoja 2: Resumen Financiero ────────────────────────────────────────────────
$rows_fin = [['Producto','Total invertido (compras)','Unid. vendidas','Ingreso ventas','Ganancia']];
$tot_inv = 0; $tot_ing = 0; $tot_gan = 0;
foreach ($financials as $f) {
    $inv = (float)$f['total_invertido'];
    $ing = (float)$f['ingreso_ventas'];
    $gan = $ing - $inv;
    $tot_inv += $inv; $tot_ing += $ing; $tot_gan += $gan;
    $rows_fin[] = [
        $f['name'],
        (float)$inv,
        (int)$f['unidades_vendidas'],
        (float)$ing,
        (float)$gan,
    ];
}
$rows_fin[] = [];
$rows_fin[] = ['TOTAL GENERAL', (float)$tot_inv, '', (float)$tot_ing, (float)$tot_gan];
$w->addSheet('Resumen Financiero', $rows_fin, [30,22,16,18,16]);

// ── Hoja 3: Baños registrados (con raza) ─────────────────────────────────────
$rows2 = [['Fecha','Raza del perro','Cantidad','Precio unitario','Total']];
$totalBanos = 0;
$totalIngresoBanos = 0;
foreach ($bathRecs as $b) {
    $total = $b['qty'] * $b['price'];
    $totalBanos += $b['qty'];
    $totalIngresoBanos += $total;
    $rows2[] = [
        $b['recorded_at'],
        $b['breed'],
        (int)$b['qty'],
        (float)$b['price'],
        (float)$total,
    ];
}
$rows2[] = [];
$rows2[] = ['', 'TOTAL BAÑOS', (int)$totalBanos, '', (float)$totalIngresoBanos];
$w->addSheet('Registro Baños', $rows2, [20,20,12,16,14]);

// ── Hoja 4: Baños por día ─────────────────────────────────────────────────────
$rows3 = [['Fecha','Baños realizados','Ingreso total']];
foreach ($dailyBaths as $b) {
    $rows3[] = [$b['day'], (int)$b['baths'], (float)($b['total_price'] ?? 0)];
}
$w->addSheet('Baños por Día', $rows3, [15,18,16]);

// ── Hoja 5: Ventas diarias ────────────────────────────────────────────────────
$rows4 = [['Fecha','Unidades vendidas']];
foreach ($dailySales as $d) {
    $rows4[] = [$d['day'], (int)$d['total']];
}
$w->addSheet('Ventas Diarias', $rows4, [15,20]);

// Enviar archivo
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
