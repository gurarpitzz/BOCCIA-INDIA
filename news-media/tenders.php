<?php
// news-media/tenders.php - Tenders Portal

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/document_renderer.php';

try {
    // Fetch tender documents
    $stmt = $pdo->query("SELECT * FROM media_assets WHERE (filename LIKE '%tender%' OR filepath LIKE '%tender%') AND deleted_at IS NULL ORDER BY uploaded_at DESC");
    $tenders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Procurement Tenders & Notices - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4" style="min-height: 70vh;">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">Procurement &amp; Tenders</h1>
        <p class="text-muted">Official procurement notices, RFP documents, and tender circulars from BSFI.</p>
    </div>

    <?php if (count($tenders) > 0): ?>
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <?php foreach ($tenders as $tender): 
                    $cleanTitle = str_replace(['_', '-'], ' ', pathinfo($tender['filename'], PATHINFO_FILENAME));
                ?>
                    <div class="mb-5">
                        <h4 class="fw-bold mb-3" style="color:var(--primary-navy);"><?php echo htmlspecialchars($cleanTitle); ?></h4>
                        <?php echo DocumentRenderer::render($tender['filepath'], $tender['mime_type']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="p-5 text-center bg-light rounded-4 border">
            <p class="text-muted fs-5 m-0">No active procurement notices or tenders published at this time.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
