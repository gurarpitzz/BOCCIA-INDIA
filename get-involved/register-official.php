<?php
// get-involved/register-official.php - Complete multi-step Official registration wizard in native PHP
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Online Official Registration - Boccia India";
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
    width: 40%;
    background: url('../about boccia/hero_bg.webp') no-repeat right center;
    background-size: cover;
    position: relative;
}

.split-card-right {
    width: 60%;
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

.form-header-box {
    text-align: center;
    margin-bottom: 30px;
}

.back-home-link {
    color: var(--boccia-maroon);
    font-family: var(--font-heading-sub);
    font-weight: 600;
    text-decoration: none;
    font-size: 1.05rem;
    display: inline-block;
    margin-bottom: 15px;
    transition: color 0.3s ease;
}

.back-home-link:hover {
    color: var(--boccia-navy);
}

.form-logo-img {
    max-height: 80px;
    width: auto;
    margin-bottom: 15px;
}

.form-title-text {
    font-family: var(--font-heading-sub);
    font-weight: 800;
    color: var(--boccia-maroon);
    font-size: 1.8rem;
    margin: 0;
}

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

.hp-field {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}

.wizard-step {
    display: none;
}

.wizard-step.active {
    display: block;
}

/* Progress indicator styles */
.bsfi-progress-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    max-width: 500px;
    margin: 0 auto 30px;
}

