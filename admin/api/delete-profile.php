<?php
// admin/api/delete-profile.php - Secure AJAX endpoint for Admin-confirmed profile deletion
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verify authentication and role is strict admin
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Administrator privileges required.']);
    exit();
}

// Validate CSRF token
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || $csrf !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Security validation token failed (CSRF).']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$password = $_POST['password'] ?? '';

if ($id <= 0 || !in_array($type, ['athlete', 'official']) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required profile identifiers or confirmation password.']);
    exit();
}

try {
    // Retrieve currently logged in administrator details
    $adminId = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect administrator password. Deletion rejected.']);
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    if ($type === 'athlete') {
        // Fetch athlete name for audit logging
        $nameStmt = $pdo->prepare("SELECT full_name, regn_no FROM athletes WHERE id = ?");
        $nameStmt->execute([$id]);
        $profile = $nameStmt->fetch();
        if (!$profile) {
            throw new Exception("Athlete profile not found.");
        }

        // Soft delete the profile
        $del = $pdo->prepare("UPDATE athletes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $del->execute([$id]);

        // Audit Log
        $logDetails = "Deleted Athlete: " . $profile['full_name'] . " (Reg No: " . $profile['regn_no'] . ")";
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'DELETE_ATHLETE', ?)");
        $logStmt->execute([$adminId, $logDetails]);

    } else {
        // Fetch official details for audit logging
        $nameStmt = $pdo->prepare("SELECT name, official_reg_no FROM officials WHERE id = ?");
        $nameStmt->execute([$id]);
        $profile = $nameStmt->fetch();
        if (!$profile) {
            throw new Exception("Official profile not found.");
        }

        // Soft delete the profile
        $del = $pdo->prepare("UPDATE officials SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $del->execute([$id]);

        // Audit Log
        $logDetails = "Deleted Official: " . $profile['name'] . " (Reg No: " . $profile['official_reg_no'] . ")";
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'DELETE_OFFICIAL', ?)");
        $logStmt->execute([$adminId, $logDetails]);
    }

    $pdo->commit();
    echo json_encode(['success' => 'Profile deleted successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage()]);
}
