<?php
// api/verify-otp.php - AJAX endpoint to verify OTP code

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/app.php';

// Validate CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Security token validation failed (CSRF).']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$action = trim($_POST['action'] ?? '');

if (empty($email) || empty($otp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and OTP code are required.']);
    exit();
}

$allowedActions = ['register_player', 'register_official', 'update_profile', 'event_registration'];
if (empty($action) || !in_array($action, $allowedActions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action context.']);
    exit();
}

try {
    $emailHash = hash_hmac('sha256', strtolower($email), OTP_SECRET);

    // Fetch active 'sent' OTP for this email and action
    $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email_hash = ? AND action = ? AND status = 'sent' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$emailHash, $action]);
    $record = $stmt->fetch();

    if (!$record) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired OTP code.']);
        exit();
    }

    // Max 5 attempts check
    if ($record['attempt_count'] >= 5) {
        $upd = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE id = ?");
        $upd->execute([$record['id']]);
        http_response_code(400);
        echo json_encode(['error' => 'Too many failed verification attempts. Please request a new OTP code.']);
        exit();
    }

    $hash = hash_hmac('sha256', $otp, OTP_SECRET);

    if ($record['otp_hash'] !== $hash) {
        // Increment attempt_count
        $upd = $pdo->prepare("UPDATE email_otps SET attempt_count = attempt_count + 1 WHERE id = ?");
        $upd->execute([$record['id']]);
        
        // Re-read attempt count to check if it has reached 5
        if ($record['attempt_count'] + 1 >= 5) {
            $upd2 = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE id = ?");
            $upd2->execute([$record['id']]);
            http_response_code(400);
            echo json_encode(['error' => 'Too many failed verification attempts. Please request a new OTP code.']);
            exit();
        }
        
        http_response_code(400);
        echo json_encode(['error' => 'Invalid OTP code. Please try again.']);
        exit();
    }

    // Success -> update status to 'used' and set used_at
    $upd = $pdo->prepare("UPDATE email_otps SET status = 'used', used_at = NOW() WHERE id = ?");
    $upd->execute([$record['id']]);

    // Save verified state in session (prefix with action to prevent cross-action session hijacking)
    $_SESSION['verified_email_' . $action] = $email;
    // For legacy compat (player/official registration check)
    $_SESSION['verified_email'] = $email;

    // Log action to activity_logs
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['OTP Verified', "Email successfully verified for action {$action}: {$email}"]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
