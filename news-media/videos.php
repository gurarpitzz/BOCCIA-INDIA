<?php
// news-media/videos.php - Federation Videos

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Fetch registered video assets from media registry
    $stmt = $pdo->query("SELECT * FROM media_assets WHERE (mime_type LIKE 'video/%' OR filename LIKE '%.mp4') AND deleted_at IS NULL ORDER BY uploaded_at DESC");
    $videos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Official Videos & Clips - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4" style="min-height: 70vh;">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">Federation Videos</h1>
        <p class="text-muted">Highlights, interviews, tutorials, and competition clips.</p>
    </div>

    <?php if (count($videos) > 0): ?>
        <div class="row g-4">
            <?php foreach ($videos as $vid): 
                $cleanTitle = str_replace(['_', '-'], ' ', pathinfo($vid['filename'], PATHINFO_FILENAME));
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="ratio ratio-16x9">
                            <video controls class="w-100 h-100" style="object-fit: cover;">
                                <source src="<?php echo htmlspecialchars($logo_path . $vid['filepath']); ?>" type="<?php echo htmlspecialchars($vid['mime_type']); ?>">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div class="card-body p-3">
                            <h5 class="card-title fw-bold text-dark m-0"><?php echo htmlspecialchars($cleanTitle); ?></h5>
                            <span class="text-muted" style="font-size:0.8rem;">Uploaded: <?php echo date('M j, Y', strtotime($vid['uploaded_at'])); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Render placeholder video card / message -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-4 mx-auto">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="ratio ratio-16x9">
                        <video class="w-100 h-100" style="object-fit: cover;" autoplay loop muted>
                            <source src="<?php echo $logo_path; ?>intro preloader.mp4" type="video/mp4">
                        </video>
                    </div>
                    <div class="card-body p-3 text-center">
                        <h5 class="card-title fw-bold text-dark mb-2">Intro Preloader Animation</h5>
                        <p class="text-muted m-0" style="font-size:0.85rem;">Official federation animation video.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
