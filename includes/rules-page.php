<?php
// includes/rules-page.php - Custom Template for Sport Rules and Regulations
$page_title = "Rules of the Game | Boccia India";
$meta_desc = "Explore the official rules, regulations, WADA guidelines, classification criteria, and equipment standards for Para Boccia in India.";
$canonical_url = "page.php?section=sport&slug=rules";

include __DIR__ . '/header.php';
?>

<!-- Add Google Fonts and Bootstrap Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-saffron: #FF9933;
    --boccia-gold: #D4AF37;
    --boccia-light: #FAF8F5;
    --boccia-card-bg: #FFFFFF;
    --boccia-text-dark: #1E293B;
    --boccia-text-muted: #64748B;
    --font-heading-main: 'Playfair Display', serif;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

body {
    background: url('board/board_bg.webp') no-repeat center top;
    background-size: 100% auto;
    background-color: #FAF8F5;
    color: var(--boccia-text-dark);
    font-family: var(--font-body-custom);
}

/* --- Hero Section --- */
.our-sport-hero {
    background: linear-gradient(90deg, #051336 0%, rgba(5, 19, 54, 0.92) 40%, rgba(16, 185, 129, 0.18) 75%, transparent 100%);
    color: #ffffff;
    padding: 80px 0 60px 0;
    position: relative;
    overflow: hidden;
}

.hero-eyebrow {
    font-family: 'Dancing Script', cursive;
    font-weight: 700;
    font-size: 1.8rem;
    font-style: italic;
    color: #10B981; /* Vibrant Green */
    text-transform: none;
    letter-spacing: 0.02em;
}

.hero-title {
    font-family: var(--font-heading-main);
    font-size: 4rem;
    font-weight: 800;
    color: #ffffff; /* White title */
    margin-top: 10px;
    margin-bottom: 20px;
}

.hero-title span {
    font-weight: 400;
    font-style: italic;
    color: #10B981 !important; /* Green Accent */
}

.hero-desc {
    font-size: 1.25rem;
    line-height: 1.8;
    color: rgba(255, 255, 255, 0.9); /* High-contrast white/light gray */
    font-weight: 500;
    max-width: 500px;
    margin-bottom: 40px;
}

.hero-img-wrapper {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
}

.hero-img-wrapper img {
    width: 100%;
    height: auto;
    object-fit: cover;
    display: block;
}

/* Quick Nav Cards */
.quick-nav-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    max-width: 600px;
}

@media (max-width: 575px) {
    .quick-nav-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

.quick-nav-card {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 20px 10px;
    text-align: center;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.quick-nav-card i {
    font-size: 1.8rem;
    color: #10B981; /* Green Icons */
    margin-bottom: 12px;
    transition: transform 0.3s ease;
}

.quick-nav-card span {
    font-family: var(--font-heading-sub);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    line-height: 1.3;
}

.quick-nav-card:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: #10B981;
    color: #ffffff;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.quick-nav-card:hover i {
    transform: scale(1.15);
}

/* --- Section Formatting --- */
.rules-section {
    padding: 80px 0;
}

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

/* --- Section 1: What is Boccia --- */
.what-is-card {
    background: var(--boccia-card-bg);
    border-radius: 24px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    padding: 40px;
    margin-bottom: 30px;
}

.diagram-holder {
    background: #EAEFF8;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    position: relative;
    border: 1px solid rgba(8, 27, 75, 0.08);
}

.diagram-holder img {
    max-width: 100%;
    height: auto;
}

.feature-list {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.feature-icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #F0F4FC;
    border: 2px solid var(--boccia-navy);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--boccia-navy);
    font-size: 1.5rem;
}

.feature-item:nth-child(2) .feature-icon-circle {
    background: #FFF5EC;
    border-color: var(--boccia-saffron);
    color: var(--boccia-saffron);
}

.feature-text h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--boccia-navy);
    margin-bottom: 6px;
}

.feature-text p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted);
    line-height: 1.6;
    margin-bottom: 0;
}

/* --- Section 2: How to Play --- */
.play-steps-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}

@media (max-width: 991px) {
    .play-steps-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    .play-steps-grid {
        grid-template-columns: 1fr;
    }
}

/* Staggered entrance animation for step cards */
@keyframes fadeInUpStaggered {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-card {
    background: var(--boccia-card-bg);
    border-radius: 16px;
    padding: 30px 20px;
    text-align: center;
    border: 1px solid rgba(8, 27, 75, 0.05);
    box-shadow: 0 8px 24px rgba(8, 27, 75, 0.02);
    position: relative;
    transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), border-color 0.3s;
    opacity: 0;
    animation: fadeInUpStaggered 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
}

/* Delay for each card */
.step-card:nth-child(1) { animation-delay: 0.1s; }
.step-card:nth-child(2) { animation-delay: 0.25s; }
.step-card:nth-child(3) { animation-delay: 0.4s; }
.step-card:nth-child(4) { animation-delay: 0.55s; }
.step-card:nth-child(5) { animation-delay: 0.7s; }

.step-card:hover {
    transform: translateY(-10px) scale(1.03);
    box-shadow: 0 20px 40px rgba(8, 27, 75, 0.08), 0 0 15px rgba(255, 153, 51, 0.15);
    border-color: rgba(255, 153, 51, 0.3);
}

