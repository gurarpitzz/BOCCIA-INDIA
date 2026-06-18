<?php
// page.php - Unified Central Dynamic Routing Controller

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/document_renderer.php';

$section = trim($_GET['section'] ?? '');
$slug    = trim($_GET['slug'] ?? '');

if (empty($section) || empty($slug)) {
    header("Location: index.php");
    exit();
}

if ($section === 'about' && $slug === 'about-boccia') {
    include __DIR__ . '/includes/about-boccia-page.php';
    exit();
}

if ($section === 'about' && $slug === 'board') {
    include __DIR__ . '/includes/board-page.php';
    exit();
}

if ($section === 'about' && $slug === 'affiliation-pci') {
    include __DIR__ . '/includes/affiliation-pci-page.php';
    exit();
}

if ($section === 'about' && $slug === 'affiliation-world-boccia') {
    include __DIR__ . '/includes/affiliation-wb-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'administrative-sanction') {
    include __DIR__ . '/includes/administrative-sanction-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'financial-sanctions') {
    include __DIR__ . '/includes/financial-sanctions-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'mandatory-disclosures') {
    include __DIR__ . '/includes/mandatory-disclosures-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'athlete-prevention') {
    include __DIR__ . '/includes/athlete-prevention-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'elections') {
    include __DIR__ . '/includes/elections-page.php';
    exit();
}

if ($section === 'myas' && $slug === 'minutes-of-meetings') {
    include __DIR__ . '/includes/minutes-page.php';
    exit();
}

try {
    // 1. Fetch Page details from database
    $stmt = $pdo->prepare("SELECT * FROM site_pages WHERE section = ? AND slug = ? AND status = 'published' AND deleted_at IS NULL");
    $stmt->execute([$section, $slug]);
    $page = $stmt->fetch();

    if (!$page) {
        // Fallback: Check if there is an exact filename match in media_assets
        $stmtAsset = $pdo->prepare("SELECT * FROM media_assets WHERE filename LIKE ? AND deleted_at IS NULL");
        $stmtAsset->execute(['%' . $slug . '%']);
        $asset = $stmtAsset->fetch();

        if ($asset) {
            $page = [
                'title' => str_replace(['_', '-'], ' ', pathinfo($asset['filename'], PATHINFO_FILENAME)),
                'content' => '',
                'meta_title' => $asset['filename'],
                'meta_description' => 'Document asset ' . $asset['filename'],
                'filepath' => $asset['filepath'],
                'mime_type' => $asset['mime_type']
            ];
        } else {
            // Render 404
            $page_title = "404 - Page Not Found";
            include __DIR__ . '/includes/header.php';
            echo "<div class='container my-5 py-5 text-center'>";
            echo "<h1 class='display-1 text-primary fw-bold'>404</h1>";
            echo "<h3 class='text-dark'>Page Not Found</h3>";
            echo "<p class='text-muted'>The requested page in section '$section' with slug '$slug' does not exist or has been archived.</p>";
            echo "<a href='index.php' class='btn btn-primary mt-3'>Return to Home Page</a>";
            echo "</div>";
            include __DIR__ . '/includes/footer.php';
            exit();
        }
    }

    // Load Header details
    $page_title = $page['meta_title'] ?? $page['title'] . " - Boccia India";
    $meta_desc = $page['meta_description'] ?? "Official BSFI document and information registry page.";
    include __DIR__ . '/includes/header.php';

} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}
?>

<!-- Premium Page Layout Container -->
<div class="container my-5 py-4" style="min-height: 60vh;">
    <div class="row">
        <!-- Main Content Column -->
        <div class="col-lg-9 mx-auto">
            
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-light p-3 rounded-pill" style="font-size:0.9rem;">
                    <li class="breadcrumb-item"><a href="index.php" style="color:var(--primary-navy); font-weight:600; text-decoration:none;">Home</a></li>
                    <li class="breadcrumb-item text-capitalize" style="color:var(--accent-saffron); font-weight:600;"><?php echo htmlspecialchars(str_replace('-', ' ', $section)); ?></li>
                    <li class="breadcrumb-item active text-truncate" aria-current="page"><?php echo htmlspecialchars($page['title']); ?></li>
                </ol>
            </nav>

            <!-- Page Title Header -->
            <div class="mb-5 border-bottom pb-3">
                <h1 class="display-5 text-dark fw-bold" style="font-family: var(--font-heading); color: #081B4B !important;"><?php echo htmlspecialchars($page['title']); ?></h1>
            </div>

            <!-- Page Text Body -->
            <?php if (!empty($page['content'])): ?>
                <div class="page-content-wrapper mb-5 fs-5 text-secondary" style="line-height:1.8; white-space: pre-wrap;">
                    <?php echo htmlspecialchars_decode($page['content']); ?>
                </div>
            <?php endif; ?>

            <!-- Document Render block -->
            <?php 
            if (isset($page['filepath'])) {
                // If it is a direct media asset link
                echo DocumentRenderer::render($page['filepath'], $page['mime_type']);
            } else {
                // Check if any media documents are attached or map to the exact same slug/section name
                try {
                    $cleanedSlugPattern = '%' . str_replace('-', '_', $slug) . '%';
                    $docsStmt = $pdo->prepare("SELECT * FROM media_assets WHERE filename LIKE ? AND mime_type = 'application/pdf' AND deleted_at IS NULL LIMIT 5");
                    $docsStmt->execute([$cleanedSlugPattern]);
                    $docs = $docsStmt->fetchAll();

                    foreach ($docs as $doc) {
                        echo DocumentRenderer::render($doc['filepath'], $doc['mime_type']);
                    }
                } catch (PDOException $e) {}
            }
            ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
