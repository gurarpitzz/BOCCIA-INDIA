<?php
// includes/affiliation-pci-page.php - Custom Template for Affiliation with PCI
$page_title = "Affiliation With PCI | Boccia India";
$meta_desc = "Official affiliation details of the Boccia Sports Federation of India (BSFI) with the Paralympic Committee of India (PCI).";
$canonical_url = "page.php?section=about&slug=affiliation-pci";

include __DIR__ . '/header.php';
require_once __DIR__ . '/document_renderer.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.png');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Affiliation --</span>
                <h1 class="board-hero-title">PARALYMPIC COMMITTEE OF INDIA</h1>
                <p class="board-hero-text">
                    Official recognition and affiliation documentation of BSFI with the Paralympic Committee of India (PCI).
                </p>
            </div>
        </div>
    </section>

    <!-- Document Section -->
    <section class="board-section">
        <div class="container">
            <div class="scroll-reveal">
                <?php echo DocumentRenderer::render('uploads/documents/Affiliation_with_PCI.pdf'); ?>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
