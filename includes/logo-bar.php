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
        <a href="<?php echo $logo_path; ?>index.php" class="tl-item">
            <img src="<?php echo $logo_path; ?>boccia-india-logo.webp" alt="BSFI" class="tl-img tl-bsfi">
        </a>
        <div class="tl-sep"></div>
        <a href="https://yas.nic.in" target="_blank" rel="noopener" class="tl-item">
            <img src="<?php echo $logo_path; ?>Ministry_of_Youth_Affairs_and_Sports.svg" alt="Ministry of Youth Affairs and Sports" class="tl-img tl-myas">
        </a>
        <div class="tl-sep"></div>
        <a href="https://www.paralympic.org.in" target="_blank" rel="noopener" class="tl-item">
            <img src="<?php echo $logo_path; ?>PCI.png" alt="Paralympic Committee of India" class="tl-img tl-pci">
        </a>
        <div class="tl-sep"></div>
        <a href="https://worldboccia.com" target="_blank" rel="noopener" class="tl-item">
            <img src="<?php echo $logo_path; ?>Full Logo World Boccia.webp" alt="World Boccia" class="tl-img tl-world">
        </a>
    </div>
</div>
