<?php
// get-involved/membership.php - Premium membership entry portal with interactive card links
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Online Player & Official Membership Portal - Boccia India";
include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-green: #10B981;
    --boccia-saffron: #FF9933;
    --boccia-maroon: #8C201C;
    --font-heading: 'Outfit', sans-serif;
    --font-body: 'Plus Jakarta Sans', sans-serif;
}

/* Hero Section precisely styled like Board/Affiliation */
.membership-hero {
    min-height: calc(100vh - 140px);
    height: calc(100vh - 140px);
    background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.85) 35%, rgba(7, 25, 84, 0.6) 55%, rgba(7, 25, 84, 0.2) 75%, transparent 100%), url('../board/board%20bg.webp');
    background-size: cover;
    background-position: center top;
    background-repeat: no-repeat;
    position: relative;
    display: flex;
    align-items: flex-end;
    padding-bottom: 5rem;
}

.membership-hero-container {
    position: relative;
    z-index: 3;
    width: 100%;
}

.membership-hero-content {
    max-width: 800px;
    text-align: left;
    color: #ffffff;
}

.membership-hero-eyebrow {
    color: #22C55E !important;
    font-family: 'Caveat', cursive, sans-serif !important;
    font-size: 1.8rem !important;
    font-weight: 400 !important;
    font-style: italic !important;
    display: block;
    margin-bottom: 0.5rem;
}

.membership-hero-title {
    font-family: var(--font-heading);
    font-size: clamp(2rem, 3.5vw, 3rem);
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 1rem;
    color: #ffffff;
    letter-spacing: -0.02em;
    text-transform: uppercase;
}

.membership-hero-text {
    font-size: clamp(0.95rem, 1.4vw, 1.15rem);
    color: rgba(255, 255, 255, 0.92);
    line-height: 1.6;
    margin: 0;
}

/* Content Section with specified background */
.membership-portal-content {
    padding: 100px 0;
    background: url('../about boccia/why_boccia_matter_BG.webp') no-repeat center center;
    background-size: cover;
    position: relative;
}

.portal-glass-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 60px;
    box-shadow: 0 20px 45px rgba(8, 27, 75, 0.12);
    border: 1px solid rgba(8, 27, 75, 0.06);
    backdrop-filter: blur(10px);
}

.portal-notice-header {
    border-bottom: 2px solid rgba(8, 27, 75, 0.08);
    padding-bottom: 30px;
    margin-bottom: 40px;
}

.portal-announcement-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(140, 32, 28, 0.08);
    color: var(--boccia-maroon);
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 8px 18px;
    border-radius: 50px;
    margin-bottom: 20px;
}

.portal-announcement-tag i {
    font-size: 1rem;
}

.portal-main-heading {
    font-family: var(--font-heading);
    color: var(--boccia-navy);
    font-weight: 800;
    font-size: 2rem;
    line-height: 1.3;
    margin: 0;
}

.portal-main-heading span {
    text-decoration: underline;
    text-underline-offset: 6px;
    color: var(--boccia-maroon);
}

.portal-info-body {
    font-family: var(--font-body);
    font-size: 1.1rem;
    line-height: 1.8;
    color: #475569;
}

.portal-info-body p {
    margin-bottom: 20px;
}

