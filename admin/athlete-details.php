<?php
// athlete-details.php - Secure administrative Athlete profile tracer & history view
require_once __DIR__ . '/../includes/db.php';
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
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
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
                    <div style="font-family: monospace; font-size: 1.15rem; font-weight: 700; color: var(--bsfi-green); margin-bottom: 1.25rem;">
                        ID: <?php echo htmlspecialchars($athlete['regn_no']); ?>
                        <div style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); margin-top: 0.15rem;">
                            🔒 Permanent Federation Identifier (Locked)
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
                    <h3 class="admin-card-title mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <i class="fa-solid fa-address-card text-success me-2"></i> Demographic &amp; Registration Profile
                    </h3>
                    
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
                        </div>
                    </div>
                </div>

                <!-- Tournament & Event History -->
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4" style="border-bottom: 2px solid #F1F5F9; padding-bottom: 0.75rem;">
                        <i class="fa-solid fa-trophy text-warning me-2"></i> Tournament &amp; Performance History
                    </h3>

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
                                        <th>Rank / Medal</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historyList as $hist): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($hist['event_year']); ?></td>
                                            <td class="fw-semibold text-navy"><?php echo htmlspecialchars($hist['event_name']); ?></td>
                                            <td><?php echo htmlspecialchars($hist['event_level'] ?? 'National'); ?></td>
                                            <td><?php echo htmlspecialchars($hist['state_represented'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($hist['classification'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (!empty($hist['medal'])): ?>
                                                    <span class="badge" style="background-color: <?php 
                                                        $m = strtolower($hist['medal']);
                                                        if ($m === 'gold') echo '#D97706; color:#fff';
                                                        elseif ($m === 'silver') echo '#94A3B8; color:#fff';
                                                        elseif ($m === 'bronze') echo '#B45309; color:#fff';
                                                        else echo '#081B4B; color:#fff';
                                                    ?>">
                                                        <i class="fa-solid fa-award"></i> <?php echo ucfirst(htmlspecialchars($hist['medal'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($hist['rank'])) echo ' (Rank: ' . htmlspecialchars($hist['rank']) . ')'; ?>
                                                <?php if (empty($hist['medal']) && empty($hist['rank'])) echo 'Participant'; ?>
                                            </td>
                                            <td style="font-size:0.8rem; color:var(--text-secondary); max-width:180px;" title="<?php echo htmlspecialchars($hist['remarks'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($hist['remarks'] ?? '-'); ?>
                                            </td>
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
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
