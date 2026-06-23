<?php
// header.php - Main navbar and Accessibility widgets
require_once __DIR__ . '/auth.php';

// If in admin directory, load admin header instead
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    include __DIR__ . '/admin_header.php';
    return;
}
?>
<!DOCTYPE html>
<html lang="en" style="background: var(--warm-surface);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Boccia Sports Federation of India"; ?></title>
    <meta name="description" content="<?php echo isset($meta_desc) ? htmlspecialchars($meta_desc) : "Official portal of Boccia Sports Federation of India (BSFI). Affiliated with Paralympic Committee of India and Boccia International Sports Federation."; ?>">
    <?php if (isset($canonical_url)): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <?php endif; ?>
    <?php if (isset($og_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php
        $script_name = $_SERVER['SCRIPT_NAME'];
        $clean_path = ltrim($script_name, '/');
        $parts = explode('/', $clean_path);
        $depth = count($parts) - 1;
        if ($depth < 0) $depth = 0;
        $relative_prefix = str_repeat('../', $depth);
        $css_path  = $relative_prefix . 'styles.css';
        $logo_path = $relative_prefix;
    ?>
    <!-- Bootstrap 5.3 (Local) -->
    <link rel="stylesheet" href="<?php echo $relative_prefix; ?>assets/vendor/bootstrap/bootstrap.min.css?v=1">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo $css_path; ?>?v=<?php echo time(); ?>">
    <!-- GLightbox (Local) -->
    <link rel="stylesheet" href="<?php echo $relative_prefix; ?>assets/vendor/glightbox/glightbox.min.css?v=1">
    <style>
        /* Prevent white flash: background set from very first frame */
        html, body { background-color: var(--warm-surface) !important; }

        /* Landing page starts invisible, fades in after sweep exits */
        #page-wrapper {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        #page-wrapper.content-ready {
            opacity: 1;
            visibility: visible;
        }
    </style>
    <script>
        try {
            const settings = JSON.parse(localStorage.getItem('bsfiAccessibility'));
            if (settings) {
                if (settings.fontSize) {
                    document.documentElement.style.fontSize = settings.fontSize + 'px';
                }
                const toggle = (cls, cond) => {
                    if (cond) document.documentElement.classList.add(cls);
                };
                toggle('high-contrast', settings.highContrast);
                toggle('reverse-contrast', settings.reverseContrast);
                toggle('grayscale-mode', settings.grayscale);
                toggle('readable-font', settings.readableFont);
                toggle('underline-links', settings.underlineLinks);
                toggle('underline-headers', settings.underlineHeaders);
                toggle('big-cursor-white', settings.bigCursorWhite);
                toggle('big-cursor-black', settings.bigCursorBlack);
                toggle('reduce-motion', settings.reduceMotion);
            }
        } catch(e) {}
    </script>
</head>
<body class="accessibility-target">
<a href="#main-content" class="skip-link">Skip to Main Content</a>

<!-- GSAP Preloader (Local) -->
<script src="<?php echo $relative_prefix; ?>assets/vendor/gsap/gsap.min.js?v=1"></script>
<div id="bsfi-preloader">
    <div id="gsap-preloader-container" style="width: 100%; max-width: 450px; margin: 0 auto; position: relative; aspect-ratio: 16/9;">
        <svg id="boccia-loader-svg" viewBox="0 0 320 180" style="width:100%; height:100%; overflow:visible;" role="status" aria-label="Loading Boccia India">
            
            <!-- Background Text (Fades in at the end) -->
            <g id="text-lockup" opacity="0">
                <text x="160" y="60" font-family="'Impact', 'Arial Black', sans-serif" font-weight="bold" font-size="36" fill="#0D47A1" text-anchor="middle" letter-spacing="4">BOCCIA</text>
                
                <text x="90" y="135" font-family="'Impact', 'Arial Black', sans-serif" font-weight="900" font-size="64" fill="#111111" text-anchor="middle">I</text>
                <text x="122" y="135" font-family="'Impact', 'Arial Black', sans-serif" font-weight="900" font-size="64" fill="#111111" text-anchor="middle">N</text>
                <text x="160" y="135" font-family="'Impact', 'Arial Black', sans-serif" font-weight="900" font-size="64" fill="#111111" text-anchor="middle">D</text>
                <text x="198" y="135" font-family="'Impact', 'Arial Black', sans-serif" font-weight="900" font-size="64" fill="#111111" text-anchor="middle">I</text>
                <text x="230" y="135" font-family="'Impact', 'Arial Black', sans-serif" font-weight="900" font-size="64" fill="#111111" text-anchor="middle">A</text>

                <!-- Tricolour -->
                <path d="M 50 158 Q 160 175 270 158" fill="none" stroke="#FF9933" stroke-width="4" stroke-linecap="round"/>
                <path d="M 50 168 Q 160 185 270 168" fill="none" stroke="#138808" stroke-width="4" stroke-linecap="round"/>
            </g>

            <!-- Ground -->
            <line id="ground-line" x1="20" y1="140" x2="300" y2="140" stroke="#111111" stroke-width="2" stroke-linecap="round" />
            
            <!-- Target Balls -->
            <circle id="blue-ball" cx="135" cy="132" r="8" fill="#0D47A1" />
            <circle id="white-ball" cx="155" cy="132" r="8" fill="#FFFFFF" stroke="#111111" stroke-width="2" />
            
            <!-- Player & Wheelchair -->
            <g id="player-group">
                <!-- Wheelchair -->
                <circle cx="45" cy="127" r="13" fill="none" stroke="#E10600" stroke-width="3" />
                <circle cx="45" cy="127" r="6.5" fill="none" stroke="#111111" stroke-width="2" />
                <circle cx="61.25" cy="136.75" r="3.25" fill="#111111" />
                <path d="M 38.5 114 L 38.5 127 L 58 127 L 61.25 136.75" fill="none" stroke="#111111" stroke-width="2.6" stroke-linejoin="round" />
                <path d="M 38.5 114 L 35.25 101" fill="none" stroke="#111111" stroke-width="2.6" stroke-linecap="round" />
                <!-- Person -->
                <circle cx="41.75" cy="84.75" r="6.5" fill="#111111" />
                <path d="M 41.75 94.5 L 38.5 114 L 48.25 114 L 48.25 123.75" fill="none" stroke="#111111" stroke-width="3.25" stroke-linejoin="round" />
                <!-- Arm -->
                <path id="player-arm" d="M 41.75 94.5 L 54.75 104.25" fill="none" stroke="#111111" stroke-width="3.25" stroke-linecap="round" />
            </g>

            <!-- Red Ball -->
            <circle id="red-ball" cx="55" cy="104" r="8" fill="#E10600" />

        </svg>
    </div>
</div>

<!-- ══ TRICOLOUR SWEEP — outside preloader, full-screen gradient panel ══ -->
<div id="tricolour-sweep"></div>

<!-- Navy hold overlay -->
<div id="navy-hold"></div>

<style>
/* ── Preloader Overlay ── */
#bsfi-preloader {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: #ececec; /* Off-white greyish */
    display: flex;
    align-items: center;
    justify-content: center;
    /* Fade IN on page load */
    opacity: 0;
    animation: preloader-fadein 0.6s ease forwards;
}

