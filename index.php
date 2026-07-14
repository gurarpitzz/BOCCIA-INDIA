<?php
// index.php - Public web portal for Boccia Sports Federation of India

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Boccia Sports Federation of India | Official Portal";
include __DIR__ . '/includes/header.php';

$message = '';

// Fetch Active Schedules
$publicSchedules = [];
try {
    $schedStmt = $pdo->query("SELECT * FROM schedules WHERE active = 1 ORDER BY sort_order ASC, id ASC");
    $publicSchedules = $schedStmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch All News
$allNews = [];
$newsImages = [];
try {
    // Fetch published and scheduled if published_at <= NOW()
    $newsStmt = $pdo->query("SELECT * FROM news WHERE status = 'published' OR (status = 'scheduled' AND published_at <= NOW()) ORDER BY pinned DESC, published_at DESC LIMIT 20");
    $allNews = $newsStmt->fetchAll();
    
    // Fetch all extra images for these news items
    $newsIds = array_column($allNews, 'id');
    if (!empty($newsIds)) {
        $inQuery = implode(',', array_fill(0, count($newsIds), '?'));
        $imgStmt = $pdo->prepare("SELECT news_id, image_path FROM news_images WHERE news_id IN ($inQuery) ORDER BY sort_order ASC");
        $imgStmt->execute($newsIds);
        while ($row = $imgStmt->fetch()) {
            $newsImages[$row['news_id']][] = $row['image_path'];
        }
    }
} catch (PDOException $e) {}

// Fetch Gallery Data
$galleryCategories = [];
$galleryAlbums = [];
$galleryImages = [];
try {
    // 1. Fetch active categories
    $galleryCategories = $pdo->query("SELECT * FROM gallery_categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Fetch published albums with image counts and cover image paths
    $galleryAlbums = $pdo->query("
        SELECT ga.*, gc.slug AS category_slug, gc.name AS category_name,
               (SELECT COUNT(*) FROM gallery_images WHERE album_id = ga.id AND status = 'published' AND is_deleted = 0) AS image_count,
               gi.image_path AS cover_image_path,
               gi.thumbnail_path AS cover_thumb_path
        FROM gallery_albums ga
        LEFT JOIN gallery_categories gc ON ga.category_id = gc.id
        LEFT JOIN gallery_images gi ON ga.cover_image_id = gi.id
        WHERE ga.is_published = 1 AND gc.is_active = 1
        ORDER BY ga.event_date DESC, ga.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch all published images
    $galleryImages = $pdo->query("
        SELECT gi.*, ga.slug AS album_slug, ga.title AS album_title
        FROM gallery_images gi
        LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
        WHERE gi.status = 'published' AND gi.is_deleted = 0
        ORDER BY gi.sort_order ASC, gi.id DESC
        LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently or log
}

// Fetch athlete counts by state dynamically
$stateCounts = [];
try {
    $countStmt = $pdo->query("SELECT state, COUNT(*) as count FROM athletes WHERE status = 'approved' GROUP BY state");
    while ($row = $countStmt->fetch()) {
        $stateCounts[strtolower(trim($row['state']))] = (int)$row['count'];
    }
} catch (PDOException $e) {
    // Fail silently
}

function getStateCount($stateCounts, $stateName, $fallback) {
    $key = strtolower(trim($stateName));
    return isset($stateCounts[$key]) ? $stateCounts[$key] : $fallback;
}

// ── Live Stats for the animated stats bar ──
$siteStats = [
    'total_athletes'   => 0,
    'approved_athletes'=> 0,
    'states_active'    => 0,
    'associations'     => 0,
    'bc_classes'       => 4,
    'years_active'     => (int)date('Y') - 2018,
];
try {
    $siteStats['total_athletes']    = (int)$pdo->query("SELECT COUNT(*) FROM athletes")->fetchColumn();
    $siteStats['approved_athletes'] = (int)$pdo->query("SELECT COUNT(*) FROM athletes WHERE status='approved'")->fetchColumn();
    $siteStats['states_active']     = (int)$pdo->query("SELECT COUNT(DISTINCT representing_for) FROM athletes WHERE representing_for IS NOT NULL AND representing_for != ''")->fetchColumn();
    $siteStats['associations']      = (int)$pdo->query("SELECT COUNT(*) FROM state_associations WHERE active=1")->fetchColumn();
} catch (PDOException $e) { /* fail silently */ }
?>

<!-- Hero Slideshow Section -->
<section id="home" class="hero-slideshow-wrapper">

    <!-- Slides -->
    <div class="slideshow-track" id="slideshowTrack">
        <?php
        $slides = [
            "gallery/WhatsApp Image 2026-06-03 at 09.31.25.jpeg",
            "gallery/WhatsApp Image 2026-06-03 at 09.31.27.jpeg",
            "gallery/WhatsApp Image 2026-06-05 at 10.18.15.jpeg",
            "gallery/WhatsApp Image 2026-06-05 at 15.51.46.jpeg",
            "gallery/WhatsApp Image 2026-06-06 at 20.57.00.jpeg",
            "gallery/WhatsApp Image 2026-06-06 at 20.57.01 (1).jpeg",
            "gallery/WhatsApp Image 2026-06-06 at 20.57.01.jpeg",
            "gallery/WhatsApp Image 2026-06-06 at 20.57.04.jpeg",
            "gallery/WhatsApp Image 2026-06-01 at 11.17.28.jpeg",
        ];
        foreach ($slides as $i => $img):
        ?>
        <div class="slide <?php echo $i === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($img); ?>?v=1.2');">
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dark gradient overlay -->
    <div class="slide-overlay"></div>

    <!-- Hero Text Content -->
    <div class="hero-content-overlay">
        <h1 class="hero-quote-text animated-quote">
            <span class="hero-quote-accent hero-anim-word" style="display: inline-block; opacity: 0; transform: translateY(10px); margin-right: 0.1em;">“</span>
            <span class="animated-words">I didn't know there was a</span><br>
            <span class="animated-words">sport for me until</span><br>
            <span class="animated-words">I found Boccia</span>
            <span class="hero-quote-accent hero-anim-word" style="display: inline-block; opacity: 0; transform: translateY(10px); margin-left: 0.1em;">”</span>
        </h1>
        <div class="hero-quote-underline animated-fade-item"></div>

        <!-- Desktop Subtitle -->
        <p class="hero-subtitle d-none d-md-block animated-fade-item">Boccia is a Paralympic precision sport for athletes with severe physical disabilities.<br>BSFI develops Boccia across India through athlete registration, coaching, competitions, and international representation.</p>
        <!-- Mobile Subtitle -->
        <p class="hero-subtitle d-block d-md-none animated-fade-item">India's official governing body for Boccia, empowering athletes with severe physical disabilities nationwide.</p>

        <div class="hero-btns animated-fade-item">
            <a href="get-involved/membership.php" class="btn btn-hero-primary">Player Registration &rarr;</a>
            <a href="page.php?section=about&slug=about-boccia" class="btn btn-hero-secondary">Explore Boccia</a>
        </div>
    </div>

    <!-- Slide Navigation Dots -->
    <div class="slide-dots" id="slideDots">
        <?php foreach ($slides as $i => $img): ?>
        <button class="slide-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Slide <?php echo $i+1; ?>"></button>
        <?php endforeach; ?>
    </div>

</section>

<script>
(function() {
    const track  = document.getElementById('slideshowTrack');
    const dots   = document.querySelectorAll('.slide-dot');
    const slides = document.querySelectorAll('.slide');
    let current  = 0;
    let timer;

    function goTo(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function next() { goTo(current + 1); }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(next, 5000);
    }

    dots.forEach(dot => dot.addEventListener('click', () => { goTo(+dot.dataset.index); startTimer(); }));

    startTimer();

    // --- Hero Text GSAP Animation Logic ---
    function prepareHeroAnimation() {
        const textElements = document.querySelectorAll('.animated-words');
        textElements.forEach(el => {
            const text = el.textContent.trim();
            const words = text.split(/\s+/);
            el.innerHTML = words.map(word => `<span class="hero-anim-word" style="display: inline-block; opacity: 0; transform: translateY(10px); margin-right: 0.25em;">${word}</span>`).join('');
        });

        // Hide subtitle and buttons initially
        document.querySelectorAll('.animated-fade-item').forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(15px)';
            item.style.transition = 'none';
        });
    }

    function triggerHeroAnimation() {
        if (typeof gsap === 'undefined') {
            // Fallback if GSAP fails to load
            document.querySelectorAll('.hero-anim-word').forEach(w => {
                w.style.opacity = '1';
                w.style.transform = 'none';
            });
            document.querySelectorAll('.animated-fade-item').forEach(i => {
                i.style.opacity = '1';
                i.style.transform = 'none';
            });
            return;
        }

        const tl = gsap.timeline();
        // Stagger all words + the quote accent spans (which also carry .hero-anim-word class)
        tl.to(".hero-anim-word", {
            opacity: 1,
            y: 0,
            stagger: 0.22,
            duration: 0.5,
            ease: "power2.out"
        });
        // Fade in subtitle and buttons sequentially after words finish
        tl.to(".animated-fade-item", {
            opacity: 1,
            y: 0,
            stagger: 0.15,
            duration: 0.6,
            ease: "power2.out"
        });
    }

// Prepare text nodes immediately
    prepareHeroAnimation();

    // Register global trigger
    window.triggerHeroAnimation = triggerHeroAnimation;
})();
</script>

<!-- ═══════════════════════════════════════════
     SECTION 2: What is Boccia? (Overview & Origins)
════════════════════════════════════════════ -->
<section id="what-is-boccia" class="about-overview" style="background-image: url('about boccia/overview_bg.webp');">
    <div class="container">
        <div class="row align-items-center g-5">
            
            <!-- Left: Interactive Content Tabs -->
            <div class="col-lg-6 col-md-12">
                <span class="about-section-eyebrow">The Sport</span>
                <h2 class="about-section-title" style="color: #081B4B;">Overview &amp; Origins</h2>
                
                <!-- Tab Headers -->
                <div class="overview-tabs-nav" style="margin-bottom: 1.75rem;">
                    <button class="overview-tab-btn active" data-tab="overview">Overview</button>
                    <button class="overview-tab-btn" data-tab="history">History</button>
                    <button class="overview-tab-btn" data-tab="reach">Global Reach</button>
                    <button class="overview-tab-btn" data-tab="india">Boccia in India</button>
                </div>
                
                <!-- Tab Contents Container -->
                <div class="overview-tabs-content">
                    
                    <!-- Tab: Overview -->
                    <div class="overview-tab-pane active" id="tab-overview">
                        <h4 class="tab-pane-heading" style="color: #081B4B; font-weight: 700; margin-bottom: 1rem; font-size: 1.25rem;">Precision. Strategy. Inclusion.</h4>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.5rem;">Boccia is a precision ball sport designed specifically for athletes with severe physical disabilities affecting motor skills. Recognized as one of the most inclusive Paralympic sports, Boccia provides individuals with high support needs an opportunity to compete at local, national, and international levels.</p>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 0;">Played indoors on a flat court, Boccia involves athletes throwing, kicking, or using an assistive ramp to propel leather balls as close as possible to a target ball known as the "jack." The objective is simple, yet the game demands exceptional skill, planning, and control.</p>
                    </div>
                    
                    <!-- Tab: History -->
                    <div class="overview-tab-pane" id="tab-history">
                        <h4 class="tab-pane-heading" style="color: #081B4B; font-weight: 700; margin-bottom: 1rem; font-size: 1.25rem;">A Rich Paralympic Legacy</h4>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.5rem;">Boccia originated in Europe during the 1970s as a competitive sport for individuals with cerebral palsy. Over time, it evolved to include athletes with a wider range of severe physical disabilities.</p>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 0;">The sport made its Paralympic debut at the 1984 Paralympic Games and has since grown into a globally recognized discipline governed internationally by World Boccia.</p>
                    </div>
                    
                    <!-- Tab: Reach -->
                    <div class="overview-tab-pane" id="tab-reach">
                        <h4 class="tab-pane-heading" style="color: #081B4B; font-weight: 700; margin-bottom: 1rem; font-size: 1.25rem;">Expanding Boundaries Worldwide</h4>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.5rem;">Today, Boccia is played in more than 70 countries and continues to expand its reach through grassroots development programs, national championships, and international competitions.</p>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 0;">As a key component of the Paralympic movement, World Boccia works to bring this highly accessible sport to new regions, establishing training centers, certifying coaches, and supporting local organizations.</p>
                    </div>
                    
                    <!-- Tab: India -->
                    <div class="overview-tab-pane" id="tab-india">
                        <h4 class="tab-pane-heading" style="color: #081B4B; font-weight: 700; margin-bottom: 1rem; font-size: 1.25rem;">Empowering Indian Athletes</h4>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.5rem;">Boccia has emerged as an important adaptive sport in India, creating opportunities for persons with severe physical disabilities to participate in competitive sports and lead active, empowered lives. Through the efforts of dedicated organizations, coaches, volunteers, and advocates, awareness and participation have steadily increased.</p>
                        <p style="color: rgba(8, 27, 75, 0.85); font-size: 1.05rem; line-height: 1.65; margin-bottom: 0;">Indian athletes have demonstrated remarkable talent and determination, representing the nation in international competitions and contributing to the growth of the sport. Development programs continue to introduce Boccia to new players while promoting accessibility.</p>
                    </div>
                    
                </div>
                
                <div style="margin-top: 2.25rem;">
                    <a href="https://bocciaindia.ajeetgraphics.com/page.php?section=about&slug=about-boccia" class="btn btn-bsfi-navy">Learn About Boccia &rarr;</a>
                </div>
            </div>
            
            <!-- Right: Premium Video Player -->
            <div class="col-lg-6 col-md-12">
                <div class="overview-video-card">
                    <div class="overview-video-wrapper">
                        <div class="youtube-lazy-load" data-youtube-id="itPWqcx7xBg">
                            <div class="yt-play-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                            <img src="https://img.youtube.com/vi/itPWqcx7xBg/hqdefault.jpg" alt="Watch overview video" class="yt-poster" loading="lazy">
                        </div>
                    </div>
                    <span class="video-caption">🎥 Official Introduction to Para Boccia</span>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 3: Who Can Participate?
════════════════════════════════════════════ -->
<section id="who-can-participate" class="who-can-participate-section">
    <div class="container section-content-relative text-center">
        <span class="section-sub-label">ATHLETE ELIGIBILITY</span>
        <h2 class="section-main-heading text-center" style="margin-bottom: 1.25rem; color: #ffffff;">Who Can Participate?</h2>
        <p class="section-desc-para mx-auto" style="max-width: 800px; margin-bottom: 3rem; color: rgba(255, 255, 255, 0.85); line-height: 1.6;">
            To participate in competitive boccia at an international level, an athlete must meet three exact requirements governed by the <a href="https://www.paralympic.org/classification" target="_blank" style="color: #24C27A; font-weight: 600; text-decoration: underline;">International Paralympic Committee (IPC)</a> and <a href="https://www.worldboccia.com" target="_blank" style="color: #24C27A; font-weight: 600; text-decoration: underline;">World Boccia</a>. The player must have an eligible Underlying Health Condition, a specific Impairment Type, and meet the Minimum Impairment Criteria.
        </p>
        
        <div class="eligibility-grid">
            <!-- Card 1: Underlying Health Conditions -->
            <div class="eligibility-card">
                <div class="eligibility-card-header">
                    <span class="panel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>
                    </span>
                    <h4>1. Underlying Conditions</h4>
                </div>
                <ul class="eligibility-details-list">
                    <li>Cerebral Palsy</li>
                    <li>Traumatic Brain Injury or Stroke</li>
                    <li>Spinal Cord Injury (quadriplegia)</li>
                    <li>Muscular Dystrophy</li>
                    <li>Spina Bifida</li>
                    <li>Severe Amputations or Limb Deficiencies</li>
                </ul>
            </div>

            <!-- Card 2: The Five Eligible Impairment Types -->
            <div class="eligibility-card">
                <div class="eligibility-card-header">
                    <span class="panel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    </span>
                    <h4>2. Impairment Types</h4>
                </div>
                <ul class="eligibility-details-list">
                    <li><strong>Hypertonia:</strong> Increased muscle tension and stiffness</li>
                    <li><strong>Ataxia:</strong> Neurological coordination destruction</li>
                    <li><strong>Athetosis:</strong> Involuntary, slow writhing movements</li>
                    <li><strong>Impaired Muscle Power:</strong> Permanent force reduction (e.g. spinal injury)</li>
                    <li><strong>Limb Deficiency:</strong> Absence of bones or joints</li>
                </ul>
            </div>

            <!-- Card 3: Minimum Impairment Criteria (MIC) -->
            <div class="eligibility-card">
                <div class="eligibility-card-header">
                    <span class="panel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6v-6H9v6zM21 21H3v-3h18v3zM12 3v9"></path></svg>
                    </span>
                    <h4>3. Minimum Criteria (MIC)</h4>
                </div>
                <ul class="eligibility-details-list">
                    <li><strong>Four-Limb Impact:</strong> Impairment must limit function in all 4 limbs</li>
                    <li><strong>Trunk/Leg Involvement:</strong> Requires playing from a wheelchair</li>
                    <li><strong>Upper Limb Limitation:</strong> Visibly reduced capability to hold, grip or propel the ball</li>
                </ul>
            </div>

            <!-- Card 4: Administrative Requirements -->
            <div class="eligibility-card">
                <div class="eligibility-card-header">
                    <span class="panel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </span>
                    <h4>4. Entry Protocol</h4>
                </div>
                <ul class="eligibility-details-list">
                    <li><strong>Medical Form (MDF):</strong> Stamped and signed by a licensed doctor</li>
                    <li><strong>In-Person Evaluation:</strong> Assessed by a certified panel before matches</li>
                    <li><strong>Passport:</strong> Holds a valid passport of the represented nation</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 4: Official State Associations (Map)
════════════════════════════════════════════ -->
<div class="map-stats-bg-wrapper">
<!-- India Participation Map Section -->
<section id="map" class="map-section">
    <div class="container">
        <div class="row align-items-center g-5">

            <!-- LEFT: Map confined card -->
            <div class="col-lg-6">
                <div class="map-bordered-card">
                    <!-- Green accent bar at top -->
                    <div class="map-card-accent-bar"></div>

                    <!-- SVG Map -->
                    <?php include __DIR__ . '/includes/india-map.php'; ?>
                </div>
            </div>

            <!-- RIGHT: Content panel -->
            <div class="col-lg-6">
                <div class="map-content-panel">
                    <span class="map-eyebrow">National Footprint</span>
                    <h2 class="map-main-title">Official State<br>Associations</h2>
                    <p class="map-desc">Click on any state on the interactive map to view its officially recognized Boccia State Association, contact representative, affiliation status, and official communication details maintained by the Boccia Sports Federation of India.</p>

                    <!-- Dynamic detail card (populated by app.js on click) -->
                    <div id="map-details-card" class="map-detail-box">
                        <h4 class="map-detail-heading">Select a State</h4>
                        <p class="map-detail-body">Click on any state on the interactive map to view its officially recognized Boccia State Association, contact representative, affiliation status, and official communication details.</p>
                        <span class="map-detail-badge">● Recognized by BSFI</span>
                    </div>

                    <!-- Athlete Density Legend -->
                    <div class="map-legend-box map-legend-right">
                        <h5>Athlete Density Legend</h5>
                        <div class="legend-row"><span class="legend-badge" style="background:#e05a10;"></span> 0 Athletes</div>
                        <div class="legend-row"><span class="legend-badge" style="background:#6b82b8;"></span> 1–5 Athletes</div>
                        <div class="legend-row"><span class="legend-badge" style="background:#3b5a9a;"></span> 6–15 Athletes</div>
                        <div class="legend-row"><span class="legend-badge" style="background:#16295a;"></span> 16–30 Athletes</div>
                        <div class="legend-row"><span class="legend-badge" style="background:#0b1b3d;"></span> 30+ Athletes</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- DYNAMIC STATS BAR -->
<section class="stats-bar-section" id="stats-bar">
    <div class="container">
        <div class="stats-bar-pill">

            <div class="stat-item">
                <span class="stat-number" data-target="19" data-suffix="+">0+</span>
                <span class="stat-label">Affiliated States</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="41" data-suffix="+">0+</span>
                <span class="stat-label">Recognized State Associations</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="4" data-suffix="">0</span>
                <span class="stat-label">Official Sport Classes</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo max(100, $siteStats['total_athletes']); ?>" data-suffix="+">0+</span>
                <span class="stat-label">Registered Athletes</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['years_active']; ?>" data-suffix="+">0+</span>
                <span class="stat-label">Years of Excellence</span>
            </div>

        </div>
    </div>
</section>
</div><!-- /.map-stats-bg-wrapper -->

<!-- ═══════════════════════════════════════════
     SECTION 5: Become a Boccia Athlete
════════════════════════════════════════════ -->
<section id="become-athlete" class="become-athlete-section">
    <div class="container text-center section-content-relative">
        <span class="section-sub-label">JOIN THE MOVEMENT</span>
        <h2 class="section-main-heading text-center" style="margin-bottom: 0.75rem; color: #ffffff;">Become a Boccia Athlete</h2>
        <p class="section-desc-para mx-auto" style="max-width: 600px; margin-bottom: 3.5rem; color: rgba(255, 255, 255, 0.75);">
            Follow the official registration pathway to become part of India's national Boccia community.
        </p>
        
        <!-- Connected Roadmap -->
        <div class="roadmap-timeline-wrapper">
            <div class="roadmap-line-connector"></div>
            <div class="roadmap-steps-container">
                <div class="roadmap-step-card">
                    <div class="step-badge">01</div>
                    <h4>Register Online</h4>
                    <p>Create your official BSFI profile.</p>
                </div>
                <div class="roadmap-step-card">
                    <div class="step-badge">02</div>
                    <h4>Submit Documents</h4>
                    <p>Upload identity & medicals.</p>
                </div>
                <div class="roadmap-step-card">
                    <div class="step-badge">03</div>
                    <h4>Classification</h4>
                    <p>Complete certified assessment.</p>
                </div>
                <div class="roadmap-step-card">
                    <div class="step-badge">04</div>
                    <h4>State Review</h4>
                    <p>Verification by state association.</p>
                </div>
                <div class="roadmap-step-card">
                    <div class="step-badge">05</div>
                    <h4>National Registration</h4>
                    <p>Receive your official BSFI registration.</p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 3.5rem;">
            <a href="get-involved/membership.php" class="btn btn-bsfi-green">Register as an Athlete &rarr;</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 5.5: Meet India's Boccia Stars
     (Flagship Interactive Hall of Fame)
════════════════════════════════════════════ -->
<section id="boccia-stars" class="stars-section">
    <div class="stars-bg-pattern"></div>
    
    <!-- Background sparkles drifting -->
    <div class="bg-sparkles-container">
        <div class="bg-sparkle" style="top: 15%; left: 8%; --pulse-delay: 1s;">✦</div>
        <div class="bg-sparkle" style="top: 25%; left: 88%; --pulse-delay: 3s;">✦</div>
        <div class="bg-sparkle" style="top: 75%; left: 12%; --pulse-delay: 5s;">✦</div>
        <div class="bg-sparkle" style="top: 85%; left: 82%; --pulse-delay: 7s;">✦</div>
    </div>

    <div class="container">
        <div class="stars-header">
            <span class="stars-eyebrow">⭐ OUR STARS ⭐</span>
            <h2 class="stars-title">Meet the Athletes Inspiring a Nation</h2>
            <p class="stars-subtitle">Representing India with Precision, Passion, and Pride.</p>
        </div>

        <div class="stars-reveal-wrapper" id="starsRevealWrapper">
            <div class="stars-layout">
                
                <!-- LEFT: Interactive Constellation -->
                <div class="constellation-wrapper" id="constellationContainer" aria-label="Athlete Star Constellation">
                    <div class="constellation-parallax-layer" id="constellationParallax">
                        
                        <!-- SVG links web -->
                        <svg class="constellation-svg" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                            <!-- Background Web Links -->
                            <line class="constellation-link-path" x1="110" y1="140" x2="160" y2="70" />
                            <line class="constellation-link-path" x1="110" y1="140" x2="75" y2="230" />
                            <line class="constellation-link-path" x1="160" y1="70" x2="390" y2="110" />
                            <line class="constellation-link-path" x1="390" y1="110" x2="375" y2="225" />
                            <line class="constellation-link-path" x1="75" y1="230" x2="120" y2="380" />
                            <line class="constellation-link-path" x1="375" y1="225" x2="410" y2="360" />
                            <line class="constellation-link-path" x1="120" y1="380" x2="220" y2="430" />
                            <line class="constellation-link-path" x1="220" y1="430" x2="410" y2="360" />
                            <line class="constellation-link-path" x1="375" y1="225" x2="120" y2="380" />
                            
                            <!-- Dynamic Active Connector Draw Path -->
                            <path id="dynamic-connector" class="constellation-link-path" stroke="var(--gold-accent)" stroke-width="2" opacity="0" d="M 0 0 L 0 0" />
                            <!-- Active Connector Travel Spark Path -->
                            <path id="dynamic-travel" class="constellation-travel-path" stroke="#ffffff" stroke-width="3" d="M 0 0 L 0 0" />
                        </svg>

                        <!-- Center piece -->
                        <div class="constellation-center" id="constellationCenter">
                            <div class="constellation-center-glow"></div>
                            <div class="constellation-center-text">BOCCIA<br>INDIA</div>
                            <div class="constellation-center-sub">National Team</div>
                        </div>

                        <?php
                        if (!function_exists('cleanStarPlayerUrl')) {
                            function cleanStarPlayerUrl($path) {
                                if (empty($path)) return '';
                                $parts = explode('/', $path);
                                $encoded = array_map('rawurlencode', $parts);
                                return implode('/', $encoded);
                            }
                        }
                        ?>

                        <!-- Athlete beacons -->
                        <!-- 1. Ajeya Raj -->
                        <button class="athlete-beacon" data-id="ajeya_raj" style="--x: 22; --y: 28; --depth-scale: 1.0; --depth-opacity: 1.0; --depth-blur: 0px; --pulse-speed: 4.3s; --pulse-delay: 0.5s; --pulse-opacity: 0.45; --class-color: var(--color-bc3); --target-x: -28; --target-y: -22;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/ajeya raj/ajeya1.jpeg'); ?>" alt="Ajeya Raj" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC3</span>
                        </button>

                        <!-- 2. Anjali Devi -->
                        <button class="athlete-beacon" data-id="anjali_devi" style="--x: 32; --y: 14; --depth-scale: 0.9; --depth-opacity: 0.85; --depth-blur: 0.5px; --pulse-speed: 5.7s; --pulse-delay: 1.2s; --pulse-opacity: 0.35; --class-color: var(--color-bc3); --target-x: -18; --target-y: -36;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/anjali devi/anjali (1).jpeg'); ?>" alt="Anjali Devi" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC3</span>
                        </button>

                        <!-- 3. Jatin Kushwah -->
                        <button class="athlete-beacon" data-id="jatin_kushwah" style="--x: 78; --y: 22; --depth-scale: 1.0; --depth-opacity: 1.0; --depth-blur: 0px; --pulse-speed: 3.9s; --pulse-delay: 0.2s; --pulse-opacity: 0.5; --class-color: var(--color-bc4); --target-x: 28; --target-y: -28;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/Jatin Kushwah/WhatsApp Image 2026-07-07 at 19.15.27 (2).jpeg'); ?>" alt="Jatin Kushwah" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC4</span>
                        </button>

                        <!-- 4. Pooja Gupta -->
                        <button class="athlete-beacon" data-id="pooja_gupta" style="--x: 15; --y: 46; --depth-scale: 0.85; --depth-opacity: 0.8; --depth-blur: 0.8px; --pulse-speed: 6.1s; --pulse-delay: 2.1s; --pulse-opacity: 0.3; --class-color: var(--color-bc4); --target-x: -35; --target-y: -4;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/pooja gupta/pooja (1).jpeg'); ?>" alt="Pooja Gupta" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC4</span>
                        </button>

                        <!-- 5. Sachin Chamaria -->
                        <button class="athlete-beacon" data-id="sachin_chamaria" style="--x: 75; --y: 45; --depth-scale: 1.0; --depth-opacity: 1.0; --depth-blur: 0px; --pulse-speed: 4.8s; --pulse-delay: 0.8s; --pulse-opacity: 0.45; --class-color: var(--color-bc3); --target-x: 25; --target-y: -5;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/Sachin Chamaria/WhatsApp Image 2026-07-07 at 19.15.28 (1).jpeg'); ?>" alt="Sachin Chamaria" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC3</span>
                        </button>

                        <!-- 6. Sarita -->
                        <button class="athlete-beacon" data-id="sarita" style="--x: 24; --y: 76; --depth-scale: 0.95; --depth-opacity: 0.9; --depth-blur: 0.3px; --pulse-speed: 5.2s; --pulse-delay: 1.5s; --pulse-opacity: 0.4; --class-color: var(--color-bc3); --target-x: -26; --target-y: 26;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/sarita/WhatsApp Image 2026-07-07 at 19.15.27.jpeg'); ?>" alt="Sarita" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC3</span>
                        </button>

                        <!-- 7. Usha Kiran -->
                        <button class="athlete-beacon" data-id="usha_kiran" style="--x: 44; --y: 86; --depth-scale: 0.85; --depth-opacity: 0.8; --depth-blur: 0.8px; --pulse-speed: 5.9s; --pulse-delay: 2.5s; --pulse-opacity: 0.3; --class-color: var(--color-bc3); --target-x: -6; --target-y: 36;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <span class="beacon-portrait-img" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #FF9933 0%, #138808 100%); color: #fff; font-weight: 800; font-size: 0.75rem;">UK</span>
                            </div>
                            <span class="beacon-badge">BC3</span>
                        </button>

                        <!-- 8. Vyom Pawa -->
                        <button class="athlete-beacon" data-id="vyom_pawa" style="--x: 82; --y: 72; --depth-scale: 1.0; --depth-opacity: 1.0; --depth-blur: 0px; --pulse-speed: 4.1s; --pulse-delay: 0.3s; --pulse-opacity: 0.5; --class-color: var(--color-bc2); --target-x: 32; --target-y: 22;">
                            <div class="beacon-pulse"></div>
                            <div class="beacon-sparkle-frame">
                                <svg viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
                            </div>
                            <div class="beacon-portrait-wrap">
                                <img src="<?php echo cleanStarPlayerUrl('star players-20260714T053810Z-1-001/star players/vyom pawa/vyom (1).jpeg'); ?>" alt="Vyom Pawa" class="beacon-portrait-img" loading="lazy">
                            </div>
                            <span class="beacon-badge">BC2</span>
                        </button>

                    </div>
                </div>

                <!-- RIGHT: Profile Card -->
                <div class="athlete-card-container">
                    <div class="athlete-profile-card card-empty-state" id="athleteProfileCard">
                        
                        <!-- Dynamic India Map Overlay -->
                        <div class="india-map-overlay" id="cardMapOverlay">
                            <?php 
                            if (file_exists(__DIR__ . '/india svg.svg')) {
                                $raw_svg = file_get_contents(__DIR__ . '/india svg.svg');
                                $svg_clean = preg_replace('/<\?xml.*?\?>/s', '', $raw_svg);
                                $svg_clean = preg_replace('/<!--.*?-->/s', '', $svg_clean);
                                echo $svg_clean;
                            }
                            ?>
                        </div>

                        <!-- Empty State View initially -->
                        <div id="cardEmptyStateView">
                            <div class="empty-state-stars">✦</div>
                            <h3 class="empty-state-title">Discover India's Finest Players</h3>
                            <p class="empty-state-subtitle">Select an athlete beacon on the map or wait to begin the showcase.</p>
                        </div>

                        <!-- Main Profile Structure (Swapped values, fixed DOM structure) -->
                        <div id="cardMainProfileView" style="display: none; width: 100%; height: 100%; grid-template-columns: 42% 58%; gap: 2.5rem; position: relative; z-index: 2;">
                            
                            <!-- Left: Image & Badge -->
                            <div class="card-left-col">
                                <div class="portrait-frame-container">
                                    <div class="card-portrait-background"></div>
                                    <!-- Fallback avatar icon for missing images -->
                                    <div id="avatarFallback" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(255, 153, 51, 0.15) 0%, rgba(19, 136, 8, 0.15) 100%); color: var(--navy-primary);">
                                        <svg viewBox="0 0 24 24" width="80" height="80" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                                    </div>
                                    <img id="cardProfileImg" src="" alt="Athlete Portrait" class="card-portrait-img">
                                </div>
                            </div>

                            <!-- Right: Structured Text content -->
                            <div class="card-right-col">
                                <div>
                                    <span class="card-selected-badge transition-item stagger-1" id="cardClassificationBadge">Selected Athlete</span>
                                    <h3 class="athlete-detail-name transition-item stagger-2" id="cardAthleteName"></h3>
                                    
                                    <div class="athlete-detail-meta transition-item stagger-3">
                                        <span class="detail-class-badge" id="cardClassBadge"></span>
                                        <span class="detail-location" id="cardLocation"></span>
                                    </div>

                                    <div class="card-badges-row transition-item stagger-3" id="cardBadgesRow">
                                        <!-- badges injected here -->
                                    </div>

                                    <div class="detail-quote-box transition-item stagger-4">
                                        <span class="quote-backdrop-mark open-mark">“</span>
                                        <p class="detail-quote-text" id="cardQuote"></p>
                                        <span class="quote-backdrop-mark close-mark">”</span>
                                    </div>

                                    <div class="detail-skills-box transition-item stagger-5">
                                        <!-- Precision -->
                                        <div class="skill-bar-row">
                                            <div class="skill-bar-header">
                                                <span>Precision</span>
                                                <span id="skillValPrecision">0%</span>
                                            </div>
                                            <div class="skill-bar-track">
                                                <div class="skill-bar-fill" id="skillFillPrecision"></div>
                                            </div>
                                        </div>
                                        <!-- Control -->
                                        <div class="skill-bar-row">
                                            <div class="skill-bar-header">
                                                <span>Control</span>
                                                <span id="skillValControl">0%</span>
                                            </div>
                                            <div class="skill-bar-track">
                                                <div class="skill-bar-fill" id="skillFillControl"></div>
                                            </div>
                                        </div>
                                        <!-- Decision Making -->
                                        <div class="skill-bar-row">
                                            <div class="skill-bar-header">
                                                <span>Decision Making</span>
                                                <span id="skillValDecision">0%</span>
                                            </div>
                                            <div class="skill-bar-track">
                                                <div class="skill-bar-fill" id="skillFillDecision"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="detail-style-box transition-item stagger-5">
                                        <span>Playing Style:</span>
                                        <span class="detail-style-value" id="cardPlayingStyle"></span>
                                    </div>

                                    <h4 class="detail-achievements-title transition-item stagger-6">Major Achievements</h4>
                                    <ul class="detail-achievements-list transition-item stagger-6" id="cardAchievementsList">
                                        <!-- list elements injected here -->
                                    </ul>
                                </div>

                                <a href="page.php?section=athletes" class="btn-stars-cta transition-item stagger-7">Explore Journey &rarr;</a>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Unified Athletes State Object
    const athletes = [
        {
            id: "ajeya_raj",
            name: "Ajeya Raj",
            classification: "BC3",
            accentColor: "#FF9933",
            state: "Punjab",
            stateCode: "INPB",
            svgCoords: { x: 110, y: 140 },
            photo: "star players-20260714T053810Z-1-001/star players/ajeya raj/ajeya1.jpeg",
            quote: "My journey reflects resilience, determination, and a commitment to making a difference in the lives of others, both through my work and my athletic pursuits.",
            badges: ["National Medallist", "Represented India 5x"],
            achievements: [
                "🥇 Gold – 10th National Championship, 2026 (Pair)",
                "🥈 Silver – 10th National Championship, 2026 (Individual)",
                "🥉 Bronze – World Boccia Challenger, Bahrain, 2024 (Individual)",
                "🥈 Silver – World Boccia Challenger, Canberra, 2025 (Pair)"
            ],
            skills: { precision: 95, control: 90, decision: 93 },
            playingStyle: "◈ Ramp Specialist"
        },
        {
            id: "anjali_devi",
            name: "Anjali Devi",
            classification: "BC3",
            accentColor: "#FF9933",
            state: "Himachal Pradesh",
            stateCode: "INHP",
            svgCoords: { x: 160, y: 70 },
            photo: "star players-20260714T053810Z-1-001/star players/anjali devi/anjali (1).jpeg",
            quote: "Following my injury, Boccia gave me a new lease on life. I remain committed to the sport, aiming to win a medal for India at the Paralympics.",
            badges: ["First Int'l Gold Medallist", "National Medallist"],
            achievements: [
                "🥇 First-ever International Gold Medalist in Boccia for India",
                "🥈 Silver Medal – 8th Boccia National Championship, 2024",
                "🥉 Bronze Medal – 7th Boccia National Championship, 2023"
            ],
            skills: { precision: 98, control: 94, decision: 91 },
            playingStyle: "◎ Jack Ball Expert"
        },
        {
            id: "jatin_kushwah",
            name: "Jatin Kushwah",
            classification: "BC4",
            accentColor: "#8E44AD",
            state: "Uttar Pradesh",
            stateCode: "INUP",
            svgCoords: { x: 390, y: 110 },
            photo: "star players-20260714T053810Z-1-001/star players/Jatin Kushwah/WhatsApp Image 2026-07-07 at 19.15.27 (2).jpeg",
            quote: "My goal is to win medals for my country and serve as a source of inspiration for other people with disabilities.",
            badges: ["National Champion", "Bahrain Medallist"],
            achievements: [
                "🥇 Gold Medallist – 10th National Championship (Individual & Pairs)",
                "🥈 Silver Medal – Bahrain World Boccia Challenger, 2024 (Pairs)",
                "🥉 Bronze Medal – Bahrain World Boccia Challenger, 2024 (Individual)"
            ],
            skills: { precision: 92, control: 88, decision: 90 },
            playingStyle: "🎯 Tactical Placement"
        },
        {
            id: "pooja_gupta",
            name: "Pooja Gupta",
            classification: "BC4",
            accentColor: "#8E44AD",
            state: "Haryana",
            stateCode: "INHR",
            svgCoords: { x: 75, y: 230 },
            photo: "star players-20260714T053810Z-1-001/star players/pooja gupta/pooja (1).jpeg",
            quote: "Disability is not a limitation; determination is the pathway to excellence. I aim to bring greater recognition to Boccia in India.",
            badges: ["World Ranked #30", "Chief Bank Manager"],
            achievements: [
                "🥇 Gold Medal – National Boccia Championship 2026 (Mixed Pairs BC4)",
                "🥈 Silver Medal – National Boccia Championship 2026 (Individual BC4)",
                "🏆 First Indian player to reach knockouts at Asian Para Games 2023"
            ],
            skills: { precision: 94, control: 92, decision: 96 },
            playingStyle: "◉ Precision Builder"
        },
        {
            id: "sachin_chamaria",
            name: "Sachin Chamaria",
            classification: "BC3",
            accentColor: "#FF9933",
            state: "Delhi",
            stateCode: "INDL",
            svgCoords: { x: 375, y: 225 },
            photo: "star players-20260714T053810Z-1-001/star players/Sachin Chamaria/WhatsApp Image 2026-07-07 at 19.15.28 (1).jpeg",
            quote: "Boccia demands focus, patience, and analysis—qualities I spent a decade sharpening. Representing India is my ultimate pride.",
            badges: ["6x National Champion", "Seoul 2026 Representative"],
            achievements: [
                "🥇 6 Consecutive Years National Champion (BC3 Male)",
                "🥇 Gold Medal – Manama 2024 World Boccia Challenger, Bahrain",
                "🏆 Only Indian selected for Seoul 2026 World Championships"
            ],
            skills: { precision: 99, control: 97, decision: 95 },
            playingStyle: "◈ Ramp Specialist"
        },
        {
            id: "sarita",
            name: "Sarita",
            classification: "BC3",
            accentColor: "#FF9933",
            state: "Uttar Pradesh",
            stateCode: "INUP",
            svgCoords: { x: 120, y: 380 },
            photo: "star players-20260714T053810Z-1-001/star players/sarita/WhatsApp Image 2026-07-07 at 19.15.27.jpeg",
            quote: "I transformed a childhood electric shock into a journey of art, innovation, and national sports representation.",
            badges: ["Zero Project Awardee", "Triple Amputee Innovator"],
            achievements: [
                "🥉 Double Bronze Medallist – Cairo 2024 World Boccia Challenger",
                "🥈 8x National Medalist (Individual & Pair)",
                "💡 Zero Project Discovery Award 2025 (Assistive Technology)"
            ],
            skills: { precision: 96, control: 95, decision: 94 },
            playingStyle: "◉ Precision Builder"
        },
        {
            id: "usha_kiran",
            name: "Usha Kiran",
            classification: "BC3",
            accentColor: "#FF9933",
            state: "Telangana",
            stateCode: "INTG",
            svgCoords: { x: 220, y: 430 },
            photo: "", // Empty to trigger fallback avatar
            quote: "Despite physical challenges, I found Boccia in rehabilitation. It gave me strength, confidence, and a sense of purpose.",
            badges: ["National Champion", "Telangana Representative"],
            achievements: [
                "🥇 1st Place (Gold) – 10th Boccia National Championship, 2026",
                "🥇 1st Place – 8th Boccia National Championship, 2024 (Mixed Pair)",
                "🥉 3rd Place – 7th Boccia National Championship, 2023"
            ],
            skills: { precision: 93, control: 90, decision: 88 },
            playingStyle: "⬤ Defensive Control"
        },
        {
            id: "vyom_pawa",
            name: "Vyom Pawa",
            classification: "BC2",
            accentColor: "#27AE60",
            state: "Delhi",
            stateCode: "INDL",
            svgCoords: { x: 410, y: 360 },
            photo: "star players-20260714T053810Z-1-001/star players/vyom pawa/vyom (1).jpeg",
            quote: "Boccia is a sport that matches my precision, strategy, and unwavering spirit. I train every day to conquer new heights.",
            badges: ["National Gold Medalist", "Asian Youth Para Games"],
            achievements: [
                "🥇 Gold Medal – 10th National Boccia Championship, Ahmedabad",
                "🥇 Gold Medal – 9th National Boccia Championship, Vizag (Team)",
                "🏆 5th Position – Kazakhstan World Boccia Challenger, 2025"
            ],
            skills: { precision: 90, control: 93, decision: 92 },
            playingStyle: "✦ Long Range Accuracy"
        }
    ];

    // DOM Elements
    const revealWrapper = document.getElementById("starsRevealWrapper");
    const constellationParallax = document.getElementById("constellationParallax");
    const constellationContainer = document.getElementById("constellationContainer");
    const constellationCenter = document.getElementById("constellationCenter");
    const beacons = document.querySelectorAll(".athlete-beacon");
    
    const card = document.getElementById("athleteProfileCard");
    const cardEmptyState = document.getElementById("cardEmptyStateView");
    const cardMainProfile = document.getElementById("cardMainProfileView");
    
    // Fixed Card Swap Elements
    const cardProfileImg = document.getElementById("cardProfileImg");
    const avatarFallback = document.getElementById("avatarFallback");
    const cardClassificationBadge = document.getElementById("cardClassificationBadge");
    const cardAthleteName = document.getElementById("cardAthleteName");
    const cardClassBadge = document.getElementById("cardClassBadge");
    const cardLocation = document.getElementById("cardLocation");
    const cardBadgesRow = document.getElementById("cardBadgesRow");
    const cardQuote = document.getElementById("cardQuote");
    
    const skillValPrecision = document.getElementById("skillValPrecision");
    const skillFillPrecision = document.getElementById("skillFillPrecision");
    const skillValControl = document.getElementById("skillValControl");
    const skillFillControl = document.getElementById("skillFillControl");
    const skillValDecision = document.getElementById("skillValDecision");
    const skillFillDecision = document.getElementById("skillFillDecision");
    
    const cardPlayingStyle = document.getElementById("cardPlayingStyle");
    const cardAchievementsList = document.getElementById("cardAchievementsList");
    const cardMapOverlay = document.getElementById("cardMapOverlay");
    
    const dynamicConnector = document.getElementById("dynamic-connector");
    const dynamicTravel = document.getElementById("dynamic-travel");

    // State Variables
    let activeAthleteIndex = -1;
    let showcaseTimer = null;
    let showcaseStopped = false;
    let isMouseOverSection = false;

    // 2. Cinematic Entrance on Viewport Enter
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                revealWrapper.classList.add("revealed");
                // Launch Idle Showcase after delay
                setTimeout(() => {
                    if (!showcaseStopped && activeAthleteIndex === -1) {
                        startShowcase();
                    }
                }, 1500);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    
    observer.observe(document.getElementById("boccia-stars"));

    // JS Image URL cleaning helper
    function getCleanImgUrl(path) {
        if (!path) return '';
        return path.split('/').map(segment => encodeURIComponent(segment)).join('/');
    }

    // 3. Selection Swapper Controller
    function selectAthlete(index, triggeredByShowcase = false) {
        if (index === activeAthleteIndex) return;
        activeAthleteIndex = index;
        
        const athlete = athletes[index];
        
        // Remove active class from all beacons
        beacons.forEach(b => b.classList.remove("active"));
        constellationContainer.classList.add("has-active-beacon");
        
        // Highlight selected beacon
        const activeBeacon = document.querySelector(`.athlete-beacon[data-id="${athlete.id}"]`);
        if (activeBeacon) {
            activeBeacon.classList.add("active");
        }

        // Draw dynamic connector path
        animateConnector(athlete.svgCoords.x, athlete.svgCoords.y, athlete.accentColor);

        // Hide Empty State and Reveal main layout with animation trigger reset
        card.classList.remove("active-transition");
        cardEmptyState.style.display = "none";
        cardMainProfile.style.display = "grid";
        
        // Apply Accent Color CSS Variable to Card
        card.style.setProperty("--class-color", athlete.accentColor);

        // Update Text & DOM Values
        setTimeout(() => {
            // Profile Portrait Fallback check
            if (athlete.photo) {
                cardProfileImg.src = getCleanImgUrl(athlete.photo);
                cardProfileImg.style.display = "block";
                avatarFallback.style.display = "none";
            } else {
                cardProfileImg.style.display = "none";
                avatarFallback.style.display = "flex";
            }
            
            cardClassificationBadge.textContent = `${athlete.classification} Selected`;
            cardAthleteName.textContent = athlete.name;
            cardClassBadge.textContent = athlete.classification;
            cardLocation.textContent = `${athlete.state}, India`;
            
            // Build Badges
            cardBadgesRow.innerHTML = athlete.badges.map(b => `<span class="meta-pill pill-gold">${b}</span>`).join("");
            
            cardQuote.textContent = athlete.quote;
            cardPlayingStyle.textContent = athlete.playingStyle;
            
            // Build Achievements
            cardAchievementsList.innerHTML = athlete.achievements.map(a => `<li>${a}</li>`).join("");

            // Highlight Home State in the India Map SVG Overlay
            highlightStateInOverlay(athlete.stateCode);

            // Trigger card entrance transition
            card.classList.add("active-transition");
            
            // Trigger Skills Expansion
            setTimeout(() => {
                skillValPrecision.textContent = `${athlete.skills.precision}%`;
                skillFillPrecision.style.width = `${athlete.skills.precision}%`;
                
                skillValControl.textContent = `${athlete.skills.control}%`;
                skillFillControl.style.width = `${athlete.skills.control}%`;
                
                skillValDecision.textContent = `${athlete.skills.decision}%`;
                skillFillDecision.style.width = `${athlete.skills.decision}%`;
            }, 50);

        }, 150);
    }

    // Dynamic SVG line connector
    function animateConnector(x, y, color) {
        dynamicConnector.setAttribute("stroke", color);
        dynamicConnector.setAttribute("d", `M ${x} ${y} L 500 250`);
        dynamicTravel.setAttribute("d", `M ${x} ${y} L 500 250`);

        const len = dynamicConnector.getTotalLength();
        dynamicConnector.style.strokeDasharray = len;
        dynamicConnector.style.strokeDashoffset = len;
        
        // Force reflow
        dynamicConnector.getBoundingClientRect();

        // Animate drawing
        dynamicConnector.style.transition = "none";
        dynamicConnector.style.strokeDashoffset = len;
        dynamicConnector.style.opacity = "1";
        
        dynamicConnector.getBoundingClientRect();
        
        dynamicConnector.style.transition = "stroke-dashoffset 0.6s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.4s ease";
        dynamicConnector.style.strokeDashoffset = "0";

        // Spark pulse travel
        dynamicTravel.classList.remove("animating");
        dynamicTravel.getBoundingClientRect();
        dynamicTravel.classList.add("animating");

        // Fade link out
        setTimeout(() => {
            dynamicConnector.style.opacity = "0.1";
        }, 1200);
    }

    // Highlight map overlay state
    function highlightStateInOverlay(stateCode) {
        if (!cardMapOverlay) return;
        const allPaths = cardMapOverlay.querySelectorAll("path");
        allPaths.forEach(p => {
            p.classList.remove("active-state");
            p.style.removeProperty("fill");
            p.style.removeProperty("stroke");
        });
        
        const statePath = cardMapOverlay.querySelector(`path[id="${stateCode}"]`);
        if (statePath) {
            statePath.classList.add("active-state");
        }
    }

    // 4. Idle Showcase loops
    function startShowcase() {
        if (showcaseStopped) return;
        clearInterval(showcaseTimer);
        
        showcaseTimer = setInterval(() => {
            if (!isMouseOverSection && !showcaseStopped) {
                let nextIdx = (activeAthleteIndex + 1) % athletes.length;
                selectAthlete(nextIdx, true);
            }
        }, 15000); // 15 seconds spec
    }

    function stopShowcasePermanently() {
        showcaseStopped = true;
        clearInterval(showcaseTimer);
    }

    // Pause on hover
    document.getElementById("boccia-stars").addEventListener("mouseenter", () => {
        isMouseOverSection = true;
    });
    document.getElementById("boccia-stars").addEventListener("mouseleave", () => {
        isMouseOverSection = false;
    });

    // 5. Beacons Click events
    beacons.forEach((beacon, idx) => {
        beacon.addEventListener("click", () => {
            stopShowcasePermanently();
            selectAthlete(idx);
        });
    });

    // 6. Gravity Pull Interaction
    constellationCenter.addEventListener("mouseenter", () => {
        constellationParallax.classList.add("gravity-active");
    });
    constellationCenter.addEventListener("mouseleave", () => {
        constellationParallax.classList.remove("gravity-active");
    });
    constellationCenter.addEventListener("click", () => {
        stopShowcasePermanently();
        // Cycle athletes on clicking center
        let nextIdx = (activeAthleteIndex + 1) % athletes.length;
        selectAthlete(nextIdx);
    });

    // 7. Parallax Move Interaction
    constellationContainer.addEventListener("mousemove", (e) => {
        if (window.innerWidth <= 768) return; // Disable parallax on mobile
        
        const rect = constellationContainer.getBoundingClientRect();
        const mouseX = e.clientX - rect.left - rect.width / 2;
        const mouseY = e.clientY - rect.top - rect.height / 2;
        
        // Max 8px shift
        const moveX = (mouseX / (rect.width / 2)) * 8;
        const moveY = (mouseY / (rect.height / 2)) * 8;
        
        constellationParallax.style.transform = `translate(${moveX}px, ${moveY}px)`;
    });

    constellationContainer.addEventListener("mouseleave", () => {
        constellationParallax.style.transform = "translate(0px, 0px)";
    });

    // 8. Keyboard Accessibility (Reading flow + spatial navigation)
    // Map spatial coordinates (in viewBox 500x500 grid)
    const coordsMap = athletes.map((a, index) => ({
        index,
        x: a.svgCoords.x,
        y: a.svgCoords.y,
        id: a.id
    }));

    function getSpatialNeighbor(currentIdx, direction) {
        const cur = coordsMap[currentIdx];
        let bestCandidate = -1;
        let bestScore = Infinity;

        coordsMap.forEach(target => {
            if (target.index === currentIdx) return;
            const dx = target.x - cur.x;
            const dy = target.y - cur.y;
            const dist = Math.hypot(dx, dy);

            let isHeadingDirection = false;
            
            // Score weight: we prefer candidates in the general direction, penalized by distance
            switch(direction) {
                case "ArrowUp":
                    isHeadingDirection = dy < -20;
                    break;
                case "ArrowDown":
                    isHeadingDirection = dy > 20;
                    break;
                case "ArrowLeft":
                    isHeadingDirection = dx < -20;
                    break;
                case "ArrowRight":
                    isHeadingDirection = dx > 20;
                    break;
            }

            if (isHeadingDirection) {
                // Score metric: distance
                if (dist < bestScore) {
                    bestScore = dist;
                    bestCandidate = target.index;
                }
            }
        });

        return bestCandidate;
    }

    // Listeners for keyboard keypress
    document.addEventListener("keydown", (e) => {
        const activeElement = document.activeElement;
        if (!activeElement || !activeElement.classList.contains("athlete-beacon")) return;

        const currentId = activeElement.getAttribute("data-id");
        const currentIdx = athletes.findIndex(a => a.id === currentId);
        
        if (currentIdx === -1) return;

        if (["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"].includes(e.key)) {
            e.preventDefault();
            stopShowcasePermanently();
            
            const neighborIdx = getSpatialNeighbor(currentIdx, e.key);
            if (neighborIdx !== -1) {
                const targetBeacon = document.querySelector(`.athlete-beacon[data-id="${athletes[neighborIdx].id}"]`);
                if (targetBeacon) {
                    targetBeacon.focus();
                    selectAthlete(neighborIdx);
                }
            }
        } else if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            stopShowcasePermanently();
            selectAthlete(currentIdx);
        }
    });

    // Tab indices setup based on logical reading flow (Top-to-Bottom, Left-to-Right)
    const sortedBeacons = Array.from(beacons).sort((a, b) => {
        const ay = parseFloat(a.style.getPropertyValue("--y"));
        const by = parseFloat(b.style.getPropertyValue("--y"));
        if (Math.abs(ay - by) < 5) { // Same vertical level
            const ax = parseFloat(a.style.getPropertyValue("--x"));
            const bx = parseFloat(b.style.getPropertyValue("--x"));
            return ax - bx;
        }
        return ay - by;
    });

    sortedBeacons.forEach((b, i) => {
        b.setAttribute("tabindex", i + 10); // Start tab index at 10 to clear main nav tab range
        b.addEventListener("focus", () => {
            stopShowcasePermanently();
            const beaconId = b.getAttribute("data-id");
            const beaconIdx = athletes.findIndex(a => a.id === beaconId);
            if (beaconIdx !== -1) {
                selectAthlete(beaconIdx);
            }
        });
    });
});
</script>

