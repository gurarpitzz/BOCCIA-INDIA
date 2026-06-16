<?php
// save-event.php - Admin / Editor event management script

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Access restricted.']);
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
$end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'upcoming';

if (empty($title) || empty($location) || empty($start_date) || empty($end_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields are missing.']);
    exit();
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE events SET title=?, location=?, description=?, start_date=?, end_date=?, status=? WHERE id=?");
        $stmt->execute([$title, $location, $description, $start_date, $end_date, $status, $id]);
        logAction($pdo, "Updated Event", "events", $id, "Title: $title");
        echo json_encode(['success' => 'Event updated successfully.']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO events (title, location, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $location, $description, $start_date, $end_date, $status]);
        $newId = $pdo->lastInsertId();
        logAction($pdo, "Created Event", "events", $newId, "Title: $title");
        echo json_encode(['success' => 'Event created successfully.', 'id' => $newId]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
