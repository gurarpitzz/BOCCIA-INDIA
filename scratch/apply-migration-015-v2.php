<?php
// scratch/apply-migration-015-v2.php - Apply Migration 015-v2 (competition_scope) to database
header('Content-Type: text/plain');
echo "Applying Migration 015-v2 to database...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // 1. Add competition_scope if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `competition_scope` ENUM('International', 'National', 'State') DEFAULT 'National'");
        echo "SUCCESS: Added column competition_scope\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "INFO: Column competition_scope already exists\n";
        } else {
            throw $e;
        }
    }

    // 2. Add result columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `result_url` VARCHAR(255) DEFAULT NULL");
        echo "SUCCESS: Added column result_url\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "INFO: Column result_url already exists\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `result_button_text` VARCHAR(255) DEFAULT 'View Results'");
        echo "SUCCESS: Added column result_button_text\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "INFO: Column result_button_text already exists\n";
        } else {
            throw $e;
        }
    }

    // 3. Migrate data if is_national exists
    $colsStmt = $pdo->query("SHOW COLUMNS FROM `schedules` LIKE 'is_national'");
    $hasIsNational = $colsStmt->fetch();
    if ($hasIsNational) {
        echo "Migrating data from is_national to competition_scope...\n";
        // If is_national was 1, it's National. Otherwise, let's treat it as International for existing events since they were the only non-national ones
        $pdo->exec("UPDATE `schedules` SET `competition_scope` = 'National' WHERE `is_national` = 1");
        $pdo->exec("UPDATE `schedules` SET `competition_scope` = 'International' WHERE `is_national` = 0");
        
        $pdo->exec("ALTER TABLE `schedules` DROP COLUMN `is_national`");
        echo "SUCCESS: Migrated data and dropped column is_national\n";
    }

} catch (Exception $e) {
    echo "ERROR: Migration failed: " . $e->getMessage() . "\n";
}

echo "\nMigration execution completed.\n";
