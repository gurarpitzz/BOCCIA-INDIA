<?php
// scratch/change-db-user.php - Switches database configurations to tstpllmy_bsfi_user
header('Content-Type: text/plain');
echo "Switching database credentials to tstpllmy_bsfi_user...\n\n";

$local_php_path = dirname(__DIR__) . '/config/local.php';

if (file_exists($local_php_path)) {
    $local_content = "<?php\n" .
                     "define('RESEND_API_KEY', 're_Y4drerv2_7rRpwoiXXcfvQZPxMsi92EDq');\n" .
                     "define('OTP_SECRET', 'boccia_secret_hmac_key_2026');\n\n" .
                     "// Database Configuration (Automated Live Switch)\n" .
                     "define('DB_HOST', 'localhost');\n" .
                     "define('DB_NAME', 'tstpllmy_bsfi_prod');\n" .
                     "define('DB_USER', 'tstpllmy_bsfi_user');\n" .
                     "define('DB_PASS', 'Boccia@2026!India#DB');\n";
                     
    if (file_put_contents($local_php_path, $local_content) !== false) {
        echo "SUCCESS: Automatically switched database credentials in config/local.php!\n";
        echo "Please run check-db-stats.php to verify the connection.";
    } else {
        echo "ERROR: Failed to write to config/local.php. Check file permissions.";
    }
} else {
    echo "ERROR: config/local.php file not found on the server.";
}
