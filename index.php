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
$galleryData = [
    'images' => [],
    'hero' => [],
    'albums' => []
];
try {
    $cacheFile = __DIR__ . '/cache/gallery_homepage.json';
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $galleryData['images'] = $cacheData['images'] ?? [];
        $galleryData['hero']   = $cacheData['hero'] ?? [];
        $galleryData['albums'] = $cacheData['albums'] ?? [];
    } else {
        $galleryData['images'] = $pdo->query("
            SELECT gi.*, ga.title AS album_title, ga.slug AS album_slug
            FROM gallery_images gi
            LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
            WHERE gi.status = 'published' AND gi.is_deleted = 0
            ORDER BY gi.sort_order ASC, gi.created_at DESC
            LIMIT 200
        ")->fetchAll();
        $galleryData['hero'] = $pdo->query("
            SELECT * FROM gallery_images
            WHERE status='published' AND is_deleted=0 AND show_in_hero=1
            ORDER BY sort_order ASC LIMIT 5
        ")->fetchAll();
        $galleryData['albums'] = $pdo->query("SELECT * FROM gallery_albums ORDER BY id ASC")->fetchAll();
    }
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
                        <span style="color: #FF9933;">🗓️</span> <?php echo htmlspecialchars($sched['date_text']); ?>
                    </p>
                    <p class="venue" style="font-size: 0.95rem; color: #3b4a6b; font-weight: 500; margin-bottom: 0; display: flex; gap: 0.5rem; align-items: center;">
                        <span>📍</span> <?php echo htmlspecialchars($sched['venue']); ?>
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
<section id="photo-gallery" style="padding: 6rem 0; background: #F8F5EF; color: #081B4B;">
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
    margin: 0 auto 2.5rem;
    line-height: 1.6;
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

/* ── Featured Collection Horizontal Banner ── */
.gal-featured-banner {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    height: 380px;
    margin-bottom: 4rem;
    box-shadow: 0 12px 30px rgba(8, 27, 75, 0.1);
    background: #081B4B;
}
.gal-featured-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center 30%;
    transition: transform 0.8s ease;
}
.gal-featured-banner:hover .gal-featured-bg {
    transform: scale(1.03);
}
.gal-featured-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(8, 27, 75, 0.95) 0%, rgba(8, 27, 75, 0.5) 60%, rgba(8, 27, 75, 0.2) 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 3rem 4.5rem;
    color: #ffffff;
    z-index: 2;
}
.gal-featured-tag {
    color: #FF9933;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-size: 0.8rem;
    margin-bottom: 0.75rem;
}
.gal-featured-title {
    font-family: var(--font-heading);
    font-size: clamp(1.8rem, 3vw, 2.6rem);
    font-weight: 800;
    margin-bottom: 0.5rem;
    max-width: 600px;
    line-height: 1.25;
}
.gal-featured-count {
    font-size: 0.95rem;
    opacity: 0.85;
    margin-bottom: 1.5rem;
}
.gal-featured-btn {
    background: #FF9933;
    color: #081B4B;
    border: none;
    border-radius: 999px;
    padding: 0.75rem 2rem;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.25s ease;
}
.gal-featured-btn:hover {
    background: #ffffff;
    color: #081B4B;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 153, 51, 0.4);
}

/* ── Masonry Grid ── */
.gal-section-title {
    font-family: var(--font-heading);
    font-size: 1.75rem;
    font-weight: 800;
    color: #081B4B;
    margin: 0 0 2rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.gal-section-title span {
    color: #FF9933;
}
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
    box-shadow: 0 6px 18px rgba(8, 27, 75, 0.08);
    cursor: pointer;
    background: #ffffff;
    border: 1px solid rgba(8, 27, 75, 0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.gal-photo-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(8, 27, 75, 0.15);
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
    .gal-featured-overlay {
        padding: 2rem;
    }
    .gal-photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }
}
</style>

