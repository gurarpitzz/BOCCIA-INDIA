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

$page_title = htmlspecialchars($official['name']) . " - Official Details";
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
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
                    <div style="font-family: monospace; font-size: 1.15rem; font-weight: 700; color: var(--bsfi-green); margin-bottom: 1.25rem;">
                        ID: <?php echo htmlspecialchars($official['official_reg_no']); ?>
                        <div style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); margin-top: 0.15rem;">
                            🔒 Permanent Federation Identifier (Locked)
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
