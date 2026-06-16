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

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Federation Database</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Athlete Directory</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:2rem; border-radius:28px; margin-bottom:3rem;">
            <form action="athletes.php" method="GET" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:1rem; align-items:flex-end;">
                <div class="input-group">
                    <label for="search" style="font-size:0.75rem; font-weight:600; color:#FAF7F0; opacity:0.7;">Search Query</label>
                    <input type="text" name="search" id="search" class="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or registration number...">
                </div>
                <div class="input-group">
                    <label for="state" style="font-size:0.75rem; font-weight:600; color:#FAF7F0; opacity:0.7;">State Association</label>
                    <select name="state" id="state" class="select-input">
                        <option value="">All States</option>
                        <?php foreach ($statesList as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>" <?php if ($state === $st) echo 'selected'; ?>><?php echo htmlspecialchars($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="class" style="font-size:0.75rem; font-weight:600; color:#FAF7F0; opacity:0.7;">Classification</label>
                    <select name="class" id="class" class="select-input">
                        <option value="">All Classifications</option>
                        <?php foreach ($classesList as $cl): ?>
                            <option value="<?php echo htmlspecialchars($cl); ?>" <?php if ($class === $cl) echo 'selected'; ?>><?php echo htmlspecialchars($cl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="status" style="font-size:0.75rem; font-weight:600; color:#FAF7F0; opacity:0.7;">Registry Status</label>
                    <select name="status" id="status" class="select-input">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="approved" <?php if ($status === 'approved') echo 'selected'; ?>>Approved</option>
                        <option value="rejected" <?php if ($status === 'rejected') echo 'selected'; ?>>Rejected</option>
                        <option value="archived" <?php if ($status === 'archived') echo 'selected'; ?>>Archived</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; padding:0.85rem; border-radius:999px; width:100%; cursor:pointer;">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="table-wrapper">
            <table class="doc-table" style="width:100%; border-collapse:collapse; text-align:left;">
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
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:1.2rem; font-family:monospace; color:#24C27A;"><?php echo htmlspecialchars($ath['regn_no']); ?></td>
                                <td style="padding:1.2rem; font-weight:bold;"><?php echo htmlspecialchars($ath['full_name']); ?></td>
                                <td style="padding:1.2rem;"><?php echo htmlspecialchars($ath['gender']); ?></td>
                                <td style="padding:1.2rem;"><?php echo htmlspecialchars($ath['dob']); ?></td>
                                <td style="padding:1.2rem;"><?php echo htmlspecialchars($ath['representing_for']); ?></td>
                                <td style="padding:1.2rem;"><?php echo htmlspecialchars($ath['classification']); ?></td>
                                <td style="padding:1.2rem;">
                                    <?php
                                        $color = '#F4B942';
                                        if ($ath['status'] === 'approved') $color = '#24C27A';
                                        if ($ath['status'] === 'rejected') $color = '#D72638';
                                        if ($ath['status'] === 'archived') $color = '#7f8c8d';
                                    ?>
                                    <span style="font-size:0.8rem; background:rgba(255,255,255,0.05); color:<?php echo $color; ?>; border:1px solid <?php echo $color; ?>; padding:0.25rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">
                                        <?php echo htmlspecialchars($ath['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem; color:#FAF7F0; opacity:0.6;">No athlete records found matching current criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:3rem; display:flex; justify-content:center; gap:0.5rem;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="athletes.php?search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($state); ?>&class=<?php echo urlencode($class); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $i; ?>" 
                       class="pagination-btn <?php if ($page === $i) echo 'active'; ?>" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
