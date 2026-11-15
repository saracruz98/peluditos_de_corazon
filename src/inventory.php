<?php
// src/inventory.php – funciones de inventario ampliadas

function getProductList(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category, name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesSummary(PDO $pdo): array {
    $sql = "
        SELECT p.id, p.sku, p.name, p.category,
               IFNULL(SUM(s.qty),0) AS sold,
               p.stock,
               p.purchase_price,
               p.sale_price
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

// Nuevas funciones ----------------------------------------------------------

function getPurchasesSummary(PDO $pdo): array {
    $sql = "
        SELECT p.id, p.sku, p.name, p.category,
               IFNULL(SUM(pr.qty),0) AS purchased,
               p.stock
        FROM products p
        LEFT JOIN purchases pr ON pr.product_id = p.id
        GROUP BY p.id
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDailySales(PDO $pdo): array {
    $sql = "
        SELECT DATE(s.sold_at) AS day,
               SUM(s.qty) AS total_sold
        FROM sales s
        GROUP BY day
        ORDER BY day DESC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyBaths(PDO $pdo): array {
    $sql = "
        SELECT DATE(s.sold_at) AS day,
               SUM(s.qty) AS baths_done
        FROM sales s
        JOIN products p ON p.id = s.product_id
        WHERE p.category = 'baño'
        GROUP BY day
        ORDER BY day DESC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>