.portal-highlight-info {
    background: rgba(8, 27, 75, 0.03);
    border-left: 4px solid var(--boccia-navy);
    padding: 20px 25px;
    border-radius: 0 16px 16px 0;
    margin: 30px 0 45px 0;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.portal-highlight-info i {
    color: var(--boccia-navy);
    font-size: 1.4rem;
    margin-top: 2px;
}

.portal-highlight-info p {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

/* Choices Grid */
.portal-choices-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

@media (max-width: 768px) {
    .portal-choices-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .portal-glass-card {
        padding: 30px 20px;
    }
    .choice-link-card {
        padding: 30px 20px;
        border-radius: 18px;
    }
    .choice-icon-wrap {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        margin-bottom: 20px;
        border-radius: 14px;
    }
    .choice-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    .choice-desc {
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .choice-action-indicator {
        font-size: 0.85rem;
        padding: 7px 15px;
        gap: 6px;
    }
    .portal-main-heading {
        font-size: 1.5rem;
    }
    .portal-info-body {
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .portal-highlight-info {
        padding: 15px;
        margin: 20px 0 30px 0;
        gap: 10px;
        border-radius: 0 12px 12px 0;
    }
    .portal-highlight-info p {
        font-size: 0.88rem;
    }
}

.choice-link-card {
    border-radius: 20px;
    padding: 40px 30px;
    text-decoration: none !important;
    display: flex;
    flex-direction: column;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
    color: #ffffff !important;
}

.choice-link-card:hover {
    transform: translateY(-5px);
}

.choice-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

.choice-title {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 1.35rem;
    color: #ffffff;
    margin-bottom: 12px;
}

.choice-desc {
    font-family: var(--font-body);
    font-size: 0.95rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.88);
    margin-bottom: 25px;
    flex-grow: 1;
}

.choice-action-indicator {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.9rem;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: auto;
    background: rgba(255, 255, 255, 0.12);
    padding: 8px 18px;
    border-radius: 50px;
    align-self: flex-start;
    transition: all 0.3s ease;
}

.choice-action-indicator i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.choice-link-card:hover .choice-action-indicator i {
    transform: translateX(5px);
}

.choice-link-card:hover .choice-action-indicator {
    background: rgba(255, 255, 255, 0.25);
}

/* Choice 1: Player Card (Solid Maroon Gradient) */
.choice-player-card {
    background: linear-gradient(135deg, #B52C28 0%, #8C1C18 100%);
    box-shadow: 0 10px 25px rgba(140, 32, 28, 0.2);
}

.choice-player-card:hover {
    box-shadow: 0 20px 40px rgba(140, 32, 28, 0.4);
}

/* Choice 2: Official Card (Solid Navy Gradient) */
.choice-official-card {
    background: linear-gradient(135deg, #1E3A8A 0%, #081B4B 100%);
    box-shadow: 0 10px 25px rgba(8, 27, 75, 0.2);
}

.choice-official-card:hover {
    box-shadow: 0 20px 40px rgba(8, 27, 75, 0.4);
}
</style>

<div class="membership-portal-wrapper">
    <!-- Hero Header Banner -->
    <section class="membership-hero">
        <div class="container membership-hero-container">
            <div class="membership-hero-content scroll-reveal">
                <span class="membership-hero-eyebrow">-- Get Involved --</span>
                <h1 class="membership-hero-title">MEMBERSHIP PORTAL</h1>
                <p class="membership-hero-text">
                    Join the Boccia Sports Federation of India (BSFI). Register online to receive your official Athlete ID, track compliance, and participate in tournaments.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Choices -->
    <section class="membership-portal-content">
        <div class="container" style="max-width: 950px;">
            <div class="portal-glass-card scroll-reveal">
                
                <div class="portal-notice-header">
                    <div class="portal-announcement-tag">
                        <i class="bi bi-megaphone-fill"></i> Official BSFI Announcement
                    </div>
                    <h2 class="portal-main-heading">
                        Boccia <span>INDIA memberships are now available.</span>
                    </h2>
                </div>

                <div class="portal-info-body">
                    <p>Becoming a member of Boccia INDIA supports the growth of the sport in this country, it enables you to compete at any Boccia India-supported event as well protect our members.</p>
                    
                    <div class="portal-highlight-info">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                        <p>If you are an athlete, coach, sport assistant, official, or volunteer you need to become a member of the sport. You will be ineligible to participate in any Boccia India-sanctioned events if you do not have an active membership status.</p>
                    </div>
                </div>

                <!-- Registration Choices Grid -->
                <div class="portal-choices-grid">
                    
                    <!-- Choice 1: Player -->
                    <a href="register-player.php" class="choice-link-card choice-player-card">
                        <div class="choice-icon-wrap">
                            <i class="bi bi-person-bounding-box"></i>
                        </div>
                        <h3 class="choice-title">Player Registration</h3>
                        <p class="choice-desc">Register as a competing athlete. Required for official classifications, ranking tracking, and tournament entries.</p>
                        <span class="choice-action-indicator">
                            Register Now <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>

                    <!-- Choice 2: Coach/Official -->
                    <a href="register-official.php" class="choice-link-card choice-official-card">
                        <div class="choice-icon-wrap">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <h3 class="choice-title">Coach &amp; Official Registration</h3>
                        <p class="choice-desc">Register as a Coach, Sport Assistant, Referee, Classifier, Technical Official, or Event Volunteer.</p>
                        <span class="choice-action-indicator">
                            Register Now <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>

                </div>

                <div class="mt-5 text-center pt-4 border-top" style="border-top-color: rgba(8, 27, 75, 0.08) !important;">
                    <p class="text-muted mb-3" style="font-family: var(--font-body); font-size: 1rem;">Already registered? Check your membership status online instantly.</p>
                    <a href="verify-membership.php" class="btn btn-outline-primary rounded-pill px-4 py-2" style="font-family: var(--font-heading); font-weight: 700; border-color: var(--boccia-navy); color: var(--boccia-navy); transition: all 0.3s ease;">
                        <i class="bi bi-shield-fill-check me-1"></i> Verify Membership Status
                    </a>
                </div>

            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
