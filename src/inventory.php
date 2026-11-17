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

/**
 * Resumen financiero por producto:
 * total_comprado   = unidades totales compradas (en purchases) + stock inicial del producto
 * total_invertido  = suma de (qty * unit_price) en cada compra + precio inicial si aplica
 * total_vendido    = unidades vendidas
 * ingreso_ventas   = suma de ventas * precio_venta del producto
 * ganancia         = ingreso_ventas - total_invertido proporcional
 */
function getFinancialSummary(PDO $pdo): array {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';

    return $pdo->query("
        SELECT
            p.id,
            p.sku,
            p.name,
            p.category,
            p.stock                                           AS stock_actual,
            p.sale_price                                      AS precio_venta,
            p.purchase_price                                  AS precio_compra_ref,
            $ifnull(SUM(pu.qty), 0)                          AS unidades_compradas,
            $ifnull(SUM(pu.qty * pu.unit_price), 0)          AS total_invertido,
            $ifnull(sv.unidades_vendidas, 0)                  AS unidades_vendidas,
            $ifnull(sv.unidades_vendidas, 0) * p.sale_price   AS ingreso_ventas
        FROM products p
        LEFT JOIN purchases pu ON pu.product_id = p.id
        LEFT JOIN (
            SELECT product_id, SUM(qty) AS unidades_vendidas
            FROM sales
            GROUP BY product_id
        ) sv ON sv.product_id = p.id
        WHERE p.category = 'general'
        GROUP BY p.id, p.sku, p.name, p.category, p.stock, p.sale_price, p.purchase_price, sv.unidades_vendidas
        ORDER BY p.name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/** Historial de compras de un producto específico */
function getPurchaseHistory(PDO $pdo, int $productId): array {
    $s = $pdo->prepare("
        SELECT qty, unit_price, (qty * unit_price) AS subtotal, purchased_at
        FROM purchases
        WHERE product_id = :pid
        ORDER BY purchased_at DESC
    ");
    $s->execute([':pid' => $productId]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

function getPurchaseHistoryAll(PDO $pdo): array {
    return $pdo->query("
        SELECT pu.purchased_at, p.name, pu.qty, pu.unit_price, (pu.qty * pu.unit_price) AS subtotal
        FROM purchases pu
        JOIN products p ON p.id = pu.product_id
        ORDER BY pu.purchased_at DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getBathCount(PDO $pdo): int {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $ifnull = $driver === 'pgsql' ? 'COALESCE' : 'IFNULL';
    return (int)$pdo->query("SELECT $ifnull(SUM(qty),0) FROM bath_records")->fetchColumn();
}

function getDailySales(PDO $pdo): array {
    return $pdo->query("
        SELECT DATE(s.sold_at) AS day, SUM(s.qty) AS total
        FROM sales s GROUP BY day ORDER BY day DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyBaths(PDO $pdo): array {
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
