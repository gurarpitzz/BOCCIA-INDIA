<?php
// index.php - Public web portal for Boccia Sports Federation of India

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Boccia Sports Federation of India | Official Portal";
include __DIR__ . '/includes/header.php';

$message = '';

// Fetch States lookup
$statesList = [];
try {
    $statesStmt = $pdo->query("SELECT id, name, type FROM states WHERE active = 1 ORDER BY name ASC");
    $statesList = $statesStmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch State Associations lookup
$associationsList = [];
try {
    $assocStmt = $pdo->query("SELECT sa.id, sa.state_id, sa.association_name, s.name as state_name FROM state_associations sa JOIN states s ON sa.state_id = s.id WHERE sa.active = 1");
    $associationsList = $assocStmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch Active Schedules
$publicSchedules = [];
try {
    $schedStmt = $pdo->query("SELECT * FROM schedules WHERE active = 1 ORDER BY sort_order ASC, id ASC");
    $publicSchedules = $schedStmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch Latest News (1 Featured, up to 3 standard)
$featuredNews = null;
$standardNews = [];
try {
    // Try to get a featured article
    $newsStmt = $pdo->query("SELECT * FROM news WHERE status = 'published' AND featured = 1 ORDER BY published_at DESC LIMIT 1");
    $featuredNews = $newsStmt->fetch();

    // Fallback: If no featured article, use the latest published one
    if (!$featuredNews) {
        $newsStmt = $pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 1");
        $featuredNews = $newsStmt->fetch();
    }

    if ($featuredNews) {
        // Fetch up to 3 more
        $newsStmt = $pdo->prepare("SELECT * FROM news WHERE status = 'published' AND id != ? ORDER BY published_at DESC LIMIT 3");
        $newsStmt->execute([$featuredNews['id']]);
        $standardNews = $newsStmt->fetchAll();
    } else {
        $newsStmt = $pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 3");
        $standardNews = $newsStmt->fetchAll();
    }
} catch (PDOException $e) {}

// Fetch Gallery Images
$galleryImages = [];
try {
    $galStmt = $pdo->query("SELECT * FROM gallery_images WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $galleryImages = $galStmt->fetchAll();
} catch (PDOException $e) {}

// Handle Public Athlete Registration Form Submission
if (isset($_POST['register_athlete'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger' style='margin-top: 1rem;'>Invalid security token.</div>";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? 'MALE');
        $dob = trim($_POST['dob'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $state_id = (int)($_POST['state_id'] ?? 0);
        $state_association_id = (int)($_POST['state_association_id'] ?? 0);
        $district = trim($_POST['district'] ?? '');
        $classification = trim($_POST['classification'] ?? 'BC1');
        $regn_no = 'BSFI-P-' . mt_rand(1000, 9999) . '-' . date('Y');

        // Verify state
        $checkState = $pdo->prepare("SELECT name FROM states WHERE id = ? AND active = 1");
        $checkState->execute([$state_id]);
        $stateRow = $checkState->fetch();

        // Verify geography constraint: Association must match chosen State
        $checkAssoc = $pdo->prepare("SELECT id FROM state_associations WHERE id = ? AND state_id = ? AND active = 1");
        $checkAssoc->execute([$state_association_id, $state_id]);
        $assocRow = $checkAssoc->fetch();

        if (empty($full_name) || empty($dob) || !$stateRow) {
            $message = "<div class='alert alert-danger' style='margin-top: 1rem;'>Please fill in all required fields.</div>";
        } elseif ($state_association_id > 0 && !$assocRow) {
            $message = "<div class='alert alert-danger' style='margin-top: 1rem;'>Geographical Boundary Error: Selected State Association does not match the chosen State.</div>";
        } else {
            try {
                $state_name = $stateRow['name'];
                $stmt = $pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, mobile, email, state, district, classification, representing_for, state_association_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$regn_no, $full_name, $gender, $dob, $mobile, $email, $state_name, $district, $classification, $state_name, $state_association_id > 0 ? $state_association_id : null]);
                $newId = $pdo->lastInsertId();
                
                // Write audit log
                logAction($pdo, "Public Athlete Registration Requested", "athletes", $newId, "Name: $full_name");
                
                $message = "<div class='alert alert-success' style='margin-top: 1rem; border-radius:12px;'><strong>Registration Submitted Successfully!</strong> Your request is pending review by the BSFI administration. Reference ID: $regn_no</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger' style='margin-top: 1rem;'>Submission failure: " . $e->getMessage() . "</div>";
            }
        }
    }
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
        <div class="map-two-col">

            <!-- LEFT: Map confined card -->
            <div class="map-bordered-card">
                <!-- Green accent bar at top -->
                <div class="map-card-accent-bar"></div>

                <!-- SVG Map (no extra headings inside the card) -->
                <?php include __DIR__ . '/includes/india-map.php'; ?>
            </div>

            <!-- RIGHT: Content panel -->
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
<section id="schedules" class="schedules-section" style="padding: 6rem 0; background: #FAF7F0;">

    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-green);"></span>
                <span style="color: var(--accent-green); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-green);"></span>
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

<!-- Discover Boccia Hub Section -->
<section id="discover" class="discover-section">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Discover Boccia</span>
            <h2 class="section-title">Educational Resources & Rules</h2>
        </div>
        
        <div class="discover-grid">
            <div class="glass-card">
                <h3 style="font-family:var(--font-heading); margin-bottom:1rem;">What is Boccia?</h3>
                <p style="font-size:0.95rem; opacity:0.8; margin-bottom:1.5rem;">Boccia is a precision ball sport designed specifically for athletes with high support needs. The goal is simple: roll, throw, or kick leather balls as close to the target white ball (the Jack) as possible.</p>
                <a href="#about" class="btn btn-hero-secondary">Read Guides</a>
            </div>
            
            <div class="glass-card">
                <h3 style="font-family:var(--font-heading); margin-bottom:1rem;">Classification System</h3>
                <p style="font-size:0.95rem; opacity:0.8; margin-bottom:1.5rem;">Athletes compete in specific divisions based on mobility profiles. These are BC1 (hand/foot throwers with assistants), BC2 (independent throwers), BC3 (ramp operators using pointer equipment), and BC4 (non-cerebral throwers).</p>
                <a href="#about" class="btn btn-hero-secondary">Class Guide</a>
            </div>

            <div class="glass-card">
                <h3 style="font-family:var(--font-heading); margin-bottom:1rem;">Equipment & Scoring</h3>
                <p style="font-size:0.95rem; opacity:0.8; margin-bottom:1.5rem;">Boccia sets contain 6 red, 6 blue, and 1 white Jack ball. Scoring takes place at the end of each 'end' of play, awarding points for each ball of the winning color that is closer to the Jack than the opponent's closest ball.</p>
                <a href="#about" class="btn btn-hero-secondary">Rules Sheet</a>
            </div>
        </div>
    </div>
</section>

<!-- Latest News Section -->
<section id="news" class="news-section" style="padding: 6rem 0; background: #FAF7F0;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 4rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="height: 1px; width: 40px; background: var(--accent-green);"></span>
                <span style="color: var(--accent-green); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">BOCCIA INDIA 2026</span>
                <span style="height: 1px; width: 40px; background: var(--accent-green);"></span>
            </div>
            <h2 class="section-title" style="color: var(--deep-navy); font-size: 2.8rem;">Latest News</h2>
        </div>

        <?php if ($featuredNews): ?>
        <!-- Featured News -->
        <div class="glass-card featured-news" style="display:grid; grid-template-columns:1.5fr 1fr; gap:0; border-radius:24px; overflow:hidden; margin-bottom: 2rem; background:#fff; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
            <div style="background:#000; position:relative; min-height: 300px;">
                <?php if(!empty($featuredNews['image'])): ?>
                    <img src="<?php echo htmlspecialchars($featuredNews['image']); ?>" alt="Featured News" style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;">
                <?php else: ?>
                    <div style="width:100%; height:100%; background:linear-gradient(135deg, var(--deep-navy), #1E88E5); display:flex; align-items:center; justify-content:center; color:#fff; font-size:4rem; position:absolute; top:0; left:0;">📰</div>
                <?php endif; ?>
            </div>
            <div style="padding:3rem; display:flex; flex-direction:column; justify-content:center;">
                <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                    <span style="background:rgba(244, 185, 66, 0.2); color:#D72638; font-weight:bold; padding:0.25rem 0.75rem; border-radius:999px; font-size:0.8rem; text-transform:uppercase;">Featured</span>
                    <span style="color:var(--deep-navy); opacity:0.6; font-size:0.85rem; font-weight:600;"><?php echo date('F j, Y', strtotime($featuredNews['published_at'])); ?></span>
                </div>
                <h3 style="font-family:var(--font-heading); font-size:2rem; font-weight:800; color:var(--deep-navy); margin-bottom:1rem; line-height:1.2;">
                    <?php echo htmlspecialchars($featuredNews['title']); ?>
                </h3>
                <p style="color:var(--deep-navy); opacity:0.8; font-size:1.05rem; margin-bottom:2rem; line-height:1.6; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                    <?php echo htmlspecialchars($featuredNews['excerpt'] ?: strip_tags($featuredNews['content'])); ?>
                </p>
                <a href="#news-<?php echo htmlspecialchars($featuredNews['slug']); ?>" class="btn btn-hero-primary" style="align-self:flex-start; font-weight:bold; letter-spacing:0.05em;">Read More &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($standardNews) > 0): ?>
        <!-- Standard News Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:2rem;">
            <?php foreach ($standardNews as $news): ?>
            <div class="glass-card" style="background:#fff; border-radius:20px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 5px 20px rgba(0,0,0,0.03); transition:transform 0.3s ease;">
                <div style="height:200px; background:#f0f0f0; position:relative;">
                    <?php if(!empty($news['image'])): ?>
                        <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="News" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:linear-gradient(135deg, #1E88E5, var(--deep-navy)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem; opacity:0.5;">📰</div>
                    <?php endif; ?>
                </div>
                <div style="padding:1.5rem; display:flex; flex-direction:column; flex-grow:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <span style="color:var(--accent-green); font-weight:bold; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;"><?php echo htmlspecialchars($news['category']); ?></span>
                        <span style="color:var(--deep-navy); opacity:0.5; font-size:0.8rem; font-weight:600;"><?php echo date('M j, Y', strtotime($news['published_at'])); ?></span>
                    </div>
                    <h4 style="font-family:var(--font-heading); font-size:1.25rem; font-weight:700; color:var(--deep-navy); margin-bottom:0.75rem; line-height:1.3;">
                        <?php echo htmlspecialchars($news['title']); ?>
                    </h4>
                    <p style="color:var(--deep-navy); opacity:0.7; font-size:0.9rem; margin-bottom:1.5rem; flex-grow:1; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                        <?php echo htmlspecialchars($news['excerpt'] ?: strip_tags($news['content'])); ?>
                    </p>
                    <a href="#news-<?php echo htmlspecialchars($news['slug']); ?>" style="color:var(--accent-green); font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; font-size:0.95rem; margin-top:auto;">
                        Read More <span style="font-size:1.2em;">&rarr;</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Latest Circulars & Downloads Section -->
<section id="downloads" style="padding: 5rem 0; background: linear-gradient(135deg, #FAF7F0, #EAE5D9);">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:3rem; flex-wrap:wrap; gap:1.5rem;">
            <div>
                <span style="color:var(--accent-green); font-weight:700; letter-spacing:0.1em; text-transform:uppercase; font-size:0.9rem;">Official Documents</span>
                <h2 style="color:var(--deep-navy); font-family:var(--font-heading); font-size:2.4rem; font-weight:800; margin-top:0.5rem;">Latest Circulars & Downloads</h2>
            </div>
            <a href="#" class="btn btn-hero-secondary" style="border-radius:999px;">View All Documents</a>
        </div>
        
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
            <!-- Mock PDFs -->
            <a href="#" class="glass-card" style="background:#fff; border-radius:16px; padding:1.5rem; text-decoration:none; display:flex; align-items:center; gap:1.25rem; box-shadow:0 4px 15px rgba(0,0,0,0.03); transition:transform 0.2s ease;">
                <div style="width:50px; height:50px; background:rgba(215, 38, 56, 0.1); color:#D72638; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">📄</div>
                <div>
                    <h5 style="color:var(--deep-navy); margin-bottom:0.25rem; font-family:var(--font-heading); font-weight:700; font-size:1rem;">National Selection Criteria 2026</h5>
                    <p style="color:var(--deep-navy); opacity:0.6; margin:0; font-size:0.8rem; font-weight:600;">PDF (1.2 MB) &bull; Updated Mar 1</p>
                </div>
            </a>
            <a href="#" class="glass-card" style="background:#fff; border-radius:16px; padding:1.5rem; text-decoration:none; display:flex; align-items:center; gap:1.25rem; box-shadow:0 4px 15px rgba(0,0,0,0.03); transition:transform 0.2s ease;">
                <div style="width:50px; height:50px; background:rgba(30, 136, 229, 0.1); color:#1E88E5; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">📄</div>
                <div>
                    <h5 style="color:var(--deep-navy); margin-bottom:0.25rem; font-family:var(--font-heading); font-weight:700; font-size:1rem;">Tournament Handbook</h5>
                    <p style="color:var(--deep-navy); opacity:0.6; margin:0; font-size:0.8rem; font-weight:600;">PDF (3.4 MB) &bull; Updated Feb 28</p>
                </div>
            </a>
            <a href="#" class="glass-card" style="background:#fff; border-radius:16px; padding:1.5rem; text-decoration:none; display:flex; align-items:center; gap:1.25rem; box-shadow:0 4px 15px rgba(0,0,0,0.03); transition:transform 0.2s ease;">
                <div style="width:50px; height:50px; background:rgba(36, 194, 122, 0.1); color:#24C27A; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">📄</div>
                <div>
                    <h5 style="color:var(--deep-navy); margin-bottom:0.25rem; font-family:var(--font-heading); font-weight:700; font-size:1rem;">Medical Classification Form</h5>
                    <p style="color:var(--deep-navy); opacity:0.6; margin:0; font-size:0.8rem; font-weight:600;">PDF (0.8 MB) &bull; Updated Feb 15</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Photo Gallery Section -->
<section id="gallery" style="padding: 6rem 0; background: linear-gradient(to bottom, #1a2f5e, var(--deep-navy)); color: #FAF7F0;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
            <h2 class="section-title" style="color: #FAF7F0; font-size: 2.8rem; text-shadow:0 2px 10px rgba(0,0,0,0.3);">Gallery View</h2>
            <div class="gallery-toggles" style="margin-top: 2rem; display: flex; justify-content: center; gap: 1rem;">
                <button id="btn-collage" class="btn active-pill" onclick="toggleGalleryView('collage')" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; padding:0.6rem 1.5rem; border:none;">▣ Collage</button>
                <button id="btn-slideshow" class="btn inactive-pill" onclick="toggleGalleryView('slideshow')" style="background:transparent; border:2px solid rgba(255,255,255,0.2); color:#FAF7F0; font-weight:bold; border-radius:999px; padding:0.6rem 1.5rem; transition:all 0.3s;">▶ Slideshow</button>
            </div>
        </div>

        <?php if(count($galleryImages) > 0): ?>
            <!-- Collage Masonry -->
            <div id="gallery-collage" class="gallery-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px; align-items:start;">
                <?php foreach($galleryImages as $img): ?>
                    <a href="<?php echo htmlspecialchars($img['image_path']); ?>" class="glightbox" data-gallery="collage" data-title="<?php echo htmlspecialchars($img['title']); ?>" data-description="<?php echo htmlspecialchars($img['event_name'] ?? ''); ?>" style="display:block; overflow:hidden; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.2);">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" style="width:100%; height:auto; display:block; transition:transform 0.4s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Slideshow Carousel -->
            <div id="gallery-slideshow" style="display:none; max-width:900px; margin:0 auto;">
                <div id="carouselGallery" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000" data-bs-touch="true" data-bs-keyboard="true">
                    <div class="carousel-inner" style="border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
                        <?php 
                        $slideshowImages = array_filter($galleryImages, function($i) { return isset($i['featured']) && $i['featured'] == 1; });
                        if(empty($slideshowImages)) $slideshowImages = $galleryImages; // Fallback to all if none featured
                        $first = true;
                        foreach($slideshowImages as $img): 
                        ?>
                            <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($img['title']); ?>" style="aspect-ratio:16/9; object-fit:cover;">
                                <div class="carousel-caption d-none d-md-block" style="background:rgba(0,0,0,0.6); border-radius:12px; padding:1rem; bottom:20px;">
                                    <h5 style="margin:0; font-family:var(--font-heading); font-weight:700;"><?php echo htmlspecialchars($img['title']); ?></h5>
                                    <?php if(isset($img['event_name']) && $img['event_name']): ?>
                                        <p style="margin:0; font-size:0.9rem; opacity:0.8;"><?php echo htmlspecialchars($img['event_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php $first = false; endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5); padding:20px; border-radius:50%;"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5); padding:20px; border-radius:50%;"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:4rem; background:rgba(0,0,0,0.2); border-radius:24px;">
                <p style="font-size:1.2rem; opacity:0.6;">Photo gallery is currently being updated.</p>
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
        btnCollage.style.background = '#24C27A';
        btnCollage.style.color = '#08142E';
        btnCollage.style.border = 'none';

        btnSlideshow.style.background = 'transparent';
        btnSlideshow.style.color = '#FAF7F0';
        btnSlideshow.style.border = '2px solid rgba(255,255,255,0.2)';

        collage.style.display = 'grid';
        slideshow.style.display = 'none';
    } else {
        btnSlideshow.style.background = '#24C27A';
        btnSlideshow.style.color = '#08142E';
        btnSlideshow.style.border = 'none';

        btnCollage.style.background = 'transparent';
        btnCollage.style.color = '#FAF7F0';
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

<!-- Athlete Registration Wizard Section -->
<section id="register" class="reg-wizard-wrapper">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">National Registry</span>
            <h2 class="section-title">Register Athlete</h2>
            <p style="max-width:600px; margin:1rem auto 0 auto; opacity:0.75;">Submit an application to register as a competitive Para Boccia athlete. BSFI reviews all entries before adding them to the public active list.</p>
        </div>

        <?php echo $message; ?>

        <!-- Wizard Node Progress Tracker -->
        <div class="wizard-steps">
            <div class="wizard-step-node active">1</div>
            <div class="wizard-step-node">2</div>
            <div class="wizard-step-node">3</div>
            <div class="wizard-step-node">4</div>
            <div class="wizard-step-node">5</div>
        </div>

        <!-- Multi-step Form -->
        <div class="glass-card wizard-form-container">
            <form action="index.php#register" method="POST" id="public-reg-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- Step 1: Personal Info -->
                <div class="wizard-step-block" id="step-1">
                    <h3 style="margin-bottom:1.5rem; font-family:var(--font-heading);">Step 1: Personal Information</h3>
                    <div class="form-group">
                        <label for="reg-name">Full Name *</label>
                        <input type="text" name="full_name" id="reg-name" class="form-input" required placeholder="Enter full name">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label for="reg-gender">Gender *</label>
                            <select name="gender" id="reg-gender" class="select-input" required>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reg-dob">Date of Birth *</label>
                            <input type="date" name="dob" id="reg-dob" class="form-input" required>
                        </div>
                    </div>
                    <button type="button" class="btn" style="background:var(--accent-green); color:var(--deep-navy); margin-top:1.5rem;" onclick="navigateWizard('next')">Next Step</button>
                </div>

                <!-- Step 2: Classification -->
                <div class="wizard-step-block" id="step-2" style="display:none;">
                    <h3 style="margin-bottom:1.5rem; font-family:var(--font-heading);">Step 2: Classification</h3>
                    <div class="form-group">
                        <label for="reg-classification">Boccia Division Class *</label>
                        <select name="classification" id="reg-classification" class="select-input" required>
                            <option value="BC1">BC1 (Assist allowed)</option>
                            <option value="BC2">BC2 (Independent thrower)</option>
                            <option value="BC3">BC3 (Ramp Operator/Pointer)</option>
                            <option value="BC4">BC4 (Independent Non-Cerebral)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reg-wheelchair">Wheelchair Status / Mobility Device</label>
                        <input type="text" name="wheelchair_status" id="reg-wheelchair" class="form-input" placeholder="e.g. Manual wheelchair, Power chair">
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="button" class="btn" style="border:1px solid rgba(0,0,0,0.15); color:var(--text-dark);" onclick="navigateWizard('prev')">Back</button>
                        <button type="button" class="btn" style="background:var(--accent-green); color:var(--deep-navy);" onclick="navigateWizard('next')">Next Step</button>
                    </div>
                </div>

                <!-- Step 3: State Association -->
                <div class="wizard-step-block" id="step-3" style="display:none;">
                    <h3 style="margin-bottom:1.5rem; font-family:var(--font-heading);">Step 3: State Association</h3>
                    <div class="form-group">
                        <label for="reg-state">Representing State *</label>
                        <select name="state_id" id="reg-state" class="select-input" required onchange="filterAssociations()">
                            <option value="">Select State / UT</option>
                            <?php foreach ($statesList as $st): ?>
                                <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reg-association">State Association *</label>
                        <select name="state_association_id" id="reg-association" class="select-input" required>
                            <option value="">Select Association (Requires State First)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reg-district">District</label>
                        <input type="text" name="district" id="reg-district" class="form-input" placeholder="e.g. Pune, Chennai">
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="button" class="btn" style="border:1px solid rgba(0,0,0,0.15); color:var(--text-dark);" onclick="navigateWizard('prev')">Back</button>
                        <button type="button" class="btn" style="background:var(--accent-green); color:var(--deep-navy);" onclick="navigateWizard('next')">Next Step</button>
                    </div>
                </div>

                <!-- Step 4: Documents Upload -->
                <div class="wizard-step-block" id="step-4" style="display:none;">
                    <h3 style="margin-bottom:1.5rem; font-family:var(--font-heading);">Step 4: Contact Information</h3>
                    <div class="form-group">
                        <label for="reg-email">Email Address</label>
                        <input type="email" name="email" id="reg-email" class="form-input" placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label for="reg-mobile">Mobile Number</label>
                        <input type="text" name="mobile" id="reg-mobile" class="form-input" placeholder="Contact number">
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="button" class="btn" style="border:1px solid rgba(0,0,0,0.15); color:var(--text-dark);" onclick="navigateWizard('prev')">Back</button>
                        <button type="button" class="btn" style="background:var(--accent-green); color:var(--deep-navy);" onclick="navigateWizard('next')">Next Step</button>
                    </div>
                </div>

                <!-- Step 5: Review & Submit -->
                <div class="wizard-step-block" id="step-5" style="display:none;">
                    <h3 style="margin-bottom:1.5rem; font-family:var(--font-heading);">Step 5: Review & Submit</h3>
                    <p style="opacity:0.8; margin-bottom:1.5rem;">Please review your athlete registration details before submitting. Once registered, a representative will contact you for trial verification.</p>
                    
                    <div id="reg-summary" style="background:rgba(0,0,0,0.03); padding:1.5rem; border-radius:12px; margin-bottom:2rem; line-height:1.8;">
                        <!-- JS inject summary details here -->
                    </div>

                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="button" class="btn" style="border:1px solid rgba(0,0,0,0.15); color:var(--text-dark);" onclick="navigateWizard('prev')">Back</button>
                        <button type="submit" name="register_athlete" class="btn" style="background:var(--accent-green); color:var(--deep-navy); font-weight:bold;">Submit Registry Request</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</section>

<script>
    // Preload state associations to filter on state dropdown change
    window.associationsData = <?php echo json_encode($associationsList); ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