<!-- National Schedules Section -->
<section id="schedules" class="schedules-section" style="padding: 6rem 0; background: url('bg_schedule.webp') center/cover no-repeat;">

    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 0.5rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
                <span class="schedules-eyebrow" style="color: var(--accent-saffron);">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
            </div>
            <h2 class="section-title" style="color: #ffffff; font-size: 2.8rem;">Schedule</h2>
        </div>

        <?php if (count($publicSchedules) > 0): ?>
        <div class="schedule-table-wrapper" style="overflow-x: auto; padding-bottom: 2rem;">
            <div class="schedule-table" style="min-width: 800px;">
                <!-- Rows -->
                <div class="schedule-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <?php 
                    $rowIdx = 1;
                    foreach ($publicSchedules as $sched): 
                        // Alternating border colors
                        $borderColor = ($rowIdx % 2 !== 0) ? '#081B4B' : '#FF9933';
                    ?>
                    <div class="schedule-row-new" style="background: #ffffff; border: 2px solid <?php echo $borderColor; ?>; border-radius: 18px; display: grid; grid-template-columns: 100px 2.5fr 2fr 3fr; padding: 1.5rem 2.25rem; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
                        
                        <!-- Number and Separator -->
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <span style="font-size: 2.2rem; font-weight: 800; color: #081B4B; font-family: var(--font-heading); min-width: 45px; letter-spacing: -0.02em;"><?php echo str_pad($rowIdx, 2, '0', STR_PAD_LEFT); ?></span>
                            <span style="width: 1.5px; height: 44px; background: rgba(8, 27, 75, 0.15); display: inline-block;"></span>
                        </div>

                        <!-- Discipline & Type -->
                        <div>
                            <div style="font-weight: 700; font-size: 1.15rem; color: #081B4B; font-family: var(--font-heading);"><?php echo htmlspecialchars($sched['discipline']); ?></div>
                            <?php if ($sched['event_type']): ?>
                            <div style="font-size: 0.85rem; color: #6b82b8; font-weight: 500; margin-top: 0.2rem;"><?php echo htmlspecialchars($sched['event_type']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Date -->
                        <div style="font-size: 1.05rem; font-weight: 700; color: #FF9933;"><?php echo htmlspecialchars($sched['date_text']); ?></div>

                        <!-- Venue & Action -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;">
                            <span style="font-size: 1.05rem; font-weight: 500; color: #3b4a6b;"><?php echo htmlspecialchars($sched['venue']); ?></span>
                            <?php if ($sched['registration_link']): ?>
                            <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" style="background: #081B4B; color: #fff; padding: 0.5rem 1.25rem; border-radius: 999px; font-size: 0.85rem; font-weight: bold; text-decoration: none; transition: background 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#FF9933'" onmouseout="this.style.background='#081B4B'">Register</a>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php $rowIdx++; endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Mobile Cards version -->
        <div class="schedule-mobile-cards">
            <?php 
            $rowIdx = 1;
            foreach ($publicSchedules as $sched): 
                $borderColor = ($rowIdx % 2 !== 0) ? '#081B4B' : '#FF9933';
            ?>
            <div class="schedule-card" style="border: 2px solid <?php echo $borderColor; ?>; border-radius: 16px; background: #ffffff; padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="schedule-card-header" style="border-bottom: 1px solid rgba(8, 27, 75, 0.08); padding-bottom: 0.75rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 class="discipline" style="font-family: var(--font-heading); color: #081B4B; font-weight: 700; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($sched['discipline']); ?></h4>
                        <?php if ($sched['event_type']): ?>
                            <span class="event-type" style="font-size: 0.75rem; color: #6b82b8; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($sched['event_type']); ?></span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 1.5rem; font-weight: 800; color: rgba(8, 27, 75, 0.15); font-family: var(--font-heading);"><?php echo str_pad($rowIdx, 2, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="schedule-card-body" style="margin-bottom: 1rem;">
                    <p class="date" style="font-size: 0.95rem; color: #FF9933; font-weight: 700; margin-bottom: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                        <span style="color: #FF9933; display: inline-flex; align-items: center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span> <?php echo htmlspecialchars($sched['date_text']); ?>
                    </p>
                    <p class="venue" style="font-size: 0.95rem; color: #3b4a6b; font-weight: 500; margin-bottom: 0; display: flex; gap: 0.5rem; align-items: center;">
                        <span style="display: inline-flex; align-items: center; color: #3b4a6b;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></span> <?php echo htmlspecialchars($sched['venue']); ?>
                    </p>
                </div>
                <?php if ($sched['registration_link']): ?>
                <div class="schedule-card-footer">
                    <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" class="btn btn-hero-primary" style="width: 100%; text-align: center; padding: 0.75rem; background: #081B4B; border-radius: 999px; color: #ffffff; text-decoration: none; font-weight: bold; display: block;">Register Now</a>
                </div>
                <?php endif; ?>
            </div>
            <?php $rowIdx++; endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Photo Gallery Section -->
<section id="photo-gallery" class="photo-gallery-section">
    <!-- Animated background light field (5 Green Blobs, 3 Blue Blobs) -->
    <div class="gallery-aurora-bg">
        <div class="aurora-blob green-blob blob-1"></div>
        <div class="aurora-blob green-blob blob-2"></div>
        <div class="aurora-blob green-blob blob-3"></div>
        <div class="aurora-blob green-blob blob-4"></div>
        <div class="aurora-blob green-blob blob-5"></div>
        <div class="aurora-blob blue-blob blob-6"></div>
        <div class="aurora-blob blue-blob blob-7"></div>
        <div class="aurora-blob blue-blob blob-8"></div>
    </div>
<style>
/* ── Wide Gallery Container ── */
.gallery-wide-container {
    max-width: 1600px;
    width: 92%;
    margin: 0 auto;
}

/* ── Gallery Header ── */
.gal-eyebrow {
    color: #FF9933;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-size: .85rem;
}
.gal-title {
    font-family: var(--font-heading);
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    font-weight: 800;
    color: #081B4B;
    margin: 0.5rem 0 1rem;
}
.gal-subtitle {
    font-size: clamp(0.95rem, 1.5vw, 1.15rem);
    color: rgba(8, 27, 75, 0.75);
    max-width: 700px;
    margin: 0 auto 1.5rem;
    line-height: 1.6;
}
.gal-stats-pill {
    display: inline-flex;
    align-items: center;
    gap: 1.5rem;
    background: rgba(8, 27, 75, 0.05);
    border: 1px solid rgba(8, 27, 75, 0.1);
    padding: 0.5rem 1.75rem;
    border-radius: 999px;
    font-size: 0.9rem;
    color: #081B4B;
    margin-bottom: 3rem;
}
.gal-stats-pill strong {
    color: #FF9933;
}

/* ── Section Dividers & Subtitles ── */
.gal-section-divider {
    font-family: var(--font-heading);
    font-size: 1.8rem;
    font-weight: 800;
    color: #081B4B;
    margin-bottom: 2rem;
    border-bottom: 2px solid rgba(8, 27, 75, 0.08);
    padding-bottom: 0.75rem;
}

/* ── Category Filters ── */
.gal-filters-wrap {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 3.5rem;
}
.gal-filter-btn {
    background: #ffffff;
    border: 1px solid rgba(8, 27, 75, 0.15);
    color: #081B4B;
    border-radius: 999px;
    padding: 0.55rem 1.35rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}
.gal-filter-btn:hover, .gal-filter-btn.active {
    background: #081B4B;
    color: #ffffff;
    border-color: #081B4B;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(8, 27, 75, 0.15);
}

/* ── Album Grid ── */
.gal-albums-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 4rem;
}
.gal-album-card {
    background: #ffffff;
    border: 1px solid rgba(8, 27, 75, 0.08);
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 15px rgba(8, 27, 75, 0.03);
}
.gal-album-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(8, 27, 75, 0.1);
    border-color: #FF9933;
}
.gal-album-img-wrap {
    width: 100%;
    aspect-ratio: 16/10;
    overflow: hidden;
    position: relative;
    background: #081B4B;
}
.gal-album-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.gal-album-card:hover .gal-album-img {
    transform: scale(1.04);
}
.gal-album-count-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(8, 27, 75, 0.85);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #F8F5EF;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.gal-album-info {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.gal-album-title {
    font-family: var(--font-heading);
    font-size: 1.4rem;
    font-weight: 750;
    color: #081B4B;
    margin-bottom: 0.5rem;
}
.gal-album-meta {
    font-size: 0.85rem;
    color: rgba(8, 27, 75, 0.6);
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    gap: 0.5rem;
}
.gal-album-desc {
    font-size: 0.9rem;
    color: rgba(8, 27, 75, 0.7);
    line-height: 1.5;
    margin: 0;
}

