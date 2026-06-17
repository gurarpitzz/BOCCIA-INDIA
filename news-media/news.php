<?php
// news-media/news.php - Official Federation Updates

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Fetch published news
    $stmt = $pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY pinned DESC, published_at DESC");
    $newsList = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Official News Updates - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4" style="min-height: 70vh;">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">News &amp; Media</h1>
        <p class="text-muted">Official statements, tournament summaries, and press releases from BSFI.</p>
    </div>

    <?php if (count($newsList) > 0): ?>
        <div class="row g-4">
            <?php foreach ($newsList as $news): 
                $rawContent = strip_tags($news['content']);
                $excerpt = strlen($rawContent) > 200 ? substr($rawContent, 0, 200) . '...' : $rawContent;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <?php if (!empty($news['image'])): ?>
                            <img src="<?php echo htmlspecialchars($logo_path . $news['image']); ?>" class="card-img-top" style="height: 220px; object-fit: cover;" alt="<?php echo htmlspecialchars($news['title']); ?>">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-direction-column justify-content-between p-4">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-light text-primary"><?php echo htmlspecialchars($news['category']); ?></span>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($news['published_at'] ?? $news['created_at'])); ?></small>
                                </div>
                                <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($news['title']); ?></h4>
                                <p class="card-text text-secondary mb-4"><?php echo htmlspecialchars($excerpt); ?></p>
                            </div>
                            <a href="../page.php?section=news&slug=<?php echo urlencode($news['slug']); ?>" class="btn btn-outline-primary w-100 rounded-pill">Read Dynamic Article</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-5 text-center bg-light rounded-4 border">
            <p class="text-muted fs-5 m-0">Official updates are currently being synchronized. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
