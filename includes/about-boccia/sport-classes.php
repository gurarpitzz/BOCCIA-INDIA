<?php
// includes/about-boccia/sport-classes.php - Sport Classes Section
?>
<section class="sport-classes">
    <div class="container">
        
        <!-- Dedicated Classification Intro Block -->
        <div class="classes-intro scroll-reveal text-center">
            <span class="about-section-eyebrow">World Boccia Classification</span>
            <h2 class="classes-main-title">SPORT CLASSES</h2>
            <p class="classes-lead-caption">FOR COMPETITION PURPOSES, ATHLETES ARE CLASSIFIED INTO ONE OF FOUR SPORT CLASSES</p>
            <p class="classes-desc mx-auto" style="max-width: 700px;">To ensure fair competition, classification groups athletes based on functional ability. Athletes undergo specialized evaluations to assign their profile category.</p>
        </div>
        
        <div class="classes-grid">
            
            <!-- BC1: Image Left, Content Right -->
            <div class="class-row align-items-center scroll-reveal">
                <div class="class-visual">
                    <div class="class-video-container class-slider-wrapper" id="bc1-slider">
                        <div class="class-slides">
                            <div class="class-slide active">
                                <img src="about boccia/category/BC1/bc1.jpg" alt="Gayithri HM - BC1 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Gayithri HM — International Silver Medalist</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/BC1/sandhya_national_gold_medlist_2.jpg" alt="Sandhya Bhumij - BC1 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Sandhya Bhumij — National Gold Medalist</div>
                            </div>
                        </div>
                        <button class="slider-arrow prev" onclick="switchClassSlide('bc1-slider', -1)" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                        <button class="slider-arrow next" onclick="switchClassSlide('bc1-slider', 1)" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                        <div class="slider-dots">
                            <span class="dot active" onclick="setClassSlide('bc1-slider', 0)"></span>
                            <span class="dot" onclick="setClassSlide('bc1-slider', 1)"></span>
                        </div>
                    </div>
                </div>
                <div class="class-details">
                    <div class="class-glass-card">
                        <h3 class="class-name">BC1</h3>
                        <p class="class-summary">Players throw the ball with their hand or foot. They may compete with an assistant who stays outside the player's box to assist with tasks such as wheelchair adjustment or passing the ball.</p>
                        <div class="class-chips">
                            <span class="class-chip"><span class="chip-check">✓</span> Assistant Allowed</span>
                            <span class="class-chip"><span class="chip-check">✓</span> Wheelchair Users</span>
                            <span class="class-chip"><span class="chip-check">✓</span> High Support Needs</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- BC2: Content Left, Image Right -->
            <div class="class-row row-reverse align-items-center scroll-reveal">
                <div class="class-details">
                    <div class="class-glass-card">
                        <h3 class="class-name">BC2</h3>
                        <p class="class-summary">Players throw the ball with their hand. They play independently and are not allowed an assistant inside the court. Wheelchair stability and throwing motion are fully autonomous.</p>
                        <div class="class-chips">
                            <span class="class-chip"><span class="chip-check">✓</span> Independent Play</span>
                            <span class="class-chip"><span class="chip-check">✓</span> No Assistant</span>
                            <span class="class-chip"><span class="chip-check">✓</span> Hand Throw</span>
                        </div>
                    </div>
                </div>
                <div class="class-visual">
                    <div class="class-video-container class-slider-wrapper" id="bc2-slider">
                        <div class="class-slides">
                            <div class="class-slide active">
                                <img src="about boccia/category/bc2/bc2.jpg" alt="Govindbhai Chaudhary - BC2 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Govindbhai Chaudhary — National Champion</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc2/vyom-pawa.jpg" alt="Vyom Bharat Pawa - BC2 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Vyom Bharat Pawa — National Medallist</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc2/lakkshhya-gupta.jpg" alt="Lakkshhya Gupta - BC2 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Lakkshhya Gupta — National Medallist</div>
                            </div>
                        </div>
                        <button class="slider-arrow prev" onclick="switchClassSlide('bc2-slider', -1)" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                        <button class="slider-arrow next" onclick="switchClassSlide('bc2-slider', 1)" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                        <div class="slider-dots">
                            <span class="dot active" onclick="setClassSlide('bc2-slider', 0)"></span>
                            <span class="dot" onclick="setClassSlide('bc2-slider', 1)"></span>
                            <span class="dot" onclick="setClassSlide('bc2-slider', 2)"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- BC3: Image Left, Content Right -->
            <div class="class-row align-items-center scroll-reveal">
                <div class="class-visual">
                    <div class="class-video-container class-slider-wrapper" id="bc3-slider">
                        <div class="class-slides">
                            <div class="class-slide active">
                                <img src="about boccia/category/bc3/bc3.jpg" alt="Ajeya Raj & Anjali Devi - BC3 Category Indian Athletes" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Ajeya Raj & Anjali Devi — International Medalists</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc3/sarita_dwivedi.jpg" alt="Sarita Dwivedi - BC3 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Sarita Dwivedi — International Medalist</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc3/raghav_mishra_international_player.jpg" alt="Raghav Mishra - BC3 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Raghav Mishra — International Player</div>
                            </div>
                        </div>
                        <button class="slider-arrow prev" onclick="switchClassSlide('bc3-slider', -1)" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                        <button class="slider-arrow next" onclick="switchClassSlide('bc3-slider', 1)" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                        <div class="slider-dots">
                            <span class="dot active" onclick="setClassSlide('bc3-slider', 0)"></span>
                            <span class="dot" onclick="setClassSlide('bc3-slider', 1)"></span>
                            <span class="dot" onclick="setClassSlide('bc3-slider', 2)"></span>
                        </div>
                    </div>
                </div>
                <div class="class-details">
                    <div class="class-glass-card">
                        <h3 class="class-name">BC3</h3>
                        <p class="class-summary">Players have severe locomotor dysfunction in all four limbs. They use an assistive ramp to propel the ball. A ramp assistant is allowed, but must keep their back to the court at all times.</p>
                        <div class="class-chips">
                            <span class="class-chip"><span class="chip-check">✓</span> Ramp Usage</span>
                            <span class="class-chip"><span class="chip-check">✓</span> Assistant Allowed</span>
                            <span class="class-chip"><span class="chip-check">✓</span> Severe Locomotor Dysfunction</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- BC4: Content Left, Image Right -->
            <div class="class-row row-reverse align-items-center scroll-reveal">
                <div class="class-details">
                    <div class="class-glass-card">
                        <h3 class="class-name">BC4</h3>
                        <p class="class-summary">Players have severe locomotor dysfunctions in all four limbs, but show sufficient dexterity to throw the ball. They play independently without assistants, demonstrating high physical skill.</p>
                        <div class="class-chips">
                            <span class="class-chip"><span class="chip-check">✓</span> Independent Throw</span>
                            <span class="class-chip"><span class="chip-check">✓</span> No Assistant</span>
                            <span class="class-chip"><span class="chip-check">✓</span> Severe Impairment</span>
                        </div>
                    </div>
                </div>
                <div class="class-visual">
                    <div class="class-video-container class-slider-wrapper" id="bc4-slider">
                        <div class="class-slides">
                            <div class="class-slide active">
                                <img src="about boccia/category/bc4/bc4.jpg" alt="Pooja Gupta & Jatin Kumar - BC4 Category Indian Athletes" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Pooja Gupta & Jatin Kumar — International Pair</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc4/jatin_international_medlist.jpg" alt="Jatin Kumar - BC4 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Jatin Kumar — International Medalist</div>
                            </div>
                            <div class="class-slide">
                                <img src="about boccia/category/bc4/usha_kiran.jpg" alt="Usha Kiran - BC4 Category Indian Athlete" class="class-loop-image" loading="lazy">
                                <div class="player-badge">🇮🇳 Usha Kiran — National Gold Medalist</div>
                            </div>
                        </div>
                        <button class="slider-arrow prev" onclick="switchClassSlide('bc4-slider', -1)" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                        <button class="slider-arrow next" onclick="switchClassSlide('bc4-slider', 1)" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                        <div class="slider-dots">
                            <span class="dot active" onclick="setClassSlide('bc4-slider', 0)"></span>
                            <span class="dot" onclick="setClassSlide('bc4-slider', 1)"></span>
                            <span class="dot" onclick="setClassSlide('bc4-slider', 2)"></span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<script>
if (typeof switchClassSlide !== 'function') {
    function switchClassSlide(sliderId, dir) {
        const slider = document.getElementById(sliderId);
        if (!slider) return;
        const slides = slider.querySelectorAll('.class-slide');
        const dots = slider.querySelectorAll('.slider-dots .dot');
        let activeIdx = 0;
        slides.forEach((slide, idx) => {
            if (slide.classList.contains('active')) activeIdx = idx;
        });
        let newIdx = (activeIdx + dir + slides.length) % slides.length;
        setClassSlide(sliderId, newIdx);
    }

    function setClassSlide(sliderId, targetIdx) {
        const slider = document.getElementById(sliderId);
        if (!slider) return;
        const slides = slider.querySelectorAll('.class-slide');
        const dots = slider.querySelectorAll('.slider-dots .dot');
        slides.forEach((slide, idx) => {
            slide.classList.toggle('active', idx === targetIdx);
        });
        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === targetIdx);
        });
    }
}
</script>

