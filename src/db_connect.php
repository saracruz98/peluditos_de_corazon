<?php
// src/db_connect.php – centraliza la conexión a SQLite y permite usar un disco persistente en Render

/**
 * Returns a PDO instance pointing to the SQLite database.
 * If the environment variable DATABASE_PATH is defined (Render persistent disk),
 * that path is used; otherwise it falls back to the local file petstore.db.
 */
function getPDO(): PDO {
    $path = getenv('DATABASE_PATH');
    if ($path === false || $path === '') {
        $path = __DIR__ . '/petstore.db';
    }
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
?>