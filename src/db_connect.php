<?php
// src/db_connect.php
function getPDO(): PDO {
    $url = getenv('DATABASE_URL');
    if ($url) {
        $parts  = parse_url($url);
        $host   = $parts['host'];
        $port   = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user   = $parts['user'];
        $pass   = $parts['pass'];
        $dsn    = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        $pdo    = new PDO($dsn, $user, $pass);
    } else {
        $path = getenv('DATABASE_PATH') ?: '/tmp/petstore.db';
        $dir  = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (file_exists($path) && !is_writable($path)) @chmod($path, 0664);
        $pdo = new PDO("sqlite:$path");
        $pdo->exec('PRAGMA journal_mode=WAL');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function initDB(PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            sku TEXT NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'general',
            stock INTEGER NOT NULL DEFAULT 0,
            purchase_price NUMERIC(10,2) NOT NULL DEFAULT 0,
            sale_price NUMERIC(10,2) NOT NULL DEFAULT 0
        )");
        // Eliminar UNIQUE en sku si existe
        @$pdo->exec("DO \$\$ BEGIN
            IF EXISTS (
                SELECT 1 FROM pg_constraint
                WHERE conname = 'products_sku_key' AND conrelid = 'products'::regclass
            ) THEN ALTER TABLE products DROP CONSTRAINT products_sku_key; END IF;
        END \$\$;");
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
            unit_price NUMERIC(12,2) NOT NULL DEFAULT 0,
            purchased_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
        // Migración: agregar unit_price si no existe
        @$pdo->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS unit_price NUMERIC(12,2) NOT NULL DEFAULT 0");
        // Tabla de registros de baños con raza
        $pdo->exec("CREATE TABLE IF NOT EXISTS bath_records (
            id SERIAL PRIMARY KEY,
            breed TEXT NOT NULL,
            qty INTEGER NOT NULL DEFAULT 1,
            price NUMERIC(10,2) NOT NULL DEFAULT 0,
            recorded_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
    } else {
        // SQLite
        $hasUnique = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND tbl_name='products' AND sql LIKE '%UNIQUE%'")->fetchColumn();
        if ($hasUnique > 0) {
            $pdo->exec("ALTER TABLE products RENAME TO products_old");
            $pdo->exec("CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sku TEXT NOT NULL, name TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT 'general',
                stock INTEGER NOT NULL DEFAULT 0,
                purchase_price REAL NOT NULL DEFAULT 0,
                sale_price REAL NOT NULL DEFAULT 0
            )");
            $pdo->exec("INSERT INTO products SELECT * FROM products_old");
            $pdo->exec("DROP TABLE products_old");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sku TEXT NOT NULL, name TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT 'general',
                stock INTEGER NOT NULL DEFAULT 0,
                purchase_price REAL NOT NULL DEFAULT 0,
                sale_price REAL NOT NULL DEFAULT 0
            )");
        }
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
            unit_price REAL NOT NULL DEFAULT 0,
            purchased_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(product_id) REFERENCES products(id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS bath_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            breed TEXT NOT NULL,
            qty INTEGER NOT NULL DEFAULT 1,
            price REAL NOT NULL DEFAULT 0,
            recorded_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        // Migración: agregar unit_price a purchases si no existe (SQLite)
        $cols = array_column($pdo->query("PRAGMA table_info(purchases)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('unit_price', $cols)) {
            $pdo->exec("ALTER TABLE purchases ADD COLUMN unit_price REAL NOT NULL DEFAULT 0");
        }
    }
}
?>
