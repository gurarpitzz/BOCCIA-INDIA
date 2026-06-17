<?php
// news-media/gallery.php - Photo Gallery Grid

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $galleryImages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Photo Gallery - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4" style="min-height: 70vh;">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">Photo Gallery</h1>
        <p class="text-muted">A visual catalog of tournaments, championships, training camps, and events.</p>
    </div>

    <?php if (count($galleryImages) > 0): ?>
        <div class="row g-4">
            <?php foreach ($galleryImages as $img): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <a href="<?php echo htmlspecialchars($logo_path . $img['image_path']); ?>" class="glightbox" data-gallery="page-gallery" data-title="<?php echo htmlspecialchars($img['title']); ?>" data-description="<?php echo htmlspecialchars($img['event_name'] ?? ''); ?>">
                            <img src="<?php echo htmlspecialchars($logo_path . $img['image_path']); ?>" class="card-img-top" style="height: 200px; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" alt="<?php echo htmlspecialchars($img['title']); ?>">
                        </a>
                        <div class="card-body p-3">
                            <h6 class="card-title fw-bold m-0 text-truncate text-dark"><?php echo htmlspecialchars($img['title']); ?></h6>
                            <span class="text-muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($img['event_name'] ?? 'Federation Event'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-5 text-center bg-light rounded-4 border">
            <p class="text-muted fs-5 m-0">No photos uploaded yet. Galleries are currently being updated.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof GLightbox !== 'undefined') {
        GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
