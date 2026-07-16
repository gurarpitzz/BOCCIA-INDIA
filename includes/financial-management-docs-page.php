<?php
// includes/financial-management-docs-page.php - Financial Management sub-category documents list
$page_title = "Financial Management Documents | Boccia India";
$meta_desc = "Financial approvals, government sanctions and related financial disclosures of BSFI.";
$canonical_url = "page.php?section=myas&slug=financial-management-docs";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">FINANCIAL MANAGEMENT</h1>
                <p class="board-hero-text">
                    Financial approvals, government sanctions and related financial disclosures of BSFI.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center scroll-reveal">
                    <span class="sub-label" style="text-transform: uppercase; color: #FF9933; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px;">Financial Disclosures</span>
                    <h3 class="board-subtitle mt-2" style="color: #081B4B !important; font-weight: 800;">Sanctions & Financial Disclosures</h3>
                </div>
            </div>

            <!-- Documents Grid -->
            <div class="row g-4 scroll-reveal justify-content-center">
                
                <!-- Document 1: Financial Sanctions -->
                <div class="col-12 col-md-5">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Approvals</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Financial Sanctions</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official financial approvals, grants-in-aid, and event sanction clearances from the Ministry of Youth Affairs and Sports.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=financial-sanctions" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Documents &rarr;
                                </a>
                                <a href="uploads/documents/Financial-sanction-boccia.pdf" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download All
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
