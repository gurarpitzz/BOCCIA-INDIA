<?php
// includes/elections-page.php - Placeholder Template for Elections Page
$page_title = "Elections | Boccia India";
$meta_desc = "Official election notifications, voter lists, and result updates from the Boccia Sports Federation of India (BSFI).";
$canonical_url = "page.php?section=myas&slug=elections";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">ELECTIONS</h1>
                <p class="board-hero-text">
                    Official records of governance, voter registers, and federation election results.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            <div class="row justify-content-center scroll-reveal">
                <div class="col-md-6 col-lg-5">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;">Elections 2022-23</span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4;">Certificate and Governing Board List</h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;">Official certificate and details of the governing board election results.</p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="uploads/documents/Certificate_and_Governing_Board_list_2-3.pdf" download class="btn btn-outline-primary rounded-pill fw-bold" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
