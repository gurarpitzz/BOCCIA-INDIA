<?php
// auth.php - Authentication and Session Management

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security: Regenerate session ID periodically or on login
function regenerateUserSession() {
    session_regenerate_id(true);
}

// CSRF validation helper
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Audit logger helper
function logAction($pdo, $action, $target_type = null, $target_id = null, $details = null) {
    try {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $stmt = $pdo->prepare("INSERT INTO audit_logs (action, user_id, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$action, $userId, $target_type, $target_id, $details]);
    } catch (\PDOException $e) {
        // Fail silently so database log issues don't crash key actions like logout/login
        error_log("Audit log failed: " . $e->getMessage());
    }
}
