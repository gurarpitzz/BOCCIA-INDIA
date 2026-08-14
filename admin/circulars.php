<?php
// admin/circulars.php - Content management dashboard for Circulars & Notices
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Access restricted to admin and editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Manage Circulars & Notices - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

// Handle POST actions (Soft delete, publish status toggles)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            // Check if document exists and is not already hard-deleted
            $stmt = $pdo->prepare("SELECT id, title, status FROM circulars_notices WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            if ($doc) {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("UPDATE circulars_notices SET deleted_at = NOW(), updated_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                    logAction($pdo, "Soft Deleted Circular", "circular", $id, "Title: " . $doc['title']);
                    $message = "<div class='alert alert-success'>Circular/Notice deleted successfully (soft delete).</div>";
                } elseif ($action === 'publish') {
                    $stmt = $pdo->prepare("UPDATE circulars_notices SET status = 'Published', updated_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                    logAction($pdo, "Published Circular", "circular", $id);
                    $message = "<div class='alert alert-success'>Circular/Notice has been published.</div>";
                } elseif ($action === 'unpublish') {
                    $stmt = $pdo->prepare("UPDATE circulars_notices SET status = 'Draft', updated_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                    logAction($pdo, "Unpublished Circular (set to Draft)", "circular", $id);
                    $message = "<div class='alert alert-success'>Circular/Notice has been set back to Draft.</div>";
                } elseif ($action === 'archive') {
                    $stmt = $pdo->prepare("UPDATE circulars_notices SET status = 'Archived', updated_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $id]);
                    logAction($pdo, "Archived Circular", "circular", $id);
                    $message = "<div class='alert alert-success'>Circular/Notice has been archived.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Document not found.</div>";
            }
        }
    }
}

// Fetch all documents where deleted_at IS NULL
$stmt = $pdo->query("SELECT cn.*, u.username as creator_name FROM circulars_notices cn LEFT JOIN users u ON cn.created_by = u.id WHERE cn.deleted_at IS NULL ORDER BY cn.publication_date DESC, cn.id DESC");
$documents = $stmt->fetchAll();
?>

<!-- FontAwesome 6 Icons and Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="admin-wrapper" id="main-content">
    <div class="container-fluid" style="padding: 2rem;">
        
        <!-- Header Row -->
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Content Management</span>
                <h1 class="admin-page-title">Circulars & Notices</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <a href="admin/circular-edit.php" class="admin-btn admin-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Document</a>
                <a href="admin/dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <!-- Documents Table -->
        <div class="admin-card">
            <h3 class="admin-card-title">All Documents</h3>
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle admin-table" style="min-width: 800px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Pub. Date</th>
                            <th>Title</th>
                            <th style="width: 110px;">Category</th>
                            <th style="width: 100px;">PDF</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 120px;">Created By</th>
                            <th style="width: 260px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    <i class="bi bi-file-earmark-pdf" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                    No documents found. Click "Add Document" to upload your first Circular or Notice.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): 
                                $statusBadge = 'bg-secondary';
                                if ($doc['status'] === 'Published') {
                                    $statusBadge = 'bg-success';
                                } elseif ($doc['status'] === 'Archived') {
                                    $statusBadge = 'bg-warning text-dark';
                                }
                                $catBadge = ($doc['category'] === 'Circular') ? 'admin-badge-info' : 'admin-badge-success';
                            ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($doc['publication_date'])); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($doc['title']); ?></div>
                                        <?php if (!empty($doc['description'])): ?>
                                            <div class="text-muted small text-truncate" style="max-width: 350px;"><?php echo htmlspecialchars($doc['description']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="admin-badge <?php echo $catBadge; ?>"><?php echo htmlspecialchars($doc['category']); ?></span>
                                    </td>
                                    <td>
                                        <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-danger" title="Download: <?php echo htmlspecialchars($doc['original_filename']); ?>">
                                            <i class="fa-solid fa-file-pdf"></i> PDF
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($doc['status']); ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary"><?php echo htmlspecialchars($doc['creator_name'] ?? 'Staff'); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="d-flex justify-content-end gap-1">
                                            <!-- Status toggle action forms -->
                                            <form action="admin/circulars.php" method="POST" class="d-inline" onsubmit="return confirm('Change status?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                                <?php if ($doc['status'] === 'Draft'): ?>
                                                    <input type="hidden" name="action" value="publish">
                                                    <button type="submit" class="btn btn-sm btn-success px-2" title="Publish"><i class="fa-solid fa-cloud-arrow-up"></i> Publish</button>
                                                <?php elseif ($doc['status'] === 'Published'): ?>
                                                    <input type="hidden" name="action" value="unpublish">
                                                    <button type="submit" class="btn btn-sm btn-secondary px-2" title="Set to Draft"><i class="fa-solid fa-eye-slash"></i> Unpublish</button>
                                                <?php endif; ?>
                                            </form>

                                            <?php if ($doc['status'] !== 'Archived'): ?>
                                                <form action="admin/circulars.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this document?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                                    <input type="hidden" name="action" value="archive">
                                                    <button type="submit" class="btn btn-sm btn-warning px-2 text-dark" title="Archive"><i class="fa-solid fa-box-archive"></i> Archive</button>
                                                </form>
                                            <?php endif; ?>

                                            <a href="admin/circular-edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary px-2" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>

                                            <form action="admin/circulars.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this document?\nThis action is a soft-delete and can be recovered by database administrators.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-danger px-2" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
