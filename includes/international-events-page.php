<?php
// includes/international-events-page.php - Frontend International Events schedule viewer

$internationalSchedules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE active = 1 AND competition_scope = 'International' ORDER BY start_date ASC, id ASC");
    $stmt->execute();
    $internationalSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback empty list
}

$page_title = "International Events | Boccia India";
$meta_desc = "Official international schedules, championships, and competition dates featuring Indian para-boccia athletes.";
$canonical_url = "page.php?section=competitions&slug=international-events";

include __DIR__ . '/header.php';
?>

<div class="national-events-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.88) 35%, rgba(7, 25, 84, 0.65) 55%, rgba(7, 25, 84, 0.35) 75%, transparent 100%), url('<?php echo htmlspecialchars($relative_prefix); ?>board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Competitions --</span>
                <h1 class="board-hero-title">International Events</h1>
                <p class="board-hero-text">
                    Official international championships, tournaments, and world ranking events featuring Indian para-boccia athletes.
                    <br>
                    <span style="color: var(--boccia-saffron, #FF9933); font-weight: 600;">Follow India's elite athletes on the global stage.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="national-events-content-section">
        <div class="container">
            <?php if (count($internationalSchedules) > 0): ?>
                <div class="schedule-table-card">
                    <!-- Desktop Table view -->
                    <div class="schedule-table-wrapper" style="overflow-x: auto; padding-bottom: 1rem;">
                        <div class="schedule-table" style="min-width: 800px;">
                            <div class="schedule-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
                                <?php 
                                $rowIdx = 1;
                                foreach ($internationalSchedules as $sched): 
                                    $borderColor = ($rowIdx % 2 !== 0) ? '#081B4B' : '#FF9933';
                                ?>
                                <div class="schedule-row-new" style="background: #ffffff; border: 2px solid <?php echo $borderColor; ?>; border-radius: 18px; display: grid; grid-template-columns: 100px 2.5fr 2fr 3fr; padding: 1.5rem 2.25rem; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
                                    
                                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                                        <span style="font-size: 2.2rem; font-weight: 800; color: #081B4B; font-family: var(--font-heading); min-width: 45px; letter-spacing: -0.02em;"><?php echo str_pad($rowIdx, 2, '0', STR_PAD_LEFT); ?></span>
                                        <span style="width: 1.5px; height: 44px; background: rgba(8, 27, 75, 0.15); display: inline-block;"></span>
                                    </div>

                                    <div>
                                        <div style="font-weight: 700; font-size: 1.15rem; color: #081B4B; font-family: var(--font-heading);"><?php echo htmlspecialchars($sched['discipline']); ?></div>
                                        <?php if ($sched['event_type']): ?>
                                        <div style="font-size: 0.85rem; color: #6b82b8; font-weight: 500; margin-top: 0.2rem;"><?php echo htmlspecialchars($sched['event_type']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="font-size: 1.05rem; font-weight: 700; color: #FF9933;"><?php echo htmlspecialchars($sched['date_text']); ?></div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;">
                                        <span style="font-size: 1.05rem; font-weight: 500; color: #3b4a6b;"><?php echo htmlspecialchars($sched['venue']); ?></span>
                                        <?php if (($sched['registration_mode'] ?? 'external') === 'internal'): ?>
                                            <a href="<?php echo htmlspecialchars($relative_prefix); ?>event-registration.php?event_id=<?php echo $sched['id']; ?>" style="background: #081B4B; color: #fff; padding: 0.5rem 1.25rem; border-radius: 999px; font-size: 0.85rem; font-weight: bold; text-decoration: none; transition: background 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#FF9933'" onmouseout="this.style.background='#081B4B'">Register</a>
                                        <?php elseif (($sched['registration_mode'] ?? 'external') === 'external' && $sched['registration_link']): ?>
                                            <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" style="background: #081B4B; color: #fff; padding: 0.5rem 1.25rem; border-radius: 999px; font-size: 0.85rem; font-weight: bold; text-decoration: none; transition: background 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#FF9933'" onmouseout="this.style.background='#081B4B'">Register</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php $rowIdx++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Cards view -->
                    <div class="schedule-mobile-cards" style="margin-top: 1rem;">
                        <?php 
                        $rowIdx = 1;
                        foreach ($internationalSchedules as $sched): 
                            $borderColor = ($rowIdx % 2 !== 0) ? '#081B4B' : '#FF9933';
                        ?>
                        <div class="schedule-card" style="border: 2px solid <?php echo $borderColor; ?>; border-radius: 16px; background: #ffffff; padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <div class="schedule-card-header" style="border-bottom: 1px solid rgba(8, 27, 75, 0.08); padding-bottom: 0.75rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 class="discipline" style="font-family: var(--font-heading); color: #081B4B; font-weight: 700; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($sched['discipline']); ?></h4>
                                    <?php if ($sched['event_type']): ?>
                                        <span class="event-type" style="font-size: 0.75rem; color: #6b82b8; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($sched['event_type']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 1.5rem; font-weight: 800; color: rgba(8, 27, 75, 0.15); font-family: var(--font-heading);"><?php echo str_pad($rowIdx, 2, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="schedule-card-body" style="margin-bottom: 1rem;">
                                <p class="date" style="font-size: 0.95rem; color: #FF9933; font-weight: 700; margin-bottom: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                                    <span style="color: #FF9933; display: inline-flex; align-items: center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span> <?php echo htmlspecialchars($sched['date_text']); ?>
                                </p>
                                <p class="venue" style="font-size: 0.95rem; color: #3b4a6b; font-weight: 500; margin-bottom: 0; display: flex; gap: 0.5rem; align-items: center;">
                                    <span style="display: inline-flex; align-items: center; color: #3b4a6b;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></span> <?php echo htmlspecialchars($sched['venue']); ?>
                                </p>
                            </div>
                            <?php if (($sched['registration_mode'] ?? 'external') === 'internal'): ?>
                            <div class="schedule-card-footer">
                                <a href="<?php echo htmlspecialchars($relative_prefix); ?>event-registration.php?event_id=<?php echo $sched['id']; ?>" class="btn btn-hero-primary" style="width: 100%; text-align: center; padding: 0.75rem; background: #081B4B; border-radius: 999px; color: #ffffff; text-decoration: none; font-weight: bold; display: block;">Register Now</a>
                            </div>
                            <?php elseif (($sched['registration_mode'] ?? 'external') === 'external' && $sched['registration_link']): ?>
                            <div class="schedule-card-footer">
                                <a href="<?php echo htmlspecialchars($sched['registration_link']); ?>" target="_blank" class="btn btn-hero-primary" style="width: 100%; text-align: center; padding: 0.75rem; background: #081B4B; border-radius: 999px; color: #ffffff; text-decoration: none; font-weight: bold; display: block;">Register Now</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php $rowIdx++; endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="schedule-table-card" style="text-align: center; padding: 5rem 2rem;">
                    <h4 style="color: var(--boccia-navy, #081B4B); font-weight: 700; margin-bottom: 0.5rem;">No International Events Scheduled</h4>
                    <p style="color: var(--boccia-text-muted, #64748B); margin-bottom: 0;">Please check back later for updates on upcoming international championships and trials.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>
