<?php
// includes/about-boccia/overview.php - Overview Section with Tabs
?>
<section class="about-overview" style="background-image: url('about boccia/overview bg.png');">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left: Interactive Content Tabs -->
            <div class="col-lg-6 scroll-reveal">
                <span class="about-section-eyebrow">The Sport</span>
                <h2 class="about-section-title">Overview &amp; Origins</h2>
                
                <!-- Tab Headers -->
                <div class="overview-tabs-nav">
                    <button class="overview-tab-btn active" data-tab="overview">Overview</button>
                    <button class="overview-tab-btn" data-tab="history">History</button>
                    <button class="overview-tab-btn" data-tab="reach">Global Reach</button>
                    <button class="overview-tab-btn" data-tab="india">Boccia in India</button>
                </div>
                
                <!-- Tab Contents Container -->
                <div class="overview-tabs-content">
                    
                    <!-- Tab: Overview -->
                    <div class="overview-tab-pane active" id="tab-overview">
                        <h4 class="tab-pane-heading">Precision. Strategy. Inclusion.</h4>
                        <p>Boccia is a precision ball sport designed specifically for athletes with severe physical disabilities affecting motor skills. Recognized as one of the most inclusive Paralympic sports, Boccia provides individuals with high support needs an opportunity to compete at local, national, and international levels.</p>
                        <p>Played indoors on a flat court, Boccia involves athletes throwing, kicking, or using an assistive ramp to propel leather balls as close as possible to a target ball known as the "jack." The objective is simple, yet the game demands exceptional skill, planning, and control.</p>
                    </div>
                    
                    <!-- Tab: History -->
                    <div class="overview-tab-pane" id="tab-history">
                        <h4 class="tab-pane-heading">A Rich Paralympic Legacy</h4>
                        <p>Boccia originated in Europe during the 1970s as a competitive sport for individuals with cerebral palsy. Over time, it evolved to include athletes with a wider range of severe physical disabilities.</p>
                        <p>The sport made its Paralympic debut at the 1984 Paralympic Games and has since grown into a globally recognized discipline governed internationally by World Boccia.</p>
                    </div>
                    
                    <!-- Tab: Reach -->
                    <div class="overview-tab-pane" id="tab-reach">
                        <h4 class="tab-pane-heading">Expanding Boundaries Worldwide</h4>
                        <p>Today, Boccia is played in more than 70 countries and continues to expand its reach through grassroots development programs, national championships, and international competitions.</p>
                        <p>As a key component of the Paralympic movement, World Boccia works to bring this highly accessible sport to new regions, establishing training centers, certifying coaches, and supporting local organizations.</p>
                    </div>
                    
                    <!-- Tab: India -->
                    <div class="overview-tab-pane" id="tab-india">
                        <h4 class="tab-pane-heading">Empowering Indian Athletes</h4>
                        <p>Boccia has emerged as an important adaptive sport in India, creating opportunities for persons with severe physical disabilities to participate in competitive sports and lead active, empowered lives. Through the efforts of dedicated organizations, coaches, volunteers, and advocates, awareness and participation have steadily increased.</p>
                        <p>Indian athletes have demonstrated remarkable talent and determination, representing the nation in international competitions and contributing to the growth of the sport. Development programs continue to introduce Boccia to new players while promoting accessibility.</p>
                    </div>
                    
                </div>
            </div>
            
            <!-- Right: Premium Video Player -->
            <div class="col-lg-6 scroll-reveal">
                <div class="overview-video-card">
                    <div class="overview-video-wrapper">
                        <!-- Custom YouTube Embed wrapper. Loads iframe only on interaction to save load performance -->
                        <div class="youtube-lazy-load" data-youtube-id="itPWqcx7xBg">
                            <div class="yt-play-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                            <!-- Background image to use as fallback/poster before video loads -->
                            <img src="https://img.youtube.com/vi/itPWqcx7xBg/hqdefault.jpg" alt="Watch overview video" class="yt-poster" loading="lazy">
                        </div>
                    </div>
                    <span class="video-caption">🎥 Official Introduction to Para Boccia</span>
                </div>
            </div>
        </div>
    </div>
</section>
