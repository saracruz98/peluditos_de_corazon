<?php
// init_db.php – crea la base SQLite con tablas de productos, ventas y compras
require_once __DIR__ . '/src/db_connect.php';
$pdo = getPDO();

// Tabla de productos (con precios y categoría)
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT NOT NULL, -- 'baño' o 'general'
    stock INTEGER NOT NULL DEFAULT 0,
    purchase_price REAL NOT NULL DEFAULT 0,
    sale_price REAL NOT NULL DEFAULT 0
);");

// Tabla de ventas (registro de cada venta)
$pdo->exec("CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    sold_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(product_id) REFERENCES products(id)
);");

// Tabla de compras (entrada de stock)
$pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    purchased_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(product_id) REFERENCES products(id)
);");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_product ON sales(product_id);");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_purchases_product ON purchases(product_id);");

echo "✅ Base de datos (con precios, ventas y compras) creada/actualizada en " . (getenv('DATABASE_PATH') ?: __DIR__ . '/petstore.db') . "\n";
?>