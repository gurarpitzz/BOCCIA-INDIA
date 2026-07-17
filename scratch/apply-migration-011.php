<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/011_add_schedule_start_date.sql');
    // Split queries by semicolon to execute one by one
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $q) {
        if (!empty($q)) {
            $pdo->exec($q);
        }
    }
    echo "Success: Applied 011_add_schedule_start_date.sql locally.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
