<?php
// includes/myas-disclosures-page.php - Dynamic MYAS Disclosures category hub
$page_title = "MYAS Disclosures | Boccia India";
$meta_desc = "Government transparency and compliance disclosures published by Boccia Sports Federation of India.";
$canonical_url = "page.php?section=myas&slug=disclosures";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Compliance --</span>
                <h1 class="board-hero-title">MYAS DISCLOSURES</h1>
                <p class="board-hero-text">
                    Government transparency and compliance documents published by BSFI.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <div class="row mb-5">
                <div class="col-12 text-center scroll-reveal">
                    <span class="sub-label" style="text-transform: uppercase; color: #FF9933; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px;">Federation Compliance</span>
                    <h3 class="board-subtitle mt-2" style="color: #081B4B !important; font-weight: 800;">Official Disclosures & Sanctions</h3>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="row g-4 scroll-reveal">
                
                <!-- Category 1: Governance -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Administration</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Governance</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Documents relating to the administration, governance, and decision-making of the federation including Administrative Sanctions, Elections, and Minutes.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=governance-docs" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Documents &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 2: Financial Management -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Accounts & Budgets</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Financial Management</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Financial approvals, audits, government sanctions, statements of accounts, utilization certificates, and budgets.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=financial-sanctions" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Documents &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 3: Compliance & Regulations -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Legal & Codes</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Compliance & Regulations</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Mandatory disclosures under the National Sports Development Code of India, anti-fraud regulations, and policy frameworks.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=myas&slug=compliance-regulations-docs" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Documents &rarr;
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
