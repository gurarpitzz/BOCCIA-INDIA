/**
 * assistant.js - BSFI Quick Assist System (WCAG 2.2 AA compliant)
 * Unifies Accessibility and Guided Virtual Assistant under a single FAB.
 */
(function() {
    // 1. Detect Path Prefix for Assets
    const pathPrefix = window.location.pathname.includes('/admin/') ? '../' : '';

    // Do not show on admin pages
    if (window.location.pathname.includes('/admin/')) return;

    // 2. Inject CSS Styles Dynamically
    const styleEl = document.createElement('style');
    styleEl.id = 'bsfi-quick-assist-styles';
    styleEl.innerHTML = `
        /* Hide original accessibility button */
        #a11y-toggle {
            display: none !important;
        }

        /* ── Quick Assist Floating Action Button ── */
        .bsfi-quick-assist-fab {
            position: fixed !important;
            bottom: 25px !important;
            left: 25px !important;
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #081B4B, #24C27A) !important;
            border: 2px solid #ffffff !important;
            cursor: pointer !important;
            z-index: 10000000 !important;
            box-shadow: 0 4px 15px rgba(8, 27, 75, 0.35) !important;
            transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            color: #ffffff !important;
        }
        .bsfi-quick-assist-fab:hover {
            transform: scale(1.08) !important;
            box-shadow: 0 6px 20px rgba(8, 27, 75, 0.45) !important;
        }
        
        /* Subtle Pulse Animation */
        @keyframes qa-pulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(8, 27, 75, 0.35); }
            50% { box-shadow: 0 4px 25px rgba(36, 194, 122, 0.6); }
        }
        .bsfi-quick-assist-fab-pulse {
            animation: qa-pulse 3s infinite ease-in-out;
        }

        /* Tooltip */
        .bsfi-quick-assist-fab::after {
            content: "Quick Assist";
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) scale(0.8);
            background: #081B4B;
            color: #ffffff;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-family: 'Outfit', sans-serif;
        }
        .bsfi-quick-assist-fab:hover::after {
            opacity: 1;
            transform: translateX(-50%) scale(1);
        }

        /* ── Quick Assist Popover Menu ── */
        .bsfi-quick-assist-popover {
            position: fixed !important;
            bottom: 95px !important;
            left: 25px !important;
            width: 255px !important;
            background: rgba(250, 247, 240, 0.94) !important; /* Cream themed */
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(8, 27, 75, 0.12) !important;
            border-radius: 24px !important;
            box-shadow: 0 15px 35px rgba(8, 27, 75, 0.2) !important;
            z-index: 10000000 !important;
            overflow: hidden !important;
            font-family: 'Outfit', 'Poppins', sans-serif !important;
            transform: translateY(15px) scale(0.95);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.3s ease;
            pointer-events: none;
            display: none;
        }
        .bsfi-quick-assist-popover.active {
            transform: translateY(0) scale(1);
            opacity: 1;
            pointer-events: all;
            display: block;
        }

        .bsfi-qa-header {
            padding: 0.85rem 1.25rem 0.5rem !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            color: rgba(8, 27, 75, 0.4) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            border-bottom: 1px solid rgba(8, 27, 75, 0.06) !important;
        }

        .bsfi-qa-items {
            padding: 0.5rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.35rem !important;
        }

        .bsfi-qa-item {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            height: 52px !important;
            padding: 0 0.85rem !important;
            background: transparent !important;
            border: none !important;
            border-radius: 14px !important;
            cursor: pointer !important;
            text-align: left !important;
            transition: background 0.2s, transform 0.2s !important;
            gap: 0.75rem !important;
        }
        .bsfi-qa-item:hover {
            background: rgba(8, 27, 75, 0.05) !important;
            transform: translateY(-1px) !important;
        }
        .bsfi-qa-item-icon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            background: rgba(36, 194, 122, 0.1) !important;
            color: #24C27A !important;
            flex-shrink: 0 !important;
        }
        .bsfi-qa-item-icon svg {
            width: 18px !important;
            height: 18px !important;
        }
        .bsfi-qa-item-title {
            flex-grow: 1 !important;
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            color: #081B4B !important;
        }
        .bsfi-qa-item-arrow {
            color: rgba(8, 27, 75, 0.3) !important;
            font-size: 0.8rem !important;
            transition: transform 0.2s ease !important;
        }
        .bsfi-qa-item:hover .bsfi-qa-item-arrow {
            transform: translateX(3px) !important;
            color: #24C27A !important;
        }

        /* ── Assistant Panel ── */
        .bsfi-ast-panel {
            position: fixed !important;
            bottom: 95px !important;
            left: 25px !important;
            width: 360px !important;
            max-width: calc(100vw - 50px) !important;
            max-height: 520px !important;
            background: #FAF7F0 !important;
            border: 1px solid rgba(8, 27, 75, 0.15) !important;
            border-radius: 28px !important;
            box-shadow: 0 15px 35px rgba(8, 27, 75, 0.15) !important;
            z-index: 10000000 !important;
            display: none;
            flex-direction: column !important;
            overflow: hidden !important;
            font-family: 'Outfit', 'Poppins', sans-serif !important;
            text-align: left !important;
            transform: translateY(20px) scale(0.95);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.35s ease;
        }
        .bsfi-ast-panel.active {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        /* Panel Header */
        .bsfi-ast-header {
            background: #081B4B !important;
            padding: 1.5rem !important;
            position: relative !important;
            color: #ffffff !important;
            border-bottom: 3px solid #FF9933 !important;
        }
        .bsfi-ast-header h3 {
            margin: 0 !important;
            font-size: 1.25rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.02em !important;
            color: #ffffff !important;
        }
        .bsfi-ast-subtitle {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            color: #FF9933 !important;
            display: block !important;
            margin-top: 2px !important;
        }
        .bsfi-ast-tagline {
            margin: 0.5rem 0 0 0 !important;
            font-size: 0.82rem !important;
            color: rgba(255, 255, 255, 0.75) !important;
            line-height: 1.3 !important;
        }
        .bsfi-ast-close-btn {
            position: absolute !important;
            top: 1.25rem !important;
            right: 1.25rem !important;
            background: none !important;
            border: none !important;
            color: #ffffff !important;
            font-size: 1.5rem !important;
            cursor: pointer !important;
            opacity: 0.75 !important;
            transition: opacity 0.2s ease !important;
            line-height: 1 !important;
            padding: 0 !important;
        }
        .bsfi-ast-close-btn:hover {
            opacity: 1 !important;
        }

        /* Panel Body / Scroller */
        .bsfi-ast-body {
            padding: 1.25rem !important;
            overflow-y: auto !important;
            flex-grow: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
            max-height: 380px !important;
        }

        /* Menu Views container */
        .bsfi-ast-view {
            display: none;
            flex-direction: column !important;
            gap: 0.75rem !important;
            width: 100% !important;
        }
        .bsfi-ast-view.active {
            display: flex !important;
        }

        /* Navigation Cards */
        .bsfi-ast-card {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            padding: 0.85rem 1rem !important;
            background: #ffffff !important;
            border: 1px solid rgba(8, 27, 75, 0.08) !important;
            border-radius: 16px !important;
            cursor: pointer !important;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease !important;
            box-shadow: 0 2px 6px rgba(8, 27, 75, 0.02) !important;
            gap: 0.85rem !important;
            text-align: left !important;
        }
        .bsfi-ast-card:hover {
            transform: translateY(-2px) !important;
            border-color: rgba(36, 194, 122, 0.3) !important;
            box-shadow: 0 6px 15px rgba(8, 27, 75, 0.06) !important;
        }
        .bsfi-ast-card-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 10px !important;
            background: rgba(36, 194, 122, 0.1) !important;
            color: #24C27A !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }
        .bsfi-ast-card-icon svg {
            width: 20px !important;
            height: 20px !important;
        }
        .bsfi-ast-card-content {
            flex-grow: 1 !important;
        }
        .bsfi-ast-card-content h4 {
            margin: 0 !important;
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            color: #081B4B !important;
        }
        .bsfi-ast-card-content p {
            margin: 2px 0 0 0 !important;
            font-size: 0.76rem !important;
            color: rgba(8, 27, 75, 0.6) !important;
            line-height: 1.2 !important;
        }
        .bsfi-ast-arrow {
            color: rgba(8, 27, 75, 0.3) !important;
            font-size: 0.8rem !important;
            transition: transform 0.2s ease !important;
        }
        .bsfi-ast-card:hover .bsfi-ast-arrow {
            transform: translateX(3px) !important;
            color: #24C27A !important;
        }

        /* Submenu Headers */
        .bsfi-ast-back-btn {
            background: none !important;
            border: none !important;
            color: #24C27A !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            padding: 0 0 0.5rem 0 !important;
            text-align: left !important;
            width: max-content !important;
        }
        .bsfi-ast-back-btn:hover {
            color: #081B4B !important;
        }
        .bsfi-ast-menu-title {
            font-size: 1.05rem !important;
            font-weight: 800 !important;
            color: #081B4B !important;
            margin: 0 0 0.25rem 0 !important;
        }
        .bsfi-ast-menu-desc {
            font-size: 0.8rem !important;
            color: rgba(8, 27, 75, 0.6) !important;
            margin: 0 0 0.75rem 0 !important;
        }

        /* Action Buttons */
        .bsfi-ast-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            background: #ffffff !important;
            border: 1px solid rgba(8, 27, 75, 0.06) !important;
            border-radius: 12px !important;
            color: #081B4B !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01) !important;
            margin-bottom: 0.5rem !important;
            cursor: pointer !important;
        }
        .bsfi-ast-btn:hover {
            background: #081B4B !important;
            color: #ffffff !important;
            border-color: #081B4B !important;
            padding-left: 1.25rem !important;
        }
        
        /* Focus styles for keyboard accessibility */
        .bsfi-quick-assist-fab:focus,
        .bsfi-qa-item:focus,
        .bsfi-ast-close-btn:focus,
        .bsfi-ast-card:focus,
        .bsfi-ast-back-btn:focus,
        .bsfi-ast-btn:focus {
            outline: 2px solid #24C27A !important;
            outline-offset: 2px !important;
        }

        /* Mobile overrides */
        @media (max-width: 768px) {
            .bsfi-quick-assist-fab {
                left: 20px !important;
                bottom: 20px !important;
            }
            .bsfi-quick-assist-popover {
                left: 20px !important;
                bottom: 85px !important;
                width: calc(100vw - 40px) !important;
            }
            .bsfi-ast-panel {
                bottom: 85px !important;
                left: 20px !important;
                right: 20px !important;
                width: calc(100vw - 40px) !important;
            }
        }
    `;
    document.head.appendChild(styleEl);

    let activeViewStack = ['bsfi-ast-main-menu'];

    // 3. Helper to close accessibility panel
    function closeAccessibilityPanel() {
        const a11yPanel = document.getElementById('a11y-panel');
        const a11yToggle = document.getElementById('a11y-toggle');
        if (a11yPanel && a11yPanel.style.display === 'block' && a11yToggle) {
            a11yToggle.click();
        }
    }

    // 4. Initialize Widgets
    function initQuickAssist() {
        if (document.getElementById('bsfi-quick-assist-fab')) return;

        // Quick Assist FAB
        const fab = document.createElement('button');
        fab.id = 'bsfi-quick-assist-fab';
        fab.className = 'bsfi-quick-assist-fab bsfi-quick-assist-fab-pulse';
        fab.setAttribute('aria-label', 'Open Quick Assist Menu');
        fab.setAttribute('aria-haspopup', 'true');
        fab.setAttribute('aria-expanded', 'false');
        fab.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:28px; height:28px; display:block;">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
            </svg>
        `;

        // Popover Menu
        const popover = document.createElement('div');
        popover.id = 'bsfi-quick-assist-popover';
        popover.className = 'bsfi-quick-assist-popover';
        popover.setAttribute('role', 'menu');
        popover.setAttribute('aria-label', 'Quick Assist Options');

        popover.innerHTML = `
            <div class="bsfi-qa-header">Quick Assist</div>
            <div class="bsfi-qa-items">
                <button class="bsfi-qa-item" id="bsfi-qa-opt-a11y" role="menuitem">
                    <span class="bsfi-qa-item-icon">
                        <svg viewBox="0 0 100 100" style="width: 18px; height: 18px; fill: currentColor;">
                            <circle cx="50" cy="22" r="8"></circle>
                            <path d="M50 32 v28" stroke="currentColor" stroke-width="8" stroke-linecap="round"></path>
                            <path d="M25 42 h50" stroke="currentColor" stroke-width="8" stroke-linecap="round"></path>
                            <path d="M50 60 L35 82" stroke="currentColor" stroke-width="8" stroke-linecap="round"></path>
                            <path d="M50 60 L65 82" stroke="currentColor" stroke-width="8" stroke-linecap="round"></path>
                        </svg>
                    </span>
                    <span class="bsfi-qa-item-title">Accessibility Options</span>
                    <span class="bsfi-qa-item-arrow">&rarr;</span>
                </button>
                <button class="bsfi-qa-item" id="bsfi-qa-opt-ast" role="menuitem">
                    <span class="bsfi-qa-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </span>
                    <span class="bsfi-qa-item-title">Ask BSFI</span>
                    <span class="bsfi-qa-item-arrow">&rarr;</span>
                </button>
            </div>
        `;

        // Assistant Panel
        const panel = document.createElement('div');
        panel.id = 'bsfi-ast-panel';
        panel.className = 'bsfi-ast-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'BSFI Virtual Assistant Navigation');
        panel.setAttribute('aria-hidden', 'true');

        panel.innerHTML = `
            <div class="bsfi-ast-header">
                <h3>Ask BSFI</h3>
                <span class="bsfi-ast-subtitle">Official Virtual Assistant</span>
                <p class="bsfi-ast-tagline">Helping you quickly find official information.</p>
                <button class="bsfi-ast-close-btn" id="bsfi-ast-close-btn" aria-label="Close Assistant">&times;</button>
            </div>
            
            <div class="bsfi-ast-body" id="bsfi-ast-body">
                
                <!-- ════ MAIN MENU VIEW ════ -->
                <div class="bsfi-ast-view active" id="bsfi-ast-main-menu">
                    <!-- Become Athlete/Official -->
                    <button class="bsfi-ast-card" onclick="openAssistantView('bsfi-ast-menu-register')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M20 8v6M23 11h-6"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Become an Athlete / Official</h4>
                            <p>Register online, criteria &amp; classification.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Learn About Boccia -->
                    <button class="bsfi-ast-card" onclick="openAssistantView('bsfi-ast-menu-learn')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Learn About Boccia</h4>
                            <p>Sport overview, official rules &amp; equipment.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- State Associations -->
                    <button class="bsfi-ast-card" onclick="openAssistantView('bsfi-ast-menu-states')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>State Associations</h4>
                            <p>Browse regional directories &amp; map location.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Upcoming Events -->
                    <button class="bsfi-ast-card" onclick="closeAssistantAndScroll('#schedules')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Upcoming Events</h4>
                            <p>View tournament schedules &amp; timelines.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Latest News -->
                    <button class="bsfi-ast-card" onclick="closeAssistantAndScroll('#official-federation-updates')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 4a2 2 0 0 0-2-2v8a2 2 0 0 0 2-2z"/><path d="M12 8H7M12 12H7M7 16h8"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Latest News</h4>
                            <p>Read official updates and event coverage.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Photo Gallery -->
                    <button class="bsfi-ast-card" onclick="closeAssistantAndScroll('#photo-gallery')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Photo Gallery</h4>
                            <p>Browse official event collage photo albums.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Contact BSFI -->
                    <button class="bsfi-ast-card" onclick="openAssistantView('bsfi-ast-menu-contact')">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Contact BSFI</h4>
                            <p>Addresses, emails &amp; contact parameters.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>

                    <!-- Accessibility Tools -->
                    <button class="bsfi-ast-card" onclick="triggerAccessibilityToolbar()">
                        <div class="bsfi-ast-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4M12 8h.01"/></svg>
                        </div>
                        <div class="bsfi-ast-card-content">
                            <h4>Accessibility Tools</h4>
                            <p>Configure screen reader contrast &amp; layouts.</p>
                        </div>
                        <span class="bsfi-ast-arrow">&rarr;</span>
                    </button>
                </div>

                <!-- ════ SUBMENU: REGISTER ════ -->
                <div class="bsfi-ast-view" id="bsfi-ast-menu-register">
                    <button class="bsfi-ast-back-btn" onclick="backAssistantView()">&larr; Back to Main Options</button>
                    <h5 class="bsfi-ast-menu-title">Become an Athlete / Official</h5>
                    <p class="bsfi-ast-menu-desc">Select a registration option or view criteria guidelines.</p>
                    
                    <a href="${pathPrefix}get-involved/register-player.php" class="bsfi-ast-btn">Register as Athlete <span>&rarr;</span></a>
                    <a href="${pathPrefix}get-involved/register-official.php" class="bsfi-ast-btn">Register as Official <span>&rarr;</span></a>
                    <button onclick="closeAssistantAndScroll('#eligibility')" class="bsfi-ast-btn">Eligibility Criteria <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndScroll('#eligibility')" class="bsfi-ast-btn">Required Documents <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=sport&slug=classification')" class="bsfi-ast-btn">Classification Process <span>&rarr;</span></button>
                    <a href="${pathPrefix}get-involved/membership.php" class="bsfi-ast-btn">Registration FAQ <span>&rarr;</span></a>
                </div>

                <!-- ════ SUBMENU: LEARN ════ -->
                <div class="bsfi-ast-view" id="bsfi-ast-menu-learn">
                    <button class="bsfi-ast-back-btn" onclick="backAssistantView()">&larr; Back to Main Options</button>
                    <h5 class="bsfi-ast-menu-title">Learn About Boccia</h5>
                    <p class="bsfi-ast-menu-desc">Explore the game, governing rules, and equipment setups.</p>
                    
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=about&slug=about-boccia')" class="bsfi-ast-btn">What is Boccia? <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=sport&slug=rules')" class="bsfi-ast-btn">Rules of the Sport <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=sport&slug=equipment')" class="bsfi-ast-btn">Equipment <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=sport&slug=rules')" class="bsfi-ast-btn">Competition Format <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndRedirect('${pathPrefix}page.php?section=about&slug=about-boccia')" class="bsfi-ast-btn">History of Boccia <span>&rarr;</span></button>
                </div>

                <!-- ════ SUBMENU: STATE ASSOCIATIONS ════ -->
                <div class="bsfi-ast-view" id="bsfi-ast-menu-states">
                    <button class="bsfi-ast-back-btn" onclick="backAssistantView()">&larr; Back to Main Options</button>
                    <h5 class="bsfi-ast-menu-title">State Associations</h5>
                    <p class="bsfi-ast-menu-desc">Find regional coordinators and associations across India.</p>
                    
                    <button onclick="closeAssistantAndScroll('#map')" class="bsfi-ast-btn">Open Interactive Map <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndScroll('#map')" class="bsfi-ast-btn">Browse State Directory <span>&rarr;</span></button>
                    <button onclick="closeAssistantAndScroll('#map')" class="bsfi-ast-btn">Contact State Association <span>&rarr;</span></button>
                </div>

                <!-- ════ SUBMENU: CONTACT ════ -->
                <div class="bsfi-ast-view" id="bsfi-ast-menu-contact">
                    <button class="bsfi-ast-back-btn" onclick="backAssistantView()">&larr; Back to Main Options</button>
                    <h5 class="bsfi-ast-menu-title">Contact BSFI</h5>
                    <p class="bsfi-ast-menu-desc">Reach out to the BSFI administrative headquarters.</p>
                    
                    <a href="${pathPrefix}contact.php" class="bsfi-ast-btn">Contact Page <span>&rarr;</span></a>
                    <a href="${pathPrefix}contact.php" class="bsfi-ast-btn">Registered Office <span>&rarr;</span></a>
                    <a href="mailto:bocciaindia@gmail.com" class="bsfi-ast-btn">Email BSFI <span>&rarr;</span></a>
                    <a href="${pathPrefix}contact.php" class="bsfi-ast-btn">Phone Numbers <span>&rarr;</span></a>
                </div>

            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(popover);
        document.body.appendChild(panel);

        // 4. Attach Event Handlers
        fab.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = popover.classList.contains('active');
            togglePopover(!isOpen);
            // Always close assistant panel when toggle clicked
            toggleAssistantPanel(false);
        });

        // Trigger Accessibility Option
        document.getElementById('bsfi-qa-opt-a11y').addEventListener('click', function(e) {
            e.stopPropagation();
            togglePopover(false);
            toggleAssistantPanel(false); // Close assistant panel if open

            const a11yPanel = document.getElementById('a11y-panel');
            const a11yToggle = document.getElementById('a11y-toggle');
            if (a11yToggle) {
                if (a11yPanel && a11yPanel.style.display === 'block') {
                    // Already open, do nothing
                } else {
                    a11yToggle.click();
                }
            }
        });

        // Trigger Virtual Assistant Option
        document.getElementById('bsfi-qa-opt-ast').addEventListener('click', function(e) {
            e.stopPropagation();
            togglePopover(false);
            closeAccessibilityPanel(); // Close accessibility panel if open
            toggleAssistantPanel(true);
        });

        document.getElementById('bsfi-ast-close-btn').addEventListener('click', function() {
            toggleAssistantPanel(false);
        });

        // Click outside closes popover or panel
        document.addEventListener('click', function(e) {
            if (popover.classList.contains('active') && !popover.contains(e.target) && e.target !== fab && !fab.contains(e.target)) {
                togglePopover(false);
            }
            if (panel.classList.contains('active') && !panel.contains(e.target) && e.target !== fab && !fab.contains(e.target)) {
                toggleAssistantPanel(false);
            }
        });

        // Escape key closes open menus
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (popover.classList.contains('active')) {
                    togglePopover(false);
                    fab.focus();
                }
                if (panel.classList.contains('active')) {
                    toggleAssistantPanel(false);
                    fab.focus();
                }
            }
        });

        // Listen for standard accessibility button clicks to close virtual assistant when it's manually opened
        const a11yToggle = document.getElementById('a11y-toggle');
        if (a11yToggle) {
            a11yToggle.addEventListener('click', function() {
                const a11yPanel = document.getElementById('a11y-panel');
                if (a11yPanel && a11yPanel.style.display === 'block') {
                    toggleAssistantPanel(false); // Close assistant if accessibility drawer opens
                }
            });
        }
    }

    // Toggle popover state
    function togglePopover(open) {
        const popover = document.getElementById('bsfi-quick-assist-popover');
        const fab = document.getElementById('bsfi-quick-assist-fab');
        if (!popover || !fab) return;

        if (open) {
            popover.style.display = 'block';
            fab.setAttribute('aria-expanded', 'true');
            fab.classList.remove('bsfi-quick-assist-fab-pulse');
            setTimeout(() => {
                popover.classList.add('active');
                const firstOpt = popover.querySelector('.bsfi-qa-item');
                if (firstOpt) firstOpt.focus();
            }, 10);
        } else {
            popover.classList.remove('active');
            fab.setAttribute('aria-expanded', 'false');
            fab.classList.add('bsfi-quick-assist-fab-pulse');
            setTimeout(() => {
                popover.style.display = 'none';
            }, 300);
        }
    }

    // Toggle assistant panel state
    function toggleAssistantPanel(open) {
        const panel = document.getElementById('bsfi-ast-panel');
        const fab = document.getElementById('bsfi-quick-assist-fab');
        if (!panel || !fab) return;

        if (open) {
            panel.style.display = 'flex';
            panel.setAttribute('aria-hidden', 'false');
            fab.classList.remove('bsfi-quick-assist-fab-pulse');
            setTimeout(() => {
                panel.classList.add('active');
                const firstCard = panel.querySelector('.bsfi-ast-card');
                if (firstCard) firstCard.focus();
            }, 10);
        } else {
            panel.classList.remove('active');
            panel.setAttribute('aria-hidden', 'true');
            fab.classList.add('bsfi-quick-assist-fab-pulse');
            setTimeout(() => {
                panel.style.display = 'none';
            }, 350);
        }
    }

    // Assistant inner view navigations
    window.openAssistantView = function(viewId) {
        const activeView = document.querySelector('.bsfi-ast-view.active');
        if (activeView) activeView.classList.remove('active');

        const nextView = document.getElementById(viewId);
        if (nextView) {
            nextView.classList.add('active');
            activeViewStack.push(viewId);
            const firstItem = nextView.querySelector('button, a');
            if (firstItem) firstItem.focus();
        }
    };

    window.backAssistantView = function() {
        if (activeViewStack.length <= 1) return;
        const currentViewId = activeViewStack.pop();
        const currentView = document.getElementById(currentViewId);
        if (currentView) currentView.classList.remove('active');

        const prevViewId = activeViewStack[activeViewStack.length - 1];
        const prevView = document.getElementById(prevViewId);
        if (prevView) {
            prevView.classList.add('active');
            const firstItem = prevView.querySelector('button, a');
            if (firstItem) firstItem.focus();
        }
    };

    // Close and handle actions
    window.closeAssistantAndScroll = function(selector) {
        toggleAssistantPanel(false);
        const el = document.querySelector(selector);
        if (el) {
            setTimeout(() => {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        } else {
            window.location.href = pathPrefix + 'index.php' + selector;
        }
    };

    window.closeAssistantAndRedirect = function(url) {
        toggleAssistantPanel(false);
        setTimeout(() => {
            window.location.href = url;
        }, 200);
    };

    window.triggerAccessibilityToolbar = function() {
        toggleAssistantPanel(false);
        setTimeout(() => {
            const a11yToggle = document.getElementById('a11y-toggle');
            if (a11yToggle) {
                a11yToggle.click();
            }
        }, 300);
    };

    // Initializer on ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuickAssist);
    } else {
        initQuickAssist();
    }
})();