@keyframes preloader-fadein {
    to { opacity: 1; }
}

#preloader-video {
    width: 384px;
    max-width: 80vw;
    height: auto;
    border-radius: 0;
    box-shadow: none;
    mix-blend-mode: multiply;
    display: block;
    transition: opacity 0.25s ease; /* Smooth fade-out when video ends */
}

/* ═──────────────────────────────────────
   TRICOLOUR SWEEP
   ─ A perfect square (300vmax) with a 45deg gradient.
   ─ 100% mark is top-right (leading edge = Saffron).
   ─ We translate it from bottom-left to center.
──────────────────────────────────────── */
#tricolour-sweep {
    position: fixed;
    inset: 0;
    z-index: 100001;
    overflow: hidden;
    pointer-events: none;
}

#tricolour-sweep::before {
    content: '';
    position: absolute;
    width:  300vmax;
    height: 300vmax;
    top:  50%;
    left: 50%;
    margin-top: -150vmax;
    margin-left: -150vmax;
    
    /* 45deg points top-right. 0% is bottom-left, 100% is top-right */
    background: linear-gradient(
        45deg,
        #081B4B 0%,   /* Navy tail */
        #081B4B 85%,  
        #138808 85%,  /* Green */
        #138808 92%,  
        #FFFFFF 92%,  /* White */
        #FFFFFF 95%,  
        #FF9933 95%,  /* Saffron leading tip */
        #FF9933 100%  
    );
    
    /* Start with top-right corner off-screen bottom-left */
    transform: translate(-200vmax, 200vmax);
}

