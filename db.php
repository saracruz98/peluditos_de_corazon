<?php
/**
 * PetStore - Conexión y esquema de la base de datos SQLite.
 * Sin dependencias externas: usa PDO SQLite incluido en PHP.
 */

declare(strict_types=1);

define('DB_FILE', __DIR__ . '/petstore.db');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function init_db(): void
{
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            sale_price REAL NOT NULL DEFAULT 0,
            purchase_cost REAL NOT NULL DEFAULT 0,
            initial_stock INTEGER NOT NULL DEFAULT 0,
            current_stock INTEGER NOT NULL DEFAULT 0,
            min_stock INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL DEFAULT 0,
            unit_cost REAL NOT NULL DEFAULT 0,
            total REAL NOT NULL DEFAULT 0,
            date TEXT NOT NULL,
            FOREIGN KEY (product_id) REFERENCES products(id)
        );

        CREATE TABLE IF NOT EXISTS baths (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            notes TEXT
        );

        CREATE TABLE IF NOT EXISTS bath_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bath_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            FOREIGN KEY (bath_id) REFERENCES baths(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );
    ");
}

function now_str(): string
{
    return date('Y-m-d H:i:s');
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $msg, int $code = 400): void
{
    json_out(['error' => $msg], $code);
}

function read_input(): array
{
    $raw = file_get_contents('php://input');
    $data = $raw !== false && $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = $_POST;
    }
    return is_array($data) ? $data : [];
}
