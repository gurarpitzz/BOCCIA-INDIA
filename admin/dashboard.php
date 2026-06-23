<?php
// dashboard.php - Administrative control center for BSFI staff

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$page_title = "Admin Control Dashboard - Boccia India";
include __DIR__ . '/../includes/header.php';

// Gather statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved' AND deleted_at IS NULL");
$totalAthletes = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM athlete_applications WHERE status='pending'");
$pendingRegistrations = $stmt->fetchColumn();

// Approved this month (athletes with status = 'approved' and created_at in the current month)
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved' AND deleted_at IS NULL AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$approvedThisMonth = $stmt->fetchColumn();

// Rejected Applications
$stmt = $pdo->query("SELECT COUNT(*) FROM athlete_applications WHERE status='rejected'");
$rejectedApplications = $stmt->fetchColumn();

// Potential Duplicates
$stmt = $pdo->query("SELECT COUNT(*) FROM athlete_applications WHERE possible_duplicate = 1 AND status = 'pending'");
$potentialDuplicates = $stmt->fetchColumn();

// Total Officials
$stmt = $pdo->query("SELECT COUNT(*) FROM officials WHERE status='approved' AND deleted_at IS NULL");
$totalOfficials = $stmt->fetchColumn();

// Pending Officials
$stmt = $pdo->query("SELECT COUNT(*) FROM official_applications WHERE status='pending'");
$pendingOfficials = $stmt->fetchColumn();

// Profiles Complete
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved' AND deleted_at IS NULL AND photo_status='verified' AND email IS NOT NULL AND email != '' AND mobile IS NOT NULL AND mobile != '' AND state IS NOT NULL AND state != '' AND classification IS NOT NULL AND classification != ''");
$profilesComplete = $stmt->fetchColumn();

// Missing Photos
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved' AND deleted_at IS NULL AND photo_status='missing'");
$missingPhotos = $stmt->fetchColumn();

// Missing Contact Info
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved' AND deleted_at IS NULL AND (email IS NULL OR email = '' OR mobile IS NULL OR mobile = '')");
$missingContactInfo = $stmt->fetchColumn();

// Pending Profile Updates
$stmt = $pdo->query("SELECT COUNT(*) FROM profile_update_requests WHERE status='pending'");
$pendingProfileUpdates = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM news");
$totalNews = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM document_pages");
$totalDocuments = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gallery_images");
$totalGallery = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalStaff = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM schedules");
$totalSchedules = $stmt->fetchColumn();

// Fetch recent activity audit logs
$stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$auditLogs = $stmt->fetchAll();
?>

