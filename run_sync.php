<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/discovery.php';

try {
    $engine = new ContentDiscoveryEngine($pdo);
    echo "<h1>Synchronizing Content Registry...</h1>";
    echo "<pre>";
    $logs = $engine->runSync();
    print_r($logs);
    echo "</pre>";
    echo "<h3>Sync Complete! Please refresh your home page.</h3>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
