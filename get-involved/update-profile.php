<?php
// get-involved/update-profile.php - Unified Profile Update Request Portal
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
$error = '';
$step = 1; // 1: Lookup, 2: OTP Verification, 3: Form Update, 4: Success

$member_type = isset($_POST['member_type']) ? $_POST['member_type'] : (isset($_GET['type']) ? $_GET['type'] : 'athlete');
$member_id_input = isset($_POST['member_id_input']) ? trim($_POST['member_id_input']) : (isset($_GET['id']) ? trim($_GET['id']) : '');
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';

$matched_id = isset($_POST['matched_id']) ? (int)$_POST['matched_id'] : 0;
$otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';

$mask_contact = '';
$needs_otp = false;

// Handle Lookup Step 1
if (isset($_POST['lookup'])) {
    if (empty($member_id_input) || empty($dob)) {
        $error = "Please fill in all identity lookup fields.";
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
                $matched_id = $matched['id'];
                $phone = $member_type === 'athlete' ? $matched['mobile'] : $matched['phone'];
                $email = $matched['email'];

                if (!empty($email) || !empty($phone)) {
                    $needs_otp = true;
                    $step = 2;
                    
                    // Format mask contact info
                    if (!empty($phone)) {
                        $mask_contact = 'Phone: ' . substr($phone, 0, 3) . '******' . substr($phone, -3);
                    } else {
                        $mask_contact = 'Email: ' . substr($email, 0, 2) . '******' . strstr($email, '@');
                    }
                } else {
                    // Direct to Step 3 - Manual Admin Review
                    $step = 3;
                    $message = "No contact details on file. Your update will require manual admin verification.";
                }
            } else {
                $error = "No active approved registration found matching the entered details.";
            }
        } catch (PDOException $e) {
            $error = "Lookup failed due to database issues.";
        }
    }
}

// Handle OTP Step 2
if (isset($_POST['verify_otp'])) {
    if (empty($otp_code)) {
        $error = "Please enter the verification code sent to your contact info.";
        $step = 2;
    } else {
        // Simulated OTP: Accept any 6 digit code
        if (strlen($otp_code) >= 4) {
            $step = 3;
        } else {
            $error = "Invalid verification code. Please try again.";
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
    
    $photo_path = null;
    $isValid = true;

    // Handle photo upload if present
    if (!empty($_FILES['photo_path']['name'])) {
        if ($_FILES['photo_path']['size'] > 5 * 1024 * 1024) {
            $error = "File size exceeds 5MB limit.";
            $isValid = false;
        } else {
            $filename = $_FILES['photo_path']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowedExts = ['png', 'jpg', 'jpeg'];
            if (!in_array($ext, $allowedExts)) {
                $error = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
                $isValid = false;
            } else {
                $uploadDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $secureName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo_path']['tmp_name'], $uploadDir . $secureName)) {
                    $photo_path = 'uploads/profiles/' . $secureName;
                }
            }
        }
    }

    if ($isValid) {
        try {
            $ins = $pdo->prepare("INSERT INTO profile_update_requests (member_type, member_id, requested_email, requested_phone, requested_address, requested_pincode, requested_photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $ins->execute([$member_type, $matched_id, $email_req, $phone_req, $address_req, $pincode_req, $photo_path]);
            
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
    background: linear-gradient(135deg, rgba(8, 27, 75, 0.95) 0%, rgba(140, 32, 28, 0.95) 100%), url('../about boccia/why boccia matter BG.png') no-repeat center center;
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
</style>

<div class="update-portal-bg">
    <div class="container d-flex justify-content-center">
        <div class="glass-card-update">
            
            <div class="text-center mb-4">
                <img src="../boccia-india-logo.webp" alt="BSFI Logo" style="max-height: 80px;" class="mb-3">
                <h1 style="font-family: var(--font-heading-sub); font-weight: 800; text-transform: uppercase; letter-spacing: -0.01em; margin:0;">Profile Update Portal</h1>
                <p style="font-family: var(--font-body-custom); color: rgba(255,255,255,0.8); font-size: 0.95rem; margin-top:5px;">
                    Keep your BSFI registration details and profile photograph updated.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 p-3 mb-4 rounded-3 text-start" style="background-color: rgba(239, 68, 68, 0.15); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.3) !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: IDENTITY LOOKUP -->
            <?php if ($step === 1): ?>
                <form action="update-profile.php" method="POST">
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
                    <button type="submit" name="lookup" class="btn-submit-update">Verify Identity</button>
                </form>
            
            <!-- STEP 2: OTP OTP OTP -->
            <?php elseif ($step === 2): ?>
                <div class="alert alert-info border-0 p-3 mb-4 rounded-3 text-start" style="background-color: rgba(59, 130, 246, 0.15); color: #93C5FD; border: 1px solid rgba(59, 130, 246, 0.3) !important;">
                    <i class="bi bi-info-circle-fill me-2"></i> A verification code has been simulated and sent to your registered: <strong><?php echo $mask_contact; ?></strong>.
                </div>
                <form action="update-profile.php" method="POST">
                    <input type="hidden" name="member_type" value="<?php echo htmlspecialchars($member_type); ?>">
                    <input type="hidden" name="matched_id" value="<?php echo $matched_id; ?>">
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

                <form action="update-profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="member_type" value="<?php echo htmlspecialchars($member_type); ?>">
                    <input type="hidden" name="matched_id" value="<?php echo $matched_id; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label-custom">New Contact Email Address</label>
                        <input type="email" name="email" class="form-control-custom" placeholder="E.g. athlete@example.com" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label-custom">New Mobile Phone Number</label>
                        <input type="tel" name="phone" class="form-control-custom" placeholder="E.g. +91 9876543210" required>
                    </div>

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
                        <input type="file" name="photo_path" class="form-control-custom" required>
                        <span style="font-size:0.8rem; color:rgba(255,255,255,0.7); display:block; margin-top:5px;">*Passport photo must show face clearly in JPG/PNG format. Maximum 5MB.</span>
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
                    <a href="verify-membership.php" class="btn btn-outline-light rounded-pill px-4 mt-4" style="font-family: var(--font-heading-sub); font-weight:700;">
                        Go to Verification Page
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($step < 4): ?>
                <div class="text-center mt-5 border-top pt-3" style="border-top-color: rgba(255,255,255,0.1) !important;">
                    <a href="verify-membership.php" class="text-white text-decoration-none" style="font-family: var(--font-heading-sub); font-weight: 600; font-size: 0.95rem;">
                        <i class="bi bi-arrow-left me-1"></i> Back to Verification Search
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
