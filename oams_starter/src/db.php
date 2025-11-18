<?php
// src/db.php
// PDO database connection with recommended options.
// Uses constants defined in src/config.php

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Don't leak sensitive info in production; log instead.
    die("DB Connection failed: " . $e->getMessage());
}
?>
