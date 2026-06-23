<?php
// export-center.php - BSFI Federation Export Control Center
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$page_title = "Export Center - BSFI Admin";
include __DIR__ . '/../includes/header.php';

// Fetch dynamic states lists
$statesList = $pdo->query("SELECT DISTINCT state FROM (
    SELECT state FROM athletes WHERE state IS NOT NULL AND state != ''
    UNION
    SELECT state FROM officials WHERE state IS NOT NULL AND state != ''
) states ORDER BY state ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch classifications
$classificationsList = ['BC1', 'BC2', 'BC3', 'BC4'];
$rolesList = ['Coach', 'Sport Assistant', 'Classifier', 'Technical Official', 'Referee', 'Volunteer'];
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation System desk</span>
                <h1 class="admin-page-title">Export Control Center</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
        </div>

        <div class="row g-4">
            
            <!-- Quick Master Exports Panel -->
            <div class="col-12 col-md-4">
                <div class="admin-card h-100" style="display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <h3 class="admin-card-title" style="color:#081B4B;"><i class="fa-solid fa-file-shield text-success me-2"></i> Federation Master Export</h3>
                        <p class="admin-card-desc">Download complete registries including sensitive fields (Aadhaar, address, documents, contact info, kit sizes) for athletes and officials.</p>
                        
                        <div class="alert alert-warning border-0 p-3 mb-4 rounded-3" style="font-size:0.82rem; background-color: rgba(255, 153, 51, 0.08); color: #B45309;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Admin Only:</strong> Contains personally identifiable information (PII). Keep downloaded files secure.
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1.5rem;">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../api/api-export-builder.php?action=master&format=xlsx" class="admin-btn admin-btn-success d-flex align-items-center justify-content-start gap-2 w-100">
                                <i class="fa-solid fa-file-excel"></i> Export Master Excel (.xlsx)
                            </a>
                            <a href="../api/api-export-builder.php?action=master&format=csv" class="admin-btn admin-btn-outline d-flex align-items-center justify-content-start gap-2 w-100">
                                <i class="fa-solid fa-file-csv"></i> Export Master CSV (.csv)
                            </a>
                        <?php else: ?>
                            <button class="admin-btn admin-btn-outline w-100 d-flex align-items-center justify-content-start gap-2" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="fa-solid fa-lock"></i> Master Export Restricted
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Public Export Panel -->
            <div class="col-12 col-md-4">
                <div class="admin-card h-100" style="display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <h3 class="admin-card-title" style="color:#081B4B;"><i class="fa-solid fa-globe text-primary me-2"></i> Public Registry Export</h3>
                        <p class="admin-card-desc">Download redacted registration sheets containing public columns only: ID, Name, Gender, State, Classification. PII and uploaded files are excluded.</p>
                        
                        <div class="alert alert-info border-0 p-3 mb-4 rounded-3" style="font-size:0.82rem; background-color: rgba(59, 130, 246, 0.08); color: #1E3A8A;">
                            <i class="fa-solid fa-circle-info me-1"></i> Safe for public release, directories, and website lookup listings.
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1.5rem;">
                        <a href="../api/api-export-builder.php?action=public&format=xlsx" class="admin-btn admin-btn-primary d-flex align-items-center justify-content-start gap-2 w-100">
                            <i class="fa-solid fa-file-excel"></i> Export Public Excel (.xlsx)
                        </a>
                        <a href="../api/api-export-builder.php?action=public&format=csv" class="admin-btn admin-btn-outline d-flex align-items-center justify-content-start gap-2 w-100">
                            <i class="fa-solid fa-file-csv"></i> Export Public CSV (.csv)
                        </a>
                    </div>
                </div>
            </div>

            <!-- SQL Database Backups Panel -->
            <div class="col-12 col-md-4">
                <div class="admin-card h-100" style="display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <h3 class="admin-card-title" style="color:#081B4B;"><i class="fa-solid fa-database text-warning me-2"></i> SQL Federation Backup</h3>
                        <p class="admin-card-desc">Execute full federation backup. Generates a complete database script including schema structure and tables: athletes, officials, applications, schedules, gallery, users, settings.</p>
                        
                        <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="font-size:0.82rem; background-color: rgba(239, 68, 68, 0.08); color: #7F1D1D;">
                            <i class="fa-solid fa-shield-halved me-1"></i> Includes staff account credentials (hashed passwords) and activity logs.
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1.5rem;">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../api/export.php?type=sql" class="admin-btn admin-btn-warning d-flex align-items-center justify-content-start gap-2 w-100">
                                <i class="fa-solid fa-download"></i> Download Full SQL Backup
                            </a>
                        <?php else: ?>
                            <button class="admin-btn admin-btn-outline w-100 d-flex align-items-center justify-content-start gap-2" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="fa-solid fa-lock"></i> Backup Download Restricted
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Custom Export Builder Panel -->
            <div class="col-12">
                <div class="admin-card">
                    <h3 class="admin-card-title" style="color:#081B4B; border-bottom: 2px solid #F1F5F9; padding-bottom: 1rem;"><i class="fa-solid fa-sliders text-info me-2"></i> Custom Export Builder</h3>
                    <p class="admin-card-desc">Filter the registry using custom parameters, select the exact columns to include, and compile custom spreadsheets dynamically.</p>
                    
                    <form action="../api/api-export-builder.php" method="GET" target="_blank" style="margin-top:2rem;">
                        <input type="hidden" name="action" value="custom">
                        
                        <div class="row g-4">
                            
                            <!-- 1. Source & Filters -->
                            <div class="col-12 col-lg-6">
                                <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">1. Filters & Scope</h5>
                                
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="scope">Export Target Type</label>
                                        <select id="scope" name="scope" class="admin-select" required>
                                            <option value="athletes">Athletes Registry Only</option>
                                            <option value="officials">Officials Registry Only</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="state">State / Association</label>
                                        <select id="state" name="state" class="admin-select">
                                            <option value="">All States / UTs</option>
                                            <?php foreach ($statesList as $st): ?>
                                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="classification">Category / Classification / Role</label>
                                        <select id="classification" name="classification" class="admin-select">
                                            <option value="">All Categories &amp; Roles</option>
                                            <optgroup label="Athlete Classifications">
                                                <?php foreach ($classificationsList as $cl): ?>
                                                    <option value="<?php echo $cl; ?>"><?php echo $cl; ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Official Roles">
                                                <?php foreach ($rolesList as $rl): ?>
                                                    <option value="<?php echo $rl; ?>"><?php echo $rl; ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender" class="admin-select">
                                            <option value="">All Genders</option>
                                            <option value="MALE">Male</option>
                                            <option value="FEMALE">Female</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="status">Registry Status</label>
                                        <select id="status" name="status" class="admin-select">
                                            <option value="">All Registry Statuses</option>
                                            <option value="approved" selected>Approved Only</option>
                                            <option value="pending">Pending Only</option>
                                            <option value="rejected">Rejected Only</option>
                                            <option value="archived">Archived Only</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6 admin-form-group">
                                        <label for="legacy">Legacy Registry Flag</label>
                                        <select id="legacy" name="legacy" class="admin-select">
                                            <option value="">All Athletes</option>
                                            <option value="1">Legacy Athletes Only (0001 -> 0099)</option>
                                            <option value="0">New Registrations Only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Column Selection -->
                            <div class="col-12 col-lg-6">
                                <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">2. Column Selection</h5>
                                
                                <div class="row g-2" style="max-height:280px; overflow-y:auto; padding-right:0.5rem;">
                                    <div class="col-6">
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="regn_no" id="col_regn" checked>
                                            <label class="form-check-label fw-semibold" for="col_regn">Registration No / ID</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="full_name" id="col_name" checked>
                                            <label class="form-check-label fw-semibold" for="col_name">Full Name</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="gender" id="col_gender" checked>
                                            <label class="form-check-label fw-semibold" for="col_gender">Gender</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="dob" id="col_dob" checked>
                                            <label class="form-check-label fw-semibold" for="col_dob">Date of Birth</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="state" id="col_state" checked>
                                            <label class="form-check-label fw-semibold" for="col_state">State</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="classification" id="col_class" checked>
                                            <label class="form-check-label fw-semibold" for="col_class">Classification / Role</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-6">
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="email" id="col_email">
                                            <label class="form-check-label text-danger fw-semibold" for="col_email"><i class="fa-solid fa-lock me-1"></i> Email</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="mobile" id="col_phone">
                                            <label class="form-check-label text-danger fw-semibold" for="col_phone"><i class="fa-solid fa-lock me-1"></i> Phone Number</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="aadhaar" id="col_aadhaar">
                                            <label class="form-check-label text-danger fw-semibold" for="col_aadhaar"><i class="fa-solid fa-lock me-1"></i> Aadhaar Number</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="address" id="col_address">
                                            <label class="form-check-label text-danger fw-semibold" for="col_address"><i class="fa-solid fa-lock me-1"></i> Permanent Address</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="kit_tshirt" id="col_kit">
                                            <label class="form-check-label text-danger fw-semibold" for="col_kit"><i class="fa-solid fa-lock me-1"></i> Kit Sizes</label>
                                        </div>
                                        <div class="form-check my-1">
                                            <input class="form-check-input" type="checkbox" name="cols[]" value="father_name" id="col_parents">
                                            <label class="form-check-label text-danger fw-semibold" for="col_parents"><i class="fa-solid fa-lock me-1"></i> Parent Names</label>
                                        </div>
                                    </div>
                                </div>
                                <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-top:10px;">* Red colored labels denote sensitive personal fields. Only administrative users can extract these fields. Non-admins selecting these columns will see them automatically redacted or blocked.</span>
                            </div>

                        </div>

                        <!-- 3. Export Compilation Buttons -->
                        <div style="border-top:2px solid #F1F5F9; margin-top:2.5rem; padding-top:1.5rem; display:flex; gap:1rem; align-items:center; justify-content:flex-end;">
                            <span style="font-size:0.9rem; color:var(--text-secondary);">Select Output Format:</span>
                            <button type="submit" name="format" value="xlsx" class="admin-btn admin-btn-success d-flex align-items-center gap-2">
                                <i class="fa-solid fa-file-excel"></i> Compile Excel Spreadsheet
                            </button>
                            <button type="submit" name="format" value="csv" class="admin-btn admin-btn-outline d-flex align-items-center gap-2">
                                <i class="fa-solid fa-file-csv"></i> Compile CSV Spreadsheet
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