.step-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--boccia-navy);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s, transform 0.3s;
}

.step-card:hover .step-badge {
    background: var(--boccia-saffron);
    transform: translateX(-50%) scale(1.15);
    box-shadow: 0 0 0 5px rgba(255, 153, 51, 0.2);
}

@keyframes iconWobble {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(-8deg) scale(1.15); }
    75% { transform: rotate(8deg) scale(1.15); }
    100% { transform: rotate(0deg); }
}

.step-icon {
    font-size: 2.2rem;
    color: var(--boccia-navy);
    margin-top: 10px;
    margin-bottom: 20px;
    display: inline-block;
    transition: transform 0.3s;
}

.step-card:hover .step-icon {
    animation: iconWobble 0.5s ease-in-out;
}

.step-card:nth-child(even) .step-icon {
    color: var(--boccia-saffron);
}

.step-card h4 {
    font-family: var(--font-heading-sub);
    font-size: 1rem;
    font-weight: 800;
    color: var(--boccia-navy);
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 12px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-card p {
    font-size: 0.85rem;
    color: var(--boccia-text-muted);
    line-height: 1.5;
    margin-bottom: 0;
}

/* --- Section 3: BC Classification System --- */
.classification-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 991px) {
    .classification-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    .classification-grid {
        grid-template-columns: 1fr;
    }
}

.class-card {
    background: var(--boccia-card-bg);
    border-radius: 20px;
    border: 1px solid rgba(8, 27, 75, 0.05);
    box-shadow: 0 8px 24px rgba(8, 27, 75, 0.02);
    padding: 30px 20px;
    text-align: center;
    transition: transform 0.3s ease;
}

.class-card:hover {
    transform: translateY(-5px);
}

.class-card-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.class-card-icon img {
    max-width: 100%;
    max-height: 100%;
}

.class-list {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
    text-align: left;
}

.class-list li {
    font-size: 0.88rem;
    color: var(--boccia-text-dark);
    padding: 8px 0;
    border-bottom: 1px solid rgba(8, 27, 75, 0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.class-list li:last-child {
    border-bottom: none;
}

.class-list li i.bi-check-circle-fill {
    color: #10B981;
}

.class-list li i.bi-x-circle-fill {
    color: #EF4444;
}

.class-card p.class-desc {
    font-size: 0.85rem;
    color: var(--boccia-text-muted);
    line-height: 1.6;
    margin-bottom: 25px;
    text-align: left;
    min-height: 80px;
}

.class-card .btn-learn-more {
    font-family: var(--font-heading-sub);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--boccia-navy);
    border: 1.5px solid var(--boccia-navy);
    border-radius: 30px;
    padding: 8px 20px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.class-card .btn-learn-more:hover {
    background: var(--boccia-navy);
    color: #ffffff;
}

/* --- Section 4: Equipment Standards --- */
#equipment {
    background-color: #FFFFFF;
}

.equipment-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 991px) {
    .equipment-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    .equipment-grid {
        grid-template-columns: 1fr;
    }
}

.equip-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(8, 27, 75, 0.08);
    box-shadow: 0 8px 24px rgba(8, 27, 75, 0.02);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.equip-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}

.equip-icon-header {
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    border-bottom: 1px solid rgba(8, 27, 75, 0.05);
    transition: transform 0.3s ease;
}

.equip-card:hover .equip-icon-header {
    transform: scale(1.05);
}

.equip-body {
    padding: 25px 20px;
}

.equip-body h4 {
    font-family: var(--font-heading-sub);
    font-size: 1rem;
    font-weight: 800;
    color: var(--boccia-navy);
    text-transform: uppercase;
    margin-bottom: 8px;
}

.equip-body p {
    font-size: 0.85rem;
    color: var(--boccia-text-muted);
    line-height: 1.6;
    margin-bottom: 20px;
    min-height: 55px;
}

.equip-body .btn-equip-link {
    font-family: var(--font-heading-sub);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--boccia-navy);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.3s ease;
}

.equip-body .btn-equip-link:hover {
    color: var(--boccia-saffron);
}

/* --- Section 5: Documents & Court Diagram --- */
.split-grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 30px;
}

@media (max-width: 991px) {
    .split-grid {
        grid-template-columns: 1fr;
    }
}

#documents {
    background: url('about boccia/NATIONAL_IMPRINT_bg.webp') no-repeat center center;
    background-size: cover;
}

/* Documents Column */
.doc-row-layout {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

@media (max-width: 575px) {
    .doc-row-layout {
        grid-template-columns: 1fr;
    }
}

.doc-download-card {
    background: var(--boccia-card-bg);
    border-radius: 16px;
    border: 1px solid rgba(8, 27, 75, 0.05);
    padding: 25px 15px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    min-height: 280px;
}

.doc-download-card i.main-icon {
    font-size: 2rem;
    color: var(--boccia-navy);
    margin-bottom: 15px;
}

.doc-download-card h4 {
    font-family: var(--font-heading-sub);
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--boccia-navy);
    text-transform: uppercase;
    margin-bottom: 6px;
    min-height: 36px;
    display: flex;
    align-items: center;
}

