<?php
// includes/about-boccia/watch-learn.php - Watch & Learn Section
$featured_video_id = 'itPWqcx7xBg';
$featured_title = 'Official Introduction to Para Boccia - Rules and Gameplay';
$featured_duration = '05:32';

$more_videos = [
    ['id' => '2nXhoc2OE-g', 'title' => 'Boccia Match Strategy & Precision throwing', 'duration' => '04:15'],
    ['id' => 'eYXL_782-Lo', 'title' => 'Training Drills & Muscle Memory Techniques', 'duration' => '03:40'],
    ['id' => 'f5CmEab8tqU', 'title' => 'Ramp Mechanics and Assistive Devices', 'duration' => '05:12'],
    ['id' => 'hrwvC6YlMNQ', 'title' => 'Tactical Planning & Jack Positioning', 'duration' => '06:02'],
    ['id' => 'PX_6tMHwHlU', 'title' => 'National Championship Match Highlights', 'duration' => '08:24'],
    ['id' => 'NQwhQFhO9_o', 'title' => 'Athlete Empowerment and Inclusion Stories', 'duration' => '05:50'],
    ['id' => 'txrFrRWFGUIs', 'title' => 'Rules of Boccia: Official Guide', 'duration' => '07:18']
];
?>
<section class="watch-learn">
    <div class="container">
        <div class="section-header text-center scroll-reveal">
            <span class="about-section-eyebrow" style="color: #60a5fa;">Resources</span>
            <h2 class="about-section-title" style="color: #ffffff;">Watch &amp; Learn</h2>
            <p class="about-section-desc mx-auto" style="max-width: 600px; color: #cbd5e1;">Explore official training clips, gameplay rule guides, and national championship highlight reels.</p>
        </div>
        
        <!-- Featured Video (70% Width Desktop) -->
        <div class="watch-featured-wrapper scroll-reveal">
            <a href="https://www.youtube.com/watch?v=<?php echo $featured_video_id; ?>" class="glightbox featured-video-card" aria-label="Play Featured Video: <?php echo htmlspecialchars($featured_title); ?>">
                <div class="featured-video-thumbnail">
                    <img src="https://img.youtube.com/vi/<?php echo $featured_video_id; ?>/hqdefault.jpg" alt="Featured video thumbnail" loading="lazy">
                    <div class="video-overlay-tint"></div>
                    <div class="featured-play-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <span class="video-duration-badge"><?php echo $featured_duration; ?></span>
                </div>
                <div class="featured-video-info">
                    <span class="video-badge-tag">Featured Video</span>
                    <h3 class="featured-video-title"><?php echo htmlspecialchars($featured_title); ?></h3>
                    <p class="featured-video-desc">Get a complete overview of the rules, court dimensions, classification standards, and inclusive strategy that defines Para Boccia.</p>
                </div>
            </a>
        </div>
        
        <!-- More Videos Grid (7 Cards) -->
        <div class="watch-grid-header scroll-reveal">
            <h3 class="watch-grid-title">More Videos</h3>
        </div>
        
        <div class="row g-4 watch-more-grid">
            <?php foreach ($more_videos as $vid): ?>
            <div class="col-12 col-md-6 col-lg-4 d-flex align-items-stretch scroll-reveal">
                <a href="https://www.youtube.com/watch?v=<?php echo $vid['id']; ?>" class="glightbox watch-grid-card w-100" aria-label="Play video: <?php echo htmlspecialchars($vid['title']); ?>">
                    <div class="watch-card-thumbnail">
                        <img src="https://img.youtube.com/vi/<?php echo $vid['id']; ?>/mqdefault.jpg" alt="Video thumbnail" loading="lazy">
                        <div class="video-overlay-tint"></div>
                        <div class="grid-play-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                        <span class="video-duration-badge"><?php echo $vid['duration']; ?></span>
                    </div>
                    <div class="watch-card-info">
                        <h4 class="watch-card-title"><?php echo htmlspecialchars($vid['title']); ?></h4>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
    </div>
</section>
