<?php
// scratch/check-upload-limits.php - Diagnoses upload limits and folder permissions
header('Content-Type: text/plain');

echo "PHP Upload Configuration:\n";
echo "=========================\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size:       " . ini_get('post_max_size') . "\n";
echo "memory_limit:        " . ini_get('memory_limit') . "\n";
echo "upload_tmp_dir:      " . ini_get('upload_tmp_dir') . "\n";

echo "\nFolder Permissions:\n";
echo "===================\n";
$targetDir = dirname(__DIR__) . '/uploads/circulars/';
echo "Target path: " . $targetDir . "\n";
if (is_dir($targetDir)) {
    echo "Directory exists: YES\n";
    echo "Is writable:      " . (is_writable($targetDir) ? "YES" : "NO") . "\n";
    echo "Permissions:      " . substr(sprintf('%o', fileperms($targetDir)), -4) . "\n";
} else {
    echo "Directory exists: NO (creating directory...)\n";
    if (mkdir($targetDir, 0755, true)) {
        echo "Successfully created directory!\n";
        echo "Is writable:      " . (is_writable($targetDir) ? "YES" : "NO") . "\n";
    } else {
        echo "Failed to create directory!\n";
    }
}

// Check fileinfo extension
echo "\nExtensions:\n";
echo "===========\n";
echo "fileinfo enabled: " . (extension_loaded('fileinfo') ? "YES" : "NO") . "\n";
echo "mime_content_type function exists: " . (function_exists('mime_content_type') ? "YES" : "NO") . "\n";
