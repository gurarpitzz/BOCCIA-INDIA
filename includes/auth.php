<?php
// auth.php - Authentication and Session Management

function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        ini_set('session.gc_maxlifetime', 5400); // 1.5 hours
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        session_set_cookie_params([
            'lifetime' => 5400,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

init_secure_session();

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
        $nextId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM audit_logs")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO audit_logs (id, action, user_id, target_type, target_id, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nextId, $action, $userId, $target_type, $target_id, $details]);
    } catch (\PDOException $e) {
        // Fail silently so database log issues don't crash key actions like logout/login
        error_log("Audit log failed: " . $e->getMessage());
    }
}