.doc-download-card p.doc-subtext {
    font-size: 0.8rem;
    color: var(--boccia-text-muted);
    line-height: 1.4;
    margin-bottom: 15px;
    min-height: 45px;
}

.doc-meta {
    font-size: 0.72rem;
    color: var(--boccia-text-muted);
    margin-bottom: 15px;
}

.doc-meta span {
    display: block;
}

.doc-actions {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn-doc-view {
    font-family: var(--font-heading-sub);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    padding: 6px 0;
    color: var(--boccia-text-dark);
    text-decoration: none;
    transition: all 0.3s ease;
    width: 100%;
    display: block;
}

.btn-doc-view:hover {
    border-color: var(--boccia-navy);
    background: #F8FAFC;
    color: var(--boccia-navy);
}

.btn-doc-dl {
    font-family: var(--font-heading-sub);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    background: #E2E8F0;
    border-radius: 6px;
    padding: 8px 0;
    color: var(--boccia-navy);
    text-decoration: none;
    transition: all 0.3s ease;
    width: 100%;
    display: block;
}

.btn-doc-dl:hover {
    background: var(--boccia-saffron);
    color: #ffffff;
}

.btn-view-all-docs {
    display: inline-block;
    max-width: 320px;
    font-family: var(--font-heading-sub);
    font-size: 0.95rem;
    font-weight: 700;
    text-transform: uppercase;
    background-color: #ffffff;
    color: var(--boccia-navy);
    border: 2px solid #ffffff;
    border-radius: 50px;
    padding: 12px 35px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.btn-view-all-docs:hover {
    background-color: var(--boccia-navy);
    color: #ffffff;
    border-color: var(--boccia-navy);
    box-shadow: 0 6px 20px rgba(8, 27, 75, 0.4);
    transform: translateY(-2px);
}

#court-diagram {
    background: url('bg.webp') no-repeat center center;
    background-size: cover;
}

/* Court Interactive */
.interactive-court-container {
    background: var(--boccia-card-bg);
    border-radius: 20px;
    border: 1px solid rgba(8, 27, 75, 0.05);
    padding: 30px;
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.02);
}

.court-svg-holder {
    position: relative;
    margin-bottom: 25px;
    background: #0D2B66;
    border-radius: 12px;
    padding: 20px;
}

.court-svg-holder svg {
    display: block;
    width: 100%;
    height: auto;
}

/* Hover Hotspot circles */
.court-hotspot {
    cursor: pointer;
    transition: fill 0.3s ease, r 0.3s ease;
}

.court-hotspot:hover, .court-hotspot.active {
    fill: var(--boccia-saffron) !important;
}

.court-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.court-info-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 10px 15px;
    border-radius: 10px;
    transition: background-color 0.3s ease;
    cursor: pointer;
}

.court-info-item.active, .court-info-item:hover {
    background: #F0F4FC;
}

.court-marker-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--boccia-navy);
    border: 2px solid #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 3px;
}

.court-info-item.active .court-marker-dot {
    background: var(--boccia-saffron);
}

.court-info-text h5 {
    font-family: var(--font-heading-sub);
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--boccia-navy);
    margin-bottom: 4px;
}

.court-info-text p {
    font-size: 0.8rem;
    color: var(--boccia-text-muted);
    line-height: 1.4;
    margin-bottom: 0;
}

/* --- Section 6: Footer CTA --- */
.boccia-footer-cta {
    background: var(--boccia-navy);
    color: #ffffff;
    border-radius: 20px;
    padding: 40px;
    margin-top: 50px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    position: relative;
    overflow: hidden;
}

@media (max-width: 767px) {
    .boccia-footer-cta {
        flex-direction: column;
        text-align: center;
        padding: 30px 20px;
        gap: 20px;
        margin-top: 30px;
    }
    .cta-left {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 15px;
    }
    .cta-text h3 {
        font-size: 1.1rem;
        line-height: 1.5;
    }
    .cta-text p {
        font-size: 1.2rem;
    }
    .btn-cta-action {
        width: 100%;
        text-align: center;
        padding: 12px 24px;
        white-space: normal;
    }
}

.cta-left {
    display: flex;
    align-items: center;
    gap: 20px;
    z-index: 1;
}

.cta-icon-holder {
    font-size: 2.5rem;
    color: var(--boccia-saffron);
}

