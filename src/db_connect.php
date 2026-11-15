<?php
// src/db_connect.php
// Siempre usa /tmp/petstore.db que Apache puede escribir en Linux.
// Si defines DATABASE_PATH en el entorno, usa ese valor.
function getPDO(): PDO {
    $path = getenv('DATABASE_PATH');
    if (!$path) {
        $path = '/tmp/petstore.db';
    }
    // Crear directorio si no existe (para rutas personalizadas)
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');
    return $pdo;
}

function initDB(PDO $pdo): void {
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
}
