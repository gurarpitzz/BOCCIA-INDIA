<?php
// contact.php - Contact Us page containing BSFI addresses and details
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Contact Us - Boccia Sports Federation of India";
include __DIR__ . '/includes/header.php';
?>

<!-- FontAwesome 6 Icons (Local fallback & CDN load) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<!-- Premium Contact Page Styles -->
<style>
/* CSS Custom Variables for BSFI Identity */
:root {
    --bsfi-primary-navy: #081B4B;
    --bsfi-saffron-accent: #FF9933;
    --bsfi-success-green: #138808;
    --bsfi-warm-cream: #F8F5EF;
    --glass-background: rgba(255, 255, 255, 0.85);
    --glass-border: 1px solid rgba(255, 255, 255, 0.45);
    --soft-shadow: 0 10px 30px rgba(8, 27, 75, 0.06);
    --card-shadow-hover: 0 18px 45px rgba(8, 27, 75, 0.12);
}

.contact-page-wrapper {
    background-color: var(--bsfi-warm-cream);
    color: var(--bsfi-primary-navy);
    font-family: 'Poppins', sans-serif;
    padding-bottom: 5rem;
    position: relative;
    overflow: hidden;
}

/* Watermark Emblem in the top-right background */
.contact-page-wrapper::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 500px;
    height: 500px;
    background-image: url('about boccia/why boccia matter BG.png');
    background-repeat: no-repeat;
    background-position: top right;
    background-size: contain;
    opacity: 0.08;
    pointer-events: none;
    z-index: 1;
}

/* Breadcrumbs Section */
.contact-breadcrumbs-nav {
    max-width: 1200px;
    margin: 0 auto;
    padding: 5rem 2rem 1rem 2rem;
    font-size: 0.9rem;
    font-weight: 500;
    position: relative;
    z-index: 2;
}
.contact-breadcrumbs-nav a {
    color: var(--bsfi-saffron-accent);
    text-decoration: none;
    transition: opacity 0.2s;
}
.contact-breadcrumbs-nav a:hover {
    opacity: 0.8;
}
.contact-breadcrumbs-nav span {
    color: var(--bsfi-primary-navy);
    opacity: 0.7;
}
.contact-breadcrumbs-nav i {
    font-size: 0.75rem;
    margin: 0 0.5rem;
    opacity: 0.5;
}

/* Section Header (Hero-like but integrated) */
.contact-header-section {
    text-align: center;
    padding: 2rem 2rem 4rem 2rem;
    position: relative;
    z-index: 2;
}

.get-in-touch-label {
    display: inline-flex;
    align-items: center;
    gap: 1.5rem;
    font-family: 'Outfit', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--bsfi-saffron-accent);
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}

.get-in-touch-label .divider-line {
    display: inline-block;
    width: 40px;
    height: 2px;
    background-color: var(--bsfi-saffron-accent);
    opacity: 0.7;
}

.contact-header-section h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--bsfi-primary-navy);
    margin-bottom: 1.25rem;
}

.contact-header-section p {
    font-size: 1.15rem;
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.6;
    color: var(--bsfi-primary-navy);
    opacity: 0.8;
}

/* 3-Column Grid for Cards */
.cards-container-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem 5rem 2rem;
    position: relative;
    z-index: 2;
}

/* Glass Card Definition */
.glass-contact-card {
    background: var(--glass-background);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: var(--glass-border);
    border-radius: 24px;
    box-shadow: var(--soft-shadow);
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    overflow: hidden;
}

.glass-contact-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--card-shadow-hover);
}

/* Specific Border Colored Accents */
.glass-contact-card.accent-blue {
    border-top: 5px solid #0056B3;
}
.glass-contact-card.accent-green {
    border-top: 5px solid var(--bsfi-success-green);
}
.glass-contact-card.accent-saffron {
    border-top: 5px solid var(--bsfi-saffron-accent);
}

