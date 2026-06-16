<?php
// save-athlete.php - Save or update athlete details

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Access restricted to Admin or Editor roles.']);
    exit();
}

// CSRF Validation
if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$regn_no = isset($_POST['regn_no']) ? trim($_POST['regn_no']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';
$district = isset($_POST['district']) ? trim($_POST['district']) : '';
$classification = isset($_POST['classification']) ? trim($_POST['classification']) : '';
$representing_for = isset($_POST['representing_for']) ? trim($_POST['representing_for']) : '';
$wheelchair_status = isset($_POST['wheelchair_status']) ? trim($_POST['wheelchair_status']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';

if (empty($regn_no) || empty($full_name) || empty($gender) || empty($dob) || empty($state) || empty($classification) || empty($representing_for)) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields are missing.']);
    exit();
}

try {
    if ($id > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE athletes SET regn_no=?, full_name=?, gender=?, dob=?, mobile=?, email=?, state=?, district=?, classification=?, representing_for=?, wheelchair_status=?, status=?, updated_by=? WHERE id=?");
        $stmt->execute([$regn_no, $full_name, $gender, $dob, $mobile, $email, $state, $district, $classification, $representing_for, $wheelchair_status, $status, $_SESSION['user_id'], $id]);
        logAction($pdo, "Updated Athlete", "athletes", $id, "Registration No: $regn_no");
        echo json_encode(['success' => 'Athlete updated successfully.']);
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, mobile, email, state, district, classification, representing_for, wheelchair_status, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$regn_no, $full_name, $gender, $dob, $mobile, $email, $state, $district, $classification, $representing_for, $wheelchair_status, $status, $_SESSION['user_id']]);
        $newId = $pdo->lastInsertId();
        logAction($pdo, "Created Athlete", "athletes", $newId, "Registration No: $regn_no");
        echo json_encode(['success' => 'Athlete registered successfully.', 'id' => $newId]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == 23000) {
        echo json_encode(['error' => 'An athlete with this Registration Number already exists.']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
