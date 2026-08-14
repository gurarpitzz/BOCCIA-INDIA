<?php
// circulars-notices.php - Public listing page for official Circulars & Notices of BSFI
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Server-side filters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$date = trim($_GET['date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Base parameters for query
$where = ["status = 'Published'", "deleted_at IS NULL"];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category) && in_array($category, ['Circular', 'Notice'])) {
    $where[] = "category = ?";
    $params[] = $category;
}

if (!empty($date)) {
    $where[] = "publication_date = ?";
    $params[] = $date;
}

$whereClause = implode(" AND ", $where);

// Count total matching items for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM circulars_notices WHERE $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch matching items
$querySql = "SELECT * FROM circulars_notices WHERE $whereClause ORDER BY publication_date DESC, id DESC LIMIT $limit OFFSET $offset";
$queryStmt = $pdo->prepare($querySql);
$queryStmt->execute($params);
$documents = $queryStmt->fetchAll();

// Dynamic SEO headers
$page_title = "Circulars & Notices | Boccia Sports Federation of India";
$meta_desc = "Stay updated with the latest official circulars, notices, announcements, and important documents of the Boccia Sports Federation of India (BSFI).";
$canonical_url = "circulars-notices.php";

include __DIR__ . '/includes/header.php';
?>

<!-- FontAwesome 6 Icons and Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --bsfi-navy: #081B4B;
    --bsfi-saffron: #FF9933;
    --bsfi-green: #138808;
    --bsfi-light: #FAF7F0;
    --card-shadow: 0 8px 30px rgba(8, 27, 75, 0.05);
}

.circulars-hero {
    background: linear-gradient(135deg, rgba(8, 27, 75, 0.95) 0%, rgba(8, 27, 75, 0.85) 100%);
    color: #ffffff;
    padding: 5rem 0 4rem 0;
    text-align: center;
    border-bottom: 4px solid var(--bsfi-saffron);
}

.circulars-section {
    background-color: var(--bsfi-light);
    min-height: 60vh;
    padding: 3rem 0;
}

