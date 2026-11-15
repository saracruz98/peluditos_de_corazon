<?php
// public/report.php — descarga CSV
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();

if (isset($_GET['download'])) {
    $sales     = getSalesSummary($pdo);
    $bathCount = getBathCount($pdo);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=reporte_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['SKU','Producto','Categoría','Stock inicial','Vendidos','Stock restante','Reponer?']);
    foreach ($sales as $p) {
        $initial = $p['stock'] + $p['sold'];
        fputcsv($out, [
            $p['sku'], $p['name'],
            $p['category'] === 'bano' ? 'Baño' : 'General',
            $initial, $p['sold'], $p['stock'],
            $p['stock'] <= 0 ? 'SÍ' : 'No'
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Total baños', $bathCount]);
    fclose($out);
    exit;
}
// Si no hay ?download, redirigir con la descarga
header('Location: report.php?download=1');
exit;