/* ── Album Detail View ── */
.gal-detail-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    border-bottom: 2px solid rgba(8, 27, 75, 0.08);
    padding-bottom: 1rem;
}
.gal-back-btn {
    background: #ffffff;
    border: 1px solid rgba(8, 27, 75, 0.15);
    color: #081B4B;
    border-radius: 999px;
    padding: 0.5rem 1.5rem;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.25s ease;
}
.gal-back-btn:hover {
    background: #081B4B;
    color: #ffffff;
    border-color: #081B4B;
}
.gal-album-detail-header {
    margin-bottom: 2.5rem;
}
.gal-detail-meta {
    display: flex;
    gap: 1.5rem;
    color: rgba(8, 27, 75, 0.6);
    font-size: 0.95rem;
    margin-top: 0.5rem;
}

/* ── Masonry Grid ── */
.gal-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}
.gal-photo-item {
    display: block;
    aspect-ratio: 4/3;
    border-radius: 18px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 6px 18px rgba(8, 27, 75, 0.06);
    cursor: pointer;
    background: #ffffff;
    border: 1px solid rgba(8, 27, 75, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.gal-photo-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(8, 27, 75, 0.12);
}
.gal-photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.gal-photo-item:hover img {
    transform: scale(1.04);
}
.gal-photo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(8, 27, 75, 0.85) 0%, transparent 60%);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: flex-end;
    padding: 1.25rem;
}
.gal-photo-item:hover .gal-photo-overlay {
    opacity: 1;
}
.gal-photo-caption {
    color: #ffffff;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
}
.gal-hidden {
    display: none !important;
}

