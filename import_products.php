<?php
// import_products.php – importa lista de productos desde CSV

$pdo = new PDO('sqlite:' . __DIR__ . '/petstore.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$csvPath = __DIR__ . '/products.csv';
if (!file_exists($csvPath)) {
    die("❌ No se encontró $csvPath\n");
}

$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle); // saltar encabezado

$insert = $pdo->prepare('INSERT OR REPLACE INTO products (sku, name, category, stock, unit_price) VALUES (:sku, :name, :category, :stock, :price)');
while (($row = fgetcsv($handle)) !== false) {
    [$sku, $name, $category, $stock, $price] = $row;
    $insert->execute([
        ':sku'      => $sku,
        ':name'     => $name,
        ':category' => strtolower($category) === 'baño' ? 'baño' : 'general',
        ':stock'    => (int)$stock,
        ':price'    => (float)$price,
    ]);
}
fclose($handle);

echo "✅ Productos importados desde $csvPath\n";
?>