<div class="admin-wrapper" id="main-content">
    <div class="container-fluid" style="padding: 0;">
        
        <!-- Navy Welcome Hero Card -->
        <div style="padding: 2rem 2rem 0 2rem;">
            <div class="admin-hero d-none d-md-block">
                <!-- Subtle Watermark -->
                <div style="position: absolute; right: 40px; bottom: -20px; opacity: 0.05; pointer-events: none; z-index: 1; transform: rotate(-10deg);">
                    <img src="../boccia-india-logo.webp" alt="" style="width: 200px; height: auto;">
                </div>
                <div style="position: relative; z-index: 2;">
                    <span class="admin-hero-eyebrow">BSFI Federation Control Desk</span>
                    <h1 class="admin-hero-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                    <p class="admin-hero-desc">Access the central operations hub to manage athletes, review registrations, coordinate schedules, and configure system utilities.</p>
                    
                    <div class="admin-hero-actions">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../import/import-athletes.php" class="admin-btn" style="background:#138808; color:#ffffff; font-weight:700; border-radius:8px; padding:0.6rem 1.25rem; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
                                <i class="fa-solid fa-file-import"></i> Bulk Import CSV
                            </a>
                            <a href="users.php" class="admin-btn" style="background:rgba(255,255,255,0.15); color:#ffffff; font-weight:600; border:1px solid rgba(255,255,255,0.3); border-radius:8px; padding:0.6rem 1.25rem; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
                                <i class="fa-solid fa-users-gear"></i> Manage Staff
                            </a>
                        <?php endif; ?>
                        <a href="../index.php" target="_blank" class="admin-btn" style="background:transparent; color:#ffffff; font-weight:600; border:1px solid rgba(255,255,255,0.2); border-radius:8px; padding:0.6rem 1.25rem; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
                            <i class="fa-solid fa-up-right-from-square"></i> View Website
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile-only Dashboard Header and Quick Actions -->
            <div class="d-block d-md-none mb-4">
                <span class="admin-section-eyebrow">BSFI Federation Control Desk</span>
                <h1 style="font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; color:var(--navy); margin-top:0.25rem; margin-bottom:1.5rem;">Dashboard Overview</h1>
                
                <h4 class="admin-section-eyebrow" style="color:var(--text-secondary); margin-bottom:0.75rem;">Quick Actions</h4>
                <div class="row row-cols-2 g-2">
                    <div class="col">
                        <a href="../import/import-athletes.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-file-import"></i> Import CSV
                        </a>
                    </div>
                    <div class="col">
                        <a href="registrations.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-user-check"></i> Registrations
                        </a>
                    </div>
                    <div class="col">
                        <a href="athletes.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-users"></i> Athletes
                        </a>
                    </div>
                    <div class="col">
                        <a href="news.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700;">
                            <i class="fa-solid fa-newspaper"></i> News
                        </a>
                    </div>
                    <div class="col">
                        <a href="../index.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700; color: var(--bsfi-green) !important; border-color: rgba(19, 136, 8, 0.3);">
                            <i class="fa-solid fa-globe"></i> View Website
                        </a>
                    </div>
                    <div class="col">
                        <a href="../logout.php" class="admin-btn admin-btn-outline w-100" style="padding: 0.75rem 0.5rem; border-radius: 10px; font-size: 0.85rem; justify-content: center; font-weight: 700; color: var(--danger) !important; border-color: rgba(220, 38, 38, 0.3);">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 0 2rem 3rem 2rem;">
            <!-- KPI Row (Responsive Bootstrap grid) -->
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-4">
                <div class="col">
                    <div class="admin-stat-card accent-green h-100">
                        <span class="admin-stat-label">Athletes</span>
                        <h2 class="admin-stat-val"><?php echo $totalAthletes; ?></h2>
                    </div>
                </div>
                <div class="col">
                    <div class="admin-stat-card accent-blue h-100">
                        <span class="admin-stat-label">Profiles Complete</span>
                        <h2 class="admin-stat-val"><?php echo $profilesComplete; ?></h2>
                    </div>
                </div>
                <div class="col">
                    <div class="admin-stat-card accent-red h-100">
                        <span class="admin-stat-label">Missing Photos</span>
                        <h2 class="admin-stat-val"><?php echo $missingPhotos; ?></h2>
                    </div>
                </div>
                <div class="col">
                    <div class="admin-stat-card accent-orange h-100">
                        <span class="admin-stat-label">Missing Contact</span>
                        <h2 class="admin-stat-val"><?php echo $missingContactInfo; ?></h2>
                    </div>
                </div>
                <div class="col">
                    <div class="admin-stat-card accent-navy h-100">
                        <span class="admin-stat-label">Officials</span>
                        <h2 class="admin-stat-val"><?php echo $totalOfficials; ?></h2>
                    </div>
                </div>
                <div class="col">
                    <div class="admin-stat-card accent-amber h-100">
                        <span class="admin-stat-label">Pending Reviews</span>
                        <h2 class="admin-stat-val"><?php echo $pendingRegistrations; ?></h2>
                    </div>
                </div>
            </div>

            <!-- Two-Column Main Layout Grid -->
            <div style="display:grid; grid-template-columns: 1fr; gap:2rem; align-items: start;">
                <!-- Desktop Columns Wrapper -->
                <div class="dashboard-grid-container" style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    
                    <!-- Left Column (75%) -->
                    <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 2.5rem;">
                        
                        <!-- ATHLETES & MEMBERS -->
                        <div>
                            <span class="admin-section-eyebrow">Athletes &amp; Members</span>
                            <hr style="margin: 0.5rem 0 1.5rem 0; border-color: rgba(0,0,0,0.06); height:1px; border:none; background:rgba(0,0,0,0.08);">
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
                                
                                <!-- Athlete Directory -->
                                <div class="admin-card hoverable" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between; min-height:160px; padding:1.5rem;">
                                    <div>
                                        <h4 class="admin-card-title" style="font-size:1.1rem; color:#081B4B; display:flex; justify-content:space-between; align-items:center;">
                                            Athlete Directory
                                            <i class="fa-solid fa-users" style="color:var(--bsfi-green); font-size:1.25rem;"></i>
                                        </h4>
                                        <p style="font-size: 1.25rem; font-weight:800; color:#1e293b; margin: 0.5rem 0 0 0;"><?php echo $totalAthletes; ?> Active Athletes</p>
                                    </div>
                                    <a href="athletes.php" style="color:var(--bsfi-green); font-weight:700; text-decoration:none; font-size:0.875rem; margin-top:1.5rem; display:inline-flex; align-items:center; gap:0.4rem;">
                                        Open Directory <i class="fa-solid fa-arrow-right-long"></i>
                                    </a>
                                </div>

                                <!-- Registrations review -->
                                <div class="admin-card hoverable" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between; min-height:160px; padding:1.5rem;">
                                    <div>
                                        <h4 class="admin-card-title" style="font-size:1.1rem; color:#081B4B; display:flex; justify-content:space-between; align-items:center;">
                                            Registrations
                                            <i class="fa-solid fa-user-check" style="color:var(--bsfi-saffron); font-size:1.25rem;"></i>
                                        </h4>
                                        <p style="font-size: 1.25rem; font-weight:800; color:#1e293b; margin: 0.5rem 0 0 0;"><?php echo $pendingRegistrations; ?> Pending Applications</p>
                                    </div>
                                    <a href="registrations.php" style="color:var(--bsfi-saffron); font-weight:700; text-decoration:none; font-size:0.875rem; margin-top:1.5rem; display:inline-flex; align-items:center; gap:0.4rem;">
                                        Review Applications <i class="fa-solid fa-arrow-right-long"></i>
                                    </a>
                                </div>

                                <!-- Staff Users -->
                                <div class="admin-card hoverable" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between; min-height:160px; padding:1.5rem;">
                                    <div>
                                        <h4 class="admin-card-title" style="font-size:1.1rem; color:#081B4B; display:flex; justify-content:space-between; align-items:center;">
                                            Staff Users
                                            <i class="fa-solid fa-user-shield" style="color:#081B4B; font-size:1.25rem;"></i>
                                        </h4>
                                        <p style="font-size: 1.25rem; font-weight:800; color:#1e293b; margin: 0.5rem 0 0 0;"><?php echo $totalStaff; ?> Active Staff</p>
                                    </div>
                                    <a href="users.php" style="color:#081B4B; font-weight:700; text-decoration:none; font-size:0.875rem; margin-top:1.5rem; display:inline-flex; align-items:center; gap:0.4rem;">
                                        Manage Access <i class="fa-solid fa-arrow-right-long"></i>
                                    </a>
                                </div>

                            </div>
                        </div>

                        <!-- CONTENT MANAGEMENT -->
                        <div>
                            <span class="admin-section-eyebrow">Content Management</span>
                            <hr style="margin: 0.5rem 0 1.5rem 0; border-color: rgba(0,0,0,0.06); height:1px; border:none; background:rgba(0,0,0,0.08);">
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem;">
                                
                                <!-- Document Pages -->
                                <div class="admin-card hoverable" style="margin-bottom:0; padding:1.25rem; min-height:140px; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <h5 style="font-size:0.95rem; font-weight:700; color:#081B4B; margin:0 0 0.35rem 0;">Document Pages</h5>
                                        <p style="font-size:0.8rem; color:#64748b; margin:0;"><?php echo $totalDocuments; ?> uploaded files & policies</p>
                                    </div>
                                    <a href="document_pages.php" style="font-size:0.82rem; font-weight:700; color:var(--bsfi-green); text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                                        Manage Documents <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>

                                <!-- News -->
                                <div class="admin-card hoverable" style="margin-bottom:0; padding:1.25rem; min-height:140px; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <h5 style="font-size:0.95rem; font-weight:700; color:#081B4B; margin:0 0 0.35rem 0;">News articles</h5>
                                        <p style="font-size:0.8rem; color:#64748b; margin:0;"><?php echo $totalNews; ?> published stories</p>
                                    </div>
                                    <a href="news.php" style="font-size:0.82rem; font-weight:700; color:var(--bsfi-green); text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                                        Manage Articles <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>

                                <!-- Gallery -->
                                <div class="admin-card hoverable" style="margin-bottom:0; padding:1.25rem; min-height:140px; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <h5 style="font-size:0.95rem; font-weight:700; color:#081B4B; margin:0 0 0.35rem 0;">Gallery Images</h5>
                                        <p style="font-size:0.8rem; color:#64748b; margin:0;"><?php echo $totalGallery; ?> photos in archive</p>
                                    </div>
                                    <a href="gallery.php" style="font-size:0.82rem; font-weight:700; color:var(--bsfi-green); text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                                        Manage Media <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>

                                <!-- Events -->
                                <div class="admin-card hoverable" style="margin-bottom:0; padding:1.25rem; min-height:140px; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <h5 style="font-size:0.95rem; font-weight:700; color:#081B4B; margin:0 0 0.35rem 0;">Events</h5>
                                        <p style="font-size:0.8rem; color:#64748b; margin:0;"><?php echo $totalEvents; ?> federation events</p>
                                    </div>
                                    <a href="events.php" style="font-size:0.82rem; font-weight:700; color:var(--bsfi-green); text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                                        Manage Calendar <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>

                                <!-- Schedules -->
                                <div class="admin-card hoverable" style="margin-bottom:0; padding:1.25rem; min-height:140px; display:flex; flex-direction:column; justify-content:space-between; grid-column: span 1;">
                                    <div>
                                        <h5 style="font-size:0.95rem; font-weight:700; color:#081B4B; margin:0 0 0.35rem 0;">Stylized Schedules</h5>
                                        <p style="font-size:0.8rem; color:#64748b; margin:0;"><?php echo $totalSchedules; ?> scheduled slots</p>
                                    </div>
                                    <a href="schedules.php" style="font-size:0.82rem; font-weight:700; color:var(--bsfi-green); text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                                        Manage Schedules <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Right Column (25% / 320px) -->
                    <div style="width: 320px; flex-shrink: 0; display: flex; flex-direction: column; gap: 2rem;">
                        
                        <!-- Recent Activity / Logs -->
                        <div id="audit-logs" class="admin-card" style="margin-bottom:0; padding:1.5rem;">
                            <h3 class="admin-card-title" style="font-size:1.15rem; color:#081B4B; display:flex; justify-content:space-between; align-items:center;">
                                Recent Activity
                                <i class="fa-solid fa-list-ul" style="font-size:1rem; opacity:0.6;"></i>
                            </h3>
                            <p class="admin-card-desc" style="margin-bottom:1.5rem; font-size:0.8rem;">Federation control audit logs.</p>
                            
                            <div class="admin-timeline" style="display:flex; flex-direction:column; gap:1.25rem;">
                                <?php if (count($auditLogs) > 0): ?>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <div style="border-left: 2px solid #138808; padding-left: 0.75rem; position: relative;">
                                            <p style="font-size:0.75rem; font-weight:800; color:#081B4B; margin:0; text-transform:uppercase;"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></p>
                                            <p style="font-size:0.82rem; color:#475569; margin:0.15rem 0; line-height:1.3;"><?php echo htmlspecialchars($log['action']); ?></p>
                                            <span style="font-size:0.68rem; color:#94a3b8; display:block;"><?php echo date('d M • h:i A', strtotime($log['created_at'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color:var(--text-muted); font-style:italic; font-size:0.85rem; margin:0;">No activity recorded yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- System Utilities (Exports & Backups) -->
                        <div id="system-utilities" class="admin-card" style="margin-bottom:0; padding:1.5rem;">
                            <h3 class="admin-card-title" style="font-size:1.15rem; color:#081B4B; display:flex; justify-content:space-between; align-items:center;">
                                Utilities
                                <i class="fa-solid fa-toolbox" style="font-size:1rem; opacity:0.6;"></i>
                            </h3>
                            <p class="admin-card-desc" style="margin-bottom:1.25rem; font-size:0.8rem;">Database export and backups.</p>
                            
                            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                                <a href="../api/export.php?type=csv" class="admin-btn" style="background:rgba(8,27,75,0.06); border:1px solid rgba(8,27,75,0.15); color:#081B4B; border-radius:8px; padding:0.6rem 0.8rem; font-size:0.85rem; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                                    <i class="fa-solid fa-file-csv" style="font-size:1rem;"></i> CSV Export (Athletes)
                                </a>
                                <a href="../api/export.php?type=xlsx" class="admin-btn" style="background:rgba(8,27,75,0.06); border:1px solid rgba(8,27,75,0.15); color:#081B4B; border-radius:8px; padding:0.6rem 0.8rem; font-size:0.85rem; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                                    <i class="fa-solid fa-file-excel" style="font-size:1rem;"></i> Excel Export (Athletes)
                                </a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="../api/export.php?type=sql" class="admin-btn" style="background:rgba(255,153,51,0.1); border:1px solid rgba(255,153,51,0.3); color:#e07d16; border-radius:8px; padding:0.6rem 0.8rem; font-size:0.85rem; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                                        <i class="fa-solid fa-database" style="font-size:1rem;"></i> SQL Full Backup
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