@media (max-width: 768px) {
    .gal-albums-grid {
        grid-template-columns: 1fr;
    }
    .gal-photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
    .gal-filters-wrap {
        justify-content: flex-start;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 0.5rem 1.5rem 0.85rem 1.5rem;
        margin-left: -1rem;
        margin-right: -1rem;
        -webkit-overflow-scrolling: touch;
        gap: 0.6rem;
        margin-bottom: 2rem;
        scrollbar-width: thin;
        scrollbar-color: #FF9933 rgba(8, 27, 75, 0.05);
    }
    .gal-filters-wrap::-webkit-scrollbar {
        display: block;
        height: 3px;
    }
    .gal-filters-wrap::-webkit-scrollbar-track {
        background: rgba(8, 27, 75, 0.05);
        border-radius: 10px;
    }
    .gal-filters-wrap::-webkit-scrollbar-thumb {
        background: #FF9933;
        border-radius: 10px;
    }
    .gal-filter-btn {
        flex-shrink: 0;
        white-space: nowrap;
        padding: 0.45rem 1.15rem;
        font-size: 0.82rem;
    }
}
</style>

<div class="gallery-wide-container section-content-relative">

    <!-- Section Header -->
    <div style="text-align:center; margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:.75rem;">
            <span style="height:1px;width:40px;background:#FF9933;"></span>
            <span class="gal-eyebrow">BSFI — Moments in Focus</span>
            <span style="height:1px;width:40px;background:#FF9933;"></span>
        </div>
        <h2 class="gal-title">Photo Gallery</h2>
        <p class="gal-subtitle">Explore national championships, international participation, training camps and athlete journeys.</p>
    </div>

    <!-- Stats Bar -->
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <div class="gal-stats-pill">
            <span><strong><?php echo count($galleryImages); ?></strong> Photos</span>
            <span style="opacity: 0.3;">•</span>
            <span><strong><?php echo count($galleryAlbums); ?></strong> Albums</span>
            <span style="opacity: 0.3;">•</span>
            <span><strong><?php echo count($galleryCategories); ?></strong> Categories</span>
        </div>
    </div>

    <!-- VIEW 1: BROWSE ALBUMS (Visible by default) -->
    <div id="galMainBrowseView">
        
        <!-- FEATURED ALBUMS (IF ANY) -->
        <?php
        $featuredAlbums = array_filter($galleryAlbums, function($a) { return (int)$a['is_featured'] === 1; });
        if (!empty($featuredAlbums)):
        ?>
        <h3 class="gal-section-divider">★ Featured Events</h3>
        <div class="gal-albums-grid" style="margin-bottom: 4rem;">
            <?php foreach ($featuredAlbums as $alb): 
                $coverSrc = !empty($alb['cover_image_override']) ? htmlspecialchars($alb['cover_image_override']) : (!empty($alb['cover_thumb_path']) ? htmlspecialchars($alb['cover_thumb_path']) : (!empty($alb['cover_image_path']) ? htmlspecialchars($alb['cover_image_path']) : 'assets/images/bsfi-placeholder.webp'));
            ?>
            <div class="gal-album-card" onclick="openAlbum('<?php echo htmlspecialchars($alb['slug']); ?>')">
                <div class="gal-album-img-wrap">
                    <img src="<?php echo $coverSrc; ?>" alt="<?php echo htmlspecialchars($alb['title']); ?>" class="gal-album-img" loading="lazy">
                    <span class="gal-album-count-badge"><?php echo (int)$alb['image_count']; ?> Photos</span>
                </div>
                <div class="gal-album-info">
                    <h3 class="gal-album-title"><?php echo htmlspecialchars($alb['title']); ?></h3>
                    <p class="gal-album-meta">
                        <?php if (!empty($alb['event_location'])): ?>
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><?php echo htmlspecialchars($alb['event_location']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($alb['event_date'])): ?>
                            <span>• <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg><?php echo date('M Y', strtotime($alb['event_date'])); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="gal-album-desc"><?php echo htmlspecialchars($alb['description'] ?: 'Official tournament and athlete media.'); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- CATEGORIES FILTERS -->
        <div class="gal-filters-wrap">
            <button class="gal-filter-btn active" data-filter="all">All Categories</button>
            <?php foreach ($galleryCategories as $cat): ?>
            <button class="gal-filter-btn" data-filter="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- ALBUM CARDS GRID -->
        <h3 class="gal-section-divider">All Collections</h3>
        <div class="gal-albums-grid" id="galAlbumsGrid">
            <?php foreach ($galleryAlbums as $alb): 
                $coverSrc = !empty($alb['cover_image_override']) ? htmlspecialchars($alb['cover_image_override']) : (!empty($alb['cover_thumb_path']) ? htmlspecialchars($alb['cover_thumb_path']) : (!empty($alb['cover_image_path']) ? htmlspecialchars($alb['cover_image_path']) : 'assets/images/bsfi-placeholder.webp'));
            ?>
            <div class="gal-album-card gal-album-card-item" data-category-slug="<?php echo htmlspecialchars($alb['category_slug']); ?>">
                <div class="gal-album-img-wrap" onclick="openAlbum('<?php echo htmlspecialchars($alb['slug']); ?>')">
                    <img src="<?php echo $coverSrc; ?>" alt="<?php echo htmlspecialchars($alb['title']); ?>" class="gal-album-img" loading="lazy">
                    <span class="gal-album-count-badge"><?php echo (int)$alb['image_count']; ?> Photos</span>
                </div>
                <div class="gal-album-info">
                    <h3 class="gal-album-title" onclick="openAlbum('<?php echo htmlspecialchars($alb['slug']); ?>')"><?php echo htmlspecialchars($alb['title']); ?></h3>
                    <p class="gal-album-meta">
                        <?php if (!empty($alb['event_location'])): ?>
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><?php echo htmlspecialchars($alb['event_location']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($alb['event_date'])): ?>
                            <span>• <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg><?php echo date('M Y', strtotime($alb['event_date'])); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="gal-album-desc"><?php echo htmlspecialchars($alb['description'] ?: 'Official media resources.'); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- VIEW 2: ALBUM DETAIL VIEW (Masonry + Lightbox - Hidden by default) -->
    <div id="galViewDetail" style="display: none;">
        <div class="gal-detail-header-row">
            <button class="gal-back-btn" onclick="closeAlbum()">← Back to Albums</button>
        </div>

        <div class="gal-album-detail-header" style="color: #ffffff; text-align: left; margin-bottom: 2rem;">
            <h2 id="detailTitle" class="gal-title" style="margin: 0 0 0.5rem; text-align: left; color: #ffffff; font-weight: 800; font-size: clamp(2rem, 4vw, 3rem);">Album Title</h2>
            <div class="gal-detail-meta" id="detailMeta" style="color: rgba(255, 255, 255, 0.75); font-size: 0.95rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Populated via JS -->
            </div>
            <p id="detailDesc" style="margin-top: 1rem; color: rgba(255, 255, 255, 0.9); max-width: 800px; line-height: 1.6; font-size: 1.05rem; font-weight: 400; opacity: 0.95;">Album Description</p>
        </div>

        <!-- Masonry Grid for selected album photos -->
        <div class="gal-photos-grid" id="albumDetailGrid">
            <!-- Populated via JS -->
        </div>
    </div>

