<?php
// scratch/setup-db.php - Automatically configure production database connection overrides
header('Content-Type: text/plain');
echo "Automating database configuration...\n\n";

$local_php_path = dirname(__DIR__) . '/config/local.php';

if (file_exists($local_php_path)) {
    $content = file_get_contents($local_php_path);
    
    // Check if DB_HOST is already defined to prevent duplicates
    if (strpos($content, 'DB_HOST') === false) {
        $db_block = "\n\n// Database Configuration (Automated Setup)\n" .
                    "define('DB_HOST', 'localhost');\n" .
                    "define('DB_NAME', 'tstpllmy_boccia_india');\n" .
                    "define('DB_USER', 'tstpllmy_boccia_user');\n" .
                    "define('DB_PASS', 'Boccia@2026!India#DB');\n";
                    
        if (file_put_contents($local_php_path, $content . $db_block) !== false) {
            echo "SUCCESS: Database credentials successfully appended to config/local.php!\n";
            echo "Site is now ready. Visit your homepage to verify.";
        } else {
            echo "ERROR: Failed to write to config/local.php. Please check file permissions.";
        }
    } else {
        echo "INFO: Database configuration already exists in config/local.php.";
    }
} else {
    echo "ERROR: config/local.php file not found on the server.";
}
