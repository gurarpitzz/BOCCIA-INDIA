<?php
// includes/anti-doping-page.php - Custom Template for Anti-Doping Regulations & Principles
$page_title = "Anti-Doping Regulations | Boccia India";
$meta_desc = "Learn about the anti-doping principles, values of clean sport, Therapeutic Use Exemptions (TUE), and rules governing Para Boccia in India.";
$canonical_url = "page.php?section=sport&slug=anti-doping";

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
    --boccia-green: #10B981;
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
.antidoping-hero {
    background: linear-gradient(135deg, #051336 0%, #0d235c 50%, #10b981 100%);
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
    color: var(--boccia-saffron);
}

.hero-desc {
    font-size: 1.1rem;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.9);
    max-width: 650px;
    margin-bottom: 0;
}

/* --- Section Layout --- */
.antidoping-content-section {
    padding: 80px 0;
    background: url('bg.png') no-repeat center center;
    background-size: cover;
    position: relative;
}

/* --- Tabs Styling --- */
.antidoping-tabs {
    display: inline-flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 45px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50px;
    padding: 8px;
    border: 1px solid rgba(8, 27, 75, 0.08);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.05);
}

.tab-btn {
    background: none;
    border: none;
    font-family: var(--font-heading-sub);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--boccia-text-muted);
    padding: 12px 28px;
    cursor: pointer;
    border-radius: 40px;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: var(--boccia-navy);
    background: rgba(8, 27, 75, 0.03);
}

.tab-btn.active {
    color: #ffffff;
    background-color: var(--boccia-green);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.tab-pane {
    display: none;
    animation: fadeIn 0.5s ease;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* --- Container Card Wrappers --- */
.intro-text-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 20px;
    padding: 35px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
}

.spirit-values-wrapper-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
    margin-top: 40px;
}

.tue-intro-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 20px;
    padding: 35px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
    margin-bottom: 35px;
}

.faq-wrapper-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
}

/* --- Intro Card Layout --- */
.intro-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 40px;
    align-items: center;
    margin-bottom: 50px;
}

@media (max-width: 991px) {
    .intro-grid {
        grid-template-columns: 1fr;
    }
}

.intro-image-wrapper {
    background: var(--boccia-card-bg);
    border-radius: 24px;
    padding: 15px;
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.06);
    border: 1px solid rgba(8, 27, 75, 0.04);
}

.intro-image-wrapper img {
    width: 100%;
    height: auto;
    border-radius: 18px;
    object-fit: cover;
}

.spirit-section-title {
    font-family: var(--font-heading-sub);
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 25px;
    border-left: 4px solid var(--boccia-green);
    padding-left: 15px;
}

/* --- Highlight Box & Quotes --- */
.anti-doping-highlight-box {
    border-left: 6px solid var(--boccia-green);
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.04) 0%, transparent 100%);
    padding: 30px;
    border-radius: 0 20px 20px 0;
    margin-bottom: 25px;
}

.anti-doping-quote {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--boccia-navy);
    line-height: 1.7;
    margin-bottom: 15px;
    font-family: var(--font-heading-sub);
}

.anti-doping-highlight-box p {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--boccia-text-muted);
    margin: 0;
}

.clean-sport-banner {
    background: linear-gradient(135deg, var(--boccia-navy) 0%, #0d235c 100%);
    border-radius: 24px;
    padding: 45px;
    color: #ffffff;
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.15);
    border-left: 8px solid var(--boccia-saffron);
    margin-top: 50px;
}

.clean-sport-banner h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--boccia-saffron);
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.clean-sport-banner p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: rgba(255, 255, 255, 0.9);
}

/* --- Values Grid --- */
.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.value-card {
    background: var(--boccia-card-bg);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(8, 27, 75, 0.05);
    box-shadow: 0 4px 15px rgba(8, 27, 75, 0.02);
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 15px;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
}

.value-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background-color: var(--boccia-green);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.value-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.2);
}

.value-card:hover::before {
    opacity: 1;
}

.value-icon {
    font-size: 1.6rem;
    color: var(--boccia-green);
    background: rgba(16, 185, 129, 0.06);
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.value-card:hover .value-icon {
    background: var(--boccia-green);
    color: #ffffff;
    transform: scale(1.05);
}

.value-card-text {
    display: flex;
    flex-direction: column;
}

.value-name {
    font-family: var(--font-heading-sub);
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 2px;
}

.value-desc {
    font-size: 0.82rem;
    color: var(--boccia-text-muted);
    line-height: 1.4;
    margin: 0;
}

/* --- Link Cards --- */
.links-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

@media (max-width: 767px) {
    .links-grid {
        grid-template-columns: 1fr;
    }
}

.link-card {
    background: var(--boccia-card-bg);
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.link-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}

.link-card h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 12px;
}

