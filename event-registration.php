<?php
// event-registration.php - Event Registration Wizard for Athletes and Officials
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Event Registration - BSFI Portal";
include __DIR__ . '/includes/header.php';
?>

<style>
    body.accessibility-target {
        background: url('about%20boccia/overview_bg.webp') no-repeat center center fixed !important;
        background-size: cover !important;
    }
    /* Add a subtle glassmorphism overlay to body to ensure text remains highly legible */
    body.accessibility-target::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(248, 245, 239, 0.90) !important;
        backdrop-filter: blur(6px) !important;
        -webkit-backdrop-filter: blur(6px) !important;
        z-index: -1;
    }
</style>

<?php
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ? AND active = 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Fetch dynamic form fields
$form_fields = [];
if ($event) {
    $ffStmt = $pdo->prepare("SELECT * FROM event_form_fields WHERE schedule_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC");
    $ffStmt->execute([$event['id']]);
    $form_fields = $ffStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch centralized bank details
$bank = [];
$bankStmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'payment_%'");
while ($row = $bankStmt->fetch(PDO::FETCH_ASSOC)) {
    $bank[$row['setting_key']] = $row['setting_value'];
}

$error = '';
$success = '';
$step = 1; // 1: Auth check / Login, 2: Form submission / Payment, 3: Completed

// ═══════════════════════════════════════════
// WIZARD INITIAL VALIDATIONS
// ═══════════════════════════════════════════

if (!$event) {
    $error = "This tournament event is not available or has been deactivated.";
    $step = 0;
} elseif (($event['registration_mode'] ?? 'external') !== 'internal') {
    $error = "This event does not support internal registration.";
    $step = 0;
} else {
    // Check registration deadline
    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
        $error = "The registration deadline for this event was " . date('d M Y, h:i A', strtotime($event['registration_deadline'])) . ". Registrations are now closed.";
        $step = 0;
    }
}

// ═══════════════════════════════════════════
// HANDLE AUTH / OTP REQUESTS
// ═══════════════════════════════════════════