</div>

<script>
(function() {
    // Inject album & photo variables
    const albumsList = <?php echo json_encode($galleryAlbums); ?>;
    const photosList = <?php echo json_encode($galleryImages); ?>;
    let activeLightbox = null;

    // Initialize GLightbox for only the visible gallery images
    function initLightbox() {
        if (activeLightbox) activeLightbox.destroy();
        activeLightbox = GLightbox({
            selector: '.gal-photo-item:not(.gal-hidden)',
            touchNavigation: true,
            loop: true
        });
    }

    // Function to Open an Album
    window.openAlbum = function(slug) {
        const album = albumsList.find(a => a.slug === slug);
        if (!album) return;

        // Set header information
        document.getElementById('detailTitle').textContent = album.title;
        
        let metaHtml = `<strong>${album.image_count}</strong> Photos`;
        if (album.event_location) metaHtml += ` <span style="opacity: 0.4;">•</span> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>${album.event_location}`;
        if (album.event_date) {
            const dateObj = new Date(album.event_date);
            const dateStr = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
            metaHtml += ` <span style="opacity: 0.4;">•</span> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>${dateStr}`;
        }
        document.getElementById('detailMeta').innerHTML = metaHtml;
        document.getElementById('detailDesc').textContent = album.description || 'Official media archive event.';

        // Filter photos for this album
        const albumPhotos = photosList.filter(p => p.album_slug === slug);
        let gridHtml = '';

        if (albumPhotos.length > 0) {
            albumPhotos.forEach(img => {
                const imgPath = img.image_path;
                const thumbPath = img.thumbnail_path || img.image_path;
                const caption = img.caption || 'BSFI Gallery Photo';
                const credit = img.credit ? `Photo: ${img.credit}` : '';

                gridHtml += `
                <a href="${imgPath}" class="glightbox gal-photo-item" data-gallery="album-detail-gallery" data-title="${caption}" data-description="${credit}">
                    <img src="${thumbPath}" alt="${caption}" loading="lazy">
                    <div class="gal-photo-overlay">
                        <p class="gal-photo-caption">${caption}</p>
                    </div>
                </a>
                `;
            });
        } else {
            gridHtml = `<div style="grid-column: 1 / -1; text-align: center; padding: 4rem; opacity: 0.5;">No photos available in this album.</div>`;
        }

        document.getElementById('albumDetailGrid').innerHTML = gridHtml;

        // Transition views
        document.getElementById('galMainBrowseView').style.display = 'none';
        document.getElementById('galViewDetail').style.display = 'block';

        // Scroll to gallery top anchor smoothly
        document.getElementById('photo-gallery').scrollIntoView({ behavior: 'smooth' });

        // Update URL hash
        window.location.hash = 'album-' + slug;

        // Re-initialize lightbox
        initLightbox();
    };

    // Function to Close Album and Go Back
    window.closeAlbum = function() {
        document.getElementById('galViewDetail').style.display = 'none';
        document.getElementById('galMainBrowseView').style.display = 'block';
        
        // Remove hash without scrolling
        history.pushState("", document.title, window.location.pathname + window.location.search);

        initLightbox();
    };

    // Category Filter Buttons logic
    const filterButtons = document.querySelectorAll('.gal-filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');
            const cards = document.querySelectorAll('.gal-album-card-item');
            
            cards.forEach(card => {
                const slug = card.getAttribute('data-category-slug');
                if (filter === 'all' || slug === filter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Handle deep linking or direct load hash
    document.addEventListener('DOMContentLoaded', () => {
        initLightbox();

        const hash = window.location.hash;
        if (hash && hash.startsWith('#album-')) {
            const slug = hash.replace('#album-', '');
            openAlbum(slug);
        }
    });

    // Listen to hash changes (e.g. back button)
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#album-')) {
            const slug = hash.replace('#album-', '');
            openAlbum(slug);
        } else if (!hash && document.getElementById('galViewDetail').style.display !== 'none') {
            closeAlbum();
        }
    });

})();
</script>
</section>

