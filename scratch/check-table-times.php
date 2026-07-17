<?php
// scratch/check-table-times.php - Diagnostic to check when database tables were created/updated
header('Content-Type: text/plain');
echo "Checking table creation and update times on production server...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME, CREATE_TIME, UPDATE_TIME 
        FROM information_schema.tables 
        WHERE TABLE_SCHEMA = ?
        ORDER BY CREATE_TIME DESC
    ");
    $stmt->execute([$db]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad("Table Name", 30) . " | " . str_pad("Create Time", 20) . " | Update Time\n";
    echo str_repeat("-", 75) . "\n";
    
    foreach ($results as $row) {
        echo str_pad($row['TABLE_NAME'], 30) . " | " . 
             str_pad($row['CREATE_TIME'] ?? 'NULL', 20) . " | " . 
             ($row['UPDATE_TIME'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
