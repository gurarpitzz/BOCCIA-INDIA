<?php
// logout.php - Destroys session and logs out user

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logAction($pdo, "User Logged Out");
}

// Clear session variables
$_SESSION = array();

// Destroy cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to home
header("Location: index.php");
exit();
