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
            <div class="header-logo-container">
                <a href="<?php echo $logo_path; ?>index.php" class="header-logo-link">
                    <img src="<?php echo $logo_path; ?>boccia-india-logo.webp" alt="BSFI" class="tl-img tl-bsfi">
                </a>
                <div class="header-v-sep"></div>
            </div>
            <div class="header-text-wrap">
                <h1 class="header-title-en">BOCCIA SPORTS FEDERATION OF INDIA</h1>
                <h2 class="header-title-hi">बोच्चिया स्पोर्ट्स फेडरेशन ऑफ़ इंडिया</h2>
            </div>
            <div class="header-right-spacer"></div>
        </div>
    </div>
</div>
