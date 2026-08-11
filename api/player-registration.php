<?php
// api/player-registration.php - Secure AJAX endpoint for Player Registration intake

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

// Validate CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Security token validation failed (CSRF).']);
    exit();
}

// Honeypot check
$website_url = trim($_POST['website_url'] ?? '');
if (!empty($website_url)) {
    // Return fake generic success response to trick the bot
    echo json_encode(['success' => true, 'reference_id' => 'BSFI-ATH-2026-' . str_pad(random_int(1000, 9999), 6, '0', STR_PAD_LEFT)]);
    exit();
}

// Ensure email is verified in the session
$email = trim($_POST['email'] ?? '');
if (empty($email) || ($_SESSION['verified_email_register_player'] ?? '') !== $email) {
    http_response_code(403);
    echo json_encode(['error' => 'Email verification is required before submitting.']);
    exit();
}

// Helper to check duplicates
function checkPlayerDuplicate($pdo, $name, $dob, $email, $phone, $aadhaar) {
    $stmt = $pdo->query("SELECT id, regn_no, full_name, dob, email, mobile, aadhaar FROM athletes WHERE status = 'approved' AND deleted_at IS NULL");
    $athletes = $stmt->fetchAll();
    
    $bestMatchId = null;
    $highestScore = 0;
    
    foreach ($athletes as $ath) {
        $score = 0;
        
        if (!empty($aadhaar) && !empty($ath['aadhaar']) && $aadhaar === $ath['aadhaar']) {
            $score += 100;
        }
        if (!empty($phone) && !empty($ath['mobile']) && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $ath['mobile'])) {
            $score += 40;
        }
        if (!empty($email) && !empty($ath['email']) && strtolower(trim($email)) === strtolower(trim($ath['email']))) {
            $score += 30;
        }
        if (!empty($dob) && !empty($ath['dob']) && $dob === $ath['dob']) {
            $score += 20;
        }
        if (!empty($name) && !empty($ath['full_name'])) {
            $n1 = strtolower(trim($name));
            $n2 = strtolower(trim($ath['full_name']));
            if ($n1 === $n2) {
                $score += 10;
                if (!empty($dob) && !empty($ath['dob']) && $dob === $ath['dob']) {
                    $score += 30;
                }
            }
        }
        
        if ($score > $highestScore) {
            $highestScore = $score;
            $bestMatchId = $ath['id'];
        }
    }
    
    return [
        'is_duplicate' => ($highestScore >= 50),
        'score' => $highestScore,
        'athlete_id' => $bestMatchId
    ];
}

