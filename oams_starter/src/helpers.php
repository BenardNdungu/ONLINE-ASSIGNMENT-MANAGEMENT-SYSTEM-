<?php
// src/helpers.php
// Small utility helpers used across the app.

/**
 * Flash messaging helper.
 * Usage:
 *   flash('success', 'Saved successfully'); // set
 *   $msg = flash('success'); // get (consumes the message)
 */
function flash(string $key, ?string $message = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($message === null) {
        if (!empty($_SESSION['flash'][$key])) {
            $val = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $val;
        }
        return null;
    }

    // set
    $_SESSION['flash'][$key] = $message;
    return null;
}

?>
