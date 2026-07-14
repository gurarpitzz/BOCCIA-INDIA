<?php
// api/send-otp.php - AJAX endpoint to send verification OTP via Resend

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

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid email address is required.']);
    exit();
}

// Get Client IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    // 1. Clean up expired OTPs older than 24 hours
    $pdo->query("DELETE FROM email_otps WHERE expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

    // 2. Rate limiting check - Email (Max 5 per hour)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_otps WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() >= 5) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many OTP requests for this email. Please try again in an hour.']);
        exit();
    }

    // 3. Rate limiting check - IP (Max 20 per hour)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_otps WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() >= 20) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many OTP requests from this connection. Please try again in an hour.']);
        exit();
    }

    // 4. Generate 6-digit OTP code
    $otpCode = (string)random_int(100000, 999999);
    $otpHash = hash_hmac('sha256', $otpCode, OTP_SECRET);

    // 5. Delete existing active OTPs for this email to ensure only one valid OTP exists
    $stmt = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
    $stmt->execute([$email]);

    // 6. Save hashed OTP
    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes validity
    $stmt = $pdo->prepare("INSERT INTO email_otps (email, otp_hash, expires_at, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $otpHash, $expiresAt, $ip]);

    // 7. Dispatch via Resend HTTP Post
    $htmlBody = "
      <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
        <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
        <p>Hello,</p>
        <p>Your 6-digit OTP verification code for registration is:</p>
        <div style=\"background: #f1f5f9; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #FF9933; margin: 20px 0; border-radius: 6px;\">
          {$otpCode}
        </div>
        <p style=\"color: #64748b; font-size: 14px;\">This code is valid for 5 minutes and can only be used once. Please do not share this code with anyone.</p>
      </div>
    ";

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'from' => 'Boccia India <noreply@bocciaindia.com>',
        'to' => $email,
        'subject' => 'Your OTP Verification Code - BSFI',
        'html' => $htmlBody
    ]));
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        // Log action to activity_logs
        $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
        $log->execute(['Email OTP Sent', "OTP code sent to email: {$email}"]);

        echo json_encode(['success' => 'OTP sent successfully.']);
    } else {
        // Log failure details
        $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
        $log->execute(['Email OTP Failed', "HTTP Code: {$httpCode}, Response: {$result}"]);
        
        http_response_code(500);
        $resObj = json_decode($result, true);
        $detail = $resObj['message'] ?? $result;
        echo json_encode(['error' => "Failed to dispatch email (Resend API Error: {$detail}). Please verify domain registration or try again."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
