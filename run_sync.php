<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/discovery.php';

// Clear OPcache for discovery.php if it exists in cache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__DIR__ . '/includes/discovery.php', true);
}

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
