<?php
// get-involved/register-official.php - Safe official/coach membership intake form with split-card layout
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
$error = '';

$official_fields = [
  [
    "name" => "full_name",
    "label" => "Full Name",
    "type" => "text",
    "required" => true
  ],
  [
    "name" => "role",
    "label" => "Registration Role",
    "type" => "select",
    "options" => ["Coach", "Sport Assistant", "Classifier", "Technical Official", "Referee", "Volunteer"],
    "required" => true
  ],
  [
    "name" => "gender",
    "label" => "Gender",
    "type" => "select",
    "options" => ["Male", "Female", "Other"],
    "required" => true
  ],
  [
    "name" => "dob",
    "label" => "Date of Birth",
    "type" => "date",
    "required" => true
  ],
  [
    "name" => "father_name",
    "label" => "Father's / Spouse's Name",
    "type" => "text",
    "required" => true
  ],
  [
    "name" => "state",
    "label" => "State / Union Territory",
    "type" => "select",
    "options" => [
      "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat",
      "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra",
      "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu",
      "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands",
      "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Jammu and Kashmir", "Ladakh",
      "Lakshadweep", "Puducherry"
    ],
    "required" => true
  ],
  [
    "name" => "aadhaar",
    "label" => "Aadhaar / Govt ID Number",
    "type" => "text",
    "required" => true
  ],
  [
    "name" => "phone",
    "label" => "Phone Number",
    "type" => "tel",
    "required" => true
  ],
  [
    "name" => "email",
    "label" => "Email Address",
    "type" => "email",
    "required" => true
  ],
  [
    "name" => "address",
    "label" => "Permanent Address",
    "type" => "textarea",
    "required" => true
  ],
  [
    "name" => "pincode",
    "label" => "Pin Code",
    "type" => "text",
    "required" => true
  ],
  [
    "name" => "kit_tshirt",
    "label" => "Kit Size - T-Shirt",
    "type" => "select",
    "options" => ["XS", "S", "M", "L", "XL", "XXL"],
    "required" => true
  ],
  [
    "name" => "kit_tracksuit",
    "label" => "Kit Size - Track Suit",
    "type" => "select",
    "options" => ["XS", "S", "M", "L", "XL", "XXL"],
    "required" => true
  ],
  [
    "name" => "kit_shoe",
    "label" => "Kit Size - Shoe (UK Size)",
    "type" => "select",
    "options" => ["5", "6", "7", "8", "9", "10", "11", "12"],
    "required" => true
  ],
  [
    "name" => "photo_path",
    "label" => "Digital Passport Size Photo (PNG/JPG)",
    "type" => "file",
    "required" => true
  ],
  [
    "name" => "receipt_path",
    "label" => "Govt ID Proof Scanned PDF/Image",
    "type" => "file",
    "required" => true
  ]
];

