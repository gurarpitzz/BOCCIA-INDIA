<?php
// scratch/apply-migration-015.php - Apply Migration 015 to live database
header('Content-Type: text/plain');
echo "Applying Migration 015 to database...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

$queries = [
    "ALTER TABLE `schedules` ADD COLUMN `is_national` TINYINT(1) DEFAULT 0",
    "ALTER TABLE `schedules` ADD COLUMN `result_url` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `schedules` ADD COLUMN `result_button_text` VARCHAR(255) DEFAULT 'View Results'"
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "SUCCESS: Executed query: $query\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "INFO: Column already exists for query: $query\n";
        } else {
            echo "ERROR: Query failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigration execution completed.\n";