<!-- Latest News Section -->
<section id="news" class="news-section" style="padding: 6rem 0; background: var(--warm-surface) url('news_bg.webp') top center / cover fixed no-repeat;">
    <div id="official-federation-updates" style="scroll-margin-top: 120px;"></div>
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 0.5rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
                <span class="news-eyebrow" style="color: var(--accent-saffron);">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
            </div>
            <h2 class="section-title" style="color: #081B4B; font-size: 2.8rem;">Official Federation Updates</h2>
        </div>

        <?php if (count($allNews) > 0): ?>
        <div class="news-scroll-container" style="max-height: 720px; overflow-y: auto; overflow-x: hidden; padding: 0.5rem; scrollbar-width: thin;">
            <div class="row g-4 align-items-stretch">
                <?php 
                foreach ($allNews as $news): 
                    $extraImgs = isset($newsImages[$news['id']]) ? $newsImages[$news['id']] : [];
                    $allMedia = [];
                    if (!empty($news['image'])) $allMedia[] = $news['image'];
                    $allMedia = array_merge($allMedia, $extraImgs);
                    $imgCount = count($allMedia);
                    
                    // Truncate Content to 250 chars
                    $rawContent = strip_tags($news['content']);
                    $isLong = strlen($rawContent) > 250;
                    $displayContent = $isLong ? substr($rawContent, 0, 250) . '...' : $rawContent;
                    
                    // Parse Hashtags
                    $displayContent = htmlspecialchars($displayContent);
                    $displayContent = preg_replace('/#(\w+)/', '<span class="hashtag" style="color:#1E88E5; font-weight:600;">#$1</span>', $displayContent);
                ?>
                <div class="col-12 col-md-6 col-lg-4 d-flex align-items-stretch">
                    <div class="glass-card news-card w-100" id="news-<?php echo htmlspecialchars($news['slug']); ?>" style="background: #ffffff; border: 2px solid rgba(22, 41, 90, 0.1); border-radius: 32px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 0;">
                        
                        <!-- Post Header -->
                        <div style="padding: 1.5rem 1.5rem 1rem; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: transparent;">
                                    <svg viewBox="0 0 100 100" width="100%" height="100%">
                                        <g stroke="#111" stroke-width="1.5" stroke-linejoin="round">
                                            <circle cx="28" cy="70" r="24" fill="#ffffff" />
                                            <path d="M 28 58 L 38 65 L 34 78 L 22 78 L 18 65 Z" fill="none" />
                                            <path d="M 28 58 L 28 46" fill="none" /><path d="M 38 65 L 50 60" fill="none" /><path d="M 34 78 L 42 90" fill="none" /><path d="M 22 78 L 14 90" fill="none" /><path d="M 18 65 L 6 60" fill="none" />
                                            
                                            <circle cx="72" cy="70" r="24" fill="#A82020" />
                                            <path d="M 72 58 L 82 65 L 78 78 L 66 78 L 62 65 Z" fill="none" />
                                            <path d="M 72 58 L 72 46" fill="none" /><path d="M 82 65 L 94 60" fill="none" /><path d="M 78 78 L 86 90" fill="none" /><path d="M 66 78 L 58 90" fill="none" /><path d="M 62 65 L 50 60" fill="none" />
      
                                            <circle cx="50" cy="32" r="24" fill="#0E4C92" />
                                            <path d="M 50 20 L 60 27 L 56 40 L 44 40 L 40 27 Z" fill="none" />
                                            <path d="M 50 20 L 50 8" fill="none" /><path d="M 60 27 L 72 22" fill="none" /><path d="M 56 40 L 64 52" fill="none" /><path d="M 44 40 L 36 52" fill="none" /><path d="M 40 27 L 28 22" fill="none" />
                                        </g>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--deep-navy); font-size: 0.95rem;"><?php echo htmlspecialchars($news['author_name'] ?? 'BSFI Official'); ?></div>
                                    <div style="font-size: 0.75rem; color: #6b82b8;"><?php echo date('M j, Y • h:i A', strtotime($news['published_at'])); ?></div>
                                </div>
                            </div>
                            <!-- Badges -->
                            <div style="display: flex; gap: 0.5rem; flex-direction: column; align-items: flex-end;">
                                <?php if($news['pinned']): ?>
                                    <span style="background: rgba(36,194,122,0.1); color: var(--accent-saffron); padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: bold;">📌 Pinned</span>
                                <?php endif; ?>
                                <?php if($news['featured']): ?>
                                    <span style="background: rgba(244,185,66,0.1); color: #F4B942; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: bold;">⭐ Featured</span>
                                <?php endif; ?>
                            </div>
                        </div>
      
                        <!-- Post Body -->
                        <div style="padding: 0 1.5rem 1rem; flex-grow: 1;">
                            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--deep-navy); margin-bottom: 0.5rem; font-family: var(--font-heading);">
                                <a href="news.php?slug=<?php echo urlencode($news['slug']); ?>" style="color: inherit; text-decoration: none;" class="hover-orange"><?php echo htmlspecialchars($news['title']); ?></a>
                            </h3>
                            <p style="font-size: 0.95rem; color: #3b5a9a; line-height: 1.5; margin-bottom: 0;">
                                <?php echo $displayContent; ?>
                            </p>
                            <a href="news.php?slug=<?php echo urlencode($news['slug']); ?>" style="color: var(--accent-saffron); font-weight: 700; text-decoration: none; font-size: 0.9rem; margin-top: 0.5rem; display: inline-block;" class="hover-orange">Read More &raquo;</a>
                        </div>
      
                        <!-- Media Grid -->
                        <?php if ($imgCount > 0): ?>
                        <div class="news-media-container" style="padding: 0 1.5rem 1.5rem;">
                            <div style="border-radius: 20px; overflow: hidden; display: grid; gap: 4px; background: #fff; <?php 
                                if ($imgCount == 1) echo 'grid-template-columns: 1fr;';
                                if ($imgCount == 2) echo 'grid-template-columns: 1fr 1fr; grid-auto-rows: 250px;';
                                if ($imgCount == 3) echo 'grid-template-columns: 1fr 1fr; grid-auto-rows: 150px;';
                                if ($imgCount >= 4) echo 'grid-template-columns: 1fr 1.5fr; grid-template-rows: repeat(3, 100px);';
                            ?>">
                                
                                <?php for($i = 0; $i < min($imgCount, 4); $i++): ?>
                                    <?php 
                                    $imgStyle = 'width: 100%; height: 100%; object-fit: cover;';
                                    $wrapperStyle = 'position: relative; width: 100%; height: 100%;';
                                    
                                    if ($imgCount == 1) {
                                        $wrapperStyle .= 'aspect-ratio: 4/3;';
                                    } elseif ($imgCount == 3 && $i == 0) {
                                        $wrapperStyle .= 'grid-row: span 2;';
                                    } elseif ($imgCount >= 4) {
                                        // Bento Box layout requested
                                        if ($i == 0) $wrapperStyle .= 'grid-area: 1 / 1 / 2 / 2;';
                                        if ($i == 1) $wrapperStyle .= 'grid-area: 1 / 2 / 3 / 3;';
                                        if ($i == 2) $wrapperStyle .= 'grid-area: 2 / 1 / 4 / 2;';
                                        if ($i == 3) $wrapperStyle .= 'grid-area: 3 / 2 / 4 / 3;';
                                    }
                                    ?>
                                    
                                    <a href="<?php echo htmlspecialchars($allMedia[$i]); ?>" class="glightbox" data-gallery="news-<?php echo $news['id']; ?>" style="<?php echo $wrapperStyle; ?>">
                                        <img src="<?php echo htmlspecialchars($allMedia[$i]); ?>" alt="News Media" style="<?php echo $imgStyle; ?>" loading="lazy">
                                        <?php if ($imgCount > 4 && $i == 3): ?>
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: bold;">
                                                +<?php echo ($imgCount - 4); ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endif; ?>
      
                        <!-- Footer / Reactions -->
                        <div style="padding: 1rem 1.5rem; border-top: 1px solid rgba(22, 41, 90, 0.05); display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 0.85rem; color: #6b82b8; font-weight: 600;">
                                👁 <?php echo (int)$news['views']; ?> Views
                            </div>
                            <div style="font-size: 0.85rem; color: var(--accent-saffron); font-weight: 700; background: rgba(36,194,122,0.1); padding: 0.25rem 0.75rem; border-radius: 999px;">
                                <?php echo htmlspecialchars($news['category']); ?>
                            </div>
                        </div>
      
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: #ffffff; border-radius: 24px; border: 2px dashed rgba(22, 41, 90, 0.2);">
            <p style="font-size: 1.2rem; color: var(--deep-navy); opacity: 0.7;">No official updates published yet.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
