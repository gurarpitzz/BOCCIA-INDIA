<?php
// api/send-otp.php - AJAX endpoint to send verification OTP via Resend

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

// Parse request parameters
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($_POST['email'] ?? $input['email'] ?? '');
$action = trim($_POST['action'] ?? $input['action'] ?? '');
$captcha_token = trim($_POST['captcha_token'] ?? $input['captcha_token'] ?? $input['h-captcha-response'] ?? $_POST['h-captcha-response'] ?? '');
$website_url = trim($_POST['website_url'] ?? $input['website_url'] ?? '');
$csrf_token = trim($_POST['csrf_token'] ?? $input['csrf_token'] ?? '');

// 1. Basic email validation
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid email address is required.']);
    exit();
}

// 2. CSRF validation
if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session has expired due to inactivity. Please refresh the page and try again.']);
    exit();
}

// 3. Honeypot check (Fails with HTTP 200, but doesn't send email)
if (!empty($website_url)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'If eligible, a verification code has been sent.']);
    exit();
}

// 4. Action allowlist validation
$allowedActions = ['register_player', 'register_official', 'update_profile', 'event_registration'];
if (empty($action) || !in_array($action, $allowedActions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action context.']);
    exit();
}

// Helper to verify hCaptcha server-side (using form-urlencoded payload)
function verifyHCaptcha($token, $ip) {
    $postData = http_build_query([
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $ip,
        'sitekey' => HCAPTCHA_SITE_KEY
    ]);
    
    $ch = curl_init('https://api.hcaptcha.com/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT        => 10
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        return false; // Fail closed
    }
    
    $data = json_decode($response, true);
    return isset($data['success']) && $data['success'] === true;
}

// Get Client IP securely (no X-Forwarded-For trusting unless configured)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Hash email and IP
$emailHash = hash_hmac('sha256', strtolower($email), OTP_SECRET);
$ipHash = hash_hmac('sha256', $clientIp, OTP_SECRET);

// Helper to check and increment rate limit atomically
function checkAndUpdateLimit($pdo, $hash, $type, $limit15m, $limit24h) {
    $now = date('Y-m-d H:i:s');
    
    // Check 15m limit
    $stmt = $pdo->prepare("SELECT id, request_count, window_started_at FROM otp_rate_limits WHERE identifier_hash = ? AND identifier_type = ? AND window_type = '15min' FOR UPDATE");
    $stmt->execute([$hash, $type]);
    $limitInfo = $stmt->fetch();
    
    if ($limitInfo) {
        $windowStart = strtotime($limitInfo['window_started_at']);
        if (time() - $windowStart < 900) { // inside 15m window
            if ($limitInfo['request_count'] >= $limit15m) {
                return false;
            }
            $upd = $pdo->prepare("UPDATE otp_rate_limits SET request_count = request_count + 1, last_request_at = ? WHERE id = ?");
            $upd->execute([$now, $limitInfo['id']]);
        } else {
            $upd = $pdo->prepare("UPDATE otp_rate_limits SET request_count = 1, window_started_at = ?, last_request_at = ? WHERE id = ?");
            $upd->execute([$now, $now, $limitInfo['id']]);
        }
    } else {
        $ins = $pdo->prepare("INSERT INTO otp_rate_limits (identifier_hash, identifier_type, window_type, request_count, window_started_at, last_request_at) VALUES (?, ?, '15min', 1, ?, ?)");
        $ins->execute([$hash, $type, $now, $now]);
    }
    
    // Check 24h limit
    $stmt = $pdo->prepare("SELECT id, request_count, window_started_at FROM otp_rate_limits WHERE identifier_hash = ? AND identifier_type = ? AND window_type = '24hr' FOR UPDATE");
    $stmt->execute([$hash, $type]);
    $limitInfo = $stmt->fetch();
    
    if ($limitInfo) {
        $windowStart = strtotime($limitInfo['window_started_at']);
        if (time() - $windowStart < 86400) { // inside 24h window
            if ($limitInfo['request_count'] >= $limit24h) {
                return false;
            }
            $upd = $pdo->prepare("UPDATE otp_rate_limits SET request_count = request_count + 1, last_request_at = ? WHERE id = ?");
            $upd->execute([$now, $limitInfo['id']]);
        } else {
            $upd = $pdo->prepare("UPDATE otp_rate_limits SET request_count = 1, window_started_at = ?, last_request_at = ? WHERE id = ?");
            $upd->execute([$now, $now, $limitInfo['id']]);
        }
    } else {
        $ins = $pdo->prepare("INSERT INTO otp_rate_limits (identifier_hash, identifier_type, window_type, request_count, window_started_at, last_request_at) VALUES (?, ?, '24hr', 1, ?, ?)");
        $ins->execute([$hash, $type, $now, $now]);
    }
    
    return true;
}

try {
    // Start transaction to enforce atomic checks
    $pdo->beginTransaction();
    
    // A. 60-second cooldown check
    $stmt = $pdo->prepare("SELECT created_at FROM email_otps WHERE email_hash = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$emailHash]);
    $lastOtp = $stmt->fetch();
    if ($lastOtp) {
        $elapsed = time() - strtotime($lastOtp['created_at']);
        if ($elapsed < 60) {
            $pdo->rollBack();
            http_response_code(429);
            echo json_encode(['error' => 'Please wait 60 seconds before requesting another code.']);
            exit();
        }
    }
    
    // B. IP and Email rate limits
    if (!checkAndUpdateLimit($pdo, $ipHash, 'ip', 5, 30)) {
        $pdo->rollBack();
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests from your connection. Please try again later.']);
        exit();
    }
    if (!checkAndUpdateLimit($pdo, $emailHash, 'email', 3, 10)) {
        $pdo->rollBack();
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests for this email. Please try again later.']);
        exit();
    }
    
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
    exit();
}

// 5. Verify hCaptcha (Fail closed if fails or is unavailable)
if (empty($captcha_token) || !verifyHCaptcha($captcha_token, $clientIp)) {
    http_response_code(403);
    echo json_encode(['error' => 'hCaptcha verification failed. Please try again.']);
    exit();
}

// 6. Context-specific checks (without leaking account existence)
try {
    if ($action === 'register_player') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'This email address is already registered to a player.']);
            exit();
        }
    } elseif ($action === 'register_official') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM officials WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'This email address is already registered to an official.']);
            exit();
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
    exit();
}

