<?php
// src/inventory.php

function getProductList(PDO $pdo): array {
    return $pdo->query('SELECT * FROM products ORDER BY category, name')->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesSummary(PDO $pdo): array {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';
    return $pdo->query("
        SELECT p.id, p.sku, p.name, p.category,
               $ifnull(SUM(s.qty),0) AS sold,
               p.stock, p.purchase_price, p.sale_price
        FROM products p
        LEFT JOIN sales s ON s.product_id = p.id
        GROUP BY p.id, p.sku, p.name, p.category, p.stock, p.purchase_price, p.sale_price
        ORDER BY p.category, p.name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getBathCount(PDO $pdo): int {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';
    return (int)$pdo->query("SELECT $ifnull(SUM(qty),0) FROM bath_records")->fetchColumn();
}

function getDailySales(PDO $pdo): array {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $dateF  = $driver === 'pgsql' ? "DATE(s.sold_at)" : "DATE(s.sold_at)";
    return $pdo->query("
        SELECT $dateF AS day, SUM(s.qty) AS total
        FROM sales s GROUP BY day ORDER BY day DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyBaths(PDO $pdo): array {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    return $pdo->query("
        SELECT DATE(recorded_at) AS day, SUM(qty) AS baths, SUM(price*qty) AS total_price
        FROM bath_records
        GROUP BY day ORDER BY day DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getBathRecords(PDO $pdo): array {
    return $pdo->query("
        SELECT breed, qty, price, recorded_at
        FROM bath_records
        ORDER BY recorded_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>
