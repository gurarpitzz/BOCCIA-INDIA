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
            <div class="row mb-5">
                <div class="col-12 text-center scroll-reveal">
                    <span class="sub-label" style="text-transform: uppercase; color: #FF9933; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px;">Transparency &amp; Codes</span>
                    <h3 class="board-subtitle mt-2" style="color: #081B4B !important; font-weight: 800;">Regulatory Compliance &amp; Disclosures</h3>
                </div>
            </div>

            <!-- Documents Grid -->
            <div class="row g-4 scroll-reveal justify-content-center">
                
                <!-- Document 1: Mandatory Disclosures (existing compliance table matrix page) -->
                <div class="col-12 col-md-5">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Mandatory</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Mandatory Disclosures</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Complete compliance matrix containing 20 core disclosures and particulars under the National Sports Development Code of India 2011.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=mandatory-disclosures" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Disclosures Table &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document 2: Prevention of Fraud -->
                <div class="col-12 col-md-5">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Policies &amp; Codes</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Prevention of Fraud</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official rules and policy framework for the prevention and regulation of fraud by athletes in Para Boccia sports.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=athlete-prevention" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Policy &rarr;
                                </a>
                                <a href="uploads/documents/Regulation_of_prevention_of_fraud_by_athletes.pdf" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download Policy
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
