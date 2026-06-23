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
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap 5.3 (Local) -->
    <link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.min.css?v=1">
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
    <script>
        try {
            const settings = JSON.parse(localStorage.getItem('bsfiAccessibility'));
            if (settings) {
                if (settings.fontSize) {
                    document.documentElement.style.fontSize = settings.fontSize + 'px';
                }
                const toggle = (cls, cond) => {
                    if (cond) document.documentElement.classList.add(cls);
                };
                toggle('high-contrast', settings.highContrast);
                toggle('reverse-contrast', settings.reverseContrast);
                toggle('grayscale-mode', settings.grayscale);
                toggle('readable-font', settings.readableFont);
                toggle('underline-links', settings.underlineLinks);
                toggle('underline-headers', settings.underlineHeaders);
                toggle('big-cursor-white', settings.bigCursorWhite);
                toggle('big-cursor-black', settings.bigCursorBlack);
                toggle('reduce-motion', settings.reduceMotion);
            }
        } catch(e) {}
    </script>
</head>
<body class="admin-body">
<a href="#main-content" class="skip-link">Skip to Main Content</a>

<div class="admin-layout" id="admin-layout-container">
    <script>
        if (localStorage.getItem("adminSidebarCollapsed") === "true" || window.innerWidth < 991) {
            document.getElementById("admin-layout-container").classList.add("sidebar-collapsed");
        }
    </script>
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <div class="brand-title-wrap">
                <strong>BSFI</strong>
                <span class="brand-subtext">Federation Portal</span>
            </div>
            <button class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="brand-logo-row">
                <img src="../boccia-india-logo.webp" alt="BSFI" title="BSFI">
                <img src="../logos/Ministry_of_Youth_Affairs_and_Sports.svg" alt="MYAS" title="MYAS">
                <img src="../logos/PCI.png" alt="PCI" title="PCI">
                <img src="../logos/Full Logo World Boccia.webp" alt="World Boccia" title="World Boccia">
            </div>
        </div>
        <nav class="admin-sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php" class="<?php echo ($current_file === 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gauge"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-section-title">Athletes</li>
                <li>
                    <a href="athletes.php" class="<?php echo ($current_file === 'athletes.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i>
                        <span class="nav-label">Athlete Directory</span>
                    </a>
                </li>
                <li>
                    <a href="registrations.php" class="<?php echo ($current_file === 'registrations.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-plus"></i>
                        <span class="nav-label">Registrations</span>
                    </a>
                </li>
                
                <li class="nav-section-title">Content</li>
                <li>
                    <a href="news.php" class="<?php echo ($current_file === 'news.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-newspaper"></i>
                        <span class="nav-label">News</span>
                    </a>
                </li>
                <li>
                    <a href="gallery.php" class="<?php echo ($current_file === 'gallery.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-images"></i>
                        <span class="nav-label">Gallery</span>
                    </a>
                </li>
                <li>
                    <a href="events.php" class="<?php echo ($current_file === 'events.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span class="nav-label">Events</span>
                    </a>
                </li>
                <li>
                    <a href="schedules.php" class="<?php echo ($current_file === 'schedules.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock"></i>
                        <span class="nav-label">Schedules</span>
                    </a>
                </li>
                
                <li class="nav-section-title">Documents</li>
                <li>
                    <a href="document_pages.php" class="<?php echo ($current_file === 'document_pages.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span class="nav-label">Document Pages</span>
                    </a>
                </li>
                
                <li class="nav-section-title">System</li>
                <li>
                    <a href="users.php" class="<?php echo ($current_file === 'users.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-shield"></i>
                        <span class="nav-label">Users</span>
                    </a>
                </li>
                <li>
                    <a href="export-center.php" class="<?php echo ($current_file === 'export-center.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-file-export"></i>
                        <span class="nav-label">Export Center</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#audit-logs">
                        <i class="fa-solid fa-list-check"></i>
                        <span class="nav-label">Audit Logs</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php#system-utilities">
                        <i class="fa-solid fa-database"></i>
                        <span class="nav-label">Backups</span>
                    </a>
                </li>
                
                <li style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem;">
                    <a href="../index.php" style="color: var(--bsfi-saffron);">
                        <i class="fa-solid fa-globe"></i>
                        <span class="nav-label">View Website</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php" style="color: #FF7777;">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="nav-label">Logout</span>
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
                <span class="admin-section-eyebrow d-none d-md-inline">BSFI Federation Control Desk</span>
                <div class="d-flex d-md-none align-items-center gap-2">
                    <img src="../boccia-india-logo.webp" alt="Boccia India Logo" style="height: 32px; width: auto; object-fit: contain;">
                    <img src="../logos/Ministry_of_Youth_Affairs_and_Sports.svg" alt="MYAS Logo" style="height: 32px; width: auto; object-fit: contain;">
                </div>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-topbar-user">
                    <span class="d-none d-sm-inline">Logged in: </span><strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></strong>
                    <span class="d-none d-md-inline-block"><?php echo htmlspecialchars($_SESSION['role'] ?? 'viewer'); ?></span>
                </div>
            </div>
        </header>
        <div class="admin-content-body">
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const layout = document.getElementById("admin-layout-container");
                const toggleBtn = document.getElementById("sidebar-toggle");
                if (toggleBtn && layout) {
                    toggleBtn.addEventListener("click", function() {
                        layout.classList.toggle("sidebar-collapsed");
                        const isCollapsed = layout.classList.contains("sidebar-collapsed");
                        localStorage.setItem("adminSidebarCollapsed", isCollapsed ? "true" : "false");
                    });
                }
            });
        </script>
