<?php
// admin/circular-edit.php - Edit/Create interface for Circulars & Notices
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$message = '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$doc = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM circulars_notices WHERE id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if (!$doc) {
        header("Location: circulars.php");
        exit();
    }
}

// Set form field default values
$title = $doc['title'] ?? '';
$category = $doc['category'] ?? 'Circular';
$publication_date = $doc['publication_date'] ?? date('Y-m-d');
$description = $doc['description'] ?? '';
$status = $doc['status'] ?? 'Draft';
$pdf_path = $doc['pdf_path'] ?? '';
$original_filename = $doc['original_filename'] ?? '';

// Ensure target upload directory exists and has access limits
$uploadDir = dirname(__DIR__) . '/uploads/circulars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'Circular';
        $publication_date = $_POST['publication_date'] ?? date('Y-m-d');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Draft';

        // Basic inputs validation
        if (empty($title) || empty($publication_date) || !in_array($category, ['Circular', 'Notice']) || !in_array($status, ['Draft', 'Published', 'Archived'])) {
            $message = "<div class='alert alert-danger'>Please fill all required fields correctly.</div>";
        } else {
            $uploadSuccess = true;
            $newPdfPath = $pdf_path;
            $newOriginalFilename = $original_filename;
            $oldFileToRemove = '';

            // Handle file upload
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $tmpFile = $_FILES['pdf_file']['tmp_name'];
                $size = $_FILES['pdf_file']['size'];
                $originalName = $_FILES['pdf_file']['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                // 1. Verify extension
                if ($ext !== 'pdf') {
                    $uploadSuccess = false;
                    $message = "<div class='alert alert-danger'>Invalid file extension. Only .pdf files are accepted.</div>";
                }
                
                // 2. Verify MIME type
                if ($uploadSuccess) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmpFile);
                    finfo_close($finfo);
                    if ($mime !== 'application/pdf') {
                        $uploadSuccess = false;
                        $message = "<div class='alert alert-danger'>Invalid MIME type. File is not a valid PDF.</div>";
                    }
                }

                // 3. Verify magic bytes (%PDF-)
                if ($uploadSuccess) {
                    $handle = fopen($tmpFile, 'rb');
                    $magicBytes = fread($handle, 5);
                    fclose($handle);
                    if ($magicBytes !== '%PDF-') {
                        $uploadSuccess = false;
                        $message = "<div class='alert alert-danger'>Invalid file signature. File header is not a valid PDF structure.</div>";
                    }
                }

                // 4. Verify file size (10MB limit)
                if ($uploadSuccess && $size > 10 * 1024 * 1024) {
                    $uploadSuccess = false;
                    $message = "<div class='alert alert-danger'>File size exceeds the maximum limit of 10MB.</div>";
                }

                // 5. Generate secure filename and store
                if ($uploadSuccess) {
                    $uniqueName = 'circular_' . bin2hex(random_bytes(8)) . '_' . time() . '.pdf';
                    $destPath = $uploadDir . $uniqueName;

                    if (move_uploaded_file($tmpFile, $destPath)) {
                        $newPdfPath = 'uploads/circulars/' . $uniqueName;
                        $newOriginalFilename = basename($originalName);
                        
                        // Queue old file to delete after successful db transaction
                        if (!empty($pdf_path) && file_exists(dirname(__DIR__) . '/' . $pdf_path)) {
                            $oldFileToRemove = dirname(__DIR__) . '/' . $pdf_path;
                        }
                    } else {
                        $uploadSuccess = false;
                        $message = "<div class='alert alert-danger'>Failed to move uploaded file. Check directory permissions.</div>";
                    }
                }
            } elseif ($id === 0 && (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK)) {
                $uploadSuccess = false;
                $message = "<div class='alert alert-danger'>A PDF document is required for new entries.</div>";
            }

            if ($uploadSuccess) {
                try {
                    if ($id > 0) {
                        // Update existing entry
                        $stmt = $pdo->prepare("UPDATE circulars_notices SET title = ?, category = ?, publication_date = ?, description = ?, pdf_path = ?, original_filename = ?, status = ?, updated_by = ? WHERE id = ?");
                        $stmt->execute([$title, $category, $publication_date, $description, $newPdfPath, $newOriginalFilename, $status, $_SESSION['user_id'], $id]);
                        logAction($pdo, "Updated Circular", "circular", $id);
                        $message = "<div class='alert alert-success'>Circular/Notice updated successfully!</div>";
                    } else {
                        // Create new entry
                        $stmt = $pdo->prepare("INSERT INTO circulars_notices (title, category, publication_date, description, pdf_path, original_filename, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $category, $publication_date, $description, $newPdfPath, $newOriginalFilename, $status, $_SESSION['user_id']]);
                        $id = $pdo->lastInsertId();
                        logAction($pdo, "Created Circular", "circular", $id);
                        $message = "<div class='alert alert-success'>Circular/Notice created successfully!</div>";
                    }

                    // Update cached variables
                    $pdf_path = $newPdfPath;
                    $original_filename = $newOriginalFilename;

                    // Safely delete old PDF file now that db write is complete
                    if (!empty($oldFileToRemove)) {
                        @unlink($oldFileToRemove);
                    }

                    // Redirect to list page
                    header("Location: circulars.php");
                    exit();

                } catch (PDOException $e) {
                    $message = "<div class='alert alert-danger'>Database Error: Failed to save changes.</div>";
                }
            }
        }
    }
}

