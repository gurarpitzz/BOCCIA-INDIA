<?php
// news.php - Dynamic routing & rendering controller for news articles
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: index.php");
    exit();
}

try {
    // Fetch published or scheduled news article matching the slug
    $stmt = $pdo->prepare("
        SELECT n.*, nc.name AS category_name 
        FROM news n 
        LEFT JOIN news_categories nc ON n.category_id = nc.id 
        WHERE n.slug = ? AND n.deleted_at IS NULL AND (n.status = 'published' OR (n.status = 'scheduled' AND n.published_at <= NOW()))
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    // Custom 404 - Article Not Found
    $page_title = "404 - Article Not Found | Boccia India";
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container my-5 py-5 text-center" style="min-height: 55vh;">
        <div class="py-5">
            <h1 class="display-1 text-primary fw-bold" style="font-family: var(--font-heading); color: #081B4B !important;">404</h1>
            <h3 class="fw-bold text-dark">Article Not Found</h3>
            <p class="text-muted">The requested news update does not exist or has been archived by the federation.</p>
            <a href="index.php" class="btn btn-primary px-4 py-2 mt-3 rounded-pill" style="background:#081B4B; border:none; font-weight:700;">Return to Home Page</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit();
}

// Increment view counter securely
try {
    $updateViews = $pdo->prepare("UPDATE news SET views = COALESCE(views, 0) + 1 WHERE id = ?");
    $updateViews->execute([$article['id']]);
    $article['views'] = ($article['views'] ?? 0) + 1;
} catch (PDOException $e) {
    // Fail silently
}

// Fetch extra gallery images associated with this article
$extraImages = [];
try {
    $imgStmt = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC");
    $imgStmt->execute([$article['id']]);
    $extraImages = $imgStmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch 3 Related Articles (Same category first, fallback to latest, excluding current)
$related = [];
try {
    $relatedStmt = $pdo->prepare("
        SELECT * FROM news 
        WHERE category_id = ? AND id <> ? AND status = 'published' AND deleted_at IS NULL 
        ORDER BY published_at DESC LIMIT 3
    ");
    $relatedStmt->execute([$article['category_id'], $article['id']]);
    $related = $relatedStmt->fetchAll();

    if (count($related) < 3) {
        $needed = 3 - count($related);
        $excludeIds = array_merge([$article['id']], array_column($related, 'id'));
        $inClause = implode(',', array_fill(0, count($excludeIds), '?'));
        
        $fallbackStmt = $pdo->prepare("
            SELECT * FROM news 
            WHERE id NOT IN ($inClause) AND status = 'published' AND deleted_at IS NULL 
            ORDER BY published_at DESC LIMIT $needed
        ");
        $fallbackStmt->execute($excludeIds);
        $related = array_merge($related, $fallbackStmt->fetchAll());
    }
} catch (PDOException $e) {
    // Fail silently
}

// Setup Dynamic SEO headers
$page_title = htmlspecialchars($article['meta_title'] ?: $article['title']) . " - Boccia India";
$meta_desc = htmlspecialchars($article['meta_description'] ?: ($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160)));
$canonical_url = "news.php?slug=" . urlencode($article['slug']);

// Header cover image fallback
$coverImage = !empty($article['cover_image']) ? $article['cover_image'] : (!empty($article['thumbnail_image']) ? $article['thumbnail_image'] : (!empty($article['image']) ? $article['image'] : ''));

// Include main site header
include __DIR__ . '/includes/header.php';
?>

<!-- Premium Article Header & Page Layout -->
<article class="article-details-page" style="background:#FAF7F0; min-height: 80vh; padding-bottom: 5rem;">
    
    <!-- Hero Banner Cover -->
    <?php if (!empty($coverImage)): ?>
        <div class="article-hero-banner" style="position: relative; height: 420px; background: linear-gradient(180deg, rgba(8, 27, 75, 0.4) 0%, rgba(8, 27, 75, 0.85) 100%), url('<?php echo htmlspecialchars($coverImage); ?>') center center / cover no-repeat; display: flex; align-items: flex-end; padding-bottom: 3rem;">
            <div class="container">
                <div class="text-white scroll-reveal">
                    <span class="badge mb-3" style="background: var(--accent-saffron); color: #081B4B; font-weight:700; padding: 0.5rem 1rem; border-radius: 999px;">
                        <?php echo htmlspecialchars($article['category_name'] ?: ($article['category'] ?: 'General')); ?>
                    </span>
                    <h1 class="display-4 fw-bold" style="font-family: var(--font-heading); line-height:1.2; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><?php echo htmlspecialchars($article['title']); ?></h1>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="py-5" style="background:#081B4B; border-bottom: 5px solid var(--accent-saffron);">
            <div class="container py-4">
                <span class="badge mb-3" style="background: var(--accent-saffron); color: #081B4B; font-weight:700; padding: 0.5rem 1rem; border-radius: 999px;">
                    <?php echo htmlspecialchars($article['category_name'] ?: ($article['category'] ?: 'General')); ?>
                </span>
                <h1 class="display-4 fw-bold text-white" style="font-family: var(--font-heading);"><?php echo htmlspecialchars($article['title']); ?></h1>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="container mt-5">
        <div class="row g-5">
            
            <!-- Left Main Column -->
            <div class="col-12 col-lg-8">
                
                <!-- Article Wrapper Card -->
                <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 24px; background:#ffffff;">
                    
                    <!-- Meta Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-4 mb-4" style="font-size:0.88rem; color:#6B7280;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-user text-primary"></i> 
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($article['author_name'] ?? 'BSFI Official'); ?></span>
                            </div>
                            <span>•</span>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-calendar"></i> 
                                <span><?php echo date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-eye"></i> 
                                <span><?php echo (int)$article['views']; ?> Views</span>
                            </div>
                            <span>•</span>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-clock"></i> 
                                <span>
                                    <?php 
                                        $wordCount = str_word_count(strip_tags($article['content']));
                                        $readTime = ceil($wordCount / 200); // 200 words per minute average
                                        echo $readTime . " min read";
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Article Body Content -->
                    <div class="article-body-content text-secondary fs-5 mb-5" style="line-height: 1.8; font-family: var(--font-body);">
                        <?php 
                            $contentHtml = $article['content'];
                            // Detect if content has HTML tags, otherwise apply formatting helpers safely
                            if (strip_tags($contentHtml) === $contentHtml) {
                                echo nl2br(htmlspecialchars($contentHtml));
                            } else {
                                // Allow safe rendering of HTML markup stored in database
                                echo $contentHtml; 
                            }
                        ?>
                    </div>

                    <!-- Extra Images Grid (GLightbox Enabled) -->
                    <?php if (count($extraImages) > 0): ?>
                        <div class="border-top pt-5">
                            <h3 class="fw-bold mb-4" style="color: #081B4B; font-family: var(--font-heading);">Event Image Gallery</h3>
                            <div class="row g-3">
                                <?php foreach ($extraImages as $img): ?>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <a href="<?php echo htmlspecialchars($img['image_path']); ?>" class="glightbox d-block hover-zoom" data-gallery="event-images" data-glightbox="title: <?php echo htmlspecialchars($img['caption'] ?? 'Event Photo'); ?>">
                                            <div style="aspect-ratio: 4/3; overflow:hidden; border-radius:16px; border:2px solid #E5E7EB; background:#F3F4F6;">
                                                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery Image" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" class="hover-zoom-img">
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Social Media Links (CMS Configured per Article) -->
                    <?php 
                        $hasSocial = !empty($article['facebook_url']) || !empty($article['instagram_url']) || !empty($article['twitter_url']) || !empty($article['linkedin_url']) || !empty($article['youtube_url']);
                        if ($hasSocial): 
                    ?>
                        <div class="border-top mt-5 pt-4">
                            <h5 class="fw-bold text-dark mb-3" style="font-size: 0.95rem;">Follow BSFI updates for this event:</h5>
                            <div class="d-flex gap-2">
                                <?php if (!empty($article['facebook_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($article['facebook_url']); ?>" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;"><i class="fa-brands fa-facebook-f"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($article['instagram_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($article['instagram_url']); ?>" target="_blank" class="btn btn-outline-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;"><i class="fa-brands fa-instagram"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($article['twitter_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($article['twitter_url']); ?>" target="_blank" class="btn btn-outline-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;"><i class="fa-brands fa-x-twitter"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($article['linkedin_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($article['linkedin_url']); ?>" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;"><i class="fa-brands fa-linkedin-in"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($article['youtube_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($article['youtube_url']); ?>" target="_blank" class="btn btn-outline-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;"><i class="fa-brands fa-youtube"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Social Sharing Buttons -->
                    <div class="border-top mt-4 pt-4 d-flex align-items-center gap-3 flex-wrap">
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">Share Article:</span>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;" title="Share on Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;" title="Share on X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($article['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0;" title="Share on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($article['title']); ?>" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding:0; border-color:#0A66C2; color:#0A66C2;" title="Share on LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div class="col-12 col-lg-4">
                
                <!-- Related Articles Sidebar -->
                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 24px; background:#ffffff;">
                    <h4 class="fw-bold text-navy mb-4" style="color: #081B4B; font-family: var(--font-heading);">Related Updates</h4>
                    
                    <?php if (count($related) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($related as $rel): 
                                $relCover = !empty($rel['thumbnail_image']) ? $rel['thumbnail_image'] : (!empty($rel['image']) ? $rel['image'] : 'assets/images/bsfi-placeholder.webp');
                            ?>
                                <div class="d-flex gap-3 align-items-center pb-3 border-bottom last-no-border" style="border-bottom: 1px dashed #E5E7EB;">
                                    <a href="news.php?slug=<?php echo urlencode($rel['slug']); ?>" style="width: 80px; height: 80px; flex-shrink: 0; border-radius: 12px; overflow:hidden; border:1px solid #E5E7EB;" class="d-block">
                                        <img src="<?php echo htmlspecialchars($relCover); ?>" alt="Related Cover" style="width: 100%; height: 100%; object-fit: cover;">
                                    </a>
                                    <div style="flex-grow:1; min-width:0;">
                                        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.68rem; color: var(--accent-saffron) !important;">
                                            <?php echo htmlspecialchars($rel['category']); ?>
                                        </span>
                                        <h6 class="fw-bold text-truncate-2 text-navy mb-1" style="font-size:0.88rem; line-height:1.3; font-family: var(--font-body);">
                                            <a href="news.php?slug=<?php echo urlencode($rel['slug']); ?>" style="color: #081B4B; text-decoration:none;" class="hover-orange"><?php echo htmlspecialchars($rel['title']); ?></a>
                                        </h6>
                                        <span style="font-size:0.75rem; color:#9CA3AF;"><i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($rel['published_at'] ?? $rel['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0 style-italic">No related articles found.</p>
                    <?php endif; ?>
                </div>

                <!-- Quick Navigation Box -->
                <div class="card border-0 shadow-sm p-4 text-center text-white" style="border-radius: 24px; background: linear-gradient(135deg, #081B4B 0%, #16295A 100%);">
                    <h5 class="fw-bold mb-2">Need Federation Support?</h5>
                    <p class="small text-white-50 mb-3">Get in touch with the state coordinators or check mandatory disclosures.</p>
                    <a href="contact.php" class="btn btn-sm btn-light w-100 rounded-pill py-2 fw-bold" style="color:#081B4B;">Contact Us</a>
                </div>

            </div>

        </div>
    </div>

</article>

<!-- Custom zoom script & GLightbox styling helpers -->
<style>
.hover-zoom:hover .hover-zoom-img {
    transform: scale(1.06);
}
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
.hover-orange:hover {
    color: var(--accent-saffron) !important;
}
.last-no-border:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if(typeof GLightbox !== 'undefined') {
        GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
