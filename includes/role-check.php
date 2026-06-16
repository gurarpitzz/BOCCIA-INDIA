<?php
// role-check.php - User Access Control

require_once __DIR__ . '/auth.php';

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function checkRole($allowedRoles) {
    requireLogin();
    
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($userRole, $allowedRoles)) {
        // Access Denied
        http_response_code(403);
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied - Boccia India</title>
            <link rel='stylesheet' href='../styles.css'>
        </head>
        <body style='background:#08142E; color:#FAF7F0; display:flex; align-items:center; justify-content:center; height:100vh; font-family:sans-serif;'>
            <div style='text-align:center; padding:3rem; background:rgba(22, 41, 90, 0.6); border-radius:28px; border:1px solid rgba(255,255,255,0.1); max-width:500px;'>
                <h1 style='color:#D72638; margin-bottom:1rem;'>Access Denied</h1>
                <p style='color:#FAF7F0; margin-bottom:2rem;'>You do not have the required permissions to access this administrative resource.</p>
                <a href='dashboard.php' class='btn' style='background:#24C27A; color:#08142E; padding:0.8rem 2rem; border-radius:999px; text-decoration:none; font-weight:bold;'>Return to Dashboard</a>
            </div>
        </body>
        </html>";
        exit();
    }
}