.step-num {
    width: 32px;
    height: 32px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-weight: bold;
    z-index: 2;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.step-num.active {
    background: var(--boccia-maroon);
    color: #ffffff;
}

.step-num.completed {
    background: var(--boccia-green);
    color: #ffffff;
}

.progress-line {
    position: absolute;
    height: 4px;
    background: #e2e8f0;
    top: 50%;
    left: 10px;
    right: 10px;
    transform: translateY(-50%);
    z-index: 1;
}

.progress-bar-fill {
    height: 100%;
    background: var(--boccia-maroon);
    width: 0%;
    transition: width 0.3s ease;
}

.btn-wizard {
    font-family: var(--font-heading-sub);
    font-size: 0.95rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 30px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-wizard-next {
    background-color: var(--boccia-maroon);
    color: #ffffff;
}

.btn-wizard-next:hover:not(:disabled) {
    background-color: var(--boccia-navy);
}

.btn-wizard-prev {
    background-color: #e2e8f0;
    color: #475569;
}

.btn-wizard-prev:hover {
    background-color: #cbd5e1;
}

.file-drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    background-color: #f8fafc;
    transition: border-color 0.3s ease;
    position: relative;
}

.file-drop-zone:hover {
    border-color: var(--boccia-maroon);
}

.file-drop-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
</style>

<div class="outer-registration-bg">
    <div class="container" style="max-width: 1200px;">
        <div class="split-card-container">
            <!-- Left Side Image Column -->
            <div class="split-card-left"></div>

            <!-- Right Side Form Column -->
            <div class="split-card-right">
                <div class="form-header-box">
                    <a href="get-involved/membership.php" class="back-home-link">Back to HOME Page</a>
                    <div>
                        <img src="../boccia-india-logo.webp" alt="BSFI Logo" class="form-logo-img">
                    </div>
                    <h2 class="form-title-text" id="wizard-title-label">Official Registration</h2>
                </div>

                <!-- Step progress indicator -->
                <div class="bsfi-progress-steps">
                    <div class="progress-line">
                        <div class="progress-bar-fill" id="progress-fill-line"></div>
                    </div>
                    <span class="step-num active" id="step-dot-0">✓</span>
                    <span class="step-num" id="step-dot-1">1</span>
                    <span class="step-num" id="step-dot-2">2</span>
                    <span class="step-num" id="step-dot-3">3</span>
                    <span class="step-num" id="step-dot-4">4</span>
                    <span class="step-num" id="step-dot-5">5</span>
                </div>

                <!-- Draft recovery container -->
                <div id="draft-restore-alert" class="alert alert-info border-0 p-4 mb-4 rounded-3 d-none" style="background-color: #EFF6FF; color: #1E40AF;">
                    <h5 class="alert-heading font-bold mb-2">Unfinished Registration Found</h5>
                    <p class="text-sm mb-3">We found your saved progress. Would you like to resume?</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary px-4 py-2" onclick="loadDraftData()">Resume Registration</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-4 py-2" onclick="discardDraftData()">Start Over</button>
                    </div>
                </div>

                <div id="general-error-box" class="alert alert-danger border-0 p-3 mb-4 rounded-3 d-none" style="background-color: #FEF2F2; color: #991B1B;"></div>

                <form id="registration-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" id="csrf_token_field" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="hp-field" aria-hidden="true">
                        <input type="text" name="website_url" id="field_website_url" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- ================= STEP 0: EMAIL VERIFICATION ================= -->
                    <div class="wizard-step active" id="wizard-step-0">
                        <h4 class="mb-3 text-slate-800 font-bold">Email Verification</h4>
                        <p class="text-slate-500 text-sm mb-4">Please verify your email address to begin the registration wizard.</p>
                        
                        <div class="mb-4">
                          <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                          <div style="margin-bottom: 30px;">
                            <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
                          </div>
                          <div class="d-flex gap-2">
                            <input type="email" id="field_email" name="email" class="form-control-custom" placeholder="e.g. official@gmail.com" required>
                            <button type="button" id="btn-send-otp" class="btn btn-primary" onclick="requestOTP()">Send OTP</button>
                          </div>
                        </div>

                        <div id="otp-input-block" class="mb-4 d-none">
                          <label class="form-label-custom">Enter 6-Digit Verification Code <span class="text-danger">*</span></label>
                          <div class="d-flex gap-2">
                            <input type="text" id="otp_code" maxlength="6" class="form-control-custom text-center font-bold tracking-widest text-lg" placeholder="000000">
                            <button type="button" class="btn btn-success" onclick="verifyOTPCode()">Verify Code</button>
                          </div>
                        </div>
                    </div>

                    <!-- ================= STEP 1: PERSONAL INFORMATION ================= -->
                    <div class="wizard-step" id="wizard-step-1">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 1: Personal Information</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select-custom" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="dob" class="form-control-custom" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" class="form-control-custom" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control-custom" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn-wizard btn-wizard-next" onclick="goToStep(2)">Continue &rarr;</button>
                        </div>
                    </div>

                    <!-- ================= STEP 2: OFFICIAL CATEGORY & PROFESSIONAL PROFILE ================= -->
                    <div class="wizard-step" id="wizard-step-2">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 2: Category &amp; Profile</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Official Category <span class="text-danger">*</span></label>
                                <select name="category" id="official-category" class="form-select-custom" onchange="onCategoryChange()" required>
                                    <option value="">Select Category</option>
                                    <option value="Coach">Coach</option>
                                    <option value="Referee">Referee</option>
                                    <option value="Volunteer">Volunteer</option>
                                    <option value="Classifier">Classifier</option>
                                    <option value="Ramp Operator / Sports Assistant">Ramp Operator / Sports Assistant</option>
                                    <option value="Escort">Escort</option>
                                </select>
                            </div>

                            <!-- Educational Qualification (Coach, Referee, Classifier) -->
                            <div class="col-md-12 category-conditional-field" id="field-wrapper-qualification" style="display:none;">
                                <label class="form-label-custom">Educational / Professional Qualification <span class="text-danger">*</span></label>
                                <input type="text" name="education_qualification" id="input-qualification" class="form-control-custom">
                            </div>

                            <!-- Classifier Type Dropdown (Classifier Only) -->
                            <div class="col-md-12 category-conditional-field" id="field-wrapper-classifier-type" style="display:none;">
                                <label class="form-label-custom">Classifier Type <span class="text-danger">*</span></label>
                                <select name="classifier_type" id="select-classifier-type" class="form-select-custom" onchange="onClassifierTypeChange()">
                                    <option value="">Select Classifier Type</option>
                                    <option value="Physio">Physio</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Coach">Coach</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Classifier Specify Other (Classifier Only) -->
                            <div class="col-md-12 category-conditional-field" id="field-wrapper-classifier-other" style="display:none;">
                                <label class="form-label-custom">Specify Classifier Type <span class="text-danger">*</span></label>
                                <input type="text" name="classifier_type_other" id="input-classifier-other" class="form-control-custom" placeholder="Please specify your classifier role">
                            </div>

                            <!-- Experience (Coach, Referee, Volunteer, Classifier required; Ramp, Escort optional) -->
                            <div class="col-md-12 category-conditional-field" id="field-wrapper-experience" style="display:none;">
                                <label class="form-label-custom" id="label-experience">Experience in Para Sports <span class="text-danger">*</span></label>
                                <textarea name="para_sports_experience" id="input-experience" rows="3" class="form-control-custom"></textarea>
                            </div>

                            <!-- Uniform Kit Sizes (All Categories) -->
                            <div class="col-md-4">
                                <label class="form-label-custom">T-Shirt Size <span class="text-danger">*</span></label>
                                <select name="kit_tshirt" class="form-select-custom" required>
                                    <option value="">Select Size</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                    <option value="3XL">3XL</option>
                                    <option value="4XL">4XL</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Track Suit Size <span class="text-danger">*</span></label>
                                <select name="kit_tracksuit" class="form-select-custom" required>
                                    <option value="">Select Size</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                    <option value="3XL">3XL</option>
                                    <option value="4XL">4XL</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Shoe Size (UK) <span class="text-danger">*</span></label>
                                <select name="kit_shoe" class="form-select-custom" required>
                                    <option value="">Select Size</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-wizard btn-wizard-prev" onclick="goToStep(1)">&larr; Back</button>
                            <button type="button" class="btn-wizard btn-wizard-next" onclick="goToStep(3)">Continue &rarr;</button>
                        </div>
                    </div>

                    <!-- ================= STEP 3: ADDRESS & IDENTITY ================= -->
                    <div class="wizard-step" id="wizard-step-3">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 3: Address &amp; Identity</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">State / Union Territory <span class="text-danger">*</span></label>
                                <select name="state" class="form-select-custom" required>
                                    <option value="">Select State</option>
                                    <?php
                                    $statesList = ["Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands", "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Jammu and Kashmir", "Ladakh", "Lakshadweep", "Puducherry"];
                                    foreach ($statesList as $st) {
                                        echo "<option value=\"".htmlspecialchars($st)."\">".htmlspecialchars($st)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Permanent Address <span class="text-danger">*</span></label>
                                <textarea name="address" rows="3" class="form-control-custom" required></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Pin Code <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" maxlength="6" class="form-control-custom" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Aadhaar Card Number <span class="text-danger">*</span></label>
                                <input type="text" name="aadhaar" id="input-aadhaar" pattern="\d{12}" title="Aadhaar number must be exactly 12 digits" maxlength="12" minlength="12" class="form-control-custom" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-wizard btn-wizard-prev" onclick="goToStep(2)">&larr; Back</button>
                            <button type="button" class="btn-wizard btn-wizard-next" onclick="goToStep(4)">Continue &rarr;</button>
                        </div>
                    </div>

                    <!-- ================= STEP 4: DOCUMENTS & UPLOADS ================= -->
                    <div class="wizard-step" id="wizard-step-4">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 4: Documents &amp; Uploads</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-custom">Passport Size Photo (JPG/PNG) <span class="text-danger">*</span></label>
                                <div class="file-drop-zone">
                                    <input type="file" id="file_photo" name="photo_path" accept="image/jpeg,image/png" onchange="updateFileLabel('file_photo', 'photo_label')" required>
                                    <div class="text-slate-400 text-3xl mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <span class="text-sm font-semibold" id="photo_label">Choose Photo File</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Government ID Proof (Aadhaar/ID Scan) <span class="text-danger">*</span></label>
                                <div class="file-drop-zone">
                                    <input type="file" id="file_doc" name="receipt_path" accept="application/pdf,image/jpeg,image/png" onchange="updateFileLabel('file_doc', 'doc_label')" required>
                                    <div class="text-slate-400 text-3xl mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <span class="text-sm font-semibold" id="doc_label">Choose Document File</span>
                                </div>
                            </div>

                            <!-- Passport Booklet Upload (Mandatory for All Official Categories) -->
                            <div class="col-md-12" id="field-wrapper-passport-upload">
                                <label class="form-label-custom" id="label-passport-upload">Passport Document Booklet / Copy <span class="text-danger">*</span></label>
                                <div class="file-drop-zone">
                                    <input type="file" id="file_passport" name="passport_file" accept="application/pdf,image/jpeg,image/png" onchange="updateFileLabel('file_passport', 'passport_label')" required>
                                    <div class="text-slate-400 text-3xl mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <span class="text-sm font-semibold" id="passport_label">Choose Passport Copy File</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-wizard btn-wizard-prev" onclick="goToStep(3)">&larr; Back</button>
                            <button type="button" class="btn-wizard btn-wizard-next" onclick="prepareReviewStep()">Continue &rarr;</button>
                        </div>
                    </div>

                    <!-- ================= STEP 5: DECLARATION & REVIEW ================= -->
                    <div class="wizard-step" id="wizard-step-5">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 5: Review &amp; Declaration</h4>
                        
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.5rem; margin-bottom:1.5rem; max-height:400px; overflow-y:auto; font-size:0.92rem; display:flex; flex-direction:column; gap:1.25rem;">
                            <div>
                                <h5 style="color:var(--boccia-navy); font-weight:700; border-bottom:1.5px solid #cbd5e1; padding-bottom:0.4rem; margin-bottom:0.6rem;">Personal Information</h5>
                                <p class="mb-1"><strong>Full Name:</strong> <span id="rev-name"></span></p>
                                <p class="mb-1"><strong>Gender:</strong> <span id="rev-gender"></span></p>
                                <p class="mb-1"><strong>Date of Birth:</strong> <span id="rev-dob"></span></p>
                                <p class="mb-1"><strong>Father's Name:</strong> <span id="rev-father"></span></p>
                                <p class="mb-1"><strong>Phone Number:</strong> <span id="rev-phone"></span></p>
                                <p class="mb-1"><strong>Verified Email:</strong> <span id="rev-email"></span></p>
                            </div>
                            <div>
                                <h5 style="color:var(--boccia-navy); font-weight:700; border-bottom:1.5px solid #cbd5e1; padding-bottom:0.4rem; margin-bottom:0.6rem;">Official Information</h5>
                                <p class="mb-1"><strong>Category:</strong> <span id="rev-category"></span></p>
                                <p class="mb-1" id="rev-wrapper-qualification"><strong>Educational Qualification:</strong> <span id="rev-qualification"></span></p>
                                <p class="mb-1" id="rev-wrapper-classifier-type"><strong>Classifier Type:</strong> <span id="rev-classifier-type"></span></p>
                                <p class="mb-1" id="rev-wrapper-experience"><strong>Experience in Para Sports:</strong> <span id="rev-experience"></span></p>
                                <p class="mb-1"><strong>Uniform Kit Sizes:</strong> T-Shirt: <span id="rev-kit-tshirt"></span> | Tracksuit: <span id="rev-kit-track"></span> | Shoes (UK): <span id="rev-kit-shoe"></span></p>
                            </div>
                            <div>
                                <h5 style="color:var(--boccia-navy); font-weight:700; border-bottom:1.5px solid #cbd5e1; padding-bottom:0.4rem; margin-bottom:0.6rem;">Address &amp; Identity</h5>
                                <p class="mb-1"><strong>State / UT:</strong> <span id="rev-state"></span></p>
                                <p class="mb-1"><strong>Address:</strong> <span id="rev-address"></span></p>
                                <p class="mb-1"><strong>Pin Code:</strong> <span id="rev-pincode"></span></p>
                                <p class="mb-1"><strong>Aadhaar Number:</strong> <span id="rev-aadhaar"></span></p>
                            </div>
                            <div>
                                <h5 style="color:var(--boccia-navy); font-weight:700; border-bottom:1.5px solid #cbd5e1; padding-bottom:0.4rem; margin-bottom:0.6rem;">Attached Documents</h5>
                                <p class="mb-1" id="rev-doc-photo"><strong>✓ Passport Size Photo:</strong> Attached</p>
                                <p class="mb-1" id="rev-doc-id"><strong>✓ Government ID Proof:</strong> Attached</p>
                                <p class="mb-1" id="rev-doc-passport"><strong>✓ Passport Booklet:</strong> Attached</p>
                            </div>
                        </div>

                        <div class="col-md-12 mb-4" style="display:flex; align-items:flex-start; gap:0.6rem;">
                            <input type="checkbox" id="declaration-check" required style="width: 20px; height: 20px; margin-top: 2px; cursor:pointer;">
                            <label for="declaration-check" style="font-size:0.88rem; color:#475569; font-weight:600; cursor:pointer;">
                                I declare that the information provided in this application is true and correct to the best of my knowledge. I understand that any false or inaccurate representation will result in immediate disqualification or termination of BSFI membership.
                            </label>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-wizard btn-wizard-prev" onclick="goToStep(4)">&larr; Back</button>
                            <button type="button" id="btn-submit-reg" class="btn-wizard btn-wizard-next" onclick="submitApplication()">Submit Application</button>
                        </div>
                    </div>

                    <!-- ================= STEP 6: SUCCESS PAGE ================= -->
                    <div class="wizard-step" id="wizard-step-6">
                        <div class="text-center space-y-4 py-4">
                            <div class="text-5xl text-emerald-500 mb-3"><i class="bi bi-check-circle-fill"></i></div>
                            <h2 class="text-2xl font-black text-slate-800 mb-2">Registration Submitted Successfully</h2>
                            <p class="text-slate-500 text-sm max-w-md mx-auto mb-4">Your application has been logged and is under review. Make sure to save your Tracking Reference ID below.</p>
                            
                            <div class="bg-slate-50 border border-slate-200 p-4 rounded-3 max-w-sm mx-auto mb-4 text-center">
                                <span class="d-block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Your Tracking Reference ID</span>
                                <span class="d-block text-2xl font-black text-orange-500 tracking-wider" id="ref-id-display">BSFI-OFF-2026-000000</span>
                            </div>

                            <div>
                                <a href="get-involved/status.php" id="track-url-btn" class="btn btn-primary px-4 py-2">Track Application Status</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/image-compressor.js"></script>
<script>
const DRAFT_KEY = 'bsfi_official_draft_v1';
let currentStep = 0;
let isVerified = false;
let cooldown = 0;
let timer = null;

// Initialize dynamic drafts
document.addEventListener("DOMContentLoaded", function() {
    const draft = localStorage.getItem(DRAFT_KEY);
    if (draft) {
        document.getElementById("draft-restore-alert").classList.remove("d-none");
    }
    
    // Auto-save drafts on form modification
    const inputs = document.querySelectorAll("#registration-form input, #registration-form select, #registration-form textarea");
    inputs.forEach(input => {
        input.addEventListener("input", saveDraftData);
        input.addEventListener("change", saveDraftData);
    });
});

function updateFileLabel(inputId, labelId) {
    const input = document.getElementById(inputId);
    const label = document.getElementById(labelId);
    if (input.files.length > 0) {
        label.innerText = input.files[0].name;
    } else {
        label.innerText = "Choose File";
    }
}

function startCooldown() {
    const btn = document.getElementById("btn-send-otp");
    cooldown = 60;
    btn.disabled = true;
    if (timer) clearInterval(timer);
    
    timer = setInterval(() => {
        cooldown--;
        if (cooldown <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            btn.innerText = "Send OTP";
        } else {
            btn.innerText = `Resend (${cooldown}s)`;
        }
    }, 1000);
}

function requestOTP() {
    const email = document.getElementById("field_email").value;
    const csrf = document.getElementById("csrf_token_field").value;
    const website_url = document.getElementById("field_website_url").value;
    const captcha_token = document.querySelector('[name="h-captcha-response"]')?.value || "";
    const errBox = document.getElementById("general-error-box");
    
    errBox.classList.add("d-none");
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errBox.innerText = "Please enter a valid email address.";
        errBox.classList.remove("d-none");
        return;
    }

    if (!captcha_token && !website_url) {
        errBox.innerText = "Please complete the hCaptcha challenge.";
        errBox.classList.remove("d-none");
        return;
    }

    const fd = new FormData();
    fd.append("email", email);
    fd.append("csrf_token", csrf);
    fd.append("website_url", website_url);
    fd.append("captcha_token", captcha_token);
    fd.append("action", "register_official");

    fetch("../api/send-otp.php", {
        method: "POST",
        body: fd
    })
    .then(async res => {
        const data = await res.json();
        if (typeof hcaptcha !== "undefined") {
            hcaptcha.reset();
        }
        if (!res.ok) {
            throw new Error(data.error || "Failed to send OTP.");
        }
        document.getElementById("otp-input-block").classList.remove("d-none");
        startCooldown();
    })
    .catch(err => {
        errBox.innerText = err.message;
        errBox.classList.remove("d-none");
    });
}

function verifyOTPCode() {
    const email = document.getElementById("field_email").value;
    const otp = document.getElementById("otp_code").value;
    const csrf = document.getElementById("csrf_token_field").value;
    const errBox = document.getElementById("general-error-box");
    
    errBox.classList.add("d-none");
    if (!otp || otp.length !== 6) {
        errBox.innerText = "OTP must be a 6-digit number.";
        errBox.classList.remove("d-none");
        return;
    }

    const fd = new FormData();
    fd.append("email", email);
    fd.append("otp", otp);
    fd.append("csrf_token", csrf);
    fd.append("action", "register_official");

    fetch("../api/verify-otp.php", {
        method: "POST",
        body: fd
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.error || "Verification failed.");
        }
        isVerified = true;
        document.getElementById("field_email").readOnly = true;
        document.getElementById("btn-send-otp").disabled = true;
        document.getElementById("otp-input-block").classList.add("d-none");
        goToStep(1);
    })
    .catch(err => {
        errBox.innerText = err.message;
        errBox.classList.remove("d-none");
    });
}

