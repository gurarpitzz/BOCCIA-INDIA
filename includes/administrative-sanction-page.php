<?php
// includes/administrative-sanction-page.php - Custom Template for MYAS Administrative Sanctions
$page_title = "Administrative Sanctions | Boccia India";
$meta_desc = "Official administrative and financial sanctions issued by the Ministry of Youth Affairs and Sports (MYAS) for Para Boccia events.";
$canonical_url = "page.php?section=myas&slug=administrative-sanction";

include __DIR__ . '/header.php';
require_once __DIR__ . '/document_renderer.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">ADMINISTRATIVE SANCTIONS</h1>
                <p class="board-hero-text">
                    Official sanctions and clearance records from the Ministry of Youth Affairs and Sports.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <!-- Downloadable Section (Grid of 3 Cards) -->
            <div class="row g-4 mb-5 scroll-reveal">
                <div class="col-12">
                    <div class="section-title-wrapper text-center mb-4">
                        <span class="sub-label">Event Clearances</span>
                        <h3 class="board-subtitle" style="color: #081B4B !important;">Downloadable Documents</h3>
                    </div>
                </div>
                
                <!-- Poland Sanction -->
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;">Poland 2022-23</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4;">Administrative Sanction FCC-67</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official sanction document for the Para Boccia event in Poland.</p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="uploads/documents/ADMINISTRATIVE_SANCTION_NO_FCC_67_2022-23_PARA_BOCCIA_POLAND-2.pdf" download class="btn btn-outline-primary rounded-pill fw-bold" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Italy Sanction -->
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;">Italy 2022-23</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4;">Administrative Sanction FCC-142</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official sanction document for the Para Boccia event in Italy.</p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="uploads/documents/ADMINISTRATIVE_SANCTION_NO_FCC-142-2022-23_FOR_PARA_BOCCIA_AT_ITALY.pdf" download class="btn btn-outline-primary rounded-pill fw-bold" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solan Sanction -->
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;">Himachal Pradesh 2022-23</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4;">Administrative Sanction NCC-112</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official sanction document for Para Boccia at Solan, Himachal Pradesh.</p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="uploads/documents/ADMINISTRATIVE_SANCTION_NO._NCC-112-2022-23_FOR_PARA_BOCCIA_AT_SOLAN_HIMACLAH_PRADESH.pdf" download class="btn btn-outline-primary rounded-pill fw-bold" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Readable PDF Section -->
            <div class="row scroll-reveal mt-5">
                <div class="col-12">
                    <div class="section-title-wrapper text-center mb-4">
                        <span class="sub-label">Official Overview</span>
                        <h3 class="board-subtitle" style="color: #081B4B !important;">Administrative Sanction Overview</h3>
                    </div>
                    <?php echo DocumentRenderer::render('uploads/documents/Administrative-sanction-Boccia.pdf'); ?>
                </div>
            </div>
            
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
