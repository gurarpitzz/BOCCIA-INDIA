<?php
// get-involved/update-profile.php - Unified Profile Update Request Portal
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF on all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Your session has expired due to inactivity. Please refresh the page and try submitting again.");
    }
}

// Helper to verify hCaptcha server-side
function verifyHCaptchaLocal($token, $ip) {
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
        return false;
    }
    
    $data = json_decode($response, true);
    return isset($data['success']) && $data['success'] === true;
}

// Helper to check rate limits atomically
function checkLimitLocal($pdo, $hash, $type, $limit15m, $limit24h) {
    $now = date('Y-m-d H:i:s');
    
    // Check 15m limit
    $stmt = $pdo->prepare("SELECT id, request_count, window_started_at FROM otp_rate_limits WHERE identifier_hash = ? AND identifier_type = ? AND window_type = '15min' FOR UPDATE");
    $stmt->execute([$hash, $type]);
    $limitInfo = $stmt->fetch();
    
    if ($limitInfo) {
        $windowStart = strtotime($limitInfo['window_started_at']);
        if (time() - $windowStart < 900) {
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
        if (time() - $windowStart < 86400) {
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

$message = '';
$error = '';
$step = 1; // 1: Lookup, 2: OTP Verification, 3: Form Update, 4: Success

$member_type = isset($_POST['member_type']) ? $_POST['member_type'] : (isset($_GET['type']) ? $_GET['type'] : 'athlete');
$member_id_input = isset($_POST['member_id_input']) ? trim($_POST['member_id_input']) : (isset($_GET['id']) ? trim($_GET['id']) : '');
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
$lookup_email = isset($_POST['lookup_email']) ? strtolower(trim($_POST['lookup_email'])) : '';
$father_name_input = isset($_POST['father_name_input']) ? trim($_POST['father_name_input']) : '';

$matched_id = isset($_POST['matched_id']) ? (int)$_POST['matched_id'] : 0;
$matched_email = isset($_POST['matched_email']) ? trim($_POST['matched_email']) : '';
$otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';

$mask_contact = '';
$needs_otp = false;

// Handle Lookup Step 1
if (isset($_POST['lookup'])) {
    if (empty($member_id_input) || empty($dob) || empty($lookup_email)) {
        $error = "Please fill in all identity lookup fields including your registered email.";
    } else {
        try {
            $matched = null;
            if ($member_type === 'official') {
                $stmt = $pdo->prepare("SELECT * FROM officials WHERE (official_reg_no = ? OR id = ?) AND dob = ? AND status = 'approved' AND deleted_at IS NULL");
                $stmt->execute([$member_id_input, $member_id_input, $dob]);
                $matched = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $lookupReg = $member_id_input;
                if (is_numeric($lookupReg)) {
                    $lookupReg = str_pad($lookupReg, 4, '0', STR_PAD_LEFT);
                }
                $stmt = $pdo->prepare("SELECT * FROM athletes WHERE (regn_no = ? OR id = ?) AND dob = ? AND status = 'approved' AND deleted_at IS NULL");
                $stmt->execute([$lookupReg, $lookupReg, $dob]);
                $matched = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($matched) {
                $email = trim($matched['email'] ?? '');
                
                // If no email exists on the legacy profile, we must verify the Father's Name is correct to prevent unauthorized claims
                if (empty($email)) {
                    if (empty($father_name_input)) {
                        $error = "Father's Name is required to claim a profile that does not have a registered email address.";
                    } else {
                        $dbFatherName = strtolower(preg_replace('/\s+/', '', $matched['father_name'] ?? ''));
                        $inputFatherName = strtolower(preg_replace('/\s+/', '', $father_name_input));
                        if ($dbFatherName !== $inputFatherName) {
                            $error = "The entered Father's Name does not match our records for this profile.";
                        }
                    }
                }

                if (empty($error)) {
                    // Email claim logic: if no email exists on the legacy profile, treat the entered email as the claimed email
                    if (empty($email)) {
                    // Check if lookup email is already registered to another athlete
                    $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE email = ? AND id != ? AND deleted_at IS NULL");
                    $dupCheck->execute([$lookup_email, $matched['id']]);
                    if ($dupCheck->fetchColumn() > 0) {
                        $error = "This email is already associated with another active player.";
                    }
                    
                    // Check if lookup email is registered to any official
                    if (empty($error)) {
                        $dupCheckOff = $pdo->prepare("SELECT COUNT(*) FROM officials WHERE email = ? AND deleted_at IS NULL");
                        $dupCheckOff->execute([$lookup_email]);
                        if ($dupCheckOff->fetchColumn() > 0) {
                            $error = "This email is registered to a federation official. Players cannot use official emails.";
                        }
                    }
                    
                    if (empty($error)) {
                        $email = $lookup_email;
                    }
                }

                // Honeypot check
                $website_url = trim($_POST['website_url'] ?? '');
                if (!empty($website_url)) {
                    $needs_otp = true;
                    $step = 2;
                    $mask_contact = 'Email: ' . substr($lookup_email, 0, 2) . '******' . strstr($lookup_email, '@');
                    $matched_email = $lookup_email;
                    // Do not actually run verification or send email
                } else {
                    $email = trim($matched['email'] ?? '');
                    if (empty($email)) {
                        $email = $lookup_email;
                    }
                    
                    if (strtolower($email) !== $lookup_email) {
                        $error = "The entered email address does not match our records for this profile.";
                    } else {
                        // 1. Verify hCaptcha (Fail closed)
                        $captcha_token = trim($_POST['h-captcha-response'] ?? '');
                        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        
                        if (empty($captcha_token) || !verifyHCaptchaLocal($captcha_token, $clientIp)) {
                            $error = "hCaptcha verification failed. Please try again.";
                        } else {
                            // 2. Persistent rate limits
                            $emailHash = hash_hmac('sha256', strtolower($email), OTP_SECRET);
                            $ipHash = hash_hmac('sha256', $clientIp, OTP_SECRET);
                            
                            try {
                                $pdo->beginTransaction();
                                
                                // Cooldown check (60s)
                                $stmt = $pdo->prepare("SELECT created_at FROM email_otps WHERE email_hash = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
                                $stmt->execute([$emailHash]);
                                $lastOtp = $stmt->fetch();
                                if ($lastOtp && (time() - strtotime($lastOtp['created_at']) < 60)) {
                                    $error = "Please wait 60 seconds before requesting another code.";
                                    $pdo->rollBack();
                                } else {
                                    // IP & Email Rate Limit check
                                    if (!checkLimitLocal($pdo, $ipHash, 'ip', 5, 30)) {
                                        $error = "Too many requests from your connection. Please try again later.";
                                        $pdo->rollBack();
                                    } elseif (!checkLimitLocal($pdo, $emailHash, 'email', 3, 10)) {
                                        $error = "Too many requests for this email. Please try again later.";
                                        $pdo->rollBack();
                                    } else {
                                        // Save OTP as 'pending'
                                        $otpCode = (string)random_int(100000, 999999);
                                        $otpHash = hash_hmac('sha256', $otpCode, OTP_SECRET);
                                        
                                        // Invalidate old OTPs
                                        $upd = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE email_hash = ? AND action = 'update_profile' AND status = 'sent'");
                                        $upd->execute([$emailHash]);
                                        
                                        $expiresAt = date('Y-m-d H:i:s', time() + 600);
                                        $ins = $pdo->prepare("INSERT INTO email_otps (email_hash, otp_hash, action, expires_at, status) VALUES (?, ?, 'update_profile', ?, 'pending')");
                                        $ins->execute([$emailHash, $otpHash, $expiresAt]);
                                        $otpId = $pdo->lastInsertId();
                                        
                                        $pdo->commit();
                                        
                                        $matched_id = $matched['id'];
                                        $phone = $member_type === 'athlete' ? $matched['mobile'] : $matched['phone'];
                                        $needs_otp = true;
                                        $step = 2;
                                        $mask_contact = 'Email: ' . substr($email, 0, 2) . '******' . strstr($email, '@');
                                        $matched_email = $email;
                                        
                                        // Dispatch Resend email
                                        $htmlBody = "
                                          <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
                                            <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
                                            <p>Hello,</p>
                                            <p>Your 6-digit OTP verification code to update your profile registration details is:</p>
                                            <div style=\"background: #f1f5f9; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #FF9933; margin: 20px 0; border-radius: 6px;\">
                                              {$otpCode}
                                            </div>
                                            <p style=\"color: #64748b; font-size: 14px;\">This code is valid for 10 minutes and can only be used once. Please do not share this code.</p>
                                          </div>
                                        ";
                                        
                                        $idempotencyKey = hash('sha256', $emailHash . 'update_profile' . $otpId);
                                        $sent = sendEmail(
                                            $email,
                                            'Profile Update Verification Code - BSFI',
                                            $htmlBody,
                                            null,
                                            true,
                                            $idempotencyKey
                                        );
                                        
                                        if ($sent) {
                                            $upd = $pdo->prepare("UPDATE email_otps SET status = 'sent' WHERE id = ?");
                                            $upd->execute([$otpId]);
                                        } else {
                                            $upd = $pdo->prepare("UPDATE email_otps SET status = 'failed' WHERE id = ?");
                                            $upd->execute([$otpId]);
                                            $error = "Unable to send verification email right now. Please try again later.";
                                            $step = 1;
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                if ($pdo->inTransaction()) {
                                    $pdo->rollBack();
                                }
                                $error = "Internal server error. Please try again later.";
                            }
                        }
                    }
                }
            }
        } else {
                $error = "No active approved registration found matching the entered details.";
            }
        } catch (PDOException $e) {
            $error = "Lookup failed due to database issues: " . $e->getMessage();
        }
    }
}

// Handle OTP Step 2
if (isset($_POST['verify_otp'])) {
    if (empty($otp_code) || empty($matched_email)) {
        $error = "Please enter the verification code sent to your contact info.";
        $step = 2;
    } else {
        try {
            $emailHash = hash_hmac('sha256', strtolower($matched_email), OTP_SECRET);
            // Fetch active OTP
            $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email_hash = ? AND action = 'update_profile' AND status = 'sent' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$emailHash]);
            $record = $stmt->fetch();

            if (!$record) {
                $error = "Invalid or expired verification code.";
                $step = 2;
            } else {
                if ($record['attempt_count'] >= 5) {
                    $upd = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE id = ?");
                    $upd->execute([$record['id']]);
                    $error = "Too many failed verification attempts. Please request a new OTP.";
                    $step = 1;
                } else {
                    $hash = hash_hmac('sha256', $otp_code, OTP_SECRET);
                    if ($record['otp_hash'] !== $hash) {
                        // Increment attempts
                        $upd = $pdo->prepare("UPDATE email_otps SET attempt_count = attempt_count + 1 WHERE id = ?");
                        $upd->execute([$record['id']]);
                        
                        if ($record['attempt_count'] + 1 >= 5) {
                            $upd2 = $pdo->prepare("UPDATE email_otps SET status = 'invalidated' WHERE id = ?");
                            $upd2->execute([$record['id']]);
                            $error = "Too many failed verification attempts. Please request a new OTP.";
                            $step = 1;
                        } else {
                            $error = "Invalid verification code. Please try again.";
                            $step = 2;
                        }
                    } else {
                        // Success -> set status to 'used'
                        $upd = $pdo->prepare("UPDATE email_otps SET status = 'used', used_at = NOW() WHERE id = ?");
                        $upd->execute([$record['id']]);
                        $step = 3;
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Verification failed due to database issues.";
            $step = 2;
        }
    }
}

// Handle Form Submission Step 3
if (isset($_POST['submit_update'])) {
    $email_req = trim($_POST['email'] ?? '');
    $phone_req = trim($_POST['phone'] ?? '');
    $address_req = trim($_POST['address'] ?? '');
    $pincode_req = trim($_POST['pincode'] ?? '');
    
    // Additional fields for players
    $kit_tshirt_req = trim($_POST['kit_tshirt'] ?? '');
    $kit_tracksuit_req = trim($_POST['kit_tracksuit'] ?? '');
    $kit_shoe_req = trim($_POST['kit_shoe'] ?? '');
    $aadhaar_req = trim($_POST['aadhaar'] ?? '');
    $impairment_type_req = trim($_POST['impairment_type'] ?? '');
    $wheelchair_status_req = trim($_POST['wheelchair_status'] ?? '');
    
    $photo_path = null;
    $passport_path = null;
    $medical_path = null;
    $isValid = true;

    // Helper for file uploads
    $uploadFile = function($fileKey, $allowedExts, $targetDirRel, &$errorMsg) {
        if (empty($_FILES[$fileKey]['name'])) return null;
        if ($_FILES[$fileKey]['size'] > 10 * 1024 * 1024) {
            $errorMsg = "File size for " . htmlspecialchars($fileKey) . " exceeds 10MB limit.";
            return false;
        }
        $filename = $_FILES[$fileKey]['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            $errorMsg = "Invalid file format for " . htmlspecialchars($fileKey) . ". Allowed: " . implode(', ', $allowedExts);
            return false;
        }
        $uploadDir = __DIR__ . '/../' . $targetDirRel;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $secureName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $secureName)) {
            return $targetDirRel . $secureName;
        }
        $errorMsg = "Failed to upload file.";
        return false;
    };

    // Handle photo upload
    if (!empty($_FILES['photo_path']['name'])) {
        $res = $uploadFile('photo_path', ['jpg', 'jpeg', 'png'], 'uploads/profiles/', $error);
        if ($res === false) { $isValid = false; } else { $photo_path = $res; }
    }

    // Handle passport/identity file upload
    if ($isValid && !empty($_FILES['passport_file']['name'])) {
        $res = $uploadFile('passport_file', ['jpg', 'jpeg', 'png', 'pdf'], 'uploads/documents/', $error);
        if ($res === false) { $isValid = false; } else { $passport_path = $res; }
    }

    // Handle medical certificate upload
    if ($isValid && !empty($_FILES['medical_certificate']['name'])) {
        $res = $uploadFile('medical_certificate', ['jpg', 'jpeg', 'png', 'pdf'], 'uploads/documents/', $error);
        if ($res === false) { $isValid = false; } else { $medical_path = $res; }
    }

    // Verify email is unique if they are requesting a change
    if ($isValid && !empty($email_req)) {
        // Query to check if the new email belongs to someone else
        $dupCheckAth = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE email = ? AND (id != ? OR ? != 'athlete') AND deleted_at IS NULL");
        $dupCheckAth->execute([$email_req, $matched_id, $member_type]);
        if ($dupCheckAth->fetchColumn() > 0) {
            $error = "This email is already associated with another active player.";
            $isValid = false;
        }

        if ($isValid) {
            $dupCheckOff = $pdo->prepare("SELECT COUNT(*) FROM officials WHERE email = ? AND (id != ? OR ? != 'official') AND deleted_at IS NULL");
            $dupCheckOff->execute([$email_req, $matched_id, $member_type]);
            if ($dupCheckOff->fetchColumn() > 0) {
                $error = "This email is already associated with a registered official. Players cannot use official emails.";
                $isValid = false;
            }
        }
    }

    if ($isValid) {
        try {
            $ins = $pdo->prepare("INSERT INTO profile_update_requests 
                (member_type, member_id, requested_email, requested_phone, requested_address, requested_pincode, requested_kit_tshirt, requested_kit_tracksuit, requested_kit_shoe, requested_aadhaar, requested_impairment_type, requested_wheelchair_status, requested_photo_path, requested_passport_file, requested_medical_certificate, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $ins->execute([
                $member_type, $matched_id, $email_req, $phone_req, $address_req, $pincode_req,
                $kit_tshirt_req, $kit_tracksuit_req, $kit_shoe_req, $aadhaar_req, $impairment_type_req, $wheelchair_status_req,
                $photo_path, $passport_path, $medical_path
            ]);
            
            $step = 4;
            $message = "Your profile update request has been successfully submitted! An administrator will review and apply the changes shortly.";
            
            logAction($pdo, "Submitted Profile Update Request", $member_type . "_applications", $matched_id, "Type: $member_type | ID: $matched_id");
        } catch (PDOException $e) {
            $error = "Failed to save update request: " . $e->getMessage();
            $step = 3;
        }
    } else {
        $step = 3;
    }
}

$page_title = "Update Profile - Boccia India";
include __DIR__ . '/../includes/header.php';
?>

<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-green: #10B981;
    --boccia-saffron: #FF9933;
    --boccia-maroon: #8C201C;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

.update-portal-bg {
    min-height: 80vh;
    background: linear-gradient(135deg, rgba(8, 27, 75, 0.95) 0%, rgba(140, 32, 28, 0.95) 100%), url('../about boccia/why_boccia_matter_BG.webp') no-repeat center center;
    background-size: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 0;
}

.glass-card-update {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 50px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    width: 100%;
    max-width: 650px;
    color: #ffffff;
}

.form-label-custom {
    font-family: var(--font-body-custom);
    font-weight: 600;
    color: #ffffff;
    font-size: 0.95rem;
    margin-bottom: 8px;
    display: block;
    text-align: left;
}

.form-control-custom, .form-select-custom {
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.95rem;
    background-color: rgba(255,255,255,0.95);
    color: var(--boccia-navy);
    font-weight: 600;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control-custom:focus, .form-select-custom:focus {
    border-color: var(--boccia-saffron);
    outline: none;
    box-shadow: 0 0 10px rgba(255, 153, 51, 0.3);
}

.btn-submit-update {
    background: linear-gradient(135deg, var(--boccia-saffron) 0%, #E68015 100%);
    border: none;
    border-radius: 12px;
    padding: 14px 40px;
    color: #ffffff;
    font-family: var(--font-heading-sub);
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    width: 100%;
    margin-top: 20px;
}

.btn-submit-update:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(230, 128, 21, 0.3);
}
.hp-field {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}
</style>

<div class="update-portal-bg">
    <div class="container" style="max-width: 650px;">
        <div class="card border-0 shadow-lg text-white" style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-radius: 20px; padding: 2.5rem 2rem;">
            
            <div class="text-center mb-4">
                <img src="../boccia-india-logo.webp" alt="BSFI Logo" style="max-height: 80px; width: auto;" class="mb-3">
                <h2 style="font-family: var(--font-heading-sub); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #FAF7F0;">Profile Update Portal</h2>
                <p style="font-size: 0.9rem; opacity: 0.8; font-weight: 400;">Keep your BSFI registration details, kit sizes, and profile documents updated.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 p-3 mb-4 rounded-3 text-start" style="background-color: rgba(220, 38, 38, 0.15); color: #FCA5A5; border: 1px solid rgba(220, 38, 38, 0.3) !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: IDENTITY LOOKUP -->
            <?php if ($step === 1): ?>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="hp-field" aria-hidden="true">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Select Registration Type</label>
                        <select name="member_type" class="form-select-custom">
                            <option value="athlete" <?php echo $member_type === 'athlete' ? 'selected' : ''; ?>>Athlete / Player</option>
                            <option value="official" <?php echo $member_type === 'official' ? 'selected' : ''; ?>>Coach / Referee / Official</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Registration Number / ID</label>
                        <input type="text" name="member_id_input" value="<?php echo htmlspecialchars($member_id_input); ?>" class="form-control-custom" placeholder="E.g. 0003 or OF-0001" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>" class="form-control-custom" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Registered Email Address</label>
                        <input type="email" name="lookup_email" value="<?php echo htmlspecialchars($lookup_email); ?>" class="form-control-custom" placeholder="E.g. athlete@example.com" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Father's Name <span style="font-size:0.8rem; opacity:0.8; font-weight:normal;">(Required only for legacy profiles without registered emails)</span></label>
                        <input type="text" name="father_name_input" value="<?php echo htmlspecialchars($father_name_input); ?>" class="form-control-custom" placeholder="Enter Father's Name">
                    </div>
                    <div class="mb-4 text-center d-flex justify-content-center">
                        <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <button type="submit" name="lookup" class="btn-submit-update">Verify Identity</button>
                </form>
            
            <!-- STEP 2: OTP OTP OTP -->
            <?php elseif ($step === 2): ?>
                <div class="alert alert-info border-0 p-3 mb-4 rounded-3 text-start" style="background-color: rgba(59, 130, 246, 0.15); color: #93C5FD; border: 1px solid rgba(59, 130, 246, 0.3) !important;">
                    <i class="bi bi-info-circle-fill me-2"></i> A verification code has been sent to your registered: <strong><?php echo $mask_contact; ?></strong>.
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="member_type" value="<?php echo htmlspecialchars($member_type); ?>">
                    <input type="hidden" name="matched_id" value="<?php echo $matched_id; ?>">
                    <input type="hidden" name="matched_email" value="<?php echo htmlspecialchars($matched_email); ?>">
                    <div class="mb-4">
                        <label class="form-label-custom">Enter Verification Code</label>
                        <input type="text" name="otp_code" class="form-control-custom text-center" placeholder="123456" style="font-size: 1.4rem; letter-spacing: 0.2em;" required>
                    </div>
                    <button type="submit" name="verify_otp" class="btn-submit-update">Verify Code</button>
                </form>

            <!-- STEP 3: UPDATE FORM -->
            <?php elseif ($step === 3): ?>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-warning border-0 p-3 mb-4 rounded-3 text-start" style="background-color: rgba(245, 158, 11, 0.15); color: #FDE68A; border: 1px solid rgba(245, 158, 11, 0.3) !important;">
                        <i class="bi bi-shield-fill-exclamation me-2"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="member_type" value="<?php echo htmlspecialchars($member_type); ?>">
                    <input type="hidden" name="matched_id" value="<?php echo $matched_id; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label-custom">Contact Email Address</label>
                        <input type="email" name="email" class="form-control-custom" placeholder="E.g. athlete@example.com" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label-custom">Mobile Phone Number</label>
                        <input type="tel" name="phone" class="form-control-custom" placeholder="E.g. +91 9876543210" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Aadhaar Number (Optional)</label>
                        <input type="text" name="aadhaar" class="form-control-custom" placeholder="E.g. 1234 5678 9012">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label-custom">T-Shirt Size</label>
                            <input type="text" name="kit_tshirt" class="form-control-custom" placeholder="E.g. M, L, XL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Tracksuit Size</label>
                            <input type="text" name="kit_tracksuit" class="form-control-custom" placeholder="E.g. M, L, XL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Shoe Size</label>
                            <input type="text" name="kit_shoe" class="form-control-custom" placeholder="E.g. UK 8">
                        </div>
                    </div>

                    <?php if ($member_type === 'athlete'): ?>
                    <div class="mb-4">
                        <label class="form-label-custom">Impairment Type (Optional)</label>
                        <input type="text" name="impairment_type" class="form-control-custom" placeholder="E.g. Cerebral Palsy">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Wheelchair Status</label>
                        <select name="wheelchair_status" class="form-select-custom">
                            <option value="None">None</option>
                            <option value="Manual Wheelchair">Manual Wheelchair</option>
                            <option value="Power Wheelchair">Power Wheelchair</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="form-label-custom">Permanent Address</label>
                        <textarea name="address" class="form-control-custom" rows="3" placeholder="Enter complete address..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Pin Code</label>
                        <input type="text" name="pincode" class="form-control-custom" placeholder="E.g. 143001" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Upload Passport Size Photograph</label>
                        <input type="file" name="photo_path" class="form-control-custom" accept=".jpg,.jpeg,.png">
                        <span style="font-size:0.8rem; color:rgba(255,255,255,0.7); display:block; margin-top:5px;">*Passport photo must show face clearly in JPG/PNG format. Max 5MB.</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Upload Passport / Identity File (Optional)</label>
                        <input type="file" name="passport_file" class="form-control-custom" accept=".jpg,.jpeg,.png,.pdf">
                        <span style="font-size:0.8rem; color:rgba(255,255,255,0.7); display:block; margin-top:5px;">*Identity proof document in PDF or image format (Max 10MB).</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Upload Medical Certificate (Optional)</label>
                        <input type="file" name="medical_certificate" class="form-control-custom" accept=".jpg,.jpeg,.png,.pdf">
                        <span style="font-size:0.8rem; color:rgba(255,255,255,0.7); display:block; margin-top:5px;">*Valid medical/disability certificate in PDF or image format (Max 10MB).</span>
                    </div>

                    <button type="submit" name="submit_update" class="btn-submit-update">Submit Profile Update</button>
                </form>

            <!-- STEP 4: SUCCESS! -->
            <?php elseif ($step === 4): ?>
                <div class="text-center py-4">
                    <div style="font-size: 4rem; color: var(--boccia-green);" class="mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h2 style="font-family: var(--font-heading-sub); font-weight: 700; margin-bottom:15px;">Submission Successful!</h2>
                    <p style="font-family: var(--font-body-custom); color: rgba(255,255,255,0.85); line-height: 1.6;">
                        <?php echo $message; ?>
                    </p>
                    <a href="status.php" class="btn btn-outline-light rounded-pill px-4 mt-4" style="font-family: var(--font-heading-sub); font-weight:700;">
                        Track Status / Verification
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($step < 4): ?>
                <div class="text-center mt-5 border-top pt-3" style="border-top-color: rgba(255,255,255,0.1) !important;">
                    <a href="status.php" class="text-white text-decoration-none" style="font-family: var(--font-heading-sub); font-weight: 600; font-size: 0.95rem;">
                        <i class="bi bi-arrow-left me-1"></i> Back to Status Verification
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
