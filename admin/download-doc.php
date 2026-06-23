<?php
// download-doc.php - Secure authenticated document streaming and audit logger
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Must be logged in to access administrative documents
requireLogin();

$fileParam = trim($_GET['file'] ?? '');
if (empty($fileParam)) {
    http_response_code(400);
    die('Bad Request: Missing file parameter.');
}

// Map the DB path prefix to the physical path
if (strpos($fileParam, 'private_uploads/') === 0) {
    $relativePath = substr($fileParam, strlen('private_uploads/'));
    $targetFile = PRIVATE_UPLOADS_DIR . $relativePath;
    $baseFolder = PRIVATE_UPLOADS_DIR;
} elseif (strpos($fileParam, 'uploads/') === 0) {
    // Keep support for any legacy public paths
    $targetFile = dirname(__DIR__) . '/' . $fileParam;
    $baseFolder = dirname(__DIR__) . '/uploads/';
} else {
    http_response_code(403);
    die('Access Denied: Invalid directory path.');
}

// Prevent directory traversal using absolute path resolution
$realTarget = realpath($targetFile);
$realBase = realpath($baseFolder);

if ($realTarget === false || $realBase === false || strpos($realTarget, $realBase) !== 0 || !file_exists($realTarget)) {
    http_response_code(404);
    die('Error: File not found or access denied.');
}

// Enforce strict administrative roles for passports
$isPassport = (strpos(strtolower($fileParam), 'passport') !== false || strpos(strtolower($realTarget), 'passports') !== false);
if ($isPassport && ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Access Denied: Passport files are restricted to Administrators only.');
}

// Map database records to identify the owner profile for audit trail
$targetType = 'document';
$targetId = null;
$detailsArr = [
    'file' => basename($realTarget),
    'ip' => $_SERVER['REMOTE_ADDR']
];

// Check Athlete profiles
$stmt = $pdo->prepare("SELECT id, regn_no, full_name FROM athletes WHERE receipt_path = ? OR passport_file = ? LIMIT 1");
$stmt->execute([$fileParam, $fileParam]);
$entity = $stmt->fetch();
if ($entity) {
    $targetType = 'athlete';
    $targetId = $entity['id'];
    $detailsArr['regn_no'] = $entity['regn_no'];
    $detailsArr['name'] = $entity['full_name'];
} else {
    // Check Official profiles
    $stmt = $pdo->prepare("SELECT id, official_reg_no, name FROM officials WHERE receipt_path = ? LIMIT 1");
    $stmt->execute([$fileParam]);
    $entity = $stmt->fetch();
    if ($entity) {
        $targetType = 'official';
        $targetId = $entity['id'];
        $detailsArr['official_reg_no'] = $entity['official_reg_no'];
        $detailsArr['name'] = $entity['name'];
    } else {
        // Check Player applications
        $stmt = $pdo->prepare("SELECT id, full_name FROM athlete_applications WHERE receipt_path = ? LIMIT 1");
        $stmt->execute([$fileParam]);
        $entity = $stmt->fetch();
        if ($entity) {
            $targetType = 'athlete_application';
            $targetId = $entity['id'];
            $detailsArr['name'] = $entity['full_name'];
        } else {
            // Check Official applications
            $stmt = $pdo->prepare("SELECT id, full_name FROM official_applications WHERE receipt_path = ? LIMIT 1");
            $stmt->execute([$fileParam]);
            $entity = $stmt->fetch();
            if ($entity) {
                $targetType = 'official_application';
                $targetId = $entity['id'];
                $detailsArr['name'] = $entity['full_name'];
            }
        }
    }
}

// Write to Audit Logs
$action = $isPassport ? 'passport_download' : 'receipt_download';
$details = json_encode($detailsArr);
logAction($pdo, $action, $targetType, $targetId, $details);

// Clean output buffer to prevent corrupted downloads
if (ob_get_level()) {
    ob_end_clean();
}

// Stream the document with proper mime headers
$mime = mime_content_type($realTarget);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realTarget));
header('Content-Disposition: inline; filename="' . basename($realTarget) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($realTarget);
exit();
