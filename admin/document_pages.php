<?php
// admin/document_pages.php - Admin panel to manage standardized PDF Document Pages
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Manage Document Pages - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';
$baseUploadDir = __DIR__ . '/../uploads/documents/';
if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}

// Handle Delete Action
if (isset($_POST['delete_page']) && isset($_POST['page_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $pageId = (int)$_POST['page_id'];
         $stmt = $pdo->prepare("DELETE FROM document_pages WHERE id = ?");
         $stmt->execute([$pageId]);
         $message = "<div class='alert alert-success'>Document page deleted successfully.</div>";
    }
}

// Handle Clear Hero Action
if (isset($_POST['clear_hero']) && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $pageId = (int)$_POST['id'];
         $stmt = $pdo->prepare("UPDATE document_pages SET hero_image = NULL WHERE id = ?");
         $stmt->execute([$pageId]);
         $message = "<div class='alert alert-success'>Custom hero image removed successfully.</div>";
         // Refresh edit target image reference
         if (isset($_GET['edit']) && (int)$_GET['edit'] === $pageId) {
             $_GET['edit'] = $pageId; // force reload in later query
         }
    }
}

// Handle Save Action (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $section_slug = trim($_POST['section_slug']);
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $description = trim($_POST['description']);
        $slug = trim($_POST['slug']);
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;

        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Validate uniqueness of slug
        $chkSlug = $pdo->prepare("SELECT id FROM document_pages WHERE slug = ? AND id != ?");
        $chkSlug->execute([$slug, $id]);
        if ($chkSlug->fetch()) {
            $slug = $slug . '-' . time();
        }

        if (empty($title) || empty($subtitle) || empty($section_slug)) {
            $message = "<div class='alert alert-danger'>Title, Subtitle, and Section are required.</div>";
        } else {
            // Document upload handling
            $pdf_file_path = isset($_POST['existing_pdf']) ? $_POST['existing_pdf'] : '';
            if (isset($_FILES['pdf_document']) && $_FILES['pdf_document']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['pdf_document']['name'], PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $sanitizedName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_FILES['pdf_document']['name']);
                    $fileName = time() . '_' . $sanitizedName;
                    if (move_uploaded_file($_FILES['pdf_document']['tmp_name'], $baseUploadDir . $fileName)) {
                        $pdf_file_path = 'uploads/documents/' . $fileName;
                    }
                } else {
                    $message = "<div class='alert alert-danger'>Only PDF files are allowed for the document.</div>";
                }
            }

            // Hero image upload handling
            $hero_image_path = isset($_POST['existing_hero_image']) ? $_POST['existing_hero_image'] : null;
            if (empty($hero_image_path)) {
                $hero_image_path = null;
            }
            if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $sanitizedName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_FILES['hero_image']['name']);
                    $fileName = 'hero_' . time() . '_' . $sanitizedName;
                    if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $baseUploadDir . $fileName)) {
                        $hero_image_path = 'uploads/documents/' . $fileName;
                    }
                }
            }

            if (empty($pdf_file_path)) {
                $message = "<div class='alert alert-danger'>A PDF document is required.</div>";
            } else {
                if ($id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE document_pages SET 
                            section_slug = ?, title = ?, subtitle = ?, description = ?, 
                            slug = ?, pdf_file = ?, hero_image = ?, sort_order = ?, is_published = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$section_slug, $title, $subtitle, $description, $slug, $pdf_file_path, $hero_image_path, $sort_order, $is_published, $id]);
                    $message = "<div class='alert alert-success'>Document page updated successfully.</div>";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO document_pages (section_slug, title, subtitle, description, slug, pdf_file, hero_image, sort_order, is_published) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$section_slug, $title, $subtitle, $description, $slug, $pdf_file_path, $hero_image_path, $sort_order, $is_published]);
                    $message = "<div class='alert alert-success'>Document page added successfully.</div>";
                }
            }
        }
    }
}

// Fetch all pages
$stmt = $pdo->query("SELECT * FROM document_pages ORDER BY sort_order ASC, id ASC");
$allPages = $stmt->fetchAll();

