<?php
// gallery.php - Admin panel to manage Photo Gallery

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Manage Gallery - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';
$uploadDir = __DIR__ . '/../uploads/gallery/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Delete
if (isset($_POST['delete_image']) && isset($_POST['image_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $imageId = (int)$_POST['image_id'];
         
         // Fetch image to delete file
         $stmt = $pdo->prepare("SELECT image_path FROM gallery_images WHERE id=?");
         $stmt->execute([$imageId]);
         $imgItem = $stmt->fetch();
         
         if ($imgItem && !empty($imgItem['image_path']) && file_exists(__DIR__ . '/../' . $imgItem['image_path'])) {
             unlink(__DIR__ . '/../' . $imgItem['image_path']);
         }

         $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id=?");
         $stmt->execute([$imageId]);
         logAction($pdo, "Deleted Gallery Image", "gallery", $imageId);
         $message = "<div class='alert alert-success'>Image deleted successfully.</div>";
    }
}

// Handle Save (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_image'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $event_name = trim($_POST['event_name']);
        $description = trim($_POST['description']);
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        
        // Handle Image Upload
        $imagePath = '';
        $uploadSuccess = false;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/gallery/' . $fileName;
                $uploadSuccess = true;
            }
        }

        if ($id > 0) {
            // Update
            if ($uploadSuccess) {
                $stmt = $pdo->prepare("UPDATE gallery_images SET title=?, category=?, event_name=?, description=?, image_path=?, sort_order=?, featured=?, active=? WHERE id=?");
                $stmt->execute([$title, $category, $event_name, $description, $imagePath, $sort_order, $featured, $active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE gallery_images SET title=?, category=?, event_name=?, description=?, sort_order=?, featured=?, active=? WHERE id=?");
                $stmt->execute([$title, $category, $event_name, $description, $sort_order, $featured, $active, $id]);
            }
            logAction($pdo, "Updated Gallery Image", "gallery", $id);
            $message = "<div class='alert alert-success'>Image details updated successfully.</div>";
        } else {
            // Insert requires an image
            if (!$uploadSuccess) {
                $message = "<div class='alert alert-danger'>You must upload an image.</div>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO gallery_images (title, category, event_name, description, image_path, sort_order, featured, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $category, $event_name, $description, $imagePath, $sort_order, $featured, $active]);
                $newId = $pdo->lastInsertId();
                logAction($pdo, "Added Gallery Image", "gallery", $newId);
                $message = "<div class='alert alert-success'>Image added successfully.</div>";
            }
        }
    }
}

// Fetch gallery images
$stmt = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC");
$galleryList = $stmt->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#ff7e67; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Content Management</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Manage Photo Gallery</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openGalleryModal(0)" class="btn" style="background:#ff7e67; color:#fff; font-weight:bold; border-radius:999px; cursor:pointer;">Upload Photo</button>
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Gallery Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:1.5rem;">
            <?php if (count($galleryList) > 0): ?>
                <?php foreach ($galleryList as $item): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); border-radius:20px; overflow:hidden; position:relative; <?php echo !$item['active'] ? 'opacity: 0.6;' : ''; ?>">
                        
                        <!-- Thumbnail -->
                        <div style="width: 100%; aspect-ratio: 4/3; background: #000; position: relative;">
                            <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Gallery Image" style="width: 100%; height: 100%; object-fit: cover;">
                            
                            <!-- Badges -->
                            <div style="position:absolute; top:10px; left:10px; display:flex; gap:0.5rem; flex-wrap:wrap;">
                                <?php if($item['featured']): ?>
                                    <span style="background:#F4B942; color:#08142E; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:bold; text-transform:uppercase;">★ Featured</span>
                                <?php endif; ?>
                                <?php if(!$item['active']): ?>
                                    <span style="background:#D72638; color:#fff; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:bold; text-transform:uppercase;">Hidden</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info -->
                        <div style="padding: 1.25rem;">
                            <h4 style="font-family:'Outfit',sans-serif; font-size:1.1rem; margin-bottom:0.25rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($item['title'] ?: 'Untitled Image'); ?>
                            </h4>
                            <p style="font-size:0.8rem; opacity:0.7; margin-bottom:1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($item['event_name'] ?: $item['category']); ?>
                            </p>
                            
                            <!-- Actions -->
                            <div style="display:flex; gap:0.5rem; justify-content:space-between; align-items:center;">
                                <div style="font-size:0.8rem; opacity:0.6;">Sort: <?php echo (int)$item['sort_order']; ?></div>
                                <div style="display:flex; gap:0.5rem;">
                                    <button onclick='openGalleryModal(<?php echo json_encode(array_map('strval', $item)); ?>)' style="background:none; border:none; color:#24C27A; cursor:pointer;" title="Edit">✏️</button>
                                    <form action="gallery.php" method="POST" onsubmit="return confirm('Delete this image permanently?');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="image_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_image" style="background:none; border:none; color:#D72638; cursor:pointer;" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1;" class="glass-card">
                    <div style="background:rgba(22, 41, 90, 0.2); padding:4rem; border-radius:28px; text-align:center;">
                        <p style="font-size:1.1rem; opacity:0.7;">No photos in the gallery. Click "Upload Photo" to begin.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for Gallery -->
