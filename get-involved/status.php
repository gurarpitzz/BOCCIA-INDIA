<?php
// get-involved/status.php - Application Registration Status Inquiry Portal
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Track Application Status - Boccia India";
include __DIR__ . '/../includes/header.php';

// Auto-fill query params if present
$prefillId = trim($_GET['id'] ?? '');
$prefillEmail = trim($_GET['email'] ?? '');
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

.outer-status-bg {
    background-color: var(--boccia-maroon);
    padding: 80px 0;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.status-card-container {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    max-width: 650px;
    margin: 0 auto;
    width: 100%;
}

.status-card-body {
    padding: 50px;
}

@media (max-width: 767px) {
    .outer-status-bg {
        padding: 40px 0;
    }
    .status-card-body {
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

.form-control-custom {
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 0.95rem;
    background-color: #ffffff;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control-custom:focus {
    border-color: var(--boccia-maroon);
    outline: none;
    box-shadow: 0 0 0 3px rgba(140, 32, 28, 0.15);
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
    width: 100%;
}

.btn-submit-custom:hover {
    background-color: var(--boccia-navy);
}

/* Badge styles */
.badge-status {
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
}

.badge-submitted {
    background-color: #EFF6FF;
    color: #1E40AF;
}

.badge-review {
    background-color: #FEF3C7;
    color: #92400E;
}

.badge-action {
    background-color: #FEE2E2;
    color: #991B1B;
}

.badge-approved {
    background-color: #D1FAE5;
    color: #065F46;
}
</style>

<div class="outer-status-bg">
    <div class="container">
        <div class="status-card-container">
            <div class="status-card-body">
                
                <div class="form-header-box">
                    <a href="get-involved/membership.php" class="back-home-link">Back to Membership Portal</a>
                    <div>
                        <img src="../boccia-india-logo.webp" alt="BSFI Logo" class="form-logo-img">
                    </div>
                    <h2 class="form-title-text">Registration Status Tracker</h2>
                </div>

                <div id="status-error-box" class="alert alert-danger border-0 p-3 mb-4 rounded-3 d-none" style="background-color: #FEF2F2; color: #991B1B;"></div>

                <!-- Input Form Block -->
                <form id="status-inquiry-form" onsubmit="fetchStatusDetails(event)">
                    <div class="mb-4">
                        <label class="form-label-custom">Reference ID <span class="text-danger">*</span></label>
                        <input type="text" id="inquiry_ref_id" class="form-control-custom" placeholder="e.g. BSFI-ATH-2026-000124" value="<?php echo htmlspecialchars($prefillId); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="inquiry_email" class="form-control-custom" placeholder="e.g. player@gmail.com" value="<?php echo htmlspecialchars($prefillEmail); ?>" required>
                    </div>
                    
                    <button type="submit" id="btn-submit-inquiry" class="btn-submit-custom">Check Status</button>
                </form>

                <!-- Status Results Display Block (Initially Hidden) -->
                <div id="status-results-block" class="d-none mt-5 pt-4 border-t border-slate-100">
                    <h4 class="font-bold text-slate-800 mb-4">Application Details</h4>
                    
                    <div class="space-y-4">
                        <div class="d-flex justify-content-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-500 font-semibold">Applicant Name:</span>
                            <span class="text-sm text-slate-800 font-bold" id="result-name-field">-</span>
                        </div>
                        
                        <div class="d-flex justify-content-between py-2 border-b border-slate-100 align-items-center">
                            <span class="text-sm text-slate-500 font-semibold">Review Status:</span>
                            <span id="result-status-badge" class="badge-status badge-submitted">Submitted</span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-b border-slate-100">
                            <span class="text-sm text-slate-500 font-semibold">Submitted Date:</span>
                            <span class="text-sm text-slate-800 font-bold" id="result-submitted-field">-</span>
                        </div>

                        <div class="d-flex justify-content-between py-2">
                            <span class="text-sm text-slate-500 font-semibold">Last Updated:</span>
                            <span class="text-sm text-slate-800 font-bold" id="result-updated-field">-</span>
                        </div>
                    </div>

                    <!-- Explanatory message box -->
                    <div class="mt-4 p-4 rounded-3 bg-slate-50 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-800 mb-1" id="result-info-title">Your Application is Received</h5>
                        <p class="text-xs text-slate-500 m-0" id="result-info-desc">We have successfully logged your registration details. An official reviewer will process your details shortly.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ref = document.getElementById("inquiry_ref_id").value;
    const email = document.getElementById("inquiry_email").value;
    if (ref && email) {
        document.getElementById("status-inquiry-form").dispatchEvent(new Event("submit"));
    }
});

function fetchStatusDetails(event) {
    event.preventDefault();
    
    const id = document.getElementById("inquiry_ref_id").value.trim();
    const email = document.getElementById("inquiry_email").value.trim();
    const errBox = document.getElementById("status-error-box");
    const results = document.getElementById("status-results-block");
    const submitBtn = document.getElementById("btn-submit-inquiry");

    errBox.classList.add("d-none");
    results.classList.add("d-none");
    submitBtn.disabled = true;
    submitBtn.innerText = "Checking...";

    fetch(`../api/status.php?id=${encodeURIComponent(id)}&email=${encodeURIComponent(email)}`)
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.error || "Failed to find matching application record.");
        }

        // Fill data fields
        document.getElementById("result-name-field").innerText = data.name;
        
        // Format dates
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        const subDate = new Date(data.submittedAt).toLocaleDateString('en-GB', options);
        const updDate = new Date(data.updatedAt).toLocaleDateString('en-GB', options);

        document.getElementById("result-submitted-field").innerText = subDate;
        document.getElementById("result-updated-field").innerText = updDate;

        // Configure status badge & explanations
        const badge = document.getElementById("result-status-badge");
        const infoTitle = document.getElementById("result-info-title");
        const infoDesc = document.getElementById("result-info-desc");

        badge.className = "badge-status";
        
        switch(data.status) {
            case 'submitted':
                badge.classList.add("badge-submitted");
                badge.innerText = "Submitted";
                infoTitle.innerText = "Application is Received";
                infoDesc.innerText = "Your registration details have been securely logged. An administrator will start the verification process shortly.";
                break;
            case 'under review':
                badge.classList.add("badge-review");
                badge.innerText = "Under Review";
                infoTitle.innerText = "Currently Under Review";
                infoDesc.innerText = "An administrator is verifying your document proofs and qualifications. Please check back later.";
                break;
            case 'action required':
                badge.classList.add("badge-action");
                badge.innerText = "Action Required";
                infoTitle.innerText = "Correction Required";
                infoDesc.innerText = "There is an issue with your uploaded document files or details. Please check your email for correction guidelines or contact support.";
                break;
            case 'approved':
                badge.classList.add("badge-approved");
                badge.innerText = "Approved";
                infoTitle.innerText = "Registration Approved";
                infoDesc.innerText = "Congratulations! Your application has been approved. You are now formally registered with the Boccia Sports Federation of India.";
                break;
        }

        results.classList.remove("d-none");
        submitBtn.disabled = false;
        submitBtn.innerText = "Check Status";
    })
    .catch(err => {
        errBox.innerText = err.message;
        errBox.classList.remove("d-none");
        submitBtn.disabled = false;
        submitBtn.innerText = "Check Status";
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