<div class="gallery-wide-container">

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

    <?php
    $allGalImages = $galleryData['images'];
    $galAlbums    = $galleryData['albums'];
    
    // Fetch only active albums (albums containing at least 1 image) to prevent empty categories
    $activeAlbums = [];
    try {
        $activeAlbums = $pdo->query("
            SELECT ga.*, COUNT(gi.id) AS image_count,
                   COALESCE(ga.cover_image, (SELECT image_path FROM gallery_images WHERE album_id = ga.id AND status = 'published' AND is_deleted = 0 ORDER BY sort_order ASC, id ASC LIMIT 1)) AS final_cover
            FROM gallery_albums ga
            INNER JOIN gallery_images gi ON ga.id = gi.album_id AND gi.status = 'published' AND gi.is_deleted = 0
            GROUP BY ga.id
            HAVING image_count > 0
            ORDER BY ga.id ASC
        ")->fetchAll();
    } catch (PDOException $e) {
        $activeAlbums = [];
    }

    // Set up Featured Collection Banner details (default to National Championships if available)
    $featuredAlbum = null;
    foreach ($activeAlbums as $alb) {
        if ($alb['slug'] === 'national-championships') {
            $featuredAlbum = $alb;
            break;
        }
    }
    if (!$featuredAlbum && !empty($activeAlbums)) {
        $featuredAlbum = $activeAlbums[0];
    }
    ?>

    <!-- Category Filters (Hiding categories with zero photos) -->
    <div class="gal-filters-wrap">
        <button class="gal-filter-btn active" data-filter="all">All</button>
        <?php foreach ($activeAlbums as $alb): 
            $shortTitle = $alb['title'];
            if ($alb['slug'] === 'national-championships') $shortTitle = 'National';
            elseif ($alb['slug'] === 'international-events') $shortTitle = 'International';
            elseif ($alb['slug'] === 'training-camps') $shortTitle = 'Camps';
            elseif ($alb['slug'] === 'athlete-development') $shortTitle = 'Development';
            elseif ($alb['slug'] === 'general') $shortTitle = 'General';
        ?>
        <button class="gal-filter-btn" data-filter="<?php echo htmlspecialchars($alb['slug']); ?>"><?php echo htmlspecialchars($shortTitle); ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Featured Collection (Horizontal Banner) -->
    <?php if ($featuredAlbum): 
        $featCover = !empty($featuredAlbum['final_cover']) ? htmlspecialchars($featuredAlbum['final_cover']) : 'assets/images/bsfi-placeholder.webp';
    ?>
    <div class="gal-featured-banner" id="galFeaturedBanner">
        <div class="gal-featured-bg" style="background-image: url('<?php echo $featCover; ?>');"></div>
        <div class="gal-featured-overlay">
            <span class="gal-featured-tag">★ Featured Collection</span>
            <h3 class="gal-featured-title"><?php echo htmlspecialchars($featuredAlbum['title']); ?></h3>
            <span class="gal-featured-count"><?php echo (int)$featuredAlbum['image_count']; ?> Photos</span>
            <button class="gal-featured-btn" onclick="activateAlbumFilter('<?php echo htmlspecialchars($featuredAlbum['slug']); ?>')">View Collection →</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Masonry Photo Gallery Title -->
    <div class="gal-section-header-row">
        <h3 class="gal-section-title" id="galSectionTitle">All Photos</h3>
    </div>

    <!-- Masonry Gallery -->
    <div class="gal-photos-grid" id="mainPhotosGrid">
        <?php 
        foreach ($allGalImages as $img): 
            $imgUrl = htmlspecialchars($img['image_path']);
            $thumbUrl = htmlspecialchars($img['thumbnail_path'] ?: $img['image_path']);
            $caption = htmlspecialchars($img['caption'] ?? 'BSFI Gallery Photo');
            $credit = htmlspecialchars($img['credit'] ?? '');
            $albSlug = htmlspecialchars($img['album_slug'] ?? 'general');
        ?>
        <a href="<?php echo $imgUrl; ?>" class="glightbox gal-photo-item" data-album-slug="<?php echo $albSlug; ?>" data-title="<?php echo $caption; ?>" data-description="<?php echo $credit; ?>">
            <img src="<?php echo $thumbUrl; ?>" alt="<?php echo $caption; ?>" loading="lazy">
            <div class="gal-photo-overlay">
                <p class="gal-photo-caption"><?php echo $caption; ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<script>
(function() {
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

    // Function to filter photos in the masonry grid
    window.filterMasonry = function(filterSlug) {
        const photos = document.querySelectorAll('.gal-photo-item');
        const sectionTitle = document.getElementById('galSectionTitle');
        const banner = document.getElementById('galFeaturedBanner');

        // Toggle photos visibility
        photos.forEach(photo => {
            const photoSlug = photo.getAttribute('data-album-slug');
            if (filterSlug === 'all' || photoSlug === filterSlug) {
                photo.classList.remove('gal-hidden');
            } else {
                photo.classList.add('gal-hidden');
            }
        });

        // Hide Featured Banner if we're filtering specifically
        if (banner) {
            if (filterSlug === 'all') {
                banner.style.display = 'block';
                sectionTitle.textContent = 'All Photos';
            } else {
                banner.style.display = 'none';
                // Find matching filter title
                const activeBtn = document.querySelector(`.gal-filter-btn[data-filter="${filterSlug}"]`);
                const titleText = activeBtn ? activeBtn.textContent : 'Collection';
                sectionTitle.textContent = `${titleText} Photos`;
            }
        }

        // Re-initialize Lightbox with visible elements only
        initLightbox();
    };

    // Category Filter Button clicks
    const filterButtons = document.querySelectorAll('.gal-filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');
            filterMasonry(filter);
        });
    });

    // View Collection Banner Click Helper
    window.activateAlbumFilter = function(slug) {
        const matchingBtn = document.querySelector(`.gal-filter-btn[data-filter="${slug}"]`);
        if (matchingBtn) {
            matchingBtn.click();
            document.getElementById('photo-gallery').scrollIntoView({ behavior: 'smooth' });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        initLightbox();
    });

})();
</script>

<!-- Latest News Section -->
<section id="news" class="news-section" style="padding: 6rem 0; background: var(--warm-surface) url('news%20bg.png') top center / cover fixed no-repeat;">
    <div id="official-federation-updates" style="scroll-margin-top: 120px;"></div>
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
