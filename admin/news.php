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
         
         // Fetch image to delete file
         $stmt = $pdo->prepare("SELECT image FROM news WHERE id=?");
         $stmt->execute([$newsId]);
         $newsItem = $stmt->fetch();
         
         if ($newsItem && !empty($newsItem['image']) && file_exists(__DIR__ . '/../' . $newsItem['image'])) {
             unlink(__DIR__ . '/../' . $newsItem['image']);
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
        $published_at = null;
        if ($status === 'published') {
            $published_at = date('Y-m-d H:i:s');
        }

        if (empty($title) || empty($content)) {
            $message = "<div class='alert alert-danger'>Title and Content are required.</div>";
        } else {
            // Handle Image Upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/news/' . $fileName;
                }
            }

            if ($id > 0) {
                // Update
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, excerpt=?, category=?, content=?, image=?, featured=?, pinned=?, status=?, meta_title=?, meta_description=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $category, $content, $imagePath, $featured, $pinned, $status, $meta_title, $meta_description, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, excerpt=?, category=?, content=?, featured=?, pinned=?, status=?, meta_title=?, meta_description=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $category, $content, $featured, $pinned, $status, $meta_title, $meta_description, $id]);
                }
                
                // Update published_at only if it wasn't published before and now is
                $stmt = $pdo->prepare("SELECT published_at FROM news WHERE id=?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if ($status === 'published' && empty($existing['published_at'])) {
                    $stmt = $pdo->prepare("UPDATE news SET published_at=? WHERE id=?");
                    $stmt->execute([$published_at, $id]);
                }

                logAction($pdo, "Updated News Article", "news", $id);
                $message = "<div class='alert alert-success'>News updated successfully.</div>";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO news (title, slug, excerpt, category, content, image, featured, pinned, status, meta_title, meta_description, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $excerpt, $category, $content, $imagePath, $featured, $pinned, $status, $meta_title, $meta_description, $published_at]);
                $newId = $pdo->lastInsertId();
                logAction($pdo, "Added News Article", "news", $newId);
                $message = "<div class='alert alert-success'>News added successfully.</div>";
            }
        }
    }
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
                                <span><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></span>
                                <span><strong>Views:</strong> <?php echo (int)$item['views']; ?></span>
                                <span><strong>Date:</strong> <?php echo $item['published_at'] ? date('M j, Y', strtotime($item['published_at'])) : 'Unpublished'; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <button onclick='openNewsModal(<?php echo json_encode(array_map('strval', $item)); ?>)' class="btn" style="border:1px solid #24C27A; color:#24C27A; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center; font-size:0.85rem;">Edit Article</button>
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
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="news-slug" style="font-size:0.8rem; font-weight:600;">URL Slug (Leave blank to auto-generate)</label>
                <input type="text" id="news-slug" name="slug" class="form-input" placeholder="e.g. national-boccia-championship-2026">
            </div>

            <div class="form-group">
                <label for="news-excerpt" style="font-size:0.8rem; font-weight:600;">Excerpt / Subhead</label>
                <textarea id="news-excerpt" name="excerpt" class="form-input" rows="2" placeholder="Brief summary of the article..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="news-content" style="font-size:0.8rem; font-weight:600;">Content (up to 5000 chars) <span style="color:#D72638">*</span></label>
                <textarea id="news-content" name="content" class="form-input" rows="8" maxlength="5000" required placeholder="Write your full article here..."></textarea>
            </div>

            <div class="form-group">
                <label for="news-image" style="font-size:0.8rem; font-weight:600;">Cover Image</label>
                <input type="file" id="news-image" name="image" class="form-input" accept="image/*">
                <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem;">Leave empty to keep existing image when editing.</p>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:0.5rem; padding-top:1.5rem;">
                <h4 style="font-size:1rem; margin-bottom:1rem; color:#A0AABF;">SEO & Publishing</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group">
                        <label for="news-meta-title" style="font-size:0.8rem; font-weight:600;">Meta Title (SEO)</label>
                        <input type="text" id="news-meta-title" name="meta_title" class="form-input" placeholder="Custom SEO title...">
                    </div>
                    <div class="form-group">
                        <label for="news-status" style="font-size:0.8rem; font-weight:600;">Status</label>
                        <select id="news-status" name="status" class="form-input">
                            <option value="draft">Draft (Hidden)</option>
                            <option value="published">Published (Live)</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="news-meta-desc" style="font-size:0.8rem; font-weight:600;">Meta Description (SEO)</label>
                    <textarea id="news-meta-desc" name="meta_description" class="form-input" rows="2" placeholder="Custom SEO description..."></textarea>
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
    } else {
        document.getElementById('modal-title').textContent = "Edit Article";
        document.getElementById('news-id').value = item.id;
        document.getElementById('news-title').value = item.title;
        document.getElementById('news-slug').value = item.slug || '';
        document.getElementById('news-category').value = item.category || 'General';
        document.getElementById('news-excerpt').value = item.excerpt || '';
        document.getElementById('news-content').value = item.content;
        document.getElementById('news-status').value = item.status;
        document.getElementById('news-meta-title').value = item.meta_title || '';
        document.getElementById('news-meta-desc').value = item.meta_description || '';
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
