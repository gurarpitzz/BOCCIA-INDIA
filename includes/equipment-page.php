<?php
// includes/equipment-page.php - Custom Template for Sport Equipment Regulations & Purchase
$page_title = "Equipment Specifications | Boccia India";
$meta_desc = "Learn about the official testing standards, roll tests, circumference, and weight specifications for Para Boccia equipment.";
$canonical_url = "page.php?section=sport&slug=equipment";

include __DIR__ . '/header.php';
?>

<!-- Add Google Fonts and Bootstrap Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

body {
    background-color: var(--boccia-light);
    color: var(--boccia-text-dark);
    font-family: var(--font-body-custom);
}

/* Content wrapper background integration */
.equipment-content-section {
    padding: 60px 0 80px 0;
    background: url('about boccia/overview bg.webp') no-repeat center center;
    background-size: cover;
    position: relative;
}

/* --- Tabs Styling --- */
.equipment-tabs {
    display: inline-flex;
    justify-content: center;
    gap: 10px;
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

/* --- Content Cards & Grid --- */
.equipment-intro-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 20px;
    padding: 35px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
    margin-bottom: 35px;
    border-left: 6px solid var(--boccia-green);
}

.equipment-intro-card p {
    font-size: 1.15rem;
    line-height: 1.7;
    color: var(--boccia-navy);
    font-weight: 600;
    margin: 0;
}

.specs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

@media (max-width: 991px) {
    .specs-grid {
        grid-template-columns: 1fr;
    }
}

.spec-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 35px 30px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.spec-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}

.spec-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    background: rgba(16, 185, 129, 0.08);
    color: var(--boccia-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 25px;
}

.spec-card h3 {
    font-family: var(--font-heading-sub);
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 15px;
}

.spec-card p {
    font-size: 0.95rem;
    line-height: 1.7;
    color: var(--boccia-text-muted);
    margin: 0;
}

/* --- Purchase Info Block --- */
.purchase-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.market-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
    margin-bottom: 35px;
}

.market-card h3 {
    font-family: var(--font-heading-sub);
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 15px;
}

.market-card p {
    font-size: 1.1rem;
    line-height: 1.7;
    color: var(--boccia-text-dark);
    margin: 0;
}

.contact-card {
    background: linear-gradient(135deg, var(--boccia-navy) 0%, #0d235c 100%);
    border-radius: 24px;
    padding: 45px;
    color: #ffffff;
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.15);
    border-left: 8px solid var(--boccia-saffron);
}

.contact-card h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--boccia-saffron);
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.contact-card p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.contact-details-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.contact-detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(255, 255, 255, 0.08);
    padding: 15px 25px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.contact-detail-item i {
    font-size: 1.5rem;
    color: var(--boccia-saffron);
}

.contact-detail-item a {
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.05rem;
    transition: color 0.3s ease;
}

.contact-detail-item a:hover {
    color: var(--boccia-saffron);
}

/* --- Mobile Responsiveness --- */
@media (max-width: 767px) {
    .equipment-content-section {
        padding: 40px 0;
    }
    .equipment-tabs {
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
    .equipment-intro-card {
        padding: 20px;
        border-radius: 16px;
    }
    .equipment-intro-card p {
        font-size: 1.05rem;
    }
    .specs-grid {
        gap: 15px;
    }
    .spec-card {
        padding: 20px;
        border-radius: 16px;
    }
    .spec-icon-wrapper {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    .spec-card h3 {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    .market-card {
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 25px;
    }
    .market-card h3 {
        font-size: 1.4rem;
    }
    .market-card p {
        font-size: 1rem;
    }
    .contact-card {
        padding: 25px 20px;
        border-radius: 20px;
        border-left-width: 6px;
    }
    .contact-card h4 {
        font-size: 1.4rem;
        margin-bottom: 15px;
    }
    .contact-card p {
        font-size: 1rem;
        margin-bottom: 20px;
    }
    .contact-details-grid {
        flex-direction: column;
        gap: 12px;
    }
    .contact-detail-item {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 15px;
        border-radius: 10px;
    }
    .contact-detail-item a {
        word-break: break-all;
        font-size: 0.95rem;
    }
}
</style>

<div class="equipment-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Equipment --</span>
                <h1 class="board-hero-title">EQUIPMENT STANDARDS</h1>
                <p class="board-hero-text">
                    Official parameters, compliance testing procedures, and standards for Boccia balls, ramps, and assistive competitive devices.
                </p>
            </div>
        </div>
    </section>

    <!-- Content Section with Toggle Tabs -->
    <section class="equipment-content-section">
        <div class="container">
            <!-- Tabs Toggle Pill -->
            <div class="text-center mb-5">
                <div class="equipment-tabs">
                    <button class="tab-btn active" onclick="switchTab('testing-tab')">Testing Standards</button>
                    <button class="tab-btn" onclick="switchTab('purchase-tab')">Purchase Inquiries</button>
                </div>
            </div>

            <!-- Tab 1: Testing Standards -->
            <div id="testing-tab" class="tab-pane active">
                <div class="equipment-intro-card scroll-reveal">
                    <p>
                        All testing devices required to conduct a tournament must be approved by the BISFed Technical Delegate and or Head Referee of each sanctioned event.
                    </p>
                </div>

                <div class="specs-grid">
                    <!-- Spec 1: Roll Test -->
                    <div class="spec-card">
                        <div class="spec-icon-wrapper">
                            <i class="bi bi-arrow-down-right-square"></i>
                        </div>
                        <h3>Roll Test Standards</h3>
                        <p>
                            The ball must roll under its own weight down a 290mm ramp consisting of a pair of aluminium bars centred 50mm apart set at 25 degrees. Exiting the ramp, the ball must travel at least 175mm in a straight line along the exit plate. Pass criteria: exits on at least one of three attempts.
                        </p>
                    </div>

                    <!-- Spec 2: Circumference -->
                    <div class="spec-card">
                        <div class="spec-icon-wrapper">
                            <i class="bi bi-circle"></i>
                        </div>
                        <h3>Circumference Limits</h3>
                        <p>
                            The circumference of the competition ball should be 270mm +/- 8mm. Testing is conducted using a BISFed STANDARD template (7–7.5mm thickness) containing two gauge holes: one measuring 262mm and another measuring 278mm.
                        </p>
                    </div>

                    <!-- Spec 3: Weight -->
                    <div class="spec-card">
                        <div class="spec-icon-wrapper">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <h3>Weight Limit Specifications</h3>
                        <p>
                            The weight of each competition ball should be 275g +/- 12g. During official inspections, each ball will be tested using a precision digital scale accurate to within 0.01g.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Purchase & Inquiries -->
            <div id="purchase-tab" class="tab-pane">
                <div class="purchase-wrapper">
                    <div class="market-card">
                        <h3>Boccia Balls & Ramps</h3>
                        <p>
                            There are many Boccia Balls and Ramps on the market, all very much dependent on the level of Boccia you wish to play.
                        </p>
                    </div>

                    <div class="contact-card">
                        <h4>Purchase Inquiries</h4>
                        <p>If you are interested in buying official Boccia equipment, kindly reach out directly to the BSFI administrative team:</p>
                        
                        <div class="contact-details-grid">
                            <div class="contact-detail-item">
                                <i class="bi bi-envelope-fill"></i>
                                <a href="mailto:Bocciaindia@gmail.com">Bocciaindia@gmail.com</a>
                            </div>
                            <div class="contact-detail-item">
                                <i class="bi bi-telephone-fill"></i>
                                <a href="tel:9855222006">9855222006</a>
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
</script>

<?php
include __DIR__ . '/footer.php';
?>
