<?php
// src/init_db.php
require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'general',
    stock INTEGER NOT NULL DEFAULT 0,
    purchase_price REAL NOT NULL DEFAULT 0,
    sale_price REAL NOT NULL DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    sold_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(product_id) REFERENCES products(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    purchased_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(product_id) REFERENCES products(id)
)");

echo "DB OK\n";