// Handle edit fetch state
$editPage = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM document_pages WHERE id = ?");
    $stmt->execute([$editId]);
    $editPage = $stmt->fetch();
}
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <!-- Header -->
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Portal Control Desk</span>
                <h1 class="admin-page-title">Manage Document Pages</h1>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <a href="dashboard.php" class="admin-btn admin-btn-outline">← Dashboard</a>
                <?php if ($editPage): ?>
                    <a href="document_pages.php" class="admin-btn admin-btn-primary">+ Create New Page</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div class="row g-5">
            <!-- Left Side: Add / Edit Form -->
            <div class="col-lg-5">
                <div class="admin-card">
                    <h3 class="admin-card-title">
                        <?php echo $editPage ? 'Edit Document Page' : 'Create Standardized Page'; ?>
                    </h3>
                    <p class="admin-card-desc">Configure layout and document templates.</p>

                    <form action="document_pages.php<?php if ($editPage) echo '?edit=' . $editPage['id']; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                        <?php if ($editPage): ?>
                            <input type="hidden" name="id" value="<?php echo $editPage['id']; ?>">
                            <input type="hidden" name="existing_pdf" value="<?php echo htmlspecialchars($editPage['pdf_file']); ?>">
                            <input type="hidden" name="existing_hero_image" value="<?php echo htmlspecialchars($editPage['hero_image'] ?? ''); ?>">
                        <?php endif; ?>

                        <!-- Section slug -->
                        <div class="admin-form-group">
                            <label>Section (for Navigation Mapping)</label>
                            <select name="section_slug" class="admin-select" required>
                                <option value="about" <?php if ($editPage && $editPage['section_slug'] === 'about') echo 'selected'; ?>>About</option>
                                <option value="selection-guidelines" <?php if ($editPage && $editPage['section_slug'] === 'selection-guidelines') echo 'selected'; ?>>Selection Guidelines</option>
                                <option value="news-media" <?php if ($editPage && $editPage['section_slug'] === 'news-media') echo 'selected'; ?>>News & Media</option>
                                <option value="governance" <?php if ($editPage && $editPage['section_slug'] === 'governance') echo 'selected'; ?>>Governance</option>
                                <option value="downloads" <?php if ($editPage && $editPage['section_slug'] === 'downloads') echo 'selected'; ?>>Forms & Downloads</option>
                            </select>
                            <small class="text-muted" style="font-size:0.75rem;">Determines which main drop-down menu it aligns with.</small>
                        </div>

                        <!-- Subtitle (Eyebrow) -->
                        <div class="admin-form-group">
                            <label>Subtitle (Eyebrow Tag)</label>
                            <input type="text" name="subtitle" class="admin-input" placeholder="e.g. Selection Guidelines" value="<?php echo htmlspecialchars($editPage['subtitle'] ?? ''); ?>" required>
                        </div>

                        <!-- Title -->
                        <div class="admin-form-group">
                            <label>Main Heading Title</label>
                            <input type="text" name="title" class="admin-input" placeholder="e.g. SELECTION POLICY" value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required>
                        </div>

                        <!-- Slug -->
                        <div class="admin-form-group">
                            <label>Page Slug (URL Identifier)</label>
                            <input type="text" name="slug" class="admin-input" placeholder="e.g. selection-policy" value="<?php echo htmlspecialchars($editPage['slug'] ?? ''); ?>">
                            <small class="text-muted" style="font-size:0.75rem;">Leave blank to auto-generate from Title.</small>
                        </div>

                        <!-- Description -->
                        <div class="admin-form-group">
                            <label>Brief Description (Hero Paragraph)</label>
                            <textarea name="description" rows="3" class="admin-textarea" placeholder="Provide a brief context or description for this document..."><?php echo htmlspecialchars($editPage['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- PDF Upload -->
                        <div class="admin-form-group">
                            <label>PDF Document File</label>
                            <input type="file" name="pdf_document" class="admin-input" accept=".pdf" <?php if (!$editPage) echo 'required'; ?>>
                            <?php if ($editPage && !empty($editPage['pdf_file'])): ?>
                                <div class="mt-2" style="font-size:0.8rem;">
                                    <span class="text-muted">Current file:</span> <a href="../<?php echo htmlspecialchars($editPage['pdf_file']); ?>" target="_blank" style="color:var(--bsfi-green); font-weight: 600;"><?php echo basename($editPage['pdf_file']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Custom Hero Image Upload -->
                        <div class="admin-form-group">
                            <label>Custom Hero Cover Image (Optional)</label>
                            <input type="file" name="hero_image" class="admin-input" accept="image/*">
                            <small class="text-muted" style="font-size:0.75rem;">Upload a custom image to overwrite the standard background.</small>
                            <?php if ($editPage && !empty($editPage['hero_image'])): ?>
                                <div class="mt-2" style="font-size:0.8rem; display:flex; align-items:center; gap:0.5rem;">
                                    <span class="text-muted">Current image:</span>
                                    <img src="../<?php echo htmlspecialchars($editPage['hero_image']); ?>" style="height:32px; border-radius:4px; object-fit:cover;" alt="Hero">
                                    <button type="submit" name="clear_hero" class="admin-btn admin-btn-outline" style="font-size:0.7rem; padding:0.2rem 0.5rem;" onclick="return confirm('Clear custom hero image?');">Clear</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sort order and publish toggle -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="admin-form-group" style="margin-bottom: 0;">
                                    <label>Sort Order</label>
                                    <input type="number" name="sort_order" class="admin-input" value="<?php echo (int)($editPage['sort_order'] ?? 0); ?>">
                                </div>
                            </div>
                            <div class="col-6" style="display:flex; align-items:flex-end;">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="is_published" <?php if (!$editPage || $editPage['is_published'] == 1) echo 'checked'; ?>>
                                    <label class="form-check-label" for="flexSwitchCheckChecked" style="font-size:0.85rem; font-weight:600; color: var(--text-secondary);">Published</label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <button type="submit" name="save_page" class="admin-btn admin-btn-primary" style="width: 100%; padding:0.75rem;">
                            <?php echo $editPage ? 'Update Page Template' : 'Publish Document Page'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Side: Existing Pages Table -->
            <div class="col-lg-7">
                <div class="admin-card">
                    <h3 class="admin-card-title">
                        Standardized Document Pages
                    </h3>
                    <p class="admin-card-desc">Active dynamic document templates.</p>

                    <?php if (count($allPages) > 0): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Page details</th>
                                        <th>Section</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allPages as $page): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:700; color:var(--navy); font-size:0.95rem;"><?php echo htmlspecialchars($page['title']); ?></div>
                                                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.15rem;">
                                                    <span>slug:</span> <code><?php echo htmlspecialchars($page['slug']); ?></code>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($page['section_slug']); ?></span>
                                            </td>
                                            <td style="text-align:center;">
                                                <?php if ($page['is_published']): ?>
                                                    <span class="admin-badge admin-badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge-pending">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <div style="display:flex; justify-content:flex-end; gap:0.4rem; align-items: center;">
                                                    <a href="document_pages.php?edit=<?php echo $page['id']; ?>" class="admin-btn admin-btn-outline" style="padding: 0.3rem 0.75rem; font-size: 0.75rem;">Edit</a>
                                                    <a href="../page.php?section=<?php echo urlencode($page['section_slug']); ?>&slug=<?php echo urlencode($page['slug']); ?>" target="_blank" class="admin-btn admin-btn-secondary" style="padding: 0.3rem 0.75rem; font-size: 0.75rem;">View</a>
                                                    <form action="document_pages.php" method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this document page?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                        <button type="submit" name="delete_page" class="admin-btn admin-btn-danger" style="padding: 0.3rem 0.75rem; font-size: 0.75rem;">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:4rem 1rem; color: var(--text-muted);">
                            No standardized document pages found. Create one using the form on the left.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
