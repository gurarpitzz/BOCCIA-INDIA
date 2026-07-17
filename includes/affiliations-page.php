<?php
// includes/affiliations-page.php - Consolidated Affiliations Resource Hub

// Query affiliation documents from database
$affiliationDocuments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM document_pages WHERE slug IN ('affiliation-pci', 'affiliation-world-boccia', 'recognition-certificates') AND is_published = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $affiliationDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if query fails
}

if (empty($affiliationDocuments)) {
    $affiliationDocuments = [
        [
            'title' => 'Paralympic Committee of India',
            'description' => 'Official recognition and affiliation certificate of BSFI with PCI.',
            'slug' => 'affiliation-pci',
            'pdf_file' => 'uploads/documents/Affiliation_with_PCI.pdf'
        ],
        [
            'title' => 'World Boccia',
            'description' => 'Official affiliation certificate issued by World Boccia (BISFed).',
            'slug' => 'affiliation-world-boccia',
            'pdf_file' => 'uploads/documents/Affiliation_with_World_Boccia.pdf'
        ],
        [
            'title' => 'Recognition Certificates',
            'description' => 'Official government and federation recognition certificates of BSFI.',
            'slug' => 'recognition-certificates',
            'pdf_file' => 'uploads/documents/Certificate___List_of_governing_body.pdf'
        ]
    ];
}

$page_title = "Affiliations | Boccia India";
$meta_desc = "Official recognition and affiliation documents of the Boccia Sports Federation of India.";
$canonical_url = "page.php?section=about&slug=affiliations";

include __DIR__ . '/header.php';
?>

<style>
.affiliations-page {
    background: url('<?php echo htmlspecialchars($relative_prefix); ?>bg.webp') no-repeat center top / cover;
    padding-bottom: 80px;
}
.affiliations-content-section {
    padding: 60px 0;
}
.affiliations-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 20px;
}
@media (max-width: 991px) {
    .affiliations-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 767px) {
    .affiliations-grid {
        grid-template-columns: 1fr;
    }
}
.affiliation-card {
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
.affiliation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}
.affiliation-card-content h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--boccia-navy, #081B4B);
    margin-bottom: 12px;
}
.affiliation-card-content p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted, #64748B);
    line-height: 1.6;
    margin-bottom: 30px;
}
.affiliation-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.affiliation-btn-primary {
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
.affiliation-btn-primary:hover {
    background-color: var(--boccia-navy, #081B4B);
    color: #ffffff;
}
.affiliation-btn-secondary {
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
.affiliation-btn-secondary:hover {
    background-color: var(--boccia-navy, #081B4B);
    color: #ffffff;
}
</style>

<div class="affiliations-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.88) 35%, rgba(7, 25, 84, 0.65) 55%, rgba(7, 25, 84, 0.35) 75%, transparent 100%), url('<?php echo htmlspecialchars($relative_prefix); ?>board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Affiliation --</span>
                <h1 class="board-hero-title">Affiliations</h1>
                <p class="board-hero-text">
                    Official affiliation, recognition and governing body documents of the Boccia Sports Federation of India.
                    <br>
                    <span style="color: var(--boccia-saffron, #FF9933); font-weight: 600;">Access all national and international affiliation certificates from one centralized location.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="affiliations-content-section">
        <div class="container">
            <div class="affiliations-grid">
                <?php foreach ($affiliationDocuments as $doc): ?>
                    <div class="affiliation-card">
                        <div class="affiliation-card-content">
                            <h4><?php echo htmlspecialchars($doc['title']); ?></h4>
                            <p><?php echo htmlspecialchars($doc['description'] ?? ''); ?></p>
                        </div>
                        <div class="affiliation-actions">
                            <a href="<?php echo htmlspecialchars($relative_prefix); ?>page.php?section=about&slug=<?php echo urlencode($doc['slug']); ?>" class="affiliation-btn-primary">View Document</a>
                            <a href="<?php echo htmlspecialchars($relative_prefix . $doc['pdf_file']); ?>" download class="affiliation-btn-secondary">Download PDF</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
