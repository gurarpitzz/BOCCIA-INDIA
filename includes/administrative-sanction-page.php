<?php
// includes/administrative-sanction-page.php - Custom Template for MYAS Administrative Sanctions
$page_title = "Administrative Sanctions | Boccia India";
$meta_desc = "Official administrative and financial sanctions issued by the Ministry of Youth Affairs and Sports (MYAS) for Para Boccia events.";
$canonical_url = "page.php?section=myas&slug=administrative-sanction";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
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
            
            <?php
            $sanctions = [
                [
                    'badge' => 'Coaching Camp',
                    'title' => 'Administrative Sanction No. 67',
                    'desc' => 'Administrative sanction for Para Powerlifting, Para Boccia & Para Lawn Ball National Coaching Camp.',
                    'file' => 'uploads/documents/ADMINISTRATIVE_SANCTION_NO_67_NATIONAL_COACHING_CAMP.pdf'
                ],
                [
                    'badge' => 'Poland 2022-23',
                    'title' => 'Administrative Sanction FCC-67',
                    'desc' => 'Official administrative sanction document for the Para Boccia event in Poland.',
                    'file' => 'uploads/documents/ADMINISTRATIVE_SANCTION_NO_FCC_67_2022-23_PARA_BOCCIA_POLAND.pdf'
                ],
                [
                    'badge' => 'Italy 2022-23',
                    'title' => 'Administrative Sanction FCC-142',
                    'desc' => 'Official administrative sanction document for the Para Boccia event in Italy.',
                    'file' => 'uploads/documents/ADMINISTRATIVE_SANCTION_NO_FCC_142_2022-23_FOR_PARA_BOCCIA_AT_ITALY.pdf'
                ],
                [
                    'badge' => 'Himachal Pradesh 2022-23',
                    'title' => 'Administrative Sanction NCC-50',
                    'desc' => 'Official administrative sanction document for Para Boccia at Solan, Himachal Pradesh.',
                    'file' => 'uploads/documents/ADMINISTRATIVE_SANCTION_NO_NCC_50_2022_23_FOR_PARA_BOCCIA_AT_SOLAN.pdf'
                ],
                [
                    'badge' => 'Himachal Pradesh 2022-23',
                    'title' => 'Administrative Sanction NCC-112',
                    'desc' => 'Official administrative sanction document for Para Boccia at Solan, Himachal Pradesh.',
                    'file' => 'uploads/documents/ADMINISTRATIVE_SANCTION_NO_NCC_112_2022_23_FOR_PARA_BOCCIA_AT_SOLAN_HIMACHAL_PRADESH.pdf'
                ],
                [
                    'badge' => 'Financial Sanction',
                    'title' => 'Financial Sanction FCC-67',
                    'desc' => 'Official financial sanction document of Para Boccia.',
                    'file' => 'uploads/documents/FINANCIAL_SANCTION_FCC_67_OF_PARA_BOCCIA.pdf'
                ],
                [
                    'badge' => 'Italy 2022-23 (Financial)',
                    'title' => 'Financial Sanction FCC-142',
                    'desc' => 'Official financial sanction document for Para Boccia at Italy.',
                    'file' => 'uploads/documents/FINANCIAL_SANCTION_NO_FCC_142_2022_23_FOR_PARA_BOCCIA_AT_ITALY.pdf'
                ],
                [
                    'badge' => 'Finland 2024',
                    'title' => 'Pajulahti Challenger Clearance',
                    'desc' => 'Clearance/sanction for Para Boccia team in the Pajulahti 2024 World Boccia Challenger, Finland.',
                    'file' => 'uploads/documents/PARA_BOCCIA_TEAM_PAJULAHTI_2024_WORLD_BOCCIA_CHALLENGER_FINLAND.pdf'
                ],
                [
                    'badge' => 'Disclosures',
                    'title' => 'Supporting Document',
                    'desc' => 'Supporting document for administrative sanctions and clearances.',
                    'file' => 'uploads/documents/SUPPORTING_DOCUMENT_ADMINISTRATIVE_SANCTION.pdf'
                ]
            ];
            ?>
            
            <!-- Downloadable Section (Grid of Cards) -->
            <div class="row g-4 mb-5 scroll-reveal">
                <div class="col-12">
                    <div class="section-title-wrapper text-center mb-4">
                        <span class="sub-label">Event Clearances</span>
                        <h3 class="board-subtitle" style="color: #081B4B !important;">Downloadable Documents</h3>
                    </div>
                </div>
                
                <?php foreach ($sanctions as $sanction): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); transition: transform 0.2s;">
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size:0.75rem;"><?= htmlspecialchars($sanction['badge']) ?></span>
                                <h5 class="card-title fw-bold text-dark mb-3" style="line-height:1.4;"><?= htmlspecialchars($sanction['title']) ?></h5>
                                <p class="card-text text-muted mb-4" style="font-size:0.9rem;"><?= htmlspecialchars($sanction['desc']) ?></p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?= htmlspecialchars($sanction['file']) ?>" download class="btn btn-outline-primary rounded-pill fw-bold" style="border: 2px solid #FF9933; color: #FF9933;">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </section>
</div>

<?php
include __DIR__ . '/footer.php';
?>
