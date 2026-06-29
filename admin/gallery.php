<?php
// admin/gallery.php – Full Gallery Management: Category -> Album -> Photo hierarchy
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

// Self-healing: Ensure primary key auto-increment is enabled on the live database
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("ALTER TABLE gallery_images MODIFY id INT NOT NULL AUTO_INCREMENT;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
} catch (PDOException $e) {
    // Fail silently if already auto-incremented
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title    = "Manage Gallery – BSFI Admin";
$uploadBase    = __DIR__ . '/../uploads/gallery/';
$GALLERY_CACHE = __DIR__ . '/../cache/gallery_homepage.json';
$SYNC_FOLDER   = __DIR__ . '/../BSFI_Website_Revamp_Assets/02_Page_Content/06_News_Media (1)/DROP_GALLERY_PHOTOS_HERE (1)/';
$ALLOWED_EXT   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$userId        = (int)($_SESSION['user_id'] ?? 0);

/* ═══════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════ */

/** Rebuild the homepage gallery JSON cache (future proof fallback) */
function rebuildGalleryCache(PDO $pdo, string $cachePath): void {
    try {
        $cats = $pdo->query("SELECT * FROM gallery_categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
        $albums = $pdo->query("
            SELECT ga.*, gc.slug AS category_slug, gc.name AS category_name,
                   (SELECT COUNT(*) FROM gallery_images WHERE album_id = ga.id AND status = 'published' AND is_deleted = 0) AS image_count,
                   gi.image_path AS cover_image_path
            FROM gallery_albums ga
            LEFT JOIN gallery_categories gc ON ga.category_id = gc.id
            LEFT JOIN gallery_images gi ON ga.cover_image_id = gi.id
            WHERE ga.is_published = 1 AND gc.is_active = 1
            ORDER BY ga.event_date DESC, ga.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $imgs = $pdo->query("
            SELECT gi.*, ga.slug AS album_slug, ga.title AS album_title
            FROM gallery_images gi
            LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
            WHERE gi.status = 'published' AND gi.is_deleted = 0
            ORDER BY gi.sort_order ASC, gi.id DESC
            LIMIT 500
        ")->fetchAll(PDO::FETCH_ASSOC);

        file_put_contents($cachePath, json_encode([
            'categories' => $cats,
            'albums'     => $albums,
            'images'     => $imgs,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } catch (PDOException $e) { /* fail silently */ }
}

/** Strip EXIF and create thumbnail/medium/full versions */
function processImageVersions(string $srcPath, string $destDir, string $baseName): array {
    $paths = ['thumb' => '', 'medium' => '', 'full' => ''];
    if (!function_exists('imagecreatefromjpeg')) return $paths;

    $ext  = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    $img  = null;

    if (in_array($ext, ['jpg', 'jpeg'])) {
        $img = @imagecreatefromjpeg($srcPath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($srcPath);
    } elseif ($ext === 'webp') {
        $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : null;
    }
    if (!$img) return $paths;

    $origW = imagesx($img);
    $origH = imagesy($img);
    $sizes = ['thumb'  => 400, 'medium' => 800, 'full'   => 1920];

    // Auto-convert to webp if supported by GD
    $useWebp = function_exists('imagewebp');
    $outBaseName = $useWebp ? (pathinfo($baseName, PATHINFO_FILENAME) . '.webp') : $baseName;

    foreach ($sizes as $label => $maxW) {
        $subDir = $destDir . $label . '/';
        if (!is_dir($subDir)) mkdir($subDir, 0775, true);

        $ratio  = min(1, $maxW / $origW);
        $newW   = (int)round($origW * $ratio);
        $newH   = (int)round($origH * $ratio);
        $canvas = imagecreatetruecolor($newW, $newH);

        // Retain alpha transparency for PNGs converting to WebP
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $outFile = $subDir . $outBaseName;
        if ($useWebp) {
            imagewebp($canvas, $outFile, 82);
        } else {
            if (in_array($ext, ['jpg', 'jpeg'])) imagejpeg($canvas, $outFile, 85);
            elseif ($ext === 'png') imagepng($canvas, $outFile, 6);
            elseif ($ext === 'webp' && function_exists('imagewebp')) imagewebp($canvas, $outFile, 82);
        }

        imagedestroy($canvas);
        $paths[$label] = $outFile;
    }
    imagedestroy($img);

    // Clean up original non-webp uploads to save storage space
    if ($useWebp && $ext !== 'webp' && file_exists($srcPath)) {
        @unlink($srcPath);
    }

    return $paths;
}

function toWebPath(string $absPath, string $docRoot): string {
    $rel = str_replace('\\', '/', str_replace(str_replace('\\','/',$docRoot), '', str_replace('\\','/',$absPath)));
    return ltrim($rel, '/');
}

$docRoot = str_replace('\\','/', __DIR__ . '/../');

/* ═══════════════════════════════════════════════════════
   AJAX RESPONSES
═══════════════════════════════════════════════════════ */
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']); exit;
    }

    $action = $_POST['action'] ?? '';
    $ids    = [];
    if (isset($_POST['ids']) && $_POST['ids'] !== '') {
        $ids = array_map('intval', explode(',', $_POST['ids']));
    }

    if (empty($ids) && !in_array($action, ['sync_folder', 'purge_bin'])) {
        echo json_encode(['ok' => false, 'error' => 'No items selected']); exit;
    }

    $ph = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'soft_delete':
            $pdo->prepare("UPDATE gallery_images SET is_deleted=1, deleted_by=?, deleted_at=NOW() WHERE id IN ($ph) AND is_deleted=0")
                ->execute(array_merge([$userId], $ids));
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true, 'count' => count($ids)]); break;

        case 'restore':
            $pdo->prepare("UPDATE gallery_images SET is_deleted=0, deleted_by=NULL, deleted_at=NULL WHERE id IN ($ph)")
                ->execute($ids);
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true]); break;

        case 'hard_delete':
            $rows = $pdo->prepare("SELECT image_path, thumbnail_path, medium_path, full_path FROM gallery_images WHERE id IN ($ph)");
            $rows->execute($ids);
            foreach ($rows->fetchAll() as $r) {
                foreach (['image_path','thumbnail_path','medium_path','full_path'] as $col) {
                    if (!empty($r[$col]) && file_exists(__DIR__ . '/../' . $r[$col])) @unlink(__DIR__ . '/../' . $r[$col]);
                }
            }
            $pdo->prepare("DELETE FROM gallery_images WHERE id IN ($ph)")->execute($ids);
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true]); break;

        case 'feature':
            $val = (int)($_POST['value'] ?? 1);
            $pdo->prepare("UPDATE gallery_images SET is_featured=$val WHERE id IN ($ph)")->execute($ids);
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true]); break;

        case 'hero':
            $val = (int)($_POST['value'] ?? 1);
            $pdo->prepare("UPDATE gallery_images SET show_in_hero=$val WHERE id IN ($ph)")->execute($ids);
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true]); break;

        case 'assign_album':
            $albumId = (int)($_POST['album_id'] ?? 0);
            $pdo->prepare("UPDATE gallery_images SET album_id=? WHERE id IN ($ph)")
                ->execute(array_merge([$albumId ?: null], $ids));
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true]); break;

        case 'purge_bin':
            $old = $pdo->query("SELECT id, image_path, thumbnail_path, medium_path, full_path FROM gallery_images WHERE is_deleted=1 AND deleted_at < NOW() - INTERVAL 90 DAY")->fetchAll();
            if ($old) {
                $oIds = array_column($old,'id');
                $oPh  = implode(',', array_fill(0, count($oIds), '?'));
                foreach ($old as $r) {
                    foreach (['image_path','thumbnail_path','medium_path','full_path'] as $col) {
                        if (!empty($r[$col]) && file_exists(__DIR__.'/../'.$r[$col])) @unlink(__DIR__.'/../'.$r[$col]);
                    }
                }
                $pdo->prepare("DELETE FROM gallery_images WHERE id IN ($oPh)")->execute($oIds);
            }
            echo json_encode(['ok' => true, 'purged' => count($old)]); break;

        case 'sync_folder':
            $targetAlbumId = (int)($_POST['album_id'] ?? 0);
            if (!$targetAlbumId) {
                echo json_encode(['ok' => false, 'error' => 'You must select a target Album for sync.']); exit;
            }
            if (!is_dir($SYNC_FOLDER)) {
                echo json_encode(['ok' => false, 'error' => 'Sync folder not found: ' . $SYNC_FOLDER]); exit;
            }
            $files   = new DirectoryIterator($SYNC_FOLDER);
            $added   = 0;
            $skipped = 0;
            $ymd     = date('Y/m');
            $destDir = $uploadBase . $ymd . '/';
            if (!is_dir($destDir)) mkdir($destDir, 0775, true);

            foreach ($files as $f) {
                if ($f->isDot() || $f->isDir()) continue;
                $ext = strtolower($f->getExtension());
                if (!in_array($ext, $ALLOWED_EXT)) continue;

                $hash = hash_file('sha256', $f->getPathname());
                $exists = $pdo->prepare("SELECT id FROM gallery_images WHERE file_hash=? LIMIT 1");
                $exists->execute([$hash]);
                if ($exists->fetch()) { $skipped++; continue; }

                $baseName = $hash . '.' . $ext;
                $destFull = $destDir . 'full/' . $baseName;
                if (!is_dir($destDir . 'full/')) mkdir($destDir . 'full/', 0775, true);
                copy($f->getPathname(), $destFull);

                $versions = processImageVersions($f->getPathname(), $destDir, $baseName);
                $relFull  = toWebPath($versions['full']  ?: $destFull, $docRoot);
                $relThumb = toWebPath($versions['thumb'] ?: $destFull, $docRoot);
                $relMed   = toWebPath($versions['medium']?: $destFull, $docRoot);

                $pdo->prepare("INSERT INTO gallery_images (file_hash, image_path, thumbnail_path, medium_path, full_path, caption, album_id, status, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$hash, $relFull, $relThumb, $relMed, $relFull,
                               pathinfo($f->getFilename(), PATHINFO_FILENAME), $targetAlbumId, 'published', $userId]);
                $added++;
            }
            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true, 'added' => $added, 'skipped' => $skipped]); break;

        case 'finalize_migration':
            try {
                $pdo->exec("DROP TABLE IF EXISTS `gallery_albums_old`");
                echo json_encode(['ok' => true]);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════
   FORM POST HANDLERS (Album & Photo Save)
═══════════════════════════════════════════════════════ */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        // --- SAVE ALBUM ---
        if (isset($_POST['save_album'])) {
            $id            = (int)($_POST['id'] ?? 0);
            $catId         = (int)($_POST['category_id'] ?? 0);
            $title         = trim($_POST['title'] ?? '');
            $slug          = trim($_POST['slug'] ?? '') ?: strtolower(str_replace(' ', '-', $title));
            $desc          = trim($_POST['description'] ?? '');
            $location      = trim($_POST['event_location'] ?? '');
            $date          = trim($_POST['event_date'] ?? '') ?: null;
            $type          = in_array($_POST['album_type'] ?? '', ['event','training','camp','ceremony','media','general']) ? $_POST['album_type'] : 'event';
            $coverOverride = trim($_POST['cover_image_override'] ?? '') ?: null;
            $isPublished   = isset($_POST['is_published']) ? 1 : 0;
            $isFeatured    = isset($_POST['is_featured']) ? 1 : 0;

            if (empty($title) || !$catId) {
                $message = "<div class='alert alert-danger'>Title and Category are required.</div>";
            } else {
                if ($id > 0) {
                    $pdo->prepare("UPDATE gallery_albums SET category_id=?, title=?, slug=?, description=?, event_date=?, event_location=?, album_type=?, cover_image_override=?, is_published=?, is_featured=? WHERE id=?")
                        ->execute([$catId, $title, $slug, $desc, $date, $location, $type, $coverOverride, $isPublished, $isFeatured, $id]);
                    $message = "<div class='alert alert-success'>Album updated successfully.</div>";
                } else {
                    $pdo->prepare("INSERT INTO gallery_albums (category_id, title, slug, description, event_date, event_location, album_type, cover_image_override, is_published, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$catId, $title, $slug, $desc, $date, $location, $type, $coverOverride, $isPublished, $isFeatured]);
                    $message = "<div class='alert alert-success'>Album created successfully.</div>";
                }
                rebuildGalleryCache($pdo, $GALLERY_CACHE);
            }
        }

        // --- SAVE PHOTO ---
        if (isset($_POST['save_image'])) {
            $id        = (int)($_POST['id'] ?? 0);
            $caption   = trim($_POST['caption'] ?? '');
            $altText   = trim($_POST['alt_text'] ?? '');
            $credit    = trim($_POST['credit'] ?? '');
            $albumId   = (int)($_POST['album_id'] ?? 0) ?: null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $featured  = isset($_POST['is_featured']) ? 1 : 0;
            $hero      = isset($_POST['show_in_hero']) ? 1 : 0;
            $status    = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'published';
            $newPaths  = [];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $ALLOWED_EXT)) {
                    $message = "<div class='alert alert-danger'>Unsupported file type.</div>";
                } else {
                    $hash    = hash_file('sha256', $_FILES['image']['tmp_name']);
                    $ymd     = date('Y/m');
                    $destDir = $uploadBase . $ymd . '/';
                    if (!is_dir($destDir)) mkdir($destDir, 0775, true);
                    $baseName = $hash . '.' . $ext;

                    $srcTmp = $_FILES['image']['tmp_name'];
                    $fullDir = $destDir . 'full/';
                    if (!is_dir($fullDir)) mkdir($fullDir, 0775, true);
                    move_uploaded_file($srcTmp, $fullDir . $baseName);

                    $versions = processImageVersions($fullDir . $baseName, $destDir, $baseName);
                    $newPaths = [
                        'image_path'     => toWebPath($versions['full']  ?: $fullDir . $baseName, $docRoot),
                        'thumbnail_path' => toWebPath($versions['thumb'] ?: $fullDir . $baseName, $docRoot),
                        'medium_path'    => toWebPath($versions['medium']?: $fullDir . $baseName, $docRoot),
                        'full_path'      => toWebPath($versions['full']  ?: $fullDir . $baseName, $docRoot),
                        'file_hash'      => $hash,
                    ];
                }
            }

            if ($message === '') {
                if ($id > 0) {
                    if ($newPaths) {
                        $pdo->prepare("UPDATE gallery_images SET caption=?,alt_text=?,credit=?,album_id=?,sort_order=?,is_featured=?,show_in_hero=?,status=?,image_path=?,thumbnail_path=?,medium_path=?,full_path=?,file_hash=? WHERE id=?")
                            ->execute([$caption,$altText,$credit,$albumId,$sortOrder,$featured,$hero,$status,
                                       $newPaths['image_path'],$newPaths['thumbnail_path'],$newPaths['medium_path'],$newPaths['full_path'],$newPaths['file_hash'],$id]);
                    } else {
                        $pdo->prepare("UPDATE gallery_images SET caption=?,alt_text=?,credit=?,album_id=?,sort_order=?,is_featured=?,show_in_hero=?,status=? WHERE id=?")
                            ->execute([$caption,$altText,$credit,$albumId,$sortOrder,$featured,$hero,$status,$id]);
                    }
                    $message = "<div class='alert alert-success'>Photo updated successfully.</div>";
                } else {
                    if (empty($newPaths)) {
                        $message = "<div class='alert alert-danger'>You must upload an image for new entries.</div>";
                    } else {
                        $pdo->prepare("INSERT INTO gallery_images (caption,alt_text,credit,album_id,sort_order,is_featured,show_in_hero,status,image_path,thumbnail_path,medium_path,full_path,file_hash,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                            ->execute([$caption,$altText,$credit,$albumId,$sortOrder,$featured,$hero,$status,
                                       $newPaths['image_path'],$newPaths['thumbnail_path'],$newPaths['medium_path'],$newPaths['full_path'],$newPaths['file_hash'],$userId]);
                        $message = "<div class='alert alert-success'>Photo uploaded successfully.</div>";
                    }
                }
                rebuildGalleryCache($pdo, $GALLERY_CACHE);
            }
        }

        // --- SET AS ALBUM COVER ---
        if (isset($_POST['set_album_cover'])) {
            $albId = (int)$_POST['album_id'];
            $imgId = (int)$_POST['image_id'];
            if ($albId > 0 && $imgId > 0) {
                $pdo->prepare("UPDATE gallery_albums SET cover_image_id = ? WHERE id = ?")->execute([$imgId, $albId]);
                $message = "<div class='alert alert-success'>Album cover image set successfully.</div>";
                rebuildGalleryCache($pdo, $GALLERY_CACHE);
            }
        }
    }
}

