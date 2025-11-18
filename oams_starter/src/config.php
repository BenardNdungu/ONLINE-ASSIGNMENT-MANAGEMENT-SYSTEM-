<?php
// src/config.php
// Application configuration - EDIT for your environment.
// Keep credentials outside version control in production.

define('DB_HOST','localhost');
define('DB_NAME','oams');
define('DB_USER','root');
define('DB_PASS','');

// Directory for uploaded files (ensure writable)
define('UPLOAD_DIR', __DIR__ . '/assets/uploads/');

// Base URL (adjust if deployed in a subfolder)
define('BASE_URL','/');

// Improve session cookie security where possible
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load common helpers (defines flash() and others)
require_once __DIR__ . '/helpers.php';
?>