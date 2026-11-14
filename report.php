<?php
// report.php – genera un reporte en CSV (compatible con Excel)
require_once __DIR__ . '/inventory.php';

$pdo = new PDO('sqlite:' . __DIR__ . '/petstore.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sales = getSalesSummary($pdo);
$bathCount = getBathCount($pdo);

$filename = 'reporte_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
// Cabeceras
fputcsv($out, ['SKU', 'Producto', 'Categoría', 'Stock inicial', 'Vendidos', 'Stock restante', 'Reordenar?']);

foreach ($sales as $p) {
    $initial = $p['stock'] + $p['sold']; // stock antes de ventas
    $remaining = $p['stock'];
    $reorder = $remaining <= 0 ? 'Sí' : 'No';
    fputcsv($out, [
        $p['sku'],
        $p['name'],
        ucfirst($p['category']),
        $initial,
        $p['sold'],
        $remaining,
        $reorder,
    ]);
}

// Línea extra con total de baños
fputcsv($out, []);
fputcsv($out, ['Total baños realizados', $bathCount]);

fclose($out);
?>