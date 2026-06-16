<?php
// dashboard.php - Administrative control center for BSFI staff

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$page_title = "Admin Control Dashboard - Boccia India";
include __DIR__ . '/../includes/header.php';

// Gather basic statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM athletes");
$totalAthletes = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='pending'");
$pendingRegistrations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved'");
$approvedAthletes = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM news");
$totalNews = $stmt->fetchColumn();

// Fetch recent activity audit logs
$stmt = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$auditLogs = $stmt->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <!-- Welcome Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Federation Portal Control Desk</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <span style="font-size:0.85rem; background:rgba(36, 194, 122, 0.1); color:#24C27A; padding:0.25rem 0.75rem; border-radius:50px; text-transform:uppercase; font-weight:600; margin-top:0.5rem; display:inline-block;">Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../import/import-athletes.php" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px;">Bulk Import CSV</a>
                    <a href="users.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Manage Staff</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dashboard Stats Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:2rem; margin-bottom:4rem;">
            <!-- Stat 1 -->
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); border-left:4px solid #F4B942; border-radius:28px;">
                <span style="color:#FAF7F0; opacity:0.6; font-size:0.85rem; text-transform:uppercase; font-weight:600;">Total Athletes</span>
                <h2 style="font-size:2.8rem; font-family:'Outfit',sans-serif; font-weight:800; color:#F4B942; margin-top:0.5rem;"><?php echo $totalAthletes; ?></h2>
            </div>
            <!-- Stat 2 -->
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); border-left:4px solid #D72638; border-radius:28px;">
                <span style="color:#FAF7F0; opacity:0.6; font-size:0.85rem; text-transform:uppercase; font-weight:600;">Pending Actions</span>
                <h2 style="font-size:2.8rem; font-family:'Outfit',sans-serif; font-weight:800; color:#D72638; margin-top:0.5rem;"><?php echo $pendingRegistrations; ?></h2>
            </div>
            <!-- Stat 3 -->
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); border-left:4px solid #24C27A; border-radius:28px;">
                <span style="color:#FAF7F0; opacity:0.6; font-size:0.85rem; text-transform:uppercase; font-weight:600;">Approved Active</span>
                <h2 style="font-size:2.8rem; font-family:'Outfit',sans-serif; font-weight:800; color:#24C27A; margin-top:0.5rem;"><?php echo $approvedAthletes; ?></h2>
            </div>
            <!-- Stat 4 -->
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); border-left:4px solid #1E88E5; border-radius:28px;">
                <span style="color:#FAF7F0; opacity:0.6; font-size:0.85rem; text-transform:uppercase; font-weight:600;">Calendar Events</span>
                <h2 style="font-size:2.8rem; font-family:'Outfit',sans-serif; font-weight:800; color:#1E88E5; margin-top:0.5rem;"><?php echo $totalEvents; ?></h2>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:3rem;">
            
            <!-- Left Side: Management Modules -->
            <div>
                <!-- USERS & MEMBERS -->
                <h3 style="font-size:1.3rem; margin-bottom:1rem; font-family:'Outfit',sans-serif; color:#FAF7F0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">USERS & MEMBERS</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom: 2.5rem;">
                    
                    <!-- Athlete Registry Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#F4B942; margin-bottom:0.5rem;">Athlete Directory</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Search, edit, verify registrations, and track athlete records.</p>
                        <a href="athletes.php" class="btn" style="background:#F4B942; color:#08142E; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Open Directory</a>
                    </div>
                    
                    <!-- Review Portal -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#D72638; margin-bottom:0.5rem;">Registrations Review</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Process pending athlete registrations and approve entries.</p>
                        <a href="registrations.php" class="btn" style="background:#D72638; color:#fff; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Review (<?php echo $pendingRegistrations; ?>)</a>
                    </div>

                    <!-- Admins Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#A0AABF; margin-bottom:0.5rem;">System Admins</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Manage admin accounts and editor permissions.</p>
                        <a href="#" class="btn" style="background:#A0AABF; color:#08142E; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage Access</a>
                    </div>
                </div>

                <!-- CONTENT MANAGEMENT -->
                <h3 style="font-size:1.3rem; margin-bottom:1rem; font-family:'Outfit',sans-serif; color:#FAF7F0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">CONTENT MANAGEMENT</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom: 2.5rem;">
                    
                    <!-- Events Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#1E88E5; margin-bottom:0.5rem;">Events</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Manage upcoming tournaments and awareness camps.</p>
                        <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                            <a href="events.php" class="btn" style="background:#1E88E5; color:#fff; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage</a>
                        <?php endif; ?>
                    </div>

                    <!-- Schedules Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#9b59b6; margin-bottom:0.5rem;">Schedules</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Manage the stylized schedule list for the landing page.</p>
                        <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                            <a href="schedules.php" class="btn" style="background:#9b59b6; color:#fff; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage</a>
                        <?php endif; ?>
                    </div>

                    <!-- News Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#24C27A; margin-bottom:0.5rem;">News</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Publish featured articles, announcements, and press releases.</p>
                        <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                            <a href="news.php" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage</a>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery Module -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#ff7e67; margin-bottom:0.5rem;">Gallery</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Upload photos, tag events, and manage the slideshow/collage.</p>
                        <?php if (in_array($_SESSION['role'], ['admin', 'editor'])): ?>
                            <a href="gallery.php" class="btn" style="background:#ff7e67; color:#fff; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MEDIA -->
                <h3 style="font-size:1.3rem; margin-bottom:1rem; font-family:'Outfit',sans-serif; color:#FAF7F0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">MEDIA</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom: 2.5rem;">
                    
                    <!-- Documents -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#00d2ff; margin-bottom:0.5rem;">Documents</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Manage secure internal federation documents.</p>
                        <a href="#" class="btn" style="background:#00d2ff; color:#08142E; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">View</a>
                    </div>

                    <!-- Downloads (Circulars) -->
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:1.5rem; border-radius:20px;">
                        <h4 style="font-size:1.1rem; color:#00d2ff; margin-bottom:0.5rem;">Downloads & Circulars</h4>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Upload public PDFs like policies and tournament handbooks.</p>
                        <a href="#" class="btn" style="background:#00d2ff; color:#08142E; font-weight:bold; font-size:0.8rem; border-radius:999px; padding: 0.4rem 1rem; display:inline-block;">Manage</a>
                    </div>
                </div>
            </div>

            <!-- Right Side: Activity Log & Backups -->
            <div>
                <!-- Recent Logs -->
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:2rem; border-radius:28px; margin-bottom:2rem;">
                    <h3 style="font-size:1.3rem; margin-bottom:1rem; font-family:'Outfit',sans-serif;">Recent Audit Activity</h3>
                    <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:1rem; font-size:0.85rem;">
                        <?php if (count($auditLogs) > 0): ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <li style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.5rem;">
                                    <strong style="color:#24C27A;"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>: 
                                    <?php echo htmlspecialchars($log['action']); ?>
                                    <span style="display:block; font-size:0.75rem; color:#FAF7F0; opacity:0.5; margin-top:0.25rem;"><?php echo $log['created_at']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li style="color:#FAF7F0; opacity:0.6; font-style:italic;">No activity recorded yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Database Backups (Admin Only) -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.3); padding:2rem; border-radius:28px;">
                        <h3 style="font-size:1.3rem; margin-bottom:1rem; font-family:'Outfit',sans-serif;">System Utilities & Backups</h3>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1.5rem;">Export primary database structures and data records natively.</p>
                        <div style="display:flex; flex-direction:column; gap:0.75rem;">
                            <a href="../api/export.php?type=csv" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; font-size:0.85rem; border-radius:999px; text-align:center;">Export Athletes to CSV</a>
                            <a href="../api/export.php?type=xlsx" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; font-size:0.85rem; border-radius:999px; text-align:center;">Export Athletes to XLS</a>
                            <a href="../api/export.php?type=sql" class="btn" style="background:#FAF7F0; color:#08142E; font-weight:bold; font-size:0.85rem; border-radius:999px; text-align:center;">Download Full Database SQL Backup</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
