<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/restore_local_test_data.sql');
    $pdo->exec($sql);
    echo "Success: Applied restore_local_test_data.sql locally.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
