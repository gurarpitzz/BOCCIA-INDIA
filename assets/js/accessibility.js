/**
 * accessibility.js - Self-contained Accessibility System (WCAG 2.2 AA compliant)
 * Works globally on front-end and admin pages.
 */
(function() {
    // 1. Detect Path Prefix for Assets
    const pathPrefix = window.location.pathname.includes('/admin/') ? '../' : '';
    const logoUrl = pathPrefix + 'assets/images/accessibility-icon.png';

    // 2. Default Accessibility State
    let settings = {
        fontSize: 16,
        highContrast: false,
        reverseContrast: false,
        grayscale: false,
        readableFont: false,
        underlineLinks: false,
        underlineHeaders: false,
        bigCursorWhite: false,
        bigCursorBlack: false,
        reduceMotion: false,
        panelOpen: false
    };

    // Load from localStorage if present
    try {
        const stored = localStorage.getItem('bsfiAccessibility');
        if (stored) {
            settings = { ...settings, ...JSON.parse(stored) };
        }
    } catch (e) {
        console.error('Error loading accessibility settings:', e);
    }

    // 3. Inject CSS Styles Dynamically to avoid duplicating stylesheet edits
    const styleEl = document.createElement('style');
    styleEl.id = 'a11y-injected-styles';
    styleEl.innerHTML = `
        /* Skip to Main Content Link styling */
        .skip-link {
            position: absolute;
            top: -100px;
            left: 20px;
            z-index: 999999;
            background: #138808;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 700;
            font-family: sans-serif;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: top 0.2s ease;
        }
        .skip-link:focus {
            top: 20px;
            outline: 3px solid #138808;
            outline-offset: 2px;
        }

        /* Focus indicators override */
        .a11y-panel button:focus,
        .a11y-panel a:focus,
        .a11y-toggle-btn:focus,
        .a11y-statement-modal button:focus {
            outline: 3px solid #138808 !important;
            outline-offset: 2px !important;
        }


        /* Accessibility Button */
        .a11y-toggle-btn {
            position: fixed !important;
            bottom: 25px !important;
            left: 25px !important;
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #0d3846, #10b981) !important;
            border: 2px solid #ffffff !important;
            cursor: pointer !important;
            z-index: 10000000 !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
        }
        .a11y-toggle-btn:hover {
            transform: scale(1.08) !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.45) !important;
        }

        /* Accessibility Control Panel */
        .a11y-panel {
            position: fixed !important;
            bottom: 95px !important;
            left: 25px !important;
            background: rgba(8, 26, 43, 0.96) !important;
            backdrop-filter: blur(12px) !important;
            border: 2px solid rgba(16, 185, 129, 0.4) !important;
            border-radius: 20px !important;
            padding: 1.25rem !important;
            width: 320px !important;
            max-width: calc(100vw - 50px) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6) !important;
            z-index: 10000000 !important;
            display: none;
            color: #ffffff !important;
            font-family: 'Outfit', 'Inter', sans-serif !important;
            text-align: left !important;
        }
        .a11y-panel h4 {
            margin: 0 0 0.5rem 0 !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            color: #10B981 !important;
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
            padding-bottom: 0.5rem !important;
        }
        .a11y-section-title {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #F4B942 !important;
            margin: 0.75rem 0 0.4rem 0 !important;
        }
        .a11y-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0.5rem !important;
        }
        .a11y-full-width {
            grid-column: span 2 !important;
        }
        .a11y-btn {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            padding: 0.6rem 0.5rem !important;
            font-size: 0.8rem !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            text-align: center !important;
            font-weight: 600 !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: background 0.2s, border-color 0.2s, color 0.2s !important;
        }
        .a11y-btn:hover {
            background: rgba(16, 185, 129, 0.15) !important;
            border-color: rgba(16, 185, 129, 0.5) !important;
        }
        .a11y-btn.active {
            background: #10B981 !important;
            color: #0b1b3d !important;
            border-color: #10B981 !important;
            font-weight: 700 !important;
        }
        .a11y-btn.reset {
            background: #ef4444 !important;
            border-color: #ef4444 !important;
            color: #ffffff !important;
        }
        .a11y-btn.reset:hover {
            background: #dc2626 !important;
        }


        /* High Contrast CSS Rules */
        html.high-contrast, html.high-contrast body, html.high-contrast div, html.high-contrast section, html.high-contrast main, html.high-contrast aside, html.high-contrast header, html.high-contrast footer {
            background-color: #000000 !important;
            color: #ffffff !important;
            background-image: none !important;
        }
        html.high-contrast a, html.high-contrast a *, html.high-contrast .link-arrow {
            color: #ffff00 !important;
            text-decoration: underline !important;
        }
        html.high-contrast button, html.high-contrast .btn, html.high-contrast input, html.high-contrast select, html.high-contrast textarea {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #ffffff !important;
        }

        /* Reverse Contrast CSS Rules */
        html.reverse-contrast, html.reverse-contrast body, html.reverse-contrast div, html.reverse-contrast section, html.reverse-contrast main, html.reverse-contrast aside, html.reverse-contrast header, html.reverse-contrast footer {
            background-color: #ffffff !important;
            color: #000000 !important;
            background-image: none !important;
        }
        html.reverse-contrast a, html.reverse-contrast a *, html.reverse-contrast .link-arrow {
            color: #0000ee !important;
            text-decoration: underline !important;
        }
        html.reverse-contrast button, html.reverse-contrast .btn, html.reverse-contrast input, html.reverse-contrast select, html.reverse-contrast textarea {
            background: #ffffff !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
        }

        /* Grayscale */
        html.grayscale-mode {
            filter: grayscale(100%) !important;
        }

        /* Readable Font */
        html.readable-font, html.readable-font * {
            font-family: Arial, Verdana, Tahoma, sans-serif !important;
        }

        /* Underlines */
        html.underline-links a {
            text-decoration: underline !important;
        }
        html.underline-headers h1, html.underline-headers h2, html.underline-headers h3, html.underline-headers h4, html.underline-headers h5, html.underline-headers h6 {
            text-decoration: underline !important;
        }

        /* Custom Cursors */
        html.big-cursor-white, html.big-cursor-white * {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 32 32'%3E%3Cpath fill='white' stroke='black' stroke-width='2' d='M0,0 L0,20 L6,14 L12,25 L16,23 L10,12 L17,12 Z'/%3E%3C/svg%3E"), auto !important;
        }
        html.big-cursor-black, html.big-cursor-black * {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 32 32'%3E%3Cpath fill='black' stroke='white' stroke-width='2' d='M0,0 L0,20 L6,14 L12,25 L16,23 L10,12 L17,12 Z'/%3E%3C/svg%3E"), auto !important;
        }

        /* Reduce Motion */
        html.reduce-motion *, html.reduce-motion *::before, html.reduce-motion *::after {
            animation: none !important;
            transition: none !important;
            scroll-behavior: auto !important;
        }

        /* Accessibility Statement Modal styling */
        .a11y-statement-modal {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.85);
            display: flex; align-items: center; justify-content: center;
            z-index: 999999;
            font-family: 'Outfit', 'Inter', sans-serif;
        }
        .a11y-statement-content {
            background: #08122E;
            border: 2px solid #138808;
            border-radius: 16px;
            padding: 2rem;
            width: 450px;
            max-width: 90%;
            color: #ffffff;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .a11y-statement-content h3 {
            margin-top: 0;
            color: #F4B942;
            font-size: 1.4rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 0.5rem;
        }
        .a11y-statement-close {
            position: absolute;
            top: 15px; right: 15px;
            background: none; border: none;
            color: #ffffff; font-size: 1.5rem;
            cursor: pointer;
        }
    `;
    document.head.appendChild(styleEl);

    // 4. Initial Sync (Apply all settings immediately to avoid FOUC)
    function applyCurrentSettings() {
        const root = document.documentElement;

        // Apply Font Size
        if (settings.fontSize) {
            root.style.fontSize = settings.fontSize + 'px';
        }

        // Helper to toggle html classes
        const toggleClass = (cls, state) => {
            if (state) root.classList.add(cls);
            else root.classList.remove(cls);
        };

        toggleClass('high-contrast', settings.highContrast);
        toggleClass('reverse-contrast', settings.reverseContrast);
        toggleClass('grayscale-mode', settings.grayscale);
        toggleClass('readable-font', settings.readableFont);
        toggleClass('underline-links', settings.underlineLinks);
        toggleClass('underline-headers', settings.underlineHeaders);
        toggleClass('big-cursor-white', settings.bigCursorWhite);
        toggleClass('big-cursor-black', settings.bigCursorBlack);
        toggleClass('reduce-motion', settings.reduceMotion);

        // Reduce Motion slider and swiper pauses
        if (settings.reduceMotion) {
            pauseSliders();
        }
    }

    // Call initially
    applyCurrentSettings();

    // 5. Build and Inject accessibility panel and toggle button
    function initAccessibilityPanel() {
        // Do not display the accessibility icon/widget on admin pages
        if (window.location.pathname.includes('/admin/')) return;

        // Only run once DOM is loaded or loading
        if (document.getElementById('a11y-toggle')) return;

        // Create elements
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'a11y-toggle';
        toggleBtn.className = 'a11y-toggle-btn';
        toggleBtn.setAttribute('aria-label', 'Accessibility Control Panel Options');
        toggleBtn.setAttribute('aria-haspopup', 'dialog');
        toggleBtn.setAttribute('aria-expanded', settings.panelOpen ? 'true' : 'false');
        toggleBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="30" height="30" style="display:block; margin:auto; pointer-events:none;">
              <circle cx="50" cy="22" r="8" fill="white" />
              <path d="M50 32 v28" stroke="white" stroke-width="8" stroke-linecap="round" />
              <path d="M25 42 h50" stroke="white" stroke-width="8" stroke-linecap="round" />
              <path d="M50 60 L35 82" stroke="white" stroke-width="8" stroke-linecap="round" />
              <path d="M50 60 L65 82" stroke="white" stroke-width="8" stroke-linecap="round" />
            </svg>
        `;

        const panel = document.createElement('div');
        panel.id = 'a11y-panel';
        panel.className = 'a11y-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Accessibility Controls');
        panel.setAttribute('aria-hidden', settings.panelOpen ? 'false' : 'true');
        if (settings.panelOpen) {
            panel.style.display = 'block';
        }

        panel.innerHTML = `
            <h4>Accessibility Panel</h4>
            
            <div class="a11y-section-title">Presets</div>
            <div class="a11y-grid">
                <button class="a11y-btn" id="preset-low-vision" onclick="applyPreset('low-vision')">Low Vision</button>
                <button class="a11y-btn" id="preset-dyslexia" onclick="applyPreset('dyslexia')">Dyslexia</button>
                <button class="a11y-btn" id="preset-motor" onclick="applyPreset('motor')">Motor Imp.</button>
                <button class="a11y-btn" id="preset-seizure" onclick="applyPreset('seizure')">Seizure Safe</button>
                <button class="a11y-btn" id="preset-screen-reader" onclick="applyPreset('screen-reader')" style="grid-column: span 2;">Screen Reader Opt.</button>
            </div>

            <div class="a11y-section-title">Manual Controls</div>
            <div class="a11y-grid">
                <button class="a11y-btn" onclick="adjustTextSize('inc')" aria-label="Increase text size">Text Size +</button>
                <button class="a11y-btn" onclick="adjustTextSize('dec')" aria-label="Decrease text size">Text Size -</button>
                
                <button class="a11y-btn" id="ctrl-highContrast" onclick="toggleOption('highContrast')">High Contrast</button>
                <button class="a11y-btn" id="ctrl-reverseContrast" onclick="toggleOption('reverseContrast')">Rev Contrast</button>
                
                <button class="a11y-btn" id="ctrl-grayscale" onclick="toggleOption('grayscale')">Grayscale</button>
                <button class="a11y-btn" id="ctrl-readableFont" onclick="toggleOption('readableFont')">Readable Font</button>
                
                <button class="a11y-btn" id="ctrl-underlineLinks" onclick="toggleOption('underlineLinks')">Underline Links</button>
                <button class="a11y-btn" id="ctrl-underlineHeaders" onclick="toggleOption('underlineHeaders')">Underline Hdr</button>
                
                <button class="a11y-btn" id="ctrl-bigCursorWhite" onclick="toggleOption('bigCursorWhite')">White Cursor</button>
                <button class="a11y-btn" id="ctrl-bigCursorBlack" onclick="toggleOption('bigCursorBlack')">Black Cursor</button>
                
                <button class="a11y-btn a11y-full-width" id="ctrl-reduceMotion" onclick="toggleOption('reduceMotion')">Reduce Motion</button>
                
                <button class="a11y-btn reset a11y-full-width" onclick="resetAccessibility()">Reset Settings</button>
            </div>
        `;

        document.body.appendChild(toggleBtn);
        document.body.appendChild(panel);

        // Update UI Button Classes based on active states
        updatePanelUI();

        // 6. Interactive Event Listeners
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = panel.style.display === 'block';
            togglePanel(!isOpen);
        });

        // Close on Escape key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && panel.style.display === 'block') {
                togglePanel(false);
                toggleBtn.focus();
            }
        });

        // Click outside panel closes it
        document.addEventListener('click', function(e) {
            if (!panel.contains(e.target) && e.target !== toggleBtn) {
                if (panel.style.display === 'block') {
                    togglePanel(false);
                }
            }
        });
    }

    // Toggle Panel Open/Close state
    function togglePanel(open) {
        const panel = document.getElementById('a11y-panel');
        const toggleBtn = document.getElementById('a11y-toggle');
        if (!panel || !toggleBtn) return;

        settings.panelOpen = open;
        panel.style.display = open ? 'block' : 'none';
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        saveSettings();

        if (open) {
            // Focus on first element inside panel
            const firstBtn = panel.querySelector('.a11y-btn');
            if (firstBtn) firstBtn.focus();
        }
    }

    // Save state to localStorage
    function saveSettings() {
        try {
            localStorage.setItem('bsfiAccessibility', JSON.stringify(settings));
        } catch (e) {
            console.error('Failed to save settings to localStorage:', e);
        }
    }

    // Update UI button Active styles based on current values
    function updatePanelUI() {
        const optionKeys = [
            'highContrast', 'reverseContrast', 'grayscale', 'readableFont',
            'underlineLinks', 'underlineHeaders', 'bigCursorWhite', 'bigCursorBlack', 'reduceMotion'
        ];
        optionKeys.forEach(key => {
            const btn = document.getElementById('ctrl-' + key);
            if (btn) {
                if (settings[key]) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-pressed', 'false');
                }
            }
        });

        // Manage mutual exclusivities in visual UI state
        // (already applied to logic inside toggleOption)
    }

    // Global action helpers attached to window
    window.toggleOption = function(key) {
        // Mutual Exclusions
        if (key === 'highContrast' && !settings.highContrast) {
            settings.reverseContrast = false;
        }
        if (key === 'reverseContrast' && !settings.reverseContrast) {
            settings.highContrast = false;
        }
        if (key === 'bigCursorWhite' && !settings.bigCursorWhite) {
            settings.bigCursorBlack = false;
        }
        if (key === 'bigCursorBlack' && !settings.bigCursorBlack) {
            settings.bigCursorWhite = false;
        }

        settings[key] = !settings[key];
        applyCurrentSettings();
        updatePanelUI();
        saveSettings();
    };

    window.adjustTextSize = function(direction) {
        const sizes = [14, 16, 18, 20, 22, 24];
        let idx = sizes.indexOf(settings.fontSize);
        if (idx === -1) idx = 1; // Default to 16

        if (direction === 'inc') {
            if (idx < sizes.length - 1) idx++;
        } else if (direction === 'dec') {
            if (idx > 0) idx--;
        }

        settings.fontSize = sizes[idx];
        applyCurrentSettings();
        saveSettings();
    };

    window.applyPreset = function(preset) {
        // Reset manual controls first
        settings.highContrast = false;
        settings.reverseContrast = false;
        settings.grayscale = false;
        settings.readableFont = false;
        settings.underlineLinks = false;
        settings.underlineHeaders = false;
        settings.bigCursorWhite = false;
        settings.bigCursorBlack = false;
        settings.reduceMotion = false;
        settings.fontSize = 16;

        switch (preset) {
            case 'low-vision':
                settings.fontSize = 22;
                settings.highContrast = true;
                settings.underlineLinks = true;
                settings.readableFont = true;
                break;
            case 'dyslexia':
                settings.readableFont = true;
                settings.underlineLinks = true;
                break;
            case 'motor':
                settings.bigCursorWhite = true;
                settings.reduceMotion = true;
                settings.underlineLinks = true;
                break;
            case 'seizure':
                settings.reduceMotion = true;
                break;
            case 'screen-reader':
                settings.underlineLinks = true;
                settings.readableFont = true;
                break;
        }

        applyCurrentSettings();
        updatePanelUI();
        saveSettings();
    };

    window.resetAccessibility = function() {
        settings = {
            fontSize: 16,
            highContrast: false,
            reverseContrast: false,
            grayscale: false,
            readableFont: false,
            underlineLinks: false,
            underlineHeaders: false,
            bigCursorWhite: false,
            bigCursorBlack: false,
            reduceMotion: false,
            panelOpen: settings.panelOpen
        };
        applyCurrentSettings();
        updatePanelUI();
        saveSettings();

        // Restore active sliders or animations
        resumeSliders();
    };

    // Pause Carousel and slider libraries
    function pauseSliders() {
        // Pause Swiper/Glide/Slick etc.
        try {
            // Find all active Swiper instances in window
            if (window.Swiper) {
                // If swiper is global or attached to selectors
                const swipers = document.querySelectorAll('.swiper-container, .swiper');
                swipers.forEach(el => {
                    if (el.swiper) {
                        el.swiper.autoplay.stop();
                    }
                });
            }
            if (window.jQuery && window.jQuery.fn.slick) {
                jQuery('.slick-slider').slick('slickPause');
            }
        } catch(e) {}
    }

    function resumeSliders() {
        try {
            if (window.Swiper) {
                const swipers = document.querySelectorAll('.swiper-container, .swiper');
                swipers.forEach(el => {
                    if (el.swiper) {
                        el.swiper.autoplay.start();
                    }
                });
            }
            if (window.jQuery && window.jQuery.fn.slick) {
                jQuery('.slick-slider').slick('slickPlay');
            }
        } catch(e) {}
    }

    // Modal Statement logic
    window.showAccessibilityStatement = function(e) {
        if (e) e.preventDefault();
        
        // Remove if exists
        const oldModal = document.getElementById('a11y-statement-modal');
        if (oldModal) oldModal.remove();

        const modal = document.createElement('div');
        modal.id = 'a11y-statement-modal';
        modal.className = 'a11y-statement-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-label', 'Accessibility Statement');

        modal.innerHTML = `
            <div class="a11y-statement-content">
                <button class="a11y-statement-close" onclick="closeAccessibilityStatement()" aria-label="Close Statement">&times;</button>
                <h3>Accessibility Statement</h3>
                <p>BSFI is committed to WCAG 2.2 AA accessibility standards.</p>
                <p>This website supports:</p>
                <ul style="padding-left: 1.2rem; margin: 1rem 0;">
                    <li>Keyboard Navigation</li>
                    <li>Screen Readers</li>
                    <li>Contrast Controls</li>
                    <li>Text Scaling</li>
                    <li>Reduced Motion</li>
                    <li>Dyslexia Friendly Fonts</li>
                </ul>
                <p>For accessibility assistance or feedback, contact:<br>
                <a href="mailto:bocciaindia@gmail.com" style="color: #F4B942;">bocciaindia@gmail.com</a></p>
            </div>
        `;
        document.body.appendChild(modal);

        // Trap focus inside modal
        modal.querySelector('.a11y-statement-close').focus();
    };

    window.closeAccessibilityStatement = function() {
        const modal = document.getElementById('a11y-statement-modal');
        if (modal) modal.remove();
    };

    // 7. Initialize widget on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccessibilityPanel);
    } else {
        initAccessibilityPanel();
    }
})();
