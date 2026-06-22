<?php
// admin_header.php - Administrative Sidebar & Top Header Template
require_once __DIR__ . '/auth.php';
$current_file = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "BSFI Federation Admin Portal"; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Standalone Admin Theme -->
    <link rel="stylesheet" href="assets/css/admin-theme.css?v=<?php echo time(); ?>">
    <!-- Inline fallback overrides in case assets resolution path needs prefix -->
    <script>
        // Check if css needs relative prefix
        document.addEventListener("DOMContentLoaded", function() {
            // Find stylesheet link and fix if path is broken
            var link = document.querySelector('link[href*="admin-theme.css"]');
            if (link) {
                var depth = (window.location.pathname.match(/\//g) || []).length;
                // If deep in directories, ensure proper link relative path
                if (depth > 2) {
                    // path is already correct since script name is in /admin/
                }
            }
        });
    </script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <a href="dashboard.php" style="display:flex; align-items:center; justify-content:center; padding: 1.5rem 1rem;">
                <img src="../boccia-india-logo.webp" alt="Boccia Sports Federation of India" style="height: 80px; width: auto; object-fit: contain;">
            </a>
        </div>
        <nav class="admin-sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php" class="<?php echo ($current_file === 'dashboard.php') ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">Athletes</li>
                <li>
                    <a href="athletes.php" class="<?php echo ($current_file === 'athletes.php') ? 'active' : ''; ?>">
                        Athlete Directory
                    </a>
                </li>
                <li>
                    <a href="registrations.php" class="<?php echo ($current_file === 'registrations.php') ? 'active' : ''; ?>">
                        Registrations
                    </a>
                </li>
                
                <li class="nav-section-title">Content</li>
                <li>
                    <a href="news.php" class="<?php echo ($current_file === 'news.php') ? 'active' : ''; ?>">
                        News
                    </a>
                </li>
                <li>
                    <a href="gallery.php" class="<?php echo ($current_file === 'gallery.php') ? 'active' : ''; ?>">
                        Gallery
                    </a>
                </li>
                <li>
                    <a href="events.php" class="<?php echo ($current_file === 'events.php') ? 'active' : ''; ?>">
                        Events
                    </a>
                </li>
                <li>
                    <a href="schedules.php" class="<?php echo ($current_file === 'schedules.php') ? 'active' : ''; ?>">
                        Schedules
                    </a>
                </li>
                
                <li class="nav-section-title">Documents</li>
                <li>
                    <a href="document_pages.php" class="<?php echo ($current_file === 'document_pages.php') ? 'active' : ''; ?>">
                        Document Pages
                    </a>
                </li>
                
                <li class="nav-section-title">System</li>
                <li>
                    <a href="users.php" class="<?php echo ($current_file === 'users.php') ? 'active' : ''; ?>">
                        Users
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#audit-logs">
                        Audit Logs
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#system-utilities">
                        Backups
                    </a>
                </li>
                
                <li style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem;">
                    <a href="../index.php" style="color: var(--bsfi-saffron);">
                        View Website
                    </a>
                </li>
                <li>
                    <a href="../logout.php" style="color: #FF7777;">
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main-content">
        <!-- Top bar with user name, role, site return link -->
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <span class="admin-section-eyebrow" style="margin-bottom: 0;">BSFI Federation Control Desk</span>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-topbar-user">
                    Logged in: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['role'] ?? 'viewer'); ?></span>
                </div>
            </div>
        </header>
        <div class="admin-content-body">
