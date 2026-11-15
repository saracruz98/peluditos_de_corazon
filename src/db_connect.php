<?php
// src/db_connect.php
// Soporta PostgreSQL (Render free DB) via DATABASE_URL
// y SQLite como fallback local.

function getPDO(): PDO {
    $url = getenv('DATABASE_URL');

    if ($url) {
        // Render proporciona DATABASE_URL en formato:
        // postgresql://user:password@host:port/dbname
        $parts = parse_url($url);
        $host   = $parts['host'];
        $port   = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user   = $parts['user'];
        $pass   = $parts['pass'];

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        $pdo = new PDO($dsn, $user, $pass);
    } else {
        // Fallback: SQLite local (para desarrollo o si no hay DATABASE_URL)
        $path = getenv('DATABASE_PATH') ?: '/tmp/petstore.db';
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $pdo = new PDO("sqlite:$path");
        $pdo->exec('PRAGMA journal_mode=WAL');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function initDB(PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        // PostgreSQL
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            sku TEXT NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'general',
            stock INTEGER NOT NULL DEFAULT 0,
            purchase_price NUMERIC(10,2) NOT NULL DEFAULT 0,
            sale_price NUMERIC(10,2) NOT NULL DEFAULT 0
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
            id SERIAL PRIMARY KEY,
            product_id INTEGER NOT NULL REFERENCES products(id),
            qty INTEGER NOT NULL,
            sold_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
            id SERIAL PRIMARY KEY,
            product_id INTEGER NOT NULL REFERENCES products(id),
            qty INTEGER NOT NULL,
            purchased_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
    } else {
        // SQLite
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku TEXT NOT NULL,
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
}
?>
