<?php
// footer.php - Layout Footer with Quick Links and scripts
$script_path = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/import/') !== false ? '../app.js' : 'app.js';
?>
    <!-- Affiliations & Recognitions Logo Grid -->
    <section class="affiliations-section">
        <div class="container">
            <h3 class="affiliations-title">Recognitions & Affiliations</h3>
            <div class="logo-grid-flex">
                <div class="logo-item" title="World Boccia">
                    <img src="Full Logo World Boccia.webp" alt="World Boccia Logo" onerror="this.src='../Full Logo World Boccia.webp'">
                </div>
                <div class="logo-item" title="Paralympic Committee of India">
                    <img src="PCI.png" alt="Paralympic Committee of India Logo" onerror="this.src='../PCI.png'">
                </div>
                <div class="logo-item text-logo" title="Ministry of Youth Affairs & Sports">
                    <strong>MYAS</strong>
                    <span>Ministry of Youth Affairs & Sports</span>
                </div>
                <div class="logo-item text-logo" title="Sports Authority of India">
                    <strong>SAI</strong>
                    <span>Sports Authority of India</span>
                </div>
                <div class="logo-item text-logo" title="Khelo India">
                    <strong>KHELO INDIA</strong>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container footer-grid">
            <!-- Left Info Block -->
            <div class="footer-info">
                <div class="footer-logo-block">
                    <img src="boccia-india-logo.webp" alt="BSFI" class="footer-logo" onerror="this.src='../boccia-india-logo.webp'">
                    <h3>Boccia Sports Federation of India</h3>
                </div>
                <p>Promoting Para Boccia across India. Empowering athletes with disabilities to achieve sporting excellence, precision, and national pride.</p>
                <div class="footer-socials">
                    <a href="#" class="social-circle">FB</a>
                    <a href="#" class="social-circle">TW</a>
                    <a href="#" class="social-circle">IG</a>
                    <a href="#" class="social-circle">YT</a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-quick-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About BSFI</a></li>
                    <li><a href="#discover">Discover Boccia</a></li>
                    <li><a href="#competitions">Competitions</a></li>
                    <li><a href="#news">News & Media</a></li>
                    <li><a href="#gallery">Gallery</a></li>
                </ul>
            </div>

            <!-- Green Contact Us Card -->
            <div class="footer-contact-card">
                <h4>Contact Us</h4>
                <div class="contact-details">
                    <div class="contact-line">
                        <span class="contact-icon">📞</span>
                        <a href="tel:18002025155" style="color: inherit; text-decoration: none;">1800-202-5155 (Toll-Free)</a>
                    </div>
                    <div class="contact-line">
                        <span class="contact-icon">✉️</span>
                        <a href="mailto:info@bocciaindia.org" style="color: inherit; text-decoration: none;">info@bocciaindia.org</a>
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.85rem; opacity: 0.85;">Boccia Sports Federation of India Headquarters, New Delhi.</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> Boccia Sports Federation of India (BSFI). All Rights Reserved.</p>
                <p style="font-size: 0.75rem; opacity: 0.6; margin-top: 0.5rem;">Affiliated to Paralympic Committee of India (PCI) & World Boccia. Powered by XAMPP.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle (includes Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmk3j6M4zRq8+r6q2KGPWR/MJVS" crossorigin="anonymous"></script>
    <!-- GLightbox JS -->
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <!-- App JavaScript -->
    <script src="<?php echo $script_path; ?>"></script>

</div> <!-- /.page-wrapper -->
</body>
</html>
