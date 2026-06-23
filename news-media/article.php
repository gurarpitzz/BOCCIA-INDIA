<?php
// news-media/article.php - Dynamic individual news article page by slug
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: /index.php#official-federation-updates");
    exit;
}

try {
    // Increment view count and fetch news item details in one transaction context
    $stmt = $pdo->prepare("
        SELECT n.*, c.name AS category_name 
        FROM news n
        LEFT JOIN news_categories c ON n.category_id = c.id
        WHERE n.slug = ? AND n.deleted_at IS NULL AND (n.status = 'published' OR (n.status = 'scheduled' AND n.published_at <= NOW()))
    ");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
    
    if (!$article) {
        // Article not found or not published
        header("HTTP/1.0 404 Not Found");
        include __DIR__ . '/../includes/header.php';
        echo '<div class="container my-5 py-5 text-center"><h1 class="display-4 fw-bold">404 - Article Not Found</h1><p class="text-muted">The requested article could not be found or is no longer available.</p><a href="/index.php#official-federation-updates" class="btn btn-primary mt-3">Back to Updates</a></div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }

    // Increment views
    $updateViews = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
    $updateViews->execute([$article['id']]);
    
    // Fetch news gallery images
    $galleryStmt = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC");
    $galleryStmt->execute([$article['id']]);
    $galleryImages = $galleryStmt->fetchAll();

    // Fetch related articles (same category)
    $relatedStmt = $pdo->prepare("
        SELECT n.*, c.name AS category_name 
        FROM news n
        LEFT JOIN news_categories c ON n.category_id = c.id
        WHERE n.category_id = ? AND n.id != ? AND n.status = 'published' AND n.deleted_at IS NULL
        ORDER BY COALESCE(n.published_at, n.created_at) DESC
        LIMIT 3
    ");
    $relatedStmt->execute([$article['category_id'], $article['id']]);
    $relatedArticles = $relatedStmt->fetchAll();

} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

// Dynamic SEO fields mapped to variables used by header.php
$page_title = (!empty($article['meta_title']) ? $article['meta_title'] : $article['title']) . " - Boccia India";
$meta_desc = !empty($article['meta_description']) ? $article['meta_description'] : (!empty($article['excerpt']) ? $article['excerpt'] : substr(strip_tags($article['content']), 0, 150));
$og_image = !empty($article['cover_image']) ? "/" . $article['cover_image'] : (!empty($article['thumbnail_image']) ? "/" . $article['thumbnail_image'] : "/assets/images/bsfi-placeholder.webp");
$canonical_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$logo_path = "../";
include __DIR__ . '/../includes/header.php';

// Prepare image fallback
$coverUrl = !empty($article['cover_image']) ? htmlspecialchars("../" . $article['cover_image']) : (!empty($article['thumbnail_image']) ? htmlspecialchars("../" . $article['thumbnail_image']) : '../assets/images/bsfi-placeholder.webp');
$articleUrl = urlencode($canonical_url);
$articleTitle = urlencode($article['title']);
?>

<div class="container my-5 py-4" style="min-height: 80vh; max-width: 960px;">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb" style="background: transparent; padding: 0; font-size: 0.9rem;">
            <li class="breadcrumb-item"><a href="../index.php" style="color: var(--primary-navy); text-decoration: none;">Home</a></li>
            <li class="breadcrumb-item"><a href="../index.php#official-federation-updates" style="color: var(--primary-navy); text-decoration: none;">News</a></li>
            <?php if (!empty($article['category_name'])): ?>
                <li class="breadcrumb-item active" style="color: var(--accent-saffron); font-weight: 500;"><?php echo htmlspecialchars($article['category_name']); ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Main Banner Image -->
    <div class="mb-5 rounded-4 overflow-hidden shadow-sm" style="max-height: 480px; width: 100%; display: flex; align-items: center; justify-content: center; background: #eee;">
        <img src="<?php echo $coverUrl; ?>" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;" alt="<?php echo htmlspecialchars($article['title']); ?>">
    </div>

    <!-- Article Header Info -->
    <div class="mb-4">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important; font-family: var(--font-heading);"><?php echo htmlspecialchars($article['title']); ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-3 text-muted mt-3" style="font-size: 0.95rem;">
            <span class="badge bg-primary px-3 py-2 rounded-pill"><?php echo htmlspecialchars($article['category_name'] ?? 'General'); ?></span>
            <span>By <strong><?php echo htmlspecialchars($article['author_name'] ?? 'BSFI Official'); ?></strong></span>
            <span>•</span>
            <span>Published: <?php echo date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
            <span>•</span>
            <span><?php echo (int)$article['views']; ?> views</span>
        </div>
    </div>

    <hr class="my-4" style="opacity: 0.1;">

    <!-- Article Content -->
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="article-body-content" style="font-size: 1.1rem; line-height: 1.8; color: #333;">
                <?php echo nl2br($article['content']); // Content is rich text / preformatted output ?>
            </div>

            <!-- News Gallery -->
            <?php if (count($galleryImages) > 0): ?>
                <div class="mt-5">
                    <h3 class="fw-bold mb-4" style="color: #081B4B; font-family: var(--font-heading);">Event Photos</h3>
                    <div class="row g-3">
                        <?php foreach($galleryImages as $img): ?>
                            <div class="col-sm-6">
                                <a href="<?php echo htmlspecialchars("../" . $img['image_path']); ?>" class="glightbox d-block rounded-3 overflow-hidden shadow-sm" data-gallery="article-gallery" data-title="<?php echo htmlspecialchars($img['caption'] ?? ''); ?>">
                                    <img src="<?php echo htmlspecialchars("../" . $img['image_path']); ?>" class="img-fluid" style="height: 180px; width: 100%; object-fit: cover; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)';" onmouseout="this.style.transform='scale(1)';" alt="Gallery" loading="lazy">
                                    <?php if (!empty($img['caption'])): ?>
                                        <div class="bg-light p-2 text-center text-muted" style="font-size: 0.85rem; border-top: 1px solid #eee;">
                                            <?php echo htmlspecialchars($img['caption']); ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Share Buttons -->
            <div class="mt-5 p-4 rounded-4 bg-light d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span class="fw-bold" style="color: #081B4B;">Share this article:</span>
                <div class="d-flex gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $articleUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $articleUrl; ?>&text=<?php echo $articleTitle; ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3">Twitter/X</a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $articleUrl; ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3">LinkedIn</a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo $articleTitle . '%20' . $articleUrl; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3">WhatsApp</a>
                </div>
            </div>
        </div>

        <!-- Sidebar (Related external posts & social icons) -->
        <div class="col-lg-4">
            <?php
            $hasSocials = !empty($article['facebook_url']) || !empty($article['instagram_url']) || !empty($article['twitter_url']) || !empty($article['linkedin_url']) || !empty($article['youtube_url']);
            if ($hasSocials):
            ?>
                <div class="p-4 rounded-4 bg-white border shadow-sm mb-4">
                    <h5 class="fw-bold mb-3" style="color: #081B4B; font-family: var(--font-heading);">Related Posts</h5>
                    <p class="small text-muted mb-4">Follow the official conversation on other federation profiles:</p>
                    <div class="d-grid gap-2">
                        <?php if (!empty($article['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($article['facebook_url']); ?>" target="_blank" class="btn btn-outline-primary w-100 text-start">Facebook Link</a>
                        <?php endif; ?>
                        <?php if (!empty($article['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($article['instagram_url']); ?>" target="_blank" class="btn btn-outline-danger w-100 text-start">Instagram Link</a>
                        <?php endif; ?>
                        <?php if (!empty($article['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($article['twitter_url']); ?>" target="_blank" class="btn btn-outline-dark w-100 text-start">Twitter/X Link</a>
                        <?php endif; ?>
                        <?php if (!empty($article['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($article['linkedin_url']); ?>" target="_blank" class="btn btn-outline-info w-100 text-start">LinkedIn Link</a>
                        <?php endif; ?>
                        <?php if (!empty($article['youtube_url'])): ?>
                            <a href="<?php echo htmlspecialchars($article['youtube_url']); ?>" target="_blank" class="btn btn-outline-danger w-100 text-start">YouTube Video</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Related Articles Sidebar -->
            <?php if (count($relatedArticles) > 0): ?>
                <div class="p-4 rounded-4 bg-white border shadow-sm">
                    <h5 class="fw-bold mb-3" style="color: #081B4B; font-family: var(--font-heading);">More in this Category</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach($relatedArticles as $rel): 
                            $relThumb = !empty($rel['thumbnail_image']) ? htmlspecialchars("../" . $rel['thumbnail_image']) : (!empty($rel['image']) ? htmlspecialchars("../" . $rel['image']) : '../assets/images/bsfi-placeholder.webp');
                        ?>
                            <a href="article.php?slug=<?php echo urlencode($rel['slug']); ?>" style="text-decoration: none; color: inherit;" class="d-flex gap-3 align-items-center">
                                <img src="<?php echo $relThumb; ?>" class="rounded-3" style="width: 70px; height: 70px; object-fit: cover; flex-shrink: 0;" alt="Related">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-size: 0.95rem; line-height: 1.3; color: #081B4B; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($rel['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($rel['published_at'] ?? $rel['created_at'])); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if(typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
