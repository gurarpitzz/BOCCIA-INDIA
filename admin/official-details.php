<?php
// admin/official-details.php - Secure administrative Official profile tracer & history view
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$officialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($officialId <= 0) {
    header("Location: officials.php");
    exit();
}

// Fetch official details
$stmt = $pdo->prepare("SELECT * FROM officials WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$officialId]);
$official = $stmt->fetch();

if (!$official) {
    $page_title = "Official Not Found - BSFI Admin";
    include __DIR__ . '/../includes/header.php';
    echo "<div class='admin-wrapper'><div class='container' style='padding: 3rem 1.5rem; text-align:center;'>";
    echo "<h2 class='text-danger'>Official Not Found</h2>";
    echo "<p class='text-muted'>The requested official profile could not be found or has been deleted.</p>";
    echo "<a href='officials.php' class='admin-btn admin-btn-primary'>Return to Officials Directory</a>";
    echo "</div></div>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_nsrs_id') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = "Security token validation failed (CSRF).";
    } elseif (!$isAdmin) {
        $error = "Only Administrators can modify NSRS IDs.";
    } else {
        $adminPass = $_POST['admin_password'] ?? '';
        $newNsrsId = trim($_POST['new_nsrs_id'] ?? '');

        // Fetch current admin password hash
        $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $currentUser = $userStmt->fetch();

        if (!$currentUser || !password_verify($adminPass, $currentUser['password_hash'])) {
            $error = "Authorization failed! Incorrect administrator password.";
        } elseif (empty($newNsrsId)) {
            $error = "NSRS ID cannot be empty.";
        } else {
            $upNsrs = $pdo->prepare("UPDATE officials SET nsrs_id = ? WHERE id = ?");
            $upNsrs->execute([$newNsrsId, $officialId]);
            logAction($pdo, "Updated Official NSRS ID", "officials", $officialId, "New NSRS ID: $newNsrsId");
            $success = "NSRS ID updated successfully to: " . htmlspecialchars($newNsrsId);
            // Refresh official details
            $stmt->execute([$officialId]);
            $official = $stmt->fetch();
        }
    }
}