function onCategoryChange() {
    const cat = document.getElementById("official-category").value;
    const qual = document.getElementById("field-wrapper-qualification");
    const qualInput = document.getElementById("input-qualification");
    const exp = document.getElementById("field-wrapper-experience");
    const expLabel = document.getElementById("label-experience");
    const expInput = document.getElementById("input-experience");
    const classType = document.getElementById("field-wrapper-classifier-type");
    const classSelect = document.getElementById("select-classifier-type");
    const passWrap = document.getElementById("field-wrapper-passport-upload");
    const passInput = document.getElementById("file_passport");
    const passLabel = document.getElementById("label-passport-upload");

    // Reset validations and values
    qualInput.required = false;
    expInput.required = false;
    classSelect.required = false;
    passInput.required = false;

    // Reset classifier conditional fields
    document.getElementById("field-wrapper-classifier-other").style.display = 'none';
    document.getElementById("input-classifier-other").required = false;

    // Hide all initially
    qual.style.display = "none";
    exp.style.display = "none";
    classType.style.display = "none";

    if (!cat) return;

    // Handle show & require validations based on category rules
    if (cat === 'Coach' || cat === 'Referee') {
        qual.style.display = "block";
        qualInput.required = true;
        exp.style.display = "block";
        expLabel.innerHTML = 'Experience in Para Sports <span class="text-danger">*</span>';
        expInput.required = true;
        
        // Passport Document Required
        passLabel.innerHTML = 'Passport Document Booklet / Copy <span class="text-danger">*</span>';
        passInput.required = true;
    } else if (cat === 'Volunteer') {
        exp.style.display = "block";
        expLabel.innerHTML = 'Experience in Para Sports <span class="text-danger">*</span>';
        expInput.required = true;
        
        // Passport Document Optional
        passLabel.innerHTML = 'Passport Document Booklet / Copy (Optional)';
        passInput.required = false;
    } else if (cat === 'Classifier') {
        qual.style.display = "block";
        qualInput.required = true;
        classType.style.display = "block";
        classSelect.required = true;
        exp.style.display = "block";
        expLabel.innerHTML = 'Experience in Para Sports <span class="text-danger">*</span>';
        expInput.required = true;
        
        // Passport Document Optional
        passLabel.innerHTML = 'Passport Document Booklet / Copy (Optional)';
        passInput.required = false;
    } else if (cat === 'Ramp Operator / Sports Assistant' || cat === 'Escort') {
        exp.style.display = "block";
        expLabel.innerHTML = 'Experience in Para Sports (Optional)';
        expInput.required = false;

        // Passport Document Required
        passLabel.innerHTML = 'Passport Document Booklet / Copy <span class="text-danger">*</span>';
        passInput.required = true;
    }
}

