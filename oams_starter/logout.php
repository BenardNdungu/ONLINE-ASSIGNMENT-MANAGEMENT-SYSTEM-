<?php
// logout.php - Ends user session and redirects to login page
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page outside teacher folder
header("Location: login.php");
exit;
