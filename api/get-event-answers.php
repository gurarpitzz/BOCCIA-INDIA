<?php
// get-event-answers.php - Retrieve dynamic custom answers for a specific registration
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Authenticate session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$reg_id = isset($_GET['reg_id']) ? (int)$_GET['reg_id'] : 0;

if ($reg_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid registration ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT f.field_label, f.field_type, a.answer_value 
        FROM event_registration_answers a
        JOIN event_form_fields f ON a.field_id = f.id
        WHERE a.registration_id = ?
        ORDER BY f.sort_order ASC, f.id ASC
    ");
    $stmt->execute([$reg_id]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($answers as $ans) {
        $formatted[] = [
            'label' => $ans['field_label'],
            'type' => $ans['field_type'],
            'value' => $ans['answer_value']
        ];
    }

    echo json_encode(['status' => 'success', 'answers' => $formatted]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
