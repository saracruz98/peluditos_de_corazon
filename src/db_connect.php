<?php
// src/db_connect.php
function getPDO(): PDO {
    // Allow overriding DB path via env var (e.g., in production)
    $path = getenv('DATABASE_PATH');
    if ($path === false || $path === '') {
        // Use a temporary directory that is always writable (Windows/Linux)
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'petstore.db';
    }
    // Ensure directory exists (for sys_get_temp_dir it's usually fine)
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable write-ahead logging for better concurrency
    $pdo->exec('PRAGMA journal_mode=WAL');
    return $pdo;
}
?>