#tricolour-sweep.sweeping::before {
    animation: tc-slide 1.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes tc-slide {
    /* End at center: Navy (0-85%) fully covers the screen */
    to { transform: translate(0, 0); }
}

/* Lock scroll while preloader is active */
body.preloader-active { overflow: hidden !important; }

/* Navy full-screen hold (shown after sweep, before page reveal) */
#navy-hold {
    position: fixed;
    inset: 0;
    z-index: 100000;   /* below sweep (100001) but above preloader (99999) */
    background: #081B4B;
    opacity: 0;
    pointer-events: none;
    display: block;
    transition: opacity 0.5s ease;
}
</style>

<script>
(function () {
    /* Lock scroll */
    document.body.classList.add('preloader-active');

    var preloader = document.getElementById('bsfi-preloader');
    var sweep     = document.getElementById('tricolour-sweep');

    var loaderTimeline;
    if (typeof gsap !== 'undefined') {
        loaderTimeline = gsap.timeline({ repeat: -1, repeatDelay: 0.5 });
        
        loaderTimeline.set(["#red-ball", "#blue-ball", "#white-ball"], { x: 0, y: 0 })
          .set("#player-arm", { rotation: 0, svgOrigin: "41.75 94.5" })
          .set(["#player-group", "#red-ball", "#ground-line"], { opacity: 1, scale: 1 })
          .set(["#blue-ball", "#white-ball"], { scale: 1 })
          .set("#text-lockup", { opacity: 0 });

        loaderTimeline.to("#player-arm", { rotation: -40, duration: 0.3, ease: "power1.inOut" })
          .add("throw")
          .to("#red-ball", { x: 65, duration: 0.5, ease: "power1.inOut" }, "throw")
          .to("#red-ball", { y: -15, duration: 0.25, ease: "power1.out" }, "throw")
          .to("#red-ball", { y: 28, duration: 0.25, ease: "power1.in" }, "throw+=0.25")
          .to("#player-arm", { rotation: 20, duration: 0.4, ease: "power2.out" }, "throw")
          
          .add("impact")
          // Red squash and settle
          .to("#red-ball", { scaleY: 0.7, scaleX: 1.2, duration: 0.1, yoyo: true, repeat: 1, transformOrigin: "bottom" }, "impact")
          .to("#red-ball", { x: 75, duration: 0.3, ease: "power1.out" }, "impact")
          
          // Blue ball arcs to left 'I'
          .to("#blue-ball", { x: -45, duration: 0.6, ease: "power1.inOut" }, "impact")
          .to("#blue-ball", { y: -80, duration: 0.3, ease: "power1.out" }, "impact")
          .to("#blue-ball", { y: -55, duration: 0.3, ease: "power1.in" }, "impact+=0.3") // drops exactly onto the 'I'
          
          // White ball arcs to right 'I'
          .to("#white-ball", { x: 43, duration: 0.6, ease: "power1.inOut" }, "impact")
          .to("#white-ball", { y: -80, duration: 0.3, ease: "power1.out" }, "impact")
          .to("#white-ball", { y: -55, duration: 0.3, ease: "power1.in" }, "impact+=0.3") // drops exactly onto the 'I'
          
          .add("lockup", "-=0.1")
          .to(["#player-group", "#red-ball", "#ground-line"], { opacity: 0, duration: 0.4 }, "lockup")
          .to("#text-lockup", { opacity: 1, duration: 0.4 }, "lockup")
          .to({}, { duration: 1.5 }); // Hold final logo
    }

    var navyHold = document.getElementById('navy-hold');
    var dismissed = false;

    function dismissPreloader() {
        if (dismissed) return;
        dismissed = true;

        // Stop GSAP loop for loader only, keeping global timeline active
        if (typeof gsap !== 'undefined' && loaderTimeline) {
            loaderTimeline.kill();
        }

        // ─ Phase 1: Start the diagonal tricolour sweep (1.5s) ─
        sweep.classList.add('sweeping');

        // ─ Phase 2 (1.0s): Navy part of gradient fills screen.
        //   Snap the navy-hold behind the sweep so there's no flicker,
        //   then hide the preloader (which was behind everything anyway).
        setTimeout(function () {
            navyHold.style.opacity = '1';
            navyHold.style.pointerEvents = 'all';
            preloader.style.display = 'none';
            document.body.classList.remove('preloader-active');
        }, 1000);

        // ─ Phase 3 (1.2s): Fade navy out and reveal page content ─
        //   (This leaves exactly 0.2s of solid navy before the transition)
        setTimeout(function () {
            document.getElementById('page-wrapper').classList.add('content-ready');
            navyHold.style.opacity = '0';
            if (typeof window.triggerHeroAnimation === 'function') {
                window.triggerHeroAnimation();
            }
            setTimeout(function () {
                navyHold.style.display = 'none';
                navyHold.style.pointerEvents = 'none';
            }, 550);
        }, 1200);

        // ─ Phase 4 (1.7s): Sweep has left the screen. Hide it to clean up DOM.
        setTimeout(function () {
            sweep.style.display = 'none';
        }, 1700);
    }

    /* Wait for page load, but guarantee min 2.5s animation */
    const startTime = Date.now();
    window.addEventListener('load', function() {
        const elapsed = Date.now() - startTime;
        const minTime = 2500;
        if (elapsed < minTime) {
            setTimeout(dismissPreloader, minTime - elapsed);
        } else {
            dismissPreloader();
        }
    });

    /* Safety fallback */
    setTimeout(dismissPreloader, 10000);
})();
</script>

