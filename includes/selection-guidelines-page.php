<?php
// includes/selection-guidelines-page.php - Consolidated Selection Guidelines Landing Page

// Query selection guidelines documents from database
$selectionDocuments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM document_pages WHERE section_slug = 'selection-guidelines' AND is_published = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $selectionDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to hardcoded array if database query fails
}

if (empty($selectionDocuments)) {
    $selectionDocuments = [
        [
            'title' => 'Selection Policy',
            'description' => 'Official BSFI athlete selection policy, eligibility requirements, evaluation methodology, and competition participation framework.',
            'slug' => 'selection-policy',
            'pdf_file' => 'uploads/documents/Selection-Policy-___-Boccia-Asian-Para-Games-2026.pdf'
        ],
        [
            'title' => 'Boccia Asian Para Games 2026',
            'description' => 'Official selection criteria, qualification process, athlete requirements and timelines for the Boccia Asian Para Games 2026.',
            'slug' => 'apg-2026',
            'pdf_file' => 'uploads/documents/Selection-Policy-___-Boccia-Asian-Para-Games-2026.pdf'
        ],
        [
            'title' => 'Selection Trials APG 2026',
            'description' => 'Official trial schedules, athlete eligibility, evaluation standards and guidelines for APG 2026 selection events.',
            'slug' => 'apg-trials-2026',
            'pdf_file' => 'uploads/documents/Selection-trails-APG-2026-1.pdf'
        ]
    ];
}
?>

<style>
.guidelines-page {
    background-color: #F8FAFC;
    padding-bottom: 80px;
}
.guidelines-content-section {
    padding: 60px 0;
}
.guidelines-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 20px;
}
@media (max-width: 991px) {
    .guidelines-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 767px) {
    .guidelines-grid {
        grid-template-columns: 1fr;
    }
}
.guideline-card {
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
.guideline-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}
.guideline-card-content h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--boccia-navy, #081B4B);
    margin-bottom: 12px;
}
.guideline-card-content p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted, #64748B);
    line-height: 1.6;
    margin-bottom: 30px;
}
.guideline-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.guideline-btn-primary {
    font-family: var(--font-heading-sub);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
    background-color: var(--boccia-green, #10B981);
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s ease;
    border: none;
    display: block;
    width: 100%;
}
.guideline-btn-primary:hover {
    background-color: var(--boccia-navy, #081B4B);
    color: #ffffff;
}
.guideline-btn-secondary {
    font-family: var(--font-heading-sub);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--boccia-navy, #081B4B);
    background-color: transparent;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid var(--boccia-navy, #081B4B);
    display: block;
    width: 100%;
}
.guideline-btn-secondary:hover {
    background-color: var(--boccia-navy, #081B4B);
    color: #ffffff;
}
</style>

<div class="guidelines-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.88) 35%, rgba(7, 25, 84, 0.65) 55%, rgba(7, 25, 84, 0.35) 75%, transparent 100%), url('<?php echo htmlspecialchars($relative_prefix); ?>assets/images/board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Selection Guidelines --</span>
                <h1 class="board-hero-title">Selection Guidelines</h1>
                <p class="board-hero-text">
                    Official BSFI policies, selection criteria, trial schedules, qualification documents and athlete guidelines for national and international competitions.
                    <br>
                    <span style="color: var(--boccia-saffron, #FF9933); font-weight: 600;">Access every official document from one centralized location.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="guidelines-content-section">
        <div class="container">
            <div class="guidelines-grid">
                <?php foreach ($selectionDocuments as $doc): ?>
                    <div class="guideline-card">
                        <div class="guideline-card-content">
                            <h4><?php echo htmlspecialchars($doc['title']); ?></h4>
                            <p><?php echo htmlspecialchars($doc['description'] ?? ''); ?></p>
                        </div>
                        <div class="guideline-actions">
                            <a href="<?php echo htmlspecialchars($relative_prefix); ?>page.php?section=selection-guidelines&slug=<?php echo urlencode($doc['slug']); ?>" class="guideline-btn-primary">View Document</a>
                            <a href="<?php echo htmlspecialchars($relative_prefix . $doc['pdf_file']); ?>" download class="guideline-btn-secondary">Download PDF</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
