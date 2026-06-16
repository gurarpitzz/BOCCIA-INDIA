<?php
// api/state-summary.php - State-level Athlete statistics API

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$state = isset($_GET['state']) ? trim($_GET['state']) : '';

if (empty($state)) {
    http_response_code(400);
    echo json_encode(['error' => 'State parameter is required.']);
    exit();
}

try {
    // Check if state exists in states table
    $stateStmt = $pdo->prepare("SELECT name FROM states WHERE name = ? AND active = 1");
    $stateStmt->execute([$state]);
    $stateRow = $stateStmt->fetch();
    
    if (!$stateRow) {
        $stateName = $state;
    } else {
        $stateName = $stateRow['name'];
    }

    // Get Approved counts & classification splits
    $approvedStmt = $pdo->prepare("
        SELECT classification, COUNT(*) as count 
        FROM athletes 
        WHERE representing_for = ? AND status = 'approved' 
        GROUP BY classification
    ");
    $approvedStmt->execute([$stateName]);
    
    $approvedCount = 0;
    $splits = ['bc1' => 0, 'bc2' => 0, 'bc3' => 0, 'bc4' => 0];
    
    while ($row = $approvedStmt->fetch()) {
        $cls = strtolower(trim($row['classification']));
        $cnt = (int)$row['count'];
        $approvedCount += $cnt;
        if (isset($splits[$cls])) {
            $splits[$cls] = $cnt;
        }
    }

    // Role-based visibility
    $isLoggedIn = isLoggedIn();
    $role = $isLoggedIn ? ($_SESSION['role'] ?? '') : null;
    
    $pendingCount = 0;
    $canViewDetails = $isLoggedIn;
    $detailsUrl = null;
    $exportUrl = null;

    if ($isLoggedIn) {
        // Fetch pending count
        $pendingStmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM athletes 
            WHERE representing_for = ? AND status = 'pending'
        ");
        $pendingStmt->execute([$stateName]);
        $pendingCount = (int)$pendingStmt->fetch()['count'];
        
        // Setup admin/view links
        $detailsUrl = 'admin/athletes.php?state=' . urlencode($stateName);
        if ($role === 'admin') {
            $exportUrl = 'api/export.php?type=csv&state=' . urlencode($stateName);
        }
    }

    echo json_encode([
        'state' => $stateName,
        'approved' => $approvedCount,
        'pending' => $isLoggedIn ? $pendingCount : 0,
        'classifications' => $splits,
        'role' => $role,
        'can_view_details' => $canViewDetails,
        'details_url' => $detailsUrl,
        'export_url' => $exportUrl
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
