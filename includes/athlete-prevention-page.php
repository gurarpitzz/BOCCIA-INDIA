<?php
// includes/athlete-prevention-page.php - Custom Template for Athlete Prevention & Age Fraud Regulations
$page_title = "Athlete Prevention & Age Fraud Regulations | Boccia India";
$meta_desc = "Official regulations of Boccia Sports Federation of India (BSFI) regarding the prevention of age fraud, national licensing, and medical screening guidelines.";
$canonical_url = "page.php?section=myas&slug=athlete-prevention";

include __DIR__ . '/header.php';
?>

<div class="board-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.png');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- MYAS Disclosures --</span>
                <h1 class="board-hero-title">ATHLETE PREVENTION</h1>
                <p class="board-hero-text">
                    Regulations on the prevention of age fraud by the athletes.
                </p>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <section class="board-section py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-10 mx-auto">
                    <div class="section-title-wrapper text-center mb-5">
                        <span class="sub-label">fair play &amp; integrity</span>
                        <h2 class="board-subtitle text-uppercase" style="color: #081B4B !important; font-size: 2.2rem;">Age Fraud Prevention Regulations</h2>
                    </div>

                    <!-- Regulation Cards Grid -->
                    <div class="row g-4">
                        <!-- Step 1: Licensing & Verification -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 p-4" style="background: rgba(255, 255, 255, 0.95); border-left: 5px solid #081B4B !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-card-checklist" viewBox="0 0 16 16">
                                                <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2z"/>
                                                <path d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-4-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0"/>
                                            </svg>
                                        </div>
                                        <h4 class="card-title fw-bold m-0" style="color: #081B4B;">Licence &amp; ID Issuance</h4>
                                    </div>
                                    <p class="card-text text-secondary" style="line-height: 1.7;">
                                        National Licence / ID cards are strictly issued based on official birth certificates. All State, UT, Member Units, and affiliated Sports Federations are mandated to systematically verify the ages of all players prior to any age-group competition.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Medical Screening -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 p-4" style="background: rgba(255, 255, 255, 0.95); border-left: 5px solid #FF9933 !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning-subtle text-warning p-3 rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; color: #FF9933 !important;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-heart-pulse-fill" viewBox="0 0 16 16">
                                                <path d="M1.475 9C2.702 10.84 4.779 12.871 8 15c3.221-2.129 5.298-4.16 6.525-6H12a.5.5 0 0 1-.465-.315l-1.87-4.67-1.12 2.24a.5.5 0 0 1-.84-.047L6.5 4.12l-1.04 2.08A.5.5 0 0 1 5 6.5H1.475Z"/>
                                                <path d="M10.846 1.154a4.5 4.5 0 0 0-7.02 5.092l.067-.133A1.5 1.5 0 0 1 5 5.5h1.12l1.171-2.342a.5.5 0 0 1 .824-.038l1.244 2.487 1.202-3.004A.5.5 0 0 1 12 2.5h2.026a4.48 4.48 0 0 0-3.18-1.346Z"/>
                                            </svg>
                                        </div>
                                        <h4 class="card-title fw-bold m-0" style="color: #081B4B;">Mandatory Medical Tests</h4>
                                    </div>
                                    <p class="card-text text-secondary" style="line-height: 1.7;">
                                        All players must undergo age verification through standard medical tests prior to participation. To ensure absolute compliance and maintain due diligence, Boccia India conducts additional independent medical assessments.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penalty & Exception Callout Box -->
                    <div class="mt-5 p-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #081B4B 0%, #16295A 100%); border-left: 8px solid #FF9933;">
                        <div class="row align-items-start">
                            <div class="col-md-1 text-center text-md-start mb-3 mb-md-0 pt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-exclamation-triangle-fill text-warning" viewBox="0 0 16 16" style="color: #FF9933 !important;">
                                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                                </svg>
                            </div>
                            <div class="col-md-11">
                                <h3 class="fw-bold mb-4" style="color: #FF9933; font-family: var(--font-heading); font-size: 1.6rem; letter-spacing: -0.01em;">Strict Penalties &amp; Regulations</h3>
                                <div style="font-size: 1.1rem; line-height: 1.9; color: #E5E7EB;">
                                    <p class="mb-4">
                                        <strong style="color: #FF9933; font-weight: 700; font-size: 1.15rem; display: block; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em;">1. Two-Year Ban</strong>
                                        Any athlete, coach, or official found deliberately involved in age fraud will face an immediate <span style="color: #ffffff; font-weight: 600;">two-year ban</span> from all federation activities.
                                    </p>
                                    <p class="mb-4">
                                        <strong style="color: #FF9933; font-weight: 700; font-size: 1.15rem; display: block; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em;">2. Registry Deactivation</strong>
                                        Boccia India will instantly deactivate the national ID and SDMS profiles of any Para Athletes associated with such fraudulent acts.
                                    </p>
                                    <p class="mb-0">
                                        <strong style="color: #FF9933; font-weight: 700; font-size: 1.15rem; display: block; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em;">3. Overage Exception</strong>
                                        Athletes assessed for age fraud and found overage in medical tests by Orthopedic Doctors are restricted from their selected age category but are permitted to register and participate in upper age categories.
                                    </p>
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
