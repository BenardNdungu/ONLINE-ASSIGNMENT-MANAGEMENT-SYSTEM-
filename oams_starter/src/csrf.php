<?php
// src/csrf.php
// CSRF protection utilities.
// Start session if not started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure a CSRF token exists for the session and return it.
 * Uses random_bytes for cryptographic randomness.
 * @return string
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a supplied CSRF token against the session token.
 * Uses hash_equals to prevent timing attacks.
 * @param string|null $token
 * @return bool
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to emit hidden input for forms
 */
function csrfInputField() {
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="'. $token .'">';
}
?>