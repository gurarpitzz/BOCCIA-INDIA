<?php
// cron/discovery_sync.php - CLI trigger for content synchronization

require_once __DIR__ . '/../includes/discovery.php';

// Set infinite execution timeout
set_time_limit(0);

if (php_sapi_name() !== 'cli') {
    // Keep it secure so non-cli cannot invoke unless authorized
    require_once __DIR__ . '/../includes/auth.php';
    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die("Access denied. Admin authorization required.");
    }
}

echo "Starting Sync Discovery Engine...\n";

$engine = new ContentDiscoveryEngine($pdo);
$logs = $engine->runSync();

foreach ($logs as $log) {
    echo $log . "\n";
}