// Helper function to check official duplicate with weighted scoring
function checkOfficialDuplicate($pdo, $name, $dob, $email, $phone, $aadhaar) {
    $stmt = $pdo->query("SELECT id, official_reg_no, name, dob, email, phone, aadhaar FROM officials WHERE status = 'approved' AND deleted_at IS NULL");
    $officials = $stmt->fetchAll();
    
    $bestMatchId = null;
    $highestScore = 0;
    
    foreach ($officials as $off) {
        $score = 0;
        
        // Aadhaar Match (100 pts)
        if (!empty($aadhaar) && !empty($off['aadhaar']) && $aadhaar === $off['aadhaar']) {
            $score += 100;
        }
        
        // Phone Match (40 pts)
        if (!empty($phone) && !empty($off['phone']) && preg_replace('/\D/', '', $phone) === preg_replace('/\D/', '', $off['phone'])) {
            $score += 40;
        }
        
        // Email Match (30 pts)
        if (!empty($email) && !empty($off['email']) && strtolower(trim($email)) === strtolower(trim($off['email']))) {
            $score += 30;
        }
        
        // DOB Match (20 pts)
        if (!empty($dob) && !empty($off['dob']) && $dob === $off['dob']) {
            $score += 20;
        }
        
        // Name similarity using levenshtein distance (10 pts)
        if (!empty($name) && !empty($off['name'])) {
            $n1 = strtolower(trim($name));
            $n2 = strtolower(trim($off['name']));
            if ($n1 === $n2) {
                $score += 10;
                
                // If both Name and DOB match, boost score to 60 to guarantee duplicate detection (threshold is 50)
                if (!empty($dob) && !empty($off['dob']) && $dob === $off['dob']) {
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
            $bestMatchId = $off['id'];
        }
    }
    
    return [
        'is_duplicate' => ($highestScore >= 50),
        'score' => $highestScore,
        'official_id' => $bestMatchId
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    $files = [];
    $isValid = true;

    // Sanitize and validate inputs
    foreach ($official_fields as $field) {
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
            $dupResult = checkOfficialDuplicate($pdo, $data['full_name'], $data['dob'], $data['email'], $data['phone'], $data['aadhaar']);

            // Save details to official_applications
            $ins = $pdo->prepare("INSERT INTO official_applications (full_name, role, gender, dob, father_name, state, aadhaar, phone, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, photo_path, receipt_path, status, existing_official_id, possible_duplicate, duplicate_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
            
            $ins->execute([
                $data['full_name'],
                $data['role'],
                $data['gender'],
                $data['dob'],
                $data['father_name'],
                $data['state'],
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
                $dupResult['official_id'],
                $dupResult['is_duplicate'] ? 1 : 0,
                $dupResult['score']
            ]);

            $message = "Your Official Registration application has been submitted successfully! It is currently under review.";
            
            // Log activity
            $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('Submit Official Registration Application', ?)");
            $log->execute(["Official registration application submitted for: " . $data['full_name']]);
        } catch (\Exception $e) {
            $error = "Database error saving application: " . $e->getMessage();
        }
    }
}

$page_title = "Online Coach & Official Registration - Boccia India";
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
    --boccia-saffron: #FF9933;
    --boccia-text-dark: #1E293B;
    --boccia-text-muted: #64748B;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

.registration-container-section {
    padding: 80px 0;
    background: url('../about boccia/hero bg.png') no-repeat right center;
    background-size: cover;
    background-attachment: fixed;
    position: relative;
}

.registration-container-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(8, 27, 75, 0.75) 0%, rgba(8, 27, 75, 0.4) 50%, transparent 100%);
    z-index: 1;
}

.registration-grid-container {
    position: relative;
    z-index: 2;
}

.form-wrapper-card {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 20px;
    padding: 45px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
}

.form-title-block {
    border-left: 4px solid var(--boccia-navy);
    padding-left: 15px;
    margin-bottom: 35px;
}

.form-title-block h2 {
    font-family: var(--font-heading-sub);
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--boccia-navy);
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 5px;
}

.form-title-block p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted);
    margin: 0;
}

/* Custom form styles */
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
    border-color: var(--boccia-navy);
    outline: none;
    box-shadow: 0 0 0 3px rgba(8, 27, 75, 0.15);
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
    background-color: var(--boccia-navy);
    border: none;
    padding: 12px 40px;
    border-radius: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-submit-custom:hover {
    background-color: var(--boccia-accent-red);
}

/* --- Mobile Responsiveness --- */
@media (max-width: 767px) {
    .registration-container-section {
        padding: 40px 0;
    }
    .form-wrapper-card {
        padding: 25px 20px;
        border-radius: 16px;
    }
    .form-title-block {
        margin-bottom: 25px;
        padding-left: 10px;
    }
    .form-title-block h2 {
        font-size: 1.4rem;
    }
    .form-title-block p {
        font-size: 0.88rem;
    }
    .form-label-custom {
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    .form-control-custom, .form-select-custom {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    .d-flex.justify-content-between {
        flex-direction: column-reverse;
        gap: 15px;
        align-items: center !important;
        text-align: center;
    }
    .btn-submit-custom {
        width: 100%;
        text-align: center;
        padding: 12px 20px;
    }
}
</style>

<div class="registration-container-section">
    <div class="container registration-grid-container">
        <div class="row">
            <!-- Form aligned to the Left (col-lg-7) -->
            <div class="col-lg-7 col-md-12">
                <div class="form-wrapper-card scroll-reveal">
                    
                    <div class="form-title-block">
                        <h2>Coach / Assistant / Official / Volunteer Registration</h2>
                        <p>Register as a coach, sport assistant, classifier, official, referee, or volunteer with BSFI.</p>
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
                        <form action="register-official.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <?php foreach ($official_fields as $field): 
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
                            
                            <div class="d-flex justify-content-between align-items-center mt-5">
                                <a href="membership.php" class="btn btn-link text-decoration-none text-muted"><i class="bi bi-arrow-left me-2"></i> Back to Portal</a>
                                <button type="submit" class="btn-submit-custom">Submit Application</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Empty Right Column to make sure the background Indian player remains fully visible -->
            <div class="col-lg-5 d-none d-lg-block"></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
