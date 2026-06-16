<?php
// header.php - Main navbar and Accessibility widgets
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en" style="background: var(--warm-surface);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Boccia Sports Federation of India"; ?></title>
    <meta name="description" content="Official portal of Boccia Sports Federation of India (BSFI). Affiliated with Paralympic Committee of India and Boccia International Sports Federation.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php
        $css_path  = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/import/') !== false ? '../styles.css' : 'styles.css';
        $logo_path = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/import/') !== false ? '../' : '';
    ?>
    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo $css_path; ?>?v=1.3">
    <!-- GLightbox -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
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
</head>
<body class="accessibility-target">

<!-- ═══════════════════════════════════════════════════
     VIDEO PRELOADER OVERLAY
═══════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
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
    <!-- 4. Tricolour + Navy Sweep (Curtain Wipe) -->
    <div id="tricolour-sweep">
        <div class="stripe saffron"></div>
        <div class="stripe white"></div>
        <div class="stripe green"></div>
        <div class="stripe navy"></div>
    </div>
</div>

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

/* ── Tricolour Sweep Transition ── */
#tricolour-sweep {
    position: fixed;
    top: 50%; left: 50%;
    width: 300vw; height: 265vh;
    margin-top: -132.5vh; margin-left: -150vw;
    z-index: 100000;
    pointer-events: none;
    display: flex;
    flex-direction: column;
    transform: rotate(45deg) translateY(300vh);
    opacity: 1;
}

