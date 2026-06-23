<?php
// includes/about-boccia-page.php - Custom Assembler Template for About Boccia Page
$page_title = "About Boccia | Boccia India";
$meta_desc = "Learn about Boccia, the Paralympic precision sport empowering athletes with severe physical disabilities. Explore history, classifications, videos, and opportunities in India.";
$canonical_url = "page.php?section=about&slug=about-boccia";
$og_image = "about boccia/hero_bg.webp";

// Load header
include __DIR__ . '/header.php';
?>

<div class="about-boccia-wrapper">
    <?php include __DIR__ . '/about-boccia/hero.php'; ?>
    <?php include __DIR__ . '/about-boccia/quick-facts.php'; ?>
    <?php include __DIR__ . '/about-boccia/overview.php'; ?>
    <?php include __DIR__ . '/about-boccia/journey.php'; ?>
    <?php include __DIR__ . '/about-boccia/india.php'; ?>
    <?php include __DIR__ . '/about-boccia/sport-classes.php'; ?>
    <?php include __DIR__ . '/about-boccia/watch-learn.php'; ?>
    <?php include __DIR__ . '/about-boccia/why-boccia.php'; ?>
    <?php include __DIR__ . '/about-boccia/mission-vision.php'; ?>
    <?php include __DIR__ . '/about-boccia/cta.php'; ?>
</div>

<?php
// Load footer
include __DIR__ . '/footer.php';
?>
