<?php
// sitemap.php - Dynamic XML Sitemap for BSFI
header("Content-Type: application/xml; charset=utf-8");

require_once __DIR__ . '/includes/db.php';

$domain = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Helper function to add a URL
function addUrl($loc, $priority = '0.5', $changefreq = 'weekly') {
    global $domain;
    $date = date('Y-m-d');
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($domain . '/' . $loc) . '</loc>' . "\n";
    echo '    <lastmod>' . $date . '</lastmod>' . "\n";
    echo '    <changefreq>' . htmlspecialchars($changefreq) . '</changefreq>' . "\n";
    echo '    <priority>' . htmlspecialchars($priority) . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// 1. Static Core Pages
addUrl('', '1.0', 'daily');
addUrl('index.php', '0.9', 'daily');
addUrl('contact.php', '0.7', 'monthly');
addUrl('news.php', '0.8', 'daily');
addUrl('event-registration.php', '0.7', 'weekly');

// 2. Competitions Pages
addUrl('page.php?section=competitions&amp;slug=international-events', '0.7', 'weekly');
addUrl('page.php?section=competitions&amp;slug=national-events', '0.7', 'weekly');
addUrl('page.php?section=competitions&amp;slug=state-competitions', '0.7', 'weekly');
addUrl('page.php?section=competitions&amp;slug=results', '0.7', 'weekly');

// 3. Dynamic document pages from database
try {
    $stmt = $pdo->query("SELECT section_slug, slug FROM document_pages WHERE is_published = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl('page.php?section=' . urlencode($row['section_slug']) . '&amp;slug=' . urlencode($row['slug']), '0.6', 'monthly');
    }
} catch (Exception $e) {
    // Fail silently
}

// 4. Dynamic news articles from database
try {
    $stmt = $pdo->query("SELECT slug FROM news WHERE status = 'published' ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl('news.php?slug=' . urlencode($row['slug']), '0.6', 'weekly');
    }
} catch (Exception $e) {
    // Fail silently
}

echo '</urlset>' . "\n";
