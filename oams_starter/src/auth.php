<?php
// src/auth.php
// Authentication and authorization helpers with comments for clarity.

require_once __DIR__ . '/db.php';

/**
 * Check whether a user is currently logged in
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Return current user row (cached statically during request)
 * @return array|null
 */
function current_user() {
    global $pdo;
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = $pdo->prepare("SELECT id, role, name, email, department, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

/**
 * Require that a user is logged in. Redirects to login page if not.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /oams_starter/login.php');
        exit;
    }
}

/**
 * Require that the logged-in user has the specified role.
 * If not, produce a 403 response.
 * @param string $role
 */
function require_role($role) {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        echo 'Forbidden - insufficient privileges';
        exit;
    }
}

?>