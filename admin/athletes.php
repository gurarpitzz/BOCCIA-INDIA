<?php
// athletes.php - Secure administrative Athlete directory browser

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$page_title = "Athlete Registry - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$class = isset($_GET['class']) ? trim($_GET['class']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$photoStatus = isset($_GET['photo_status']) ? trim($_GET['photo_status']) : '';
$contactStatus = isset($_GET['contact_status']) ? trim($_GET['contact_status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch state options
$stateStmt = $pdo->query("SELECT DISTINCT representing_for FROM athletes WHERE representing_for IS NOT NULL AND representing_for != '' ORDER BY representing_for");
$statesList = $stateStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch classification options
$classStmt = $pdo->query("SELECT DISTINCT classification FROM athletes WHERE classification IS NOT NULL AND classification != '' ORDER BY classification");
$classesList = $classStmt->fetchAll(PDO::FETCH_COLUMN);

// Build SQL
$query = "SELECT * FROM athletes WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (regn_no LIKE ? OR full_name LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if ($state !== '') {
    $query .= " AND representing_for = ?";
    $params[] = $state;
}

if ($class !== '') {
    $query .= " AND classification = ?";
    $params[] = $class;
}

if ($status !== '') {
    $query .= " AND status = ?";
    $params[] = $status;
}

if ($photoStatus !== '') {
    $query .= " AND photo_status = ?";
    $params[] = $photoStatus;
}

if ($contactStatus === 'missing') {
    $query .= " AND (email IS NULL OR email = '' OR mobile IS NULL OR mobile = '')";
}

// Get count
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRows = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// Get records
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$athletesList = $stmt->fetchAll();
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Database</span>
                <h1 class="admin-page-title">Athlete Directory</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
        </div>

        <!-- Filter Form Toolbar -->
        <div class="admin-toolbar" style="padding: 1.25rem;">
            <form action="athletes.php" method="GET" class="row g-2 align-items-end w-100 m-0">
                <div class="col-12 col-md-3 admin-form-group mb-0">
                    <label for="search">Search Query</label>
                    <input type="text" name="search" id="search" class="admin-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or reg no...">
                </div>
                <div class="col-12 col-sm-6 col-md-2 admin-form-group mb-0">
                    <label for="state">State Association</label>
                    <select name="state" id="state" class="admin-select">
                         <option value="">All States</option>
                         <?php foreach ($statesList as $st): ?>
                             <option value="<?php echo htmlspecialchars($st); ?>" <?php if ($state === $st) echo 'selected'; ?>><?php echo htmlspecialchars($st); ?></option>
                         <?php endforeach; ?>
                     </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 admin-form-group mb-0">
                    <label for="class">Classification</label>
                    <select name="class" id="class" class="admin-select">
                         <option value="">All Classifications</option>
                         <?php foreach ($classesList as $cl): ?>
                             <option value="<?php echo htmlspecialchars($cl); ?>" <?php if ($class === $cl) echo 'selected'; ?>><?php echo htmlspecialchars($cl); ?></option>
                         <?php endforeach; ?>
                     </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 admin-form-group mb-0">
                    <label for="status">Registry Status</label>
                    <select name="status" id="status" class="admin-select">
                         <option value="">All Statuses</option>
                         <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                         <option value="approved" <?php if ($status === 'approved') echo 'selected'; ?>>Approved</option>
                         <option value="rejected" <?php if ($status === 'rejected') echo 'selected'; ?>>Rejected</option>
                         <option value="archived" <?php if ($status === 'archived') echo 'selected'; ?>>Archived</option>
                     </select>
                </div>
                <div class="col-12 col-sm-6 col-md-1 admin-form-group mb-0">
                    <label for="photo_status">Photo</label>
                    <select name="photo_status" id="photo_status" class="admin-select">
                         <option value="">All Photos</option>
                         <option value="missing" <?php if ($photoStatus === 'missing') echo 'selected'; ?>>Missing</option>
                         <option value="verified" <?php if ($photoStatus === 'verified') echo 'selected'; ?>>Verified</option>
                     </select>
                </div>
                <div class="col-12 col-sm-6 col-md-1 admin-form-group mb-0">
                    <label for="contact_status">Contact</label>
                    <select name="contact_status" id="contact_status" class="admin-select">
                         <option value="">All Contacts</option>
                         <option value="missing" <?php if ($contactStatus === 'missing') echo 'selected'; ?>>Missing</option>
                     </select>
                </div>
                <div class="col-12 col-md-1">
                    <button type="submit" class="admin-btn admin-btn-primary w-100" style="padding: 0.65rem 1rem;">Apply</button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="admin-card" style="padding: 1.5rem;">
            <div class="admin-results-count" style="margin-bottom: 1rem;">
                Found <strong><?php echo $totalRows; ?></strong> athlete records
            </div>
            
            <!-- Desktop Table View (Hidden on mobile) -->
            <div class="admin-table-wrapper d-none d-md-block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Registration No</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>State Association</th>
                            <th>Classification</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($athletesList) > 0): ?>
                            <?php foreach ($athletesList as $ath): ?>
                                <tr>
                                    <td style="font-family:monospace; color:var(--bsfi-green); font-weight: 700;"><?php echo htmlspecialchars($ath['regn_no']); ?></td>
                                    <td style="font-weight:bold;"><?php echo htmlspecialchars($ath['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ath['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($ath['dob']); ?></td>
                                    <td><?php echo htmlspecialchars($ath['representing_for']); ?></td>
                                    <td><?php echo htmlspecialchars($ath['classification']); ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = 'admin-badge-warning';
                                            if ($ath['status'] === 'approved') $badgeClass = 'admin-badge-success';
                                            if ($ath['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                                            if ($ath['status'] === 'archived') $badgeClass = 'admin-badge-pending';
                                        ?>
                                        <span class="admin-badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($ath['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted); font-style:italic;">No athlete records found matching current criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Mobile Card View (Hidden on desktop) -->
            <div class="mobile-athlete-cards d-block d-md-none">
                <?php if (count($athletesList) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($athletesList as $ath): ?>
                            <div class="admin-card hoverable" style="padding: 1.25rem; margin-bottom: 0; border-radius: 12px; border-left: 4px solid <?php echo ($ath['status'] === 'approved') ? 'var(--bsfi-green)' : 'var(--bsfi-saffron)'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <span style="font-family: monospace; color: var(--bsfi-green); font-weight: 700; font-size: 0.85rem;"><?php echo htmlspecialchars($ath['regn_no']); ?></span>
                                    <?php
                                        $badgeClass = 'admin-badge-warning';
                                        if ($ath['status'] === 'approved') $badgeClass = 'admin-badge-success';
                                        if ($ath['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                                        if ($ath['status'] === 'archived') $badgeClass = 'admin-badge-pending';
                                    ?>
                                    <span class="admin-badge <?php echo $badgeClass; ?>" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                        <?php echo htmlspecialchars($ath['status']); ?>
                                    </span>
                                </div>
                                <h4 class="admin-card-title" style="font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 800; color: var(--navy);"><?php echo htmlspecialchars($ath['full_name']); ?></h4>
                                <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                                    <div><strong>State:</strong> <?php echo htmlspecialchars($ath['representing_for']); ?></div>
                                    <div><strong>Category:</strong> <?php echo htmlspecialchars($ath['classification']); ?></div>
                                    <div><strong>DOB/Gender:</strong> <?php echo htmlspecialchars($ath['dob']); ?> • <?php echo htmlspecialchars($ath['gender']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:2rem 1rem; color:var(--text-muted); font-style:italic;">No athlete records found matching current criteria.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:0.5rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="athletes.php?search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($state); ?>&class=<?php echo urlencode($class); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $i; ?>" 
                           class="admin-btn <?php echo ($page === $i) ? 'admin-btn-secondary' : 'admin-btn-outline'; ?>" style="min-width: 40px; padding: 0.4rem 0.8rem; font-size: 0.8rem; border-radius: 6px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
