<?php
// scratch/fix-schedules-autoincrement.php - Fix auto-increment on schedules table
header('Content-Type: text/plain');
echo "Checking and repairing schedules table auto-increment...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // 1. Check if there's any record with ID 0
    $stmt = $pdo->query("SELECT COUNT(*) FROM schedules WHERE id = 0");
    $countZero = $stmt->fetchColumn();
    
    if ($countZero > 0) {
        echo "Found $countZero record(s) with id = 0. Repairing IDs...\n";
        
        // Find the maximum ID to safe-assign new IDs
        $maxIdStmt = $pdo->query("SELECT MAX(id) FROM schedules");
        $maxId = (int)$maxIdStmt->fetchColumn();
        if ($maxId < 1) {
            $maxId = 1;
        }
        
        // Fetch all rows with ID 0
        $zerosStmt = $pdo->query("SELECT * FROM schedules WHERE id = 0");
        $zeroRows = $zerosStmt->fetchAll();
        
        foreach ($zeroRows as $row) {
            $maxId++;
            // Temporarily disable foreign keys constraint checks if any
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Update the ID to maxId
            $updateStmt = $pdo->prepare("UPDATE schedules SET id = ? WHERE id = 0 LIMIT 1");
            $updateStmt->execute([$maxId]);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            echo "-> Updated a schedule with id = 0 to new id = $maxId\n";
        }
    } else {
        echo "No records with id = 0 found.\n";
    }

    // 2. Modify the column to ensure AUTO_INCREMENT is active
    echo "Enforcing AUTO_INCREMENT on schedules.id...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("ALTER TABLE `schedules` MODIFY COLUMN `id` INT AUTO_INCREMENT");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "SUCCESS: schedules.id is now AUTO_INCREMENT.\n";
    
} catch (Exception $e) {
    echo "ERROR: Repair failed: " . $e->getMessage() . "\n";
}

echo "\nRepair process completed.\n";
