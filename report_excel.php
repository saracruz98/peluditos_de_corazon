<?php
// report_excel.php – genera reporte en formato XLSX usando SimpleXLSXWriter (clase incluida en xlsx.php)
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/xlsx.php'; // SimpleXLSXWriter class

$pdo = new PDO('sqlite:' . __DIR__ . '/petstore.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sales = getSalesSummary($pdo);
$bathCount = getBathCount($pdo);

$filename = 'reporte_' . date('Ymd_His') . '.xlsx';

// Preparar filas: primera fila = encabezado
$rows = [];
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
// Añadir una fila vacía y la fila de total de baños
$rows[] = [];
$rows[] = ['Total baños realizados', $bathCount];

$writer = new SimpleXLSXWriter();
$writer->addSheet('Reporte', $rows);
$writer->save($filename);

// Enviar como descarga si se accede vía navegador
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    readfile($filename);
    // Opcional: eliminar archivo temporal después de enviar
    // unlink($filename);
    exit;
}

echo "✅ Reporte Excel creado: $filename\n";
?>