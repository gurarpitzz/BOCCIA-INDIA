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
$baseUploadDir = __DIR__ . '/../uploads/news/';

// Helper function to create slug
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', "-", $string);
    return rtrim($string, '-');
}

// Handle Delete (Soft Delete)
if (isset($_POST['delete_news']) && isset($_POST['news_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $newsId = (int)$_POST['news_id'];
         $stmt = $pdo->prepare("UPDATE news SET deleted_at = NOW() WHERE id = ?");
         $stmt->execute([$newsId]);
         logAction($pdo, "Soft Deleted News Article", "news", $newsId);
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
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $content = trim($_POST['content']);
        $status = $_POST['status'];
        $author_name = trim($_POST['author_name']) ?: 'BSFI Official';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        
        $facebook_url = trim($_POST['facebook_url']);
        $instagram_url = trim($_POST['instagram_url']);
        $twitter_url = trim($_POST['twitter_url']);
        $linkedin_url = trim($_POST['linkedin_url']);
        $youtube_url = trim($_POST['youtube_url']);
        
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        
        if (empty($slug)) {
            $slug = createSlug($title);
        }

        // Check if slug exists
        $slugCheck = $pdo->prepare("SELECT id FROM news WHERE slug = ? AND id != ? AND deleted_at IS NULL");
        $slugCheck->execute([$slug, $id]);
        if ($slugCheck->fetch()) {
            $slug = $slug . '-' . time();
        }

        // Published Date / Scheduled Date logic
        $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
        $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        
        if ($status === 'published' && empty($published_at)) {
            $published_at = date('Y-m-d H:i:s');
        }

        if (empty($title) || empty($content)) {
            $message = "<div class='alert alert-danger'>Title and Content are required.</div>";
        } else {
            // First insert/update row to get ID for directory grouping
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE news SET 
                        title=?, slug=?, excerpt=?, category_id=?, content=?, is_featured=?, is_pinned=?, status=?, 
                        meta_title=?, meta_description=?, author_name=?, published_at=?, scheduled_at=?,
                        facebook_url=?, instagram_url=?, twitter_url=?, linkedin_url=?, youtube_url=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $title, $slug, $excerpt, $category_id, $content, $is_featured, $is_pinned, $status,
                    $meta_title, $meta_description, $author_name, $published_at, $scheduled_at,
                    $facebook_url, $instagram_url, $twitter_url, $linkedin_url, $youtube_url, $id
                ]);
                $newsId = $id;
                logAction($pdo, "Updated News Article", "news", $newsId);
                $message = "<div class='alert alert-success'>News updated successfully.</div>";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO news (
                        title, slug, excerpt, category_id, content, is_featured, is_pinned, status, 
                        meta_title, meta_description, author_name, published_at, scheduled_at,
                        facebook_url, instagram_url, twitter_url, linkedin_url, youtube_url
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title, $slug, $excerpt, $category_id, $content, $is_featured, $is_pinned, $status,
                    $meta_title, $meta_description, $author_name, $published_at, $scheduled_at,
                    $facebook_url, $instagram_url, $twitter_url, $linkedin_url, $youtube_url
                ]);
                $newsId = $pdo->lastInsertId();
                logAction($pdo, "Added News Article", "news", $newsId);
                $message = "<div class='alert alert-success'>News added successfully.</div>";
            }

            // Create target folder `/uploads/news/{news_id}/`
            $newsUploadDir = $baseUploadDir . $newsId . '/';
            if (!is_dir($newsUploadDir)) {
                mkdir($newsUploadDir, 0777, true);
            }

            // Upload main Thumbnail Image (Max 1MB)
            $thumbnailPath = null;
            if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['thumbnail_image']['name'], PATHINFO_EXTENSION));
                $size = $_FILES['thumbnail_image']['size'];
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $size <= 1024 * 1024) {
                    $fileName = 'thumb_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $newsUploadDir . $fileName)) {
                        $thumbnailPath = 'uploads/news/' . $newsId . '/' . $fileName;
                        $pdo->prepare("UPDATE news SET thumbnail_image = ? WHERE id = ?")->execute([$thumbnailPath, $newsId]);
                    }
                }
            }

            // Upload Cover Image (Max 1MB)
            $coverPath = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                $size = $_FILES['cover_image']['size'];
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $size <= 1024 * 1024) {
                    $fileName = 'cover_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $newsUploadDir . $fileName)) {
                        $coverPath = 'uploads/news/' . $newsId . '/' . $fileName;
                        $pdo->prepare("UPDATE news SET cover_image = ? WHERE id = ?")->execute([$coverPath, $newsId]);
                    }
                }
            }

            // If thumbnail/cover was set but the other was empty, sync them
            $stmt = $pdo->prepare("SELECT thumbnail_image, cover_image FROM news WHERE id = ?");
            $stmt->execute([$newsId]);
            $currentImages = $stmt->fetch();
            if ($currentImages) {
                if (empty($currentImages['thumbnail_image']) && !empty($currentImages['cover_image'])) {
                    $pdo->prepare("UPDATE news SET thumbnail_image = ? WHERE id = ?")->execute([$currentImages['cover_image'], $newsId]);
                } elseif (!empty($currentImages['thumbnail_image']) && empty($currentImages['cover_image'])) {
                    $pdo->prepare("UPDATE news SET cover_image = ? WHERE id = ?")->execute([$currentImages['thumbnail_image'], $newsId]);
                }
            }

            // Handle Gallery Images (multiple uploads with captions - max 1MB per image)
            if (isset($_FILES['gallery_images'])) {
                $file_count = is_array($_FILES['gallery_images']['name']) ? count($_FILES['gallery_images']['name']) : 0;
                $captions = isset($_POST['gallery_captions']) ? $_POST['gallery_captions'] : [];
                
                // Get current count of additional images to enforce sorting order
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM news_images WHERE news_id=?");
                $stmt->execute([$newsId]);
                $current_count = $stmt->fetchColumn();

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION));
                        $size = $_FILES['gallery_images']['size'][$i];
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $size <= 1024 * 1024) {
                            $fileName = 'gallery_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                            if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $newsUploadDir . $fileName)) {
                                $caption = isset($captions[$i]) ? trim($captions[$i]) : '';
                                $stmt = $pdo->prepare("INSERT INTO news_images (news_id, image_path, caption, sort_order) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$newsId, 'uploads/news/' . $newsId . '/' . $fileName, $caption, $current_count + $i]);
                            }
                        }
                    }
                }
            }
        }
    }
}

