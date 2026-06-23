<?php
// includes/classification-page.php - Custom Template for Sport Classification
$page_title = "Athlete Classification | Boccia India";
$meta_desc = "Learn about the World Boccia Classification system. Explore the four sport classes (BC1, BC2, BC3, BC4) designed to ensure fair competition.";
$canonical_url = "page.php?section=sport&slug=classification";

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
    --boccia-green: #10B981;
    --boccia-card-bg: #FFFFFF;
    --boccia-text-dark: #1E293B;
    --boccia-text-muted: #64748B;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

/* Hide default sport-classes intro because we have the board-hero */
.classification-page-wrapper .classes-intro {
    display: none !important;
}

/* Content wrapper background integration */
.classification-content-section {
    padding: 60px 0 80px 0;
    background: url('bg.webp') no-repeat center center;
    background-size: cover;
    position: relative;
}

/* --- Tabs Styling (Match Anti-Doping Pill Style) --- */
.classification-tabs-container {
    text-align: center;
    margin-bottom: 50px;
}

.classification-tabs {
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

/* Adjust standalone sport-classes padding when inside tab pane */
.classification-page-wrapper .sport-classes {
    padding: 0 !important;
    background: none !important;
}

/* --- FAQ Card & Accordion Styling --- */
.faq-wrapper-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 45px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
}

.faq-section-title {
    font-family: var(--font-heading-sub);
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--boccia-navy);
    margin-bottom: 35px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    border-left: 4px solid var(--boccia-green);
    padding-left: 15px;
}

.faq-accordion {
    max-width: 850px;
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
    .classification-content-section {
        padding: 40px 0;
    }
    .classification-tabs {
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
    .faq-wrapper-card {
        padding: 20px;
        border-radius: 20px;
    }
    .faq-section-title {
        font-size: 1.4rem;
        margin-bottom: 25px;
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

<div class="classification-page-wrapper">
    <!-- Hero Section -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.92) 0%, rgba(7, 25, 84, 0.82) 35%, rgba(7, 25, 84, 0.55) 55%, rgba(7, 25, 84, 0.15) 75%, transparent 100%), url('board/board%20bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Classification --</span>
                <h1 class="board-hero-title">WORLD BOCCIA CLASSIFICATION</h1>
                <p class="board-hero-text">
                    To ensure fair competition, classification groups athletes based on functional ability. Athletes undergo specialized evaluations to assign their profile category.
                </p>
            </div>
        </div>
    </section>

    <!-- Content Section with Toggle Tabs -->
    <section class="classification-content-section">
        <div class="container">
            <!-- Tabs Toggle Pill -->
            <div class="text-center mb-5">
                <div class="classification-tabs">
                    <button class="tab-btn active" onclick="switchTab('sport-classes-tab')">Sport Classes</button>
                    <button class="tab-btn" onclick="switchTab('faqs-tab')">FAQs</button>
                </div>
            </div>

            <!-- Tab 1: Sport Classes Grid -->
            <div id="sport-classes-tab" class="tab-pane active">
                <?php include __DIR__ . '/about-boccia/sport-classes.php'; ?>
            </div>

            <!-- Tab 2: FAQs Accordion -->
            <div id="faqs-tab" class="tab-pane">
                <div class="faq-wrapper-card">
                    <h3 class="faq-section-title text-center">Frequently Asked Questions</h3>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                What is classification?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    Classification is the system that determines eligibility and groups athletes in Para sports based on their functional capacity. In Boccia, this ensures that athletes compete against others with similar levels of physical impairment, focusing the outcome of matches on athletic skill, tactics, and fitness.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                Why is classification required?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    Classification is required to guarantee fair, equitable, and safe competition. By grouping athletes based on functional capacity rather than degree of disability, it minimizes the impact of impairments on the competition outcome, allowing sporting excellence to determine the winner.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                When is classification required?
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    If you want to compete in Boccia as an athlete with a disability, you must undergo a sports-specific classification assessment and hold a classification class. This isn’t required for general participation or social involvement in Boccia.
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
