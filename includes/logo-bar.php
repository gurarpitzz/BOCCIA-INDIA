<?php
// includes/logo-bar.php - Reusable Top Logo Bar for BSFI Public and Admin
if (!isset($logo_path)) {
    $script_name = $_SERVER['SCRIPT_NAME'];
    $clean_path = ltrim($script_name, '/');
    $parts = explode('/', $clean_path);
    $depth = count($parts) - 1;
    if ($depth < 0) $depth = 0;
    $logo_path = str_repeat('../', $depth);
}
?>
<!-- TOP LOGO BAR -->
<div class="top-logo-bar">
    <div class="top-logo-inner">
        <div class="header-brand-wrap">
            <a href="<?php echo $logo_path; ?>index.php" class="header-logo-link">
                <img src="<?php echo $logo_path; ?>boccia-india-logo.webp" alt="BSFI" class="tl-img tl-bsfi">
            </a>
            <div class="header-text-wrap">
                <h1 class="header-title-en">BOCCIA SPORTS FEDERATION OF INDIA</h1>
                <h2 class="header-title-hi">बोच्चिया स्पोर्ट्स फेडरेशन ऑफ़ इंडिया</h2>
                <div class="header-separator">
                    <span class="sep-line sep-saffron"></span>
                    <span class="sep-chakra">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#081B4B" stroke-width="1.2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="2" fill="#081B4B" />
                            <path d="M12 2 L12 22 M2 12 L22 12 M4.93 4.93 L19.07 19.07 M4.93 19.07 L19.07 4.93" />
                            <path d="M8.5 3.5 L15.5 20.5 M15.5 3.5 L8.5 20.5 M3.5 8.5 L20.5 15.5 M3.5 15.5 L20.5 8.5" />
                        </svg>
                    </span>
                    <span class="sep-line sep-green"></span>
                </div>
            </div>
        </div>
    </div>
</div>