// Generate UUID helper
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    // Collect Form inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age_category = trim($_POST['age_category'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $impairment_type = trim($_POST['impairment_type'] ?? '');
    $classification = trim($_POST['classification'] ?? '');
    if (empty($classification)) {
        $classification = 'PENDING';
    }
    $wheelchair_status = trim($_POST['wheelchair_status'] ?? '');
    $kit_tshirt = trim($_POST['kit_tshirt'] ?? '');
    $kit_tracksuit = trim($_POST['kit_tracksuit'] ?? '');
    $kit_shoe = trim($_POST['kit_shoe'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    // Files
    $photo = $_FILES['photo_path'] ?? null;
    $doc = $_FILES['receipt_path'] ?? null;
    $med = $_FILES['medical_certificate'] ?? null;

    if (empty($full_name) || empty($gender) || empty($dob) || empty($father_name) || empty($mother_name) || empty($phone) || empty($photo['name']) || empty($doc['name']) || empty($med['name']) || empty($aadhaar)) {
        http_response_code(400);
        echo json_encode(['error' => 'Required registration fields are missing.']);
        exit();
    }

    if (!preg_match('/^\d{12}$/', $aadhaar)) {
        http_response_code(400);
        echo json_encode(['error' => 'Aadhaar number must be exactly 12 digits.']);
        exit();
    }

    // Validate Photo - accept jpg, jpeg, png, webp
    $photoExt = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    $photoMime = function_exists('mime_content_type') ? mime_content_type($photo['tmp_name']) : $photo['type'];
    if (!in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp']) || !in_array($photoMime, ['image/jpeg', 'image/png', 'image/webp'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid passport photo file type (only JPG/PNG/WebP allowed).']);
        exit();
    }
    if ($photo['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Passport photo size must be less than 5MB.']);
        exit();
    }

    // Validate Doc
    $docExt = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
    $docMime = function_exists('mime_content_type') ? mime_content_type($doc['tmp_name']) : $doc['type'];
    if (!in_array($docExt, ['jpg', 'jpeg', 'png', 'pdf']) || !in_array($docMime, ['image/jpeg', 'image/png', 'application/pdf'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid document proof file type (only JPG/PNG/PDF allowed).']);
        exit();
    }
    if ($doc['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Document proof size must be less than 10MB.']);
        exit();
    }

    // Validate Medical Certificate
    $medExt = strtolower(pathinfo($med['name'], PATHINFO_EXTENSION));
    $medMime = function_exists('mime_content_type') ? mime_content_type($med['tmp_name']) : $med['type'];
    if (!in_array($medExt, ['jpg', 'jpeg', 'png', 'pdf']) || !in_array($medMime, ['image/jpeg', 'image/png', 'application/pdf'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid medical certificate file type (only JPG/PNG/PDF allowed).']);
        exit();
    }
    if ($med['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Medical certificate size must be less than 2MB.']);
        exit();
    }

    // Always save photos as WebP for performance
    $photoUuidName = generateUUID() . '.webp';
    $docUuidName = generateUUID() . '.' . $docExt;
    $medUuidName = generateUUID() . '.' . $medExt;

    // Verify email is not already in use by any other player or official
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'This email address is already registered to a player.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM athlete_applications WHERE email = ? AND status = 'pending'");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'This email address is already in use by a pending player application.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM officials WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'This email address is already registered to an official. Players cannot register as officials.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM official_applications WHERE email = ? AND status = 'pending'");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'This email address is already in use by a pending official application.']);
        exit();
    }

    // Start Transaction
    $pdo->beginTransaction();

    $dupResult = checkPlayerDuplicate($pdo, $full_name, $dob, $email, $phone, $aadhaar);
    $appUuid = generateUUID();

    // Insert to get the insertId (using unique appUuid temporarily as reference_id to avoid UNIQUE constraint clashes)
    $stmt = $pdo->prepare("INSERT INTO athlete_applications (
        application_uuid, reference_id, full_name, gender, dob, father_name, mother_name, 
        age_category, state, district, impairment_type, classification, 
        wheelchair_status, aadhaar, phone, email, address, pincode, 
        kit_tshirt, kit_tracksuit, kit_shoe, status, existing_athlete_id, 
        possible_duplicate, duplicate_score
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");

    $stmt->execute([
        $appUuid, $appUuid, $full_name, $gender, $dob, $father_name, $mother_name,
        $age_category, $state, $pincode, $impairment_type, $classification,
        $wheelchair_status, $aadhaar, $phone, $email, $address, $pincode,
        $kit_tshirt, $kit_tracksuit, $kit_shoe, $dupResult['athlete_id'],
        $dupResult['is_duplicate'] ? 1 : 0, $dupResult['score']
    ]);

    $appId = $pdo->lastInsertId();
    $referenceId = 'BSFI-ATH-2026-' . str_pad($appId, 6, '0', STR_PAD_LEFT);

    // Save files
    $photoDir = UPLOAD_PATH . 'athletes/photos/';
    $docDir = UPLOAD_PATH . 'athletes/documents/';

    if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    // Convert photo to WebP before saving
    if (in_array($photoExt, ['jpg', 'jpeg'])) {
        $imgRes = imagecreatefromjpeg($photo['tmp_name']);
    } elseif ($photoExt === 'png') {
        $imgRes = imagecreatefrompng($photo['tmp_name']);
    } else {
        $imgRes = imagecreatefromwebp($photo['tmp_name']);
    }
    if (!$imgRes || !imagewebp($imgRes, $photoDir . $photoUuidName, 85)) {
        throw new Exception("Failed to process and save passport photograph.");
    }
    imagedestroy($imgRes);

    if (!move_uploaded_file($doc['tmp_name'], $docDir . $docUuidName)) {
        throw new Exception("Failed to write government document proof upload.");
    }

    if (!move_uploaded_file($med['tmp_name'], $docDir . $medUuidName)) {
        throw new Exception("Failed to write medical certificate upload.");
    }

    $photoPath = 'uploads/athletes/photos/' . $photoUuidName;
    $receiptPath = 'uploads/athletes/documents/' . $docUuidName;
    $medPath = 'uploads/athletes/documents/' . $medUuidName;

    // Update with generated reference_id and file paths
    $upd = $pdo->prepare("UPDATE athlete_applications SET reference_id = ?, photo_path = ?, receipt_path = ?, medical_certificate = ? WHERE id = ?");
    $upd->execute([$referenceId, $photoPath, $receiptPath, $medPath, $appId]);

    $pdo->commit();

    // Log action to activity_logs
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['Player Registration Submitted', "Application Reference: {$referenceId} for Name: {$full_name}"]);

    // Send confirmation email via Resend
    $htmlBody = "
      <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
        <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
        <p>Dear {$full_name},</p>
        <p>Thank you for submitting your Player Registration application. It is currently under review.</p>
        <p>Your application reference details:</p>
        <div style=\"background: #f1f5f9; padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 16px;\">
          <strong>Reference ID:</strong> {$referenceId}<br/>
          <strong>Tracking URL:</strong> <a href=\"https://bocciaindia.com/get-involved/status.php?id={$referenceId}&email=" . urlencode($email) . "\" style=\"color: #FF9933;\">Check Status</a>
        </div>
        <p>Please keep this Reference ID for all future communications.</p>
      </div>
    ";

    // Send acknowledgement email — acts as a submission receipt for the applicant
    sendEmail(
        $email,
        'Registration Application Received - BSFI',
        $htmlBody
    );

    // Clear verification session variable
    unset($_SESSION['verified_email_register_player']);

    echo json_encode(['success' => true, 'reference_id' => $referenceId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Registration processing failed: ' . $e->getMessage()]);
}
