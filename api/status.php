<?php
// api/status.php - Secure AJAX status check endpoint

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$referenceId = trim($_GET['id'] ?? '');
$email = trim($_GET['email'] ?? '');

if (empty($referenceId) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Reference ID and Email address are required.']);
    exit();
}

try {
    $application = null;

    if (strpos($referenceId, 'BSFI-ATH-') === 0) {
        $stmt = $pdo->prepare("SELECT full_name, email, status, review_notes, created_at, updated_at FROM athlete_applications WHERE reference_id = ? AND email = ?");
        $stmt->execute([$referenceId, $email]);
        $application = $stmt->fetch();
    } elseif (strpos($referenceId, 'BSFI-OFF-') === 0) {
        $stmt = $pdo->prepare("SELECT full_name, email, status, review_notes, created_at, updated_at FROM official_applications WHERE reference_id = ? AND email = ?");
        $stmt->execute([$referenceId, $email]);
        $application = $stmt->fetch();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Reference ID format.']);
        exit();
    }

    if (!$application) {
        http_response_code(404);
        echo json_encode(['error' => 'No matching application record found. Please verify details.']);
        exit();
    }

    // Status mapping for public UI
    $publicStatus = $application['status'];
    if ($publicStatus === 'pending') {
        $publicStatus = 'submitted';
    }

    // Log tracking view
    $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $log->execute(['Status Viewed', "Tracking status viewed for Reference ID: {$referenceId}"]);

    echo json_encode([
        'name' => $application['full_name'],
        'status' => $publicStatus,
        'reviewNotes' => $application['review_notes'] ?? '',
        'submittedAt' => $application['created_at'],
        'updatedAt' => $application['updated_at']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error fetching status: ' . $e->getMessage()]);
}
