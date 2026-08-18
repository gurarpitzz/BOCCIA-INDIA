<?php
// includes/board-page.php - Custom Assembler Template for Board of Directors
$page_title = "Governing Body | Boccia India";
$meta_desc = "Meet the leadership team guiding the growth, governance, and future of Boccia in India. Explore the Executive Committee and view official federation documentation.";
$canonical_url = "page.php?section=about&slug=board";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Governing Body --</span>
                <h1 class="board-hero-title">BOARD OF DIRECTORS</h1>
                <p class="board-hero-text">
                    The leadership team guiding the growth, governance, and future of Boccia in India.
                </p>
            </div>
        </div>
    </section>

    <!-- 1. Chairman Spotlight Section -->
    <section class="board-section chairman-section pb-0">
        <div class="container">
            <div class="president-spotlight scroll-reveal">
                <div class="spotlight-card">
                    <div class="row align-items-center g-0">
                        <div class="col-md-5 col-lg-4 d-flex justify-content-center">
                            <div class="spotlight-img-wrapper">
                                <img src="gallery/board/ashok-bedi.jpg" alt="Ashok Bedi" class="spotlight-img" loading="lazy">
                            </div>
                        </div>
                        <div class="col-md-7 col-lg-8">
                            <div class="spotlight-info">
                                <span class="spotlight-badge" style="background: #E2E8F0; color: #081B4B;">Chairman</span>
                                <h2 class="spotlight-name">ASHOK BEDI</h2>
                                <h3 class="spotlight-title">Chairman &amp; Vice President, BSFI</h3>
                                <p class="spotlight-desc">Guiding the overall vision, strategic governance, and national expansion of the Boccia Sports Federation of India. Under his chairmanship, BSFI has established robust development programs, national training camps, and grassroots sports infrastructure across the country.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. President Spotlight Section -->
    <section class="board-section president-section">
        <div class="container">
            <div class="president-spotlight scroll-reveal">
                <div class="spotlight-card">
                    <div class="row align-items-center g-0">
                        <div class="col-md-5 col-lg-4 d-flex justify-content-center">
                            <div class="spotlight-img-wrapper">
                                <img src="gallery/board/jaspreet-singh.jpg" alt="Jaspreet Singh" class="spotlight-img" loading="lazy">
                            </div>
                        </div>
                        <div class="col-md-7 col-lg-8">
                            <div class="spotlight-info">
                                <span class="spotlight-badge">President</span>
                                <h2 class="spotlight-name">JASPREET SINGH</h2>
                                <h3 class="spotlight-title">President, BSFI</h3>
                                <p class="spotlight-desc">The pioneering development and introduction of Boccia in India is widely credited to Jaspreet Singh Dhaliwal. He established the sport nationally, founded the federation (initially the Para Boccia Sports Welfare Society, now the Boccia Sports Federation of India), secured recognition from World Boccia (BISFed) and the Paralympic Committee of India, and continues to drive India's national championships and international representation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Leadership Team (3-Column Desktop Grid) -->
    <section class="board-section leadership-section">
        <div class="container">
            <div class="section-title-wrapper scroll-reveal text-center">
                <div class="title-divider-top"></div>
                <span class="sub-label">EXECUTIVE OFFICERS</span>
                <h3 class="board-subtitle">Leadership Team</h3>
                <div class="title-divider-bottom"></div>
            </div>
            
            <div class="board-grid-leadership scroll-reveal">
                <!-- Gursharan Singh -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/gursharan-singh.jpg" alt="Gursharan Singh" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">Gursharan Singh</h4>
                        <p class="board-member-role">Vice President</p>
                    </div>
                </div>

                <!-- Shaminder Singh Dhillon -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/shaminder-singh.jpg" alt="Shaminder Singh Dhillon" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">Shaminder Singh Dhillon</h4>
                        <p class="board-member-role">Secretary General</p>
                    </div>
                </div>

                <!-- Jagroop Singh -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/jagroop-singh.jpg" alt="Jagroop Singh" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">Jagroop Singh</h4>
                        <p class="board-member-role">Treasurer</p>
                    </div>
                </div>

                <!-- Manpreet -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/manpreet-singh.jpg" alt="Manpreet" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">Manpreet</h4>
                        <p class="board-member-role">Joint Secretary</p>
                    </div>
                </div>

                <!-- Vipul Goyal -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/vipul-goyal.jpg" alt="Vipul Goyal" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">Vipul Goyal</h4>
                        <p class="board-member-role">Joint Secretary</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Executive Committee (2-Column Desktop Grid) -->
    <section class="board-section exec-section">
        <div class="container">
            <div class="section-title-wrapper scroll-reveal text-center">
                <div class="title-divider-top"></div>
                <span class="sub-label">GOVERNING COMMITTEE</span>
                <h3 class="board-subtitle">Executive Committee</h3>
                <div class="title-divider-bottom"></div>
            </div>
            
            <div class="board-grid-exec scroll-reveal">
                <!-- Satyea Janardhana -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/satya-janardhana.jpg" alt="Satyea Janardhana Sridhar Rayala" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name font-small">Satyea Janardhana S.R.</h4>
                        <p class="board-member-role">EC Member</p>
                    </div>
                </div>

                <!-- B.V. Srinivas -->
                <div class="board-card">
                    <div class="board-img-container">
                        <img src="gallery/board/bv-srinivas.jpg" alt="B.V. Srinivas" class="board-img" loading="lazy">
                    </div>
                    <div class="board-card-info">
                        <h4 class="board-member-name">B.V. Srinivas</h4>
                        <p class="board-member-role">EC Member</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Committees & Information Officers Section (PoSH, Grievance, PIO) -->
    <section class="board-section committees-section pt-0">
        <div class="container">
            <div class="section-title-wrapper scroll-reveal text-center">
                <div class="title-divider-top"></div>
                <span class="sub-label">COMPLIANCE & REDRESSAL</span>
                <h3 class="board-subtitle">Committees &amp; Public Information</h3>
                <div class="title-divider-bottom"></div>
            </div>

            <div class="row g-4 scroll-reveal">
                <!-- PoSH Committee Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4" style="background: #ffffff; border: 1px solid rgba(8,27,75,0.08) !important;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle p-3 me-3" style="background: #FFF5EB; color: #FF9933;">
                                <i class="bi bi-shield-check fs-3"></i>
                            </div>
                            <div>
                                <span class="badge bg-warning text-dark text-uppercase fw-bold" style="font-size:0.7rem;">Compliance</span>
                                <h4 class="h5 font-weight-bold mb-0 text-dark" style="color: #081B4B !important;">PoSH Committee</h4>
                            </div>
                        </div>
                        <p class="text-muted small mb-4">Internal Complaints Committee constituted under the Prevention of Sexual Harassment (PoSH) Act for athlete and official safety.</p>
                        <div class="mt-auto">
                            <a href="uploads/documents/Posh_Committee.pdf" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold w-100 btn-sm" style="border-color: #081B4B; color: #081B4B;">
                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>View PoSH Document
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Grievance Redressal Committee Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4" style="background: #ffffff; border: 1px solid rgba(8,27,75,0.08) !important;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle p-3 me-3" style="background: #EEF2FF; color: #081B4B;">
                                <i class="bi bi-person-exclamation fs-3"></i>
                            </div>
                            <div>
                                <span class="badge bg-primary text-white text-uppercase fw-bold" style="font-size:0.7rem; background-color: #081B4B !important;">Athletes Cell</span>
                                <h4 class="h5 font-weight-bold mb-0 text-dark" style="color: #081B4B !important;">Grievance Committee</h4>
                            </div>
                        </div>
                        <p class="text-muted small mb-4">Official Complaint &amp; Athlete Grievance Redressal Mechanism ensuring transparent, fair, and prompt dispute resolution.</p>
                        <div class="mt-auto">
                            <a href="uploads/documents/internal_grievance_redressal_committee.pdf" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold w-100 btn-sm" style="border-color: #081B4B; color: #081B4B;">
                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>View Grievance Policy
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Public Information Officer (PIO) Card -->
                <div class="col-lg-4 col-md-12">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4" style="background: #ffffff; border: 1px solid rgba(8,27,75,0.08) !important;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle p-3 me-3" style="background: #E6F4EA; color: #137333;">
                                <i class="bi bi-info-circle fs-3"></i>
                            </div>
                            <div>
                                <span class="badge bg-success text-white text-uppercase fw-bold" style="font-size:0.7rem;">RTI / Sports Code</span>
                                <h4 class="h5 font-weight-bold mb-0 text-dark" style="color: #081B4B !important;">Public Information Officer</h4>
                            </div>
                        </div>
                        <div class="bg-light p-3 rounded-3 mb-3 small">
                            <div class="fw-bold text-dark fs-6 mb-1">Gurpreet Singh</div>
                            <div class="text-muted mb-1"><i class="bi bi-envelope me-2 text-primary"></i>gsablu8383@gmail.com</div>
                            <div class="text-muted mb-1"><i class="bi bi-telephone me-2 text-primary"></i>+91 9855222006</div>
                            <div class="text-muted"><i class="bi bi-geo-alt me-2 text-primary"></i>#69, VPO-Ablu, Tehsil &amp; Distt. Bathinda, Punjab-151201</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Certificate Download Callout Section -->
    <section class="board-section certificate-section">
        <div class="container">
            <div class="certificate-download-wrapper scroll-reveal">
                <div class="certificate-download-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <h3 class="cert-title">Official Governing Body Certificate</h3>
                            <p class="cert-desc">View the registered governing body and official federation documentation approved by authorities.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end d-flex gap-3 justify-content-lg-end align-items-center">
                            <a href="gallery/board/governing-body-certificate.pdf" target="_blank" rel="noopener" class="btn btn-outline-navy cert-btn">View PDF</a>
                            <a href="gallery/board/governing-body-certificate.pdf" download class="btn btn-primary-navy cert-btn">Download PDF</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. Federation Values Banner -->
    <section class="board-values-banner scroll-reveal">
        <div class="container-fluid p-0">
            <div class="values-banner-container" style="background-image: linear-gradient(rgba(8, 27, 75, 0.9), rgba(8, 27, 75, 0.95)), url('bg_schedule.webp');">
                <div class="container">
                    <div class="row align-items-center g-5">
                        <div class="col-12">
                            <div class="values-banner-text">
                                <span class="values-eyebrow">-- Federation Values --</span>
                                <h2 class="values-main-title">OUR CORE PRINCIPLES</h2>
                                <p class="values-desc-text">We are committed to building an inclusive sporting ecosystem, adhering to the highest standards of athletic growth, transparency, and equity.</p>
                                
                                <div class="values-list">
                                    <div class="value-item">
                                        <h4 class="value-name"><span class="value-bullet">•</span> Integrity</h4>
                                        <p class="value-para">Adhering to ethical sporting governance and fair play in every championship.</p>
                                    </div>
                                    <div class="value-item">
                                        <h4 class="value-name"><span class="value-bullet">•</span> Transparency</h4>
                                        <p class="value-para">Open communication, auditability, and responsible resource deployment.</p>
                                    </div>
                                    <div class="value-item">
                                        <h4 class="value-name"><span class="value-bullet">•</span> Inclusion</h4>
                                        <p class="value-para">Creating competitive platforms for athletes with severe physical disabilities.</p>
                                    </div>
                                    <div class="value-item">
                                        <h4 class="value-name"><span class="value-bullet">•</span> Excellence</h4>
                                        <p class="value-para">Fostering high-performance coaching, metrics-driven training, and medals.</p>
                                    </div>
                                </div>
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
