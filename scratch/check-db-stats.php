<?php
// scratch/check-db-stats.php - Diagnostic to check database tables and row counts
header('Content-Type: text/plain');
echo "Checking database statistics on production server...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    echo "Connected successfully to database: " . $db . "\n\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database!\n";
    } else {
        echo str_pad("Table Name", 35) . " | Row Count\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($tables as $table) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo str_pad($table, 35) . " | " . $count . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
