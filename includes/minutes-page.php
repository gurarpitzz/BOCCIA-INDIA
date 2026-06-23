<?php
// includes/minutes-page.php - Placeholder Template for Minutes of Meetings Page
$page_title = "Minutes of Meetings | Boccia India";
$meta_desc = "Official minutes and resolutions of meetings of the Executive Committee and Annual General Meetings of BSFI.";
$canonical_url = "page.php?section=myas&slug=minutes-of-meetings";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">MINUTES OF MEETINGS</h1>
                <p class="board-hero-text">
                    Official minutes, resolutions, and logs of federation and board discussions.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            <div class="row justify-content-center scroll-reveal">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4 p-5 text-center" style="background: rgba(255, 255, 255, 0.96);">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: rgba(255, 153, 51, 0.15);">
                                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#FF9933" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-3" style="color: #081B4B; font-family: var(--font-heading);">Content Yet to be Updated</h3>
                        <p class="text-muted fs-5 mb-0" style="line-height:1.6;">
                            The official logs and minutes of meetings are currently being reviewed and formatted for compliance and will be published shortly.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
