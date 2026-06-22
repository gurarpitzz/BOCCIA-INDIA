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

// Rejected Applications (athlete_applications with status = 'rejected')
$stmt = $pdo->query("SELECT COUNT(*) FROM athlete_applications WHERE status='rejected'");
$rejectedApplications = $stmt->fetchColumn();

// Potential Duplicates (athlete_applications with possible_duplicate = 1 and status = 'pending')
$stmt = $pdo->query("SELECT COUNT(*) FROM athlete_applications WHERE possible_duplicate = 1 AND status = 'pending'");
$potentialDuplicates = $stmt->fetchColumn();

// Total Officials (approved officials)
$stmt = $pdo->query("SELECT COUNT(*) FROM officials WHERE status='approved' AND deleted_at IS NULL");
$totalOfficials = $stmt->fetchColumn();

// Pending Officials (official_applications status = 'pending')
$stmt = $pdo->query("SELECT COUNT(*) FROM official_applications WHERE status='pending'");
$pendingOfficials = $stmt->fetchColumn();

// Profiles Complete (photo_status = 'verified' AND email exists AND mobile exists AND state exists AND classification exists)
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

// Fetch recent activity audit logs
$stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$auditLogs = $stmt->fetchAll();
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 0;">
        
        <!-- Welcome Hero Card -->
        <div style="padding: 2rem 2rem 0 2rem;">
            <div class="admin-hero">
                <span class="admin-hero-eyebrow">BSFI FEDERATION CONTROL DESK</span>
                <h1 class="admin-hero-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <p class="admin-hero-desc">Manage athletes, registrations, media, competitions, documents, and federation operations from a centralized portal.</p>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="admin-hero-actions">
                        <a href="../import/import-athletes.php" class="admin-btn admin-btn-primary">Bulk Import CSV</a>
                        <a href="users.php" class="admin-btn admin-btn-outline">Manage Staff</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 0 2rem 3rem 2rem;">
            <!-- Dashboard Stats Grid -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:3rem;">
                <!-- Stat 1 -->
                <div class="admin-stat-card accent-green">
                    <span class="admin-stat-label">Total Athletes</span>
                    <h2 class="admin-stat-val"><?php echo $totalAthletes; ?></h2>
                </div>
                <!-- Stat 2 -->
                <div class="admin-stat-card accent-blue">
                    <span class="admin-stat-label">Profiles Complete</span>
                    <h2 class="admin-stat-val"><?php echo $profilesComplete; ?></h2>
                </div>
                <!-- Stat 3 -->
                <div class="admin-stat-card accent-red">
                    <span class="admin-stat-label">Missing Photos</span>
                    <h2 class="admin-stat-val"><?php echo $missingPhotos; ?></h2>
                </div>
                <!-- Stat 4 -->
                <div class="admin-stat-card accent-orange">
                    <span class="admin-stat-label">Missing Contact Info</span>
                    <h2 class="admin-stat-val"><?php echo $missingContactInfo; ?></h2>
                </div>
                <!-- Stat 5 -->
                <div class="admin-stat-card accent-purple">
                    <span class="admin-stat-label">Pending Updates</span>
                    <h2 class="admin-stat-val"><?php echo $pendingProfileUpdates; ?></h2>
                </div>
                <!-- Stat 6 -->
                <div class="admin-stat-card accent-navy">
                    <span class="admin-stat-label">Total Officials</span>
                    <h2 class="admin-stat-val"><?php echo $totalOfficials; ?></h2>
                </div>
                <!-- Stat 7 -->
                <div class="admin-stat-card accent-amber">
                    <span class="admin-stat-label">Pending Officials</span>
                    <h2 class="admin-stat-val"><?php echo $pendingOfficials; ?></h2>
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:2.5rem; align-items: start;">
                
                <!-- Left Side: Management Modules -->
                <div>
                    <!-- ATHLETES & MEMBERS SECTION -->
                    <div style="margin-bottom: 2.5rem;">
                        <span class="admin-section-eyebrow">ATHLETES &amp; MEMBERS</span>
                        <hr style="margin: 0.5rem 0 1.5rem 0; border-color: #E2E8F0;">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
                            
                            <!-- Athlete Directory Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Athlete Directory</h4>
                                <p class="admin-card-desc">Search, edit, verify registrations, and track athlete records.</p>
                                <a href="athletes.php" class="admin-btn admin-btn-secondary">Open Directory</a>
                            </div>
                            
                            <!-- Review Portal -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Registrations Review</h4>
                                <p class="admin-card-desc">Process pending athlete registrations and approve entries.</p>
                                <a href="registrations.php" class="admin-btn admin-btn-primary">
                                    Review Applications <?php if($pendingRegistrations > 0): ?><span class="admin-badge admin-badge-warning" style="margin-left: 0.5rem; background: #FF9933; color: white;"><?php echo $pendingRegistrations; ?></span><?php endif; ?>
                                </a>
                            </div>

                            <!-- Staff Users Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Staff Users</h4>
                                <p class="admin-card-desc">Manage admin accounts and editor permissions.</p>
                                <a href="users.php" class="admin-btn admin-btn-outline">Manage Access</a>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT MANAGEMENT SECTION -->
                    <div>
                        <span class="admin-section-eyebrow">CONTENT MANAGEMENT</span>
                        <hr style="margin: 0.5rem 0 1.5rem 0; border-color: #E2E8F0;">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
                            
                            <!-- Document Pages Module -->
                            <div class="admin-card hoverable" style="grid-column: span 2;">
                                <h4 class="admin-card-title">Document Pages</h4>
                                <p class="admin-card-desc">Manage standardized PDF document pages, upload policies, selection criteria, tenders, and governance documents.</p>
                                <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                                    <a href="document_pages.php" class="admin-btn admin-btn-primary">Manage Documents</a>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pending">Read-only Access</span>
                                <?php endif; ?>
                            </div>

                            <!-- News Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">News Management</h4>
                                <p class="admin-card-desc">Publish featured articles, announcements, and press releases.</p>
                                <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                                    <a href="news.php" class="admin-btn admin-btn-secondary">Manage News</a>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pending">Read-only Access</span>
                                <?php endif; ?>
                            </div>

                            <!-- Gallery Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Gallery Management</h4>
                                <p class="admin-card-desc">Upload photos, tag events, and manage the slideshow/collage.</p>
                                <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                                    <a href="gallery.php" class="admin-btn admin-btn-secondary">Manage Gallery</a>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pending">Read-only Access</span>
                                <?php endif; ?>
                            </div>

                            <!-- Events Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Events Management</h4>
                                <p class="admin-card-desc">Manage upcoming tournaments and awareness camps.</p>
                                <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                                    <a href="events.php" class="admin-btn admin-btn-secondary">Manage Events</a>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pending">Read-only Access</span>
                                <?php endif; ?>
                            </div>

                            <!-- Schedules Module -->
                            <div class="admin-card hoverable">
                                <h4 class="admin-card-title">Schedules</h4>
                                <p class="admin-card-desc">Manage the stylized schedule list for the landing page.</p>
                                <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                                    <a href="schedules.php" class="admin-btn admin-btn-secondary">Manage Schedules</a>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-pending">Read-only Access</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Activity Log & Backups -->
                <div>
                    <!-- Recent Logs -->
                    <div id="audit-logs" class="admin-card" style="margin-bottom: 2.5rem;">
                        <h3 class="admin-card-title">Recent Audit Activity</h3>
                        <p class="admin-card-desc">Federation-wide administrative action logs.</p>
                        <div class="admin-timeline">
                            <?php if (count($auditLogs) > 0): ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <div class="admin-timeline-item <?php echo (strpos(strtolower($log['action']), 'delete') !== false) ? 'accent-saffron' : ''; ?>">
                                        <p class="admin-timeline-title"><?php echo htmlspecialchars(strtoupper($log['username'] ?? 'System')); ?></p>
                                        <p class="admin-timeline-desc"><?php echo htmlspecialchars($log['action']); ?></p>
                                        <span class="admin-timeline-time"><?php echo date('d M Y • h:i A', strtotime($log['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:var(--text-muted); font-style:italic;">No activity recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Database Backups (Admin Only) -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div id="system-utilities" class="admin-card">
                            <h3 class="admin-card-title">System Utilities &amp; Backups</h3>
                            <p class="admin-card-desc">Export primary database structures and data records natively.</p>
                            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                                <a href="../api/export.php?type=csv" class="admin-btn admin-btn-outline" style="justify-content: flex-start; text-align: left;">
                                    Export Athletes to CSV
                                </a>
                                <a href="../api/export.php?type=xlsx" class="admin-btn admin-btn-outline" style="justify-content: flex-start; text-align: left;">
                                    Export Athletes to XLS
                                </a>
                                <a href="../api/export.php?type=sql" class="admin-btn admin-btn-warning" style="justify-content: flex-start; text-align: left;">
                                    Download Full SQL Backup
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