// Fetch Search and Filter query parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Build Query
$sql = "SELECT n.*, c.name AS category_name FROM news n LEFT JOIN news_categories c ON n.category_id = c.id WHERE n.deleted_at IS NULL";
$params = [];

if ($search !== '') {
    $sql .= " AND n.title LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($filter_category !== '') {
    $sql .= " AND n.category_id = ?";
    $params[] = (int)$filter_category;
}
if ($filter_status !== '') {
    $sql .= " AND n.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY n.is_pinned DESC, n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

// Fetch Categories for filters
$categories = $pdo->query("SELECT * FROM news_categories ORDER BY name ASC")->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Content Management</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Manage News (v4.1)</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openNewsModal(0)" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; cursor:pointer;">Write Article</button>
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Search and Filtering Panel -->
        <div class="glass-card mb-4" style="background:rgba(22, 41, 90, 0.2); padding:1.5rem; border-radius:20px; border:1px solid rgba(255,255,255,0.08);">
            <form method="GET" action="news.php" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?php echo htmlspecialchars($search); ?>" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                </div>
                <div class="col-md-3">
                    <select name="filter_category" class="form-select" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_status" class="form-select" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary" style="border-radius:8px;">Search</button>
                </div>
            </form>
        </div>

        <!-- News List -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php if (count($newsList) > 0): ?>
                <?php foreach ($newsList as $item): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:1.5rem; border-radius:20px; display:grid; grid-template-columns:120px 3fr 1fr; gap:2rem; align-items:center; <?php echo $item['status'] === 'draft' ? 'opacity: 0.7;' : ''; ?>">
                        
                        <!-- Thumbnail -->
                        <div style="width: 120px; height: 90px; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center;">
                            <?php if(!empty($item['thumbnail_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['thumbnail_image']); ?>" alt="News Image" style="width: 100%; height: 100%; object-fit: cover;">
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
                                <?php if($item['is_featured']): ?>
                                    <span style="font-size:0.7rem; background:rgba(244, 185, 66, 0.1); border:1px solid #F4B942; color:#F4B942; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Featured</span>
                                <?php endif; ?>
                                <?php if($item['is_pinned']): ?>
                                    <span style="font-size:0.7rem; background:rgba(36, 194, 122, 0.1); border:1px solid #24C27A; color:#24C27A; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Pinned</span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.85rem; opacity:0.8; margin-bottom:0.5rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                <?php echo htmlspecialchars($item['excerpt']); ?>
                            </p>
                            <div style="font-size:0.8rem; opacity:0.6; display:flex; gap:1.5rem;">
                                <span><strong>Author:</strong> <?php echo htmlspecialchars($item['author_name']); ?></span>
                                <span><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></span>
                                <span><strong>Views:</strong> <?php echo (int)$item['views']; ?></span>
                                <span><strong>Date:</strong> <?php echo $item['published_at'] ? date('M j, Y h:i A', strtotime($item['published_at'])) : 'Unpublished'; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <a href="../news-media/article.php?slug=<?php echo $item['slug']; ?>" target="_blank" class="btn" style="border:1px solid #FAF7F0; color:#FAF7F0; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center; font-size:0.85rem; text-decoration:none;">Preview</a>
                            <button onclick='openNewsModal(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)' class="btn" style="border:1px solid #24C27A; color:#24C27A; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center; font-size:0.85rem;">Edit Article</button>
                            <form action="news.php" method="POST" onsubmit="return confirm('Delete this article? (It will be soft-deleted)');" style="display:block;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_news" class="btn" style="border:1px solid #D72638; color:#D72638; padding:0.5rem; font-weight:bold; border-radius:999px; cursor:pointer; width:100%; text-align:center; font-size:0.85rem;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.2); padding:4rem; border-radius:28px; text-align:center;">
                    <p style="font-size:1.1rem; opacity:0.7;">No news articles found matching the criteria.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for News -->
<div id="news-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); z-index:9999; position:fixed; inset:0;">
    <div class="glass-card" style="background:#08142E; border:1px solid rgba(255,255,255,0.1); padding:2.5rem; border-radius:28px; max-width:800px; width:95%; position:relative; max-height: 90vh; overflow-y: auto;">
        <button onclick="closeNewsModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#FAF7F0; font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem;">Write Article</h3>
        
        <form action="news.php" method="POST" enctype="multipart/form-data" id="news-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="save_news" value="1">
            <input type="hidden" name="id" id="news-id" value="0">
            
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="news-title" style="font-size:0.8rem; font-weight:600; color:#fff;">Article Title <span style="color:#D72638">*</span></label>
                    <input type="text" id="news-title" name="title" class="form-control" required style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Enter headline...">
                </div>
                <div class="form-group">
                    <label for="news-category" style="font-size:0.8rem; font-weight:600; color:#fff;">Category <span style="color:#D72638">*</span></label>
                    <select id="news-category" name="category_id" class="form-select" required style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="news-author" style="font-size:0.8rem; font-weight:600; color:#fff;">Author Name</label>
                    <input type="text" id="news-author" name="author_name" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="e.g. BSFI Media Team" value="BSFI Official">
                </div>
                <div class="form-group">
                    <label for="news-slug" style="font-size:0.8rem; font-weight:600; color:#fff;">URL Slug</label>
                    <input type="text" id="news-slug" name="slug" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Auto-generated if empty">
                </div>
            </div>

            <div class="form-group">
                <label for="news-excerpt" style="font-size:0.8rem; font-weight:600; color:#fff;">Excerpt / Subhead</label>
                <textarea id="news-excerpt" name="excerpt" class="form-control" rows="2" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Brief summary of the article..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="news-content" style="font-size:0.8rem; font-weight:600; color:#fff;">Content (up to 5000 chars) <span style="color:#D72638">*</span></label>
                <textarea id="news-content" name="content" class="form-control" rows="8" required style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Write your full article here."></textarea>
            </div>

            <!-- Uploads Grid -->
            <div style="border:1px dashed rgba(255,255,255,0.2); padding:1rem; border-radius:12px; display:flex; flex-direction:column; gap:1rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label for="news-thumbnail" style="font-size:0.8rem; font-weight:600; color:#fff;">Thumbnail Image</label>
                        <input type="file" id="news-thumbnail" name="thumbnail_image" class="form-control" accept="image/jpeg,image/png,image/webp" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem; color:#fff;">Landscape format. Max 1MB.</p>
                    </div>
                    <div class="form-group">
                        <label for="news-cover" style="font-size:0.8rem; font-weight:600; color:#fff;">Cover / Hero Banner</label>
                        <input type="file" id="news-cover" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem; color:#fff;">Large header format. Max 1MB.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="news-extra-images" style="font-size:0.8rem; font-weight:600; color:#fff;">Upload Multiple Gallery Photos (Max 1MB each)</label>
                    <input type="file" id="news-extra-images" name="gallery_images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                    <p style="font-size:0.75rem; opacity:0.6; margin-top:0.25rem; color:#fff;">Select one or more photos to display on the article page gallery.</p>
                </div>
            </div>

            <!-- Social Links Group -->
            <div style="border:1px solid rgba(255,255,255,0.1); padding:1rem; border-radius:12px;">
                <h4 style="font-size:0.9rem; margin-bottom:1rem; color:#A0AABF;">External Related Posts (Social Links)</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">Facebook URL</label>
                        <input type="url" name="facebook_url" id="news-facebook" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="https://facebook.com/...">
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">Instagram URL</label>
                        <input type="url" name="instagram_url" id="news-instagram" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="https://instagram.com/...">
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">Twitter/X URL</label>
                        <input type="url" name="twitter_url" id="news-twitter" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="https://twitter.com/...">
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" id="news-linkedin" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="https://linkedin.com/in/...">
                    </div>
                </div>
                <div class="form-group mt-2">
                    <label style="font-size:0.75rem; color:#fff;">YouTube URL</label>
                    <input type="url" name="youtube_url" id="news-youtube" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="https://youtube.com/watch?...">
                </div>
            </div>

            <!-- SEO Settings -->
            <div style="border:1px solid rgba(255,255,255,0.1); padding:1rem; border-radius:12px;">
                <h4 style="font-size:0.9rem; margin-bottom:1rem; color:#A0AABF;">SEO Custom Headers</h4>
                <div style="display:grid; grid-template-columns:1fr; gap:1rem;">
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">Meta Title</label>
                        <input type="text" name="meta_title" id="news-meta-title" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Custom title header">
                    </div>
                    <div class="form-group">
                        <label style="font-size:0.75rem; color:#fff;">Meta Description</label>
                        <textarea name="meta_description" id="news-meta-desc" class="form-control" rows="2" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;" placeholder="Custom description header"></textarea>
                    </div>
                </div>
            </div>

            <!-- Publishing control -->
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:0.5rem; padding-top:1.5rem;">
                <h4 style="font-size:1rem; margin-bottom:1rem; color:#A0AABF;">Publishing & Scheduling</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group">
                        <label for="news-status" style="font-size:0.8rem; font-weight:600; color:#fff;">Status</label>
                        <select id="news-status" name="status" class="form-select" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                            <option value="draft">Draft (Hidden)</option>
                            <option value="published">Published (Live)</option>
                            <option value="scheduled">Scheduled (Future)</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="news-published-at" style="font-size:0.8rem; font-weight:600; color:#fff;">Publish Date/Time</label>
                        <input type="datetime-local" id="news-published-at" name="published_at" class="form-control" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:1.5rem; align-items:center; margin-top: 0.5rem;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="news-featured" name="is_featured" value="1" style="width: 18px; height: 18px;">
                    <label for="news-featured" style="font-size:0.9rem; font-weight:600; cursor:pointer; color:#F4B942;">★ Featured Article</label>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" id="news-pinned" name="is_pinned" value="1" style="width: 18px; height: 18px;">
                    <label for="news-pinned" style="font-size:0.9rem; font-weight:600; cursor:pointer; color:#fff;">📌 Pin to Top</label>
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
        document.getElementById('news-category').value = item.category_id || '';
        document.getElementById('news-excerpt').value = item.excerpt || '';
        document.getElementById('news-content').value = item.content;
        document.getElementById('news-status').value = item.status;
        
        document.getElementById('news-facebook').value = item.facebook_url || '';
        document.getElementById('news-instagram').value = item.instagram_url || '';
        document.getElementById('news-twitter').value = item.twitter_url || '';
        document.getElementById('news-linkedin').value = item.linkedin_url || '';
        document.getElementById('news-youtube').value = item.youtube_url || '';
        
        document.getElementById('news-meta-title').value = item.meta_title || '';
        document.getElementById('news-meta-desc').value = item.meta_description || '';

        if(item.published_at) {
            document.getElementById('news-published-at').value = item.published_at.substring(0, 16).replace(' ', 'T');
        } else {
            document.getElementById('news-published-at').value = '';
        }
        document.getElementById('news-featured').checked = item.is_featured == 1;
        document.getElementById('news-pinned').checked = item.is_pinned == 1;
    }
    
    modal.style.display = 'flex';
}

function closeNewsModal() {
    document.getElementById('news-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
