<?php
// api/state-summary.php - State Association details API

header('Content-Type: application/json');

$state = isset($_GET['state']) ? trim($_GET['state']) : '';

if (empty($state)) {
    http_response_code(400);
    echo json_encode(['error' => 'State parameter is required.']);
    exit();
}

$jsonPath = __DIR__ . '/../database/state_associations_clean.json';
$associations = [];
if (file_exists($jsonPath)) {
    $associations = json_decode(file_get_contents($jsonPath), true);
}

// Normalize names for comparison
$normalizedSearch = strtolower($state);
$matchedData = null;

foreach ($associations as $key => $data) {
    if (strtolower($key) === $normalizedSearch) {
        $matchedData = $data;
        break;
    }
}

if ($matchedData && $matchedData['status'] !== 'Not Available') {
    echo json_encode([
        'state' => $matchedData['state_name'],
        'has_association' => true,
        'association_name' => $matchedData['association_name'],
        'contact_person' => $matchedData['contact_person'],
        'email' => $matchedData['email'],
        'phone' => $matchedData['phone'],
        'status' => $matchedData['status']
    ]);
} else {
    echo json_encode([
        'state' => $state,
        'has_association' => false,
        'status' => 'Not Available'
    ]);
}
