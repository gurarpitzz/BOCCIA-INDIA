// app.js - Boccia India main application logic

document.addEventListener('DOMContentLoaded', () => {
    
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
                                <p class="map-detail-body" style="color:#ff8a95;">Failed to load details.</p>`;
                            return;
                        }

                        if (data.has_association) {
                            let statusBadge = '';
                            let statusColor = '#ff8a95';
                            if (data.status === 'Registered') {
                                statusBadge = '🟢 Registered';
                                statusColor = '#24C27A';
                            } else if (data.status === 'Under Process') {
                                statusBadge = '🟡 Under Process';
                                statusColor = '#F4B942';
                            } else {
                                statusBadge = '🔴 Not Available';
                            }

                            mapDetailsCard.innerHTML = `
                                <h4 class="map-detail-heading">${data.state}</h4>
                                <div style="margin-bottom:1.25rem;">
                                    <div class="map-stat-row" style="margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                                        <span style="opacity:0.8; font-size:0.85rem;">State Association</span>
                                        <strong style="color:#FAF7F0; font-size:0.95rem; text-align:right; max-width:60%; word-break:break-word;">${data.association_name}</strong>
                                    </div>
                                    <div class="map-stat-row" style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="opacity:0.8; font-size:0.85rem;">Affiliation Status</span>
                                        <strong style="color:${statusColor}; font-size:0.95rem;">${statusBadge}</strong>
                                    </div>
                                </div>
                                <div class="map-detail-grid">
                                    <div style="background:rgba(255,255,255,0.05);padding:0.5rem 0.8rem;border-radius:8px;text-align:left;">
                                        <div style="font-size:0.68rem;opacity:0.6;margin-bottom:0.15rem;">Contact Person</div>
                                        <strong style="font-size:0.85rem;color:#FAF7F0;display:block;word-break:break-word;line-height:1.3;" title="${data.contact_person}">${data.contact_person}</strong>
                                    </div>
                                    <div style="background:rgba(255,255,255,0.05);padding:0.5rem 0.8rem;border-radius:8px;text-align:left;">
                                        <div style="font-size:0.68rem;opacity:0.6;margin-bottom:0.15rem;">Official Email</div>
                                        <strong style="font-size:0.85rem;color:#FAF7F0;display:block;word-break:break-all;line-height:1.3;" title="${data.email}">${data.email}</strong>
                                    </div>
                                    <div style="background:rgba(255,255,255,0.05);padding:0.5rem 0.8rem;border-radius:8px;text-align:left;">
                                        <div style="font-size:0.68rem;opacity:0.6;margin-bottom:0.15rem;">Contact Number</div>
                                        <strong style="font-size:0.85rem;color:#FAF7F0;display:block;word-break:break-word;line-height:1.3;" title="${data.phone}">${data.phone}</strong>
                                    </div>
                                    <div style="background:rgba(255,255,255,0.05);padding:0.5rem 0.8rem;border-radius:8px;text-align:left;">
                                        <div style="font-size:0.68rem;opacity:0.6;margin-bottom:0.15rem;">Association Status</div>
                                        <strong style="font-size:0.85rem;color:${statusColor};display:block;word-break:break-word;line-height:1.3;" title="${data.status}">${data.status}</strong>
                                    </div>
                                </div>
                                <span class="map-detail-badge">● Recognized by BSFI</span>
                            `;
                        } else {
                            mapDetailsCard.innerHTML = `
                                <h4 class="map-detail-heading">${data.state}</h4>
                                <div style="margin-bottom:1.25rem;">
                                    <div class="map-stat-row" style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="opacity:0.8; font-size:0.85rem;">Affiliation Status</span>
                                        <strong style="color:#ff8a95; font-size:0.95rem;">🔴 No Registered Association</strong>
                                    </div>
                                </div>
                                <p class="map-detail-body" style="font-size:0.85rem; line-height:1.45; color:rgba(250,247,240,0.75); margin-bottom:1.25rem;">
                                    No official Boccia State Association has been registered for this state yet. Please contact the Boccia Sports Federation of India for further information.
                                </p>
                                <span class="map-detail-badge">● Recognized by BSFI</span>
                            `;
                        }
                    })
                    .catch(() => {
                        mapDetailsCard.innerHTML = `
                            <h4 class="map-detail-heading">${stateName}</h4>
                            <p class="map-detail-body" style="color:#ff8a95;">Failed to load details.</p>`;
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
   COUNT-UP ANIMATION — Stats Bar
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

/* ----------------------------------------
   ABOUT BOCCIA REDESIGN INTERACTIVITY
   ---------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Scroll Reveal System using IntersectionObserver
    const revealElements = document.querySelectorAll('.scroll-reveal');
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });
    
    revealElements.forEach(el => revealObserver.observe(el));

    // 2. Overview Content Tabs Switcher
    const tabButtons = document.querySelectorAll('.overview-tab-btn');
    const tabPanes = document.querySelectorAll('.overview-tab-pane');
    
    if (tabButtons.length > 0) {
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.getAttribute('data-tab');
                
                // Toggle active classes on buttons
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Toggle active classes on panes
                tabPanes.forEach(pane => {
                    if (pane.id === `tab-${targetTab}`) {
                        pane.classList.add('active');
                    } else {
                        pane.classList.remove('active');
                    }
                });
            });
        });
    }

    // 3. Performance Lazy YouTube iframe loader on click
    const lazyYTs = document.querySelectorAll('.youtube-lazy-load');
    lazyYTs.forEach(wrapper => {
        wrapper.addEventListener('click', () => {
            const ytId = wrapper.getAttribute('data-youtube-id');
            if (ytId) {
                const iframe = document.createElement('iframe');
                iframe.setAttribute('src', `https://www.youtube.com/embed/${ytId}?autoplay=1`);
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                iframe.setAttribute('allowfullscreen', 'true');
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.position = 'absolute';
                iframe.style.inset = '0';
                
                wrapper.innerHTML = '';
                wrapper.appendChild(iframe);
            }
        });
    });

    // 4. On-scroll Stats Count-up Observer for Boccia in India section
    const statsBar = document.getElementById('india-stats-bar');
    if (statsBar) {
        const statNums = statsBar.querySelectorAll('.stat-num');
        let animated = false;
        
        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !animated) {
                    animated = true;
                    observer.unobserve(statsBar);
                    
                    statNums.forEach(el => {
                        const target = parseInt(el.getAttribute('data-val'), 10);
                        const duration = 1500;
                        const startTime = performance.now();
                        
                        function count(now) {
                            const elapsed = now - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const current = Math.floor(progress * target);
                            el.textContent = current;
                            if (progress < 1) {
                                requestAnimationFrame(count);
                            } else {
                                el.textContent = target;
                            }
                        }
                        requestAnimationFrame(count);
                    });
                }
            });
        }, { threshold: 0.25 });
        
        statsObserver.observe(statsBar);
    }
});