function onClassifierTypeChange() {
    const type = document.getElementById("select-classifier-type").value;
    const specify = document.getElementById("field-wrapper-classifier-other");
    const specInput = document.getElementById("input-classifier-other");

    if (type === 'Other') {
        specify.style.display = 'block';
        specInput.required = true;
    } else {
        specify.style.display = 'none';
        specInput.required = false;
        specInput.value = '';
    }
}

function goToStep(stepIndex) {
    const errBox = document.getElementById("general-error-box");
    errBox.classList.add("d-none");

    if (stepIndex > currentStep) {
        // Run validations for current active step fields
        const currentFields = document.querySelectorAll(`#wizard-step-${currentStep} [required]`);
        let stepValid = true;
        
        currentFields.forEach(f => {
            if (!f.checkValidity()) {
                stepValid = false;
                f.reportValidity();
            }
        });
        
        if (!stepValid) return;
    }

    // Toggle active classes
    document.querySelectorAll(".wizard-step").forEach((el, idx) => {
        el.classList.toggle("active", idx === stepIndex);
    });

    // Update Indicators
    document.querySelectorAll(".step-num").forEach((el, idx) => {
        el.classList.remove("active", "completed");
        if (idx === stepIndex) {
            el.classList.add("active");
        } else if (idx < stepIndex) {
            el.classList.add("completed");
        }
    });

    // Fill line length
    const fillPercent = ((stepIndex) / 5) * 100;
    document.getElementById("progress-fill-line").style.width = `${fillPercent}%`;

    currentStep = stepIndex;
    saveDraftData();
}

