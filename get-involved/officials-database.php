<?php
// get-involved/officials-database.php - Officials directory page

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$search = trim($_GET['search'] ?? '');
$stateFilter = trim($_GET['state'] ?? '');

$query = "SELECT * FROM athletes WHERE status = 'approved' AND classification = 'Official'"; // Classifying officials under the same core user table or matching classification
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR regn_no LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($stateFilter)) {
    $query .= " AND state = ?";
    $params[] = $stateFilter;
}

$query .= " ORDER BY full_name ASC LIMIT 100";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $officials = $stmt->fetchAll();

    // Fetch lists for filter dropdowns
    $statesList = $pdo->query("SELECT DISTINCT state FROM athletes WHERE status = 'approved' ORDER BY state ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database access failure: " . $e->getMessage());
}

$page_title = "Registered Officials & Referees Database - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4">
    <div class="mb-5 border-bottom pb-3">
        <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">Registered Officials Database</h1>
        <p class="text-muted">Official directory of approved technical officials, classifiers, and referees registered with BSFI.</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 bg-light p-4 rounded-4 shadow-sm border mb-5">
        <div class="col-md-5">
            <label class="form-label fw-bold text-dark">Search Name or ID</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="E.g. Name, ID">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold text-dark">Filter by State</label>
            <select name="state" class="form-select">
                <option value="">All States</option>
                <?php foreach ($statesList as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $stateFilter === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100 py-2">Filter Records</button>
        </div>
    </form>

    <!-- Results Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-primary text-white">
                        <th class="p-3">Official ID</th>
                        <th class="p-3">Official Name</th>
                        <th class="p-3">Gender</th>
                        <th class="p-3">State</th>
                        <th class="p-3">Role / Classification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($officials) > 0): ?>
                        <?php foreach ($officials as $off): ?>
                            <tr>
                                <td class="p-3"><code><?php echo htmlspecialchars($off['regn_no']); ?></code></td>
                                <td class="p-3"><strong><?php echo htmlspecialchars($off['full_name']); ?></strong></td>
                                <td class="p-3"><?php echo htmlspecialchars($off['gender']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($off['state']); ?></td>
                                <td class="p-3"><span class="badge bg-secondary px-3 py-2">Technical Official</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-5 text-center text-muted">No registered official records match your search criteria. Placeholders will be populated when officials are synced.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
