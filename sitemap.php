<?php
// sitemap.php - Dynamic XML Sitemap for BSFI
header('Content-Type: application/xml; charset=UTF-8');

require_once __DIR__ . '/includes/db.php';

$domain = 'https://bocciaindia.com';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Helper function to add a URL
function addUrl($loc, $lastmod = null, $priority = '0.5', $changefreq = 'weekly') {
    global $domain;
    if (empty($lastmod)) {
        $lastmod = date('Y-m-d');
    } else {
        $lastmod = date('Y-m-d', strtotime($lastmod));
    }
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($domain . '/' . $loc) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . htmlspecialchars($changefreq) . "</changefreq>\n";
    echo "    <priority>" . htmlspecialchars($priority) . "</priority>\n";
    echo "  </url>\n";
}

// 1. Core Pages
addUrl('', null, '1.0', 'daily');
addUrl('index.php', null, '0.9', 'daily');
addUrl('contact.php', null, '0.7', 'monthly');
addUrl('news.php', null, '0.8', 'daily');
addUrl('event-registration.php', null, '0.7', 'weekly');

// 2. Dynamic navigation paths from database (discovery navigation items)
try {
    $stmt = $pdo->query("SELECT section, slug, title FROM navigation_items WHERE slug IS NOT NULL");
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Resolve link using the exact structure from header.php
        if ($item['section'] === 'get-involved' && in_array($item['slug'], ['membership', 'players-database', 'officials-database'])) {
            addUrl("get-involved/" . $item['slug'] . ".php", null, '0.6', 'monthly');
        } elseif ($item['section'] === 'news-media' && in_array($item['slug'], ['news', 'gallery', 'videos'])) {
            if ($item['slug'] === 'videos') {
                addUrl("news-media/videos.php", null, '0.6', 'monthly');
            }
            // Skip news and gallery redirects (since they point to sections of homepage / news.php)
        } elseif ($item['section'] === 'competitions') {
            if ($item['slug'] === 'international-events') {
                // External link (worldboccia.io), skip
                continue;
            } elseif (in_array($item['slug'], ['national-events', 'state-competitions', 'results'])) {
                addUrl("page.php?section=competitions&amp;slug=" . urlencode($item['slug']), null, '0.7', 'weekly');
            }
        } else {
            // Check if it's a dynamic document page to fetch its creation timestamp for lastmod
            $docStmt = $pdo->prepare("SELECT created_at FROM document_pages WHERE slug = ? AND is_published = 1 LIMIT 1");
            $docStmt->execute([$item['slug']]);
            $docCreated = $docStmt->fetchColumn();
            
            if ($docCreated) {
                addUrl("page.php?section=" . urlencode($item['section']) . "&amp;slug=" . urlencode($item['slug']), $docCreated, '0.6', 'monthly');
            } else {
                // Verify if it's a standard static routed subpage
                addUrl("page.php?section=" . urlencode($item['section']) . "&amp;slug=" . urlencode($item['slug']), null, '0.6', 'monthly');
            }
        }
    }
} catch (Exception $e) {
    // Fail silently
}

// 3. Dynamic News Articles (published news items only)
try {
    $stmt = $pdo->query("SELECT slug, published_at, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $timestamp = !empty($row['published_at']) ? $row['published_at'] : $row['created_at'];
        addUrl('news.php?slug=' . urlencode($row['slug']), $timestamp, '0.6', 'weekly');
    }
} catch (Exception $e) {
    // Fail silently
}

echo '</urlset>' . "\n";
