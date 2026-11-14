<?php
// init_db.php – create SQLite DB and tables
$dbFile = __DIR__ . '/petstore.db';
$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT NOT NULL, -- 'baño' or 'general'
    stock INTEGER NOT NULL DEFAULT 0,
    unit_price REAL NOT NULL DEFAULT 0
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    sold_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(product_id) REFERENCES products(id)
);");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_product ON sales(product_id);");

echo "✅ Base de datos creada en $dbFile\n";
?>