$member_type = isset($_POST['member_type']) ? $_POST['member_type'] : 'athlete';
$member_id_input = isset($_POST['member_id_input']) ? trim($_POST['member_id_input']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
$lookup_email = isset($_POST['lookup_email']) ? strtolower(trim($_POST['lookup_email'])) : '';
$otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
$matched_email = isset($_POST['matched_email']) ? trim($_POST['matched_email']) : '';

// 1. Send OTP
if (isset($_POST['send_otp']) && $step > 0) {
    if (empty($member_id_input) || empty($dob) || empty($lookup_email)) {
        $error = "Please fill in all identity lookup fields including your registered email address.";
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
                $email = $matched['email'];
                if (empty($email) || strtolower(trim($email)) !== $lookup_email) {
                    $error = "The entered email address does not match our records for this profile.";
                } else {
                    // Generate OTP code
                    $otpCode = (string)random_int(100000, 999999);
                    $otpHash = hash_hmac('sha256', $otpCode, OTP_SECRET);

                    // Delete existing active OTPs for this email
                    $del = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
                    $del->execute([$email]);

                    // Save hashed OTP
                    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 mins
                    $ins = $pdo->prepare("INSERT INTO email_otps (email, otp_hash, expires_at, ip_address) VALUES (?, ?, ?, ?)");
                    $ins->execute([$email, $otpHash, $expiresAt, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    // Dispatch via Resend REST call
                    $htmlBody = "
                        <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
                            <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
                            <p>Hello,</p>
                            <p>Your 6-digit OTP verification code to log in and register for the tournament event is:</p>
                            <div style=\"background: #f1f5f9; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #FF9933; margin: 20px 0; border-radius: 6px;\">
                                {$otpCode}
                            </div>
                            <p style=\"color: #64748b; font-size: 14px;\">This code is valid for 5 minutes and can only be used once. Please do not share this code.</p>
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
                        'subject' => 'Event Registration Verification Code - BSFI',
                        'html' => $htmlBody
                    ]));
                    curl_exec($ch);
                    curl_close($ch);

                    // Log to cache for easy debugging/local verification
                    @file_put_contents(__DIR__ . '/cache/otp_debug.log', "[" . date('Y-m-d H:i:s') . "] Email: {$email} | OTP: {$otpCode}\n", FILE_APPEND);

                    $matched_email = $email;
                    $success = "A 6-digit verification code has been dispatched to your email: " . htmlspecialchars($email);
                    $step = 1.5; // OTP entry screen
                }
            } else {
                $error = "No active approved registration found matching the entered details.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// 2. Verify OTP
if (isset($_POST['verify_otp']) && $step > 0) {
    if (empty($otp_code) || empty($matched_email)) {
        $error = "Please enter the verification code sent to your email.";
        $step = 1.5;
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email = ? AND verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$matched_email]);
            $record = $stmt->fetch();

            if (!$record) {
                $error = "Invalid or expired verification code.";
                $step = 1.5;
            } else {
                $hash = hash_hmac('sha256', $otp_code, OTP_SECRET);
                if ($record['otp_hash'] !== $hash) {
                    $upd = $pdo->prepare("UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?");
                    $upd->execute([$record['id']]);
                    $error = "Invalid verification code. Please try again.";
                    $step = 1.5;
                } else {
                    // Success -> delete OTP records to prevent reuse
                    $del = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
                    $del->execute([$matched_email]);

                    // Fetch member ID and set session
                    if ($member_type === 'official') {
                        $mStmt = $pdo->prepare("SELECT * FROM officials WHERE email = ? AND status = 'approved' AND deleted_at IS NULL");
                        $mStmt->execute([$matched_email]);
                        $mRow = $mStmt->fetch();
                        if ($mRow) {
                            $_SESSION['member_id'] = $mRow['id'];
                            $_SESSION['member_type'] = 'official';
                            $_SESSION['member_regn_no'] = $mRow['official_reg_no'];
                        }
                    } else {
                        $mStmt = $pdo->prepare("SELECT * FROM athletes WHERE email = ? AND status = 'approved' AND deleted_at IS NULL");
                        $mStmt->execute([$matched_email]);
                        $mRow = $mStmt->fetch();
                        if ($mRow) {
                            $_SESSION['member_id'] = $mRow['id'];
                            $_SESSION['member_type'] = 'athlete';
                            $_SESSION['member_regn_no'] = $mRow['regn_no'];
                        }
                    }
                    $success = "Identity verified successfully. You may now complete your registration form.";
                }
            }
        } catch (PDOException $e) {
            $error = "Verification failed due to database issues.";
            $step = 1.5;
        }
    }
}

// ═══════════════════════════════════════════
// WIZARD FORWARD STATE LOGIC
// ═══════════════════════════════════════════

if ($step > 0 && isset($_SESSION['member_id'])) {
    $step = 2; // Proceed directly to form

    // Check if member already registered for this event
    $chk = $pdo->prepare("SELECT * FROM event_registrations WHERE schedule_id = ? AND member_type = ? AND member_id = ?");
    $chk->execute([$event_id, $_SESSION['member_type'], $_SESSION['member_id']]);
    $existing_reg = $chk->fetch();

    if ($existing_reg) {
        $success = "You are already registered for this event. Event Registration ID: " . htmlspecialchars($existing_reg['registration_no']);
        $step = 3;
    }
}