.cta-text h3 {
    font-family: var(--font-heading-sub);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.cta-text p {
    font-size: 1.4rem;
    font-family: var(--font-heading-sub);
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0;
}

.cta-text p span {
    color: var(--boccia-saffron);
}

.btn-cta-action {
    font-family: var(--font-heading-sub);
    font-weight: 800;
    background: var(--boccia-saffron);
    color: #ffffff;
    border: none;
    border-radius: 30px;
    padding: 14px 32px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    z-index: 1;
    white-space: nowrap;
}

.btn-cta-action:hover {
    background: #e6851f;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255, 153, 51, 0.3);
}

/* Court Hotspot Tooltip styling */
.tooltip-custom {
    position: absolute;
    background: var(--boccia-navy);
    color: #ffffff;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 10;
    max-width: 200px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
.tooltip-custom::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 6px;
    border-style: solid;
    border-color: var(--boccia-navy) transparent transparent transparent;
}
</style>

<div class="our-sport-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="our-sport-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="hero-eyebrow"><i>--Boccia India--</i></span>
                    <h1 class="hero-title">Our <span>Sport</span></h1>
                    <p class="hero-desc">Discover the rules, classifications, equipment standards, and competitive structure of Para Boccia.</p>
                    
                    <div class="quick-nav-container">
                        <a href="#how-it-works" class="quick-nav-card">
                            <i class="bi bi-compass-fill"></i>
                            <span>How Boccia Works</span>
                        </a>
                        <a href="#classification" class="quick-nav-card">
                            <i class="bi bi-people-fill"></i>
                            <span>Classification</span>
                        </a>
                        <a href="#equipment" class="quick-nav-card">
                            <i class="bi bi-tools"></i>
                            <span>Equipment</span>
                        </a>
                        <a href="#documents" class="quick-nav-card">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            <span>Official Documents</span>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-img-wrapper" style="box-shadow: 0 15px 35px rgba(0,0,0,0.2); border-radius: 20px; overflow: hidden; border: 4px solid rgba(255,255,255,0.15);">
                        <img src="gallery/WhatsApp Image 2026-06-03 at 09.31.25.jpeg" alt="Para Boccia Athlete throwing ball" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 1: WHAT IS BOCCIA ═══════════ -->
    <section id="how-it-works" class="rules-section">
        <div class="container">
            <h3 class="section-divider-title">1. What is Boccia?</h3>
            <div class="what-is-card">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="diagram-holder">
                            <!-- Draw 3D Court Diagram in styled SVG directly -->
                            <svg viewBox="0 0 500 320" width="100%" height="auto">
                                <defs>
                                    <linearGradient id="courtGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#1E4D9C" />
                                        <stop offset="100%" stop-color="#0E2F6C" />
                                    </linearGradient>
                                </defs>
                                <!-- Court Base -->
                                <polygon points="60,280 440,280 340,40 160,40" fill="url(#courtGrad)" stroke="#FFFFFF" stroke-width="4" />
                                
                                <!-- Lines -->
                                <!-- Throwing lines/boxes split into 6 boxes at bottom -->
                                <line x1="90" y1="240" x2="410" y2="240" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="120" y1="240" x2="120" y2="280" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="175" y1="240" x2="175" y2="280" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="250" y1="240" x2="250" y2="280" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="325" y1="240" x2="325" y2="280" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="380" y1="240" x2="380" y2="280" stroke="#FFFFFF" stroke-width="2" />

                                <!-- V Line -->
                                <polygon points="190,100 250,140 310,100" fill="none" stroke="#FFFFFF" stroke-dasharray="4,3" stroke-width="2" />
                                
                                <!-- Center Cross -->
                                <line x1="245" y1="80" x2="255" y2="80" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="250" y1="75" x2="250" y2="85" stroke="#FFFFFF" stroke-width="2" />

                                <!-- Balls representation -->
                                <circle cx="200" cy="180" r="10" fill="#EF4444" filter="drop-shadow(0px 3px 3px rgba(0,0,0,0.3))" />
                                <circle cx="215" cy="185" r="10" fill="#EF4444" filter="drop-shadow(0px 3px 3px rgba(0,0,0,0.3))" />
                                <circle cx="280" cy="170" r="10" fill="#3B82F6" filter="drop-shadow(0px 3px 3px rgba(0,0,0,0.3))" />
                                <circle cx="295" cy="190" r="10" fill="#3B82F6" filter="drop-shadow(0px 3px 3px rgba(0,0,0,0.3))" />
                                <circle cx="250" cy="160" r="8" fill="#FFFFFF" filter="drop-shadow(0px 3px 3px rgba(0,0,0,0.3))" />

                                <!-- Dimension Labels -->
                                <!-- Dead ball area -->
                                <rect x="200" y="8" width="100" height="20" rx="4" fill="var(--boccia-navy)" />
                                <text x="250" y="22" fill="#FFFFFF" font-size="8" font-weight="700" text-anchor="middle">Dead Ball Area</text>
                                <line x1="250" y1="28" x2="250" y2="40" stroke="var(--boccia-navy)" stroke-width="1.5" />

                                <!-- V Line label -->
                                <rect x="390" y="90" width="50" height="20" rx="4" fill="var(--boccia-navy)" />
                                <text x="415" y="103" fill="#FFFFFF" font-size="8" font-weight="700" text-anchor="middle">V Line</text>
                                <path d="M390,100 L310,100" stroke="var(--boccia-navy)" stroke-width="1.5" stroke-dasharray="3,2" fill="none" />

                                <!-- Cross Label -->
                                <rect x="25" y="130" width="50" height="20" rx="4" fill="var(--boccia-navy)" />
                                <text x="50" y="143" fill="#FFFFFF" font-size="8" font-weight="700" text-anchor="middle">Cross</text>
                                <path d="M75,140 L250,80" stroke="var(--boccia-navy)" stroke-width="1.5" stroke-dasharray="3,2" fill="none" />

                                <!-- Playing Box Label -->
                                <rect x="395" y="180" width="70" height="20" rx="4" fill="var(--boccia-navy)" />
                                <text x="430" y="193" fill="#FFFFFF" font-size="8" font-weight="700" text-anchor="middle">Playing Box</text>
                                <path d="M395,190 L340,255" stroke="var(--boccia-navy)" stroke-width="1.5" stroke-dasharray="3,2" fill="none" />

                                <!-- Jack Placement Area Label -->
                                <rect x="90" y="295" width="120" height="20" rx="4" fill="var(--boccia-navy)" />
                                <text x="150" y="308" fill="#FFFFFF" font-size="8" font-weight="700" text-anchor="middle">Jack Placement Area</text>
                                <path d="M150,295 L250,115" stroke="var(--boccia-navy)" stroke-width="1.5" stroke-dasharray="3,2" fill="none" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="feature-list">
                            <div class="feature-item">
                                <div class="feature-icon-circle">
                                    <i class="bi bi-bullseye"></i>
                                </div>
                                <div class="feature-text">
                                    <h4>Inclusive Target Sport</h4>
                                    <p>Boccia is a precision target sport designed for athletes with physical impairments. It is played on a flat indoor court, similar in size to a badminton court.</p>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon-circle">
                                    <i class="bi bi-vinyl-fill"></i>
                                </div>
                                <div class="feature-text">
                                    <h4>Core Gameplay</h4>
                                    <p>The objective is simple: get your red or blue leather balls closer to the white target ball (the jack) than your opponent.</p>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon-circle">
                                    <i class="bi bi-globe2"></i>
                                </div>
                                <div class="feature-text">
                                    <h4>International Standards</h4>
                                    <p>Boccia Sports Federation of India (BSFI) follows the official rules and equipment guidelines regulated globally by BISFed.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 2: HOW TO PLAY BOCCIA ═══════════ -->
    <section class="rules-section bg-white border-top border-bottom">
        <div class="container">
            <h3 class="section-divider-title">2. How to Play Boccia</h3>
            
            <div class="play-steps-grid">
                <div class="step-card">
                    <span class="step-badge">1</span>
                    <span class="step-icon"><i class="bi bi-hand-thumbs-up-fill"></i></span>
                    <h4>Coin Toss</h4>
                    <p>A coin toss decides which side plays red and which plays blue. Red throws the jack first.</p>
                </div>
                
                <div class="step-card">
                    <span class="step-badge">2</span>
                    <span class="step-icon"><i class="bi bi-circle"></i></span>
                    <h4>Jack Thrown</h4>
                    <p>The white target ball (jack) is thrown into the court, past the V Line.</p>
                </div>
                
                <div class="step-card">
                    <span class="step-badge">3</span>
                    <span class="step-icon"><i class="bi bi-record-circle-fill"></i></span>
                    <h4>Balls Played</h4>
                    <p>Sides take turns throwing or rolling their 6 colored balls. The side furthest from the jack always plays next.</p>
                </div>
                
                <div class="step-card">
                    <span class="step-badge">4</span>
                    <span class="step-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                    <h4>Closest Scores</h4>
                    <p>Once all 12 colored balls are thrown, the side with the ball closest to the jack scores 1 point for each ball closer than their opponent's closest ball.</p>
                </div>
                
                <div class="step-card">
                    <span class="step-badge">5</span>
                    <span class="step-icon"><i class="bi bi-trophy-fill"></i></span>
                    <h4>Winner Determined</h4>
                    <p>Matches consist of 4 ends (individual/pairs) or 6 ends (teams). Highest cumulative score wins.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 3: BC CLASSIFICATION SYSTEM ═══════════ -->
    <section id="classification" class="rules-section">
        <div class="container">
            <h3 class="section-divider-title">3. BC Classification System</h3>
            
            <div class="classification-grid">
                <!-- BC1 -->
                <div class="class-card">
                    <div class="class-card-icon">
                        <!-- Custom icon vector representation -->
                        <svg viewBox="0 0 100 100" width="80" height="80">
                            <circle cx="50" cy="30" r="12" fill="var(--boccia-navy)" />
                            <path d="M30 65 L45 50 L48 68 L32 88 M45 50 L58 45 L72 55 L82 72 M58 45 L62 65 L50 88" stroke="var(--boccia-navy)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                        </svg>
                    </div>
                    <h4 style="border-bottom: 2px solid #3B82F6; padding-bottom: 10px;">BC1 Category</h4>
                    <ul class="class-list">
                        <li><span>Hand or Foot Throw</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Assistant Outside Box</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Uses Ramp</span> <i class="bi bi-x-circle-fill"></i></li>
                    </ul>
                    <p class="class-desc">Athletes throw the ball with their hand or foot. They may play with the assistance of an assistant who stays outside the athlete's box to adjust the chair or pass the ball.</p>
                    <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="btn-learn-more">Learn More</a>
                </div>

                <!-- BC2 -->
                <div class="class-card">
                    <div class="class-card-icon">
                        <svg viewBox="0 0 100 100" width="80" height="80">
                            <circle cx="50" cy="30" r="12" fill="#EF4444" />
                            <path d="M35 70 L48 52 L55 70 L42 90 M48 52 L62 48 L76 50 L84 40 M62 48 L65 72 L55 90" stroke="#EF4444" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                        </svg>
                    </div>
                    <h4 style="border-bottom: 2px solid #EF4444; padding-bottom: 10px;">BC2 Category</h4>
                    <ul class="class-list">
                        <li><span>Hand Throw Only</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Assistant in Box</span> <i class="bi bi-x-circle-fill"></i></li>
                        <li><span>Uses Ramp</span> <i class="bi bi-x-circle-fill"></i></li>
                    </ul>
                    <p class="class-desc">Athletes throw the ball with their hand. They are not eligible for any assistance inside the box, performing all actions independently.</p>
                    <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="btn-learn-more">Learn More</a>
                </div>

                <!-- BC3 -->
                <div class="class-card">
                    <div class="class-card-icon">
                        <svg viewBox="0 0 100 100" width="80" height="80">
                            <circle cx="45" cy="25" r="10" fill="#3B82F6" />
                            <!-- Ramper + Ramp representation -->
                            <path d="M25 60 L38 45 L42 62 L32 82 M38 45 L50 42 M50 42 L55 60 L45 82" stroke="#3B82F6" stroke-width="6" stroke-linecap="round" fill="none" />
                            <path d="M58 85 L70 35 L85 85" stroke="#1E293B" stroke-width="4" stroke-linecap="round" fill="none" />
                            <circle cx="75" cy="45" r="5" fill="#EF4444" />
                        </svg>
                    </div>
                    <h4 style="border-bottom: 2px solid #3B82F6; padding-bottom: 10px;">BC3 Category</h4>
                    <ul class="class-list">
                        <li><span>Uses Ramp</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Assistant (Ramper)</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Hand Throw</span> <i class="bi bi-x-circle-fill"></i></li>
                    </ul>
                    <p class="class-desc">Athletes have severe locomotor dysfunction in all four limbs. They use an assistive device (ramp) and an assistant (ramper) who must keep their back to the court.</p>
                    <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="btn-learn-more">Learn More</a>
                </div>

                <!-- BC4 -->
                <div class="class-card">
                    <div class="class-card-icon">
                        <svg viewBox="0 0 100 100" width="80" height="80">
                            <circle cx="50" cy="30" r="12" fill="#10B981" />
                            <path d="M30 65 L45 50 L48 68 L32 88 M45 50 L58 45 L72 52 L85 62 M58 45 L62 65 L50 88" stroke="#10B981" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                        </svg>
                    </div>
                    <h4 style="border-bottom: 2px solid #10B981; padding-bottom: 10px;">BC4 Category</h4>
                    <ul class="class-list">
                        <li><span>Hand Throw Only</span> <i class="bi bi-check-circle-fill"></i></li>
                        <li><span>Assistant in Box</span> <i class="bi bi-x-circle-fill"></i></li>
                        <li><span>Uses Ramp</span> <i class="bi bi-x-circle-fill"></i></li>
                    </ul>
                    <p class="class-desc">Athletes have non-cerebral origin physical impairment (e.g. Muscular Dystrophy). They throw the ball with their hand and are not eligible for assistance.</p>
                    <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="btn-learn-more">Learn More</a>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <div style="background: rgba(255, 255, 255, 0.85); display: inline-block; padding: 20px 40px; border-radius: 50px; border: 1px solid rgba(8, 27, 75, 0.08); box-shadow: 0 10px 25px rgba(8, 27, 75, 0.03); backdrop-filter: blur(5px);">
                    <p class="mb-2" style="font-size: 1rem; color: #1E293B; font-weight: 600; line-height: 1.5;"><i class="bi bi-info-circle" style="color: var(--boccia-navy); margin-right: 5px;"></i> Classification is based on functional ability and reviewed periodically to ensure fair competition.</p>
                    <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="color: var(--boccia-navy); font-size: 1.05rem;">
                        View Classification Guidelines <i class="bi bi-arrow-right-short" style="font-size: 1.2rem;"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 4: EQUIPMENT STANDARDS ═══════════ -->
    <section id="equipment" class="rules-section border-top border-bottom">
        <div class="container">
            <h3 class="section-divider-title">4. Equipment Standards</h3>
            
            <div class="equipment-grid">
                <!-- Competition Balls -->
                <div class="equip-card">
                    <div class="equip-icon-header" style="background: #EFF6FF; color: #3B82F6;">
                        <i class="bi bi-circle-fill" style="text-shadow: 15px 15px 0px #EF4444;"></i>
                    </div>
                    <div class="equip-body">
                        <h4>Competition Balls</h4>
                        <p>Leather balls meeting BISFed standards for size, weight (275g +/- 12g) and circumference (270mm +/- 8mm).</p>
                        <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" class="btn-equip-link">View Specs <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Ramps -->
                <div class="equip-card">
                    <div class="equip-icon-header" style="background: #FFF7ED; color: #F97316;">
                        <i class="bi bi-chevron-double-down"></i>
                    </div>
                    <div class="equip-body">
                        <h4>Ramps</h4>
                        <p>Approved assistive devices for BC3 athletes. Ramps must fit within the athlete's 2.5m x 1m playing box.</p>
                        <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" class="btn-equip-link">View Guidelines <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Court Specifications -->
                <div class="equip-card">
                    <div class="equip-icon-header" style="background: #F0FDF4; color: #22C55E;">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div class="equip-body">
                        <h4>Court Specs</h4>
                        <p>Flat indoor court measuring 12.5m x 6m. The throwing area is divided into 6 distinct boxes.</p>
                        <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" class="btn-equip-link">View Details <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Measuring Tools -->
                <div class="equip-card">
                    <div class="equip-icon-header" style="background: #FAF5FF; color: #A855F7;">
                        <i class="bi bi-rulers"></i>
                    </div>
                    <div class="equip-body">
                        <h4>Measuring Tools</h4>
                        <p>Callipers, feeler gauges, and tape measures used by referees to determine extremely close scores.</p>
                        <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" class="btn-equip-link">View Tools <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 5: OFFICIAL DOCUMENTS ═══════════ -->
    <section id="documents" class="rules-section border-bottom">
        <div class="container">
            <h3 class="section-divider-title">5. Official Documents</h3>
            <div class="doc-row-layout">
                <!-- Anti Doping -->
                <div class="doc-download-card">
                    <i class="bi bi-shield-fill-check main-icon" style="color: #EF4444;"></i>
                    <h4>Anti-Doping (WADA)</h4>
                    <p class="doc-subtext">WADA Anti-Doping Code and International Standards.</p>
                    <div class="doc-meta">
                        <span>PDF • 1.2 MB</span>
                        <span>Updated: 01 May 2026</span>
                    </div>
                    <div class="doc-actions">
                        <a href="https://www.worldboccia.com/documents/#AntiDopingDoc" target="_blank" class="btn-doc-view">View Online</a>
                        <a href="https://www.worldboccia.com/documents/#AntiDopingDoc" download class="btn-doc-dl">Download</a>
                    </div>
                </div>

                <!-- Classification -->
                <div class="doc-download-card">
                    <i class="bi bi-person-badge-fill main-icon" style="color: #3B82F6;"></i>
                    <h4>Athlete Classification</h4>
                    <p class="doc-subtext">Medical and functional classification rules and procedures.</p>
                    <div class="doc-meta">
                        <span>PDF • 2.4 MB</span>
                        <span>Updated: 15 Apr 2026</span>
                    </div>
                    <div class="doc-actions">
                        <a href="https://www.worldboccia.com/documents/#ClassificationDoc" target="_blank" class="btn-doc-view">View Online</a>
                        <a href="https://www.worldboccia.com/documents/#ClassificationDoc" download class="btn-doc-dl">Download</a>
                    </div>
                </div>

                <!-- Equipment -->
                <div class="doc-download-card">
                    <i class="bi bi-rulers main-icon" style="color: #10B981;"></i>
                    <h4>Equipment Guidelines</h4>
                    <p class="doc-subtext">Official equipment standards and testing procedures.</p>
                    <div class="doc-meta">
                        <span>PDF • 1.8 MB</span>
                        <span>Updated: 10 Apr 2026</span>
                    </div>
                    <div class="doc-actions">
                        <a href="https://www.worldboccia.com/about-boccia/sport-equipment/" target="_blank" class="btn-doc-view">View Online</a>
                        <a href="uploads/documents/World-Boccia-Rules-2025-2028-v1.2.1-2.pdf" download class="btn-doc-dl">Download</a>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="https://www.worldboccia.com/documents/" target="_blank" class="btn-view-all-docs">View All Documents</a>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 6: INTERACTIVE COURT DIAGRAM ═══════════ -->
    <section id="court-diagram" class="rules-section">
        <div class="container">
            <h3 class="section-divider-title">6. Interactive Court Diagram</h3>
            
            <div class="row align-items-center g-5">
                <!-- Left: SVG Diagram -->
                <div class="col-lg-6">
                    <div class="interactive-court-container">
                        <div class="court-svg-holder">
                            <!-- Court Overhead Top-Down View SVG with Hotspots -->
                            <svg viewBox="0 0 400 500" width="100%" height="auto" id="interactiveCourtSvg">
                                <!-- Base Background -->
                                <rect x="0" y="0" width="400" height="500" fill="#0D2B66" />
                                
                                <!-- Court Boundary Lines -->
                                <rect x="40" y="40" width="320" height="420" fill="none" stroke="#FFFFFF" stroke-width="3" />
                                
                                <!-- 6 Playing Boxes lines at bottom -->
                                <line x1="40" y1="380" x2="360" y2="380" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="93.3" y1="380" x2="93.3" y2="460" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="146.6" y1="380" x2="146.6" y2="460" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="200" y1="380" x2="200" y2="460" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="253.3" y1="380" x2="253.3" y2="460" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="306.6" y1="380" x2="306.6" y2="460" stroke="#FFFFFF" stroke-width="2" />

                                <!-- V Line -->
                                <polyline points="93.3,240 200,300 306.6,240" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-dasharray="4,3" />

                                <!-- Cross -->
                                <line x1="195" y1="180" x2="205" y2="180" stroke="#FFFFFF" stroke-width="2" />
                                <line x1="200" y1="175" x2="200" y2="185" stroke="#FFFFFF" stroke-width="2" />

                                <!-- Hotspot interactive circles (1-indexed based on list on right) -->
                                <!-- 1. Playing Box -->
                                <circle cx="200" cy="420" r="16" fill="rgba(255, 153, 51, 0.4)" stroke="var(--boccia-saffron)" stroke-width="2" class="court-hotspot" data-index="0" />
                                <text x="200" y="424" fill="#FFFFFF" font-size="11" font-weight="800" text-anchor="middle" pointer-events="none">1</text>
                                
                                <!-- 2. V Line -->
                                <circle cx="200" cy="300" r="16" fill="rgba(255, 153, 51, 0.4)" stroke="var(--boccia-saffron)" stroke-width="2" class="court-hotspot" data-index="1" />
                                <text x="200" y="304" fill="#FFFFFF" font-size="11" font-weight="800" text-anchor="middle" pointer-events="none">2</text>

                                <!-- 3. Cross -->
                                <circle cx="200" cy="180" r="16" fill="rgba(255, 153, 51, 0.4)" stroke="var(--boccia-saffron)" stroke-width="2" class="court-hotspot" data-index="2" />
                                <text x="200" y="184" fill="#FFFFFF" font-size="11" font-weight="800" text-anchor="middle" pointer-events="none">3</text>

                                <!-- 4. Jack Placement Area -->
                                <circle cx="200" cy="240" r="16" fill="rgba(255, 153, 51, 0.4)" stroke="var(--boccia-saffron)" stroke-width="2" class="court-hotspot" data-index="3" />
                                <text x="200" y="244" fill="#FFFFFF" font-size="11" font-weight="800" text-anchor="middle" pointer-events="none">4</text>

                                <!-- 5. Dead Ball Area -->
                                <circle cx="200" cy="20" r="12" fill="rgba(255, 153, 51, 0.4)" stroke="var(--boccia-saffron)" stroke-width="2" class="court-hotspot" data-index="4" />
                                <text x="200" y="24" fill="#FFFFFF" font-size="9" font-weight="800" text-anchor="middle" pointer-events="none">5</text>
                            </svg>
                            <div class="tooltip-custom" id="courtTooltip">Tooltip</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Descriptions list -->
                <div class="col-lg-6">
                    <div class="interactive-court-container">
                        <ul class="court-info-list" id="courtInfoList">
                            <li class="court-info-item active" data-index="0">
                                <div class="court-marker-dot">1</div>
                                <div class="court-info-text">
                                    <h5>Playing Box</h5>
                                    <p>Area where athletes must keep at least one part of their body or chair during delivery.</p>
                                </div>
                            </li>
                            <li class="court-info-item" data-index="1">
                                <div class="court-marker-dot">2</div>
                                <div class="court-info-text">
                                    <h5>V Line</h5>
                                    <p>Marks the front limit of the throwing area. The jack must cross this line to be in play.</p>
                                </div>
                            </li>
                            <li class="court-info-item" data-index="2">
                                <div class="court-marker-dot">3</div>
                                <div class="court-info-text">
                                    <h5>Cross</h5>
                                    <p>Reference point for symmetry, alignment, and starting placements during play.</p>
                                </div>
                            </li>
                            <li class="court-info-item" data-index="3">
                                <div class="court-marker-dot">4</div>
                                <div class="court-info-text">
                                    <h5>Jack Placement Area</h5>
                                    <p>Area between the V Line and the cross where the jack must land to be considered valid.</p>
                                </div>
                            </li>
                            <li class="court-info-item" data-index="4">
                                <div class="court-marker-dot">5</div>
                                <div class="court-info-text">
                                    <h5>Dead Ball Area</h5>
                                    <p>Area beyond the far line of the court. Balls crossing this boundary become dead and out of play.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ CTA FOOTER BAND ═══════════ -->
    <div class="container mb-5">
        <div class="boccia-footer-cta">
            <div class="cta-left">
                <div class="cta-icon-holder">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div class="cta-text">
                    <h3>Boccia is more than a sport — it's about precision, strategy and inclusion.</h3>
                    <p>PLAY. RESPECT. <span>INSPIRE.</span></p>
                </div>
            </div>
            <a href="page.php?section=get-involved&slug=become-member" class="btn-cta-action">Get Involved <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const hotspots = document.querySelectorAll(".court-hotspot");
    const listItems = document.querySelectorAll(".court-info-item");

    function activateIndex(index) {
        hotspots.forEach(hs => hs.classList.remove("active"));
        listItems.forEach(li => li.classList.remove("active"));
        
        const matchingHs = document.querySelector(`.court-hotspot[data-index="${index}"]`);
        const matchingLi = document.querySelector(`.court-info-item[data-index="${index}"]`);
        
        if (matchingHs) matchingHs.classList.add("active");
        if (matchingLi) matchingLi.classList.add("active");
    }

    hotspots.forEach(hs => {
        hs.addEventListener("mouseenter", function () {
            const index = this.getAttribute("data-index");
            activateIndex(index);
        });
        hs.addEventListener("click", function () {
            const index = this.getAttribute("data-index");
            activateIndex(index);
        });
    });

    listItems.forEach(li => {
        li.addEventListener("mouseenter", function () {
            const index = this.getAttribute("data-index");
            activateIndex(index);
        });
    });
});
</script>

<?php
include __DIR__ . '/footer.php';
?>