.filter-card {
    background: #ffffff;
    border: none;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.category-chip {
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    border: 2px solid #E5E7EB;
    background: #ffffff;
    color: #4B5563;
    transition: all 0.25s ease;
    text-decoration: none;
    display: inline-block;
}

.category-chip.active {
    background: var(--bsfi-navy);
    color: #ffffff;
    border-color: var(--bsfi-navy);
}

.category-chip:hover:not(.active) {
    border-color: var(--bsfi-navy);
    color: var(--bsfi-navy);
}

.doc-card {
    background: #ffffff;
    border: none;
    border-radius: 20px;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.doc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(8, 27, 75, 0.1);
}

.date-box {
    background: var(--bsfi-navy);
    color: #ffffff;
    border-radius: 12px;
    padding: 0.75rem;
    text-align: center;
    min-width: 80px;
    display: inline-block;
}

.date-box .day {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
    display: block;
}

.date-box .month {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
    margin-top: 2px;
}

.date-box .year {
    font-size: 0.8rem;
    font-weight: 500;
    opacity: 0.8;
    display: block;
}

.badge-circular {
    background-color: rgba(255, 153, 51, 0.15);
    color: var(--bsfi-saffron);
    font-weight: 700;
}

.badge-notice {
    background-color: rgba(19, 136, 8, 0.15);
    color: var(--bsfi-green);
    font-weight: 700;
}

.download-btn {
    background: var(--bsfi-navy);
    color: #ffffff;
    font-weight: 600;
    border-radius: 50px;
    padding: 0.5rem 1.25rem;
    border: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.download-btn:hover {
    background: var(--bsfi-saffron);
    color: #ffffff;
}

.pagination-link {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 1px solid #E5E7EB;
    background: #ffffff;
    color: var(--bsfi-navy);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-link.active {
    background: var(--bsfi-navy);
    color: #ffffff;
    border-color: var(--bsfi-navy);
}

.pagination-link:hover:not(.active) {
    background: #E5E7EB;
}
</style>

<!-- Hero Section -->
<section class="circulars-hero">
    <div class="container">
        <h1 class="display-5 fw-bold" style="font-family: 'Outfit', sans-serif;">Circulars & Notices</h1>
        <p class="lead mb-0 opacity-90" style="max-width: 600px; margin: 0 auto; font-family: 'Poppins', sans-serif;">
            Stay updated with the latest official circulars, notices, and important announcements from the federation.
        </p>
    </div>
</section>

<!-- Main Directory section -->
<section class="circulars-section">
    <div class="container">
        <div class="row">
            <!-- Filtering Sidebar -->
            <div class="col-lg-3 col-md-4">
                <form action="circulars-notices.php" method="GET" class="filter-card">
                    <h5 class="fw-bold mb-3" style="color: var(--bsfi-navy);">Search & Filters</h5>
                    
                    <!-- Search input -->
                    <div class="mb-3">
                        <label for="search-input" class="form-label small fw-bold text-secondary">Search Keyword</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" id="search-input" name="search" class="form-control border-start-0 ps-0" placeholder="Type title..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>

                    <!-- Date picker -->
                    <div class="mb-4">
                        <label for="date-input" class="form-label small fw-bold text-secondary">Filter by Date</label>
                        <input type="date" id="date-input" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                    </div>

                    <!-- Hidden category to preserve -->
                    <?php if (!empty($category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn text-white w-100 fw-bold rounded-pill" style="background: var(--bsfi-navy);">Apply Filters</button>
                        <a href="circulars-notices.php" class="btn btn-outline-secondary w-100 fw-bold rounded-pill">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9 col-md-8">
                <!-- Category Chips -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="circulars-notices.php?<?php echo http_build_query(array_filter(['search' => $search, 'date' => $date])); ?>" class="category-chip <?php echo empty($category) ? 'active' : ''; ?>">All Categories</a>
                    <a href="circulars-notices.php?<?php echo http_build_query(array_filter(['category' => 'Circular', 'search' => $search, 'date' => $date])); ?>" class="category-chip <?php echo $category === 'Circular' ? 'active' : ''; ?>">Circulars</a>
                    <a href="circulars-notices.php?<?php echo http_build_query(array_filter(['category' => 'Notice', 'search' => $search, 'date' => $date])); ?>" class="category-chip <?php echo $category === 'Notice' ? 'active' : ''; ?>">Notices</a>
                </div>

                <!-- Empty State -->
                <?php if (empty($documents)): ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm" style="border: 2px dashed #E5E7EB;">
                        <div class="display-3 text-secondary mb-3"><i class="bi bi-file-earmark-pdf"></i></div>
                        <h4 class="fw-bold text-dark">No Documents Found</h4>
                        <p class="text-muted mb-4">No circulars or notices match your search queries or selected filters.</p>
                        <a href="circulars-notices.php" class="btn btn-primary rounded-pill fw-bold px-4 py-2" style="background: var(--bsfi-navy); border: none;">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <!-- Listing Grid -->
                    <div class="row">
                        <?php foreach ($documents as $doc): 
                            $dTime = strtotime($doc['publication_date']);
                            $day = date('d', $dTime);
                            $month = date('M', $dTime);
                            $year = date('Y', $dTime);
                            $catBadge = ($doc['category'] === 'Circular') ? 'badge-circular' : 'badge-notice';
                        ?>
                            <div class="col-12">
                                <div class="doc-card p-4">
                                    <div class="row align-items-center g-3">
                                        <!-- Date Box -->
                                        <div class="col-auto">
                                            <div class="date-box">
                                                <span class="day"><?php echo $day; ?></span>
                                                <span class="month"><?php echo $month; ?></span>
                                                <span class="year"><?php echo $year; ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Document Info -->
                                        <div class="col">
                                            <span class="badge rounded-pill px-3 py-1 mb-2 <?php echo $catBadge; ?>">
                                                <?php echo htmlspecialchars($doc['category']); ?>
                                            </span>
                                            <h4 class="fw-bold mb-1" style="color: var(--bsfi-navy); font-size: 1.25rem;">
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </h4>
                                            <?php if (!empty($doc['description'])): ?>
                                                <p class="text-secondary small mb-0 mt-2">
                                                    <?php echo htmlspecialchars($doc['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Download Trigger -->
                                        <div class="col-12 col-md-auto text-end">
                                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="download-btn w-100 w-md-auto justify-content-center" aria-label="Download PDF for <?php echo htmlspecialchars($doc['title']); ?>">
                                                <span>Download PDF</span>
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-center gap-2 mt-4">
                            <?php if ($page > 1): ?>
                                <a href="circulars-notices.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-link"><i class="bi bi-chevron-left"></i></a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="circulars-notices.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="circulars-notices.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-link"><i class="bi bi-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
