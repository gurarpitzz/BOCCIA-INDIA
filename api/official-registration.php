<?php
// api/official-registration.php - Secure AJAX endpoint for Official Registration intake with category validation
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

// Validate CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session has expired due to inactivity. Please refresh the page and try submitting again.']);
    exit();
}

// Honeypot check
$website_url = trim($_POST['website_url'] ?? '');
if (!empty($website_url)) {
    echo json_encode(['success' => true, 'reference_id' => 'BSFI-OFF-2026-' . str_pad(random_int(1000, 9999), 6, '0', STR_PAD_LEFT)]);
    exit();
}

// Ensure email is verified in the session
$email = trim($_POST['email'] ?? '');
if (empty($email) || ($_SESSION['verified_email_register_official'] ?? '') !== $email) {
    http_response_code(403);
    echo json_encode(['error' => 'Email verification is required before submitting.']);
    exit();
}

// Helper to normalize names (strip punctuation, hyphens, extra whitespace, lowercase)
if (!function_exists('normalizeName')) {
    function normalizeName($name) {
        if (empty($name)) return '';
        $clean = preg_replace('/[.\-,_\'\"]+/u', ' ', $name);
        return strtolower(trim(preg_replace('/\s+/', ' ', $clean)));
    }
}

// Helper to compute DOB similarity score (exact or close birthdate within 15 days)
if (!function_exists('computeDobSimilarityScore')) {
    function computeDobSimilarityScore($dob1, $dob2) {
        if (empty($dob1) || empty($dob2)) return 0;
        if ($dob1 === $dob2) return 20;

        $time1 = strtotime($dob1);
        $time2 = strtotime($dob2);
        if ($time1 && $time2) {
            $dayDiff = abs($time1 - $time2) / 86400;
            if ($dayDiff <= 15) {
                return 10;
            }
        }
        return 0;
    }
}