function toggleGalleryView(view) {
    const btnCollage = document.getElementById('btn-collage');
    const btnSlideshow = document.getElementById('btn-slideshow');
    const collage = document.getElementById('gallery-collage');
    const slideshow = document.getElementById('gallery-slideshow');

    if (view === 'collage') {
        btnCollage.style.background = 'var(--accent-saffron)';
        btnCollage.style.color = 'var(--primary-navy)';
        btnCollage.style.border = 'none';

        btnSlideshow.style.background = 'transparent';
        btnSlideshow.style.color = 'var(--warm-surface)';
        btnSlideshow.style.border = '2px solid rgba(255,255,255,0.2)';

        collage.style.display = 'grid';
        slideshow.style.display = 'none';
    } else {
        btnSlideshow.style.background = 'var(--accent-saffron)';
        btnSlideshow.style.color = 'var(--primary-navy)';
        btnSlideshow.style.border = 'none';

        btnCollage.style.background = 'transparent';
        btnCollage.style.color = 'var(--warm-surface)';
        btnCollage.style.border = '2px solid rgba(255,255,255,0.2)';

        slideshow.style.display = 'block';
        collage.style.display = 'none';
    }
}

// Initialize GLightbox
document.addEventListener("DOMContentLoaded", function() {
    if(typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            keyboardNavigation: true
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
