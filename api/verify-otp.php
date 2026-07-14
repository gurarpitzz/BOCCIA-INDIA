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

if (empty($email) || empty($otp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and OTP code are required.']);
    exit();
}

try {
    // Fetch active OTP
    $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email = ? AND verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $record = $stmt->fetch();

    if (!$record) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired OTP code.']);
        exit();
    }

    // Max 5 attempts check
    if ($record['attempts'] >= 5) {
        $del = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
        $del->execute([$email]);
        http_response_code(400);
        echo json_encode(['error' => 'Too many failed verification attempts. Please request a new OTP code.']);
        exit();
    }

    $hash = hash_hmac('sha256', $otp, OTP_SECRET);

    if ($record['otp_hash'] !== $hash) {
        // Increment attempts
        $upd = $pdo->prepare("UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?");
        $upd->execute([$record['id']]);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid OTP code. Please try again.']);
        exit();
    }

    // Success -> delete OTP records to prevent reuse
    $del = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
    $del->execute([$email]);

    // Save verified state in session
    $_SESSION['verified_email'] = $email;

    // Log action to activity_logs
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['OTP Verified', "Email successfully verified: {$email}"]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
