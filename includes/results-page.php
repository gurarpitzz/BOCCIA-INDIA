<?php
// includes/results-page.php - Frontend Competition Results Hub

$resultSchedules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE active = 1 AND result_url IS NOT NULL AND result_url != '' ORDER BY start_date DESC, id DESC");
    $stmt->execute();
    $resultSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback empty list
}

$page_title = "Competition Results | Boccia India";
$meta_desc = "Official tournament results, standings, and classification outcomes of the Boccia Sports Federation of India.";
$canonical_url = "page.php?section=competitions&slug=results";

include __DIR__ . '/header.php';
?>

<style>
.results-page {
    background: url('<?php echo htmlspecialchars($relative_prefix); ?>bg.webp') no-repeat center top / cover;
    padding-bottom: 80px;
}
.results-content-section {
    padding: 60px 0;
}
.results-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 20px;
}
@media (max-width: 991px) {
    .results-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 767px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
}
.result-card {
    background: var(--boccia-card-bg, #ffffff);
    border-radius: 20px;
    padding: 35px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}
.result-card-content h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--boccia-navy, #081B4B);
    margin-bottom: 12px;
}
.result-card-content .date-badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #FF9933;
    text-transform: uppercase;
    margin-bottom: 15px;
}
.result-card-content p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted, #64748B);
    line-height: 1.6;
    margin-bottom: 30px;
}
.result-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.result-btn-primary {
    font-family: var(--font-heading-sub);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
    background-color: var(--boccia-navy, #081B4B);
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s ease;
    border: none;
    display: block;
    width: 100%;
}
.result-btn-primary:hover {
    background-color: var(--boccia-green, #10B981);
    color: #ffffff;
}
</style>

<div class="results-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.88) 35%, rgba(7, 25, 84, 0.65) 55%, rgba(7, 25, 84, 0.35) 75%, transparent 100%), url('<?php echo htmlspecialchars($relative_prefix); ?>board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Results --</span>
                <h1 class="board-hero-title">Competition Results</h1>
                <p class="board-hero-text">
                    Official competition results published by the Boccia Sports Federation of India.
                    <br>
                    <span style="color: var(--boccia-saffron, #FF9933); font-weight: 600;">Access schedules, brackets, athlete standings, and final tournament scores.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="results-content-section">
        <div class="container">
            <?php if (count($resultSchedules) > 0): ?>
                <div class="results-grid">
                    <?php foreach ($resultSchedules as $sched): ?>
                        <div class="result-card">
                            <div class="result-card-content">
                                <span class="date-badge"><?php echo htmlspecialchars($sched['date_text']); ?></span>
                                <h4><?php echo htmlspecialchars($sched['discipline']); ?></h4>
                                <?php if ($sched['event_type']): ?>
                                    <p><?php echo htmlspecialchars($sched['event_type']); ?> (Held at <?php echo htmlspecialchars($sched['venue']); ?>)</p>
                                <?php else: ?>
                                    <p>Held at <?php echo htmlspecialchars($sched['venue']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="result-actions">
                                <a href="<?php echo htmlspecialchars($sched['result_url']); ?>" target="_blank" class="result-btn-primary">
                                    <?php echo htmlspecialchars($sched['result_button_text'] ?: 'View Results'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="result-card" style="text-align: center; padding: 5rem 2rem; border-radius: 20px;">
                    <h4 style="color: var(--boccia-navy, #081B4B); font-weight: 700; margin-bottom: 0.5rem;">No Results Published</h4>
                    <p style="color: var(--boccia-text-muted, #64748B); margin-bottom: 0;">There are no competition results published at this time. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
