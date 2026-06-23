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
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($athlete['classification']); ?></span>
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
                            <div class="col-12 col-sm-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between" style="background:#F8FAFC;">
                                    <div>
                                        <i class="fa-solid fa-file-invoice-dollar text-success me-2" style="font-size:1.15rem;"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">Registration Receipt</span>
                                    </div>
                                    <?php if (!empty($athlete['receipt_path'])): ?>
                                        <a href="download-doc.php?file=<?php echo urlencode($athlete['receipt_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">View Document</a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.75rem;">None Uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between" style="background:#F8FAFC;">
                                    <div>
                                        <i class="fa-solid fa-passport text-primary me-2" style="font-size:1.15rem;"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">Passport / Identity File</span>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <?php if (!empty($athlete['passport_file'])): ?>
                                            <a href="download-doc.php?file=<?php echo urlencode($athlete['passport_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">View Document</a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
