<?php
// api/official-registration.php - Secure AJAX endpoint for Official Registration intake

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

// Ensure email is verified in the session
$email = trim($_POST['email'] ?? '');
if (empty($email) || ($_SESSION['verified_email'] ?? '') !== $email) {
    http_response_code(403);
    echo json_encode(['error' => 'Email verification is required before submitting.']);
    exit();
}

// Helper to check duplicates
function checkOfficialDuplicate($pdo, $name, $dob, $email, $phone, $aadhaar) {
    $stmt = $pdo->query("SELECT id, name, dob, email, phone, aadhaar FROM officials WHERE status = 'approved' AND deleted_at IS NULL");
    $officials = $stmt->fetchAll();
    
    $bestMatchId = null;
    $highestScore = 0;
    
    foreach ($officials as $off) {
        $score = 0;
        
        if (!empty($aadhaar) && !empty($off['aadhaar']) && $aadhaar === $off['aadhaar']) {
            $score += 100;
        }
        if (!empty($phone) && !empty($off['phone']) && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $off['phone'])) {
            $score += 40;
        }
        if (!empty($email) && !empty($off['email']) && strtolower(trim($email)) === strtolower(trim($off['email']))) {
            $score += 30;
        }
        if (!empty($dob) && !empty($off['dob']) && $dob === $off['dob']) {
            $score += 20;
        }
        if (!empty($name) && !empty($off['name'])) {
            $n1 = strtolower(trim($name));
            $n2 = strtolower(trim($off['name']));
            if ($n1 === $n2) {
                $score += 10;
                if (!empty($dob) && !empty($off['dob']) && $dob === $off['dob']) {
                    $score += 30;
                }
            }
        }
        
        if ($score > $highestScore) {
            $highestScore = $score;
            $bestMatchId = $off['id'];
        }
    }
    
    return [
        'is_duplicate' => ($highestScore >= 50),
        'score' => $highestScore,
        'official_id' => $bestMatchId
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
    $role = trim($_POST['role'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $father_name = trim($_POST['father_name'] ?? ''); // Father's / Spouse's Name
    $phone = trim($_POST['phone'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $kit_tshirt = trim($_POST['kit_tshirt'] ?? '');
    $kit_tracksuit = trim($_POST['kit_tracksuit'] ?? '');
    $kit_shoe = trim($_POST['kit_shoe'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? ''); // Aadhaar / Govt ID

    // Files
    $photo = $_FILES['photo_path'] ?? null;
    $doc = $_FILES['receipt_path'] ?? null;

    if (empty($full_name) || empty($role) || empty($gender) || empty($dob) || empty($father_name) || empty($phone) || empty($photo['name']) || empty($doc['name']) || empty($aadhaar)) {
        http_response_code(400);
        echo json_encode(['error' => 'Required registration fields are missing.']);
        exit();
    }

    if (!preg_match('/^\d{12}$/', $aadhaar)) {
        http_response_code(400);
        echo json_encode(['error' => 'Aadhaar / Government ID must be exactly 12 digits.']);
        exit();
    }

    // Validate Photo
    $photoExt = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    $photoMime = function_exists('mime_content_type') ? mime_content_type($photo['tmp_name']) : $photo['type'];
    if (!in_array($photoExt, ['jpg', 'jpeg', 'png']) || !in_array($photoMime, ['image/jpeg', 'image/png'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid passport photo file type (only JPG/PNG allowed).']);
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
        echo json_encode(['error' => 'Invalid government ID file type (only JPG/PNG/PDF allowed).']);
        exit();
    }
    if ($doc['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Government ID proof size must be less than 10MB.']);
        exit();
    }

    $photoUuidName = generateUUID() . '.' . $photoExt;
    $docUuidName = generateUUID() . '.' . $docExt;

    // Start Transaction
    $pdo->beginTransaction();

    $dupResult = checkOfficialDuplicate($pdo, $full_name, $dob, $email, $phone, $aadhaar);
    $appUuid = generateUUID();

    // Insert to get the insertId (using unique appUuid temporarily as reference_id to avoid UNIQUE constraint clashes)
    $stmt = $pdo->prepare("INSERT INTO official_applications (
        application_uuid, reference_id, full_name, role, gender, dob, father_name, 
        state, aadhaar, phone, email, address, pincode, 
        kit_tshirt, kit_tracksuit, kit_shoe, status, existing_official_id, 
        possible_duplicate, duplicate_score
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");

    $stmt->execute([
        $appUuid, $appUuid, $full_name, $role, $gender, $dob, $father_name,
        $state, $aadhaar, $phone, $email, $address, $pincode,
        $kit_tshirt, $kit_tracksuit, $kit_shoe, $dupResult['official_id'],
        $dupResult['is_duplicate'] ? 1 : 0, $dupResult['score']
    ]);

    $appId = $pdo->lastInsertId();
    $referenceId = 'BSFI-OFF-2026-' . str_pad($appId, 6, '0', STR_PAD_LEFT);

    // Save files
    $photoDir = UPLOAD_PATH . 'officials/photos/';
    $docDir = UPLOAD_PATH . 'officials/documents/';

    if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    if (!move_uploaded_file($photo['tmp_name'], $photoDir . $photoUuidName)) {
        throw new Exception("Failed to write passport photograph upload.");
    }
    if (!move_uploaded_file($doc['tmp_name'], $docDir . $docUuidName)) {
        throw new Exception("Failed to write government ID proof upload.");
    }

    $photoPath = 'uploads/officials/photos/' . $photoUuidName;
    $receiptPath = 'uploads/officials/documents/' . $docUuidName;

    // Update with generated reference_id and file paths
    $upd = $pdo->prepare("UPDATE official_applications SET reference_id = ?, photo_path = ?, receipt_path = ? WHERE id = ?");
    $upd->execute([$referenceId, $photoPath, $receiptPath, $appId]);

    $pdo->commit();

    // Log action to activity_logs
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['Official Registration Submitted', "Application Reference: {$referenceId} for Name: {$full_name}"]);

    // Send confirmation email via Resend
    $htmlBody = "
      <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
        <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
        <p>Dear {$full_name},</p>
        <p>Thank you for submitting your Official/Coach Registration application. It is currently under review.</p>
        <p>Your application reference details:</p>
        <div style=\"background: #f1f5f9; padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 16px;\">
          <strong>Reference ID:</strong> {$referenceId}<br/>
          <strong>Tracking URL:</strong> <a href=\"https://bocciaindia.com/get-involved/status.php?id={$referenceId}&email=" . urlencode($email) . "\" style=\"color: #FF9933;\">Check Status</a>
        </div>
        <p>Please keep this Reference ID for all future communications.</p>
      </div>
    ";

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'from' => 'Boccia India <noreply@bocciaindia.com>',
        'to' => $email,
        'subject' => 'Official Registration Application Received - BSFI',
        'html' => $htmlBody
    ]));
    curl_exec($ch);
    curl_close($ch);

    // Clear verification session variable
    unset($_SESSION['verified_email']);

    echo json_encode(['success' => true, 'reference_id' => $referenceId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Registration processing failed: ' . $e->getMessage()]);
}
