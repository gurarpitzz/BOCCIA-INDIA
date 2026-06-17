<?php
// competitions/national-events.php - Events circulars & schedules

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/document_renderer.php';

try {
    // Fetch all events
    $stmt = $pdo->query("SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_date DESC");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "National Events & Calendar - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4" style="min-height: 70vh;">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">National Tournaments &amp; Events</h1>
        <p class="text-muted">Official calendar, selection trials, circulars, and schedules for national tournaments.</p>
    </div>

    <?php if (count($events) > 0): ?>
        <div class="row g-4">
            <?php foreach ($events as $event): 
                // Fetch documents associated with this event
                $docStmt = $pdo->prepare("SELECT ed.*, ma.filepath, ma.mime_type FROM event_documents ed JOIN media_assets ma ON ed.media_asset_id = ma.id WHERE ed.event_id = ?");
                $docStmt->execute([$event['id']]);
                $docs = $docStmt->fetchAll();
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white p-4 mb-4">
                        <div class="row align-items-center">
                            
                            <!-- Left: Event Meta info -->
                            <div class="col-md-5 mb-3 mb-md-0 border-end pe-md-4">
                                <span class="badge bg-light text-primary mb-2 text-uppercase fw-bold"><?php echo htmlspecialchars($event['status']); ?></span>
                                <h3 class="fw-bold text-dark mb-3" style="font-family: var(--font-heading); color:#081B4B !important;"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="text-secondary mb-3"><?php echo htmlspecialchars($event['description'] ?? 'National tournament under BSFI.'); ?></p>
                                <p class="m-0 text-muted" style="font-size:0.9rem;">
                                    <strong>📍 Venue:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                    <strong>🗓️ Dates:</strong> <?php echo date('M j, Y', strtotime($event['start_date'])); ?> – <?php echo date('M j, Y', strtotime($event['end_date'])); ?>
                                </p>
                            </div>

                            <!-- Right: Associated Documents / Circulars -->
                            <div class="col-md-7 ps-md-4">
                                <h5 class="fw-bold mb-3 text-secondary">Circulars &amp; Results</h5>
                                <?php if (count($docs) > 0): ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($docs as $doc): ?>
                                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center border">
                                                <div>
                                                    <strong class="d-block text-dark"><?php echo htmlspecialchars($doc['title']); ?></strong>
                                                    <span class="badge bg-secondary text-uppercase" style="font-size:0.75rem;"><?php echo htmlspecialchars($doc['doc_type']); ?></span>
                                                </div>
                                                <a href="<?php echo htmlspecialchars($logo_path . $doc['filepath']); ?>" download class="btn btn-sm btn-primary">Download File</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted m-0">No documents or circulars uploaded for this tournament yet.</p>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-5 text-center bg-light rounded-4 border">
            <p class="text-muted fs-5 m-0">Dynamic national calendar events are currently being synchronized. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
