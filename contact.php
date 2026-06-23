<?php
// contact.php - Contact Us page containing BSFI addresses and details
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Contact Us - Boccia Sports Federation of India";
include __DIR__ . '/includes/header.php';
?>

<!-- Custom Contact Styles -->
<style>
.contact-hero {
    background: linear-gradient(135deg, rgba(8, 27, 75, 0.9) 0%, rgba(11, 42, 110, 0.9) 100%), url('about boccia/why boccia matter BG.png') no-repeat center center / cover;
    color: #ffffff;
    padding: 5.5rem 2rem;
    text-align: center;
    border-bottom: 5px solid var(--bsfi-saffron);
}
.contact-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 1rem;
}
.contact-hero p {
    font-size: 1.15rem;
    max-width: 700px;
    margin: 0 auto;
    opacity: 0.9;
}
.contact-section {
    padding: 6rem 2rem;
    background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.9) 100%), url('about boccia/why boccia matter BG.png');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: contain;
    position: relative;
    min-height: 80vh;
}
.contact-grid {
    display: flex;
    flex-direction: column;
    gap: 3rem;
    max-width: 1000px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}
.contact-card {
    background: #081B4B;
    color: #ffffff !important;
    border: none;
    border-radius: 24px;
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.2);
    padding: 3rem;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
    overflow: hidden;
}
.contact-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px rgba(8, 27, 75, 0.3);
}

/* Accent Left Borders */
.contact-card.theme-saffron {
    border-left: 12px solid var(--bsfi-saffron);
}
.contact-card.theme-green {
    border-left: 12px solid var(--bsfi-green);
}
.contact-card.theme-blue {
    border-left: 12px solid #3b82f6;
}

.contact-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.contact-card.theme-saffron .contact-card-title {
    color: var(--bsfi-saffron);
}
.contact-card.theme-green .contact-card-title {
    color: var(--bsfi-green);
}
.contact-card.theme-blue .contact-card-title {
    color: #3b82f6;
}

.contact-description {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.contact-pills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.contact-badge-pill {
    background: #12285A;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 1rem 1.75rem;
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    color: #ffffff !important;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
    transition: background 0.2s, border-color 0.2s;
}

.contact-badge-pill:hover {
    background: #1b3573;
    border-color: rgba(255, 255, 255, 0.3);
}

.contact-badge-pill i {
    font-size: 1.3rem;
}

/* Icon Colors based on parent theme */
.contact-card.theme-saffron .contact-badge-pill i {
    color: var(--bsfi-saffron);
}
.contact-card.theme-green .contact-badge-pill i {
    color: var(--bsfi-green);
}
.contact-card.theme-blue .contact-badge-pill i {
    color: #3b82f6;
}

.address-box {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 1.05rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

.address-box strong {
    display: block;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.contact-card.theme-saffron .address-box strong {
    color: var(--bsfi-saffron);
}
.contact-card.theme-green .address-box strong {
    color: var(--bsfi-green);
}
.contact-card.theme-blue .address-box strong {
    color: #3b82f6;
}

.bank-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1.5rem;
    font-size: 1.05rem;
}

.bank-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.bank-table td:first-child {
    font-weight: 700;
    color: rgba(255, 255, 255, 0.7);
    width: 30%;
}

.bank-table td:last-child {
    color: #ffffff;
}

.tax-badge {
    display: inline-block;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #93c5fd;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 1.5rem;
}
</style>

<div class="contact-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p>In case of any further query, the Athletes should not hesitate to contact us</p>
    </div>
</div>

<div class="contact-section">
    <div class="container">
        <div class="contact-grid">
            
            <!-- Corporate Office -->
            <div class="contact-card theme-green">
                <h3 class="contact-card-title">Corporate Office</h3>
                <p class="contact-description">
                    For general administrative inquiries, official letters, and corporate communications, please reach out to our New Delhi office:
                </p>
                <div class="contact-pills-container">
                    <a href="mailto:bocciaindia@gmail.com" class="contact-badge-pill">
                        <i class="fa-solid fa-envelope"></i> Bocciaindia@gmail.com
                    </a>
                    <a href="tel:01141653466" class="contact-badge-pill">
                        <i class="fa-solid fa-phone"></i> 011-41653466
                    </a>
                </div>
                <div class="address-box">
                    <strong>Office Address</strong>
                    LG 101, Bharat Chamber, 70/71, Scindia House, Connaught Circus, Janpath, New Delhi - 110001
                </div>
            </div>

            <!-- Registered Office -->
            <div class="contact-card theme-saffron">
                <h3 class="contact-card-title">Registered Office</h3>
                <p class="contact-description">
                    For statutory, legal registration affairs, and state-level coordinates, contact our registered office in Punjab:
                </p>
                <div class="contact-pills-container">
                    <a href="mailto:bocciaindia@gmail.com" class="contact-badge-pill">
                        <i class="fa-solid fa-envelope"></i> Bocciaindia@gmail.com
                    </a>
                    <a href="tel:+919803454949" class="contact-badge-pill">
                        <i class="fa-solid fa-phone"></i> +91 9803454949
                    </a>
                </div>
                <div class="address-box">
                    <strong>Registered Address</strong>
                    #69, VPO Ablu, District Bathinda, Punjab - 151201
                </div>
            </div>

            <!-- Bank Details -->
            <div class="contact-card theme-blue">
                <h3 class="contact-card-title">Our Bank Details</h3>
                <p class="contact-description">
                    Direct donations, sponsorship payments, and registration fee transfers can be made to the following bank account:
                </p>
                
                <table class="bank-table">
                    <tr>
                        <td>Name</td>
                        <td>Boccia Sports Federation of India</td>
                    </tr>
                    <tr>
                        <td>Bank Name</td>
                        <td>State Bank of India (SBI)</td>
                    </tr>
                    <tr>
                        <td>Account No</td>
                        <td style="font-family: monospace; font-weight: 700; letter-spacing: 0.5px;">36123404464</td>
                    </tr>
                    <tr>
                        <td>IFSC Code</td>
                        <td style="font-family: monospace; font-weight: 700; letter-spacing: 0.5px;">SBIN0017259</td>
                    </tr>
                    <tr>
                        <td>Branch</td>
                        <td>SCO 128-129, Grain Market, Bathinda, Pin Code 151001</td>
                    </tr>
                </table>

                <div class="tax-badge">
                    <i class="fa-solid fa-circle-check"></i> We have 12A, 80G and Income tax approvals
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

