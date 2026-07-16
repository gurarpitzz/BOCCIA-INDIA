<?php
// admin/officials.php - Secure administrative Official directory browser
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles: admin, editor, viewer
requireLogin();

$page_title = "Official Registry - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$contactStatus = isset($_GET['contact_status']) ? trim($_GET['contact_status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch state options
$stateStmt = $pdo->query("SELECT DISTINCT state FROM officials WHERE state IS NOT NULL AND state != '' ORDER BY state");
$statesList = $stateStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch role options
$roleStmt = $pdo->query("SELECT DISTINCT role FROM officials WHERE role IS NOT NULL AND role != '' ORDER BY role");
$rolesList = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

// Build SQL
$query = "SELECT * FROM officials WHERE deleted_at IS NULL";
$params = [];

if ($search !== '') {
    $query .= " AND (official_reg_no LIKE ? OR name LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if ($state !== '') {
    $query .= " AND state = ?";
    $params[] = $state;
}

if ($role !== '') {
    $query .= " AND role = ?";
    $params[] = $role;
}

if ($status !== '') {
    $query .= " AND status = ?";
    $params[] = $status;
}

if ($contactStatus === 'missing') {
    $query .= " AND (email IS NULL OR email = '' OR phone IS NULL OR phone = '')";
}

// Get count
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRows = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// Get records
$query .= " ORDER BY id ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$officialsList = $stmt->fetchAll();
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Database</span>
                <h1 class="admin-page-title">Officials Directory</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
        </div>

        <!-- Filter Form Toolbar -->
        <div class="admin-toolbar" style="padding: 1.25rem;">
            <form action="officials.php" method="GET" class="row g-2 align-items-end w-100 m-0">
                <div class="col-12 col-md-3 admin-form-group mb-0">
                    <label for="search">Search Query</label>
                    <input type="text" name="search" id="search" class="admin-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or ID...">
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
                    <label for="role">Role / Category</label>
                    <select name="role" id="role" class="admin-select">
                         <option value="">All Roles</option>
                         <?php foreach ($rolesList as $rl): ?>
                             <option value="<?php echo htmlspecialchars($rl); ?>" <?php if ($role === $rl) echo 'selected'; ?>><?php echo htmlspecialchars($rl); ?></option>
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
                         <option value="suspended" <?php if ($status === 'suspended') echo 'selected'; ?>>Suspended</option>
                     </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 admin-form-group mb-0">
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
                Found <strong><?php echo $totalRows; ?></strong> official records
            </div>
            
            <!-- Desktop Table View -->
            <div class="admin-table-wrapper d-none d-md-block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Registration ID</th>
                            <th>Full Name</th>
                            <th>Role / Designation</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>State Association</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($officialsList) > 0): ?>
                            <?php foreach ($officialsList as $off): ?>
                                <tr>
                                    <td style="font-family:monospace; color:var(--bsfi-green); font-weight: 700;">
                                        <a href="official-details.php?id=<?php echo $off['id']; ?>" style="text-decoration:none; color:inherit; font-weight:bold;">
                                            <?php echo htmlspecialchars($off['official_reg_no']); ?>
                                        </a>
                                    </td>
                                    <td style="font-weight:bold;">
                                        <a href="official-details.php?id=<?php echo $off['id']; ?>" style="text-decoration:none; color:inherit;">
                                            <?php echo htmlspecialchars($off['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($off['role']); ?></span>
                                        <?php if (!empty($off['designation'])): ?>
                                            <small class="d-block text-muted" style="font-size: 0.72rem;"><?php echo htmlspecialchars($off['designation']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($off['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($off['dob'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($off['state']); ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = 'admin-badge-warning';
                                            if ($off['status'] === 'approved') $badgeClass = 'admin-badge-success';
                                            if ($off['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                                            if ($off['status'] === 'suspended') $badgeClass = 'admin-badge-pending';
                                        ?>
                                        <span class="admin-badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($off['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="official-details.php?id=<?php echo $off['id']; ?>" class="admin-btn admin-btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;">
                                            <i class="fa-solid fa-clock-history"></i> Profile &amp; Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted); font-style:italic;">No official records found matching current criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Mobile Card View -->
            <div class="mobile-athlete-cards d-block d-md-none">
                <?php if (count($officialsList) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($officialsList as $off): ?>
                            <div class="admin-card hoverable" style="padding: 1.25rem; margin-bottom: 0; border-radius: 12px; border-left: 4px solid <?php echo ($off['status'] === 'approved') ? 'var(--bsfi-green)' : 'var(--bsfi-saffron)'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <span style="font-family: monospace; color: var(--bsfi-green); font-weight: 700; font-size: 0.85rem;"><?php echo htmlspecialchars($off['official_reg_no']); ?></span>
                                    <?php
                                        $badgeClass = 'admin-badge-warning';
                                        if ($off['status'] === 'approved') $badgeClass = 'admin-badge-success';
                                        if ($off['status'] === 'rejected') $badgeClass = 'admin-badge-danger';
                                        if ($off['status'] === 'suspended') $badgeClass = 'admin-badge-pending';
                                    ?>
                                    <span class="admin-badge <?php echo $badgeClass; ?>" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                        <?php echo htmlspecialchars($off['status']); ?>
                                    </span>
                                </div>
                                <h4 class="admin-card-title" style="font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 800; color: var(--navy);"><?php echo htmlspecialchars($off['name']); ?></h4>
                                <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4;">
                                    <div><strong>Role:</strong> <?php echo htmlspecialchars($off['role']); ?></div>
                                    <div><strong>State:</strong> <?php echo htmlspecialchars($off['state']); ?></div>
                                    <div><strong>DOB/Gender:</strong> <?php echo htmlspecialchars($off['dob'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($off['gender']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:2rem 1rem; color:var(--text-muted); font-style:italic;">No official records found matching current criteria.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:0.5rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="officials.php?search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($state); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $i; ?>" 
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
