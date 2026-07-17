<?php
// includes/compliance-regulations-docs-page.php - Compliance & Regulations sub-category documents list
$page_title = "Compliance & Regulations | Boccia India";
$meta_desc = "Mandatory disclosures, regulatory compliance and policy frameworks published by BSFI.";
$canonical_url = "page.php?section=myas&slug=compliance-regulations-docs";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">COMPLIANCE &amp; REGULATIONS</h1>
                <p class="board-hero-text">
                    Mandatory disclosures, regulatory compliance and policy frameworks published by BSFI.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            <?php include __DIR__ . '/myas/mandatory-disclosures-table.php'; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
