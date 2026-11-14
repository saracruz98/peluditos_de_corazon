<?php
// inventory.php – funciones reutilizables de inventario

function getProductList(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category, name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesSummary(PDO $pdo): array {
    $sql = "
        SELECT p.id, p.sku, p.name, p.category,
               IFNULL(SUM(s.qty),0) AS sold,
               p.stock
        FROM products p
        LEFT JOIN sales s ON s.product_id = p.id
        GROUP BY p.id
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBathCount(PDO $pdo): int {
    $sql = "
        SELECT IFNULL(SUM(s.qty),0) AS total_baths
        FROM sales s
        JOIN products p ON p.id = s.product_id
        WHERE p.category = 'baño'
    ";
    return (int)$pdo->query($sql)->fetchColumn();
}
?>