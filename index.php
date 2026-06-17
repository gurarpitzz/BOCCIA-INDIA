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

// Fetch Gallery Images
$galleryImages = [];
try {
    $galStmt = $pdo->query("SELECT * FROM gallery_images WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $galleryImages = $galStmt->fetchAll();
} catch (PDOException $e) {}

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
        <h1 class="hero-quote-text"><span class="hero-quote-accent">"</span>I didn't know there was a sport for me until I found Boccia<span class="hero-quote-accent">"</span></h1>
        <p class="hero-subtitle">We are proud to have widened the reach of Boccia in India — from those with severe physical disabilities, including Cerebral Palsy. We hope every Indian Boccia athlete achieves their targets.</p>
        <div class="hero-btns">
            <a href="#register" class="btn btn-hero-primary">Player Registration &rarr;</a>
            <a href="#discover" class="btn btn-hero-secondary">Explore Boccia</a>
        </div>
    </div>

    <!-- Slide Navigation Dots -->
    <div class="slide-dots" id="slideDots">
        <?php foreach ($slides as $i => $img): ?>
        <button class="slide-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Slide <?php echo $i+1; ?>"></button>
        <?php endforeach; ?>
    </div>

    <!-- Prev / Next arrows -->
    <button class="slide-arrow slide-prev" id="slidePrev" aria-label="Previous slide">&#8249;</button>
    <button class="slide-arrow slide-next" id="slideNext" aria-label="Next slide">&#8250;</button>
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
    function prev() { goTo(current - 1); }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(next, 5000);
    }

    document.getElementById('slideNext').addEventListener('click', () => { next(); startTimer(); });
    document.getElementById('slidePrev').addEventListener('click', () => { prev(); startTimer(); });
    dots.forEach(dot => dot.addEventListener('click', () => { goTo(+dot.dataset.index); startTimer(); }));

    startTimer();
})();
</script>

<!-- ══════════════════════════════════════════════════════
     ONE SHARED bg.png wrapper — map + stats bar together
═══════════════════════════════════════════════════════ -->
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

                    <!-- SVG Map (no extra headings inside the card) -->
                    <?php include __DIR__ . '/includes/india-map.php'; ?>
                </div>
            </div>

            <!-- RIGHT: Content panel -->
            <div class="col-lg-6">
                <div class="map-content-panel">
                    <span class="map-eyebrow">National Footprint</span>
                    <h2 class="map-main-title">State Participation<br>&amp; Associations</h2>
                    <p class="map-desc">Click on any state on the interactive map to view athlete registration counts, classification splits, and state association details.</p>

                    <!-- Dynamic detail card (populated by app.js on click) -->
                    <div id="map-details-card" class="map-detail-box">
                        <h4 class="map-detail-heading">Select a State</h4>
                        <p class="map-detail-body">Click on any of the active states on the map to load association statistics, athlete representation, and active registries.</p>
                        <span class="map-detail-badge">● National Registry System</span>
                    </div>

                    <!-- Athlete Density Legend — moved here from map card -->
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

<!-- ═══════════════════════════════════════════
     DYNAMIC STATS BAR
════════════════════════════════════════════ -->
<section class="stats-bar-section" id="stats-bar">
    <div class="container">
        <p class="stats-bar-eyebrow">BSFI in Numbers</p>
        <div class="stats-bar-pill">

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['total_athletes']; ?>" data-suffix="+">0+</span>
                <span class="stat-label">Registered Athletes</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['states_active']; ?>" data-suffix="+">0+</span>
                <span class="stat-label">States Represented</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['associations']; ?>" data-suffix="+">0+</span>
                <span class="stat-label">State Associations</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['bc_classes']; ?>" data-suffix="">0</span>
                <span class="stat-label">BC Classifications</span>
            </div>
            <div class="stat-divider"></div>

            <div class="stat-item">
                <span class="stat-number" data-target="<?php echo $siteStats['years_active']; ?>" data-suffix="+">0+</span>
                <span class="stat-label">Years Active</span>
            </div>

        </div>
    </div>
</section>

</div><!-- /.map-stats-bg-wrapper -->