// Fetch member details if authenticated
$member = null;
if ($step == 2 && isset($_SESSION['member_id'])) {
    if ($_SESSION['member_type'] === 'official') {
        $stmt = $pdo->prepare("SELECT * FROM officials WHERE id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch();
    }

    if (!$member) {
        unset($_SESSION['member_id'], $_SESSION['member_type'], $_SESSION['member_regn_no']);
        $step = 1;
    }
}

// ═══════════════════════════════════════════
// SUBMIT REGISTRATION HANDLER
// ═══════════════════════════════════════════

if ($step == 2 && isset($_POST['submit_registration'])) {
    // Capacity Check
    $stat_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE schedule_id = ? AND registration_status IN ('approved', 'pending')");
    $stat_stmt->execute([$event_id]);
    $current_count = (int)$stat_stmt->fetchColumn();

    $reg_status = 'pending';
    if ($event['max_participants'] && $current_count >= $event['max_participants']) {
        if ($event['allow_waiting_list']) {
            $reg_status = 'waiting_list';
        } else {
            $error = "This event is at full capacity and registration is closed.";
        }
    }

    $fee = (float)$event['registration_fee'];
    $payment_status = ($fee == 0) ? 'free' : 'pending';
    $tx_ref = isset($_POST['transaction_reference']) ? trim($_POST['transaction_reference']) : '';
    $uploaded_receipt = null;

    // Handle payment proof upload
    if (empty($error) && $fee > 0) {
        if (empty($tx_ref)) {
            $error = "Transaction reference number is required.";
        } elseif (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] !== UPLOAD_ERR_OK) {
            $error = "Please upload a valid payment receipt file.";
        } else {
            $file = $_FILES['payment_receipt'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed_exts)) {
                $error = "Invalid file extension. Only PDF, JPG, JPEG, and PNG are allowed.";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "File size must be less than 5MB.";
            } else {
                // Verify MIME type securely
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];
                if (!in_array($mime, $allowed_mimes)) {
                    $error = "Invalid receipt document file type.";
                } else {
                    // Setup path
                    $year = date('Y');
                    $uuid = uniqid('receipt_', true);
                    $dest_dir = "uploads/payments/{$year}/event_{$event_id}/";
                    if (!is_dir($dest_dir)) {
                        mkdir($dest_dir, 0755, true);
                    }
                    $uploaded_receipt = $dest_dir . $uuid . '.' . $ext;
                    if (!move_uploaded_file($file['tmp_name'], $uploaded_receipt)) {
                        $error = "Failed to store uploaded receipt document.";
                        $uploaded_receipt = null;
                    }
                }
            }
        }
    }

    // Process submission inside transaction
    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Double check duplicate registration inside transaction
            $dup = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE schedule_id = ? AND member_type = ? AND member_id = ? FOR UPDATE");
            $dup->execute([$event_id, $_SESSION['member_type'], $_SESSION['member_id']]);
            if ($dup->fetchColumn() > 0) {
                throw new Exception("You are already registered for this event.");
            }

            // Generate Immutable Registration ID
            // Format: ER-YEAR-PADDED_ID (we first insert a temporary record or use sequence mapping)
            // To do this simply in procedural PHP, we insert first and then construct the number using the last insert ID.
            
            // Collect snapshot details
            $snapshot_name = $member['full_name'];
            $snapshot_regn_no = $_SESSION['member_type'] === 'athlete' ? $member['regn_no'] : $member['official_reg_no'];
            $snapshot_email = $member['email'];
            $snapshot_mobile = $_SESSION['member_type'] === 'athlete' ? $member['mobile'] : $member['phone'];
            $snapshot_state = $member['state'];
            $snapshot_classification = $_SESSION['member_type'] === 'athlete' ? ($member['classification'] ?? '') : ($member['role'] ?? '');
            $snapshot_gender = $member['gender'] ?? '';
            $snapshot_dob = $member['dob'] ?? null;

            $ins = $pdo->prepare("
                INSERT INTO event_registrations (
                    registration_no, schedule_id, member_type, member_id, payment_status, registration_status, 
                    payment_receipt_path, transaction_reference, snapshot_name, snapshot_regn_no, snapshot_email, 
                    snapshot_mobile, snapshot_state, snapshot_classification, snapshot_gender, snapshot_dob
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // Use dummy reg_no initially to get auto_increment ID
            $dummy_reg_no = 'TEMP-' . uniqid();
            $ins->execute([
                $dummy_reg_no, $event_id, $_SESSION['member_type'], $_SESSION['member_id'], $payment_status, $reg_status,
                $uploaded_receipt, $tx_ref, $snapshot_name, $snapshot_regn_no, $snapshot_email,
                $snapshot_mobile, $snapshot_state, $snapshot_classification, $snapshot_gender, $snapshot_dob
            ]);
            
            $reg_db_id = $pdo->lastInsertId();
            $year_prefix = date('Y');
            $reg_no = "ER-{$year_prefix}-" . str_pad($reg_db_id, 6, '0', STR_PAD_LEFT);

            // Update registration with permanent ID
            $upPermanent = $pdo->prepare("UPDATE event_registrations SET registration_no = ? WHERE id = ?");
            $upPermanent->execute([$reg_no, $reg_db_id]);

            // Save custom fields answers
            $custom_fields = $_POST['custom'] ?? [];
            $ins_ans = $pdo->prepare("INSERT INTO event_registration_answers (registration_id, field_id, answer_value) VALUES (?, ?, ?)");
            
            // Handle custom file uploads
            if (isset($_FILES['custom'])) {
                foreach ($_FILES['custom']['name'] as $fid => $fname) {
                    if ($_FILES['custom']['error'][$fid] === UPLOAD_ERR_OK) {
                        $cfile = [
                            'name' => $_FILES['custom']['name'][$fid],
                            'type' => $_FILES['custom']['type'][$fid],
                            'tmp_name' => $_FILES['custom']['tmp_name'][$fid],
                            'error' => $_FILES['custom']['error'][$fid],
                            'size' => $_FILES['custom']['size'][$fid]
                        ];
                        $cext = strtolower(pathinfo($cfile['name'], PATHINFO_EXTENSION));
                        
                        $year = date('Y');
                        $cuuid = uniqid('custom_', true);
                        $cdest_dir = "uploads/payments/{$year}/event_{$event_id}/registration_{$reg_db_id}/";
                        if (!is_dir($cdest_dir)) {
                            mkdir($cdest_dir, 0755, true);
                        }
                        $cpath = $cdest_dir . $cuuid . '.' . $cext;
                        if (move_uploaded_file($cfile['tmp_name'], $cpath)) {
                            $custom_fields[$fid] = $cpath;
                        }
                    }
                }
            }

            foreach ($custom_fields as $fid => $val) {
                // Ensure field exists
                $ins_ans->execute([$reg_db_id, $fid, is_array($val) ? implode(', ', $val) : trim($val)]);
            }

            $pdo->commit();
            logAction($pdo, "Event Registration Submitted", "event_registrations", $reg_db_id, "Reg No: $reg_no");

            // Dispatch confirmation email
            $subject = "Event Registration Confirmed - BSFI";
            $status_msg = ($reg_status === 'waiting_list') ? "placed on the WAITING LIST" : "submitted and is PENDING verification";
            
            $htmlBody = "
                <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
                    <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
                    <p>Hello <strong>" . htmlspecialchars($snapshot_name) . "</strong>,</p>
                    <p>Thank you for registering. Your event registration has been successfully {$status_msg}.</p>
                    <table style=\"width: 100%; border-collapse: collapse; margin: 20px 0;\">
                        <tr>
                            <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>Event Registration ID:</strong></td>
                            <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>{$reg_no}</strong></td>
                        </tr>
                        <tr>
                            <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>Tournament:</strong></td>
                            <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\">" . htmlspecialchars($event['discipline']) . "</td>
                        </tr>
                    </table>
                    <p style=\"color: #64748b; font-size: 14px;\">Please keep your Registration ID for future reference and accreditation checks.</p>
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
                'to' => $snapshot_email,
                'subject' => $subject,
                'html' => $htmlBody
            ]));
            curl_exec($ch);
            curl_close($ch);

            $success = "Your event registration has been submitted successfully! Your Registration Reference ID is <strong>" . htmlspecialchars($reg_no) . "</strong>.";
            $step = 3;
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($uploaded_receipt && file_exists($uploaded_receipt)) {
                unlink($uploaded_receipt);
            }
            $error = "Submission failed: " . $e->getMessage();
        }
    } else {
        // If validation error occurred, delete file if uploaded
        if ($uploaded_receipt && file_exists($uploaded_receipt)) {
            unlink($uploaded_receipt);
        }
    }
}

