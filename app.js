// app.js - Boccia India main application logic

document.addEventListener('DOMContentLoaded', () => {
    
    // Accessibility Panel Toggle
    const a11yToggle = document.getElementById('a11y-toggle');
    const a11yPanel = document.getElementById('a11y-panel');
    
    if (a11yToggle && a11yPanel) {
        a11yToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = a11yPanel.style.display === 'block';
            a11yPanel.style.display = isVisible ? 'none' : 'block';
            a11yPanel.setAttribute('aria-hidden', isVisible ? 'true' : 'false');
        });
        
        document.addEventListener('click', (e) => {
            if (!a11yPanel.contains(e.target) && e.target !== a11yToggle) {
                a11yPanel.style.display = 'none';
                a11yPanel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // Toggle navigation classes on target elements
    window.toggleAccessibilityClass = function(className) {
        const body = document.body;
        body.classList.toggle(className);
        // Persist preferences in localStorage
        const isActive = body.classList.contains(className);
        localStorage.setItem(className, isActive ? 'true' : 'false');
    };

    // Load persisted accessibility settings
    const accessibilityClasses = [
        'contrast-high', 'contrast-rev', 'grayscale-mode', 
        'font-readable', 'underline-links', 'underline-headers', 
        'big-cursor-white', 'big-cursor-black', 'reduce-motion'
    ];
    
    accessibilityClasses.forEach(cls => {
        if (localStorage.getItem(cls) === 'true') {
            document.body.classList.add(cls);
        }
    });

    // Font size scaling
    let currentFontSizePercent = parseInt(localStorage.getItem('font-size-scale') || '100');
    document.body.style.fontSize = `${currentFontSizePercent / 100}rem`;

    window.adjustTextSize = function(direction) {
        if (direction === 'inc' && currentFontSizePercent < 130) {
            currentFontSizePercent += 10;
        } else if (direction === 'dec' && currentFontSizePercent > 80) {
            currentFontSizePercent -= 10;
        }
        document.body.style.fontSize = `${currentFontSizePercent / 100}rem`;
        localStorage.setItem('font-size-scale', currentFontSizePercent.toString());
    };

    // Reset Accessibility settings
    window.resetAccessibility = function() {
        accessibilityClasses.forEach(cls => {
            document.body.classList.remove(cls);
            localStorage.removeItem(cls);
        });
        currentFontSizePercent = 100;
        document.body.style.fontSize = '1rem';
        localStorage.removeItem('font-size-scale');
    };

    // Responsive Mobile Menu
    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const navMenuWrapper = document.querySelector('.nav-menu-wrapper');
    
    if (menuToggleBtn && navMenuWrapper) {
        menuToggleBtn.addEventListener('click', () => {
            const isExpanded = navMenuWrapper.style.display === 'block';
            navMenuWrapper.style.display = isExpanded ? 'none' : 'block';
        });
    }

    // Interactive India Map SVG triggers
    const indiaStates = document.querySelectorAll('.india-state-path');
    const mapDetailsCard = document.getElementById('map-details-card');
    
    if (indiaStates.length > 0 && mapDetailsCard) {
        indiaStates.forEach(state => {
            state.addEventListener('click', () => {
                // Clear previous active states
                indiaStates.forEach(s => s.classList.remove('active-state'));
                state.classList.add('active-state');
                
                const stateName = state.getAttribute('data-name');
                
                // Skeleton loader while fetching
                mapDetailsCard.innerHTML = `
                    <h4 class="map-detail-heading">${stateName}</h4>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1.25rem;">
                        <div style="height:1rem;background:rgba(255,255,255,0.08);border-radius:4px;width:72%;" class="skeleton-pulse"></div>
                        <div style="height:1rem;background:rgba(255,255,255,0.08);border-radius:4px;width:48%;" class="skeleton-pulse"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;">
                        <div style="height:2.8rem;background:rgba(255,255,255,0.05);border-radius:8px;" class="skeleton-pulse"></div>
                        <div style="height:2.8rem;background:rgba(255,255,255,0.05);border-radius:8px;" class="skeleton-pulse"></div>
                        <div style="height:2.8rem;background:rgba(255,255,255,0.05);border-radius:8px;" class="skeleton-pulse"></div>
                        <div style="height:2.8rem;background:rgba(255,255,255,0.05);border-radius:8px;" class="skeleton-pulse"></div>
                    </div>
                `;
                
                // Fetch live metrics
                fetch(`api/state-summary.php?state=${encodeURIComponent(stateName)}`)
                    .then(r => { if (!r.ok) throw new Error('fail'); return r.json(); })
                    .then(data => {
                        if (data.error) {
                            mapDetailsCard.innerHTML = `
                                <h4 class="map-detail-heading">${stateName}</h4>
                                <p class="map-detail-body" style="color:#ff8a95;">Failed to load statistics.</p>`;
                            return;
                        }

                        const pendingRow = data.role ? `
                            <div class="map-stat-row">
                                <span>Pending Approval</span>
                                <strong style="color:#F4B942;">${data.pending}</strong>
                            </div>` : '';

                        const adminActions = data.can_view_details ? `
                            <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-top:1.25rem;">
                                <a href="${data.details_url}" class="btn" style="font-size:0.82rem;padding:0.55rem 1.1rem;border-radius:999px;background:#24C27A;color:#0B1B3D;font-weight:700;text-decoration:none;">View Directory</a>
                                ${data.export_url ? `<a href="${data.export_url}" class="btn" style="font-size:0.82rem;padding:0.55rem 1.1rem;border-radius:999px;border:1px solid rgba(255,255,255,0.2);color:#FAF7F0;font-weight:700;text-decoration:none;">Export CSV</a>` : ''}
                            </div>` : `<span class="map-detail-badge">‚óè National Registry System</span>`;

                        mapDetailsCard.innerHTML = `
                            <h4 class="map-detail-heading">${data.state}</h4>
                            <div style="margin-bottom:1.25rem;">
                                <div class="map-stat-row">
                                    <span>Approved Athletes</span>
                                    <strong style="color:#F4B942;">${data.approved}</strong>
                                </div>
                                ${pendingRow}
                            </div>
                            <p style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;color:rgba(250,247,240,0.6);margin-bottom:0.6rem;">Classification Split</p>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1rem;">
                                ${['BC1','BC2','BC3','BC4'].map(c => `
                                <div style="background:rgba(255,255,255,0.05);padding:0.5rem 0.6rem;border-radius:8px;text-align:center;">
                                    <div style="font-size:0.72rem;opacity:0.65;">${c}</div>
                                    <strong style="font-size:1rem;color:#FAF7F0;">${data.classifications[c.toLowerCase()]}</strong>
                                </div>`).join('')}
                            </div>
                            ${adminActions}
                        `;
                    })
                    .catch(() => {
                        mapDetailsCard.innerHTML = `
                            <h4 class="map-detail-heading">${stateName}</h4>
                            <p class="map-detail-body" style="color:#ff8a95;">Failed to load statistics.</p>`;
                    });
            });
        });
    }

    // Dynamic State Association Filtering
    window.filterAssociations = function() {
        const stateSelect = document.getElementById('reg-state');
        const assocSelect = document.getElementById('reg-association');
        if (!stateSelect || !assocSelect) return;

        const selectedStateId = stateSelect.value;
        
        // Reset dropdown
        assocSelect.innerHTML = '<option value="">Select Association</option>';

        if (!selectedStateId) {
            assocSelect.innerHTML = '<option value="">Select Association (Requires State First)</option>';
            return;
        }

        // Filter associations matching selected state from php pre-loaded array
        const filtered = window.associationsData.filter(a => a.state_id == selectedStateId);
        
        if (filtered.length === 0) {
            assocSelect.innerHTML = '<option value="0">Independent Registry (No State Association)</option>';
        } else {
            filtered.forEach(assoc => {
                const opt = document.createElement('option');
                opt.value = assoc.id;
                opt.textContent = assoc.association_name;
                assocSelect.appendChild(opt);
            });
        }
    };

    // Public Athlete Registration Form Wizard logic
    let currentStep = 1;
    const wizardForm = document.getElementById('public-reg-form');
    
    // Auto-save Draft listener
    if (wizardForm) {
        const inputs = wizardForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                const draftData = {};
                inputs.forEach(inp => {
                    if (inp.name) {
                        draftData[inp.name] = inp.value;
                    }
                });
                localStorage.setItem('bsfi_registration_draft', JSON.stringify(draftData));
            });
        });

        // Load Draft
        const savedDraft = localStorage.getItem('bsfi_registration_draft');
        if (savedDraft) {
            try {
                const draftData = JSON.parse(savedDraft);
                Object.keys(draftData).forEach(key => {
                    const inp = wizardForm.querySelector(`[name="${key}"]`);
                    if (inp) {
                        inp.value = draftData[key];
                    }
                });
                // Trigger filter manually to restore associations
                window.filterAssociations();
                if (draftData['state_association_id']) {
                    const assocField = document.getElementById('reg-association');
                    if (assocField) {
                        assocField.value = draftData['state_association_id'];
                    }
                }
            } catch (e) {
                // Ignore parsing errors
            }
        }
    }

    window.navigateWizard = function(direction) {
        const stepBlocks = document.querySelectorAll('.wizard-step-block');
        const stepNodes = document.querySelectorAll('.wizard-step-node');
        
        if (direction === 'next' && currentStep < 5) {
            // validate current step inputs
            const currentInputs = stepBlocks[currentStep - 1].querySelectorAll('input[required], select[required]');
            let isValid = true;
            currentInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--boccia-red)';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                alert("Please fill all required fields before proceeding.");
                return;
            }
            
            currentStep++;
        } else if (direction === 'prev' && currentStep > 1) {
            currentStep--;
        }
        
        // Update view
        stepBlocks.forEach((block, idx) => {
            block.style.display = (idx === currentStep - 1) ? 'block' : 'none';
        });
        
        stepNodes.forEach((node, idx) => {
            if (idx === currentStep - 1) {
                node.classList.add('active');
            } else {
                node.classList.remove('active');
            }
        });
        
        // Review step population
        if (currentStep === 5) {
            populateReviewSummary();
        }
    };
    
    function populateReviewSummary() {
        const summaryBox = document.getElementById('reg-summary');
        if (summaryBox) {
            const stateField = document.getElementById('reg-state');
            const assocField = document.getElementById('reg-association');
            
            const stateText = stateField ? stateField.options[stateField.selectedIndex].text : 'N/A';
            const assocText = assocField ? assocField.options[assocField.selectedIndex].text : 'Independent';
            
            summaryBox.innerHTML = `
                <p><strong>Full Name:</strong> ${document.getElementById('reg-name').value}</p>
                <p><strong>Gender:</strong> ${document.getElementById('reg-gender').value}</p>
                <p><strong>DOB:</strong> ${document.getElementById('reg-dob').value}</p>
                <p><strong>Email:</strong> ${document.getElementById('reg-email').value}</p>
                <p><strong>Representing State:</strong> ${stateText}</p>
                <p><strong>State Association:</strong> ${assocText}</p>
                <p><strong>Classification:</strong> ${document.getElementById('reg-classification').value}</p>
            `;
        }
    }
});

/* ----------------------------------------
   COUNT-UP ANIMATION ó Stats Bar
---------------------------------------- */
(function () {
    function animateCountUp(el, delay) {
        var target   = parseInt(el.dataset.target, 10);
        var suffix   = el.dataset.suffix || '';
        var duration = 1800;
        var startTime = null;

        setTimeout(function () {
            el.classList.add('counted');

            function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var elapsed  = timestamp - startTime;
                var progress = Math.min(elapsed / duration, 1);
                var current  = Math.round(easeOutCubic(progress) * target);
                el.textContent = current + suffix;
                if (progress < 1) { requestAnimationFrame(step); }
                else { el.textContent = target + suffix; }
            }
            requestAnimationFrame(step);
        }, delay);
    }

    var statsSection = document.getElementById('stats-bar');
    if (!statsSection) return;
    var statNumbers = statsSection.querySelectorAll('.stat-number');
    var triggered   = false;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && !triggered) {
                triggered = true;
                observer.disconnect();
                statNumbers.forEach(function (el, i) { animateCountUp(el, i * 120); });
            }
        });
    }, { threshold: 0.3 });

    observer.observe(statsSection);
})();
