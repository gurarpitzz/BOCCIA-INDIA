<?php
// includes/results-page.php - Frontend Competition Results Hub

$resultSchedules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE active = 1 AND result_url IS NOT NULL AND result_url != '' ORDER BY start_date DESC, id DESC");
    $stmt->execute();
    $resultSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback empty list
}

// Extract unique event types & scopes for filtering
$eventScopes = array_unique(array_filter(array_column($resultSchedules, 'competition_scope')));
sort($eventScopes);

$eventTypes = array_unique(array_filter(array_column($resultSchedules, 'event_type')));
sort($eventTypes);

$page_title = "Competition Results | Boccia India";
$meta_desc = "Official tournament results, standings, and classification outcomes of the Boccia Sports Federation of India.";
$canonical_url = "page.php?section=competitions&slug=results";

include __DIR__ . '/header.php';
?>

<style>
.results-page {
    background: url('<?php echo htmlspecialchars($relative_prefix); ?>bg.webp') no-repeat center top / cover;
    padding-bottom: 80px;
}
.results-content-section {
    padding: 60px 0;
}
.competition-filter-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 20px 25px;
    border: 1px solid rgba(8, 27, 75, 0.08);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.04);
    margin-bottom: 30px;
}
.filter-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b82b8;
    margin-bottom: 6px;
    display: block;
}
.results-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 20px;
}
@media (max-width: 991px) {
    .results-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 767px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
}
.result-card {
    background: var(--boccia-card-bg, #ffffff);
    border-radius: 20px;
    padding: 35px;
    border: 1px solid rgba(8, 27, 75, 0.06);
    box-shadow: 0 10px 30px rgba(8, 27, 75, 0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(8, 27, 75, 0.08);
}
.result-card-content h4 {
    font-family: var(--font-heading-sub);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--boccia-navy, #081B4B);
    margin-bottom: 12px;
}
.result-card-content .date-badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #FF9933;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.scope-badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    background: rgba(8, 27, 75, 0.08);
    color: #081B4B;
    margin-left: 8px;
    text-transform: uppercase;
}
.result-card-content p {
    font-size: 0.95rem;
    color: var(--boccia-text-muted, #64748B);
    line-height: 1.6;
    margin-bottom: 30px;
}
.result-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.result-btn-primary {
    font-family: var(--font-heading-sub);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
    background-color: var(--boccia-navy, #081B4B);
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s ease;
    border: none;
    display: block;
    width: 100%;
}
.result-btn-primary:hover {
    background-color: var(--boccia-green, #10B981);
    color: #ffffff;
}
</style>

<div class="results-page">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="board-hero" style="background-image: linear-gradient(90deg, rgba(7, 25, 84, 0.95) 0%, rgba(7, 25, 84, 0.88) 35%, rgba(7, 25, 84, 0.65) 55%, rgba(7, 25, 84, 0.35) 75%, transparent 100%), url('<?php echo htmlspecialchars($relative_prefix); ?>board/board_bg.webp');">
        <div class="container board-hero-container">
            <div class="board-hero-content scroll-reveal">
                <span class="board-hero-eyebrow">-- Results --</span>
                <h1 class="board-hero-title">Competition Results</h1>
                <p class="board-hero-text">
                    Official competition results published by the Boccia Sports Federation of India.
                    <br>
                    <span style="color: var(--boccia-saffron, #FF9933); font-weight: 600;">Access schedules, brackets, athlete standings, and final tournament scores.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTENT SECTION ═══════════ -->
    <section class="results-content-section">
        <div class="container">
            <?php if (count($resultSchedules) > 0): ?>

                <!-- Search & Filter Controls Toolbar -->
                <div class="competition-filter-card scroll-reveal">
                    <div class="row g-3 align-items-end">
                        <!-- Search Bar -->
                        <div class="col-12 col-md-4">
                            <label for="resultSearchInput" class="filter-label"><i class="bi bi-search me-1"></i> Search Results</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted ps-3">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="resultSearchInput" class="form-control border-start-0 rounded-end-pill py-2" placeholder="Search championship, venue, state..." style="font-size: 0.95rem;">
                            </div>
                        </div>

                        <!-- Filter by Scope -->
                        <div class="col-6 col-md-3">
                            <label for="resultScopeFilter" class="filter-label"><i class="bi bi-globe me-1"></i> Scope</label>
                            <select id="resultScopeFilter" class="form-select rounded-pill py-2" style="font-size: 0.95rem;">
                                <option value="">All Scopes (<?php echo count($resultSchedules); ?>)</option>
                                <?php foreach ($eventScopes as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter by Event Type -->
                        <div class="col-6 col-md-2">
                            <label for="resultTypeFilter" class="filter-label"><i class="bi bi-funnel me-1"></i> Type</label>
                            <select id="resultTypeFilter" class="form-select rounded-pill py-2" style="font-size: 0.95rem;">
                                <option value="">All Types</option>
                                <?php foreach ($eventTypes as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sort by Date -->
                        <div class="col-10 col-md-2">
                            <label for="resultDateSort" class="filter-label"><i class="bi bi-sort-down me-1"></i> Sort by Date</label>
                            <select id="resultDateSort" class="form-select rounded-pill py-2" style="font-size: 0.95rem;">
                                <option value="desc">Date: Latest First</option>
                                <option value="asc">Date: Earliest First</option>
                            </select>
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="col-2 col-md-1 text-md-end">
                            <button id="resetResultFiltersBtn" class="btn btn-outline-secondary rounded-circle w-100" title="Reset search and filters" style="height: 42px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="results-grid" id="resultsGridContainer">
                    <?php 
                    $cardIdx = 1;
                    foreach ($resultSchedules as $sched): 
                        $searchable = strtolower($sched['discipline'] . ' ' . $sched['event_type'] . ' ' . $sched['date_text'] . ' ' . $sched['venue'] . ' ' . ($sched['competition_scope'] ?? ''));
                    ?>
                        <div class="result-card result-item-card"
                             data-index="<?php echo $cardIdx; ?>"
                             data-start-date="<?php echo htmlspecialchars($sched['start_date'] ?? ''); ?>"
                             data-scope="<?php echo htmlspecialchars($sched['competition_scope'] ?? ''); ?>"
                             data-type="<?php echo htmlspecialchars($sched['event_type'] ?? ''); ?>"
                             data-search="<?php echo htmlspecialchars($searchable); ?>">
                            <div class="result-card-content">
                                <div>
                                    <span class="date-badge"><?php echo htmlspecialchars($sched['date_text']); ?></span>
                                    <?php if (!empty($sched['competition_scope'])): ?>
                                        <span class="scope-badge"><?php echo htmlspecialchars($sched['competition_scope']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h4><?php echo htmlspecialchars($sched['discipline']); ?></h4>
                                <?php if ($sched['event_type']): ?>
                                    <p><?php echo htmlspecialchars($sched['event_type']); ?> (Held at <?php echo htmlspecialchars($sched['venue']); ?>)</p>
                                <?php else: ?>
                                    <p>Held at <?php echo htmlspecialchars($sched['venue']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="result-actions">
                                <a href="<?php echo htmlspecialchars($sched['result_url']); ?>" target="_blank" class="result-btn-primary">
                                    <?php echo htmlspecialchars($sched['result_button_text'] ?: 'View Results'); ?>
                                </a>
                            </div>
                        </div>
                    <?php $cardIdx++; endforeach; ?>
                </div>

                <!-- Dynamic No Results Notice -->
                <div id="noMatchingResults" style="display: none; text-align: center; padding: 5rem 2rem; background: #ffffff; border-radius: 20px; margin-top: 20px; border: 1px solid rgba(8, 27, 75, 0.06);">
                    <i class="bi bi-search" style="font-size: 2.5rem; color: #6b82b8;"></i>
                    <h4 style="color: #081B4B; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem;">No Matching Results Found</h4>
                    <p style="color: #64748B; margin-bottom: 1.5rem;">No competition results match your current search and filter criteria.</p>
                    <button id="resetFromResultsNoticeBtn" class="btn btn-outline-primary rounded-pill px-4 font-weight-bold">Clear Filters</button>
                </div>

            <?php else: ?>
                <div class="result-card" style="text-align: center; padding: 5rem 2rem; border-radius: 20px;">
                    <h4 style="color: var(--boccia-navy, #081B4B); font-weight: 700; margin-bottom: 0.5rem;">No Results Published</h4>
                    <p style="color: var(--boccia-text-muted, #64748B); margin-bottom: 0;">There are no competition results published at this time. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('resultSearchInput');
    const scopeFilter = document.getElementById('resultScopeFilter');
    const typeFilter = document.getElementById('resultTypeFilter');
    const dateSort = document.getElementById('resultDateSort');
    const resetBtn = document.getElementById('resetResultFiltersBtn');
    const resetNoticeBtn = document.getElementById('resetFromResultsNoticeBtn');
    
    const resultsContainer = document.getElementById('resultsGridContainer');
    const noMatchingNotice = document.getElementById('noMatchingResults');
    
    if (!resultsContainer) return;
    
    const resultCards = Array.from(resultsContainer.querySelectorAll('.result-item-card'));
    
    function filterAndSortResults() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const selectedScope = (scopeFilter.value || '').trim();
        const selectedType = (typeFilter.value || '').trim();
        const sortOrder = dateSort.value || 'desc';
        
        let visibleCount = 0;
        
        resultCards.forEach(card => {
            const searchText = card.getAttribute('data-search') || '';
            const itemScope = card.getAttribute('data-scope') || '';
            const itemType = card.getAttribute('data-type') || '';
            
            const matchesQuery = !query || searchText.includes(query);
            const matchesScope = !selectedScope || itemScope === selectedScope;
            const matchesType = !selectedType || itemType === selectedType;
            
            if (matchesQuery && matchesScope && matchesType) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Sort Cards by start-date
        resultCards.sort((a, b) => {
            const dateA = a.getAttribute('data-start-date') || '0000-00-00';
            const dateB = b.getAttribute('data-start-date') || '0000-00-00';
            return sortOrder === 'asc' ? dateA.localeCompare(dateB) : dateB.localeCompare(dateA);
        }).forEach(card => resultsContainer.appendChild(card));
        
        // Toggle No Results Notice
        if (visibleCount === 0) {
            noMatchingNotice.style.display = 'block';
        } else {
            noMatchingNotice.style.display = 'none';
        }
    }
    
    if (searchInput) searchInput.addEventListener('input', filterAndSortResults);
    if (scopeFilter) scopeFilter.addEventListener('change', filterAndSortResults);
    if (typeFilter) typeFilter.addEventListener('change', filterAndSortResults);
    if (dateSort) dateSort.addEventListener('change', filterAndSortResults);
    
    function resetAll() {
        if (searchInput) searchInput.value = '';
        if (scopeFilter) scopeFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        if (dateSort) dateSort.value = 'desc';
        filterAndSortResults();
    }
    
    if (resetBtn) resetBtn.addEventListener('click', resetAll);
    if (resetNoticeBtn) resetNoticeBtn.addEventListener('click', resetAll);
});
</script>

<?php include __DIR__ . '/footer.php'; ?>

