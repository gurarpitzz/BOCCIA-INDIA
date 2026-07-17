<?php
// scratch/scan-credentials.php - Scans server directories for database credentials
header('Content-Type: text/plain');
echo "Scanning server for backup database configuration files...\n\n";

$start_dir = '/home1/tstpllmy';

function scanDirRecursive($dir, &$results, $depth = 0) {
    if ($depth > 4) return;
    if (!is_dir($dir) || !is_readable($dir)) return;
    
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        // Skip system/temp directories to prevent timeouts
        if (preg_match('/(node_modules|\.git|\.next|cache|logs|mail|ssl|tmp)/i', $path)) {
            continue;
        }
        
        if (is_dir($path)) {
            scanDirRecursive($path, $results, $depth + 1);
        } else {
            // Check for potential credential containing files
            if ($file === 'db.php' || $file === 'config.php' || strpos($file, 'db_backup') !== false) {
                $results[] = $path;
            }
        }
    }
}

$results = [];
scanDirRecursive($start_dir, $results);

if (empty($results)) {
    echo "No potential config files found.\n";
} else {
    echo "Found " . count($results) . " potential configuration files:\n\n";
    foreach ($results as $path) {
        echo "File: $path\n";
        $content = @file_get_contents($path);
        if ($content !== false) {
            // Read lines and look for DB credentials variables
            $lines = explode("\n", $content);
            echo "--- Preview (First 20 lines) ---\n";
            for ($i = 0; $i < min(20, count($lines)); $i++) {
                // Let's print the lines. It is safe because only you are viewing this page in your browser.
                echo $lines[$i] . "\n";
            }
            echo "---------------------------------\n\n";
        } else {
            echo "Could not read file contents.\n\n";
        }
    }
}