function prepareReviewStep() {
    // Run Step 4 validations before allowing review screen access
    const fields = document.querySelectorAll(`#wizard-step-4 [required]`);
    let valid = true;
    fields.forEach(f => {
        if (!f.checkValidity()) {
            valid = false;
            f.reportValidity();
        }
    });
    if (!valid) return;

    // Fill review details
    document.getElementById("rev-name").innerText = document.querySelector('[name="full_name"]').value;
    document.getElementById("rev-gender").innerText = document.querySelector('[name="gender"]').value;
    document.getElementById("rev-dob").innerText = document.querySelector('[name="dob"]').value;
    document.getElementById("rev-father").innerText = document.querySelector('[name="father_name"]').value;
    document.getElementById("rev-phone").innerText = document.querySelector('[name="phone"]').value;
    document.getElementById("rev-email").innerText = document.getElementById("field_email").value;

    const cat = document.getElementById("official-category").value;
    document.getElementById("rev-category").innerText = cat;

    // Category specifics
    const revQual = document.getElementById("rev-wrapper-qualification");
    const revClass = document.getElementById("rev-wrapper-classifier-type");
    const revExp = document.getElementById("rev-wrapper-experience");

    revQual.style.display = "none";
    revClass.style.display = "none";
    revExp.style.display = "none";

    if (cat === 'Coach' || cat === 'Referee' || cat === 'Classifier') {
        revQual.style.display = "block";
        document.getElementById("rev-qualification").innerText = document.getElementById("input-qualification").value;
    }
    if (cat === 'Classifier') {
        revClass.style.display = "block";
        const cType = document.getElementById("select-classifier-type").value;
        document.getElementById("rev-classifier-type").innerText = cType === 'Other' ? `Other (${document.getElementById("input-classifier-other").value})` : cType;
    }
    revExp.style.display = "block";
    document.getElementById("rev-experience").innerText = document.getElementById("input-experience").value || 'None';

    document.getElementById("rev-kit-tshirt").innerText = document.querySelector('[name="kit_tshirt"]').value;
    document.getElementById("rev-kit-track").innerText = document.querySelector('[name="kit_tracksuit"]').value;
    document.getElementById("rev-kit-shoe").innerText = document.querySelector('[name="kit_shoe"]').value;

    document.getElementById("rev-state").innerText = document.querySelector('[name="state"]').value;
    document.getElementById("rev-address").innerText = document.querySelector('[name="address"]').value;
    document.getElementById("rev-pincode").innerText = document.querySelector('[name="pincode"]').value;

    // Mask Aadhaar
    const aadhaarVal = document.getElementById("input-aadhaar").value;
    document.getElementById("rev-aadhaar").innerText = aadhaarVal.substring(0, 4) + "-XXXX-XXXX";

    // Documents check
    document.getElementById("rev-doc-passport").style.display = document.getElementById("file_passport").files.length > 0 ? "block" : "none";

    goToStep(5);
}

