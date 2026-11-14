<?php
// init_db.php – create SQLite DB with purchase and sale prices (simplified schema)
require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

// Tabla de productos con precios de compra y venta
$pdo->exec("CREATE TABLE IF NOT EXISTS products (\n    id INTEGER PRIMARY KEY AUTOINCREMENT,\n    sku TEXT NOT NULL UNIQUE,\n    name TEXT NOT NULL,\n    category TEXT NOT NULL, -- 'baño' o 'general'\n    stock INTEGER NOT NULL DEFAULT 0,\n    purchase_price REAL NOT NULL DEFAULT 0,\n    sale_price REAL NOT NULL DEFAULT 0\n);\n");

// Tabla de ventas (solo cantidad, se asume venta al precio de venta del producto)
$pdo->exec("CREATE TABLE IF NOT EXISTS sales (\n    id INTEGER PRIMARY KEY AUTOINCREMENT,\n    product_id INTEGER NOT NULL,\n    qty INTEGER NOT NULL,\n    sold_at TEXT NOT NULL DEFAULT (datetime('now')),\n    FOREIGN KEY(product_id) REFERENCES products(id)\n);\n");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_product ON sales(product_id);");

echo "✅ Base de datos (con precios) creada/actualizada en " . (getenv('DATABASE_PATH') ?: __DIR__ . '/petstore.db') . "\n";
?>