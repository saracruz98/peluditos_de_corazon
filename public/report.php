<?php
require_once __DIR__ . '/partials/header.php';
$title = 'Reporte CSV';
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/inventory.php';

$pdo = getPDO();

if (isset($_GET['download'])) {
    // generar CSV y enviarlo
    $sales = getSalesSummary($pdo);
    $bathCount = getBathCount($pdo);
    $filename = 'reporte_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['SKU','Producto','Categoría','Stock inicial','Vendidos','Stock restante','Reordenar?']);
    foreach ($sales as $p) {
        $initial = $p['stock'] + $p['sold'];
        $remaining = $p['stock'];
        $reorder = $remaining <= 0 ? 'Sí' : 'No';
        fputcsv($out, [$p['sku'],$p['name'],ucfirst($p['category']),$initial,$p['sold'],$remaining,$reorder]);
    }
    // línea extra con total de baños
    fputcsv($out, []);
    fputcsv($out, ['Total baños realizados',$bathCount]);
    fclose($out);
    exit;
}
?>
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <i class="bi bi-file-earmark-csv"></i> Reporte CSV
    </div>
    <div class="card-body">
        <p>Descarga el reporte con el resumen de productos y el total de baños realizados.</p>
        <a href="report.php?download=1" class="btn btn-primary"><i class="bi bi-download"></i> Descargar CSV</a>
        <a href="manage_products.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>
<?php
require_once __DIR__ . '/partials/footer.php';
?>