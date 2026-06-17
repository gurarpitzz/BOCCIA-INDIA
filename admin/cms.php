<?php
// admin/cms.php - Central CMS dashboard panel

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

requireLogin();

$message = '';
$error = '';

// Handle manual trigger sync request
if (isset($_POST['trigger_sync'])) {
    require_once __DIR__ . '/../includes/discovery.php';
    $engine = new ContentDiscoveryEngine($pdo);
    $syncLogs = $engine->runSync();
    $message = "Content Discovery Sync finished successfully! " . count($syncLogs) . " log entries generated.";
}

// Handle updating page content
if (isset($_POST['update_page'])) {
    $pageId = (int)$_POST['page_id'];
    $content = $_POST['content'];
    $metaTitle = trim($_POST['meta_title']);
    $metaDesc = trim($_POST['meta_description']);
    
    try {
        // Log snapshot version first
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(version), 0) + 1 FROM page_versions WHERE page_id = ?");
        $stmt->execute([$pageId]);
        $nextVer = $stmt->fetchColumn();

        $ins = $pdo->prepare("INSERT INTO page_versions (page_id, version, content_snapshot, created_by) VALUES (?, ?, ?, ?)");
        $ins->execute([$pageId, $nextVer, $content, $_SESSION['user_id']]);

        // Update main page
        $up = $pdo->prepare("UPDATE site_pages SET content = ?, meta_title = ?, meta_description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $up->execute([$content, $metaTitle, $metaDesc, $pageId]);

        $message = "Page updated successfully and version $nextVer snapshot logged.";
        
        // Log activity
        $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Update Page', ?)");
        $log->execute([$_SESSION['user_id'], "Updated page ID $pageId"]);
    } catch (PDOException $e) {
        $error = "Failed to update page: " . $e->getMessage();
    }
}

// Fetch dynamic pages
$pagesStmt = $pdo->query("SELECT * FROM site_pages WHERE deleted_at IS NULL ORDER BY section ASC, title ASC");
$sitePages = $pagesStmt->fetchAll();

// Fetch active navigation menu items
$navStmt = $pdo->query("SELECT n.*, p.title as parent_title FROM navigation_items n LEFT JOIN navigation_items p ON n.parent_id = p.id ORDER BY n.parent_id ASC, n.sort_order ASC");
$navItems = $navStmt->fetchAll();

// Fetch activity logs
$activityStmt = $pdo->query("SELECT a.*, u.username FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10");
$activityLogs = $activityStmt->fetchAll();

$page_title = "CMS Management Console - Boccia India";
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">

        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <a href="dashboard.php" style="color:#FAF7F0; font-size:0.9rem; text-decoration:none;">&larr; Back to Dashboard</a>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700; margin-top:0.5rem;">CMS &amp; Navigation Console</h1>
            </div>
            <form method="POST">
                <button type="submit" name="trigger_sync" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px;">Run Content Discovery Sync Now</button>
            </form>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success bg-success border-0 text-white p-3 mb-4 rounded-3">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger bg-danger border-0 text-white p-3 mb-4 rounded-3">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- Left Column: Site Pages List & Editor -->
            <div class="col-lg-8">
                <div class="card bg-dark text-white border-0 p-4 rounded-4 shadow-sm mb-4">
                    <h3 style="font-family:'Outfit',sans-serif; color:#F4B942;" class="mb-4">Discovered Site Pages</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-borderless align-middle">
                            <thead>
                                <tr class="text-muted" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                                    <th>Title</th>
                                    <th>Section</th>
                                    <th>Slug</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sitePages as $page): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                        <td class="text-capitalize"><?php echo htmlspecialchars($page['section']); ?></td>
                                        <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="editPage(<?php echo htmlspecialchars(json_encode($page)); ?>)">Edit Page</button>
                                            <a href="../page.php?section=<?php echo urlencode($page['section']); ?>&slug=<?php echo urlencode($page['slug']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Preview</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Interactive Edit Modal/Section -->
                <div id="pageEditorSection" class="card bg-dark text-white border-0 p-4 rounded-4 shadow-sm" style="display:none;">
                    <h3 style="font-family:'Outfit',sans-serif; color:#24C27A;" class="mb-4">Edit Content Snapshot</h3>
                    <form method="POST">
                        <input type="hidden" name="page_id" id="edit-page-id">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Page Title</label>
                            <input type="text" id="edit-page-title" class="form-control bg-secondary text-white border-0" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">SEO Meta Title</label>
                            <input type="text" name="meta_title" id="edit-page-meta-title" class="form-control bg-secondary text-white border-0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">SEO Meta Description</label>
                            <textarea name="meta_description" id="edit-page-meta-desc" rows="2" class="form-control bg-secondary text-white border-0"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">HTML/Text Body Content</label>
                            <textarea name="content" id="edit-page-content" rows="10" class="form-control bg-secondary text-white border-0" style="font-family:monospace;"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_page" class="btn btn-success">Save and Update Page</button>
                            <button type="button" class="btn btn-outline-light" onclick="cancelEdit()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Navigation & Log Registry -->
            <div class="col-lg-4">
                
                <!-- Nav Tree -->
                <div class="card bg-dark text-white border-0 p-4 rounded-4 shadow-sm mb-4">
                    <h3 style="font-family:'Outfit',sans-serif;" class="mb-3">Header Navigation</h3>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php foreach ($navItems as $nav): ?>
                            <li class="list-group-item bg-transparent text-white border-bottom border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <span style="font-size:0.95rem; font-weight:bold;"><?php echo htmlspecialchars($nav['title']); ?></span>
                                    <?php if ($nav['parent_title']): ?>
                                        <span class="badge bg-secondary ms-2" style="font-size:0.75rem;">Sub of <?php echo htmlspecialchars($nav['parent_title']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-primary ms-2" style="font-size:0.75rem;">Main Header</span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-info">Sort: <?php echo $nav['sort_order']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Activity Log -->
                <div class="card bg-dark text-white border-0 p-4 rounded-4 shadow-sm">
                    <h3 style="font-family:'Outfit',sans-serif;" class="mb-3">CMS Audit Logs</h3>
                    <ul class="list-style-none p-0" style="font-size:0.8rem; line-height:1.6;">
                        <?php foreach ($activityLogs as $act): ?>
                            <li class="border-bottom border-secondary pb-2 mb-2">
                                <strong class="text-success"><?php echo htmlspecialchars($act['username'] ?? 'Discovery Engine'); ?></strong>: 
                                <?php echo htmlspecialchars($act['action']); ?>
                                <p class="text-muted m-0" style="font-size:0.75rem;"><?php echo htmlspecialchars($act['details']); ?></p>
                                <span class="text-muted d-block" style="font-size:0.7rem;"><?php echo $act['created_at']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function editPage(page) {
    document.getElementById('edit-page-id').value = page.id;
    document.getElementById('edit-page-title').value = page.title;
    document.getElementById('edit-page-meta-title').value = page.meta_title || '';
    document.getElementById('edit-page-meta-desc').value = page.meta_description || '';
    document.getElementById('edit-page-content').value = page.content || '';
    
    document.getElementById('pageEditorSection').style.display = 'block';
    window.scrollTo({
        top: document.getElementById('pageEditorSection').offsetTop - 100,
        behavior: 'smooth'
    });
}
function cancelEdit() {
    document.getElementById('pageEditorSection').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
