<?php
// includes/document-view-page.php - Premium Document & Policy Viewer Card Template
// Used for displaying individual documents on demand with standard metadata sidebar.

require_once __DIR__ . '/document_renderer.php';

// Safe fallbacks if metadata is missing
$doc_title = $doc_title ?? ($docPage['title'] ?? 'Document Registry');
$doc_subtitle = $doc_subtitle ?? ($docPage['subtitle'] ?? 'Official Publication');
$doc_desc = $doc_desc ?? ($docPage['description'] ?? '');
$pdf_file = $pdf_file ?? ($docPage['pdf_file'] ?? '');
$heroBg = $heroBg ?? (!empty($docPage['hero_image']) ? $docPage['hero_image'] : 'board/board_bg.webp');

// Dynamic details metadata
$doc_date = $doc_date ?? 'Published';
$doc_dept = $doc_dept ?? 'BSFI Secretariat';
$doc_type = $doc_type ?? 'Official Certificate / Policy';

$view_pdf = isset($_GET['view']) && $_GET['view'] === '1';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('<?php echo htmlspecialchars($heroBg); ?>');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- <?php echo htmlspecialchars($doc_subtitle); ?> --</span>
                <h1 class="board-hero-title"><?php echo htmlspecialchars($doc_title); ?></h1>
                <?php if (!empty($doc_desc)): ?>
                    <p class="board-hero-text">
                        <?php echo htmlspecialchars($doc_desc); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section">
        <div class="container">
            
            <div class="row g-5">
                
                <!-- Left: Document Overview & Metadata -->
                <div class="col-lg-4 scroll-reveal">
                    <div class="card border-0 shadow-sm rounded-4 p-4" style="background: rgba(255, 255, 255, 0.95);">
                        <h4 class="fw-bold text-dark mb-4 pb-2 border-bottom" style="font-family: var(--font-heading); color: #081B4B !important;">Document Information</h4>
                        
                        <div class="mb-3">
                            <label class="text-secondary fw-semibold text-uppercase" style="font-size:0.75rem;">Department / Issuer</label>
                            <div class="text-dark fw-bold" style="font-size:1rem;"><?php echo htmlspecialchars($doc_dept); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary fw-semibold text-uppercase" style="font-size:0.75rem;">Document Type</label>
                            <div class="text-dark fw-bold" style="font-size:1rem;"><?php echo htmlspecialchars($doc_type); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="text-secondary fw-semibold text-uppercase" style="font-size:0.75rem;">Publication Status</label>
                            <div class="text-dark fw-bold" style="font-size:1rem;"><i class="bi bi-patch-check-fill text-success me-1"></i> Verified Active</div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2">
                            <?php if (!$view_pdf): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'view=1'; ?>" class="btn btn-primary rounded-pill fw-bold py-2" style="background: #081B4B; border-color: #081B4B;">
                                    <i class="bi bi-eye-fill me-1"></i> View Document
                                </a>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars(str_replace(['&view=1', '?view=1'], '', $_SERVER['REQUEST_URI'])); ?>" class="btn btn-outline-secondary rounded-pill fw-bold py-2">
                                    <i class="bi bi-eye-slash-fill me-1"></i> Hide Preview
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars($pdf_file); ?>" download class="btn btn-outline-primary rounded-pill fw-bold py-2" style="border: 2px solid #FF9933; color: #FF9933;">
                                <i class="bi bi-download me-1"></i> Download PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right: Content View Area (Only displays PDF when requested) -->
                <div class="col-lg-8 scroll-reveal">
                    <?php if ($view_pdf): ?>
                        <div class="section-title-wrapper mb-4">
                            <span class="sub-label">Interactive Preview</span>
                            <h3 class="board-subtitle" style="color: #081B4B !important;">Document Preview</h3>
                        </div>
                        <?php echo DocumentRenderer::render($pdf_file); ?>
                    <?php else: ?>
                        <div class="p-5 border border-dashed rounded-4 text-center bg-light shadow-sm" style="border-width: 2px;">
                            <div class="display-3 text-secondary mb-3"><i class="bi bi-file-earmark-pdf-fill" style="color: #ea4335;"></i></div>
                            <h4 class="fw-bold text-dark">Document Ready for Viewing</h4>
                            <p class="text-muted">This document is loaded on demand. Click "View Document" in the panel to open the interactive PDF reader inside this page, or click "Download PDF" to save it locally.</p>
                            <a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'view=1'; ?>" class="btn btn-primary rounded-pill fw-bold px-4 py-2 mt-2" style="background: #081B4B; border-color: #081B4B;">
                                <i class="bi bi-eye-fill me-1"></i> View Document
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </section>
</div>
