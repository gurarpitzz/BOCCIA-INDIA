<?php
// news.php - Admin panel to manage News & Announcements

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Manage News - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';
$uploadDir = __DIR__ . '/../uploads/news/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper function to create slug
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', "-", $string);
    return rtrim($string, '-');
}

// Handle Delete
if (isset($_POST['delete_news']) && isset($_POST['news_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $newsId = (int)$_POST['news_id'];
         
         // Fetch images to delete files
         $stmt = $pdo->prepare("SELECT image FROM news WHERE id=?");
         $stmt->execute([$newsId]);
         $newsItem = $stmt->fetch();
         if ($newsItem && !empty($newsItem['image']) && file_exists(__DIR__ . '/../' . $newsItem['image'])) {
             unlink(__DIR__ . '/../' . $newsItem['image']);
         }
         
         $stmt = $pdo->prepare("SELECT image_path FROM news_images WHERE news_id=?");
         $stmt->execute([$newsId]);
         while($img = $stmt->fetch()) {
             if (file_exists(__DIR__ . '/../' . $img['image_path'])) {
                 unlink(__DIR__ . '/../' . $img['image_path']);
             }
         }

         $stmt = $pdo->prepare("DELETE FROM news WHERE id=?");
         $stmt->execute([$newsId]);
         logAction($pdo, "Deleted News Article", "news", $newsId);
         $message = "<div class='alert alert-success'>News article deleted successfully.</div>";
    }
}

// Handle Save (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_news'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $excerpt = trim($_POST['excerpt']);
        $category = trim($_POST['category']);
        $content = trim($_POST['content']);
        $status = $_POST['status'];
        $author_name = trim($_POST['author_name']) ?: 'BSFI Official';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $pinned = isset($_POST['pinned']) ? 1 : 0;
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        
        if (empty($slug)) {
            $slug = createSlug($title);
        }

        // Check if slug exists
        $slugCheck = $pdo->prepare("SELECT id FROM news WHERE slug = ? AND id != ?");
        $slugCheck->execute([$slug, $id]);
        if ($slugCheck->fetch()) {
            $slug = $slug . '-' . time();
        }

        // Published Date logic
        $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
        if ($status === 'published' && empty($published_at)) {
            $published_at = date('Y-m-d H:i:s');
        }

        if (empty($title) || empty($content)) {
            $message = "<div class='alert alert-danger'>Title and Content are required.</div>";
        } else {
            // Handle Image Upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                        $imagePath = 'uploads/news/' . $fileName;
                    }
                }
            }

            if ($id > 0) {
                // Update
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, excerpt=?, category=?, content=?, image=?, featured=?, pinned=?, status=?, meta_title=?, meta_description=?, author_name=?, published_at=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $category, $content, $imagePath, $featured, $pinned, $status, $meta_title, $meta_description, $author_name, $published_at, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, excerpt=?, category=?, content=?, featured=?, pinned=?, status=?, meta_title=?, meta_description=?, author_name=?, published_at=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $category, $content, $featured, $pinned, $status, $meta_title, $meta_description, $author_name, $published_at, $id]);
                }
                
                logAction($pdo, "Updated News Article", "news", $id);
                $message = "<div class='alert alert-success'>News updated successfully.</div>";
                $newsIdForExtra = $id;
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO news (title, slug, excerpt, category, content, image, featured, pinned, status, meta_title, meta_description, author_name, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $excerpt, $category, $content, $imagePath, $featured, $pinned, $status, $meta_title, $meta_description, $author_name, $published_at]);
                $newsIdForExtra = $pdo->lastInsertId();
                logAction($pdo, "Added News Article", "news", $newsIdForExtra);
                $message = "<div class='alert alert-success'>News added successfully.</div>";
            }
            
            // Handle additional_images
            if (isset($_FILES['additional_images'])) {
                $file_count = is_array($_FILES['additional_images']['name']) ? count($_FILES['additional_images']['name']) : 0;
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Get current count of additional images to enforce max 4
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM news_images WHERE news_id=?");
                $stmt->execute([$newsIdForExtra]);
                $current_count = $stmt->fetchColumn();
                
                for ($i = 0; $i < $file_count && ($current_count + $i) < 4; $i++) {
                    if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['additional_images']['name'][$i], PATHINFO_EXTENSION));
                        $size = $_FILES['additional_images']['size'][$i];
                        if (in_array($ext, $allowed_exts) && $size <= $max_size) {
                            $fileName = time() . '_' . rand(1000, 9999) . '_' . basename($_FILES['additional_images']['name'][$i]);
                            $targetFile = $uploadDir . $fileName;
                            if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $targetFile)) {
                                $stmt = $pdo->prepare("INSERT INTO news_images (news_id, image_path, sort_order) VALUES (?, ?, ?)");
                                $stmt->execute([$newsIdForExtra, 'uploads/news/' . $fileName, $current_count + $i]);
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle Delete Extra Image
if (isset($_GET['delete_img']) && isset($_GET['news_id'])) {
    $imgId = (int)$_GET['delete_img'];
    $newsId = (int)$_GET['news_id'];
    $stmt = $pdo->prepare("SELECT image_path FROM news_images WHERE id=? AND news_id=?");
    $stmt->execute([$imgId, $newsId]);
    $img = $stmt->fetch();
    if ($img) {
        if (file_exists(__DIR__ . '/../' . $img['image_path'])) unlink(__DIR__ . '/../' . $img['image_path']);
        $pdo->prepare("DELETE FROM news_images WHERE id=?")->execute([$imgId]);
    }
    header("Location: news.php");
    exit;
}

// Fetch news
$stmt = $pdo->query("SELECT * FROM news ORDER BY pinned DESC, created_at DESC");
$newsList = $stmt->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Content Management</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Manage News</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openNewsModal(0)" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; cursor:pointer;">Write Article</button>
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- News List -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php if (count($newsList) > 0): ?>
                <?php foreach ($newsList as $item): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:1.5rem; border-radius:20px; display:grid; grid-template-columns:120px 3fr 1fr; gap:2rem; align-items:center; <?php echo $item['status'] === 'draft' ? 'opacity: 0.7;' : ''; ?>">
                        
                        <!-- Thumbnail -->
                        <div style="width: 120px; height: 90px; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center;">
                            <?php if(!empty($item['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="News Image" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size:2rem; opacity:0.3;">📰</span>
                            <?php endif; ?>
                        </div>

                        <!-- Content Info -->
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.5rem;">
                                <h3 style="font-size:1.2rem; font-family:'Outfit',sans-serif;"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <?php if($item['status'] === 'published'): ?>
                                    <span style="font-size:0.7rem; background:rgba(36, 194, 122, 0.1); border:1px solid #24C27A; color:#24C27A; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Published</span>
                                <?php elseif($item['status'] === 'scheduled'): ?>
                                    <span style="font-size:0.7rem; background:rgba(107, 130, 184, 0.1); border:1px solid #6b82b8; color:#6b82b8; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Scheduled</span>
                                <?php else: ?>
                                    <span style="font-size:0.7rem; background:rgba(255, 255, 255, 0.05); border:1px solid rgba(255,255,255,0.2); color:#FAF7F0; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Draft</span>
                                <?php endif; ?>
                                <?php if($item['featured']): ?>
                                    <span style="font-size:0.7rem; background:rgba(244, 185, 66, 0.1); border:1px solid #F4B942; color:#F4B942; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Featured</span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.85rem; opacity:0.8; margin-bottom:0.5rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                <?php echo htmlspecialchars($item['excerpt']); ?>
                            </p>
                            <div style="font-size:0.8rem; opacity:0.6; display:flex; gap:1.5rem;">
                                <span><strong>Author:</strong> <?php echo htmlspecialchars($item['author_name']); ?></span>
                                <span><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></span>
                                <span><strong>Views:</strong> <?php echo (int)$item['views']; ?></span>
                                <span><strong>Date:</strong> <?php echo $item['published_at'] ? date('M j, Y h:i A', strtotime($item['published_at'])) : 'Unpublished'; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <a href="../index.php#news-<?php echo $item['slug']; ?>" target="_blank" class="btn" style="border:1px solid #FAF7F0; color:#FAF7F0; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center; font-size:0.85rem; text-decoration:none;">Preview</a>
                            <button onclick='openNewsModal(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)' class="btn" style="border:1px solid #24C27A; color:#24C27A; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center; font-size:0.85rem;">Edit Article</button>
                            <form action="news.php" method="POST" onsubmit="return confirm('Delete this article forever?');" style="display:block;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_news" class="btn" style="border:1px solid #D72638; color:#D72638; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; width:100%; text-align:center; font-size:0.85rem;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.2); padding:4rem; border-radius:28px; text-align:center;">
                    <p style="font-size:1.1rem; opacity:0.7;">No news found. Click "Write Article" to publish your first post.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for News -->
<div id="news-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); z-index:9999;">
    <div class="glass-card" style="background:#08142E; border:1px solid rgba(255,255,255,0.1); padding:2.5rem; border-radius:28px; max-width:800px; width:95%; position:relative; max-height: 90vh; overflow-y: auto;">
        <button onclick="closeNewsModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#FAF7F0; font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem;">Write Article</h3>
        
        <form action="news.php" method="POST" enctype="multipart/form-data" id="news-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="save_news" value="1">
            <input type="hidden" name="id" id="news-id" value="0">
            
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="news-title" style="font-size:0.8rem; font-weight:600;">Article Title <span style="color:#D72638">*</span></label>
                    <input type="text" id="news-title" name="title" class="form-input" required placeholder="Enter headline...">
                </div>
                <div class="form-group">
                    <label for="news-category" style="font-size:0.8rem; font-weight:600;">Category</label>
                    <select id="news-category" name="category" class="form-input">
                        <option value="General">General</option>
                        <option value="Championships">Championships</option>
                        <option value="Announcements">Announcements</option>
                        <option value="Selections">Selections</option>
                        <option value="Workshops">Workshops</option>
                        <option value="International">International</option>
                        <option value="Press Release">Press Release</option>
                        <option value="Athlete Spotlight">Athlete Spotlight</option>
                        <option value="National Events">National Events</option>
                        <option value="Results">Results</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="news-author" style="font-size:0.8rem; font-weight:600;">Author Name</label>
                    <input type="text" id="news-author" name="author_name" class="form-input" placeholder="e.g. BSFI Media Team" value="BSFI Official">
                </div>
                <div class="form-group">
                    <label for="news-slug" style="font-size:0.8rem; font-weight:600;">URL Slug</label>
                    <input type="text" id="news-slug" name="slug" class="form-input" placeholder="Auto-generated if empty">
                </div>
            </div>

            <div class="form-group">
                <label for="news-excerpt" style="font-size:0.8rem; font-weight:600;">Excerpt / Subhead</label>
                <textarea id="news-excerpt" name="excerpt" class="form-input" rows="2" placeholder="Brief summary of the article..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="news-content" style="font-size:0.8rem; font-weight:600;">Content (up to 5000 chars) <span style="color:#D72638">*</span></label>
                <textarea id="news-content" name="content" class="form-input" rows="8" maxlength="5000" required placeholder="Write your full article here. Hashtags will be auto-styled."></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; border:1px dashed rgba(255,255,255,0.2); padding:1rem; border-radius:12px;">
                <div class="form-group">
                    <label for="news-image" style="font-size:0.8rem; font-weight:600;">Cover Image (Thumbnail)</label>
                    <input type="file" id="news-image" name="image" class="form-input" accept="image/jpeg,image/png,image/webp">
                    <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem;">Max 5MB. Leave empty to keep existing.</p>
                </div>
                <div class="form-group">
                    <label for="news-extra-images" style="font-size:0.8rem; font-weight:600;">Additional Images (Max 4)</label>
                    <input type="file" id="news-extra-images" name="additional_images[]" class="form-input" multiple accept="image/jpeg,image/png,image/webp">
                    <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem;">Select up to 4 images to create a gallery.</p>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:0.5rem; padding-top:1.5rem;">
                <h4 style="font-size:1rem; margin-bottom:1rem; color:#A0AABF;">Publishing & Scheduling</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group">
                        <label for="news-status" style="font-size:0.8rem; font-weight:600;">Status</label>
                        <select id="news-status" name="status" class="form-input">
                            <option value="draft">Draft (Hidden)</option>
                            <option value="published">Published (Live)</option>
                            <option value="scheduled">Scheduled (Future)</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="news-published-at" style="font-size:0.8rem; font-weight:600;">Publish Date/Time</label>
                        <input type="datetime-local" id="news-published-at" name="published_at" class="form-input">
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:1.5rem; align-items:center; margin-top: 0.5rem;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="news-featured" name="featured" value="1" style="width: 18px; height: 18px;">
                    <label for="news-featured" style="font-size:0.9rem; font-weight:600; cursor:pointer; color:#F4B942;">★ Featured Article</label>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="news-pinned" name="pinned" value="1" style="width: 18px; height: 18px;">
                    <label for="news-pinned" style="font-size:0.9rem; font-weight:600; cursor:pointer;">📌 Pin to Top</label>
                </div>
            </div>
            
            <button type="submit" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; padding:0.85rem; border-radius:999px; cursor:pointer; margin-top:1rem; width:100%; font-size:1.1rem;">Save Article</button>
        </form>
    </div>
</div>

<script>
function openNewsModal(item) {
    const modal = document.getElementById('news-modal');
    const form = document.getElementById('news-editor-form');
    
    if (item === 0) {
        document.getElementById('modal-title').textContent = "Write Article";
        document.getElementById('news-id').value = 0;
        form.reset();
        document.getElementById('news-author').value = 'BSFI Official';
    } else {
        document.getElementById('modal-title').textContent = "Edit Article";
        document.getElementById('news-id').value = item.id;
        document.getElementById('news-title').value = item.title;
        document.getElementById('news-author').value = item.author_name || 'BSFI Official';
        document.getElementById('news-slug').value = item.slug || '';
        document.getElementById('news-category').value = item.category || 'General';
        document.getElementById('news-excerpt').value = item.excerpt || '';
        document.getElementById('news-content').value = item.content;
        document.getElementById('news-status').value = item.status;
        if(item.published_at) {
            // Convert to datetime-local format YYYY-MM-DDThh:mm
            document.getElementById('news-published-at').value = item.published_at.substring(0, 16).replace(' ', 'T');
        } else {
            document.getElementById('news-published-at').value = '';
        }
        document.getElementById('news-featured').checked = item.featured == 1;
        document.getElementById('news-pinned').checked = item.pinned == 1;
    }
    
    modal.style.display = 'flex';
}

function closeNewsModal() {
    document.getElementById('news-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