.link-card p {
    font-size: 0.9rem;
    color: var(--boccia-text-muted);
    line-height: 1.5;
    margin-bottom: 20px;
}

.link-btn {
    font-family: var(--font-heading-sub);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
    background-color: var(--boccia-green);
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s ease;
}

.link-btn:hover {
    background-color: var(--boccia-navy);
    color: #ffffff;
}

/* --- Contact & Info Callout --- */
.contact-callout {
    background: linear-gradient(135deg, #051336 0%, #0d235c 100%);
    color: #ffffff;
    border-radius: 24px;
    padding: 40px;
    margin-top: 40px;
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.1);
}

.contact-callout h3 {
    font-family: var(--font-heading-sub);
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--boccia-saffron);
    margin-bottom: 15px;
}

.contact-callout p {
    font-size: 1rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

.contact-email {
    font-weight: 700;
    color: var(--boccia-saffron);
    text-decoration: none;
}

.contact-email:hover {
    text-decoration: underline;
}

/* --- FAQ Accordion --- */
.faq-accordion {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.faq-item {
    background: var(--boccia-card-bg);
    border-radius: 16px;
    border: 1px solid rgba(8, 27, 75, 0.05);
    box-shadow: 0 5px 20px rgba(8, 27, 75, 0.02);
    overflow: hidden;
}

.faq-question {
    width: 100%;
    background: none;
    border: none;
    text-align: left;
    padding: 20px 25px;
    font-family: var(--font-heading-sub);
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--boccia-navy);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.faq-question:hover {
    background-color: #F8FAFC;
}

.faq-question i {
    font-size: 1.2rem;
    color: var(--boccia-green);
    transition: all 0.3s ease;
}

.faq-item.open .faq-question {
    background-color: var(--boccia-green);
    color: #ffffff;
}

.faq-item.open .faq-question i {
    color: #ffffff;
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    background-color: #ffffff;
}

.faq-item.open .faq-answer {
    max-height: 1000px;
}

.faq-answer-content {
    padding: 25px;
    font-size: 1rem;
    line-height: 1.7;
    color: var(--boccia-text-dark);
}

/* --- Mobile Responsiveness --- */
@media (max-width: 767px) {
    .antidoping-hero {
        padding: 60px 0 40px 0;
    }
    .hero-title {
        font-size: 2.5rem;
    }
    .antidoping-content-section {
        padding: 40px 0;
    }
    .antidoping-tabs {
        display: flex;
        flex-direction: column;
        width: 100%;
        border-radius: 20px;
        padding: 8px;
        gap: 8px;
    }
    .tab-btn {
        width: 100%;
        padding: 10px 20px;
        font-size: 0.95rem;
        border-radius: 15px;
        text-align: center;
    }
    .intro-grid {
        gap: 20px;
        margin-bottom: 30px;
    }
    .intro-image-wrapper {
        border-radius: 16px;
        padding: 10px;
    }
    .intro-text-card {
        padding: 20px;
    }
    .anti-doping-highlight-box {
        padding: 20px;
        border-radius: 0 16px 16px 0;
    }
    .spirit-values-wrapper-card {
        padding: 20px;
        margin-top: 30px;
        border-radius: 20px;
    }
    .values-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    .value-card {
        padding: 15px;
        border-radius: 14px;
    }
    .clean-sport-banner {
        padding: 25px 20px;
        margin-top: 30px;
        border-left-width: 6px;
        border-radius: 20px;
    }
    .clean-sport-banner h4 {
        font-size: 1.4rem;
    }
    .tue-intro-card {
        padding: 20px;
        margin-bottom: 25px;
        border-radius: 20px;
    }
    .link-card {
        padding: 20px;
        border-radius: 16px;
    }
    .contact-callout {
        padding: 25px 20px;
        margin-top: 30px;
        border-radius: 20px;
    }
    .faq-wrapper-card {
        padding: 20px;
        border-radius: 20px;
    }
    .faq-question {
        padding: 15px 20px;
        font-size: 1.05rem;
    }
    .faq-answer-content {
        padding: 20px;
    }
}
</style>

<div class="antidoping-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.png');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Rules & Regulations --</span>
                <h1 class="board-hero-title">ANTI-DOPING PROGRAM</h1>
                <p class="board-hero-text">
                    BISFed and BSFI seek to maintain the integrity of Boccia by promoting a clean, fair, and honest competitive field of play.
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="antidoping-content-section">
        <div class="container">
            <!-- Tabs -->
            <div class="text-center">
                <div class="antidoping-tabs">
                    <button class="tab-btn active" onclick="switchTab('principles')">Principles & Values</button>
                    <button class="tab-btn" onclick="switchTab('tue-links')">TUE & Useful Links</button>
                    <button class="tab-btn" onclick="switchTab('faqs')">FAQs</button>
                </div>
            </div>

            <!-- Tab 1: Principles & Values -->
            <div id="principles" class="tab-pane active">
                <div class="intro-grid">
                    <div class="intro-text-card">
                        <div class="anti-doping-highlight-box">
                            <div class="anti-doping-quote">"Doping has no place in Boccia."</div>
                            <p>
                                The use of doping substances or doping methods to enhance performance is fundamentally wrong and is detrimental to the overall spirit of sport. Drug misuse can be harmful to an Athlete’s health and to other Athletes competing in the sport. It severely damages the integrity, image, and value of sport.
                            </p>
                        </div>
                        <p class="text-secondary mb-0" style="line-height: 1.75; font-size: 1.05rem;">
                            To achieve integrity and fairness in sport, a commitment to a clean field of play is critical. BISFed seeks to maintain the integrity of Boccia by running a comprehensive anti-doping program focusing equally on education, prevention, and testing, with consequent sanctioning of those who break the rules.
                        </p>
                    </div>
                    <div class="intro-image-wrapper">
                        <img src="about boccia/Antidoping.png" alt="Anti-Doping Clean Sport" />
                    </div>
                </div>

                <div class="spirit-values-wrapper-card">
                    <h3 class="spirit-section-title">Principles and values of clean sport</h3>
                    <p class="text-secondary mb-3" style="line-height: 1.7; font-size: 1.05rem;">
                        Anti-doping programs seek to maintain the integrity of sport in terms of respect for rules, other competitors, fair competition, a level playing field, and the value of clean sport to the world.
                    </p>
                    <p class="text-secondary mb-4" style="line-height: 1.7; font-size: 1.05rem;">
                        The spirit of sport is the celebration of the human spirit, body, and mind. It is the essence of Paralympism and is reflected in the values we find in and through sport, including:
                    </p>

                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-heart-pulse"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Health</div>
                                <p class="value-desc">Protecting the physical and mental well-being of every athlete.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-shield-check"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Ethics & Fair Play</div>
                                <p class="value-desc">Upholding honesty, respect, and sportsmanship above all.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-person-check"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Athletes' Rights</div>
                                <p class="value-desc">Safeguarding equality, voice, and fairness in competition.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-trophy"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Excellence</div>
                                <p class="value-desc">Pushing human limits through dedication and honest effort.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-book"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Character & Education</div>
                                <p class="value-desc">Fostering life skills and moral development through sport.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-emoji-smile"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Fun & Joy</div>
                                <p class="value-desc">Celebrating the pure enjoyment and happiness of playing.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-people"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Teamwork</div>
                                <p class="value-desc">Uniting strengths to achieve common goals together.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-award"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Dedication</div>
                                <p class="value-desc">Pledging hard work, resilience, and focus to your sport.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-scale"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Respect for Rules</div>
                                <p class="value-desc">Adhering to the codes and laws that govern our game.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-people-fill"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Respect for Others</div>
                                <p class="value-desc">Valuing competitors, officials, and yourself equally.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-lightning-fill"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Courage</div>
                                <p class="value-desc">Displaying strength and integrity under pressure.</p>
                            </div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon"><i class="bi bi-globe"></i></div>
                            <div class="value-card-text">
                                <div class="value-name">Community</div>
                                <p class="value-desc">Building solidarity, friendship, and shared belonging.</p>
                            </div>
                        </div>
                    </div>

                    <div class="clean-sport-banner">
                        <h4>We Play True</h4>
                        <p class="mb-3">
                            The spirit of sport is expressed in how we play true. BISFed embodies these values – we believe in a clean and fair field of play, and doping stands in direct contradiction to what Boccia represents.
                        </p>
                        <p class="mb-0">
                            Our goal is to empower all Boccia athletes to stay on top of their game – not just Athletes, but coaches, administrators, medical personnel and all other members of the Athlete entourage. We encourage everyone to take the time to review this section and get informed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab 2: TUE & Useful Links -->
            <div id="tue-links" class="tab-pane">
                <div class="tue-intro-card">
                    <h3 class="spirit-section-title">Therapeutic Use Exemptions (TUE)</h3>
                    <p class="text-secondary mb-0" style="line-height: 1.7;">
                        Athletes, like all people, may have illnesses or conditions that require them to take medications. If the medication an athlete is required to take to treat an illness or condition happens to fall under WADA's Prohibited List, a <strong>Therapeutic Use Exemption (TUE)</strong> may give that athlete the authorization to take the needed medicine.
                    </p>
                </div>

                <div class="links-grid mt-4">
                    <div class="link-card">
                        <div>
                            <h4>WADA ISTUE Standard</h4>
                            <p>WADA International Standard for Therapeutic Use Exemptions (ISTUE) official guidelines and criteria.</p>
                        </div>
                        <a href="https://www.wada-ama.org/en/resources/world-anti-doping-code-and-international-standards/international-standard-therapeutic-use" target="_blank" class="link-btn">View Resource</a>
                    </div>

                    <div class="link-card">
                        <div>
                            <h4>TUE Application Checklist</h4>
                            <p>WADA checklists and verification procedures to prepare your Therapeutic Use Exemptions application.</p>
                        </div>
                        <a href="https://www.wada-ama.org/en/search?q=Checklists%20for%20TUE%20Applications&filters%5Bcontent_type%5D%5B%5D=%22resource%22" target="_blank" class="link-btn">View Checklist</a>
                    </div>

                    <div class="link-card">
                        <div>
                            <h4>ISTUE Guidelines 2021</h4>
                            <p>Guidelines for the 2021 International Standard for Therapeutic Use Exemptions (ISTUE).</p>
                        </div>
                        <a href="https://www.wada-ama.org/en/resources/world-anti-doping-program/guidelines-international-standard-therapeutic-use-exemptions" target="_blank" class="link-btn">View Guidelines</a>
                    </div>

                    <div class="link-card">
                        <div>
                            <h4>WADA ADEL Platform</h4>
                            <p>Anti-Doping Education and Learning (ADEL) program for athletes, coaches, and sports support personnel.</p>
                        </div>
                        <a href="https://adel.wada-ama.org/learn" target="_blank" class="link-btn">Access ADEL</a>
                    </div>
                </div>

                <div class="contact-callout">
                    <h3>Contact Information</h3>
                    <p>
                        For any further information and questions in relation to BISFed’s personal information practices, please contact <a href="mailto:admin@bisfed.com" class="contact-email">admin@bisfed.com</a>.
                    </p>
                    <p class="mb-0">
                        If you have a doubt as regards to which organization you should apply for a TUE, or as to the recognition process, or any other question about TUEs, please contact: <a href="mailto:admin@bisfed.com" class="contact-email">admin@bisfed.com</a>.
                    </p>
                </div>
            </div>

            <!-- Tab 3: FAQs -->
            <div id="faqs" class="tab-pane">
                <div class="faq-wrapper-card">
                    <h3 class="spirit-section-title text-center mb-5">Frequently Asked Questions</h3>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                Why is anti-doping important?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    Anti-doping is crucial to protect the health of athletes, ensure a level playing field, and maintain the integrity and spirit of clean sport. By preventing the use of performance-enhancing substances and methods, we ensure that victories are earned solely through dedication, training, skill, and genuine talent.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                What is doping?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    Doping is defined by the World Anti-Doping Agency (WADA) as the occurrence of one or more Anti-Doping Rule Violations (ADRVs) set forth in the World Anti-Doping Code. This includes the presence of a prohibited substance in an athlete's bodily sample, the use or attempted use of prohibited substances/methods, refusing or evading sample collection, tampering, trafficking, or possessing prohibited substances.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                Who is subject to the anti-doping rules?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    All athletes participating in Para Boccia under the jurisdiction of BISFed, the Boccia Sports Federation of India (BSFI), or any member national federation are subject to the anti-doping rules. Additionally, Athlete Support Personnel—including coaches, trainers, managers, medical staff, agents, and administrators—are also bound by these regulations.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                Who governs anti-doping?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    Globally, anti-doping is governed by the World Anti-Doping Agency (WADA) and implemented by international sport federations like BISFed. Within India, the National Anti-Doping Agency (NADA India) is the official body responsible for promoting, coordinating, and monitoring the anti-doping program in sports across the country in alignment with the WADA Code.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
function switchTab(tabId) {
    // Hide all panes
    const panes = document.querySelectorAll('.tab-pane');
    panes.forEach(pane => pane.classList.remove('active'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Show clicked pane
    document.getElementById(tabId).classList.add('active');
    
    // Set clicked button to active
    event.currentTarget.classList.add('active');
}

function toggleFaq(btn) {
    const item = btn.parentElement;
    const isOpen = item.classList.contains('open');
    
    // Close all FAQs
    const allItems = document.querySelectorAll('.faq-item');
    allItems.forEach(i => i.classList.remove('open'));
    
    // If it was closed, open it
    if (!isOpen) {
        item.classList.add('open');
    }
}
</script>

<?php
include __DIR__ . '/footer.php';
?>
