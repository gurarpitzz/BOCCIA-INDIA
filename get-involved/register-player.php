<?php
// get-involved/register-player.php - Safe player membership intake form with split-card layout
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
$error = '';

// Load dynamic fields configuration
$fieldsJson = file_get_contents(__DIR__ . '/../includes/membership_fields.json');
$fields = json_decode($fieldsJson, true);

// Helper function to check player duplicate with weighted scoring
function checkPlayerDuplicate($pdo, $name, $dob, $email, $phone, $aadhaar) {
    $stmt = $pdo->query("SELECT id, regn_no, full_name, dob, email, mobile, aadhaar FROM athletes WHERE status = 'approved' AND deleted_at IS NULL");
    $athletes = $stmt->fetchAll();
    
    $bestMatchId = null;
    $highestScore = 0;
    
    foreach ($athletes as $ath) {
        $score = 0;
        
        // Aadhaar Match (100 pts)
        if (!empty($aadhaar) && !empty($ath['aadhaar']) && $aadhaar === $ath['aadhaar']) {
            $score += 100;
        }
        
        // Phone Match (40 pts)
        if (!empty($phone) && !empty($ath['mobile']) && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $ath['mobile'])) {
            $score += 40;
        }
        
        // Email Match (30 pts)
        if (!empty($email) && !empty($ath['email']) && strtolower(trim($email)) === strtolower(trim($ath['email']))) {
            $score += 30;
        }
        
        // DOB Match (20 pts)
        if (!empty($dob) && !empty($ath['dob']) && $dob === $ath['dob']) {
            $score += 20;
        }
        
        // Name similarity using levenshtein distance (10 pts)
        if (!empty($name) && !empty($ath['full_name'])) {
            $n1 = strtolower(trim($name));
            $n2 = strtolower(trim($ath['full_name']));
            if ($n1 === $n2) {
                $score += 10;
                
                // If both Name and DOB match, boost score to 60 to guarantee duplicate detection (threshold is 50)
                if (!empty($dob) && !empty($ath['dob']) && $dob === $ath['dob']) {
                    $score += 30; // 20 (dob) + 10 (name) + 30 (boost) = 60
                }
            } else {
                $lev = levenshtein($n1, $n2);
                $maxLen = max(strlen($n1), strlen($n2));
                if ($maxLen > 0 && ($lev / $maxLen) < 0.25) {
                    $score += 8;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    $files = [];
    $isValid = true;

    // Sanitize and validate inputs
    foreach ($fields as $field) {
        $name = $field['name'];
        if ($field['type'] === 'file') {
            if (empty($_FILES[$name]['name']) && $field['required']) {
                $error = "File upload for '{$field['label']}' is required.";
                $isValid = false;
                break;
            } elseif (!empty($_FILES[$name]['name'])) {
                // File Size Check (Limit to 5MB)
                if ($_FILES[$name]['size'] > 5 * 1024 * 1024) {
                    $error = "File size of '{$field['label']}' exceeds the maximum allowed limit of 5MB.";
                    $isValid = false;
                    break;
                }

                // Extension verification
                $filename = $_FILES[$name]['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'png', 'jpg', 'jpeg'];
                
                if (!in_array($ext, $allowedExts)) {
                    $error = "Invalid file extension for '{$field['label']}'. Only PDF, PNG, JPG, and JPEG files are allowed.";
                    $isValid = false;
                    break;
                }

                // MIME Type Verification
                $tempFile = $_FILES[$name]['tmp_name'];
                $mime = mime_content_type($tempFile);
                $allowedMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
                if (!in_array($mime, $allowedMimes)) {
                    $error = "Invalid file content for '{$field['label']}'. The file content does not match allowed types.";
                    $isValid = false;
                    break;
                }

                // Extra safety: Check if image has valid dimensions
                if (in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $imgSize = getimagesize($tempFile);
                    if ($imgSize === false) {
                        $error = "Uploaded file '{$field['label']}' is not a valid image.";
                        $isValid = false;
                        break;
                    }
                }
                
                $files[$name] = $_FILES[$name];
            }
        } else {
            $val = trim($_POST[$name] ?? '');
            if ($field['required'] && empty($val)) {
                $error = "Field '{$field['label']}' is required.";
                $isValid = false;
                break;
            }
            // Whitelist/Sanitize inputs
            if ($field['type'] === 'select' && !in_array($val, $field['options'])) {
                $error = "Invalid option selected for '{$field['label']}'.";
                $isValid = false;
                break;
            }
            $data[$name] = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
        }
    }

    if ($isValid) {
        try {
            // Process file uploads securely
            $uploadedPaths = [];
            $uploadDir = __DIR__ . '/../uploads/memberships/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($files as $name => $fileInfo) {
                $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
                $secureName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $secureName;
                
                if (move_uploaded_file($fileInfo['tmp_name'], $destPath)) {
                    $uploadedPaths[$name] = 'uploads/memberships/' . $secureName;
                }
            }

            // Check for duplicates
            $dupResult = checkPlayerDuplicate($pdo, $data['full_name'], $data['dob'], $data['email'], $data['phone'], $data['aadhaar']);

            // Save details to athlete_applications
            $ins = $pdo->prepare("INSERT INTO athlete_applications (full_name, gender, dob, father_name, mother_name, age_category, state, district, impairment_type, classification, wheelchair_status, aadhaar, phone, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, photo_path, receipt_path, status, existing_athlete_id, possible_duplicate, duplicate_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
            
            $ins->execute([
                $data['full_name'],
                $data['gender'],
                $data['dob'],
                $data['father_name'],
                $data['mother_name'],
                $data['age_category'],
                $data['state'],
                $data['pincode'], // Pincode mapped to district field historically or empty
                $data['impairment_type'],
                $data['classification'],
                $data['wheelchair_status'],
                $data['aadhaar'],
                $data['phone'],
                $data['email'],
                $data['address'],
                $data['pincode'],
                $data['kit_tshirt'],
                $data['kit_tracksuit'],
                $data['kit_shoe'],
                $uploadedPaths['photo_path'] ?? null,
                $uploadedPaths['receipt_path'] ?? null,
                $dupResult['athlete_id'],
                $dupResult['is_duplicate'] ? 1 : 0,
                $dupResult['score']
            ]);

            $message = "Your Player Membership application has been submitted successfully! It is currently under review.";
            
            // Log activity
            $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('Submit Player Membership Application', ?)");
            $log->execute(["Player membership registration application submitted for: " . $data['full_name']]);
        } catch (\Exception $e) {
            $error = "Database error saving application: " . $e->getMessage();
        }
    }
}

$page_title = "Online Player Registration - Boccia India";
include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-green: #10B981;
    --boccia-accent-red: #E10600;
    --boccia-maroon: #8C201C;
    --boccia-text-dark: #1E293B;
    --boccia-text-muted: #64748B;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

.outer-registration-bg {
    background-color: var(--boccia-maroon);
    padding: 80px 0;
    min-height: 90vh;
    display: flex;
    align-items: center;
}

.split-card-container {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    display: flex;
    width: 100%;
    min-height: 850px;
}

.split-card-left {
    width: 45%;
    background: url('../about boccia/hero bg.png') no-repeat right center;
    background-size: cover;
    position: relative;
}

.split-card-right {
    width: 55%;
    padding: 50px;
    display: flex;
    flex-direction: column;
}

@media (max-width: 991px) {
    .outer-registration-bg {
        padding: 40px 0;
    }
    .split-card-container {
        flex-direction: column;
        min-height: auto;
    }
    .split-card-left {
        width: 100%;
        height: 200px;
        background-position: center center;
    }
    .split-card-right {
        width: 100%;
        padding: 30px 20px;
    }
}

@media (max-width: 767px) {
    .split-card-right {
        padding: 25px 15px;
    }
    .form-header-box {
        margin-bottom: 25px;
    }
    .form-title-text {
        font-size: 1.4rem;
    }
    .form-label-custom {
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    .form-control-custom, .form-select-custom {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    .btn-submit-custom {
        width: 100%;
        text-align: center;
        padding: 12px 20px;
    }
}

.form-header-box {
    text-align: center;
    margin-bottom: 40px;
}

.back-home-link {
    color: var(--boccia-maroon);
    font-family: var(--font-heading-sub);
    font-weight: 600;
    text-decoration: none;
    font-size: 1.05rem;
    display: inline-block;
    margin-bottom: 25px;
    transition: color 0.3s ease;
}

.back-home-link:hover {
    color: var(--boccia-navy);
}

.form-logo-img {
    max-height: 80px;
    width: auto;
    margin-bottom: 20px;
}

.form-title-text {
    font-family: var(--font-heading-sub);
    font-weight: 800;
    color: var(--boccia-maroon);
    font-size: 1.8rem;
    margin: 0;
}

/* Custom form elements styled exactly like reference */
.form-label-custom {
    font-family: var(--font-body-custom);
    font-weight: 600;
    color: var(--boccia-text-dark);
    font-size: 0.95rem;
    margin-bottom: 8px;
    display: block;
}

.form-control-custom, .form-select-custom {
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 0.95rem;
    background-color: #ffffff;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control-custom:focus, .form-select-custom:focus {
    border-color: var(--boccia-maroon);
    outline: none;
    box-shadow: 0 0 0 3px rgba(140, 32, 28, 0.15);
}

.form-field-info-text {
    font-size: 0.82rem;
    color: #ef4444;
    margin-top: 4px;
    font-weight: 500;
}

.btn-submit-custom {
    font-family: var(--font-heading-sub);
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #ffffff;
    background-color: var(--boccia-maroon);
    border: none;
    padding: 12px 40px;
    border-radius: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-submit-custom:hover {
    background-color: var(--boccia-navy);
}
</style>

<div class="outer-registration-bg">
    <div class="container" style="max-width: 1200px;">
        <div class="split-card-container scroll-reveal">
            
            <!-- Left Side Image Column -->
            <div class="split-card-left"></div>

            <!-- Right Side Form Column -->
            <div class="split-card-right">
                
                <div class="form-header-box">
                    <a href="membership.php" class="back-home-link">Back to HOME Page</a>
                    <div>
                        <img src="../boccia-india-logo.webp" alt="BSFI Logo" class="form-logo-img">
                    </div>
                    <h2 class="form-title-text">Player Registration Form</h2>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success border-0 p-4 mb-4 rounded-3" style="background-color: #ECFDF5; color: #065F46;">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $message; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="membership.php" class="btn btn-outline-primary rounded-pill px-4">Back to Membership Portal</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background-color: #FEF2F2; color: #991B1B;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($message)): ?>
                    <form action="register-player.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <?php foreach ($fields as $field): 
                                $name = $field['name'];
                                $label = $field['label'];
                                $required = $field['required'] ? 'required' : '';
                            ?>
                                <div class="col-md-12">
                                    <label class="form-label-custom"><?php echo htmlspecialchars($label); ?> <?php echo $field['required'] ? '<span class="text-danger">*</span>' : ''; ?></label>
                                    
                                    <?php if ($field['type'] === 'select'): ?>
                                        <select name="<?php echo $name; ?>" class="form-select-custom" <?php echo $required; ?>>
                                            <option value="">Select option</option>
                                            <?php foreach ($field['options'] as $opt): ?>
                                                <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($field['type'] === 'textarea'): ?>
                                        <textarea name="<?php echo $name; ?>" class="form-control-custom" rows="2" <?php echo $required; ?>></textarea>
                                    <?php elseif ($field['type'] === 'file'): ?>
                                        <input type="file" name="<?php echo $name; ?>" class="form-control-custom" <?php echo $required; ?>>
                                        <?php if ($name === 'photo_path'): ?>
                                            <div class="form-field-info-text">*Choose passport size (Face) photo only</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="<?php echo $field['type']; ?>" name="<?php echo $name; ?>" class="form-control-custom" <?php echo $required; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-5">
                            <button type="submit" class="btn-submit-custom">Submit Application</button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
