<?php
// footer.php - Layout Footer with Quick Links and scripts
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    include __DIR__ . '/admin_footer.php';
    return;
}

if (!isset($relative_prefix)) {
    $script_name = $_SERVER['SCRIPT_NAME'];
    $clean_path = ltrim($script_name, '/');
    $parts = explode('/', $clean_path);
    $depth = count($parts) - 1;
    if ($depth < 0) $depth = 0;
    $relative_prefix = str_repeat('../', $depth);
}
$script_path = $relative_prefix . 'app.js';
?>
    <?php
    // Only show Recognitions & Affiliations and Strategic Partners on the Home Page
    $is_home_page = (basename($_SERVER['SCRIPT_NAME']) === 'index.php');
    if ($is_home_page):
    ?>
    <!-- ══ RECOGNITIONS & AFFILIATIONS ══ -->
    <section class="affiliations-section" id="affiliations" style="background: #081B4B !important;">
        <div class="container">

            <!-- Row 1: Official Recognitions -->
            <div class="aff-row-label">
                <span class="aff-row-tag">Recognitions &amp; Affiliations</span>
            </div>
            <div class="logo-grid-flex aff-row-1">

                <!-- World Boccia -->
                <div class="logo-item" title="World Boccia – International Governing Body">
                    <img src="logos/Full Logo World Boccia.webp"
                         alt="World Boccia"
                         onerror="this.src='../logos/Full Logo World Boccia.webp'">
                </div>

                <!-- Paralympic Committee of India -->
                <div class="logo-item" title="Paralympic Committee of India">
                    <img src="PCI.webp"
                         alt="Paralympic Committee of India"
                         onerror="this.src='../PCI.webp'">
                </div>

                <!-- MYAS -->
                <div class="logo-item" title="Ministry of Youth Affairs &amp; Sports">
                    <img src="logos/Ministry_of_Youth_Affairs_and_Sports.svg"
                         alt="Ministry of Youth Affairs &amp; Sports"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span class="logo-text-fallback" style="display:none;">
                        <strong>MYAS</strong>
                        <small>Ministry of Youth Affairs &amp; Sports</small>
                    </span>
                </div>

                <!-- Anti-Doping -->
                <div class="logo-item" title="World Anti-Doping Agency / NADA India">
                    <img src="logos/antidoping.jpg"
                         alt="Anti-Doping"
                         onerror="this.src='../logos/antidoping.jpg'">
                </div>


            </div>

            <!-- Divider -->
            <div class="aff-divider"></div>

            <!-- Row 2: Strategic Partners -->
            <div class="aff-row-label">
                <span class="aff-row-tag">Our Strategic Partners</span>
            </div>
            <div class="logo-grid-flex aff-row-2">

                <!-- AMTZ -->
                <div class="logo-item" title="Andhra MedTech Zone (AMTZ)">
                    <img src="logos/AMTZ_New_Logo.jpg"
                         alt="AMTZ"
                         onerror="this.src='../logos/AMTZ_New_Logo.jpg'">
                </div>

                <!-- ARCIL -->
                <div class="logo-item" title="Asset Reconstruction Company (India) Ltd – ARCIL">
                    <img src="logos/Arcil.jpg"
                         alt="ARCIL"
                         onerror="this.src='../logos/Arcil.jpg'">
                </div>

                <!-- Swavlamban (MSJE / SBIF) -->
                <div class="logo-item" title="Swavlamban – Ministry of Social Justice &amp; Empowerment">
                    <img src="logos/logo_msje.webp"
                         alt="Swavlamban / MSJE"
                         onerror="this.src='../logos/logo_msje.webp'">
                </div>

                <!-- BEML -->
                <div class="logo-item" title="Bharat Earth Movers Limited (BEML)">
                    <img src="logos/BEML.png"
                         alt="BEML"
                         onerror="this.src='../logos/BEML.png'">
                </div>

                <!-- NTPC -->
                <div class="logo-item" title="NTPC Limited">
                    <img src="logos/NTPC_Logo.svg.png"
                         alt="NTPC"
                         onerror="this.src='../logos/NTPC_Logo.svg.png'">
                </div>

                <!-- Central Bank of India -->
                <div class="logo-item" title="Central Bank of India">
                    <img src="logos/CENTRAL BANK OF INDIA LOGO.jpg"
                         alt="Central Bank of India"
                         onerror="this.src='../logos/CENTRAL BANK OF INDIA LOGO.jpg'">
                </div>

                <!-- Union Bank -->
                <div class="logo-item" title="Union Bank of India">
                    <img src="logos/Union Bank.png"
                         alt="Union Bank of India"
                         onerror="this.src='../logos/Union Bank.png'">
                </div>

                <!-- PNB -->
                <div class="logo-item" title="Punjab National Bank">
                    <img src="logos/pnb-logo.jpeg"
                         alt="PNB"
                         onerror="this.src='../logos/pnb-logo.jpeg'">
                </div>

                <!-- REC -->
                <div class="logo-item" title="REC Limited">
                    <img src="logos/2560px-REC_logo.svg.png"
                         alt="REC"
                         onerror="this.src='../logos/2560px-REC_logo.svg.png'">
                </div>

                <!-- IDBI -->
                <div class="logo-item" title="IDBI Bank">
                    <img src="logos/IDBI.png"
                         alt="IDBI Bank"
                         onerror="this.src='../logos/IDBI.png'">
                </div>

            </div>

        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="main-footer" id="contact-bottom">
        <div class="container footer-grid">
            <!-- Left Info Block -->
            <div class="footer-info">
                <div class="footer-logos-row">
                    <img src="<?php echo $relative_prefix; ?>logos/Ministry_of_Youth_Affairs_and_Sports.svg" alt="MYAS" class="footer-top-logo">
                    <img src="<?php echo $relative_prefix; ?>boccia-india-logo.webp" alt="Boccia India" class="footer-top-logo">
                    <img src="<?php echo $relative_prefix; ?>logos/Full Logo World Boccia.webp" alt="World Boccia" class="footer-top-logo">
                </div>
                <h3 class="footer-title">Boccia Sports Federation of India</h3>
                <p class="footer-desc">Promoting Para Boccia across India and empowering athletes with disabilities through competitive excellence, inclusion, and international representation.</p>
                
                <h3 class="footer-title" style="margin-top: 2rem;">Follow Us</h3>
                <div class="footer-socials-new">
                    <a href="https://www.facebook.com/bocciaindia/" target="_blank" class="social-icon-btn facebook" title="Facebook">
                        <svg viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z"/></svg>
                    </a>
                    <a href="https://twitter.com/bocciaindia" target="_blank" class="social-icon-btn twitter" title="Twitter/X">
                        <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/boccia_india/" target="_blank" class="social-icon-btn instagram" title="Instagram">
                        <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@bocciaindia4196%20" target="_blank" class="social-icon-btn youtube" title="YouTube">
                        <svg viewBox="0 0 24 24"><path d="M23.498 6.163a3.003 3.003 0 00-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.517 0-9.388.507a3.003 3.003 0 00-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 002.11 2.11c1.871.507 9.388.507 9.388.507s7.517 0 9.388-.507a3.003 3.003 0 002.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-quick-links">
                <h3 class="footer-title">Quick Links</h3>
                <ul>
                    <li><a href="#home"><span class="link-arrow">↗</span> Home</a></li>
                    <li><a href="#about"><span class="link-arrow">↗</span> About BSFI</a></li>
                    <li><a href="#discover"><span class="link-arrow">↗</span> Our Sport</a></li>
                    <li><a href="#competitions"><span class="link-arrow">↗</span> Competitions</a></li>
                    <li><a href="#news"><span class="link-arrow">↗</span> News & Media</a></li>
                    <li><a href="#photo-gallery"><span class="link-arrow">↗</span> Gallery</a></li>
                    <li><a href="<?php echo $relative_prefix; ?>contact.php"><span class="link-arrow">↗</span> Contact Us</a></li>
                </ul>
            </div>

            <!-- Addresses Column -->
            <div class="footer-addresses">
                <h3 class="footer-title">Addresses</h3>
                <div class="address-block">
                    <div class="address-item">
                        <svg class="address-icon" viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: #FF9933; margin-top: 2px; flex-shrink: 0;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <div class="address-text">
                            <strong>Registered Address</strong>
                            <p>#69, VPO Ablu, Tehsil & District Bathinda, Punjab – 151201</p>
                        </div>
                    </div>
                    <div class="address-item">
                        <svg class="address-icon" viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: #138808; margin-top: 2px; flex-shrink: 0;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <div class="address-text">
                            <strong>Correspondence Address</strong>
                            <p>LG-101, Bharat Chamber, 70/71 Scindia House, Connaught Circus, New Delhi – 110001</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Us Column -->
            <div class="footer-contact-new">
                <h3 class="footer-title">Contact Us</h3>
                <div class="contact-block">
                    <div class="contact-item">
                        <svg class="contact-icon-new" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #FF9933; flex-shrink: 0;"><path d="M6.62 10.79a15.149 15.149 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <div class="contact-text">
                            <a href="tel:+919803454949">+91 98034 54949</a>
                            <a href="tel:+919464500042">+91 94645 00042</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <svg class="contact-icon-new" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #138808; flex-shrink: 0;"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <div class="contact-text">
                            <a href="mailto:bocciaindia@gmail.com">bocciaindia@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <svg class="contact-icon-new" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #081B4B; flex-shrink: 0;"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95a15.65 15.65 0 00-1.38-3.56A8.03 8.03 0 0118.92 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.33-.14 2 0 .67.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 015.08 16zm2.95-8H5.08a7.987 7.987 0 013.96-3.56 15.65 15.65 0 00-1.38 3.56zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.34-.16-2 0-.67.07-1.35.16-2h4.68c.09.65.16 1.33.16 2 0 .67-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a7.987 7.987 0 01-4.33 3.56zM16.36 14c.08-.66.14-1.33.14-2 0-.67-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                        <div class="contact-text">
                            <a href="http://bocciaindia.com/" target="_blank">www.bocciaindia.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2026 Boccia Sports Federation of India (BSFI). All Rights Reserved. | <a href="#" onclick="showAccessibilityStatement(event)" style="color: inherit; text-decoration: underline;">Accessibility Statement</a></p>
                <p style="font-size: 0.82rem; opacity: 0.85; margin-top: 0.5rem; color: #081B4B; font-weight: 500;">Recognized by World Boccia, Paralympic Committee of India (PCI), Ministry of Youth Affairs & Sports and Sports Authority of India.</p>
                <p style="font-size: 0.75rem; opacity: 0.65; margin-top: 0.4rem; color: #081B4B;">Designed by Gurarpit Singh . Ajeet Graphics</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle (Local) -->
    <script src="<?php echo $relative_prefix; ?>assets/vendor/bootstrap/bootstrap.bundle.min.js?v=1"></script>
    <!-- GLightbox JS (Local) -->
    <script src="<?php echo $relative_prefix; ?>assets/vendor/glightbox/glightbox.min.js?v=1"></script>
    <!-- App JavaScript -->
    <script src="<?php echo $script_path; ?>"></script>
    <!-- Accessibility Script -->
    <script src="<?php echo $relative_prefix; ?>assets/js/accessibility.js"></script>

</div> <!-- /.page-wrapper -->
</body>
</html>
