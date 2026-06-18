<?php
// includes/athlete-prevention-page.php - Custom Template for Athlete Prevention & Age Fraud Regulations
$page_title = "Athlete Prevention & Age Fraud Regulations | Boccia India";
$meta_desc = "Official regulations of Boccia Sports Federation of India (BSFI) regarding the prevention of age fraud, national licensing, and medical screening guidelines.";
$canonical_url = "page.php?section=myas&slug=athlete-prevention";

include __DIR__ . '/header.php';
?>

<!-- Add Google Fonts and Bootstrap Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-saffron: #FF9933;
    --boccia-light: #FAF8F5;
    --boccia-card-bg: #FFFFFF;
    --boccia-text-dark: #1E293B;
    --boccia-text-muted: #64748B;
    --font-heading-main: 'Playfair Display', serif;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

body {
    background-color: var(--boccia-light);
    color: var(--boccia-text-dark);
    font-family: var(--font-body-custom);
}

/* --- Hero Section --- */
.prevention-hero {
    background: linear-gradient(135deg, #051336 0%, #0d235c 50%, #153582 100%);
    color: #ffffff;
    padding: 80px 0 60px 0;
    position: relative;
    overflow: hidden;
}

.hero-eyebrow {
    font-family: var(--font-heading-sub);
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 0.15em;
    color: var(--boccia-saffron);
    text-transform: uppercase;
}

.hero-title {
    font-family: var(--font-heading-main);
    font-size: 4rem;
    font-weight: 800;
    color: #ffffff;
    margin-top: 10px;
    margin-bottom: 20px;
}

.hero-title span {
    font-weight: 400;
    font-style: italic;
}

.hero-desc {
    font-size: 1.1rem;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.85);
    max-width: 600px;
    margin-bottom: 0;
}

/* --- Section Title --- */
.section-divider-title {
    font-family: var(--font-heading-sub);
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--boccia-navy);
    text-transform: uppercase;
    text-align: center;
    letter-spacing: 0.05em;
    margin-bottom: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}

.section-divider-title::before,
.section-divider-title::after {
    content: "";
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--boccia-saffron), transparent);
    flex-grow: 1;
    max-width: 150px;
}

/* --- Regulation Cards --- */
.reg-card {
    background: var(--boccia-card-bg);
    border-radius: 20px;
    padding: 40px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.reg-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}

.reg-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 25px;
}

.reg-card.navy-border {
    border-top: 5px solid var(--boccia-navy);
}

.reg-card.saffron-border {
    border-top: 5px solid var(--boccia-saffron);
}

.reg-card.navy-border .reg-icon-wrapper {
    background: #EEF2FF;
    color: var(--boccia-navy);
}

.reg-card.saffron-border .reg-icon-wrapper {
    background: #FFF7ED;
    color: var(--boccia-saffron);
}

.reg-card h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 15px;
}

.reg-card p {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--boccia-text-muted);
    margin-bottom: 0;
}

/* --- Strict Penalties & Regulations Callout --- */
.penalty-callout-box {
    background: linear-gradient(135deg, #051336 0%, #081B4B 100%);
    color: #ffffff;
    border-radius: 24px;
    padding: 50px;
    border-left: 8px solid var(--boccia-saffron);
    box-shadow: 0 20px 45px rgba(8, 27, 75, 0.15);
    margin-top: 60px;
}

.penalty-callout-box h3 {
    font-family: var(--font-heading-sub);
    font-size: 2rem;
    font-weight: 800;
    color: var(--boccia-saffron);
    margin-bottom: 35px;
    letter-spacing: -0.01em;
}

.penalty-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.penalty-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.penalty-number-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 153, 51, 0.15);
    border: 2px solid var(--boccia-saffron);
    color: var(--boccia-saffron);
    font-family: var(--font-heading-sub);
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
    margin-top: 3px;
}

.penalty-text h5 {
    font-family: var(--font-heading-sub);
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.penalty-text p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #E2E8F0;
    margin-bottom: 0;
}

.penalty-text p strong {
    color: #ffffff;
}
</style>

<div class="athlete-prevention-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="prevention-hero">
        <div class="container">
            <span class="hero-eyebrow">MYAS Disclosures</span>
            <h1 class="hero-title">Athlete <span>Prevention</span></h1>
            <p class="hero-desc">Federation-wide rules and measures focused on eradicating age fraud to protect the integrity of Para Boccia in India.</p>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="rules-section">
        <div class="container">
            <h3 class="section-divider-title">Age Fraud Prevention Regulations</h3>
            
            <div class="row g-4 justify-content-center">
                <!-- Step 1: Licensing -->
                <div class="col-md-6">
                    <div class="reg-card navy-border">
                        <div class="reg-icon-wrapper">
                            <i class="bi bi-card-checklist"></i>
                        </div>
                        <h4>Licence & ID Issuance</h4>
                        <p>National Licence / ID cards are strictly issued based on official birth certificates. All State, UT, Member Units, and affiliated Sports Federations are mandated to systematically verify the ages of all players prior to any age-group competition.</p>
                    </div>
                </div>

                <!-- Step 2: Verification -->
                <div class="col-md-6">
                    <div class="reg-card saffron-border">
                        <div class="reg-icon-wrapper">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                        <h4>Mandatory Medical Tests</h4>
                        <p>All players must undergo age verification through standard medical tests prior to participation. To ensure absolute compliance and maintain due diligence, Boccia India conducts additional independent medical assessments.</p>
                    </div>
                </div>
            </div>

            <!-- Callout: Penalties -->
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="penalty-callout-box">
                        <h3>Strict Penalties & Regulations</h3>
                        
                        <div class="penalty-grid">
                            <div class="penalty-item">
                                <div class="penalty-number-badge">1</div>
                                <div class="penalty-text">
                                    <h5>Two-Year Ban</h5>
                                    <p>Any athlete, coach, or official found deliberately involved in age fraud will face an immediate <strong>two-year ban</strong> from all federation activities.</p>
                                </div>
                            </div>
                            
                            <div class="penalty-item">
                                <div class="penalty-number-badge">2</div>
                                <div class="penalty-text">
                                    <h5>Registry Deactivation</h5>
                                    <p>Boccia India will instantly deactivate the national ID and SDMS profiles of any Para Athletes associated with such fraudulent acts.</p>
                                </div>
                            </div>
                            
                            <div class="penalty-item">
                                <div class="penalty-number-badge">3</div>
                                <div class="penalty-text">
                                    <h5>Overage Exception</h5>
                                    <p>Athletes assessed for age fraud and found overage in medical tests by Orthopedic Doctors are restricted from their selected age category but are permitted to register and participate in <strong>upper age categories</strong>.</p>
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