<!-- National Schedules Section -->
<section id="schedules" class="schedules-section" style="padding: 6rem 0; background: url('bg schedule.png') center/cover no-repeat;">

    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
                <span style="color: var(--accent-saffron); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
            </div>
            <h2 class="section-title" style="color: var(--deep-navy); font-size: 2.8rem;">Schedule</h2>
        </div>

        <?php if (count($publicSchedules) > 0): ?>
        <div class="schedule-table-wrapper" style="overflow-x: auto; padding-bottom: 2rem;">
            <div class="schedule-table" style="min-width: 800px;">
                <!-- Header -->
                <div class="schedule-header" style="background: var(--deep-navy); color: #fff; border-radius: 999px; display: grid; grid-template-columns: 80px 2fr 1.5fr 2.5fr; padding: 1.25rem 2rem; font-weight: 700; font-family: var(--font-heading); margin-bottom: 1.5rem;">
                    <div>S.No.</div>
                    <div>Discipline / Event</div>
                    <div>Date</div>
                    <div>Venue</div>
                </div>

                <!-- Rows -->
                <div class="schedule-body" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                    $rowIdx = 1;
                    foreach ($publicSchedules as $sched): 
                        // Alternating colors
                        $bgColor = ($rowIdx % 2 !== 0) ? '#EDE9FF' : '#DCE8F8';
                    ?>
                    <div class="schedule-row" style="background: <?php echo $bgColor; ?>; border-radius: 999px; display: grid; grid-template-columns: 80px 2fr 1.5fr 2.5fr; padding: 1.25rem 2rem; align-items: center; transition: all 0.25s ease; color: var(--deep-navy);">
                        <div style="font-weight: 600; font-size: 0.95rem;"><?php echo $rowIdx; ?>.</div>
                        <div>
                            <div style="font-weight: 600; font-size: 1rem;"><?php echo htmlspecialchars($sched['discipline']); ?></div>
                            <?php if ($sched['event_type']): ?>
                            <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 0.2rem;"><?php echo htmlspecialchars($sched['event_type']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.95rem;"><?php echo htmlspecialchars($sched['date_text']); ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                            <span><?php echo htmlspecialchars($sched['venue']); ?></span>
                            <?php if ($sched['registration_link']): ?>
                            <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" style="background: var(--deep-navy); color: #fff; padding: 0.4rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: bold; text-decoration: none; transition: background 0.2s;">Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $rowIdx++; endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Mobile Cards version (Hidden on desktop, shown on mobile via CSS) -->
        <div class="schedule-mobile-cards">
            <?php foreach ($publicSchedules as $sched): ?>
            <div class="schedule-card">
                <div class="schedule-card-header">
                    <h4 class="discipline"><?php echo htmlspecialchars($sched['discipline']); ?></h4>
                    <?php if ($sched['event_type']): ?>
                        <span class="event-type"><?php echo htmlspecialchars($sched['event_type']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="schedule-card-body">
                    <p class="date"><strong>🗓️</strong> <?php echo htmlspecialchars($sched['date_text']); ?></p>
                    <p class="venue"><strong>📍</strong> <?php echo htmlspecialchars($sched['venue']); ?></p>
                </div>
                <?php if ($sched['registration_link']): ?>
                <div class="schedule-card-footer">
                    <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" class="btn btn-hero-primary" style="width: 100%; text-align: center; padding: 0.75rem;">Register Now</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Photo Gallery Section -->
<section id="gallery" style="padding: 6rem 0; background: #081B4B; color: #F8F5EF;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
            <h2 class="section-title" style="color: #F8F5EF; font-size: 2.8rem; text-shadow:0 2px 10px rgba(0,0,0,0.3);">Photo Gallery</h2>
        </div>

        <?php if(count($galleryImages) > 0): ?>
            <!-- Collage Masonry -->
            <style>
                .gallery-bento {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    grid-auto-rows: 200px;
                    grid-auto-flow: dense;
                    gap: 20px;
                }
                .gallery-bento-item {
                    display: block; overflow: hidden; border-radius: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); position: relative;
                }
                .gallery-bento-item img {
                    width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; display: block;
                }
                /* Bento spans */
                .gallery-bento-item:nth-child(6n+1) { grid-column: span 2; grid-row: span 2; }
                .gallery-bento-item:nth-child(6n+2) { grid-column: span 1; grid-row: span 1; }
                .gallery-bento-item:nth-child(6n+3) { grid-column: span 1; grid-row: span 2; }
                .gallery-bento-item:nth-child(6n+4) { grid-column: span 2; grid-row: span 1; }
                .gallery-bento-item:nth-child(6n+5) { grid-column: span 1; grid-row: span 1; }
                .gallery-bento-item:nth-child(6n+6) { grid-column: span 1; grid-row: span 1; }
                @media (max-width: 768px) {
                    .gallery-bento-item { grid-column: span 1 !important; grid-row: span 1 !important; }
                    .gallery-bento { grid-auto-rows: 200px; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
                }
            </style>
            <div id="gallery-collage" class="gallery-bento">
                <?php foreach($galleryImages as $img): ?>
                    <a href="<?php echo htmlspecialchars($img['image_path']); ?>" class="glightbox gallery-bento-item" data-gallery="collage" data-title="<?php echo htmlspecialchars($img['title']); ?>" data-description="<?php echo htmlspecialchars($img['event_name'] ?? ''); ?>">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" loading="lazy" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:4rem; background:rgba(0,0,0,0.2); border-radius:24px;">
                <p style="font-size:1.2rem; opacity:0.6;">Photo gallery is currently being updated.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Latest News Section -->
<section id="news" class="news-section" style="padding: 6rem 0; background: var(--warm-surface) url('news%20bg.png') top center / cover fixed no-repeat;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
                <span style="color: var(--accent-saffron); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-saffron);"></span>
            </div>
            <h2 class="section-title" style="color: #081B4B; font-size: 2.8rem;">Official Federation Updates</h2>
        </div>

        <?php if (count($allNews) > 0): ?>
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
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--deep-navy); margin-bottom: 0.5rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p style="font-size: 0.95rem; color: #3b5a9a; line-height: 1.5; margin-bottom: 0;">
                            <?php echo $displayContent; ?>
                        </p>
                        <?php if($isLong): ?>
                            <button onclick="alert('Read More feature to be implemented inline/modal')" style="background:none; border:none; color: var(--accent-saffron); font-weight: 700; padding: 0; margin-top: 0.5rem; cursor: pointer; font-size: 0.9rem;">Read More &raquo;</button>
                        <?php endif; ?>
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
