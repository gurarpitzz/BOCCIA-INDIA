<?php
// athlete-details.php - Secure administrative Athlete profile tracer & history view
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$athleteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($athleteId <= 0) {
    header("Location: athletes.php");
    exit();
}

// Fetch athlete details
$stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$athleteId]);
$athlete = $stmt->fetch();

if (!$athlete) {
    $page_title = "Athlete Not Found - BSFI Admin";
    include __DIR__ . '/../includes/header.php';
    echo "<div class='admin-wrapper'><div class='container' style='padding: 3rem 1.5rem; text-align:center;'>";
    echo "<h2 class='text-danger'>Athlete Not Found</h2>";
    echo "<p class='text-muted'>The requested athlete profile could not be found or has been deleted.</p>";
    echo "<a href='athletes.php' class='admin-btn admin-btn-primary'>Return to Athlete Directory</a>";
    echo "</div></div>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$page_title = htmlspecialchars($athlete['full_name']) . " - Profile Tracker";
include __DIR__ . '/../includes/header.php';

// Fetch tournament/event history
$histStmt = $pdo->prepare("SELECT * FROM athlete_history WHERE athlete_id = ? ORDER BY event_year DESC, id DESC");
$histStmt->execute([$athleteId]);
$historyList = $histStmt->fetchAll();

// Fetch status change history joined with users table
$statusStmt = $pdo->prepare("
    SELECT h.*, u.username as changer_name 
    FROM athlete_status_history h 
    LEFT JOIN users u ON h.changed_by = u.id 
    WHERE h.athlete_id = ? 
    ORDER BY h.changed_at DESC
");
$statusStmt->execute([$athleteId]);
$statusList = $statusStmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_nsrs_id') {
        if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
            $error = "Your session has expired due to inactivity. Please refresh the page and try again.";
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
            } elseif (($chkUnique = isNsrsIdUnique($pdo, $newNsrsId, $athleteId, null)) !== true) {
                $error = $chkUnique;
            } else {
                $upNsrs = $pdo->prepare("UPDATE athletes SET nsrs_id = ? WHERE id = ?");
                $upNsrs->execute([$newNsrsId, $athleteId]);
                logAction($pdo, "Updated Athlete NSRS ID", "athletes", $athleteId, "New NSRS ID: $newNsrsId");
                $success = "NSRS ID updated successfully to: " . htmlspecialchars($newNsrsId);
                // Refresh athlete details
                $stmt->execute([$athleteId]);
                $athlete = $stmt->fetch();
            }
        }
    } elseif ($_POST['action'] === 'update_athlete_details') {
        if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
            $error = "Your session has expired due to inactivity. Please refresh the page and try again.";
        } elseif (!$isAdmin) {
            $error = "Only Administrators can modify athlete profile details.";
        } else {
            $father_name = trim($_POST['father_name'] ?? '');
            $mother_name = trim($_POST['mother_name'] ?? '');
            $age_category = trim($_POST['age_category'] ?? '');
            $classification = trim($_POST['classification'] ?? '');
            $impairment_type = trim($_POST['impairment_type'] ?? '');
            $representing_for = trim($_POST['representing_for'] ?? '');
            $wheelchair_status = trim($_POST['wheelchair_status'] ?? '');
            $kit_tshirt = trim($_POST['kit_tshirt'] ?? '');
            $kit_tracksuit = trim($_POST['kit_tracksuit'] ?? '');
            $kit_shoe = trim($_POST['kit_shoe'] ?? '');
            $aadhaar = trim($_POST['aadhaar'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');

            $upStmt = $pdo->prepare("UPDATE athletes SET 
                father_name = NULLIF(?, ''),
                mother_name = NULLIF(?, ''),
                age_category = NULLIF(?, ''),
                classification = NULLIF(?, ''),
                impairment_type = NULLIF(?, ''),
                state = NULLIF(?, ''),
                representing_for = NULLIF(?, ''),
                wheelchair_status = NULLIF(?, ''),
                kit_tshirt = NULLIF(?, ''),
                kit_tracksuit = NULLIF(?, ''),
                kit_shoe = NULLIF(?, ''),
                aadhaar = NULLIF(?, ''),
                mobile = NULLIF(?, ''),
                email = NULLIF(?, ''),
                address = NULLIF(?, ''),
                pincode = NULLIF(?, '')
                WHERE id = ?");
            
            $upStmt->execute([
                $father_name, $mother_name, $age_category, $classification, $impairment_type,
                $representing_for, $representing_for, $wheelchair_status,
                $kit_tshirt, $kit_tracksuit, $kit_shoe, $aadhaar, $mobile, $email,
                $address, $pincode, $athleteId
            ]);

            logAction($pdo, "Updated Athlete Profile Details", "athletes", $athleteId, "Updated profile fields directly via Admin Panel");
            $success = "Athlete profile details updated successfully!";
            
            // Refresh athlete details
            $stmt->execute([$athleteId]);
            $athlete = $stmt->fetch();
        }
    }
}
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <?php if (!empty($error)): ?>
            <div class="alert border-0 p-3 mb-4 rounded-4 shadow-sm d-flex align-items-center gap-3" style="background-color: #FEF2F2; border-left: 6px solid #DC2626 !important; color: #991B1B;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background-color: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1" style="font-size: 0.98rem; color: #991B1B;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Access &amp; Authorization Error</h6>
                    <div style="font-size: 0.92rem; font-weight: 700; color: #7F1D1D;"><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var modalEl = document.getElementById("editNsrsIdModal");
                if (modalEl) {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
            </script>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert border-0 p-3 mb-4 rounded-4 shadow-sm d-flex align-items-center gap-3" style="background-color:#ECFDF5; border-left: 6px solid #10B981 !important; color:#065F46;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background-color: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1" style="font-size: 0.98rem; color: #065F46;">Authorization Confirmed</h6>
                    <div style="font-size: 0.92rem; font-weight: 700; color: #047857;"><?php echo htmlspecialchars($success); ?></div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Breadcrumbs / Top Actions -->
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Database / Details</span>
                <h1 class="admin-page-title"><?php echo htmlspecialchars($athlete['full_name']); ?></h1>
            </div>
            <div class="d-flex gap-2">
                <a href="athletes.php" class="admin-btn admin-btn-outline">
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
                        <?php if (!empty($athlete['photo_path']) && file_exists(__DIR__ . '/../' . $athlete['photo_path'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($athlete['photo_path']); ?>" alt="Profile Photo" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #E2E8F0; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                        <?php else: ?>
                            <div style="width: 140px; height: 140px; border-radius: 50%; background-color: var(--navy); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 700; border: 4px solid #E2E8F0; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin: 0 auto;">
                                <?php 
                                    $words = explode(" ", $athlete['full_name']);
                                    $initials = isset($words[0][0]) ? $words[0][0] : '';
                                    if (isset($words[1][0])) $initials .= $words[1][0];
                                    echo strtoupper($initials);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($athlete['is_legacy_registry']): ?>
                            <span class="position-absolute bottom-0 end-0 translate-middle-x badge rounded-pill bg-danger border border-white" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;" title="Imported from official legacy registry (0001 - 0099)">
                                <i class="fa-solid fa-shield"></i> Legacy Registry
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 style="font-weight: 800; color: var(--navy); margin-bottom: 0.25rem;">
                        <?php echo htmlspecialchars($athlete['full_name']); ?>
                    </h3>
                    <div style="font-family: monospace; font-size: 1.15rem; font-weight: 700; color: var(--bsfi-green); margin-bottom: 0.75rem;">
                        ID: <?php echo htmlspecialchars($athlete['regn_no']); ?>
                        <div style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); margin-top: 0.15rem;">
                            🔒 Permanent Federation Identifier (Locked)
                        </div>
                    </div>

                    <div class="mb-4 p-2 rounded-3" style="background:#F1F5F9; border:1px solid #CBD5E1;">
                        <div style="font-size:0.75rem; font-weight:700; color:#475569; text-transform:uppercase;">NSRS ID</div>
                        <div style="font-family:monospace; font-weight:800; font-size:1.05rem; color:#081B4B;">
                            <?php echo htmlspecialchars($athlete['nsrs_id'] ?: 'Not Assigned'); ?>
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
                            if ($athlete['status'] === 'approved') $badgeClass = 'admin-badge-success';
                            if ($athlete['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                            if ($athlete['status'] === 'archived') $badgeClass = 'admin-badge-pending';
                        ?>
                        <span class="admin-badge <?php echo $badgeClass; ?>" style="font-size: 0.85rem; padding: 0.35rem 1rem;">
                            Registry Status: <?php echo ucfirst(htmlspecialchars($athlete['status'])); ?>
                        </span>
                    </div>

                    <hr style="border-top: 1px solid #E2E8F0; margin: 1.5rem 0;">

                    <!-- Quick Metadata Details -->
                    <div class="text-start" style="font-size: 0.9rem; line-height: 1.8;">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Classification:</span>
                            <span class="fw-bold text-dark">
                                <?php echo htmlspecialchars($athlete['classification']); ?>
                                <?php if ($isAdmin): ?>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none ms-1" data-bs-toggle="modal" data-bs-target="#changeCategoryModal" style="font-size:0.75rem; vertical-align:middle;">
                                        <i class="fa-solid fa-pencil text-primary"></i> Edit
                                    </button>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Impairment Type:</span>
                            <span class="fw-bold text-dark text-end" style="max-width: 60%;"><?php echo htmlspecialchars($athlete['impairment_type'] ?? 'Not Specified'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Representing State:</span>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($athlete['representing_for']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Wheelchair Status:</span>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($athlete['wheelchair_status'] ?? 'None'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Tabs for details & logs -->
            <div class="col-12 col-lg-8">
                
                <!-- Demographic & PII Details -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <h3 class="admin-card-title m-0">
                            <i class="fa-solid fa-address-card text-success me-2"></i> Demographic &amp; Registration Profile
                        </h3>
                        <?php if ($isAdmin): ?>
                            <button type="button" class="admin-btn admin-btn-outline" data-bs-toggle="modal" data-bs-target="#editAthleteProfileModal" style="font-size:0.8rem; padding:0.4rem 0.85rem;">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Father's Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($athlete['father_name'] ?? 'Not Specified'); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Mother's Name</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($athlete['mother_name'] ?? 'Not Specified'); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Date of Birth / Gender</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($athlete['dob']); ?> • <?php echo htmlspecialchars($athlete['gender']); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Age Category</span>
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($athlete['age_category'] ?? 'Not Specified'); ?></span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Kit Details (T-Shirt / Tracksuit / Shoe)</span>
                                <span class="fw-semibold text-dark">
                                    T-Shirt: <?php echo htmlspecialchars($athlete['kit_tshirt'] ?? 'N/A'); ?> |
                                    Track: <?php echo htmlspecialchars($athlete['kit_tracksuit'] ?? 'N/A'); ?> |
                                    Shoes: <?php echo htmlspecialchars($athlete['kit_shoe'] ?? 'N/A'); ?>
                                </span>
                            </div>

                            <!-- PII Fields (Admin role check required) -->
                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Aadhaar Number</span>
                                <?php if ($isAdmin): ?>
                                    <?php 
                                        $rawAadhaar = $athlete['aadhaar'] ?? '';
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
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($athlete['mobile'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($athlete['email'] ?? 'N/A'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted style-italic" style="font-size: 0.85rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted - Admin Only]</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size:0.8rem; font-weight:600;">Permanent Address</span>
                                <?php if ($isAdmin): ?>
                                    <span class="fw-semibold text-dark">
                                        <?php echo htmlspecialchars($athlete['address'] ?? 'N/A'); ?>
                                        <?php if (!empty($athlete['pincode'])) echo " - Pincode: " . htmlspecialchars($athlete['pincode']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted style-italic" style="font-size: 0.85rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted - Admin Only]</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- File uploads & documents -->
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #E2E8F0;">
                        <h5 class="fw-bold mb-3 text-secondary" style="font-size: 0.9rem;">Document Registry</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between" style="background:#F8FAFC; max-width: 400px;">
                                    <div>
                                        <i class="fa-solid fa-passport text-primary me-2" style="font-size:1.15rem;"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">Passport / Identity File</span>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <?php if (!empty($athlete['receipt_path'])): ?>
                                            <a href="download-doc.php?file=<?php echo urlencode($athlete['receipt_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">View Document</a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.75rem;">None Uploaded</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted style-italic" style="font-size: 0.75rem;"><i class="fa-solid fa-lock text-danger me-1"></i> [Restricted]</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between" style="background:#F8FAFC; max-width: 400px;">
                                    <div>
                                        <i class="fa-solid fa-file-medical text-danger me-2" style="font-size:1.15rem;"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">Medical Certificate</span>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <?php if (!empty($athlete['medical_certificate'])): ?>
                                            <a href="download-doc.php?file=<?php echo urlencode($athlete['medical_certificate']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">View Document</a>
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

                <!-- Tournament & Event History -->
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <h3 class="admin-card-title m-0">
                            <i class="fa-solid fa-trophy text-warning me-2"></i> Tournament &amp; Performance History
                        </h3>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch m-0" style="font-size: 0.85rem;">
                                <input class="form-check-input" type="checkbox" id="toggleArchivedSwitch" onchange="toggleArchivedVisibility(this.checked)">
                                <label class="form-check-label text-muted fw-semibold" for="toggleArchivedSwitch">Show Archived</label>
                            </div>
                            <?php if ($isAdmin): ?>
                                <button type="button" class="btn btn-sm btn-primary rounded-3 fw-bold px-3" onclick="openHistoryModal(0)">
                                    <i class="fa-solid fa-plus me-1"></i> Add Performance Record
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (count($historyList) > 0): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table" style="margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Event Name</th>
                                        <th>Level</th>
                                        <th>State Represented</th>
                                        <th>Class</th>
                                        <th>Rank / Result</th>
                                        <th>Remarks</th>
                                        <?php if ($isAdmin): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historyList as $hist): 
                                        $isArchived = !empty($hist['deleted_at']);
                                        $rowClass = $isArchived ? 'archived-row table-secondary text-muted d-none' : '';
                                    ?>
                                        <tr id="history-row-<?php echo $hist['id']; ?>" class="<?php echo $rowClass; ?>">
                                            <td class="fw-bold">
                                                <?php echo htmlspecialchars($hist['event_year']); ?>
                                                <?php if ($isArchived): ?>
                                                    <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Archived</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold text-navy"><?php echo htmlspecialchars($hist['event_name']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($hist['event_level'] ?? 'National'); ?></span></td>
                                            <td><?php echo htmlspecialchars($hist['state_represented'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hist['classification'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php 
                                                $rankVal = htmlspecialchars($hist['rank'] ?? '');
                                                $isMedal = in_array(strtolower($rankVal), ['gold', 'silver', 'bronze']);
                                                if ($isMedal): 
                                                ?>
                                                    <span class="badge" style="background-color: <?php 
                                                        $m = strtolower($rankVal);
                                                        if ($m === 'gold') echo '#D97706; color:#fff';
                                                        elseif ($m === 'silver') echo '#94A3B8; color:#fff';
                                                        elseif ($m === 'bronze') echo '#B45309; color:#fff';
                                                    ?>">
                                                        <i class="fa-solid fa-award"></i> <?php echo ucfirst($rankVal); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="fw-semibold text-dark"><?php echo $rankVal ?: 'Participant'; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:0.8rem; color:var(--text-secondary); max-width:180px;" title="<?php echo htmlspecialchars($hist['remarks'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($hist['remarks'] ?? '-'); ?>
                                            </td>
                                            <?php if ($isAdmin): ?>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($isArchived): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success p-1 px-2" onclick="restoreHistory(<?php echo $hist['id']; ?>)" title="Restore Record">
                                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2" onclick="purgeHistory(<?php echo $hist['id']; ?>)" title="Delete Permanently">
                                                                <i class="fa-solid fa-trash"></i> Delete
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary p-1 px-2" onclick="editHistory(<?php echo htmlspecialchars(json_encode($hist)); ?>)" title="Edit Record">
                                                                <i class="fa-solid fa-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2" onclick="deleteHistory(<?php echo $hist['id']; ?>)" title="Archive Record">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 border border-dashed rounded-3" style="background-color:#FAF7F0;">
                            <i class="fa-solid fa-medal text-muted" style="font-size: 2.5rem; opacity: 0.5; margin-bottom:1rem; display:block;"></i>
                            <span class="text-muted style-italic">No official tournament history records available for this athlete profile.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Registration Audit / Status History Logs -->
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <i class="fa-solid fa-list-check text-primary me-2"></i> Registry Status Change Logs
                    </h3>

                    <?php if (count($statusList) > 0): ?>
                        <div class="admin-timeline">
                            <?php foreach ($statusList as $log): ?>
                                <?php
                                    $accentClass = '';
                                    if ($log['new_status'] === 'approved') $accentClass = 'accent-green';
                                    elseif ($log['new_status'] === 'rejected') $accentClass = 'accent-danger';
                                    else $accentClass = 'accent-saffron';
                                ?>
                                <div class="admin-timeline-item <?php echo $accentClass; ?>">
                                    <h5 class="admin-timeline-title">
                                        Status updated to <span class="text-uppercase fw-bold text-primary"><?php echo htmlspecialchars($log['new_status']); ?></span>
                                        <?php if (!empty($log['old_status'])): ?>
                                            <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted);"> (from <?php echo htmlspecialchars($log['old_status']); ?>)</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="admin-timeline-desc">
                                        <strong>Changed by:</strong> <?php echo htmlspecialchars($log['changer_name'] ?? 'System/Administrator'); ?><br>
                                        <strong>Auditor Comments:</strong> <?php echo htmlspecialchars($log['remarks'] ?? 'No comments provided.'); ?>
                                    </p>
                                    <span class="admin-timeline-time">
                                        <i class="fa-regular fa-clock me-1"></i> <?php echo date("F j, Y, g:i a", strtotime($log['changed_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 border border-dashed rounded-3" style="background-color:#FAF7F0;">
                            <i class="fa-solid fa-history text-muted" style="font-size: 2rem; opacity: 0.5; margin-bottom:0.75rem; display:block;"></i>
                            <span class="text-muted style-italic">No status transition audits recorded. Profile remains in the original imported/initialized state.</span>
                        </div>
                    <?php endif; ?>
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
                    <p class="text-dark mb-2">You are about to delete the athlete profile for <strong><?php echo htmlspecialchars($athlete['full_name']); ?></strong> (Reg No: <?php echo htmlspecialchars($athlete['regn_no']); ?>).</p>
                    <p class="text-danger fw-semibold" style="font-size: 0.9rem;"><i class="fa-solid fa-circle-exclamation me-1"></i> Warning: This action will restrict this profile from registry databases, audits, and tournament directories. This action is logged.</p>
                    
                    <form id="delete-profile-form" class="mt-3" onsubmit="return false;">
                        <input type="hidden" name="id" value="<?php echo $athleteId; ?>">
                        <input type="hidden" name="type" value="athlete">
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
                window.location.href = "athletes.php";
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
<?php endif; ?>

<?php if ($isAdmin): ?>
<!-- Change Category Modal -->
<div class="modal fade" id="changeCategoryModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h3 class="modal-title fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">Update Classification</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="category-modal-close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="change-category-form" onsubmit="return false;">
                    <input type="hidden" name="id" value="<?php echo $athleteId; ?>">
                    <input type="hidden" name="field" value="classification">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="form-group mb-3">
                        <label for="new_category_val" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Select New Boccia Category</label>
                        <select class="form-select rounded-3" id="new_category_val" name="value" required>
                            <option value="">Choose Category</option>
                            <option value="BC1" <?php echo $athlete['classification'] === 'BC1' ? 'selected' : ''; ?>>BC1</option>
                            <option value="BC2" <?php echo $athlete['classification'] === 'BC2' ? 'selected' : ''; ?>>BC2</option>
                            <option value="BC3" <?php echo $athlete['classification'] === 'BC3' ? 'selected' : ''; ?>>BC3</option>
                            <option value="BC4" <?php echo $athlete['classification'] === 'BC4' ? 'selected' : ''; ?>>BC4</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="category_admin_password" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Confirm Administrator Password</label>
                        <input type="password" class="form-control rounded-3" id="category_admin_password" name="password" required placeholder="Your admin account password...">
                    </div>
                    <div id="category-error-msg" class="alert alert-danger mt-3 d-none rounded-3 py-2 px-3" style="font-size:0.85rem;"></div>
                </form>
            </div>
            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-category-btn" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const confirmBtn = document.getElementById("confirm-category-btn");
    const errorMsg = document.getElementById("category-error-msg");
    const passwordInput = document.getElementById("category_admin_password");
    const newCategorySelect = document.getElementById("new_category_val");
    
    confirmBtn.addEventListener("click", function() {
        const password = passwordInput.value.trim();
        const value = newCategorySelect.value;
        if (!value) {
            errorMsg.textContent = "Please select a category.";
            errorMsg.classList.remove("d-none");
            return;
        }
        if (!password) {
            errorMsg.textContent = "Please enter your password.";
            errorMsg.classList.remove("d-none");
            return;
        }
        
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';
        errorMsg.classList.add("d-none");
        
        const formData = new FormData(document.getElementById("change-category-form"));
        
        fetch("api/update-athlete.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200) {
                // Success! Reload the page to display updated category
                window.location.reload();
            } else {
                errorMsg.textContent = res.body.error || "Failed to update category.";
                errorMsg.classList.remove("d-none");
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = "Save Changes";
            }
        })
        .catch(err => {
            errorMsg.textContent = "Server connection error. Please try again.";
            errorMsg.classList.remove("d-none");
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = "Save Changes";
        });
    });
});
</script>
<!-- Tournament History Modal -->
<div class="modal fade" id="historyModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h3 class="modal-title fw-bold text-dark" id="history-modal-title" style="font-family: 'Outfit', sans-serif;">Add Performance Record</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="history-form" onsubmit="return false;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="athlete_id" value="<?php echo $athleteId; ?>">
                    <input type="hidden" name="history_id" id="history_id" value="0">
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="hist_event_name" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Event Name *</label>
                            <input type="text" class="form-control rounded-3" id="hist_event_name" name="event_name" required placeholder="e.g. 9th National Boccia Championship">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="hist_event_year" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Event Year *</label>
                            <input type="number" class="form-control rounded-3" id="hist_event_year" name="event_year" required min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="hist_classification" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Classification *</label>
                            <select class="form-select rounded-3" id="hist_classification" name="classification" required>
                                <option value="">Select Category</option>
                                <?php foreach (CLASSIFICATIONS as $class): ?>
                                    <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="hist_event_level" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Event Level *</label>
                            <select class="form-select rounded-3" id="hist_event_level" name="event_level" required>
                                <option value="">Select Level</option>
                                <?php foreach (EVENT_LEVELS as $lvl): ?>
                                    <option value="<?php echo $lvl; ?>"><?php echo $lvl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="hist_state_represented" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">State Represented *</label>
                            <select class="form-select rounded-3" id="hist_state_represented" name="state_represented" required>
                                <option value="">Select State</option>
                                <?php foreach (INDIAN_STATES as $stateOpt): ?>
                                    <option value="<?php echo htmlspecialchars($stateOpt); ?>" <?php echo (strtolower(trim($athlete['representing_for'])) === strtolower(trim($stateOpt)) || strtolower(trim($athlete['state'])) === strtolower(trim($stateOpt))) ? 'selected' : ''; ?>><?php echo htmlspecialchars($stateOpt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="hist_rank" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Rank / Result *</label>
                            <select class="form-select rounded-3" id="hist_rank" name="rank" required onchange="toggleCustomRankField()">
                                <option value="">Select Result</option>
                                <?php foreach (RESULT_OPTIONS as $resOpt): ?>
                                    <option value="<?php echo $resOpt; ?>"><?php echo $resOpt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-none" id="custom-rank-group">
                            <label for="hist_custom_rank" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Specify Other Result *</label>
                            <input type="text" class="form-control rounded-3" id="hist_custom_rank" name="custom_rank" placeholder="e.g. 6th Place, Quarterfinalist">
                        </div>
                        <div class="col-12">
                            <label for="hist_remarks" class="form-label fw-bold text-secondary" style="font-size:0.82rem;">Remarks (Optional)</label>
                            <textarea class="form-control rounded-3" id="hist_remarks" name="remarks" rows="2" placeholder="Certificate numbers, notes, etc."></textarea>
                        </div>
                    </div>

                    <div id="history-error-msg" class="alert alert-danger mt-3 d-none rounded-3 py-2 px-3" style="font-size:0.85rem;"></div>
                    <div id="history-warning-msg" class="alert alert-warning mt-3 d-none rounded-3 py-2 px-3" style="font-size:0.85rem; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fa-solid fa-triangle-exclamation me-1"></i> A similar performance record already exists for this athlete. Proceed anyway?</span>
                        <button type="button" class="btn btn-sm btn-warning fw-bold ms-2 py-0 px-2" onclick="saveHistoryRecord(true)">Yes, Save</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="save-history-btn" onclick="saveHistoryRecord(false)" class="btn btn-primary rounded-pill px-4 fw-bold">Save Record</button>
            </div>
        </div>
    </div>
</div>

<!-- Archiving Confirmation Modal -->
<div class="modal fade" id="deleteHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h3 class="modal-title fw-bold text-dark" style="font-family: 'Outfit', sans-serif; font-size:1.2rem;">Archive Record?</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-0" style="font-size:0.9rem;">Are you sure you want to archive this tournament history record? It will be hidden from the profile dashboard.</p>
                <input type="hidden" id="delete_history_id" value="0">
            </div>
            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-1" data-bs-dismiss="modal" style="font-size:0.85rem;">Cancel</button>
                <button type="button" id="confirm-delete-history-btn" onclick="confirmDeleteHistory()" class="btn btn-danger rounded-pill px-3 py-1 fw-bold" style="font-size:0.85rem;">Yes, Archive</button>
            </div>
        </div>
    </div>
</div>

<script>
let historyModalInstance = null;
let deleteHistoryModalInstance = null;

function toggleCustomRankField() {
    const rankVal = document.getElementById("hist_rank").value;
    const customGroup = document.getElementById("custom-rank-group");
    const customInput = document.getElementById("hist_custom_rank");
    if (rankVal === "Other") {
        customGroup.classList.remove("d-none");
        customInput.required = true;
    } else {
        customGroup.classList.add("d-none");
        customInput.required = false;
        customInput.value = "";
    }
}

function openHistoryModal(id = 0) {
    if (!historyModalInstance) {
        historyModalInstance = new bootstrap.Modal(document.getElementById('historyModal'));
    }
    
    // Clear form
    document.getElementById("history-form").reset();
    document.getElementById("history_id").value = id;
    document.getElementById("history-error-msg").classList.add("d-none");
    document.getElementById("history-warning-msg").classList.add("d-none");
    document.getElementById("custom-rank-group").classList.add("d-none");
    document.getElementById("hist_custom_rank").required = false;
    
    // Set title
    document.getElementById("history-modal-title").textContent = id === 0 ? "Add Performance Record" : "Edit Performance Record";
    
    // Default state pre-fill if adding
    if (id === 0) {
        const defaultState = "<?php echo htmlspecialchars($athlete['representing_for'] ?: $athlete['state']); ?>";
        document.getElementById("hist_state_represented").value = defaultState;
    }

    historyModalInstance.show();
}

function editHistory(record) {
    openHistoryModal(record.id);
    
    // Populate form fields
    document.getElementById("hist_event_name").value = record.event_name;
    document.getElementById("hist_event_year").value = record.event_year;
    document.getElementById("hist_classification").value = record.classification || "";
    document.getElementById("hist_event_level").value = record.event_level || "";
    document.getElementById("hist_state_represented").value = record.state_represented || "";
    
    const standardResults = <?php echo json_encode(RESULT_OPTIONS); ?>;
    if (standardResults.includes(record.rank)) {
        document.getElementById("hist_rank").value = record.rank;
    } else {
        document.getElementById("hist_rank").value = "Other";
        document.getElementById("custom-rank-group").classList.remove("d-none");
        document.getElementById("hist_custom_rank").value = record.rank;
        document.getElementById("hist_custom_rank").required = true;
    }
    
    document.getElementById("hist_remarks").value = record.remarks || "";
}

function saveHistoryRecord(bypassWarning = false) {
    const errorMsg = document.getElementById("history-error-msg");
    const warningMsg = document.getElementById("history-warning-msg");
    const saveBtn = document.getElementById("save-history-btn");
    
    errorMsg.classList.add("d-none");
    warningMsg.classList.add("d-none");
    
    // Simple frontend validation check
    const form = document.getElementById("history-form");
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';
    
    const formData = new FormData(form);
    
    if (!bypassWarning) {
        // Step 1: Duplicate check via API
        const checkData = new FormData();
        checkData.append("csrf_token", formData.get("csrf_token"));
        checkData.append("action", "check_duplicate");
        checkData.append("athlete_id", formData.get("athlete_id"));
        checkData.append("history_id", formData.get("history_id"));
        checkData.append("event_name", formData.get("event_name"));
        checkData.append("event_year", formData.get("event_year"));
        checkData.append("classification", formData.get("classification"));
        checkData.append("event_level", formData.get("event_level"));
        
        fetch("api/athlete-history.php", {
            method: "POST",
            body: checkData
        })
        .then(res => res.json())
        .then(data => {
            if (data.duplicate) {
                // Show warning message
                warningMsg.classList.remove("d-none");
                saveBtn.disabled = false;
                saveBtn.innerHTML = "Save Record";
            } else {
                // No duplicate found, proceed to save directly
                executeSave(formData);
            }
        })
        .catch(err => {
            errorMsg.textContent = "Connection warning check failed. Click save again to retry.";
            errorMsg.classList.remove("d-none");
            saveBtn.disabled = false;
            saveBtn.innerHTML = "Save Record";
        });
    } else {
        // Direct save bypassing the warning
        executeSave(formData);
    }
}

function executeSave(formData) {
    const errorMsg = document.getElementById("history-error-msg");
    const saveBtn = document.getElementById("save-history-btn");
    
    fetch("api/athlete-history.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200) {
            window.location.reload();
        } else {
            errorMsg.textContent = res.body.error || "Failed to save record.";
            errorMsg.classList.remove("d-none");
            saveBtn.disabled = false;
            saveBtn.innerHTML = "Save Record";
        }
    })
    .catch(err => {
        errorMsg.textContent = "Server connection error. Please try again.";
        errorMsg.classList.remove("d-none");
        saveBtn.disabled = false;
        saveBtn.innerHTML = "Save Record";
    });
}

let activeDeleteHistoryId = null;

function deleteHistory(id) {
    if (!deleteHistoryModalInstance) {
        deleteHistoryModalInstance = new bootstrap.Modal(document.getElementById('deleteHistoryModal'));
    }
    activeDeleteHistoryId = id;
    deleteHistoryModalInstance.show();
}

function confirmDeleteHistory() {
    const id = activeDeleteHistoryId;
    if (!id) {
        alert("No record selected for archiving.");
        return;
    }
    const confirmBtn = document.getElementById("confirm-delete-history-btn");
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Archiving...';
    
    const formData = new FormData();
    formData.append("csrf_token", "<?php echo $_SESSION['csrf_token'] ?? ''; ?>");
    formData.append("action", "delete");
    formData.append("history_id", id);
    formData.append("athlete_id", "<?php echo $athleteId; ?>");
    
    fetch("api/athlete-history.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200) {
            window.location.reload();
        } else {
            alert(res.body.error || "Failed to archive record.");
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = "Yes, Archive";
        }
    })
    .catch(err => {
        alert("Server connection error. Please try again.");
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = "Yes, Archive";
    });
}

function toggleArchivedVisibility(showArchived) {
    sessionStorage.setItem("show_archived_history", showArchived ? "true" : "false");
    const archivedRows = document.querySelectorAll(".archived-row");
    archivedRows.forEach(row => {
        if (showArchived) {
            row.classList.remove("d-none");
        } else {
            row.classList.add("d-none");
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const showArchivedPersisted = sessionStorage.getItem("show_archived_history") === "true";
    const toggleSwitch = document.getElementById("toggleArchivedSwitch");
    if (toggleSwitch) {
        toggleSwitch.checked = showArchivedPersisted;
        toggleArchivedVisibility(showArchivedPersisted);
    }
});

function restoreHistory(id) {
    if (!confirm("Are you sure you want to restore this archived tournament record?")) {
        return;
    }
    
    const formData = new FormData();
    formData.append("csrf_token", "<?php echo $_SESSION['csrf_token'] ?? ''; ?>");
    formData.append("action", "restore");
    formData.append("history_id", id);
    formData.append("athlete_id", "<?php echo $athleteId; ?>");
    
    fetch("api/athlete-history.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200) {
            window.location.reload();
        } else {
            alert(res.body.error || "Failed to restore record.");
        }
    })
    .catch(err => {
        alert("Server connection error. Please try again.");
    });
}

function purgeHistory(id) {
    if (!confirm("WARNING: Are you sure you want to permanently delete this tournament record? This action CANNOT be undone!")) {
        return;
    }
    
    const formData = new FormData();
    formData.append("csrf_token", "<?php echo $_SESSION['csrf_token'] ?? ''; ?>");
    formData.append("action", "purge");
    formData.append("history_id", id);
    formData.append("athlete_id", "<?php echo $athleteId; ?>");
    
    fetch("api/athlete-history.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200) {
            window.location.reload();
        } else {
            alert(res.body.error || "Failed to permanently delete record.");
        }
    })
    .catch(err => {
        alert("Server connection error. Please try again.");
    });
}
</script>

<!-- Edit NSRS ID Modal -->
<div class="modal fade" id="editNsrsIdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0" style="background:#081B4B; color:white; border-radius: 16px 16px 0 0; padding:1.25rem 1.5rem;">
                <h5 class="modal-title font-bold text-white"><i class="fa-solid fa-shield-halved me-2"></i> Edit NSRS ID</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="athlete-details.php?id=<?php echo $athlete['id']; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update_nsrs_id">
                <div class="modal-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 small mb-3 fw-bold" style="background:#FEE2E2; color:#991B1B;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <div class="alert alert-warning border-0 text-dark small mb-3" style="background:#FEF3C7;">
                        <i class="fa-solid fa-lock me-1"></i> Security Verification: Modifying an athlete's NSRS ID requires your current administrator account password.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NSRS ID <span class="text-danger">*</span></label>
                        <input type="text" name="new_nsrs_id" class="form-control" value="<?php echo htmlspecialchars($athlete['nsrs_id'] ?? ''); ?>" placeholder="Enter NSRS ID..." required>
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
<!-- Edit Athlete Full Profile Modal -->
<div class="modal fade" id="editAthleteProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0" style="background:#081B4B; color:white; border-radius: 16px 16px 0 0; padding:1.25rem 1.5rem;">
                <h5 class="modal-title font-bold text-white"><i class="fa-solid fa-user-pen me-2"></i> Edit Athlete Profile &amp; Demographics</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="athlete-details.php?id=<?php echo $athlete['id']; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="update_athlete_details">
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    <h6 class="fw-bold mb-3 text-primary" style="border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem;"><i class="fa-solid fa-address-card me-1"></i> Family &amp; Demographics</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($athlete['father_name'] ?? ''); ?>" placeholder="Enter father's name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($athlete['mother_name'] ?? ''); ?>" placeholder="Enter mother's name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Age Category</label>
                            <select name="age_category" class="form-select">
                                <option value="">Select Age Category</option>
                                <option value="OPEN" <?php echo ($athlete['age_category'] ?? '') === 'OPEN' ? 'selected' : ''; ?>>Open / Senior</option>
                                <option value="JUNIOR" <?php echo ($athlete['age_category'] ?? '') === 'JUNIOR' ? 'selected' : ''; ?>>Junior</option>
                                <option value="YOUTH" <?php echo ($athlete['age_category'] ?? '') === 'YOUTH' ? 'selected' : ''; ?>>Youth</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Classification</label>
                            <select name="classification" class="form-select">
                                <option value="BC1" <?php echo ($athlete['classification'] ?? '') === 'BC1' ? 'selected' : ''; ?>>BC1</option>
                                <option value="BC2" <?php echo ($athlete['classification'] ?? '') === 'BC2' ? 'selected' : ''; ?>>BC2</option>
                                <option value="BC3" <?php echo ($athlete['classification'] ?? '') === 'BC3' ? 'selected' : ''; ?>>BC3</option>
                                <option value="BC4" <?php echo ($athlete['classification'] ?? '') === 'BC4' ? 'selected' : ''; ?>>BC4</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Impairment Type</label>
                            <input type="text" name="impairment_type" class="form-control" value="<?php echo htmlspecialchars($athlete['impairment_type'] ?? ''); ?>" placeholder="E.g. Cerebral Palsy">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Representing State</label>
                            <input type="text" name="representing_for" class="form-control" value="<?php echo htmlspecialchars($athlete['representing_for'] ?? ''); ?>" placeholder="State Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Wheelchair Status</label>
                            <select name="wheelchair_status" class="form-select">
                                <option value="None" <?php echo ($athlete['wheelchair_status'] ?? '') === 'None' ? 'selected' : ''; ?>>None</option>
                                <option value="Manual Wheelchair" <?php echo ($athlete['wheelchair_status'] ?? '') === 'Manual Wheelchair' ? 'selected' : ''; ?>>Manual Wheelchair</option>
                                <option value="Power Wheelchair" <?php echo ($athlete['wheelchair_status'] ?? '') === 'Power Wheelchair' ? 'selected' : ''; ?>>Power Wheelchair</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-primary" style="border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem;"><i class="fa-solid fa-shirt me-1"></i> Kit Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">T-Shirt Size</label>
                            <input type="text" name="kit_tshirt" class="form-control" value="<?php echo htmlspecialchars($athlete['kit_tshirt'] ?? ''); ?>" placeholder="E.g. M, L, XL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tracksuit Size</label>
                            <input type="text" name="kit_tracksuit" class="form-control" value="<?php echo htmlspecialchars($athlete['kit_tracksuit'] ?? ''); ?>" placeholder="E.g. M, L, XL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Shoe Size</label>
                            <input type="text" name="kit_shoe" class="form-control" value="<?php echo htmlspecialchars($athlete['kit_shoe'] ?? ''); ?>" placeholder="E.g. UK 8">
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-primary" style="border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem;"><i class="fa-solid fa-id-card me-1"></i> Identity &amp; Contact Info</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Aadhaar Number</label>
                            <input type="text" name="aadhaar" class="form-control" value="<?php echo htmlspecialchars($athlete['aadhaar'] ?? ''); ?>" placeholder="12 Digit Aadhaar Number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Phone Number</label>
                            <input type="tel" name="mobile" class="form-control" value="<?php echo htmlspecialchars($athlete['mobile'] ?? ''); ?>" placeholder="Mobile number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($athlete['email'] ?? ''); ?>" placeholder="Email address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pin Code</label>
                            <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars($athlete['pincode'] ?? ''); ?>" placeholder="6 Digit Pin Code">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Permanent Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Complete address"><?php echo htmlspecialchars($athlete['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4" style="background:#10B981; border-color:#10B981;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