<div id="gallery-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); z-index:9999;">
    <div class="glass-card" style="background:#08142E; border:1px solid rgba(255,255,255,0.1); padding:2.5rem; border-radius:28px; max-width:600px; width:95%; position:relative; max-height: 90vh; overflow-y: auto;">
        <button onclick="closeGalleryModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#FAF7F0; font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem;">Upload Photo</h3>
        
        <form action="gallery.php" method="POST" enctype="multipart/form-data" id="gallery-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="save_image" value="1">
            <input type="hidden" name="id" id="gallery-id" value="0">
            
            <div class="form-group">
                <label for="gallery-image" style="font-size:0.8rem; font-weight:600;">Image File <span style="color:#D72638" id="image-req-star">*</span></label>
                <input type="file" id="gallery-image" name="image" class="form-input" accept="image/*" required>
                <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem;" id="image-help-text">Select an image to upload.</p>
            </div>

            <div class="form-group">
                <label for="gallery-title" style="font-size:0.8rem; font-weight:600;">Caption / Title</label>
                <input type="text" id="gallery-title" name="title" class="form-input" placeholder="e.g. Medal Ceremony">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="gallery-category" style="font-size:0.8rem; font-weight:600;">Category</label>
                    <select id="gallery-category" name="category" class="form-input">
                        <option value="Championship">Championship</option>
                        <option value="Camp">Camp</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Award">Award</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gallery-event" style="font-size:0.8rem; font-weight:600;">Associated Event</label>
                    <input type="text" id="gallery-event" name="event_name" class="form-input" placeholder="e.g. Nationals 2026">
                </div>
            </div>

            <div class="form-group">
                <label for="gallery-desc" style="font-size:0.8rem; font-weight:600;">Detailed Description (Optional)</label>
                <textarea id="gallery-desc" name="description" class="form-input" rows="2" placeholder="More details about the photo..."></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:center; border-top: 1px solid rgba(255,255,255,0.1); margin-top:0.5rem; padding-top:1.5rem;">
                <div class="form-group">
                    <label for="gallery-sort" style="font-size:0.8rem; font-weight:600;">Sort Order</label>
                    <input type="number" id="gallery-sort" name="sort_order" class="form-input" value="0">
                </div>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" id="gallery-featured" name="featured" value="1" style="width: 18px; height: 18px;">
                        <label for="gallery-featured" style="font-size:0.9rem; font-weight:600; cursor:pointer; color:#F4B942;">★ Add to Slideshow</label>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" id="gallery-active" name="active" value="1" checked style="width: 18px; height: 18px;">
                        <label for="gallery-active" style="font-size:0.9rem; font-weight:600; cursor:pointer;">Visible on Site</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn" style="background:#ff7e67; color:#fff; font-weight:bold; padding:0.85rem; border-radius:999px; cursor:pointer; margin-top:1rem; width:100%; font-size:1.1rem;">Save Photo</button>
        </form>
    </div>
</div>

<script>
function openGalleryModal(item) {
    const modal = document.getElementById('gallery-modal');
    const form = document.getElementById('gallery-editor-form');
    const imageInput = document.getElementById('gallery-image');
    
    if (item === 0) {
        document.getElementById('modal-title').textContent = "Upload Photo";
        document.getElementById('gallery-id').value = 0;
        form.reset();
        document.getElementById('gallery-active').checked = true;
        imageInput.required = true;
        document.getElementById('image-req-star').style.display = 'inline';
        document.getElementById('image-help-text').textContent = "Select an image to upload.";
    } else {
        document.getElementById('modal-title').textContent = "Edit Photo Details";
        document.getElementById('gallery-id').value = item.id;
        document.getElementById('gallery-title').value = item.title || '';
        document.getElementById('gallery-category').value = item.category || 'Championship';
        document.getElementById('gallery-event').value = item.event_name || '';
        document.getElementById('gallery-desc').value = item.description || '';
        document.getElementById('gallery-sort').value = item.sort_order;
        document.getElementById('gallery-featured').checked = item.featured == 1;
        document.getElementById('gallery-active').checked = item.active == 1;
        
        imageInput.required = false;
        document.getElementById('image-req-star').style.display = 'none';
        document.getElementById('image-help-text').textContent = "Leave empty to keep existing image.";
    }
    
    modal.style.display = 'flex';
}

function closeGalleryModal() {
    document.getElementById('gallery-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
