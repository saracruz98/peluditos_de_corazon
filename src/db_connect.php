<?php
// src/db_connect.php
function getPDO(): PDO {
    $path = getenv('DATABASE_PATH');
    if ($path === false || $path === '') {
        // /tmp siempre tiene permisos de escritura en cualquier servidor Linux
        $path = '/tmp/petstore.db';
    }
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');
    return $pdo;
}