/* Card Header with Icon Circle */
.card-header-wrap {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.card-icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.glass-contact-card:hover .card-icon-circle {
    transform: scale(1.1);
}

/* Color codes for circles */
.accent-blue .card-icon-circle {
    background-color: rgba(0, 86, 179, 0.1);
    color: #0056B3;
}
.accent-green .card-icon-circle {
    background-color: rgba(19, 136, 8, 0.1);
    color: var(--bsfi-success-green);
}
.accent-saffron .card-icon-circle {
    background-color: rgba(255, 153, 51, 0.1);
    color: var(--bsfi-saffron-accent);
}

.card-header-wrap h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--bsfi-primary-navy);
    margin: 0;
}

.card-desc {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--bsfi-primary-navy);
    opacity: 0.8;
    margin-bottom: 2rem;
    min-height: 70px;
}

/* Detailed List Formats */
.contact-rows-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-row-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.row-icon {
    font-size: 1.15rem;
    width: 24px;
    text-align: center;
    margin-top: 0.15rem;
}

.accent-blue .row-icon {
    color: #0056B3;
}
.accent-green .row-icon {
    color: var(--bsfi-success-green);
}

.row-details {
    font-size: 1rem;
    line-height: 1.5;
}

.row-details a {
    color: var(--bsfi-primary-navy);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.row-details a:hover {
    color: var(--bsfi-saffron-accent);
}

.row-details address {
    margin: 0;
    font-style: normal;
    white-space: pre-line;
}

/* Table Style for Bank Details */
.bank-info-table {
    width: 100%;
    border-collapse: collapse;
}

.bank-info-table tr {
    border-bottom: 1px solid rgba(8, 27, 75, 0.08);
}

.bank-info-table tr:last-child {
    border-bottom: none;
}

.bank-info-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.95rem;
}

.bank-info-table td.label-col {
    font-weight: 500;
    color: var(--bsfi-primary-navy);
    opacity: 0.7;
    width: 38%;
}

.bank-info-table td.val-col {
    font-weight: 700;
    color: var(--bsfi-primary-navy);
}

.bank-info-table td.highlight-saffron {
    color: var(--bsfi-saffron-accent) !important;
}

/* Split Layout Section (Maps & Approvals) */
.split-layout-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: grid;
    grid-template-columns: 67% 30%;
    gap: 3%;
    position: relative;
    z-index: 2;
}

/* Find Us Panel & Interactive maps toggle */
.find-us-panel {
    background: var(--glass-background);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: var(--glass-border);
    border-radius: 24px;
    box-shadow: var(--soft-shadow);
    padding: 2.5rem;
}

.find-us-panel h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.find-us-panel h2 i {
    color: #0056B3;
}

/* Segmented Map Controls styling */
.map-segmented-control {
    display: inline-flex;
    background-color: rgba(8, 27, 75, 0.05);
    padding: 6px;
    border-radius: 14px;
    margin-bottom: 2rem;
    width: 100%;
    max-width: 500px;
}

.map-toggle-btn {
    flex: 1;
    border: none;
    background: transparent;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--bsfi-primary-navy);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: center;
}

.map-toggle-btn.active {
    background-color: var(--bsfi-primary-navy);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(8, 27, 75, 0.15);
}

.map-iframe-container {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(8, 27, 75, 0.1);
    height: 350px;
    position: relative;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);
}

.map-wrapper-element {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s ease, visibility 0.4s ease;
}

.map-wrapper-element.active {
    opacity: 1;
    visibility: visible;
}

/* Approvals Panel styling */
.approvals-panel {
    background: var(--glass-background);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: var(--glass-border);
    border-radius: 24px;
    box-shadow: var(--soft-shadow);
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
}

.approvals-panel h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.approvals-panel h2 i {
    color: var(--bsfi-success-green);
}

.approvals-panel p {
    font-size: 0.95rem;
    line-height: 1.5;
    opacity: 0.8;
    margin-bottom: 2rem;
}

/* Grid of Approval badges */
.approvals-badges-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.approval-badge-tile {
    background-color: rgba(19, 136, 8, 0.06);
    border: 1px solid rgba(19, 136, 8, 0.15);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bsfi-success-green);
    transition: all 0.25s ease;
}

.approval-badge-tile:hover {
    background-color: rgba(19, 136, 8, 0.1);
    transform: translateX(5px);
}

.approval-badge-tile i {
    font-size: 1.2rem;
}