// 7. Generate cryptographically secure OTP
try {
    $otpCode = (string)random_int(100000, 999999);
    $otpHash = hash_hmac('sha256', $otpCode, OTP_SECRET);
    
    $pdo->beginTransaction();
    
    // Invalidate previous OTPs for this action/email
    $upd = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE email_hash = ? AND action = ? AND status = 'sent'");
    $upd->execute([$emailHash, $action]);
    
    // Save hashed OTP as 'pending'
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes validity
    $stmt = $pdo->prepare("INSERT INTO email_otps (email_hash, otp_hash, action, expires_at, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$emailHash, $otpHash, $action, $expiresAt]);
    $otpId = $pdo->lastInsertId();
    
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process request.']);
    exit();
}

// 8. Call Resend API (accepted for processing is sent status)
$htmlBody = "
  <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
    <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
    <p>Hello,</p>
    <p>Your 6-digit OTP verification code is:</p>
    <div style=\"background: #f1f5f9; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #FF9933; margin: 20px 0; border-radius: 6px;\">
      {$otpCode}
    </div>
    <p style=\"color: #64748b; font-size: 14px;\">This code is valid for 10 minutes and can only be used once. Please do not share this code.</p>
  </div>
";

// Generate unique idempotency key
$idempotencyKey = hash('sha256', $emailHash . $action . $otpId);

$sent = sendEmail(
    $email,
    'Your OTP Verification Code - BSFI',
    $htmlBody,
    null,
    true, // $skipDedupe
    $idempotencyKey
);

if ($sent) {
    $upd = $pdo->prepare("UPDATE email_otps SET status = 'sent' WHERE id = ?");
    $upd->execute([$otpId]);
    
    echo json_encode(['success' => true, 'message' => 'If eligible, a verification code has been sent.']);
} else {
    $upd = $pdo->prepare("UPDATE email_otps SET status = 'failed' WHERE id = ?");
    $upd->execute([$otpId]);
    
    http_response_code(502);
    echo json_encode(['error' => 'Unable to send verification email right now. Please try again later.']);
}