function saveDraftData() {
    if (currentStep < 1 || currentStep > 5) return;
    
    const form = document.getElementById("registration-form");
    const fd = new FormData(form);
    const data = {};
    fd.forEach((value, key) => {
        if (!(value instanceof File)) {
            data[key] = value;
        }
    });

    localStorage.setItem(DRAFT_KEY, JSON.stringify({
        step: currentStep,
        formData: data,
        timestamp: Date.now()
    }));
}

function loadDraftData() {
    const draftJson = localStorage.getItem(DRAFT_KEY);
    if (!draftJson) return;

    const { step, formData } = JSON.parse(draftJson);
    const form = document.getElementById("registration-form");
    
    Object.entries(formData).forEach(([key, val]) => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = val;
        }
    });

    onCategoryChange(); // Trigger dynamic layouts load
    onClassifierTypeChange();

    isVerified = true;
    document.getElementById("field_email").readOnly = true;
    document.getElementById("btn-send-otp").disabled = true;
    document.getElementById("draft-restore-alert").classList.add("d-none");
    
    goToStep(step);
}

function discardDraftData() {
    localStorage.removeItem(DRAFT_KEY);
    document.getElementById("draft-restore-alert").classList.add("d-none");
}

async function submitApplication() {
    const form = document.getElementById("registration-form");
    const errBox = document.getElementById("general-error-box");
    const submitBtn = document.getElementById("btn-submit-reg");
    
    errBox.classList.add("d-none");
    
    if (!document.getElementById("declaration-check").checked) {
        errBox.innerText = "Please accept the declaration checkbox before submitting.";
        errBox.classList.remove("d-none");
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = "Compressing files & submitting...";

    try {
        const photoFile = await prepareFile(document.getElementById("file_photo")?.files[0]);
        const docFile = await prepareFile(document.getElementById("file_doc")?.files[0]);
        const passportFile = await prepareFile(document.getElementById("file_passport")?.files[0]);

        // Check total payload size for remaining uncompressed files (e.g. large PDFs)
        const totalPayload = calculateTotalPayloadSize([photoFile, docFile, passportFile]);
        const maxAllowedPayload = 12 * 1024 * 1024; // 12MB safety limit
        if (totalPayload > maxAllowedPayload) {
            throw new Error(`Total attached files size (${(totalPayload / (1024 * 1024)).toFixed(1)}MB) exceeds the maximum upload limit (12MB). Please attach smaller PDF documents.`);
        }

        const fd = new FormData(form);

        if (photoFile) {
            fd.set("photo_path", photoFile);
        }
        if (docFile) {
            fd.set("receipt_path", docFile);
        }
        if (passportFile) {
            fd.set("passport_file", passportFile);
        }

        const res = await fetch("../api/official-registration.php", {
            method: "POST",
            body: fd
        });

        const text = await res.text();
        let data = {};
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error("Server response error (" + res.status + "). Please check your network connection or upload file sizes.");
        }

        if (!res.ok) {
            throw new Error(data.error || "Submission failed.");
        }
        
        // Show success screen
        document.getElementById("ref-id-display").innerText = data.reference_id;
        document.getElementById("track-url-btn").href = `get-involved/status.php?id=${data.reference_id}&email=${encodeURIComponent(document.getElementById("field_email").value)}`;
        localStorage.removeItem(DRAFT_KEY);
        goToStep(6);
    } catch (err) {
        let msg = err.message || "An unexpected error occurred.";
        if (msg.includes("Failed to fetch") || msg.includes("NetworkError") || msg.includes("Failed to execute 'fetch'")) {
            msg = "Network connection failed or upload limit exceeded. Please ensure your attached files are valid and your internet connection is active, then click Submit again.";
        }
        errBox.innerText = msg;
        errBox.classList.remove("d-none");
        submitBtn.disabled = false;
        submitBtn.innerText = "Submit Application";
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