// Helper to check duplicates
function checkOfficialDuplicate($pdo, $name, $dob, $email, $phone, $aadhaar) {
    $stmt = $pdo->query("SELECT id, name, dob, email, phone, aadhaar FROM officials WHERE status = 'approved' AND deleted_at IS NULL");
    $officials = $stmt->fetchAll();
    
    $bestMatchId = null;
    $highestScore = 0;
    $normNameInput = normalizeName($name);
    
    foreach ($officials as $off) {
        $score = 0;
        
        // 1. Aadhaar Match (100 pts)
        if (!empty($aadhaar) && !empty($off['aadhaar']) && $aadhaar === $off['aadhaar']) {
            $score += 100;
        }
        
        // 2. Phone Match (40 pts)
        if (!empty($phone) && !empty($off['phone']) && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $off['phone'])) {
            $score += 40;
        }
        
        // 3. Email Match (30 pts)
        if (!empty($email) && !empty($off['email']) && strtolower(trim($email)) === strtolower(trim($off['email']))) {
            $score += 30;
        }
        
        // 4. DOB Match & Proximity Score (10 - 20 pts)
        $dobScore = function_exists('computeDobSimilarityScore') ? computeDobSimilarityScore($dob, $off['dob']) : ($dob === $off['dob'] ? 20 : 0);
        $score += $dobScore;
        
        // 5. Normalized & Fuzzy Name Scoring
        if (!empty($normNameInput) && !empty($off['name'])) {
            $normOffName = function_exists('normalizeName') ? normalizeName($off['name']) : strtolower(trim($off['name']));
            
            if (!empty($normOffName)) {
                if ($normNameInput === $normOffName) {
                    $score += 30;
                    if ($dobScore === 20) {
                        $score += 20; // Direct Combo Bonus
                    }
                } else {
                    // Fuzzy matching: Levenshtein distance & Similar Text
                    $lev = levenshtein($normNameInput, $normOffName);
                    similar_text($normNameInput, $normOffName, $percent);
                    
                    if ($lev <= 2 || $percent >= 85) {
                        $score += 25;
                    } elseif ($lev <= 4 || $percent >= 75) {
                        $score += 15;
                    }
                    
                    // Metaphone Phonetic match
                    $m1 = metaphone($normNameInput);
                    $m2 = metaphone($normOffName);
                    if (!empty($m1) && !empty($m2) && $m1 === $m2) {
                        $score += 15;
                    }
                    
                    // Word Token & Substring Overlap
                    $w1 = array_filter(explode(' ', $normNameInput), fn($w) => strlen($w) > 1);
                    $w2 = array_filter(explode(' ', $normOffName), fn($w) => strlen($w) > 1);
                    if (!empty($w1) && !empty($w2)) {
                        $matches = 0;
                        foreach ($w1 as $word1) {
                            foreach ($w2 as $word2) {
                                if ($word1 === $word2 || strpos($word2, $word1) !== false || strpos($word1, $word2) !== false || (strlen($word1) > 3 && strlen($word2) > 3 && levenshtein($word1, $word2) <= 1)) {
                                    $matches++;
                                    break;
                                }
                            }
                        }
                        $overlapRatio = $matches / max(count($w1), count($w2));
                        if ($overlapRatio >= 0.66) {
                            $score += 20;
                        } elseif ($matches >= 2) {
                            $score += 10;
                        }
                    }
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
    $category = trim($_POST['category'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $kit_tshirt = trim($_POST['kit_tshirt'] ?? '');
    $kit_tracksuit = trim($_POST['kit_tracksuit'] ?? '');
    $kit_shoe = trim($_POST['kit_shoe'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');

    // Category specifics
    $education_qualification = trim($_POST['education_qualification'] ?? '');
    $para_sports_experience = trim($_POST['para_sports_experience'] ?? '');
    $classifier_type = trim($_POST['classifier_type'] ?? '');
    $classifier_type_other = trim($_POST['classifier_type_other'] ?? '');

    // Files
    $photo = $_FILES['photo_path'] ?? null;
    $doc = $_FILES['receipt_path'] ?? null;
    $passport = $_FILES['passport_file'] ?? null;

    // Validate Category
    $allowedCategories = ['Coach', 'Referee', 'Volunteer', 'Classifier', 'Ramp Operator / Sports Assistant', 'Escort'];
    if (!in_array($category, $allowedCategories)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or unsupported registration category.']);
        exit();
    }

    // Common validations
    if (empty($full_name) || empty($gender) || empty($dob) || empty($father_name) || empty($phone) || empty($state) || empty($address) || empty($pincode) || empty($aadhaar) || empty($kit_tshirt) || empty($kit_tracksuit) || empty($kit_shoe)) {
        http_response_code(400);
        echo json_encode(['error' => 'Required registration fields are missing.']);
        exit();
    }

    if (!preg_match('/^\d{12}$/', $aadhaar)) {
        http_response_code(400);
        echo json_encode(['error' => 'Aadhaar Card number must be exactly 12 digits.']);
        exit();
    }

    // Category-specific validations
    if (in_array($category, ['Coach', 'Referee', 'Classifier'])) {
        if (empty($education_qualification)) {
            http_response_code(400);
            echo json_encode(['error' => "Educational Qualification is required for {$category} registrations."]);
            exit();
        }
    } else {
        $education_qualification = null;
    }

    if (in_array($category, ['Coach', 'Referee', 'Volunteer', 'Classifier'])) {
        if (empty($para_sports_experience)) {
            http_response_code(400);
            echo json_encode(['error' => "Experience in Para Sports is required for {$category} registrations."]);
            exit();
        }
    }

    if ($category === 'Classifier') {
        $allowedClassifierTypes = ['Physio', 'Doctor', 'Coach', 'Other'];
        if (!in_array($classifier_type, $allowedClassifierTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Please select a valid Classifier Type.']);
            exit();
        }
        if ($classifier_type === 'Other' && empty($classifier_type_other)) {
            http_response_code(400);
            echo json_encode(['error' => 'Please specify the custom Classifier Type.']);
            exit();
        }
        if ($classifier_type !== 'Other') {
            $classifier_type_other = null;
        }
    } else {
        $classifier_type = null;
        $classifier_type_other = null;
    }

    // Passport / Identity Proof Document requirement (Mandatory for all categories)
    if (empty($passport['name']) || $passport['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => "Passport / Identity Proof document is required for {$category} registrations."]);
        exit();
    }

    // Validate Photo File
    if (empty($photo['name']) || $photo['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Passport-size photo file is required.']);
        exit();
    }
    $photoExt = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    $photoMime = function_exists('mime_content_type') ? mime_content_type($photo['tmp_name']) : $photo['type'];
    if (!in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp']) || !in_array($photoMime, ['image/jpeg', 'image/png', 'image/webp'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid photo file type (only JPG/PNG/WebP allowed).']);
        exit();
    }
    if ($photo['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Passport photo size must be less than 5MB.']);
        exit();
    }

    // Validate Identity Doc
    if (empty($doc['name']) || $doc['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Government ID proof document scan is required.']);
        exit();
    }
    $docExt = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
    $docMime = function_exists('mime_content_type') ? mime_content_type($doc['tmp_name']) : $doc['type'];
    if (!in_array($docExt, ['jpg', 'jpeg', 'png', 'pdf']) || !in_array($docMime, ['image/jpeg', 'image/png', 'application/pdf'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID document file type (only JPG/PNG/PDF allowed).']);
        exit();
    }
    if ($doc['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Government ID proof document size must be less than 10MB.']);
        exit();
    }

    // Validate Passport Document (if provided)
    $passportUuidName = null;
    $passportPath = null;
    if (!empty($passport['name']) && $passport['error'] === UPLOAD_ERR_OK) {
        $passExt = strtolower(pathinfo($passport['name'], PATHINFO_EXTENSION));
        $passMime = function_exists('mime_content_type') ? mime_content_type($passport['tmp_name']) : $passport['type'];
        if (!in_array($passExt, ['jpg', 'jpeg', 'png', 'pdf']) || !in_array($passMime, ['image/jpeg', 'image/png', 'application/pdf'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid passport document file type (only JPG/PNG/PDF allowed).']);
            exit();
        }
        if ($passport['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Passport document booklet size must be less than 10MB.']);
            exit();
        }
        $passportUuidName = generateUUID() . '.' . $passExt;
        $passportPath = 'uploads/officials/documents/' . $passportUuidName;
    }

    // Verify email uniqueness against live and pending databases
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
        echo json_encode(['error' => 'This email address is already registered to an official.']);
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

    $dupResult = checkOfficialDuplicate($pdo, $full_name, $dob, $email, $phone, $aadhaar);
    $appUuid = generateUUID();

    // Map selected category to role as well for backward compatibility
    $role = $category;

    // Insert to get the insertId
    $stmt = $pdo->prepare("INSERT INTO official_applications (
        application_uuid, reference_id, full_name, category, role, gender, dob, father_name, 
        state, aadhaar, phone, email, address, pincode, 
        kit_tshirt, kit_tracksuit, kit_shoe, education_qualification, para_sports_experience,
        classifier_type, classifier_type_other, status, existing_official_id, 
        possible_duplicate, duplicate_score
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");

    $stmt->execute([
        $appUuid, $appUuid, $full_name, $category, $role, $gender, $dob, $father_name,
        $state, $aadhaar, $phone, $email, $address, $pincode,
        $kit_tshirt, $kit_tracksuit, $kit_shoe, $education_qualification, $para_sports_experience,
        $classifier_type, $classifier_type_other, $dupResult['official_id'],
        $dupResult['is_duplicate'] ? 1 : 0, $dupResult['score']
    ]);

    $appId = $pdo->lastInsertId();
    $referenceId = 'BSFI-OFF-2026-' . str_pad($appId, 6, '0', STR_PAD_LEFT);

    // Save files
    $photoDir = UPLOAD_PATH . 'officials/photos/';
    $docDir = UPLOAD_PATH . 'officials/documents/';

    if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    $photoUuidName = generateUUID() . '.webp';
    $docUuidName = generateUUID() . '.' . $docExt;

    // Convert and save photo
    $imgRes = false;
    if (in_array($photoExt, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
        $imgRes = imagecreatefromjpeg($photo['tmp_name']);
    } elseif ($photoExt === 'png' && function_exists('imagecreatefrompng')) {
        $imgRes = imagecreatefrompng($photo['tmp_name']);
    } elseif (function_exists('imagecreatefromwebp')) {
        $imgRes = imagecreatefromwebp($photo['tmp_name']);
    }

    if ($imgRes && function_exists('imagewebp') && imagewebp($imgRes, $photoDir . $photoUuidName, 85)) {
        imagedestroy($imgRes);
    } else {
        // Fallback if GD is missing or conversion failed: move raw file directly
        $photoUuidName = generateUUID() . '.' . $photoExt;
        if (!move_uploaded_file($photo['tmp_name'], $photoDir . $photoUuidName)) {
            throw new Exception("Failed to process and save passport photograph.");
        }
    }

    // Save Identity Doc
    if (!move_uploaded_file($doc['tmp_name'], $docDir . $docUuidName)) {
        throw new Exception("Failed to write government ID proof upload.");
    }

    // Save Passport Document
    if ($passportUuidName !== null && $passportPath !== null) {
        if (!move_uploaded_file($passport['tmp_name'], $docDir . $passportUuidName)) {
            throw new Exception("Failed to write passport document booklet copy upload.");
        }
    }

    $photoPath = 'uploads/officials/photos/' . $photoUuidName;
    $receiptPath = 'uploads/officials/documents/' . $docUuidName;

    // Update with generated reference_id and file paths
    $upd = $pdo->prepare("UPDATE official_applications SET reference_id = ?, photo_path = ?, receipt_path = ?, passport_file = ? WHERE id = ?");
    $upd->execute([$referenceId, $photoPath, $receiptPath, $passportPath, $appId]);

    $pdo->commit();

    // Log action to activity_logs
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['Official Registration Submitted', "Application Reference: {$referenceId} for Category: {$category} - {$full_name}"]);

    // Send confirmation email
    $htmlBody = "
      <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
        <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
        <p>Dear {$full_name},</p>
        <p>Thank you for submitting your Official Registration application. It is currently under review.</p>
        <p>Your application reference details:</p>
        <div style=\"background: #f1f5f9; padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 16px;\">
          <strong>Category:</strong> {$category}<br/>
          <strong>Reference ID:</strong> {$referenceId}<br/>
          <strong>Tracking URL:</strong> <a href=\"https://bocciaindia.com/get-involved/status.php?id={$referenceId}&email=" . urlencode($email) . "\" style=\"color: #FF9933;\">Check Status</a>
        </div>
        <p>Please keep this Reference ID for all future communications.</p>
      </div>
    ";

    sendEmail($email, 'Official Registration Application Received - BSFI', $htmlBody);

    // Clear verification session variable
    unset($_SESSION['verified_email_register_official']);

    echo json_encode(['success' => true, 'reference_id' => $referenceId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Registration processing failed: ' . $e->getMessage()]);
}