/* ═══════════════════════════════════════════════════════
   DATA FETCH & TABS ROUTING
═══════════════════════════════════════════════════════ */
$view = $_GET['view'] ?? 'albums'; // 'albums' | 'photos' | 'bulk' | 'bin' | 'settings'
$filterAlbum = (int)($_GET['album'] ?? 0);

// 1. Fetch Categories
$categories = $pdo->query("SELECT * FROM gallery_categories ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Albums (with category detail & count)
$albumsList = $pdo->query("
    SELECT ga.*, gc.name AS category_name,
           (SELECT COUNT(*) FROM gallery_images WHERE album_id = ga.id AND is_deleted = 0) AS image_count,
           gi.thumbnail_path AS cover_thumb
    FROM gallery_albums ga
    LEFT JOIN gallery_categories gc ON ga.category_id = gc.id
    LEFT JOIN gallery_images gi ON ga.cover_image_id = gi.id
    ORDER BY ga.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Photos list
$baseWhere = $view === 'bin' ? "gi.is_deleted = 1" : "gi.is_deleted = 0";
if ($filterAlbum) $baseWhere .= " AND gi.album_id = $filterAlbum";

$photosList = $pdo->query("
    SELECT gi.*, ga.title AS album_title
    FROM gallery_images gi
    LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
    WHERE $baseWhere
    ORDER BY gi.sort_order ASC, gi.id DESC
    LIMIT 300
")->fetchAll(PDO::FETCH_ASSOC);

// 4. CMS Dashboard Metrics
$mTotalAlbums = count($albumsList);
$mTotalPhotos = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_deleted=0")->fetchColumn();
$mFeaturedAlbums = (int)$pdo->query("SELECT COUNT(*) FROM gallery_albums WHERE is_featured=1")->fetchColumn();
$mUnpublishedAlbums = (int)$pdo->query("SELECT COUNT(*) FROM gallery_albums WHERE is_published=0")->fetchColumn();
$mBinCount = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_deleted=1")->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<style>
/* -- Revamped CMS Style rules -- */
.gadm-wrap    { background: var(--admin-bg); min-height:95vh; padding: 2rem 0; color: var(--text-primary); }
.gadm-topbar  { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;
                border-bottom:1px solid var(--card-border); padding-bottom:1.5rem; margin-bottom:2rem; }
.gadm-topbar h1 { font-family:'Outfit',sans-serif; font-size:2rem; font-weight:700; color: var(--navy); margin:0; }
.gadm-eyebrow { color: var(--bsfi-green); text-transform:uppercase; letter-spacing:.07em; font-size:.8rem; font-weight:600; }
.gadm-btn     { padding:.55rem 1.25rem; border-radius:999px; font-size:.85rem; font-weight:600;
                border:1px solid transparent; cursor:pointer; transition:all .2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; }
.gadm-btn-pri { background: var(--bsfi-green); color:#fff !important; }
.gadm-btn-pri:hover { background: #0f6c06; text-decoration: none; }
.gadm-btn-sec { background: transparent; color: var(--text-secondary) !important; border:1px solid #CBD5E1; }
.gadm-btn-sec:hover { background: #F8FAFC; border-color: #94A3B8; text-decoration: none; }
.gadm-btn-danger { background: var(--danger); color:#fff !important; }
.gadm-btn-danger:hover { background: #bd1d1d; text-decoration: none; }
.gadm-btn-warn  { background: var(--bsfi-saffron); color:#fff !important; }
.gadm-btn-warn:hover { background: #e5821e; text-decoration: none; }

/* Dashboard Cards row */
.gadm-metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.gadm-metric-card { background: #ffffff; border: 1px solid var(--card-border); border-radius: 16px; padding: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.01); }
.gadm-metric-num  { font-size: 1.75rem; font-weight: 800; color: var(--navy); line-height: 1.2; }
.gadm-metric-lbl  { font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.25rem; text-transform: uppercase; letter-spacing: 0.03em; }

/* Navigation Tabs */
.gadm-tabs    { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:2rem; border-bottom: 2px solid var(--card-border); padding-bottom: 0.5rem; }
.gadm-tab     { padding:.5rem 1.35rem; border-radius:999px; font-size:.85rem; font-weight:600; cursor:pointer;
                border:1px solid transparent; color: var(--text-secondary);
                background: transparent; text-decoration:none; transition:all .2s; }
.gadm-tab.active, .gadm-tab:hover { background: var(--bsfi-saffron); color:#FFFFFF !important; text-decoration: none; }

/* Tables styling */
.gadm-table-wrap { background: #ffffff; border: 1px solid var(--card-border); border-radius: 18px; overflow: hidden; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.01); }
.gadm-table { width: 100%; border-collapse: collapse; text-align: left; }
.gadm-table th { background: #F8FAFC; padding: 1rem 1.25rem; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; border-bottom: 1px solid var(--card-border); }
.gadm-table td { padding: 1rem 1.25rem; font-size: 0.88rem; border-bottom: 1px solid var(--card-border); vertical-align: middle; }
.gadm-table tr:last-child td { border-bottom: none; }
.gadm-table-thumb { width: 60px; aspect-ratio: 16/10; object-fit: cover; border-radius: 8px; border: 1px solid var(--card-border); }

/* Grid views */
.gadm-grid    { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1.25rem; }
.gadm-card    { background: #FFFFFF; border-radius:18px; overflow:hidden;
                border:1px solid var(--card-border); position:relative;
                box-shadow: 0 4px 15px rgba(0,0,0,0.01);
                transition:border-color .2s, box-shadow .2s, transform .2s; }
.gadm-card:hover { border-color: var(--bsfi-saffron); box-shadow:0 8px 24px rgba(0,0,0,.06); transform: translateY(-4px); }
.gadm-card.selected { border-color: var(--bsfi-saffron); box-shadow:0 0 0 2px var(--bsfi-saffron); }
.gadm-card-thumb { width:100%; aspect-ratio:4/3; object-fit:cover; display:block; background:#F1F5F9; }
.gadm-card-body  { padding:1rem; }
.gadm-card-caption { font-size:.88rem; font-weight:600; color: var(--navy); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:.3rem; }
.gadm-card-meta  { font-size:.72rem; color: var(--text-secondary); }
.gadm-card-badges { display:flex; gap:.3rem; flex-wrap:wrap; margin-top:.5rem; }
.gadm-badge      { font-size:.65rem; font-weight:700; padding:.18rem .55rem; border-radius:4px; text-transform:uppercase; }
.badg-feat       { background: var(--bsfi-saffron); color:#FFFFFF; }
.badg-hero       { background: #2563EB; color:#FFFFFF; }
.badg-hidden     { background: var(--danger); color:#fff; }
.badg-draft      { background: #64748B; color:#fff; }
.gadm-card-footer { display:flex; justify-content:space-between; align-items:center; padding:.6rem 1rem;
                    border-top:1px solid var(--card-border); gap:.4rem; }
.gadm-check-wrap { position:absolute; top:10px; left:10px; z-index:3; }
.gadm-check      { width:18px; height:18px; accent-color: var(--bsfi-saffron); cursor:pointer; }

/* Modals */
.gadm-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index:9990;
                        align-items:center; justify-content:center; }
.gadm-modal-backdrop.open { display:flex; }
.gadm-modal-box { background:#FFFFFF; border:1px solid var(--card-border); border-radius:24px;
                  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
                  padding:2rem; max-width:580px; width:95%; max-height:90vh; overflow-y:auto; position:relative; }
.gadm-modal-close { position:absolute; top:16px; right:16px; background:none; border:none; color: var(--text-secondary);
                    font-size:1.4rem; cursor:pointer; }
.gadm-form-label  { font-size:.78rem; font-weight:600; color: var(--text-secondary); margin-bottom:.3rem; display:block; }
.gadm-form-input  { width:100%; background:#FFFFFF; border:1px solid #CBD5E1; color: var(--text-primary);
                    padding:.55rem .85rem; border-radius:10px; font-size:.88rem; box-sizing:border-box; }
.gadm-form-input:focus { outline:none; border-color: var(--bsfi-green); box-shadow: 0 0 0 4px rgba(19,136,8,0.12); }
.gadm-form-row    { margin-bottom:1rem; }
.gadm-form-2col   { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
</style>

<div class="gadm-wrap">
<div class="container">

    <!-- Top bar -->
    <div class="gadm-topbar">
        <div>
            <div class="gadm-eyebrow">Content Management</div>
            <h1>Photo Gallery Center</h1>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
            <button class="gadm-btn gadm-btn-pri" onclick="openAlbumModal(0)">+ Create Album</button>
            <button class="gadm-btn gadm-btn-pri" onclick="openPhotoModal(0)">Upload Photo</button>
            <a href="dashboard.php" class="gadm-btn gadm-btn-sec">Dashboard</a>
        </div>
    </div>

    <!-- Metrics Dashboard Row -->
    <div class="gadm-metrics-row">
        <div class="gadm-metric-card">
            <div class="gadm-metric-num"><?php echo $mTotalAlbums; ?></div>
            <div class="gadm-metric-lbl">Total Albums</div>
        </div>
        <div class="gadm-metric-card">
            <div class="gadm-metric-num"><?php echo $mTotalPhotos; ?></div>
            <div class="gadm-metric-lbl">Total Photos</div>
        </div>
        <div class="gadm-metric-card">
            <div class="gadm-metric-num"><?php echo $mFeaturedAlbums; ?></div>
            <div class="gadm-metric-lbl">Featured Albums</div>
        </div>
        <div class="gadm-metric-card">
            <div class="gadm-metric-num"><?php echo $mUnpublishedAlbums; ?></div>
            <div class="gadm-metric-lbl">Unpublished</div>
        </div>
        <div class="gadm-metric-card">
            <div class="gadm-metric-num"><?php echo $mBinCount; ?></div>
            <div class="gadm-metric-lbl">Recycle Bin</div>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Navigation Tabs -->
    <div class="gadm-tabs">
        <a href="gallery.php?view=albums" class="gadm-tab <?php echo $view==='albums'?'active':''; ?>">Albums (<?php echo $mTotalAlbums; ?>)</a>
        <a href="gallery.php?view=photos" class="gadm-tab <?php echo $view==='photos'?'active':''; ?>">Photos (<?php echo $mTotalPhotos; ?>)</a>
        <a href="gallery.php?view=bulk" class="gadm-tab <?php echo $view==='bulk'?'active':''; ?>">Bulk Actions</a>
        <a href="gallery.php?view=bin" class="gadm-tab <?php echo $view==='bin'?'active':''; ?>">Recycle Bin (<?php echo $mBinCount; ?>)</a>
        <a href="gallery.php?view=settings" class="gadm-tab <?php echo $view==='settings'?'active':''; ?>">Settings</a>
    </div>

    <!-- ═══════════════════════════════════════════════════════
       TAB 1: ALBUMS VIEW
    ═══════════════════════════════════════════════════════ -->
    <?php if ($view === 'albums'): ?>
    <div class="gadm-table-wrap">
        <table class="gadm-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Album Info</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Photos</th>
                    <th>Date / Location</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($albumsList as $alb): 
                    $cover = !empty($alb['cover_image_override']) ? htmlspecialchars($alb['cover_image_override']) : (!empty($alb['cover_thumb']) ? '../' . htmlspecialchars($alb['cover_thumb']) : 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1 1%22><rect fill=%22%23081b4b%22/></svg>');
                ?>
                <tr>
                    <td><img src="<?php echo $cover; ?>" class="gadm-table-thumb" alt=""></td>
                    <td>
                        <div style="font-weight: 700; color: var(--navy);"><?php echo htmlspecialchars($alb['title']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); max-width: 250px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                            <?php echo htmlspecialchars($alb['description'] ?: '(no description)'); ?>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark px-2.5 py-1 rounded"><?php echo htmlspecialchars($alb['category_name'] ?: 'None'); ?></span></td>
                    <td><span class="badge bg-secondary text-white text-uppercase px-2.5" style="font-size:0.7rem;"><?php echo htmlspecialchars($alb['album_type']); ?></span></td>
                    <td><strong><?php echo (int)$alb['image_count']; ?></strong></td>
                    <td>
                        <div style="font-size:0.8rem;"><?php echo $alb['event_date'] ? date('d M Y', strtotime($alb['event_date'])) : '—'; ?></div>
                        <div style="font-size:0.75rem; color:var(--text-secondary);"><?php echo htmlspecialchars($alb['event_location'] ?: '—'); ?></div>
                    </td>
                    <td>
                        <?php if ($alb['is_featured']): ?><span class="gadm-badge badg-feat" style="margin-right: 0.25rem;">Featured</span><?php endif; ?>
                        <?php if ($alb['is_published']): ?>
                            <span class="gadm-badge bg-success text-white">Published</span>
                        <?php else: ?>
                            <span class="gadm-badge bg-warning text-dark">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <button class="gadm-btn gadm-btn-sec" style="padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick='openAlbumModal(<?php echo json_encode($alb); ?>)'>Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══════════════════════════════════════════════════════
       TAB 2: PHOTOS LIST & ALBUMS FILTERING
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($view === 'photos'): ?>
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; background: #fff; padding: 1rem; border-radius: 14px; border: 1px solid var(--card-border);">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary);">Browse Album:</label>
        <select onchange="location.href='gallery.php?view=photos&album=' + this.value" class="gadm-form-input" style="max-width: 300px; padding: 0.4rem 0.8rem; height: auto;">
            <option value="0">All Photos</option>
            <?php foreach ($albumsList as $alb): ?>
                <option value="<?php echo $alb['id']; ?>" <?php echo $filterAlbum === (int)$alb['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($alb['title']); ?> (<?php echo (int)$alb['image_count']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (count($photosList) > 0): ?>
    <div class="gadm-grid">
        <?php foreach ($photosList as $item): ?>
        <div class="gadm-card">
            <img class="gadm-card-thumb" src="../<?php echo htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']); ?>" alt="">
            <div class="gadm-card-body">
                <div class="gadm-card-caption"><?php echo htmlspecialchars($item['caption'] ?: '(no caption)'); ?></div>
                <div class="gadm-card-meta">
                    <?php echo htmlspecialchars($item['album_title'] ?? 'No album'); ?>
                </div>
                <div class="gadm-card-badges">
                    <?php if ($item['is_featured']): ?><span class="gadm-badge badg-feat">Featured</span><?php endif; ?>
                    <?php if ($item['show_in_hero']): ?><span class="gadm-badge badg-hero">Hero</span><?php endif; ?>
                    <?php if ($item['status'] !== 'published'): ?><span class="gadm-badge badg-draft"><?php echo htmlspecialchars($item['status']); ?></span><?php endif; ?>
                </div>
            </div>
            <div class="gadm-card-footer">
                <span style="font-size:.7rem;opacity:.4;">#<?php echo $item['id']; ?></span>
                <div style="display:flex;gap:.35rem;">
                    <?php if ($item['album_id']): ?>
                        <form method="POST" action="gallery.php?view=photos" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="set_album_cover" value="1">
                            <input type="hidden" name="album_id" value="<?php echo $item['album_id']; ?>">
                            <input type="hidden" name="image_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="gadm-btn gadm-btn-sec" style="padding:.3rem .6rem;font-size:.72rem;" title="Set as Album Cover">Cover</button>
                        </form>
                    <?php endif; ?>
                    <button class="gadm-btn gadm-btn-sec" style="padding:.3rem .6rem;font-size:.78rem;" onclick='openPhotoModal(<?php echo json_encode(array_map("strval",$item)); ?>)'>Edit</button>
                    <button class="gadm-btn gadm-btn-danger" style="padding:.3rem .6rem;font-size:.78rem;" onclick="softDeleteOne(<?php echo $item['id']; ?>)">Delete</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div style="background:#fff;border-radius:18px;padding:4rem;text-align:center;border:1px solid var(--card-border);">
            <p style="opacity:.6;font-size:1rem;margin:0;">No photos in this view selection.</p>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════
       TAB 3: BULK ACTIONS MANAGER
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($view === 'bulk'): ?>
    <div class="gadm-toolbar" id="bulkToolbar" style="background:#fff;">
        <button class="gadm-btn gadm-btn-sec" onclick="selectAll()">Select All</button>
        <button class="gadm-btn gadm-btn-sec" onclick="selectNone()">Deselect</button>
        <span id="selCount">0 selected</span>

        <button class="gadm-btn gadm-btn-danger" onclick="bulkAction('soft_delete')">Move to Bin</button>
        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('feature',{value:1})">Featured</button>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAction('feature',{value:0})">Unfeature</button>
        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('hero',{value:1})">Show in Hero</button>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAction('hero',{value:0})">Remove Hero</button>
        
        <select id="bulkAlbum" style="min-width:150px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:0.35rem 0.75rem; font-size:0.82rem;">
            <option value="">— Assign Album —</option>
            <?php foreach ($albumsList as $alb): ?>
            <option value="<?php echo $alb['id']; ?>"><?php echo htmlspecialchars($alb['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAssignAlbum()">Apply Album</button>
    </div>

    <div class="gadm-grid">
        <?php foreach ($photosList as $item): ?>
        <div class="gadm-card" id="card-<?php echo $item['id']; ?>">
            <div class="gadm-check-wrap">
                <input type="checkbox" class="gadm-check item-check" value="<?php echo $item['id']; ?>" onchange="updateSelCount()">
            </div>
            <img class="gadm-card-thumb" src="../<?php echo htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']); ?>" alt="">
            <div class="gadm-card-body">
                <div class="gadm-card-caption"><?php echo htmlspecialchars($item['caption'] ?: '(no caption)'); ?></div>
                <div class="gadm-card-meta"><?php echo htmlspecialchars($item['album_title'] ?? 'No Album'); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════
       TAB 4: RECYCLE BIN
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($view === 'bin'): ?>
    <div class="gadm-toolbar" id="bulkToolbar" style="background:#fff;">
        <button class="gadm-btn gadm-btn-sec" onclick="selectAll()">Select All</button>
        <button class="gadm-btn gadm-btn-sec" onclick="selectNone()">Deselect</button>
        <span id="selCount">0 selected</span>

        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('restore')">Restore</button>
        <button class="gadm-btn gadm-btn-danger" onclick="bulkAction('hard_delete')">Delete Permanently</button>
        <button class="gadm-btn gadm-btn-danger" onclick="purgeBin()" style="margin-left:auto;">Purge 90d+ Expired</button>
    </div>

    <?php if (count($photosList) > 0): ?>
    <div class="gadm-grid">
        <?php foreach ($photosList as $item): ?>
        <div class="gadm-card" id="card-<?php echo $item['id']; ?>">
            <div class="gadm-check-wrap">
                <input type="checkbox" class="gadm-check item-check" value="<?php echo $item['id']; ?>" onchange="updateSelCount()">
            </div>
            <img class="gadm-card-thumb" src="../<?php echo htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']); ?>" alt="">
            <div class="gadm-card-body">
                <div class="gadm-card-caption"><?php echo htmlspecialchars($item['caption'] ?: '(no caption)'); ?></div>
                <div class="gadm-card-meta">Deleted: <?php echo date('d M Y', strtotime($item['deleted_at'])); ?></div>
            </div>
            <div class="gadm-card-footer">
                <span style="font-size:.7rem;opacity:.4;">#<?php echo $item['id']; ?></span>
                <div style="display:flex;gap:.4rem;">
                    <button class="gadm-btn gadm-btn-warn" style="padding:.3rem .75rem;font-size:.78rem;" onclick="restoreOne(<?php echo $item['id']; ?>)">Restore</button>
                    <button class="gadm-btn gadm-btn-danger" style="padding:.3rem .75rem;font-size:.78rem;" onclick="hardDeleteOne(<?php echo $item['id']; ?>)">Delete</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div style="background:#fff;border-radius:18px;padding:4rem;text-align:center;border:1px solid var(--card-border);">
            <p style="opacity:.6;font-size:1rem;margin:0;">Recycle bin is empty.</p>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════
       TAB 5: SETTINGS & FOLDER SYNC
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($view === 'settings'): ?>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Folder Sync card -->
        <div style="background:#fff; border:1px solid var(--card-border); border-radius:18px; padding:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.01);">
            <h4 style="font-family:'Outfit',sans-serif; font-weight:700; margin-bottom:1rem;">Bulk Folder Sync</h4>
            <p style="font-size:0.85rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.5rem;">
                Scan the server directory: <code>DROP_GALLERY_PHOTOS_HERE</code>.<br>
                Images are auto-deduplicated, EXIF tags stripped, and layout thumbnails auto-generated.
            </p>
            
            <div class="gadm-form-row">
                <label class="gadm-form-label">Target Album for Synced Photos</label>
                <select id="syncAlbumId" class="gadm-form-input" style="margin-bottom:1.5rem;">
                    <option value="">— Select Target Album —</option>
                    <?php foreach ($albumsList as $alb): ?>
                        <option value="<?php echo $alb['id']; ?>"><?php echo htmlspecialchars($alb['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button class="gadm-btn gadm-btn-pri" style="width:100%;" id="syncRunBtn" onclick="runSync()">▶ Start Sync Process</button>
            <ul id="syncLog"></ul>
        </div>

        <!-- Categories and order settings info -->
        <div style="background:#fff; border:1px solid var(--card-border); border-radius:18px; padding:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.01);">
            <h4 style="font-family:'Outfit',sans-serif; font-weight:700; margin-bottom:1rem;">Gallery Categories Status</h4>
            <p style="font-size:0.85rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.5rem;">
                These default core categories organize your event albums.
            </p>
            <div class="gadm-table-wrap" style="border-radius:12px;">
                <table class="gadm-table" style="font-size:0.85rem;">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['icon'] ?: ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                            <td><?php echo (int)$cat['display_order']; ?></td>
                            <td><?php echo $cat['is_active'] ? '<span class="text-success">Active</span>' : '<span class="text-muted">Inactive</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        $checkOldTable = $pdo->query("SHOW TABLES LIKE 'gallery_albums_old'")->fetch();
        if ($checkOldTable):
        ?>
        <!-- Finalize Migration card -->
        <div style="background:#fff; border:1px solid var(--card-border); border-radius:18px; padding:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.01); margin-top:2rem; grid-column: span 2;">
            <h4 style="font-family:'Outfit',sans-serif; font-weight:700; color: var(--danger); margin-bottom:1rem;">Legacy Migration Safe-Guard</h4>
            <p style="font-size:0.85rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.5rem;">
                The legacy table <code>gallery_albums_old</code> is kept intact to protect the original Category ➔ Photo mappings. 
                Please perform manual verification on the website and administration panel. Once verified, click <strong>Finalize Migration</strong> to drop the old table.
            </p>
            <button class="gadm-btn gadm-btn-danger" id="finalizeBtn" onclick="finalizeMigration()">✓ Finalize Migration (Drop Legacy Table)</button>
            <span id="finalizeStatus" style="margin-left: 1rem; font-size: 0.85rem; font-weight: 600;"></span>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div><!-- /.container -->
</div><!-- /.gadm-wrap -->

<!-- ══════ ALBUM MODAL (UPLOAD / EDIT) ══════ -->
<div class="gadm-modal-backdrop" id="albumModal">
<div class="gadm-modal-box">
    <button class="gadm-modal-close" onclick="closeAlbumModal()">×</button>
    <h3 id="albumModalHeading" style="font-family:'Outfit',sans-serif;font-size:1.5rem;margin-bottom:1.5rem;font-weight:700;">Create Album</h3>

    <form action="gallery.php?view=albums" method="POST" id="albumForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="save_album" value="1">
        <input type="hidden" name="id" id="albId" value="0">

        <div class="gadm-form-row">
            <label class="gadm-form-label">Album Title *</label>
            <input type="text" name="title" id="albTitle" class="gadm-form-input" required placeholder="e.g. National Championship June 2026">
        </div>

        <div class="gadm-form-2col gadm-form-row">
            <div>
                <label class="gadm-form-label">Category *</label>
                <select name="category_id" id="albCatId" class="gadm-form-input" required>
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="gadm-form-label">Album Type</label>
                <select name="album_type" id="albType" class="gadm-form-input">
                    <option value="event">Event / Championship</option>
                    <option value="training">Training Session</option>
                    <option value="camp">Selection Camp</option>
                    <option value="ceremony">Ceremony / Awards</option>
                    <option value="media">Media / Press</option>
                    <option value="general">General Showcase</option>
                </select>
            </div>
        </div>

        <div class="gadm-form-row">
            <label class="gadm-form-label">Description</label>
            <textarea name="description" id="albDesc" class="gadm-form-input" rows="3" placeholder="Brief details about the event..."></textarea>
        </div>

        <div class="gadm-form-2col gadm-form-row">
            <div>
                <label class="gadm-form-label">Event Date</label>
                <input type="date" name="event_date" id="albDate" class="gadm-form-input">
            </div>
            <div>
                <label class="gadm-form-label">Event Location</label>
                <input type="text" name="event_location" id="albLocation" class="gadm-form-input" placeholder="e.g. Bathinda, Punjab">
            </div>
        </div>

        <div class="gadm-form-row">
            <label class="gadm-form-label">Cover Image Override URL (Optional)</label>
            <input type="text" name="cover_image_override" id="albCoverOverride" class="gadm-form-input" placeholder="e.g. assets/images/official-poster.jpg">
            <small style="opacity:0.55;font-size:0.75rem;">Allows setting a poster or logo instead of a photo.</small>
        </div>

        <div class="gadm-form-row" style="display:flex; gap:1.5rem; margin-top:1.5rem;">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.88rem;font-weight:600;">
                <input type="checkbox" name="is_published" id="albPublished" value="1" checked style="accent-color:#138808;width:16px;height:16px;">
                Published (Visible to public)
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.88rem;font-weight:600;">
                <input type="checkbox" name="is_featured" id="albFeatured" value="1" style="accent-color:#FF9933;width:16px;height:16px;">
                Featured Event (Banner/Highlight)
            </label>
        </div>

        <button type="submit" class="gadm-btn gadm-btn-pri" style="width:100%;padding:.8rem;font-size:1rem;margin-top:1rem;">
            Save Album
        </button>
    </form>
</div>
</div>

<!-- ══════ PHOTO MODAL (UPLOAD / EDIT) ══════ -->
<div class="gadm-modal-backdrop" id="photoModal">
<div class="gadm-modal-box">
    <button class="gadm-modal-close" onclick="closePhotoModal()">×</button>
    <h3 id="photoModalHeading" style="font-family:'Outfit',sans-serif;font-size:1.5rem;margin-bottom:1.5rem;font-weight:700;">Upload Photo</h3>

    <form action="gallery.php?view=photos" method="POST" enctype="multipart/form-data" id="photoForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="save_image" value="1">
        <input type="hidden" name="id" id="fId" value="0">

        <div class="gadm-form-row">
            <label class="gadm-form-label">Image File <span id="imgStar" style="color:#D72638;">*</span></label>
            <input type="file" name="image" id="fImage" class="gadm-form-input" accept="image/*">
            <p id="imgHelp" style="font-size:.73rem;opacity:.55;margin-top:.3rem;">Select JPG, PNG, or WEBP.</p>
        </div>

        <div class="gadm-form-row">
            <label class="gadm-form-label">Caption / Title</label>
            <input type="text" name="caption" id="fCaption" class="gadm-form-input" placeholder="e.g. Medal Ceremony 2026">
        </div>

        <div class="gadm-form-2col gadm-form-row">
            <div>
                <label class="gadm-form-label">Alt Text</label>
                <input type="text" name="alt_text" id="fAlt" class="gadm-form-input" placeholder="Screen-reader description">
            </div>
            <div>
                <label class="gadm-form-label">Photo Credit</label>
                <input type="text" name="credit" id="fCredit" class="gadm-form-input" placeholder="Photographer name">
            </div>
        </div>

        <div class="gadm-form-2col gadm-form-row">
            <div>
                <label class="gadm-form-label">Target Album</label>
                <select name="album_id" id="fAlbum" class="gadm-form-input">
                    <option value="">— No Album —</option>
                    <?php foreach ($albumsList as $alb): ?>
                        <option value="<?php echo $alb['id']; ?>"><?php echo htmlspecialchars($alb['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="gadm-form-label">Sort Order</label>
                <input type="number" name="sort_order" id="fSort" class="gadm-form-input" value="0">
            </div>
        </div>

        <div class="gadm-form-2col gadm-form-row">
            <div>
                <label class="gadm-form-label">Status</label>
                <select name="status" id="fStatus" class="gadm-form-input">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;justify-content:flex-end;gap:.5rem;padding-bottom:.15rem;">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.88rem;font-weight:600;">
                    <input type="checkbox" name="is_featured" id="fFeatured" value="1" style="accent-color:#FF9933;width:16px;height:16px;">
                    Featured
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.88rem;font-weight:600;">
                    <input type="checkbox" name="show_in_hero" id="fHero" value="1" style="accent-color:#4FC3F7;width:16px;height:16px;">
                    Show in Hero Slider
                </label>
            </div>
        </div>

        <button type="submit" class="gadm-btn gadm-btn-pri" style="width:100%;padding:.8rem;font-size:1rem;margin-top:.5rem;">
            Save Photo
        </button>
    </form>
</div>
</div>

<script>
const CSRF  = <?php echo json_encode($_SESSION['csrf_token']); ?>;
const BASE  = 'gallery.php';

/* ── Selection ── */
function updateSelCount() {
    const n = document.querySelectorAll('.item-check:checked').length;
    const countEl = document.getElementById('selCount');
    if (countEl) countEl.textContent = n + ' selected';
    document.querySelectorAll('.gadm-card').forEach(c => {
        const cb = c.querySelector('.item-check');
        c.classList.toggle('selected', cb && cb.checked);
    });
}
function selectAll()  { document.querySelectorAll('.item-check').forEach(c=>{c.checked=true;}); updateSelCount(); }
function selectNone() { document.querySelectorAll('.item-check').forEach(c=>{c.checked=false;}); updateSelCount(); }
function getSelIds()  { return Array.from(document.querySelectorAll('.item-check:checked')).map(c=>c.value); }

/* ── Bulk actions ── */
function bulkAction(action, extra={}) {
    const ids = getSelIds();
    if (!ids.length) { alert('Select at least one photo first.'); return; }
    if (action === 'hard_delete' && !confirm('Permanently delete ' + ids.length + ' photo(s)? This CANNOT be undone.')) return;
    if (action === 'soft_delete' && !confirm('Move ' + ids.length + ' photo(s) to recycle bin?')) return;
    ajax({action, ids: ids.join(','), ...extra}, res => {
        if (res.ok) location.reload();
        else alert('Error: ' + res.error);
    });
}
function bulkAssignAlbum() {
    const albumId = document.getElementById('bulkAlbum').value;
    if (!albumId) { alert('Please select an album first.'); return; }
    const ids = getSelIds();
    if (!ids.length) { alert('Select at least one photo first.'); return; }
    ajax({action:'assign_album', ids:ids.join(','), album_id:albumId}, res => {
        if (res.ok) location.reload();
        else alert('Error: ' + res.error);
    });
}
function softDeleteOne(id) {
    if (!confirm('Move to recycle bin?')) return;
    ajax({action:'soft_delete', ids:String(id)}, res => {
        if(res.ok) location.reload();
        else alert('Error: ' + res.error);
    });
}
function restoreOne(id) {
    ajax({action:'restore', ids:String(id)}, res => {
        if(res.ok) location.reload();
        else alert('Error: ' + res.error);
    });
}
function hardDeleteOne(id) {
    if (!confirm('Delete permanently?')) return;
    ajax({action:'hard_delete', ids:String(id)}, res => {
        if(res.ok) location.reload();
        else alert('Error: ' + res.error);
    });
}
function purgeBin() {
    if (!confirm('Permanently delete all items in the bin older than 90 days?')) return;
    ajax({action:'purge_bin', ids:''}, res => {
        if (res.ok) { alert('Purged ' + res.purged + ' item(s).'); location.reload(); }
    });
}

/* ── Finalize Migration ── */
function finalizeMigration() {
    if (!confirm('Are you absolutely sure you want to finalize the migration and drop the legacy gallery_albums_old table? This cannot be undone.')) return;
    const btn = document.getElementById('finalizeBtn');
    const status = document.getElementById('finalizeStatus');
    btn.disabled = true;
    status.style.color = 'var(--text-secondary)';
    status.textContent = 'Dropping legacy table...';
    ajax({action:'finalize_migration', ids:''}, res => {
        if (res.ok) {
            status.style.color = 'var(--bsfi-green)';
            status.textContent = 'Migration finalized successfully!';
            setTimeout(() => location.reload(), 1500);
        } else {
            btn.disabled = false;
            status.style.color = 'var(--danger)';
            status.textContent = 'Error: ' + res.error;
        }
    });
}

/* ── Folder sync ── */
function runSync() {
    const albumId = document.getElementById('syncAlbumId').value;
    if (!albumId) { alert('Please select a target Album first.'); return; }
    const btn = document.getElementById('syncRunBtn');
    btn.disabled = true; btn.textContent = '⏳ Syncing…';
    const log = document.getElementById('syncLog');
    log.style.display = 'block'; log.innerHTML = '<li class="log-ok">Starting folder scan…</li>';
    ajax({action:'sync_folder', ids:'', album_id:albumId}, res => {
        btn.disabled = false; btn.textContent = '▶ Run Sync';
        if (res.ok) {
            log.innerHTML += '<li class="log-ok">✓ Added: ' + res.added + ' new photos</li>';
            log.innerHTML += '<li class="log-skip">⊘ Skipped (duplicate): ' + res.skipped + '</li>';
            if (res.added > 0) setTimeout(()=>location.reload(), 1200);
        } else {
            log.innerHTML += '<li class="log-err">✗ Error: ' + (res.error||'unknown') + '</li>';
        }
    });
}

/* ── Modals open/close ── */
function openAlbumModal(item) {
    const modal = document.getElementById('albumModal');
    const isNew = item === 0;
    document.getElementById('albumModalHeading').textContent = isNew ? 'Create Album' : 'Edit Album';
    document.getElementById('albId').value = isNew ? 0 : item.id;
    if (!isNew) {
        document.getElementById('albTitle').value = item.title;
        document.getElementById('albCatId').value = item.category_id;
        document.getElementById('albDesc').value = item.description || '';
        document.getElementById('albDate').value = item.event_date || '';
        document.getElementById('albLocation').value = item.event_location || '';
        document.getElementById('albCoverOverride').value = item.cover_image_override || '';
        document.getElementById('albType').value = item.album_type || 'event';
        document.getElementById('albPublished').checked = item.is_published == 1;
        document.getElementById('albFeatured').checked = item.is_featured == 1;
    } else {
        document.getElementById('albumForm').reset();
    }
    modal.classList.add('open');
}
function closeAlbumModal() {
    document.getElementById('albumModal').classList.remove('open');
}

function openPhotoModal(item) {
    const modal = document.getElementById('photoModal');
    const isNew = item === 0;
    document.getElementById('photoModalHeading').textContent = isNew ? 'Upload Photo' : 'Edit Photo';
    document.getElementById('fId').value = isNew ? 0 : item.id;
    document.getElementById('fImage').required = isNew;
    document.getElementById('imgStar').style.display = isNew ? 'inline' : 'none';
    document.getElementById('imgHelp').textContent = isNew ? 'Select JPG, PNG, or WEBP.' : 'Leave empty to keep existing image.';
    if (!isNew) {
        document.getElementById('fCaption').value  = item.caption  || '';
        document.getElementById('fAlt').value      = item.alt_text || '';
        document.getElementById('fCredit').value   = item.credit   || '';
        document.getElementById('fAlbum').value    = item.album_id || '';
        document.getElementById('fSort').value     = item.sort_order;
        document.getElementById('fStatus').value   = item.status   || 'published';
        document.getElementById('fFeatured').checked  = item.is_featured == 1;
        document.getElementById('fHero').checked      = item.show_in_hero == 1;
    } else {
        document.getElementById('photoForm').reset();
        document.getElementById('fStatus').value = 'published';
    }
    modal.classList.add('open');
}
function closePhotoModal() {
    document.getElementById('photoModal').classList.remove('open');
}

// Close modals when clicking backdrop
document.getElementById('albumModal').addEventListener('click', function(e){ if (e.target === this) closeAlbumModal(); });
document.getElementById('photoModal').addEventListener('click', function(e){ if (e.target === this) closePhotoModal(); });

/* ── AJAX helper ── */
function ajax(data, cb) {
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('csrf_token', CSRF);
    for (const k in data) fd.append(k, data[k]);
    fetch(BASE, {method:'POST', body:fd})
        .then(r=>r.json()).then(cb)
        .catch(e=>{ console.error(e); alert('Network error — check console.'); });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
