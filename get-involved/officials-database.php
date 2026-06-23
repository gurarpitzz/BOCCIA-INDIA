<?php
// get-involved/officials-database.php - Officials directory page

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$search = trim($_GET['search'] ?? '');
$stateFilter = trim($_GET['state'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

// Initialize query components
$whereClauses = ["status = 'approved'", "deleted_at IS NULL"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR official_reg_no LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($stateFilter)) {
    $whereClauses[] = "state = ?";
    $params[] = $stateFilter;
}

if (!empty($roleFilter)) {
    $whereClauses[] = "role = ?";
    $params[] = $roleFilter;
}

$whereSql = implode(" AND ", $whereClauses);

// CSV Export for Admins
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $filename = 'registered_officials_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Official ID', 'Name', 'Role', 'Gender', 'DOB', 'Phone', 'Email', 'State', 'Created At']);
    
    try {
        $stmt = $pdo->prepare("SELECT official_reg_no, name, role, gender, dob, phone, email, state, created_at FROM officials WHERE $whereSql ORDER BY name ASC");
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        logAction($pdo, "Exported filtered Officials CSV list");
    } catch (PDOException $e) {
        // Log or handle error silently
    }
    fclose($output);
    exit();
}

$recordsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $recordsPerPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM officials WHERE $whereSql");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

    $query = "SELECT id, official_reg_no, name, role, gender, dob, state, photo_path, photo_status FROM officials WHERE $whereSql ORDER BY name ASC LIMIT $recordsPerPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $officials = $stmt->fetchAll();

    // Fetch lists for filter dropdowns
    $statesList = $pdo->query("SELECT DISTINCT state FROM officials WHERE status = 'approved' AND deleted_at IS NULL ORDER BY state ASC")->fetchAll(PDO::FETCH_COLUMN);
    $rolesList = $pdo->query("SELECT DISTINCT role FROM officials WHERE status = 'approved' AND deleted_at IS NULL ORDER BY role ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Registered Officials - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
@media (max-width: 767px) {
    .table-responsive table,
    .table-responsive tbody,
    .table-responsive th,
    .table-responsive td,
    .table-responsive tr {
        display: block !important;
        width: 100% !important;
    }
    .table-responsive thead {
        display: none !important;
    }
    .table-responsive tr {
        border-bottom: 2px solid #E2E8F0 !important;
        padding: 15px 10px !important;
        margin-bottom: 15px !important;
        background: #ffffff !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 10px rgba(8, 27, 75, 0.03) !important;
    }
    .table-responsive td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 12px !important;
        border: none !important;
        font-size: 0.9rem !important;
        background: none !important;
    }
    .table-responsive td:first-child {
        justify-content: center !important;
        border-bottom: 1px solid #F1F5F9 !important;
        padding-bottom: 12px !important;
        margin-bottom: 8px !important;
    }
    .table-responsive td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #081B4B;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    .table-responsive td:first-child::before {
        display: none !important;
    }
    .pagination {
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 5px !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .pagination .page-item .page-link {
        border-radius: 8px !important;
        margin: 2px !important;
        padding: 8px 12px !important;
        font-size: 0.85rem !important;
        border: 1px solid #E2E8F0 !important;
    }
}
</style>

