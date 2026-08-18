<?php
// includes/yearly-audit-page.php - Custom Template for MYAS Yearly Audits & 3 Years ITR
$page_title = "Yearly Audit & 3-Year ITR | Boccia India";
$meta_desc = "Audited financial statements, donation certificates, and Income Tax Returns (ITR) of Boccia Sports Federation of India for the last 3 financial years.";
$canonical_url = "page.php?section=myas&slug=yearly-audit";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">YEARLY AUDITS & ITR</h1>
                <p class="board-hero-text">
                    Audited statements of accounts and Income Tax Returns (ITR) for the last 3 financial years.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <?php
            $audits = [
                [
                    'badge' => 'AY 2023–24 ITR Assessment',
                    'year' => '2023 - 2024',
                    'title' => 'ITR Assessment Filing 2023-24',
                    'desc' => 'Official Income Tax Return (ITR) filing, acknowledgment, and audited financial statements for Assessment Year 2023-24.',
                    'file' => 'uploads/documents/ITR_Assessment_2023-24.pdf'
                ],
                [
                    'badge' => 'AY 2024–25 ITR Assessment',
                    'year' => '2024 - 2025',
                    'title' => 'ITR Assessment Filing 2024-25',
                    'desc' => 'Official Income Tax Return (ITR) filing, acknowledgment, and audited financial statements for Assessment Year 2024-25.',
                    'file' => 'uploads/documents/ITR_Assessment_2024-25.pdf'
                ],
                [
                    'badge' => 'AY 2025–26 Income Tax Return',
                    'year' => '2025 - 2026',
                    'title' => 'Income Tax Return (ITR) 2025-26',
                    'desc' => 'Official Income Tax Return (ITR) filing and audited accounts summary of Boccia Sports Federation of India for Assessment Year 2025-26.',
                    'file' => 'uploads/documents/ITR_2025-26.pdf'
                ]
            ];
            ?>
            
            <!-- Downloadable Section (Grid of Cards) -->
            <div class="row g-4 mb-5 scroll-reveal">
                <div class="col-12">
                    <div class="section-title-wrapper text-center mb-4">
                        <span class="sub-label" style="text-transform: uppercase; color: #FF9933; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px;">Financial Transparency</span>
                        <h3 class="board-subtitle mt-2" style="color: #081B4B !important; font-weight: 800;">3-Year Audited Accounts &amp; Income Tax Returns</h3>
                    </div>
                </div>
                
                <?php foreach ($audits as $audit): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;"><?= htmlspecialchars($audit['badge']) ?></span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;"><?= htmlspecialchars($audit['title']) ?></h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;"><?= htmlspecialchars($audit['desc']) ?></p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="<?= htmlspecialchars($audit['file']) ?>" target="_blank" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Document &rarr;
                                </a>
                                <a href="<?= htmlspecialchars($audit['file']) ?>" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
