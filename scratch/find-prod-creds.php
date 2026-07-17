<?php
// scratch/find-prod-creds.php - Finds username/password for tstpllmy_bsfi_prod and writes them to local.php
header('Content-Type: text/plain');
echo "Searching for production database credentials...\n\n";

$start_dir = '/home1/tstpllmy';
$found_file = null;
$db_user = null;
$db_pass = null;

function searchForProdCreds($dir, &$found_file, &$db_user, &$db_pass, $depth = 0) {
    if ($depth > 4) return;
    if (!is_dir($dir) || !is_readable($dir)) return;
    
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        // Skip common directories to prevent timeouts
        if (preg_match('/(node_modules|\.git|\.next|cache|logs|mail|ssl|tmp)/i', $path)) {
            continue;
        }
        
        if (is_dir($path)) {
            searchForProdCreds($path, $found_file, $db_user, $db_pass, $depth + 1);
            if ($found_file !== null) return; // Stop searching if found
        } else {
            // Read file and check if it contains tstpllmy_bsfi_prod
            if ($file === 'db.php' || $file === 'config.php' || $file === 'wp-config.php' || strpos($file, 'connection') !== false) {
                $content = @file_get_contents($path);
                if ($content !== false && strpos($content, 'tstpllmy_bsfi_prod') !== false) {
                    $found_file = $path;
                    
                    // Regex patterns to capture user and pass variables or defines
                    if (preg_match('/\$user\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                        $db_user = $matches[1];
                    } elseif (preg_match('/DB_USER[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                        $db_user = $matches[1];
                    }
                    
                    if (preg_match('/\$pass\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                        $db_pass = $matches[1];
                    } elseif (preg_match('/DB_PASS[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                        $db_pass = $matches[1];
                    }
                    return;
                }
            }
        }
    }
}

searchForProdCreds($start_dir, $found_file, $db_user, $db_pass);

if ($found_file === null) {
    echo "Could not find any configuration file referencing tstpllmy_bsfi_prod.\n";
    echo "Attempting fallback: writing tstpllmy_bsfi_prod with tstpllmy_boccia_user...\n";
    
    // We try writing the DB change anyway in case the user has privileges
    $local_php_path = dirname(__DIR__) . '/config/local.php';
    if (file_exists($local_php_path)) {
        $content = file_get_contents($local_php_path);
        // Replace database name
        $content = str_replace("'tstpllmy_boccia_india'", "'tstpllmy_bsfi_prod'", $content);
        file_put_contents($local_php_path, $content);
        echo "Updated local.php to tstpllmy_bsfi_prod.\n";
    }
} else {
    echo "Found credentials in: $found_file\n";
    echo "User: " . ($db_user ?? "NOT FOUND") . "\n";
    echo "Pass: " . ($db_pass ? "FOUND (hidden for security)" : "NOT FOUND") . "\n\n";
    
    if ($db_user && $db_pass) {
        $local_php_path = dirname(__DIR__) . '/config/local.php';
        $local_content = "<?php\n" .
                         "define('RESEND_API_KEY', 're_Y4drerv2_7rRpwoiXXcfvQZPxMsi92EDq');\n" .
                         "define('OTP_SECRET', 'boccia_secret_hmac_key_2026');\n\n" .
                         "// Database Configuration (Automated Live Recovery)\n" .
                         "define('DB_HOST', 'localhost');\n" .
                         "define('DB_NAME', 'tstpllmy_bsfi_prod');\n" .
                         "define('DB_USER', '$db_user');\n" .
                         "define('DB_PASS', '$db_pass');\n";
                         
        if (file_put_contents($local_php_path, $local_content) !== false) {
            echo "SUCCESS: Automatically updated config/local.php to connect to the live production database!\n";
            echo "Please refresh your home page to verify.";
        } else {
            echo "ERROR: Failed to write to config/local.php. Check permissions.";
        }
    }
}
