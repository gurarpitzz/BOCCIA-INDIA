<?php
// includes/affiliations-page.php - Dynamic Affiliations Document Hub template
$page_title = "Affiliations | Boccia India";
$meta_desc = "Official recognition and affiliation documents of the Boccia Sports Federation of India.";
$canonical_url = "page.php?section=about&slug=affiliations";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Affiliation --</span>
                <h1 class="board-hero-title">AFFILIATIONS</h1>
                <p class="board-hero-text">
                    Official recognition and affiliation documents of the Boccia Sports Federation of India.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <div class="row mb-5">
                <div class="col-12 text-center scroll-reveal">
                    <span class="sub-label" style="text-transform: uppercase; color: #FF9933; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px;">Official Affiliations</span>
                    <h3 class="board-subtitle mt-2" style="color: #081B4B !important; font-weight: 800;">Official recognition documents issued by national and international bodies.</h3>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="row g-4 scroll-reveal">
                
                <!-- Card 1: PCI -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">National Affiliation</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Paralympic Committee of India</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official recognition and affiliation certificate issued by the Paralympic Committee of India.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=about&slug=affiliation-pci" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Document &rarr;
                                </a>
                                <a href="uploads/documents/Affiliation_with_PCI.pdf" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: World Boccia -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">International Affiliation</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">World Boccia</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official international affiliation certificate with International BSF (World Boccia).</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=about&slug=affiliation-world-boccia" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Document &rarr;
                                </a>
                                <a href="uploads/documents/Affiliation_with_World_Boccia.pdf" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Recognition Certificates -->
                <div class="col-12 col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.72rem;">Government/Federation</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4; color: #081B4B !important;">Recognition Certificates</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Additional government, ministry, and sports council recognition certificates for BSFI.</p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="page.php?section=about&slug=recognition-certificates" class="btn btn-primary rounded-pill fw-bold w-100" style="background: #081B4B; border-color: #081B4B; color: #ffffff;">
                                    View Document &rarr;
                                </a>
                                <a href="uploads/documents/Certificate___List_of_governing_body.pdf" download class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download
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
