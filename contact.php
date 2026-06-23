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
    padding: 5rem 2rem;
    background-color: #F8FAFC;
}
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
}
.contact-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    padding: 2.5rem;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.contact-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.06);
}
.contact-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #081B4B;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 2px solid #F1F5F9;
    padding-bottom: 0.75rem;
}
.contact-info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    font-size: 0.95rem;
    color: var(--text-secondary);
}
.contact-info-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}
.contact-info-icon {
    font-size: 1.1rem;
    margin-top: 0.2rem;
}
.contact-info-item strong {
    color: var(--text-primary);
}
.bank-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    font-size: 0.9rem;
}
.bank-table td {
    padding: 0.6rem 0.5rem;
    border-bottom: 1px solid #F1F5F9;
}
.bank-table td:first-child {
    font-weight: 700;
    color: #081B4B;
    width: 35%;
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
            <div class="contact-card">
                <h3 class="contact-card-title" style="border-bottom-color: rgba(19,136,8,0.2);">
                    <i class="fa-solid fa-building" style="color: var(--bsfi-green);"></i> Corporate Office
                </h3>
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <i class="fa-solid fa-phone contact-info-icon" style="color: var(--bsfi-green);"></i>
                        <div>
                            <strong>Phone:</strong><br>
                            <a href="tel:01141653466" style="color: inherit; text-decoration: none;">011-41653466</a>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-envelope contact-info-icon" style="color: var(--bsfi-green);"></i>
                        <div>
                            <strong>Email:</strong><br>
                            <a href="mailto:bocciaindia@gmail.com" style="color: inherit; text-decoration: none;">bocciaindia@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-location-dot contact-info-icon" style="color: var(--bsfi-green);"></i>
                        <div>
                            <strong>Address:</strong><br>
                            LG 101, Bharat Chamber, 70/71, Scindia House, Connaught Circus, Janpath, New Delhi - 110001
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registered Office -->
            <div class="contact-card">
                <h3 class="contact-card-title" style="border-bottom-color: rgba(255,153,51,0.2);">
                    <i class="fa-solid fa-house-chimney" style="color: var(--bsfi-saffron);"></i> Registered Office
                </h3>
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <i class="fa-solid fa-phone contact-info-icon" style="color: var(--bsfi-saffron);"></i>
                        <div>
                            <strong>Phone:</strong><br>
                            <a href="tel:+919803454949" style="color: inherit; text-decoration: none;">+91 9803454949</a>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-envelope contact-info-icon" style="color: var(--bsfi-saffron);"></i>
                        <div>
                            <strong>Email:</strong><br>
                            <a href="mailto:bocciaindia@gmail.com" style="color: inherit; text-decoration: none;">bocciaindia@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fa-solid fa-location-dot contact-info-icon" style="color: var(--bsfi-saffron);"></i>
                        <div>
                            <strong>Address:</strong><br>
                            #69, VPO Ablu, District Bathinda, Punjab - 151201
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="contact-card">
                <h3 class="contact-card-title">
                    <i class="fa-solid fa-landmark" style="color: #081B4B;"></i> Our Bank Details
                </h3>
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
                        <td style="font-family: monospace; font-weight: 700; font-size: 0.95rem;">36123404464</td>
                    </tr>
                    <tr>
                        <td>IFSC Code</td>
                        <td style="font-family: monospace; font-weight: 700; font-size: 0.95rem;">SBIN0017259</td>
                    </tr>
                    <tr>
                        <td>Branch</td>
                        <td>SCO 128-129, Grain Market, Bathinda, Pin Code 151001</td>
                    </tr>
                </table>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1.25rem; font-style: italic;">
                    * We have 12A, 80G and Income tax approvals.
                </p>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