$page_title = ($id > 0 ? "Edit" : "Add New") . " Circular/Notice - BSFI Admin";
include __DIR__ . '/../includes/header.php';
?>

<!-- FontAwesome 6 Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="admin-wrapper" id="main-content">
    <div class="container-fluid" style="padding: 2rem;">
        
        <!-- Header Title Row -->
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Content Management</span>
                <h1 class="admin-page-title"><?php echo ($id > 0 ? "Edit" : "Add New"); ?> Circular / Notice</h1>
            </div>
            <div>
                <a href="circulars.php" class="admin-btn admin-btn-outline"><i class="fa-solid fa-arrow-left me-1"></i> Back to Listing</a>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <!-- Form Card -->
        <div class="admin-card" style="max-width: 800px;">
            <h3 class="admin-card-title"><?php echo ($id > 0 ? "Document Details" : "Upload Document"); ?></h3>
            
            <form action="circular-edit.php<?php echo ($id > 0 ? '?id=' . $id : ''); ?>" method="POST" enctype="multipart/form-data" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-3">
                    <!-- Title -->
                    <div class="col-12">
                        <label for="title-input" class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title-input" name="title" class="form-control" placeholder="e.g. Selection Policy for Asian Para Games 2026" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label for="category-select" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                        <select id="category-select" name="category" class="form-select" required>
                            <option value="Circular" <?php echo $category === 'Circular' ? 'selected' : ''; ?>>Circular</option>
                            <option value="Notice" <?php echo $category === 'Notice' ? 'selected' : ''; ?>>Notice</option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-6">
                        <label for="date-select" class="form-label fw-bold">Publication Date <span class="text-danger">*</span></label>
                        <input type="date" id="date-select" name="publication_date" class="form-control" value="<?php echo htmlspecialchars($publication_date); ?>" required>
                    </div>

                    <!-- Short Description -->
                    <div class="col-12">
                        <label for="desc-textarea" class="form-label fw-bold">Short Description</label>
                        <textarea id="desc-textarea" name="description" class="form-control" rows="3" placeholder="Briefly describe the contents or purpose of this document..."><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label for="status-select" class="form-label fw-bold">Publishing Status <span class="text-danger">*</span></label>
                        <select id="status-select" name="status" class="form-select" required>
                            <option value="Draft" <?php echo $status === 'Draft' ? 'selected' : ''; ?>>Draft (Hidden from public)</option>
                            <option value="Published" <?php echo $status === 'Published' ? 'selected' : ''; ?>>Published (Visible on site)</option>
                            <option value="Archived" <?php echo $status === 'Archived' ? 'selected' : ''; ?>>Archived (Access restricted)</option>
                        </select>
                    </div>

                    <!-- PDF File Upload -->
                    <div class="col-md-6">
                        <label for="file-upload" class="form-label fw-bold">PDF Document <?php echo ($id === 0 ? '<span class="text-danger">*</span>' : ''); ?></label>
                        <input type="file" id="file-upload" name="pdf_file" class="form-control" accept=".pdf" <?php echo ($id === 0 ? 'required' : ''); ?>>
                        <div class="form-text small text-secondary">
                            Only valid .pdf files up to 10MB are allowed.
                        </div>
                        <?php if ($id > 0 && !empty($original_filename)): ?>
                            <div class="mt-2 text-primary small">
                                <i class="fa-solid fa-file-pdf"></i> Current File: <strong><?php echo htmlspecialchars($original_filename); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="circulars.php" class="admin-btn admin-btn-outline rounded-pill px-4">Cancel</a>
                    <button type="submit" name="save_document" class="admin-btn admin-btn-primary rounded-pill px-4">Save Document</button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
