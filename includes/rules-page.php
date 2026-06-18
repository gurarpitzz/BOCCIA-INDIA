<?php
// includes/rules-page.php - Custom Template for Sport Rules and Regulations
$page_title = "Rules of the Game | Boccia India";
$meta_desc = "Explore the official rules, regulations, WADA guidelines, classification criteria, and equipment standards for Para Boccia in India.";
$canonical_url = "page.php?section=sport&slug=rules";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.png');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Our Sport --</span>
                <h1 class="board-hero-title">RULES OF BOCCIA</h1>
                <p class="board-hero-text">
                    Official rules, equipment standards, and classification guidelines for Para Boccia.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="board-section py-5">
        <div class="container">
            
            <!-- Intro Content Block -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <span class="sub-label" style="letter-spacing: 0.1em; text-transform: uppercase; color: #FF9933; font-weight: 700;">Game Objectives</span>
                    <h2 class="board-subtitle my-3" style="color: #081B4B !important; font-size: 2.2rem; font-weight: 700;">How to Play Boccia</h2>
                    <p class="lead text-secondary" style="line-height: 1.8; font-size: 1.15rem;">
                        Boccia is a target sport that is suitable for a wide range of participants. It can be played by individuals, pairs, or teams of three, and all events are unaffected by age or gender.
                    </p>
                    <p class="text-secondary mt-3" style="line-height: 1.8; font-size: 1.05rem;">
                        The aim is to throw leather balls, coloured either red or blue (which side gets which is determined by a coin toss), as close as you can to a white target ball, or jack. The balls can be thrown, kicked, or athletes can use an assistive device such as a ramp.
                    </p>
                </div>
            </div>

            <!-- Rules & Licensing Alert -->
            <div class="row mb-5">
                <div class="col-lg-10 mx-auto">
                    <div class="p-4 rounded-4 border-0 shadow-sm d-flex flex-column flex-md-row align-items-center" style="background: #FFF9F6; border-left: 6px solid #FF9933 !important; gap: 1.5rem;">
                        <div class="text-warning" style="color: #FF9933 !important;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-2" style="color: #081B4B; font-size: 1.25rem;">Federation Rules &amp; Ball Licensing</h4>
                            <p class="text-secondary mb-0" style="font-size: 0.95rem; line-height: 1.6;">
                                Boccia India uses rules from the International Federation, <strong>BISFed</strong>, for competitions in India with some variations. The ball licensing rules apply only to International Competitions.
                            </p>
                            <div class="mt-3">
                                <a href="http://www.bisfed.com" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary fw-bold rounded-pill me-2" style="border-color: #081B4B; color: #081B4B;">Visit BISFed.com</a>
                                <a href="uploads/documents/BISFed_Ball.pdf" download class="btn btn-sm btn-primary-navy fw-bold rounded-pill me-2" style="background: #081B4B; color: #ffffff;">Download Ball Licensing PDF</a>
                                <a href="uploads/documents/World-Boccia-Rules-2025-2028-v1.2.1-2.pdf" download class="btn btn-sm btn-primary-navy fw-bold rounded-pill" style="background: #FF9933; border-color: #FF9933; color: #ffffff;">Download World Boccia Rules (PDF)</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Compliance Section -->
            <div class="row g-4 mb-5">
                <div class="col-12 text-center mb-2">
                    <span class="sub-label" style="letter-spacing: 0.1em; text-transform: uppercase; color: #FF9933; font-weight: 700;">Regulatory Documents</span>
                    <h3 class="board-subtitle my-2" style="color: #081B4B !important; font-size: 1.8rem; font-weight: 700;">Official Standards &amp; Anti-Doping</h3>
                </div>

                <!-- WADA card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff; transition: transform 0.2s;">
                        <div style="height: 180px; overflow: hidden; position: relative;">
                            <img src="gallery/anti_doping.png" alt="WADA Anti-Doping" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-danger text-white mb-2 text-uppercase fw-bold" style="font-size: 0.75rem; background: #E74C3C !important;">Anti-Doping</span>
                                <h4 class="card-title fw-bold text-dark mb-3" style="font-size: 1.25rem;">WADA Regulations</h4>
                                <p class="card-text text-secondary mb-4" style="font-size: 0.9rem; line-height: 1.6;">All documents are mandatory for World Anti-Doping Agency (WADA) compliance and regulations.</p>
                            </div>
                            <a href="https://www.worldboccia.com/documents/#AntiDopingDoc" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                WADA Guidelines
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Classification card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff; transition: transform 0.2s;">
                        <div style="height: 180px; overflow: hidden; position: relative;">
                            <img src="gallery/classification.png" alt="Athlete Classification" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-info text-white mb-2 text-uppercase fw-bold" style="font-size: 0.75rem; background: #3498DB !important;">Classification</span>
                                <h4 class="card-title fw-bold text-dark mb-3" style="font-size: 1.25rem;">Athlete Classification</h4>
                                <p class="card-text text-secondary mb-4" style="font-size: 0.9rem; line-height: 1.6;">All documents are mandatory for Para Boccia athlete medical and functional classifications.</p>
                            </div>
                            <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                Classification Docs
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Equipment Guideline card -->
                <div class="col-md-6 col-lg-4 mx-auto">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff; transition: transform 0.2s;">
                        <div style="height: 180px; overflow: hidden; position: relative;">
                            <img src="gallery/sports_equipment.png" alt="Equipment Guidelines" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-success text-white mb-2 text-uppercase fw-bold" style="font-size: 0.75rem; background: #2ECC71 !important;">Equipment</span>
                                <h4 class="card-title fw-bold text-dark mb-3" style="font-size: 1.25rem;">Equipment Guidelines</h4>
                                <p class="card-text text-secondary mb-4" style="font-size: 0.9rem; line-height: 1.6;">Official measurements, testing, and standard configurations allowed for competition ramps and balls.</p>
                            </div>
                            <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-pill fw-bold w-100" style="border: 2px solid #FF9933; color: #FF9933;">
                                Equipment Guide
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BC Classification Categories Section -->
            <div class="row g-4 mt-5">
                <div class="col-12 text-center">
                    <span class="sub-label" style="letter-spacing: 0.1em; text-transform: uppercase; color: #FF9933; font-weight: 700;">Athlete Categories</span>
                    <h3 class="board-subtitle my-2" style="color: #081B4B !important; font-size: 1.8rem; font-weight: 700;">BC Classifications</h3>
                </div>

                <!-- BC1 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff;">
                        <div style="height: 200px; overflow: hidden; position: relative;">
                            <img src="gallery/bc1.jpg" alt="BC1 Category" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-2" style="color: #081B4B; font-size: 1.2rem;">BC1 Category</h4>
                            <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                                Athletes throw the ball with their hand or foot. They may play with the assistance of an assistant who stays outside the athlete’s playing box.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- BC2 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff;">
                        <div style="height: 200px; overflow: hidden; position: relative;">
                            <img src="gallery/bc2.jpg" alt="BC2 Category" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-2" style="color: #081B4B; font-size: 1.2rem;">BC2 Category</h4>
                            <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                                Athletes throw the ball with their hand. They are not eligible for assistance in the box.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- BC3 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff;">
                        <div style="height: 200px; overflow: hidden; position: relative;">
                            <img src="gallery/bc3.jpg" alt="BC3 Category" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-2" style="color: #081B4B; font-size: 1.2rem;">BC3 Category</h4>
                            <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                                Athletes have severe locomotor dysfunction in all four limbs and use an assistive device (ramp) and an assistant (ramper).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- BC4 -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff;">
                        <div style="height: 200px; overflow: hidden; position: relative;">
                            <img src="gallery/bc4.jpg" alt="BC4 Category" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-2" style="color: #081B4B; font-size: 1.2rem;">BC4 Category</h4>
                            <p class="text-secondary mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                                Athletes have non-cerebral origin physical impairment. They throw the ball with their hand and are not eligible for assistance.
                            </p>
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
