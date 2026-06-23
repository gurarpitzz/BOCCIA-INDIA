<?php
// includes/financial-sanctions-page.php - Custom Template for MYAS Financial Sanctions
$page_title = "Financial Sanctions | Boccia India";
$meta_desc = "Official financial sanction details issued by the Ministry of Youth Affairs and Sports (MYAS) for Para Boccia events.";
$canonical_url = "page.php?section=myas&slug=financial-sanctions";

include __DIR__ . '/header.php';
require_once __DIR__ . '/document_renderer.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">FINANCIAL SANCTIONS</h1>
                <p class="board-hero-text">
                    Official financial sanction logs and compliance records approved by the Ministry of Youth Affairs and Sports.
                </p>
            </div>
        </div>
    </section>

    <!-- Document Section -->
    <section class="board-section">
        <div class="container">
            <div class="scroll-reveal">
                <div class="section-title-wrapper text-center mb-4">
                    <span class="sub-label">Event Budget & Allocations</span>
                    <h3 class="board-subtitle" style="color: #081B4B !important;">Financial Sanction FCC-67</h3>
                </div>
                <?php echo DocumentRenderer::render('uploads/documents/FINANCIAL_SANCTION_FCC-67_OF_PARA_BOCCIA.pdf'); ?>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
