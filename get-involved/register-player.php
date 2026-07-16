<?php
// get-involved/register-player.php - Modern 3-step Player registration wizard in native PHP
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    max-width: 400px;
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
                    <a href="membership.php" class="back-home-link">Back to HOME Page</a>
                    <div class="mb-2">
                        <a href="../index.php#who-can-participate" class="back-home-link" style="font-size:0.85rem; color:#64748b; background:#f1f5f9; padding:6px 12px; border-radius:20px; display:inline-flex; align-items:center; gap:6px;">
                            <i class="bi bi-info-circle-fill"></i> Athlete Eligibility: Who Can Participate?
                        </a>
                    </div>
                    <div>
                        <img src="../boccia-india-logo.webp" alt="BSFI Logo" class="form-logo-img">
                    </div>
                    <h2 class="form-title-text" id="wizard-title-label">Player Registration</h2>
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

                    <!-- ================= STEP 0: EMAIL VERIFICATION ================= -->
                    <div class="wizard-step active" id="wizard-step-0">
                        <h4 class="mb-3 text-slate-800 font-bold">Email Verification</h4>
                        <p class="text-slate-500 text-sm mb-4">Please verify your email address to begin the registration wizard.</p>
                        
                        <div class="mb-4">
                          <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                          <div class="d-flex gap-2">
                            <input type="email" id="field_email" name="email" class="form-control-custom" placeholder="e.g. player@gmail.com" required>
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
                                <label class="form-label-custom">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" name="mother_name" class="form-control-custom" required>
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

                    <!-- ================= STEP 2: SPORTS & CLASSIFICATION ================= -->
                    <div class="wizard-step" id="wizard-step-2">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 2: Sports &amp; Sizes</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-custom">Age Category <span class="text-danger">*</span></label>
                                <select name="age_category" class="form-select-custom" required>
                                    <option value="">Select Category</option>
                                    <option value="Junior (U18)">Junior (U18)</option>
                                    <option value="Senior">Senior</option>
                                </select>
                            </div>
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label class="form-label-custom">Impairment Type <span class="text-danger">*</span></label>
                                <input type="text" name="impairment_type" class="form-control-custom" placeholder="e.g. Hypertonia" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Boccia Category <span class="text-danger">*</span></label>
                                <select name="classification" class="form-select-custom" required>
                                    <option value="">Select Category</option>
                                    <option value="BC1">BC1</option>
                                    <option value="BC2">BC2</option>
                                    <option value="BC3">BC3</option>
                                    <option value="BC4">BC4</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Do you use a Wheelchair? <span class="text-danger">*</span></label>
                                <select name="wheelchair_status" class="form-select-custom" required>
                                    <option value="">Select Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
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

                    <!-- ================= STEP 3: IDENTITY VERIFICATION ================= -->
                    <div class="wizard-step" id="wizard-step-3">
                        <h4 class="mb-4 text-slate-800 font-bold">Step 3: Identity &amp; Uploads</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Aadhaar Card Number <span class="text-danger">*</span></label>
                                <input type="text" name="aadhaar" pattern="\d{12}" title="Aadhaar number must be exactly 12 digits" maxlength="12" minlength="12" class="form-control-custom" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Permanent Address <span class="text-danger">*</span></label>
                                <textarea name="address" rows="3" class="form-control-custom" required></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Pin Code <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" maxlength="6" class="form-control-custom" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Passport Size Photo (JPG/PNG) <span class="text-danger">*</span></label>
                                <div class="file-drop-zone">
                                    <input type="file" id="file_photo" name="photo_path" accept="image/jpeg,image/png" onchange="updateFileLabel('file_photo', 'photo_label')" required>
                                    <div class="text-slate-400 text-3xl mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <span class="text-sm font-semibold" id="photo_label">Choose Photo File</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">Passport PDF / Scan Copy <span class="text-danger">*</span></label>
                                <div class="file-drop-zone">
                                    <input type="file" id="file_doc" name="receipt_path" accept="application/pdf,image/jpeg,image/png" onchange="updateFileLabel('file_doc', 'doc_label')" required>
                                    <div class="text-slate-400 text-3xl mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <span class="text-sm font-semibold" id="doc_label">Choose Document File</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn-wizard btn-wizard-prev" onclick="goToStep(2)">&larr; Back</button>
                            <button type="button" id="btn-submit-reg" class="btn-wizard btn-wizard-next" onclick="submitApplication()">Submit Application</button>
                        </div>
                    </div>

                    <!-- ================= STEP 4: SUCCESS PAGE ================= -->
                    <div class="wizard-step" id="wizard-step-4">
                        <div class="text-center space-y-4 py-4">
                            <div class="text-5xl text-emerald-500 mb-3"><i class="bi bi-check-circle-fill"></i></div>
                            <h2 class="text-2xl font-black text-slate-800 mb-2">Registration Submitted Successfully</h2>
                            <p class="text-slate-500 text-sm max-w-md mx-auto mb-4">Your application has been logged and is under review. Make sure to save your Tracking Reference ID below.</p>
                            
                            <div class="bg-slate-50 border border-slate-200 p-4 rounded-3 max-w-sm mx-auto mb-4 text-center">
                                <span class="d-block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Your Tracking Reference ID</span>
                                <span class="d-block text-2xl font-black text-orange-500 tracking-wider" id="ref-id-display">BSFI-ATH-2026-000000</span>
                            </div>

                            <div>
                                <a href="status.php" id="track-url-btn" class="btn btn-primary px-4 py-2">Track Application Status</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const DRAFT_KEY = 'bsfi_player_draft_v1';
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
    const errBox = document.getElementById("general-error-box");
    
    errBox.classList.add("d-none");
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errBox.innerText = "Please enter a valid email address.";
        errBox.classList.remove("d-none");
        return;
    }

    const fd = new FormData();
    fd.append("email", email);
    fd.append("csrf_token", csrf);

    fetch("../api/send-otp.php", {
        method: "POST",
        body: fd
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.error || "Failed to deliver OTP.");
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
    const fillPercent = ((stepIndex) / 3) * 100;
    document.getElementById("progress-fill-line").style.width = `${fillPercent}%`;

    currentStep = stepIndex;
    saveDraftData();
}

function saveDraftData() {
    if (currentStep < 1 || currentStep > 3) return;
    
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

function submitApplication() {
    const form = document.getElementById("registration-form");
    const errBox = document.getElementById("general-error-box");
    const submitBtn = document.getElementById("btn-submit-reg");
    
    errBox.classList.add("d-none");
    
    // Check final step validations
    const fields = form.querySelectorAll(`#wizard-step-3 [required]`);
    let valid = true;
    fields.forEach(f => {
        if (!f.checkValidity()) {
            valid = false;
            f.reportValidity();
        }
    });
    if (!valid) return;

    submitBtn.disabled = true;
    submitBtn.innerText = "Submitting...";

    const fd = new FormData(form);

    fetch("../api/player-registration.php", {
        method: "POST",
        body: fd
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.error || "Submission failed.");
        }
        
        // Show success screen
        document.getElementById("ref-id-display").innerText = data.reference_id;
        document.getElementById("track-url-btn").href = `status.php?id=${data.reference_id}&email=${encodeURIComponent(document.getElementById("field_email").value)}`;
        localStorage.removeItem(DRAFT_KEY);
        goToStep(4);
    })
    .catch(err => {
        errBox.innerText = err.message;
        errBox.classList.remove("d-none");
        submitBtn.disabled = false;
        submitBtn.innerText = "Submit Application";
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
