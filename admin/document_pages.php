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

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Federation Portal Control Desk</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Manage Document Pages</h1>
            </div>
            <div>
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">← Dashboard</a>
                <?php if ($editPage): ?>
                    <a href="document_pages.php" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; margin-left:0.5rem;">+ Create New Page</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div class="row g-5">
            <!-- Left Side: Add / Edit Form -->
            <div class="col-lg-5">
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.25); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:2rem;">
                    <h3 style="font-family:'Outfit',sans-serif; font-weight:700; margin-bottom:1.5rem; color:#24C27A;">
                        <?php echo $editPage ? 'Edit Document Page' : 'Create Standardized Page'; ?>
                    </h3>

                    <form action="document_pages.php<?php if ($editPage) echo '?edit=' . $editPage['id']; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                        <?php if ($editPage): ?>
                            <input type="hidden" name="id" value="<?php echo $editPage['id']; ?>">
                            <input type="hidden" name="existing_pdf" value="<?php echo htmlspecialchars($editPage['pdf_file']); ?>">
                            <input type="hidden" name="existing_hero_image" value="<?php echo htmlspecialchars($editPage['hero_image'] ?? ''); ?>">
                        <?php endif; ?>

                        <!-- Section slug -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Section (for Navigation Mapping)</label>
                            <select name="section_slug" class="form-select bg-dark text-light border-secondary" required>
                                <option value="about" <?php if ($editPage && $editPage['section_slug'] === 'about') echo 'selected'; ?>>About</option>
                                <option value="selection-guidelines" <?php if ($editPage && $editPage['section_slug'] === 'selection-guidelines') echo 'selected'; ?>>Selection Guidelines</option>
                                <option value="news-media" <?php if ($editPage && $editPage['section_slug'] === 'news-media') echo 'selected'; ?>>News & Media</option>
                                <option value="governance" <?php if ($editPage && $editPage['section_slug'] === 'governance') echo 'selected'; ?>>Governance</option>
                                <option value="downloads" <?php if ($editPage && $editPage['section_slug'] === 'downloads') echo 'selected'; ?>>Forms & Downloads</option>
                            </select>
                            <small class="text-muted" style="font-size:0.75rem;">Determines which main drop-down menu it aligns with.</small>
                        </div>

                        <!-- Subtitle (Eyebrow) -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Subtitle (Eyebrow Tag)</label>
                            <input type="text" name="subtitle" class="form-control bg-dark text-light border-secondary" placeholder="e.g. Selection Guidelines" value="<?php echo htmlspecialchars($editPage['subtitle'] ?? ''); ?>" required>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Main Heading Title</label>
                            <input type="text" name="title" class="form-control bg-dark text-light border-secondary" placeholder="e.g. SELECTION POLICY" value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required>
                        </div>

                        <!-- Slug -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Page Slug (URL Identifier)</label>
                            <input type="text" name="slug" class="form-control bg-dark text-light border-secondary" placeholder="e.g. selection-policy" value="<?php echo htmlspecialchars($editPage['slug'] ?? ''); ?>">
                            <small class="text-muted" style="font-size:0.75rem;">Leaves blank to auto-generate from Title.</small>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Brief Description (Hero Paragraph)</label>
                            <textarea name="description" rows="3" class="form-control bg-dark text-light border-secondary" placeholder="Provide a brief context or description for this document..."><?php echo htmlspecialchars($editPage['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- PDF Upload -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">PDF Document File</label>
                            <input type="file" name="pdf_document" class="form-control bg-dark text-light border-secondary" accept=".pdf" <?php if (!$editPage) echo 'required'; ?>>
                            <?php if ($editPage && !empty($editPage['pdf_file'])): ?>
                                <div class="mt-2" style="font-size:0.8rem;">
                                    <span style="opacity:0.7;">Current file:</span> <a href="../<?php echo htmlspecialchars($editPage['pdf_file']); ?>" target="_blank" style="color:#24C27A;"><?php echo basename($editPage['pdf_file']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Custom Hero Image Upload -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Custom Hero Cover Image (Optional)</label>
                            <input type="file" name="hero_image" class="form-control bg-dark text-light border-secondary" accept="image/*">
                            <small class="text-muted" style="font-size:0.75rem;">Upload a custom image to overwrite the standard cream background.</small>
                            <?php if ($editPage && !empty($editPage['hero_image'])): ?>
                                <div class="mt-2" style="font-size:0.8rem; display:flex; align-items:center; gap:0.5rem;">
                                    <span style="opacity:0.7;">Current image:</span>
                                    <img src="../<?php echo htmlspecialchars($editPage['hero_image']); ?>" style="height:32px; border-radius:4px; object-fit:cover;" alt="Hero">
                                    <button type="submit" name="clear_hero" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem; padding:0.1rem 0.3rem;" onclick="return confirm('Clear custom hero image?');">Clear</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sort order and publish toggle -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control bg-dark text-light border-secondary" value="<?php echo (int)($editPage['sort_order'] ?? 0); ?>">
                            </div>
                            <div class="col-6" style="display:flex; align-items:flex-end;">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="is_published" <?php if (!$editPage || $editPage['is_published'] == 1) echo 'checked'; ?>>
                                    <label class="form-check-label" for="flexSwitchCheckChecked" style="font-size:0.85rem; opacity:0.85; font-weight:600;">Published</label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <button type="submit" name="save_page" class="btn w-100" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:12px; padding:0.75rem;">
                            <?php echo $editPage ? 'Update Page Template' : 'Publish Document Page'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Side: Existing Pages Table -->
            <div class="col-lg-7">
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.15); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:2rem;">
                    <h3 style="font-family:'Outfit',sans-serif; font-weight:700; margin-bottom:1.5rem; color:#FAF7F0;">
                        Standardized Document Pages
                    </h3>

                    <?php if (count($allPages) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover" style="border:none; margin:0; vertical-align:middle;">
                                <thead>
                                    <tr style="border-bottom:2px solid rgba(255,255,255,0.08);">
                                        <th style="padding:1rem 0.5rem; opacity:0.6; font-size:0.8rem; text-transform:uppercase;">Page details</th>
                                        <th style="padding:1rem 0.5rem; opacity:0.6; font-size:0.8rem; text-transform:uppercase;">Section</th>
                                        <th style="padding:1rem 0.5rem; opacity:0.6; font-size:0.8rem; text-transform:uppercase; text-align:center;">Status</th>
                                        <th style="padding:1rem 0.5rem; opacity:0.6; font-size:0.8rem; text-transform:uppercase; text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allPages as $page): ?>
                                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                            <td style="padding:1.25rem 0.5rem;">
                                                <div style="font-weight:700; color:#FAF7F0; font-size:0.95rem;"><?php echo htmlspecialchars($page['title']); ?></div>
                                                <div style="font-size:0.8rem; color:#6b82b8; margin-top:0.15rem;">
                                                    <span style="opacity:0.6;">slug:</span> <code><?php echo htmlspecialchars($page['slug']); ?></code>
                                                </div>
                                            </td>
                                            <td style="padding:1.25rem 0.5rem;">
                                                <span class="badge bg-secondary" style="font-size:0.75rem; text-transform:uppercase;"><?php echo htmlspecialchars($page['section_slug']); ?></span>
                                            </td>
                                            <td style="padding:1.25rem 0.5rem; text-align:center;">
                                                <?php if ($page['is_published']): ?>
                                                    <span class="badge" style="background:rgba(36,194,122,0.15); color:#24C27A;">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark text-muted">Draft</span>
                                                <?php ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding:1.25rem 0.5rem; text-align:right;">
                                                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                                                    <a href="document_pages.php?edit=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline-light" style="border-radius:6px; font-size:0.8rem;">Edit</a>
                                                    <a href="../page.php?section=<?php echo urlencode($page['section_slug']); ?>&slug=<?php echo urlencode($page['slug']); ?>" target="_blank" class="btn btn-sm" style="background:#24C27A; color:#08142E; border-radius:6px; font-size:0.8rem; font-weight:bold;">View</a>
                                                    <form action="document_pages.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this document page?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                        <button type="submit" name="delete_page" class="btn btn-sm btn-outline-danger" style="border-radius:6px; font-size:0.8rem;">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:4rem 1rem; opacity:0.6;">
                            No standardized document pages found. Create one using the form on the left.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