// Fetch centralized bank settings
$stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'payment_%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$bank = [];
foreach ($rows as $row) {
    $bank[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 6rem; max-width: 900px; font-family: 'Outfit', sans-serif;">
    
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="color: #081B4B; font-weight: 800; margin-bottom: 0.5rem;">Event Registration</h1>
        <p style="color: #64748b; font-size: 1.1rem;">Register for the official BSFI sanctioned tournament events</p>
    </div>

    <!-- Error/Success Alerts -->
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600; padding: 1rem 1.5rem; margin-bottom: 2rem;">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success" style="border-radius: 12px; font-weight: 600; padding: 1rem 1.5rem; margin-bottom: 2rem;">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- STEP 1: IDENTITY LOOKUP & PASSWORDSLESS OTP AUTH -->
    <?php if ($step == 1): ?>
        <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-navy text-white text-center py-4" style="background: #081B4B;">
                <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0;">Step 1: Verify Federation Membership</h4>
            </div>
            <div class="card-body p-5">
                <p class="text-secondary text-center mb-4">Please verify your membership identity to pre-fill your official details and proceed with registration.</p>
                
                <form action="event-registration.php?event_id=<?php echo $event_id; ?>" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                    <input type="hidden" name="send_otp" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Member Type</label>
                        <div class="d-flex gap-4">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <input type="radio" name="member_type" value="athlete" checked style="width: 18px; height: 18px;"> Athlete
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <input type="radio" name="member_type" value="official" style="width: 18px; height: 18px;"> Official
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="member_id" class="form-label fw-bold">Registration Number (e.g. 042)</label>
                        <input type="text" id="member_id" name="member_id_input" class="form-control py-2.5 px-3" required placeholder="Enter your BSFI registration number">
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label fw-bold">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control py-2.5 px-3" required>
                    </div>

                    <div class="mb-4">
                        <label for="lookup_email" class="form-label fw-bold">Registered Email Address</label>
                        <input type="email" id="lookup_email" name="lookup_email" class="form-control py-2.5 px-3" required placeholder="Enter your registered email address">
                    </div>

                    <button type="submit" class="btn text-white fw-bold py-2.5" style="background: #FF9933; border-radius: 8px; font-size: 1.05rem;">Request Verification Code</button>
                </form>

                <div style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem; text-align: center;">
                    <p class="text-secondary m-0">Not a registered federation member yet? <a href="https://bocciaindia.com/get-involved/membership.php" class="fw-bold" style="color: #FF9933; text-decoration: none;">Apply Here</a></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- STEP 1.5: ENTER OTP CODE -->
    <?php if ($step == 1.5): ?>
        <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; max-width: 500px; margin: 0 auto;">
            <div class="card-header bg-navy text-white text-center py-4" style="background: #081B4B;">
                <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0;">Step 1.5: Verification Code</h4>
            </div>
            <div class="card-body p-5">
                <form action="event-registration.php?event_id=<?php echo $event_id; ?>" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                    <input type="hidden" name="verify_otp" value="1">
                    <input type="hidden" name="member_type" value="<?php echo htmlspecialchars($member_type); ?>">
                    <input type="hidden" name="matched_email" value="<?php echo htmlspecialchars($matched_email); ?>">
                    
                    <div class="mb-4">
                        <label for="otp_code" class="form-label fw-bold d-block text-center mb-3">Enter 6-Digit OTP</label>
                        <input type="text" id="otp_code" name="otp_code" maxlength="6" class="form-control text-center fw-bold py-3" style="font-size: 1.6rem; letter-spacing: 0.25em;" required placeholder="000000">
                        <small class="text-muted d-block text-center mt-2">The code is valid for 5 minutes.</small>
                    </div>

                    <button type="submit" class="btn text-white fw-bold py-2.5" style="background: #FF9933; border-radius: 8px;">Verify Code & Continue</button>
                    <a href="event-registration.php?event_id=<?php echo $event_id; ?>" class="btn btn-outline-secondary py-2.5">Back</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- STEP 2: PROFILE PRE-FILL, CUSTOM FIELDS, & PAYMENT -->
    <?php if ($step == 2 && $member): ?>
        <div class="card shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-header bg-navy text-white py-4" style="background: #081B4B;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0;">Step 2: Complete Registration</h4>
                    <span class="badge bg-success text-white fw-bold px-3 py-2 text-uppercase" style="letter-spacing: 0.5px;"><i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($_SESSION['member_type']); ?> Verified</span>
                </div>
            </div>
            
            <form action="event-registration.php?event_id=<?php echo $event_id; ?>" method="POST" enctype="multipart/form-data" class="card-body p-5" style="display:flex; flex-direction:column; gap:2rem;">
                <input type="hidden" name="submit_registration" value="1">

                <!-- 1. System Read-Only Profile Snapshot -->
                <div>
                    <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: #081B4B;"><i class="fa-solid fa-id-card"></i> Member Profile Details (Pre-Filled)</h5>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; background:#f8fafc; padding:1.5rem; border-radius:12px; font-size:0.95rem;">
                        <div><span class="text-secondary fw-semibold">Name:</span> <strong class="text-dark"><?php echo htmlspecialchars($member['full_name']); ?></strong></div>
                        <div><span class="text-secondary fw-semibold">Reg No:</span> <strong class="text-dark"><?php echo htmlspecialchars($_SESSION['member_type'] === 'athlete' ? $member['regn_no'] : $member['official_reg_no']); ?></strong></div>
                        <div><span class="text-secondary fw-semibold">Email:</span> <span class="text-dark"><?php echo htmlspecialchars($member['email']); ?></span></div>
                        <div><span class="text-secondary fw-semibold">Mobile:</span> <span class="text-dark"><?php echo htmlspecialchars($_SESSION['member_type'] === 'athlete' ? $member['mobile'] : $member['phone']); ?></span></div>
                        <div><span class="text-secondary fw-semibold">State Association:</span> <span class="text-dark"><?php echo htmlspecialchars($member['state']); ?></span></div>
                        <div><span class="text-secondary fw-semibold">DOB:</span> <span class="text-dark"><?php echo htmlspecialchars($member['dob']); ?></span></div>
                    </div>
                </div>

                <!-- 2. Dynamic Custom Form Fields -->
                <?php if (count($form_fields) > 0): ?>
                    <div>
                        <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: #081B4B;"><i class="fa-solid fa-list-check"></i> Additional Tournament Questions</h5>
                        <div style="display:flex; flex-direction:column; gap:1.25rem;">
                            <?php foreach ($form_fields as $f): ?>
                                <?php if (!$f['is_active']) continue; ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #1e293b;">
                                        <?php echo htmlspecialchars($f['field_label']); ?>
                                        <?php if($f['is_required']): ?>
                                            <span style="color:var(--danger)">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($f['field_type'] === 'text'): ?>
                                        <input type="text" name="custom[<?php echo $f['id']; ?>]" class="form-control py-2 px-3" <?php echo $f['is_required'] ? 'required' : ''; ?> placeholder="<?php echo htmlspecialchars($f['placeholder'] ?? ''); ?>">
                                    
                                    <?php elseif ($f['field_type'] === 'textarea'): ?>
                                        <textarea name="custom[<?php echo $f['id']; ?>]" class="form-control py-2 px-3" rows="3" <?php echo $f['is_required'] ? 'required' : ''; ?> placeholder="<?php echo htmlspecialchars($f['placeholder'] ?? ''); ?>"></textarea>
                                    
                                    <?php elseif ($f['field_type'] === 'number'): ?>
                                        <input type="number" name="custom[<?php echo $f['id']; ?>]" class="form-control py-2 px-3" <?php echo $f['is_required'] ? 'required' : ''; ?> placeholder="<?php echo htmlspecialchars($f['placeholder'] ?? ''); ?>">
                                    
                                    <?php elseif ($f['field_type'] === 'date'): ?>
                                        <input type="date" name="custom[<?php echo $f['id']; ?>]" class="form-control py-2 px-3" <?php echo $f['is_required'] ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($f['field_type'] === 'dropdown'): ?>
                                        <select name="custom[<?php echo $f['id']; ?>]" class="form-select py-2" <?php echo $f['is_required'] ? 'required' : ''; ?>>
                                            <option value="">-- Choose Option --</option>
                                            <?php
                                            $opts = explode(',', $f['field_options']);
                                            foreach ($opts as $o) {
                                                $o = trim($o);
                                                echo "<option value=\"" . htmlspecialchars($o) . "\">" . htmlspecialchars($o) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    
                                    <?php elseif ($f['field_type'] === 'radio'): ?>
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php
                                            $opts = explode(',', $f['field_options']);
                                            foreach ($opts as $o) {
                                                $o = trim($o);
                                                echo "
                                                    <label style=\"cursor:pointer; display:flex; align-items:center; gap:0.5rem;\">
                                                        <input type=\"radio\" name=\"custom[{$f['id']}]\" value=\"" . htmlspecialchars($o) . "\" " . ($f['is_required'] ? 'required' : '') . " style=\"width:16px; height:16px;\"> " . htmlspecialchars($o) . "
                                                    </label>
                                                ";
                                            }
                                            ?>
                                        </div>
                                    
                                    <?php elseif ($f['field_type'] === 'checkbox'): ?>
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php
                                            $opts = explode(',', $f['field_options']);
                                            foreach ($opts as $o) {
                                                $o = trim($o);
                                                echo "
                                                    <label style=\"cursor:pointer; display:flex; align-items:center; gap:0.5rem;\">
                                                        <input type=\"checkbox\" name=\"custom[{$f['id']}][]\" value=\"" . htmlspecialchars($o) . "\" style=\"width:16px; height:16px;\"> " . htmlspecialchars($o) . "
                                                    </label>
                                                ";
                                            }
                                            ?>
                                        </div>

                                    <?php elseif ($f['field_type'] === 'file' || $f['field_type'] === 'image'): ?>
                                        <input type="file" name="custom[<?php echo $f['id']; ?>]" class="form-control py-2" <?php echo $f['is_required'] ? 'required' : ''; ?> accept="<?php echo ($f['field_type'] === 'image') ? 'image/png, image/jpeg' : '*/*'; ?>">
                                    <?php endif; ?>

                                    <?php if ($f['help_text']): ?>
                                        <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($f['help_text']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 3. Payment Instructions (If registration fee is configured > 0) -->
                <div>
                    <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: #081B4B;"><i class="fa-solid fa-credit-card"></i> Payment Checkout</h5>
                    
                    <?php if ($event['registration_fee'] > 0): ?>
                        <div class="alert alert-info border-0 mb-4" style="background: rgba(8, 27, 75, 0.05); border-radius: 12px; padding: 1.5rem;">
                            <h6 class="fw-bold text-navy mb-3">Bank Transfer Payment Instructions</h6>
                            <p style="font-size: 0.95rem;" class="mb-3">Please transfer the registration fee of <strong>₹<?php echo number_format($event['registration_fee'], 2); ?></strong> to the federation's official bank account:</p>
                            
                            <table class="table table-borderless align-middle m-0" style="font-size: 0.92rem;">
                                <tr><td class="py-1 text-secondary fw-semibold">Bank Name:</td><td class="py-1 text-dark fw-bold"><?php echo htmlspecialchars($bank['payment_bank_name'] ?? ''); ?></td></tr>
                                <tr><td class="py-1 text-secondary fw-semibold">Account Name:</td><td class="py-1 text-dark fw-bold"><?php echo htmlspecialchars($bank['payment_account_name'] ?? ''); ?></td></tr>
                                <tr><td class="py-1 text-secondary fw-semibold">Account Number:</td><td class="py-1 text-dark fw-bold"><?php echo htmlspecialchars($bank['payment_account_number'] ?? ''); ?></td></tr>
                                <tr><td class="py-1 text-secondary fw-semibold">IFSC Code:</td><td class="py-1 text-dark fw-bold"><?php echo htmlspecialchars($bank['payment_ifsc_code'] ?? ''); ?></td></tr>
                                <tr><td class="py-1 text-secondary fw-semibold">Branch Address:</td><td class="py-1 text-dark"><?php echo htmlspecialchars($bank['payment_branch'] ?? ''); ?></td></tr>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tx_ref" class="form-label fw-semibold">Transaction Reference Number <span style="color:var(--danger)">*</span></label>
                                <input type="text" id="tx_ref" name="transaction_reference" class="form-control py-2" required placeholder="e.g. UTR / IMPS ref no.">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="receipt" class="form-label fw-semibold">Upload Payment Receipt (PDF / Image) <span style="color:var(--danger)">*</span></label>
                                <input type="file" id="receipt" name="payment_receipt" class="form-control py-2" accept="application/pdf, image/png, image/jpeg" required>
                                <small class="text-muted d-block mt-1">Allowed file types: PDF, JPG, PNG. Max size: 5MB.</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 m-0" style="border-radius:12px;">
                            <i class="fa-solid fa-circle-check me-2"></i> This tournament event has <strong>No Registration Fee</strong> (Free). You can submit without uploading receipts.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn text-white fw-bold py-2.5 w-100" style="background: #081B4B; border-radius: 8px; font-size: 1.1rem;">Submit Tournament Registration</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- STEP 3: SUCCESS COMPLETED SCREEN -->
    <?php if ($step == 3): ?>
        <div class="card shadow-sm border-0 text-center" style="border-radius: 16px; overflow: hidden;">
            <div class="card-body p-5">
                <div style="font-size: 4rem; color: #10B981; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i></div>
                <h3 style="color: #081B4B; font-weight: 800; margin-bottom: 1rem;">Registration Submitted</h3>
                <p class="text-secondary mb-4">Your registration request and transaction reference have been safely received by the federation and are currently under verification checks.</p>
                <a href="index.php" class="btn btn-navy text-white fw-bold px-4 py-2.5" style="background: #081B4B; border-radius: 8px;">Return to Home</a>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
