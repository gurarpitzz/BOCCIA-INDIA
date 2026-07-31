<?php
// admin/api/update-athlete.php - Secure AJAX endpoint for password-confirmed athlete field updates
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
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';
$password = $_POST['password'] ?? '';

if ($id <= 0 || empty($field) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required athlete identifier, update field, or confirmation password.']);
    exit();
}

// Whitelist updateable fields
$whitelistedFields = ['classification'];
if (!in_array($field, $whitelistedFields)) {
    http_response_code(400);
    echo json_encode(['error' => 'The requested field cannot be updated through this endpoint.']);
    exit();
}

// Validate value based on field
if ($field === 'classification') {
    if (!in_array($value, ['BC1', 'BC2', 'BC3', 'BC4'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Please select a valid Boccia category (BC1-BC4).']);
        exit();
    }
}

try {
    // Retrieve currently logged in administrator details
    $adminId = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect password. Verification failed.']);
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Fetch current details for audit logging
    $athStmt = $pdo->prepare("SELECT full_name, regn_no, classification FROM athletes WHERE id = ? AND deleted_at IS NULL");
    $athStmt->execute([$id]);
    $athlete = $athStmt->fetch();

    if (!$athlete) {
        throw new Exception("Athlete profile not found.");
    }

    $oldValue = $athlete[$field];

    // Check if value actually changed
    if ($oldValue === $value) {
        $pdo->rollBack();
        echo json_encode(['success' => 'Field value is already up to date.', 'no_change' => true]);
        exit();
    }

    // Perform the update
    $upStmt = $pdo->prepare("UPDATE athletes SET {$field} = ?, updated_by = ? WHERE id = ?");
    $upStmt->execute([$value, $adminId, $id]);

    // Insert history log
    $hist = $pdo->prepare("INSERT INTO athlete_status_history (athlete_id, old_status, new_status, changed_by, remarks) VALUES (?, ?, ?, ?, ?)");
    $hist->execute([$id, 'approved', 'approved', $adminId, "Classification changed from {$oldValue} to {$value}"]);

    // Write a detailed audit log
    $logActionName = "athlete_{$field}_updated";
    $logDetails = "Changed Athlete: " . $athlete['full_name'] . " (Reg No: " . $athlete['regn_no'] . ") | Field: {$field} | Old: {$oldValue} | New: {$value}";
    logAction($pdo, $logActionName, "athletes", $id, $logDetails);

    $pdo->commit();
    echo json_encode(['success' => 'Field updated successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
