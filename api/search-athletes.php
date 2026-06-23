<?php
// search-athletes.php - Secure Athlete search JSON API

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Authentication Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login to view athlete records.']);
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$classification = isset($_GET['classification']) ? trim($_GET['classification']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Build query
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$selectCols = $isAdmin ? "*" : "id, regn_no, full_name, gender, dob, state, district, classification, representing_for, wheelchair_status, photo_path, receipt_path, status, is_legacy_registry";
$query = "SELECT $selectCols FROM athletes WHERE 1=1";
$params = [];

if ($search !== '') {
    if (is_numeric($search)) {
        $lookupRegVal = str_pad($search, 4, '0', STR_PAD_LEFT);
        $query .= " AND (regn_no = ? OR regn_no LIKE ? OR full_name LIKE ? OR representing_for LIKE ? OR district LIKE ?)";
        $searchWild = "%$search%";
        $params[] = $lookupRegVal;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
    } else {
        $query .= " AND (regn_no LIKE ? OR full_name LIKE ? OR representing_for LIKE ? OR district LIKE ?)";
        $searchWild = "%$search%";
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
    }
}

if ($state !== '') {
    $query .= " AND representing_for = ?";
    $params[] = $state;
}

if ($classification !== '') {
    $query .= " AND classification = ?";
    $params[] = $classification;
}

if ($status !== '') {
    $query .= " AND status = ?";
    $params[] = $status;
}

// Get total count
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRows = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch data
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$athletes = $stmt->fetchAll();

echo json_encode([
    'results' => $athletes,
    'total_results' => $totalRows,
    'total_pages' => $totalPages,
    'current_page' => $page,
    'items_per_page' => $limit
]);
