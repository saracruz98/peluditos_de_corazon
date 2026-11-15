<?php
// import_products.php – script para cargar productos desde un CSV de ejemplo
// Colócalo en la raíz del proyecto y ejecútalo una sola vez (git add/commit) o vía CLI.
// Formato CSV: SKU,Nombre,Categoría (baño|general),Stock,PrecioCompra,PrecioVenta

require_once __DIR__ . '/src/db_connect.php';

$pdo = getPDO();

$csvPath = __DIR__ . '/products_seed.csv';
if (!file_exists($csvPath)) {
    echo "⚠️ No se encontró $csvPath. Crea el archivo CSV y vuelve a ejecutar.\n";
    exit(1);
}

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    echo "⚠️ No se pudo abrir $csvPath\n";
    exit(1);
}

$insert = $pdo->prepare('INSERT OR IGNORE INTO products (sku, name, category, stock, purchase_price, sale_price) VALUES (:sku, :name, :cat, :stk, :pp, :sp)');
$row = 0;
while (($data = fgetcsv($handle, 1000, ",")) !== false) {
    $row++;
    // Saltar encabezado
    if ($row === 1 && strtolower($data[0]) === 'sku') {
        continue;
    }
    [$sku, $name, $category, $stock, $purchase, $sale] = $data;
    $category = strtolower(trim($category)) === 'baño' ? 'baño' : 'general';
    $insert->execute([
        ':sku' => trim($sku),
        ':name' => trim($name),
        ':cat' => $category,
        ':stk' => (int) $stock,
        ':pp' => (float) $purchase,
        ':sp' => (float) $sale,
    ]);
}
fclose($handle);

echo "✅ Importación completada.\n";
?>