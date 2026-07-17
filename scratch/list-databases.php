<?php
// scratch/list-databases.php - Lists all MySQL databases the current user has access to
header('Content-Type: text/plain');
echo "Listing accessible databases...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($dbs as $db_name) {
        echo "Database: " . $db_name . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