<div style="
    background: linear-gradient(100deg, #081B4B 30%, rgba(8, 27, 75, 0.92) 55%, rgba(8, 27, 75, 0.25) 100%),
                url('../about boccia/why_boccia_matter_BG.webp') no-repeat center right;
    background-size: cover;
    min-height: 100vh;
    color: #ffffff;
    position: relative;
    display: flex;
    align-items: flex-end;
    padding-bottom: 5rem;
">
    <div class="container" style="position: relative; z-index: 2;">
        <div style="max-width: 860px;">
            <span style="
                color: #24C27A;
                font-family: 'Outfit', sans-serif;
                font-size: 1.1rem;
                font-weight: 600;
                letter-spacing: 0.15em;
                font-style: italic;
                display: block;
                margin-bottom: 1rem;
            ">-- BSFI Technical Officials Registry 2026 --</span>

            <h1 style="
                font-family: 'Outfit', sans-serif;
                font-size: clamp(2.8rem, 5vw, 4.5rem);
                font-weight: 900;
                line-height: 1.0;
                margin: 0 0 1.75rem 0;
                letter-spacing: -0.025em;
                text-transform: uppercase;
            ">Registered Officials<br><span style="color: #FF9933;">Directory</span></h1>

            <p style="
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: 1.15rem;
                line-height: 1.7;
                color: rgba(255,255,255,0.82);
                margin: 0 0 2rem 0;
                max-width: 600px;
            ">A verified national registry of all BSFI-licensed coaches, classifiers, technical officials, and referees accredited for the 2026 championship season.</p>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;">
                <div style="display:flex; align-items:center; gap:0.5rem; color: rgba(255,255,255,0.7); font-family: 'Plus Jakarta Sans', sans-serif; font-size:0.9rem;">
                    <span style="color:#24C27A; font-size:1.1rem;">&#10003;</span> BSFI Licensed
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; color: rgba(255,255,255,0.7); font-family: 'Plus Jakarta Sans', sans-serif; font-size:0.9rem;">
                    <span style="color:#24C27A; font-size:1.1rem;">&#10003;</span> Classification Certified
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; color: rgba(255,255,255,0.7); font-family: 'Plus Jakarta Sans', sans-serif; font-size:0.9rem;">
                    <span style="color:#24C27A; font-size:1.1rem;">&#10003;</span> PCI Recognized
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 py-4">
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3">
        <div>
            <h2 class="h3 text-dark fw-bold" style="color: #081B4B !important; margin: 0;">Registered Officials</h2>
            <p class="text-muted mb-0">Official directory of approved technical officials, classifiers, coaches, and referees registered with BSFI.</p>
        </div>
        <?php if (isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="?export=csv&search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($stateFilter); ?>&role=<?php echo urlencode($roleFilter); ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Filtered CSV
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 bg-light p-4 rounded-4 shadow-sm border mb-5">
        <div class="col-md-4">
            <label class="form-label fw-bold text-dark">Search Name or ID</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="E.g. Name, ID">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold text-dark">Filter by State</label>
            <select name="state" class="form-select">
                <option value="">All States</option>
                <?php foreach ($statesList as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $stateFilter === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold text-dark">Filter by Role</label>
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <?php foreach ($rolesList as $role): ?>
                    <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>><?php echo htmlspecialchars($role); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100 py-2">Filter Records</button>
        </div>
    </form>

    <!-- Results Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-primary text-white">
                        <th class="p-3" style="width: 80px;">Photo</th>
                        <th class="p-3">Official ID</th>
                        <th class="p-3">Official Name</th>
                        <th class="p-3">Gender</th>
                        <th class="p-3">State</th>
                        <th class="p-3">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($officials) > 0): ?>
                        <?php foreach ($officials as $off): ?>
                            <tr>
                                <td class="p-3" data-label="Photo">
                                    <?php if (!empty($off['photo_path']) && isset($off['photo_status']) && $off['photo_status'] === 'verified'): ?>
                                        <img src="../<?php echo htmlspecialchars($off['photo_path']); ?>" alt="Profile" style="width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #CBD5E1;">
                                    <?php else: ?>
                                        <div style="width: 45px; height: 45px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; border: 2px solid #CBD5E1;">
                                            <i class="bi bi-person-fill" style="font-size: 1.4rem; color: #94A3B8;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3" data-label="Official ID"><code><?php echo htmlspecialchars($off['official_reg_no']); ?></code></td>
                                <td class="p-3" data-label="Official Name"><strong><?php echo htmlspecialchars($off['name']); ?></strong></td>
                                <td class="p-3" data-label="Gender"><?php echo htmlspecialchars($off['gender']); ?></td>
                                <td class="p-3" data-label="State"><?php echo htmlspecialchars($off['state']); ?></td>
                                <td class="p-3" data-label="Role"><span class="badge bg-secondary px-3 py-2"><?php echo htmlspecialchars($off['role']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-5 text-center text-muted">No registered official records match your search criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-5">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-md shadow-sm rounded-pill overflow-hidden border-0">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link border-0 px-3 py-2 text-dark fw-bold" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($stateFilter); ?>&role=<?php echo urlencode($roleFilter); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link border-0 px-3 py-2 <?php echo $i === $currentPage ? 'bg-primary text-white' : 'text-dark'; ?> fw-bold" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($stateFilter); ?>&role=<?php echo urlencode($roleFilter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link border-0 px-3 py-2 text-dark fw-bold" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&state=<?php echo urlencode($stateFilter); ?>&role=<?php echo urlencode($roleFilter); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
