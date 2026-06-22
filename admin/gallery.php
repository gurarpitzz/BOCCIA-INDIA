<?php
// admin/gallery.php – Full Gallery Management: upload, bulk actions, folder-sync, recycle bin
require_once __DIR__ . '/../includes/db.php';
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

/** Rebuild the homepage gallery JSON cache */
function rebuildGalleryCache(PDO $pdo, string $cachePath): void {
    try {
        $imgs = $pdo->query("
            SELECT gi.*, ga.title AS album_title, ga.slug AS album_slug
            FROM gallery_images gi
            LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
            WHERE gi.status = 'published' AND gi.is_deleted = 0
            ORDER BY gi.sort_order ASC, gi.created_at DESC
            LIMIT 200
        ")->fetchAll();

        $hero = $pdo->query("
            SELECT * FROM gallery_images
            WHERE status='published' AND is_deleted=0 AND show_in_hero=1
            ORDER BY sort_order ASC LIMIT 5
        ")->fetchAll();

        $albums = $pdo->query("SELECT * FROM gallery_albums ORDER BY id ASC")->fetchAll();

        file_put_contents($cachePath, json_encode([
            'images'     => $imgs,
            'hero'       => $hero,
            'albums'     => $albums,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } catch (PDOException $e) { /* fail silently */ }
}

/** Strip EXIF and create thumbnail/medium/full versions, returns path array */
function processImageVersions(string $srcPath, string $destDir, string $baseName): array {
    $paths = ['thumb' => '', 'medium' => '', 'full' => ''];

    // Make sure GD is available
    if (!function_exists('imagecreatefromjpeg')) {
        return $paths;
    }

    $ext  = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    $img  = null;

    if (in_array($ext, ['jpg', 'jpeg'])) {
        $img = @imagecreatefromjpeg($srcPath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($srcPath);
    } elseif ($ext === 'webp') {
        $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : null;
    }

    if (!$img) {
        return $paths;
    }

    $origW = imagesx($img);
    $origH = imagesy($img);

    $sizes = [
        'thumb'  => 400,
        'medium' => 800,
        'full'   => 1920,
    ];

    foreach ($sizes as $label => $maxW) {
        $subDir = $destDir . $label . '/';
        if (!is_dir($subDir)) {
            mkdir($subDir, 0775, true);
        }

        $ratio  = min(1, $maxW / $origW);
        $newW   = (int)round($origW * $ratio);
        $newH   = (int)round($origH * $ratio);

        $canvas = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG/WEBP
        if (in_array($ext, ['png', 'webp'])) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        }

        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $outFile = $subDir . $baseName;
        if (in_array($ext, ['jpg', 'jpeg'])) {
            imagejpeg($canvas, $outFile, 85);   // EXIF auto-stripped on re-save
        } elseif ($ext === 'png') {
            imagepng($canvas, $outFile, 6);
        } elseif ($ext === 'webp') {
            imagewebp($canvas, $outFile, 82);
        }

        imagedestroy($canvas);
        $paths[$label] = $outFile;
    }

    imagedestroy($img);
    return $paths;
}

/** Convert absolute FS path to relative web path */
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
    $ids    = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));

    if (empty($ids) && !in_array($action, ['sync_folder'])) {
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
                    if (!empty($r[$col])) {
                        $fp = __DIR__ . '/../' . $r[$col];
                        if (file_exists($fp)) @unlink($fp);
                    }
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
            // Hard-delete anything soft-deleted > 90 days
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

                // Skip duplicates
                $exists = $pdo->prepare("SELECT id FROM gallery_images WHERE file_hash=? LIMIT 1");
                $exists->execute([$hash]);
                if ($exists->fetch()) { $skipped++; continue; }

                $baseName = $hash . '.' . $ext;
                $destFull = $destDir . 'full/' . $baseName;
                if (!is_dir($destDir . 'full/')) mkdir($destDir . 'full/', 0775, true);
                copy($f->getPathname(), $destFull);

                // Generate versions + strip EXIF
                $versions = processImageVersions($f->getPathname(), $destDir, $baseName);
                $relFull  = toWebPath($versions['full']  ?: $destFull, $docRoot);
                $relThumb = toWebPath($versions['thumb'] ?: $destFull, $docRoot);
                $relMed   = toWebPath($versions['medium']?: $destFull, $docRoot);

                $pdo->prepare("INSERT INTO gallery_images (file_hash, image_path, thumbnail_path, medium_path, full_path, caption, status, uploaded_by) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$hash, $relFull, $relThumb, $relMed, $relFull,
                               pathinfo($f->getFilename(), PATHINFO_FILENAME), 'published', $userId]);

                $added++;
            }

            rebuildGalleryCache($pdo, $GALLERY_CACHE);
            echo json_encode(['ok' => true, 'added' => $added, 'skipped' => $skipped]); break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════
   SINGLE UPLOAD (non-AJAX form POST)
═══════════════════════════════════════════════════════ */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_image'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
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
                $message = "<div class='alert alert-success'>Photo updated.</div>";
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
}

/* ═══════════════════════════════════════════════════════
   DATA FETCH
═══════════════════════════════════════════════════════ */
$view       = $_GET['view'] ?? 'active'; // 'active' | 'bin'
$filterAlbum= (int)($_GET['album'] ?? 0);

$albums = $pdo->query("SELECT * FROM gallery_albums ORDER BY id ASC")->fetchAll();

$baseWhere = $view === 'bin' ? "gi.is_deleted = 1" : "gi.is_deleted = 0";
if ($filterAlbum) $baseWhere .= " AND gi.album_id = $filterAlbum";

$galleryList = $pdo->query("
    SELECT gi.*, ga.title AS album_title
    FROM gallery_images gi
    LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
    WHERE $baseWhere
    ORDER BY gi.sort_order ASC, gi.created_at DESC
    LIMIT 300
")->fetchAll();

$binCount    = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_deleted=1")->fetchColumn();
$activeCount = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_deleted=0")->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Admin Gallery Styles ── */
.gadm-wrap    { background:#08142E; min-height:95vh; padding:5.5rem 0 4rem; color:#FAF7F0; }
.gadm-topbar  { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;
                border-bottom:1px solid rgba(255,255,255,.07); padding-bottom:1.5rem; margin-bottom:2rem; }
.gadm-topbar h1 { font-family:'Outfit',sans-serif; font-size:2rem; font-weight:700; margin:0; }
.gadm-eyebrow { color:#ff7e67; text-transform:uppercase; letter-spacing:.07em; font-size:.8rem; font-weight:600; }
.gadm-btn     { padding:.45rem 1.2rem; border-radius:999px; font-size:.85rem; font-weight:700;
                border:none; cursor:pointer; transition:background .2s,color .2s; }
.gadm-btn-pri { background:#ff7e67; color:#fff; }
.gadm-btn-pri:hover { background:#e5604d; }
.gadm-btn-sec { background:rgba(255,255,255,.08); color:#FAF7F0; border:1px solid rgba(255,255,255,.15); }
.gadm-btn-sec:hover { background:rgba(255,255,255,.16); }
.gadm-btn-danger { background:#D72638; color:#fff; }
.gadm-btn-danger:hover { background:#b91c2c; }
.gadm-btn-warn  { background:#F4B942; color:#08142E; }
.gadm-btn-warn:hover { background:#d89c20; }

/* Tabs */
.gadm-tabs    { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.gadm-tab     { padding:.4rem 1.1rem; border-radius:999px; font-size:.82rem; font-weight:600; cursor:pointer;
                border:1px solid rgba(255,255,255,.12); color:#FAF7F0;
                background:rgba(255,255,255,.06); text-decoration:none; transition:all .2s; }
.gadm-tab.active, .gadm-tab:hover { background:#FF9933; color:#08142E; border-color:#FF9933; }

/* Album filter strip */
.gadm-album-strip { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; align-items:center; }
.gadm-afilter { font-size:.78rem; padding:.3rem .9rem; border-radius:999px;
                border:1px solid rgba(255,255,255,.1); background:rgba(255,255,255,.05);
                color:#FAF7F0; cursor:pointer; font-weight:600; text-decoration:none; transition:all .2s; }
.gadm-afilter.active, .gadm-afilter:hover { background:#FF9933; color:#08142E; border-color:#FF9933; }

/* Toolbar */
.gadm-toolbar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:1.25rem;
                background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07);
                padding:.75rem 1rem; border-radius:14px; }
.gadm-toolbar select { background:#0f2040; color:#FAF7F0; border:1px solid rgba(255,255,255,.12);
                        padding:.35rem .75rem; border-radius:8px; font-size:.82rem; }
#selCount { font-size:.82rem; opacity:.65; min-width:100px; }

/* Grid */
.gadm-grid    { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1.25rem; }
.gadm-card    { background:rgba(22,41,90,.35); border-radius:18px; overflow:hidden;
                border:1px solid rgba(255,255,255,.06); position:relative;
                transition:border-color .2s, box-shadow .2s; }
.gadm-card:hover { border-color:rgba(255,153,51,.35); box-shadow:0 6px 24px rgba(0,0,0,.35); }
.gadm-card.selected { border-color:#FF9933; box-shadow:0 0 0 2px #FF9933; }
.gadm-card-thumb { width:100%; aspect-ratio:4/3; object-fit:cover; display:block; background:#0a1d3b; }
.gadm-card-body  { padding:1rem; }
.gadm-card-caption { font-size:.88rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:.3rem; }
.gadm-card-meta  { font-size:.72rem; opacity:.55; }
.gadm-card-badges { display:flex; gap:.3rem; flex-wrap:wrap; margin-top:.5rem; }
.gadm-badge      { font-size:.65rem; font-weight:700; padding:.18rem .55rem; border-radius:4px; text-transform:uppercase; }
.badg-feat       { background:#F4B942; color:#08142E; }
.badg-hero       { background:#4FC3F7; color:#08142E; }
.badg-hidden     { background:#D72638; color:#fff; }
.badg-draft      { background:#888; color:#fff; }
.gadm-card-footer { display:flex; justify-content:space-between; align-items:center; padding:.6rem 1rem;
                    border-top:1px solid rgba(255,255,255,.06); gap:.4rem; }
.gadm-check-wrap { position:absolute; top:10px; left:10px; z-index:3; }
.gadm-check      { width:18px; height:18px; accent-color:#FF9933; cursor:pointer; }

/* Sync log */
#syncLog  { max-height:180px; overflow-y:auto; background:rgba(0,0,0,.3); border-radius:10px;
            padding:.75rem 1rem; font-size:.8rem; font-family:monospace; display:none;
            border:1px solid rgba(255,255,255,.08); margin-top:.75rem; }
#syncLog li { list-style:none; padding:.15rem 0; }
.log-ok  { color:#24C27A; }
.log-skip{ color:#aaa; }
.log-err { color:#ff7e67; }

/* Modal */
.gadm-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.8); z-index:9990;
                        align-items:center; justify-content:center; }
.gadm-modal-backdrop.open { display:flex; }
.gadm-modal-box { background:#08142E; border:1px solid rgba(255,255,255,.1); border-radius:24px;
                  padding:2rem; max-width:560px; width:95%; max-height:90vh; overflow-y:auto; position:relative; }
.gadm-modal-close { position:absolute; top:12px; right:14px; background:none; border:none; color:#FAF7F0;
                    font-size:1.4rem; cursor:pointer; }
.gadm-form-label  { font-size:.78rem; font-weight:600; margin-bottom:.3rem; display:block; }
.gadm-form-input  { width:100%; background:#0f2040; border:1px solid rgba(255,255,255,.12); color:#FAF7F0;
                    padding:.55rem .85rem; border-radius:10px; font-size:.88rem; box-sizing:border-box; }
.gadm-form-input:focus { outline:none; border-color:#FF9933; }
.gadm-form-row    { margin-bottom:1rem; }
.gadm-form-2col   { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
</style>

<div class="gadm-wrap">
<div class="container">

    <!-- Top bar -->
    <div class="gadm-topbar">
        <div>
            <div class="gadm-eyebrow">Content Management</div>
            <h1>Photo Gallery</h1>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
            <button class="gadm-btn gadm-btn-pri" onclick="openModal(0)">Upload Photo</button>
            <button class="gadm-btn gadm-btn-sec" onclick="openSyncPanel()">Sync Folder</button>
            <a href="dashboard.php" class="gadm-btn gadm-btn-sec">Dashboard</a>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- View tabs -->
    <div class="gadm-tabs">
        <a href="gallery.php?view=active" class="gadm-tab <?php echo $view==='active'?'active':''; ?>">
            Active (<?php echo $activeCount; ?>)
        </a>
        <a href="gallery.php?view=bin" class="gadm-tab <?php echo $view==='bin'?'active':''; ?>">
            Recycle Bin (<?php echo $binCount; ?>)
        </a>
    </div>

    <!-- Album filter strip -->
    <div class="gadm-album-strip">
        <span style="font-size:.78rem;opacity:.55;font-weight:600;">Filter:</span>
        <a href="gallery.php?view=<?php echo $view; ?>" class="gadm-afilter <?php echo !$filterAlbum?'active':''; ?>">All</a>
        <?php foreach ($albums as $alb): ?>
        <a href="gallery.php?view=<?php echo $view; ?>&album=<?php echo $alb['id']; ?>" class="gadm-afilter <?php echo $filterAlbum===$alb['id']?'active':''; ?>">
            <?php echo htmlspecialchars($alb['title']); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Bulk action toolbar -->
    <div class="gadm-toolbar" id="bulkToolbar">
        <button class="gadm-btn gadm-btn-sec" onclick="selectAll()">Select All</button>
        <button class="gadm-btn gadm-btn-sec" onclick="selectNone()">Deselect</button>
        <span id="selCount">0 selected</span>

        <?php if ($view === 'active'): ?>
        <button class="gadm-btn gadm-btn-danger" onclick="bulkAction('soft_delete')">Move to Bin</button>
        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('feature',{value:1})">Mark Featured</button>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAction('feature',{value:0})">Unfeature</button>
        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('hero',{value:1})">Show in Hero</button>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAction('hero',{value:0})">Remove Hero</button>
        <select id="bulkAlbum" style="min-width:130px;">
            <option value="">— Assign Album —</option>
            <?php foreach ($albums as $alb): ?>
            <option value="<?php echo $alb['id']; ?>"><?php echo htmlspecialchars($alb['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="gadm-btn gadm-btn-sec" onclick="bulkAssignAlbum()">Apply Album</button>
        <?php else: ?>
        <button class="gadm-btn gadm-btn-warn" onclick="bulkAction('restore')">Restore Selected</button>
        <button class="gadm-btn gadm-btn-danger" onclick="bulkAction('hard_delete')">Delete Permanently</button>
        <button class="gadm-btn gadm-btn-danger" onclick="purgeBin()" style="margin-left:auto;">Purge 90d+ Expired</button>
        <?php endif; ?>
    </div>

    <!-- Folder Sync Panel -->
    <div id="syncPanel" style="display:none; background:rgba(255,255,255,.04); border:1px solid rgba(255,153,51,.3); border-radius:16px; padding:1.5rem; margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <div>
                <div style="font-weight:700;font-size:1rem;">Folder Sync</div>
                <div style="font-size:.78rem;opacity:.55;margin-top:.25rem;">
                    Scans: <code style="background:rgba(255,255,255,.08);padding:.1rem .4rem;border-radius:4px;font-size:.75rem;">DROP_GALLERY_PHOTOS_HERE</code> — SHA-256 deduplication, EXIF stripped, versions generated.
                </div>
            </div>
            <button class="gadm-btn gadm-btn-pri" id="syncRunBtn" onclick="runSync()">Run Sync</button>
        </div>
        <ul id="syncLog"></ul>
    </div>

    <!-- Gallery Grid -->
    <?php if (count($galleryList) > 0): ?>
    <div class="gadm-grid" id="galGrid">
        <?php foreach ($galleryList as $item): ?>
        <div class="gadm-card" id="card-<?php echo $item['id']; ?>" data-id="<?php echo $item['id']; ?>">

            <div class="gadm-check-wrap">
                <input type="checkbox" class="gadm-check item-check" value="<?php echo $item['id']; ?>"
                       onchange="updateSelCount()">
            </div>

            <img class="gadm-card-thumb"
                 src="../<?php echo htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']); ?>"
                 alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['caption'] ?? ''); ?>"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1 1%22><rect fill=%22%230a1d3b%22/></svg>'">

            <div class="gadm-card-body">
                <div class="gadm-card-caption"><?php echo htmlspecialchars($item['caption'] ?: '(no caption)'); ?></div>
                <div class="gadm-card-meta">
                    <?php echo htmlspecialchars($item['album_title'] ?? 'No album'); ?> &nbsp;·&nbsp;
                    Sort: <?php echo (int)$item['sort_order']; ?>
                </div>
                <div class="gadm-card-badges">
                    <?php if ($item['is_featured']): ?><span class="gadm-badge badg-feat">Featured</span><?php endif; ?>
                    <?php if ($item['show_in_hero']): ?><span class="gadm-badge badg-hero">Hero</span><?php endif; ?>
                    <?php if ($item['status'] !== 'published'): ?><span class="gadm-badge badg-draft"><?php echo htmlspecialchars($item['status']); ?></span><?php endif; ?>
                    <?php if ($view === 'bin'): ?><span class="gadm-badge badg-hidden">Deleted <?php echo $item['deleted_at'] ? date('d M', strtotime($item['deleted_at'])) : ''; ?></span><?php endif; ?>
                </div>
            </div>

            <div class="gadm-card-footer">
                <span style="font-size:.7rem;opacity:.4;">#<?php echo $item['id']; ?></span>
                <div style="display:flex;gap:.4rem;">
                    <?php if ($view === 'active'): ?>
                    <button class="gadm-btn gadm-btn-sec" style="padding:.3rem .75rem;font-size:.78rem;"
                            onclick='openModal(<?php echo json_encode(array_map("strval",$item)); ?>)'>Edit</button>
                    <button class="gadm-btn gadm-btn-danger" style="padding:.3rem .75rem;font-size:.78rem;"
                            onclick="softDeleteOne(<?php echo $item['id']; ?>)">Delete</button>
                    <?php else: ?>
                    <button class="gadm-btn gadm-btn-warn" style="padding:.3rem .75rem;font-size:.78rem;"
                            onclick="restoreOne(<?php echo $item['id']; ?>)">Restore</button>
                    <button class="gadm-btn gadm-btn-danger" style="padding:.3rem .75rem;font-size:.78rem;"
                            onclick="hardDeleteOne(<?php echo $item['id']; ?>)">Delete</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="background:rgba(255,255,255,.04);border-radius:24px;padding:4rem;text-align:center;">
        <p style="opacity:.6;font-size:1.1rem;">
            <?php echo $view==='bin' ? 'Recycle bin is empty.' : 'No photos yet. Upload or sync from folder.'; ?>
        </p>
    </div>
    <?php endif; ?>

</div><!-- /.container -->
</div><!-- /.gadm-wrap -->

<!-- ══════ UPLOAD / EDIT MODAL ══════ -->
<div class="gadm-modal-backdrop" id="galModal">
<div class="gadm-modal-box">
    <button class="gadm-modal-close" onclick="closeModal()">×</button>
    <h3 id="modalHeading" style="font-family:'Outfit',sans-serif;font-size:1.5rem;margin-bottom:1.5rem;font-weight:700;">Upload Photo</h3>

    <form action="gallery.php" method="POST" enctype="multipart/form-data" id="galForm">
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
                <label class="gadm-form-label">Album</label>
                <select name="album_id" id="fAlbum" class="gadm-form-input">
                    <option value="">— No album —</option>
                    <?php foreach ($albums as $alb): ?>
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
    document.getElementById('selCount').textContent = n + ' selected';
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
    ajax({action:'soft_delete', ids:String(id)}, res => { if(res.ok) location.reload(); });
}
function restoreOne(id) {
    ajax({action:'restore', ids:String(id)}, res => { if(res.ok) location.reload(); });
}
function hardDeleteOne(id) {
    if (!confirm('Delete permanently?')) return;
    ajax({action:'hard_delete', ids:String(id)}, res => { if(res.ok) location.reload(); });
}
function purgeBin() {
    if (!confirm('Permanently delete all items in the bin older than 90 days?')) return;
    ajax({action:'purge_bin', ids:''}, res => {
        if (res.ok) { alert('Purged ' + res.purged + ' item(s).'); location.reload(); }
    });
}

/* ── Folder sync ── */
function openSyncPanel() {
    const p = document.getElementById('syncPanel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
}
function runSync() {
    const btn = document.getElementById('syncRunBtn');
    btn.disabled = true; btn.textContent = '⏳ Syncing…';
    const log = document.getElementById('syncLog');
    log.style.display = 'block'; log.innerHTML = '<li class="log-ok">Starting folder scan…</li>';
    ajax({action:'sync_folder', ids:''}, res => {
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

/* ── Modal ── */
function openModal(item) {
    const modal = document.getElementById('galModal');
    const isNew = item === 0;
    document.getElementById('modalHeading').textContent = isNew ? 'Upload Photo' : 'Edit Photo';
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
        document.getElementById('galForm').reset();
        document.getElementById('fStatus').value = 'published';
    }
    modal.classList.add('open');
}
function closeModal() {
    document.getElementById('galModal').classList.remove('open');
}
document.getElementById('galModal').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});

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