$page_title = htmlspecialchars($official['name']) . " - Official Details";
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background-color:#FEF2F2; color:#991B1B;">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 p-3 mb-4 rounded-3" style="background-color:#ECFDF5; color:#065F46;">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumbs / Top Actions -->
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Database / Details</span>
                <h1 class="admin-page-title"><?php echo htmlspecialchars($official['name']); ?></h1>
            </div>
            <div class="d-flex gap-2">
                <a href="officials.php" class="admin-btn admin-btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Back to Directory
                </a>
                <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-danger btn-sm rounded-3 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#deleteProfileModal" style="font-size:0.82rem;">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete Profile
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Left Panel: Core profile card -->
            <div class="col-12 col-lg-4">
                <div class="admin-card text-center" style="padding: 2.5rem 1.5rem; border-top: 5px solid var(--bsfi-green);">
                    <div style="position: relative; display: inline-block; margin-bottom: 1.5rem;">
                        <?php if (!empty($official['photo_path']) && file_exists(__DIR__ . '/../' . $official['photo_path'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($official['photo_path']); ?>" alt="Profile Photo" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #E2E8F0; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                        <?php else: ?>
                            <div style="width: 140px; height: 140px; border-radius: 50%; background-color: var(--navy); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 700; border: 4px solid #E2E8F0; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin: 0 auto;">
                                <?php 
                                    $words = explode(" ", $official['name']);
                                    $initials = isset($words[0][0]) ? $words[0][0] : '';
                                    if (isset($words[1][0])) $initials .= $words[1][0];
                                    echo strtoupper($initials);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h3 style="font-weight: 800; color: var(--navy); margin-bottom: 0.25rem;">
                        <?php echo htmlspecialchars($official['name']); ?>
                    </h3>
                    <div style="font-family: monospace; font-size: 1.15rem; font-weight: 700; color: var(--bsfi-green); margin-bottom: 0.75rem;">
                        ID: <?php echo htmlspecialchars($official['official_reg_no']); ?>
                        <div style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); margin-top: 0.15rem;">
                            🔒 Permanent Federation Identifier (Locked)
                        </div>
                    </div>

                    <div class="mb-4 p-2 rounded-3" style="background:#F1F5F9; border:1px solid #CBD5E1;">
                        <div style="font-size:0.75rem; font-weight:700; color:#475569; text-transform:uppercase;">NSRS ID</div>
                        <div style="font-family:monospace; font-weight:800; font-size:1.05rem; color:#081B4B;">
                            <?php echo htmlspecialchars($official['nsrs_id'] ?: 'Not Assigned'); ?>
                            <?php if ($isAdmin): ?>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none ms-2" data-bs-toggle="modal" data-bs-target="#editNsrsIdModal" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-pen-to-square text-primary"></i> Edit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <?php
                            $badgeClass = 'admin-badge-warning';
                            if ($official['status'] === 'approved') $badgeClass = 'admin-badge-success';
                            if ($official['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                            if ($official['status'] === 'suspended') $badgeClass = 'admin-badge-pending';
                        ?>
                        <span class="admin-badge <?php echo $badgeClass; ?>" style="font-size: 0.85rem; padding: 0.35rem 1rem;">
                            Registry Status: <?php echo ucfirst(htmlspecialchars($official['status'])); ?>
                        </span>
                    </div>

                    <hr style="border-top: 1px solid #E2E8F0; margin: 1.5rem 0;">

                    <!-- Quick Metadata Details -->
                    <div class="text-start" style="font-size: 0.9rem; line-height: 1.8;">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Role / Category:</span>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($official['role']); ?></span>
                        </div>
                        <?php if (!empty($official['designation'])): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Designation:</span>
                            <span class="fw-bold text-dark text-end" style="max-width: 60%;"><?php echo htmlspecialchars($official['designation']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Representing State:</span>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($official['state']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Demographics & PII -->
            <div class="col-12 col-lg-8">
                
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <i class="fa-solid fa-address-card text-success me-2"></i> Demographic &amp; Contact Profile
                    </h3>
                    
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Father's / Spouse's Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($official['father_name'] ?? 'Not Specified'); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Date of Birth / Gender</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($official['dob'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($official['gender']); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Kit Details (T-Shirt / Tracksuit / Shoe)</span>
                                <span class="fw-semibold text-dark">
                                    T-Shirt: <?php echo htmlspecialchars($official['kit_tshirt'] ?? 'N/A'); ?> |
                                    Track: <?php echo htmlspecialchars($official['kit_tracksuit'] ?? 'N/A'); ?> |
                                    Shoes: <?php echo htmlspecialchars($official['kit_shoe'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <!-- PII Fields (Admin role check required) -->
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Aadhaar Number</span>
                                <?php if ($isAdmin): ?>
                                    <?php 
                                        $rawAadhaar = $official['aadhaar'] ?? '';
                                        $maskedAadhaar = 'Not Provided';
                                        if (strlen($rawAadhaar) === 12 && ctype_digit($rawAadhaar)) {
                                            $maskedAadhaar = 'XXXX-XXXX-' . substr($rawAadhaar, -4);
                                        } elseif (!empty($rawAadhaar)) {
                                            $maskedAadhaar = htmlspecialchars($rawAadhaar);
                                        }
                                    ?>
                                    <span id="aadhaar-text" class="fw-bold text-dark" style="font-family: monospace;" data-full="<?php echo htmlspecialchars($rawAadhaar); ?>" data-masked="<?php echo htmlspecialchars($maskedAadhaar); ?>"><?php echo htmlspecialchars($maskedAadhaar); ?></span>
                                    <?php if (strlen($rawAadhaar) === 12): ?>
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-2 text-decoration-none" onclick="toggleAadhaarDisplay()" style="font-size:0.75rem; vertical-align:middle; border:none; background:none;">
                                            <i id="aadhaar-icon" class="fa-solid fa-eye text-primary"></i> <span id="aadhaar-btn-lbl">Show</span>
                                        </button>
                                        <script>
                                        function toggleAadhaarDisplay() {
                                            const txt = document.getElementById('aadhaar-text');
                                            const icon = document.getElementById('aadhaar-icon');
                                            const lbl = document.getElementById('aadhaar-btn-lbl');
                                            if (txt.textContent === txt.dataset.masked) {
                                                txt.textContent = txt.dataset.full;
                                                icon.classList.remove('fa-eye');
                                                icon.classList.add('fa-eye-slash');
                                                lbl.textContent = 'Hide';
                                            } else {
                                                txt.textContent = txt.dataset.masked;
                                                icon.classList.remove('fa-eye-slash');
                                                icon.classList.add('fa-eye');
                                                lbl.textContent = 'Show';
                                            }
                                        }
                                        </script>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted style-italic" style="font-size: 0.85rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted - Admin Only]</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Contact Details (Phone / Email)</span>
                                <?php if ($isAdmin): ?>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($official['phone'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($official['email'] ?? 'N/A'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted style-italic" style="font-size: 0.85rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted - Admin Only]</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Address Details</span>
                                <?php if ($isAdmin): ?>
                                    <span class="fw-semibold text-dark">
                                        <?php echo htmlspecialchars($official['address'] ?? 'N/A'); ?>
                                        <?php if (!empty($official['pincode'])) echo " - Pincode: " . htmlspecialchars($official['pincode']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted style-italic" style="font-size: 0.85rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted - Admin Only]</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Document uploads -->
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #E2E8F0;">
                        <h5 class="fw-bold mb-3 text-secondary" style="font-size: 0.9rem;">Document Registry</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between" style="background:#F8FAFC; max-width: 400px;">
                                    <div>
                                        <i class="fa-solid fa-passport text-primary me-2" style="font-size:1.15rem;"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">ID Proof Document</span>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <?php if (!empty($official['receipt_path'])): ?>
                                            <a href="download-doc.php?file=<?php echo urlencode($official['receipt_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">View Document</a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.75rem;">None Uploaded</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted style-italic" style="font-size: 0.75rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted]</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

</div>

<?php if ($isAdmin): ?>
<!-- Delete Profile Confirmation Modal -->
<div class="modal fade" id="deleteProfileModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white border-0 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold" id="deleteProfileModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Confirm Profile Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" id="delete-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Standard Confirmation State -->
            <div id="delete-form-state">
                <div class="modal-body p-4">
                    <p class="text-dark mb-2">You are about to delete the official profile for <strong><?php echo htmlspecialchars($official['name']); ?></strong> (ID: <?php echo htmlspecialchars($official['official_reg_no']); ?>).</p>
                    <p class="text-danger fw-semibold" style="font-size: 0.9rem;"><i class="fa-solid fa-circle-exclamation me-1"></i> Warning: This action will restrict this profile from registry databases, audits, and official directories. This action is logged.</p>
                    
                    <form id="delete-profile-form" class="mt-3" onsubmit="return false;">
                        <input type="hidden" name="id" value="<?php echo $officialId; ?>">
                        <input type="hidden" name="type" value="official">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        
                        <div class="form-group mb-0">
                            <label for="admin_password" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Enter Administrator Password to Confirm</label>
                            <input type="password" class="form-control rounded-3" id="admin_password" name="password" required placeholder="Your admin account password...">
                        </div>
                        <div id="delete-error-msg" class="alert alert-danger mt-3 d-none rounded-3 py-2 px-3" style="font-size:0.85rem;"></div>
                    </form>
                </div>
                <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirm-delete-btn" class="btn btn-danger rounded-pill px-4 fw-bold">Delete Profile</button>
                </div>
            </div>

            <!-- Countdown Deletion State -->
            <div id="delete-countdown-state" class="d-none">
                <div class="modal-body p-4 text-center">
                    <div style="font-size: 3rem; color: var(--danger); margin-bottom: 1rem;">
                        <i class="fa-solid fa-hourglass-half fa-spin"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Profile Deletion Scheduled</h4>
                    <p class="text-secondary" style="font-size: 0.95rem;">Deleting profile in <span id="countdown-timer-val" class="fw-bold text-danger">10</span> seconds...</p>
                    
                    <!-- Backward going timer bar -->
                    <div class="progress my-4" style="height: 12px; background-color: #E2E8F0; border-radius: 99px; overflow: hidden;">
                        <div id="countdown-progress-bar" class="progress-bar bg-danger" role="progressbar" style="width: 100%; transition: width 0.1s linear;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <p class="text-muted mb-0" style="font-size:0.82rem;">You can safely cancel or undo this action before the timer expires.</p>
                </div>
                <div class="modal-footer border-0 p-3 bg-light justify-content-center rounded-bottom-4">
                    <button type="button" id="undo-delete-btn" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Undo Deletion
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const confirmBtn = document.getElementById("confirm-delete-btn");
    const errorMsg = document.getElementById("delete-error-msg");
    const passwordInput = document.getElementById("admin_password");
    const modalCloseBtn = document.getElementById("delete-modal-close");
    
    const formState = document.getElementById("delete-form-state");
    const countdownState = document.getElementById("delete-countdown-state");
    const undoBtn = document.getElementById("undo-delete-btn");
    const timerVal = document.getElementById("countdown-timer-val");
    const progressBar = document.getElementById("countdown-progress-bar");
    
    let countdownInterval = null;
    let progressInterval = null;
    let deleteTimeout = null;
    
    const DURATION = 10; // 10 seconds
    let timeLeft = DURATION;
    
    // Dry-run password check
    confirmBtn.addEventListener("click", function() {
        const password = passwordInput.value.trim();
        if (!password) {
            errorMsg.textContent = "Please enter your password.";
            errorMsg.classList.remove("d-none");
            return;
        }
        
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying...';
        errorMsg.classList.add("d-none");
        
        const formData = new FormData(document.getElementById("delete-profile-form"));
        formData.append("check_only", "1");
        
        fetch("api/delete-profile.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200) {
                // Password matches! Start 40s countdown state
                startDeletionCountdown();
            } else {
                errorMsg.textContent = res.body.error || "Verification failed.";
                errorMsg.classList.remove("d-none");
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = "Delete Profile";
            }
        })
        .catch(err => {
            errorMsg.textContent = "Server connection error. Please try again.";
            errorMsg.classList.remove("d-none");
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = "Delete Profile";
        });
    });
    
    function startDeletionCountdown() {
        // Swap UI states
        formState.classList.add("d-none");
        countdownState.classList.remove("d-none");
        modalCloseBtn.classList.add("d-none"); // Prevent closing modal via X
        
        timeLeft = DURATION;
        timerVal.textContent = timeLeft;
        progressBar.style.width = "100%";
        
        // Progress bar smooth decrementor (runs every 100ms)
        const totalSteps = DURATION * 10;
        let currentStep = totalSteps;
        progressInterval = setInterval(function() {
            currentStep--;
            const percentage = (currentStep / totalSteps) * 100;
            progressBar.style.width = percentage + "%";
            progressBar.setAttribute("aria-valuenow", percentage);
            
            if (currentStep <= 0) {
                clearInterval(progressInterval);
            }
        }, 100);
        
        // Timer countdown tick (runs every second)
        countdownInterval = setInterval(function() {
            timeLeft--;
            timerVal.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
        
        // Final execute timeout after 40 seconds
        deleteTimeout = setTimeout(executeDeletion, DURATION * 1000);
    }
    
    // Undo trigger
    undoBtn.addEventListener("click", cancelDeletionCountdown);
    
    function cancelDeletionCountdown() {
        // Clear all timers
        clearTimeout(deleteTimeout);
        clearInterval(countdownInterval);
        clearInterval(progressInterval);
        
        // Reset inputs and UI states
        timeLeft = DURATION;
        passwordInput.value = "";
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = "Delete Profile";
        
        formState.classList.remove("d-none");
        countdownState.classList.add("d-none");
        modalCloseBtn.classList.remove("d-none");
    }
    
    function executeDeletion() {
        progressBar.style.width = "0%";
        undoBtn.disabled = true;
        undoBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';
        
        const formData = new FormData(document.getElementById("delete-profile-form"));
        
        fetch("api/delete-profile.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200) {
                window.location.href = "officials.php";
            } else {
                alert("Deletion error: " + (res.body.error || "Failed to complete deletion."));
                cancelDeletionCountdown();
            }
        })
        .catch(err => {
            alert("Database connection timeout. Please reload and try again.");
            cancelDeletionCountdown();
        });
    }
});
</script>

<!-- Edit NSRS ID Modal -->
<div class="modal fade" id="editNsrsIdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0" style="background:#081B4B; color:white; border-radius: 16px 16px 0 0; padding:1.25rem 1.5rem;">
                <h5 class="modal-title font-bold text-white"><i class="fa-solid fa-shield-halved me-2"></i> Edit NSRS ID</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="official-details.php?id=<?php echo $official['id']; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update_nsrs_id">
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 text-dark small mb-3" style="background:#FEF3C7;">
                        <i class="fa-solid fa-lock me-1"></i> Security Verification: Modifying an official's NSRS ID requires your current administrator account password.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NSRS ID <span class="text-danger">*</span></label>
                        <input type="text" name="new_nsrs_id" class="form-control" value="<?php echo htmlspecialchars($official['nsrs_id'] ?? ''); ?>" placeholder="Enter NSRS ID..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Account Password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_password" class="form-control" placeholder="Enter your login password..." required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background:#081B4B; border-color:#081B4B;">Authorize &amp; Save NSRS ID</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
