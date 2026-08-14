<?php
// download.php - Secure public PDF download router for Circulars & Notices
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    http_response_code(400);
    die('Bad Request: Invalid or missing document ID.');
}

try {
    // Fetch only published and non-deleted documents
    $stmt = $pdo->prepare("SELECT pdf_path, original_filename FROM circulars_notices WHERE id = ? AND status = 'Published' AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    die('Internal Server Error.');
}

if (!$doc) {
    http_response_code(404);
    die('Error: Document not found or is no longer available.');
}

$pdfPath = $doc['pdf_path'];
$originalName = $doc['original_filename'];

// Construct absolute path and prevent directory traversal
$baseUploadDir = realpath(__DIR__ . '/uploads/circulars/');
if ($baseUploadDir === false) {
    http_response_code(500);
    die('Internal Server Configuration Error.');
}

$absoluteFilePath = realpath(__DIR__ . '/' . $pdfPath);

if ($absoluteFilePath === false || strpos($absoluteFilePath, $baseUploadDir) !== 0 || !file_exists($absoluteFilePath)) {
    http_response_code(404);
    die('Error: Target file not found.');
}

// Double check magic bytes of the file on disk
$handle = fopen($absoluteFilePath, 'rb');
if (!$handle) {
    http_response_code(500);
    die('Error: Unable to open file.');
}
$magicBytes = fread($handle, 5);
fclose($handle);

if ($magicBytes !== '%PDF-') {
    http_response_code(415);
    die('Error: Unsupported media type.');
}

// Sanitize original filename to prevent HTTP Header Injection attacks
$cleanFilename = preg_replace('/[\r\n\t]+/', ' ', $originalName); // Strip control chars
$cleanFilename = preg_replace('/[^a-zA-Z0-9_\-\. \(\)]/', '_', $cleanFilename); // Replace symbols with underscore

// Ensure it has exactly one .pdf extension
$cleanFilename = rtrim($cleanFilename, '.pdf');
if (empty($cleanFilename)) {
    $cleanFilename = "document-" . $id;
}
$cleanFilename .= '.pdf';

// Log the public download activity
logAction($pdo, "Public Download Circular", "circular", $id, json_encode([
    'filename' => $cleanFilename,
    'ip' => $_SERVER['REMOTE_ADDR']
]));

// Clear output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Set secure download headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $cleanFilename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($absoluteFilePath));

readfile($absoluteFilePath);
exit();
