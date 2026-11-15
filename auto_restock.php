<?php
// auto_restock.php – agrega stock automáticamente cada 8 días
require_once __DIR__ . '/src/db_connect.php';
require_once __DIR__ . '/src/inventory.php';

$pdo = getPDO();
$pdo->beginTransaction();

// Definir cantidad a reabastecer por producto (puedes cambiar $defaultQty)
$defaultQty = 10;

// Obtener todos los productos
$stmt = $pdo->query('SELECT id, stock FROM products');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pid = $row['id'];
    // Registrar la compra
    $pdo->prepare('INSERT INTO purchases (product_id, qty) VALUES (:pid, :qty)')
        ->execute([':pid' => $pid, ':qty' => $defaultQty]);
    // Actualizar stock
    $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :pid')
        ->execute([':qty' => $defaultQty, ':pid' => $pid]);
}

$pdo->commit();
echo "✅ Reabastecimiento automático de $defaultQty unidades por producto completado.\n";
?>