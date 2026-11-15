<?php
// src/inventory.php
function getProductList(PDO $pdo): array {
    return $pdo->query('SELECT * FROM products ORDER BY category, name')->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesSummary(PDO $pdo): array {
    return $pdo->query("
        SELECT p.id, p.sku, p.name, p.category,
               IFNULL(SUM(s.qty),0) AS sold,
               p.stock,
               p.purchase_price,
               p.sale_price
        FROM products p
        LEFT JOIN sales s ON s.product_id = p.id
        GROUP BY p.id
        ORDER BY p.category, p.name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getBathCount(PDO $pdo): int {
    return (int)$pdo->query("
        SELECT IFNULL(SUM(s.qty),0)
        FROM sales s
        JOIN products p ON p.id = s.product_id
        WHERE p.category = 'bano'
    ")->fetchColumn();
}

function getDailySales(PDO $pdo): array {
    return $pdo->query("
        SELECT DATE(s.sold_at) AS day, SUM(s.qty) AS total
        FROM sales s GROUP BY day ORDER BY day DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyBaths(PDO $pdo): array {
    return $pdo->query("
        SELECT DATE(s.sold_at) AS day, SUM(s.qty) AS baths
        FROM sales s
        JOIN products p ON p.id = s.product_id
        WHERE p.category = 'bano'
        GROUP BY day ORDER BY day DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