#tricolour-sweep .stripe {
    width: 100%;
}
.stripe.saffron { flex: 0 0 40vh;  background: #FF9933; }
.stripe.white   { flex: 0 0 35vh;  background: #FFFFFF; }
.stripe.green   { flex: 0 0 40vh;  background: #138808; }
.stripe.navy    { flex: 0 0 150vh; background: var(--primary-navy); } /* Dominant blue sweeps in last */

#tricolour-sweep.sweeping {
    /* Pure curtain wipe: takes 2.5s to move from bottom-left to top-right */
    animation: sweep-diag 2.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes sweep-diag {
    0%   { transform: rotate(45deg) translateY(300vh); } /* Starts off-screen bottom-left */
    100% { transform: rotate(45deg) translateY(-300vh); } /* Ends off-screen top-right */
}

/* Lock scroll while preloader is active */
body.preloader-active {
    overflow: hidden !important;
}
</style>

<script>
(function () {
    /* Lock scroll */
    document.body.classList.add('preloader-active');

    var preloader = document.getElementById('bsfi-preloader');
    var sweep     = document.getElementById('tricolour-sweep');

    if (typeof gsap !== 'undefined') {
        const tl = gsap.timeline({ repeat: -1, repeatDelay: 0.5 });
        
        tl.set(["#red-ball", "#blue-ball", "#white-ball"], { x: 0, y: 0 })
          .set("#player-arm", { rotation: 0, svgOrigin: "41.75 94.5" })
          .set(["#player-group", "#red-ball", "#ground-line"], { opacity: 1, scale: 1 })
          .set(["#blue-ball", "#white-ball"], { scale: 1 })
          .set("#text-lockup", { opacity: 0 });

        tl.to("#player-arm", { rotation: -40, duration: 0.3, ease: "power1.inOut" })
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

    function dismissPreloader() {
        if(preloader.style.display === 'none') return;
        
        /* Step 1: Start the tricolour sweep */
        sweep.classList.add('sweeping');

        /* Step 2: Hide preloader behind the curtain */
        setTimeout(function () {
            preloader.style.display = 'none';
            document.body.classList.remove('preloader-active');
            document.body.style.background = 'var(--primary-navy)';
        }, 1200);

        /* Step 3: Fade in landing page */
        setTimeout(function () {
            document.getElementById('page-wrapper').classList.add('content-ready');
        }, 2500);

        /* Step 4: Clean up */
        setTimeout(function () {
            sweep.style.display = 'none';
        }, 2800);
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

    <!-- Accessibility Floating Button -->
    <button class="a11y-toggle-btn" id="a11y-toggle" aria-label="Accessibility Options">♿</button>
    <div class="a11y-panel" id="a11y-panel" aria-hidden="true">
        <h4>Accessibility Control Panel</h4>
        <div class="a11y-grid">
            <button onclick="adjustTextSize('inc')" class="a11y-btn">Text Size +</button>
            <button onclick="adjustTextSize('dec')" class="a11y-btn">Text Size -</button>
            <button onclick="toggleAccessibilityClass('contrast-high')" class="a11y-btn">High Contrast</button>
            <button onclick="toggleAccessibilityClass('contrast-rev')" class="a11y-btn">Reverse Contrast</button>
            <button onclick="toggleAccessibilityClass('grayscale-mode')" class="a11y-btn">Grayscale</button>
            <button onclick="toggleAccessibilityClass('font-readable')" class="a11y-btn">Readable Font</button>
            <button onclick="toggleAccessibilityClass('underline-links')" class="a11y-btn">Underline Links</button>
            <button onclick="toggleAccessibilityClass('underline-headers')" class="a11y-btn">Underline Headers</button>
            <button onclick="toggleAccessibilityClass('big-cursor-white')" class="a11y-btn">Big White Cursor</button>
            <button onclick="toggleAccessibilityClass('big-cursor-black')" class="a11y-btn">Big Black Cursor</button>
            <button onclick="toggleAccessibilityClass('reduce-motion')" class="a11y-btn">Reduce Motion</button>
            <button onclick="resetAccessibility()" class="a11y-btn reset">Reset Settings</button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         FULL HEADER WRAPPER (logo bar + navbar)
    ════════════════════════════════════════════ -->
    <div class="site-header-fixed">

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

        <!-- NAVBAR ROW -->
        <div class="navbar-row">
            <div class="navbar-row-inner">

                <!-- Pill Nav Links -->
                <nav class="nav-pill" role="navigation" aria-label="Main navigation">
                    <ul class="nav-pill-list">
                        <li><a href="<?php echo $logo_path; ?>index.php#home"       class="npl">Home</a></li>
                        <li><a href="<?php echo $logo_path; ?>index.php#about"      class="npl">About</a></li>
                        <li class="npl-dropdown">
                            <a href="#" class="npl npl-has-drop">Our Sport <span class="drop-caret">▾</span></a>
                            <ul class="npl-submenu">
                                <li><a href="<?php echo $logo_path; ?>index.php#discover" class="npl-sub-item">Discover Boccia</a></li>
                                <li><a href="<?php echo $logo_path; ?>index.php#map"      class="npl-sub-item">State Participation</a></li>
                                <li><a href="<?php echo $logo_path; ?>index.php#classify" class="npl-sub-item">Classification</a></li>
                            </ul>
                        </li>
                        <li class="npl-dropdown">
                            <a href="#" class="npl npl-has-drop">Get Involved <span class="drop-caret">▾</span></a>
                            <ul class="npl-submenu">
                                <li><a href="<?php echo $logo_path; ?>index.php#register" class="npl-sub-item">Register Athlete</a></li>
                                <li><a href="<?php echo $logo_path; ?>index.php#coaches"  class="npl-sub-item">For Coaches</a></li>
                                <li><a href="<?php echo $logo_path; ?>index.php#clubs"    class="npl-sub-item">State Clubs</a></li>
                            </ul>
                        </li>
                        <li><a href="<?php echo $logo_path; ?>index.php#competitions" class="npl">Competitions</a></li>
                        <li><a href="<?php echo $logo_path; ?>index.php#news"         class="npl">News &amp; Media</a></li>
                        <li><a href="<?php echo $logo_path; ?>index.php#selection"    class="npl">Selection Guidelines</a></li>
                        <li><a href="<?php echo $logo_path; ?>index.php#contact"      class="npl">Contact Us</a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo $logo_path; ?>admin/dashboard.php"    class="npl">Dashboard</a></li>
                        <?php endif; ?>
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