/* Compliance Card Info Box */
.compliance-info-card {
    background-color: rgba(0, 86, 179, 0.05);
    border: 1px solid rgba(0, 86, 179, 0.12);
    border-radius: 14px;
    padding: 1.15rem;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #0056B3;
    margin-top: auto;
}

.compliance-info-card i {
    font-size: 1.1rem;
    margin-top: 0.1rem;
}

/* Responsive Overrides */
@media (max-width: 991px) {
    .split-layout-section {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
    
    .cards-container-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .contact-header-section h1 {
        font-size: 2.75rem;
    }
    
    .cards-container-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .approvals-badges-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .approval-badge-tile {
        padding: 0.75rem;
        flex-direction: column;
        text-align: center;
        font-size: 0.8rem;
        gap: 0.5rem;
    }

    .approval-badge-tile i {
        font-size: 1.4rem;
    }
}

@media (max-width: 480px) {
    .approvals-badges-grid {
        grid-template-columns: 1fr;
    }
    .approval-badge-tile {
        flex-direction: row;
        text-align: left;
        font-size: 0.9rem;
    }
}
</style>

<div class="contact-page-wrapper">
    
    <!-- Section Breadcrumbs -->
    <div class="contact-breadcrumbs-nav">
        <a href="index.php">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Contact Us</span>
    </div>

    <!-- Section 1: Hero Header -->
    <div class="contact-header-section">
        <div class="get-in-touch-label">
            <span class="divider-line"></span>
            GET IN TOUCH
            <span class="divider-line"></span>
        </div>
        <h1>Contact Us</h1>
        <p>In case of any further query, athletes, officials, sponsors, and stakeholders are encouraged to contact us through the channels below.</p>
    </div>

    <!-- Section 2: 3-Column Info Cards -->
    <div class="cards-container-grid">

        <!-- Card 1: Corporate Office -->
        <div class="glass-contact-card accent-blue">
            <div class="card-header-wrap">
                <div class="card-icon-circle">
                    <i class="fa-solid fa-building"></i>
                </div>
                <h2>Corporate Office</h2>
            </div>
            <p class="card-desc">
                For general administrative inquiries, official correspondence, federation communications, and operational matters.
            </p>
            <div class="contact-rows-list">
                <div class="contact-row-item">
                    <i class="fa-solid fa-envelope row-icon"></i>
                    <div class="row-details">
                        <a href="mailto:Bocciaindia@gmail.com">Bocciaindia@gmail.com</a>
                    </div>
                </div>
                <div class="contact-row-item">
                    <i class="fa-solid fa-phone row-icon"></i>
                    <div class="row-details">
                        <a href="tel:01141653466">011-41653466</a>
                    </div>
                </div>
                <div class="contact-row-item">
                    <i class="fa-solid fa-location-dot row-icon"></i>
                    <div class="row-details">
                        <address>LG 101, Bharat Chamber,
70/71 Scindia House,
Connaught Circus,
Janpath,
New Delhi – 110001</address>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Registered Office -->
        <div class="glass-contact-card accent-green">
            <div class="card-header-wrap">
                <div class="card-icon-circle">
                    <i class="fa-solid fa-landmark-dome"></i>
                </div>
                <h2>Registered Office</h2>
            </div>
            <p class="card-desc">
                For statutory registrations, legal matters, federation records, and state-level coordination.
            </p>
            <div class="contact-rows-list">
                <div class="contact-row-item">
                    <i class="fa-solid fa-envelope row-icon"></i>
                    <div class="row-details">
                        <a href="mailto:Bocciaindia@gmail.com">Bocciaindia@gmail.com</a>
                    </div>
                </div>
                <div class="contact-row-item">
                    <i class="fa-solid fa-phone row-icon"></i>
                    <div class="row-details">
                        <a href="tel:+919803454949">+91 9803454949</a>
                    </div>
                </div>
                <div class="contact-row-item">
                    <i class="fa-solid fa-location-dot row-icon"></i>
                    <div class="row-details">
                        <address>#69, VPO Ablu,
District Bathinda,
Punjab – 151201</address>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Bank Details -->
        <div class="glass-contact-card accent-saffron">
            <div class="card-header-wrap">
                <div class="card-icon-circle">
                    <i class="fa-solid fa-piggy-bank"></i>
                </div>
                <h2>Federation Bank Details</h2>
            </div>
            <p class="card-desc">
                Direct donations, sponsorship payments, registration fee transfers, and federation contributions can be made through the following account.
            </p>
            
            <table class="bank-info-table">
                <tr>
                    <td class="label-col">Account Name</td>
                    <td class="val-col">Boccia Sports Federation of India</td>
                </tr>
                <tr>
                    <td class="label-col">Bank</td>
                    <td class="val-col">State Bank of India (SBI)</td>
                </tr>
                <tr>
                    <td class="label-col">Account Number</td>
                    <td class="val-col highlight-saffron" style="font-family: monospace; font-size: 1.05rem;">36123404464</td>
                </tr>
                <tr>
                    <td class="label-col">IFSC</td>
                    <td class="val-col highlight-saffron" style="font-family: monospace; font-size: 1.05rem;">SBIN0017259</td>
                </tr>
                <tr>
                    <td class="label-col">Branch</td>
                    <td class="val-col">SCO 128–129, Grain Market, Bathinda – 151001</td>
                </tr>
            </table>
        </div>

    </div>

    <!-- Section 3: Maps & Approvals Split Layout -->
    <div class="split-layout-section">
        
        <!-- Left Panel: Find Us (Maps) -->
        <div class="find-us-panel">
            <h2><i class="fa-solid fa-map-location-dot"></i> Find Us</h2>
            
            <div class="map-segmented-control">
                <button type="button" class="map-toggle-btn active" id="btn-delhi" onclick="toggleMap('delhi')">Corporate Office (New Delhi)</button>
                <button type="button" class="map-toggle-btn" id="btn-bathinda" onclick="toggleMap('bathinda')">Registered Office (Bathinda)</button>
            </div>
            
            <div class="map-iframe-container">
                <!-- Delhi Map Wrapper -->
                <div class="map-wrapper-element active" id="map-delhi">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.1332822453535!2d77.2198581!3d28.6242907!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cfd3680a9bb4d%3A0x590f592dfafc7f13!2sScindia%20House!5e0!3m2!1sen!2sin!4v1719124000000!5m2!1sen!2sin" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                
                <!-- Bathinda Map Wrapper -->
                <div class="map-wrapper-element" id="map-bathinda">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d27537.491325324317!2d74.79633845!3d30.3454949!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x391732ea5235f3a5%3A0xe54e60579e01362e!2sAblu%2C%20Punjab%20151201!5e0!3m2!1sen!2sin!4v1719124100000!5m2!1sen!2sin" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <!-- Right Panel: Approvals & Compliance -->
        <div class="approvals-panel">
            <h2><i class="fa-solid fa-shield-halved"></i> Our Approvals</h2>
            <p>BSFI is recognized and compliant under applicable Income Tax provisions and statutory regulations.</p>
            
            <div class="approvals-badges-grid">
                <div class="approval-badge-tile">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>12A Approved</span>
                </div>
                <div class="approval-badge-tile">
                    <i class="fa-solid fa-percent"></i>
                    <span>80G Approved</span>
                </div>
                <div class="approval-badge-tile">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Income Tax Compliant</span>
                </div>
            </div>

            <div class="compliance-info-card">
                <i class="fa-solid fa-circle-info"></i>
                <span>All donations to BSFI are eligible for tax exemption under section 80G of the Income Tax Act.</span>
            </div>
        </div>

    </div>

</div>

<!-- Segmented Control JavaScript -->
<script>
function toggleMap(office) {
    // Reset active button state
    document.getElementById('btn-delhi').classList.remove('active');
    document.getElementById('btn-bathinda').classList.remove('active');
    
    // Reset active map wrapper state
    document.getElementById('map-delhi').classList.remove('active');
    document.getElementById('map-bathinda').classList.remove('active');
    
    // Set clicked items as active
    if (office === 'delhi') {
        document.getElementById('btn-delhi').classList.add('active');
        document.getElementById('map-delhi').classList.add('active');
    } else {
        document.getElementById('btn-bathinda').classList.add('active');
        document.getElementById('map-bathinda').classList.add('active');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