<!-- Content Wrapper (Fades in after preloader) -->
<div id="page-wrapper" class="page-wrapper">



    <!-- ═══════════════════════════════════════════
         FULL HEADER WRAPPER (logo bar + navbar)
    ════════════════════════════════════════════ -->
    <div class="site-header-fixed">

        <!-- TOP LOGO BAR -->
        <?php include __DIR__ . '/logo-bar.php'; ?>


        <!-- NAVBAR ROW -->
        <div class="navbar-row">
            <div class="navbar-row-inner">

                <!-- Pill Nav Links -->
                <nav class="nav-pill" role="navigation" aria-label="Main navigation">
                    <ul class="nav-pill-list">
                        <?php
                        if (!function_exists('renderNavItem')) {
                            function renderNavItem($item, $logo_path, $pdo) {
                                // Fetch children
                                $childStmt = $pdo->prepare("SELECT * FROM navigation_items WHERE parent_id = ? AND is_visible = 1 ORDER BY sort_order ASC");
                                $childStmt->execute([$item['id']]);
                                $children = $childStmt->fetchAll();

                                if (!empty($children)) {
                                    $isSubmenu = ($item['parent_id'] !== null);
                                    $liClass = $isSubmenu ? 'npl-sub-dropdown' : 'npl-dropdown';
                                    $aClass = $isSubmenu ? 'npl-sub-item npl-has-sub-drop' : 'npl npl-has-drop';
                                    $ulClass = $isSubmenu ? 'npl-sub-submenu' : 'npl-submenu';
                                    $caret = $isSubmenu ? ' ▸' : ' ▾';

                                    echo '<li class="' . $liClass . '">';
                                    echo '<a href="#" class="' . $aClass . '">' . htmlspecialchars($item['title']) . '<span class="drop-caret">' . $caret . '</span></a>';
                                    echo '<ul class="' . $ulClass . '">';
                                    foreach ($children as $child) {
                                        renderNavItem($child, $logo_path, $pdo);
                                    }
                                    echo '</ul>';
                                    echo '</li>';
                                } else {
                                    if ($item['section'] === 'get-involved' && in_array($item['slug'], ['membership', 'players-database', 'officials-database'])) {
                                         $link = $logo_path . "get-involved/" . $item['slug'] . ".php";
                                    } elseif ($item['section'] === 'news-media' && in_array($item['slug'], ['news', 'gallery', 'videos'])) {
                                         if ($item['slug'] === 'news') {
                                             $link = $logo_path . "index.php#official-federation-updates";
                                         } elseif ($item['slug'] === 'gallery') {
                                             $link = $logo_path . "index.php#photo-gallery";
                                         } else {
                                             $link = $logo_path . "news-media/" . $item['slug'] . ".php";
                                         }
                                    } elseif ($item['section'] === 'competitions') {
                                         if ($item['slug'] === 'international-events') {
                                             $link = "https://worldboccia.io/events";
                                         } else {
                                             $link = $logo_path . "competitions/national-events.php";
                                         }
                                    } else {
                                         $link = !empty($item['slug']) ? $logo_path . "page.php?section=" . urlencode($item['section']) . "&slug=" . urlencode($item['slug']) : "#";
                                    }

                                    $aClass = ($item['parent_id'] !== null) ? 'npl-sub-item' : 'npl';
                                    echo '<li><a href="' . $link . '" class="' . $aClass . '">' . htmlspecialchars($item['title']) . '</a></li>';
                                }
                            }
                        }

                        $navItems = [];
                        try {
                            $parentStmt = $pdo->query("SELECT * FROM navigation_items WHERE parent_id IS NULL AND is_visible = 1 ORDER BY sort_order ASC");
                            $parents = $parentStmt->fetchAll();
                            
                            // Home is always first
                            echo '<li><a href="' . $logo_path . 'index.php#home" class="npl">Home</a></li>';
                            foreach ($parents as $parent) {
                                if (strtolower($parent['title']) === 'home') continue;
                                renderNavItem($parent, $logo_path, $pdo);
                            }
                        } catch (\Exception $e) {
                            // Fallback rendering
                            echo '<li><a href="' . $logo_path . 'index.php#home" class="npl">Home</a></li>';
                        }
                        if (isLoggedIn()) {
                            echo '<li><a href="' . $logo_path . 'admin/dashboard.php" class="npl">Dashboard</a></li>';
                        }
                        ?>
                    </ul>
                </nav>

                <!-- Login Pill -->
                <div class="nav-login-wrap">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo $logo_path; ?>logout.php" class="login-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 1.1em; height: 1.1em; margin-right: 0.5rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
                    <?php else: ?>
                        <a href="<?php echo $logo_path; ?>login.php" class="login-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 1.1em; height: 1.1em; margin-right: 0.5rem;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg> LOGIN</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile burger -->
                <button class="burger-btn" id="burgerBtn" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

    </div><!-- /.site-header-fixed -->

<script>
(function(){
    // Mobile burger
    var burger = document.getElementById('burgerBtn');
    var pillList = document.querySelector('.nav-pill-list');
    burger.addEventListener('click', function(){
        pillList.classList.toggle('open');
    });
    // Dropdown on click (touch-friendly)
    document.querySelectorAll('.npl-has-drop').forEach(function(link){
        link.addEventListener('click', function(e){
            e.preventDefault();
            this.closest('.npl-dropdown').classList.toggle('open');
        });
    });
    document.querySelectorAll('.npl-has-sub-drop').forEach(function(link){
        link.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            this.closest('.npl-sub-dropdown').classList.toggle('open');
        });
    });

    // Scroll effect to toggle header background and transition layout
    var header = document.querySelector('.site-header-fixed');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
})();
</script>
