<?php
// scratch/run-all-migrations.php - Runs all database migrations in order
header('Content-Type: text/plain');
echo "Initializing database migrations...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

$migrations_dir = dirname(__DIR__) . '/database/migrations';
$files = scandir($migrations_dir);

$migration_files = [];
foreach ($files as $file) {
    // Only select numbered migration files (e.g. 001_..., 014_...)
    if (preg_match('/^\d{3}_.*\.sql$/', $file)) {
        $migration_files[] = $file;
    }
}

// Sort migrations numerically in order (001, 002, 003...)
sort($migration_files);

echo "Found " . count($migration_files) . " migration files.\n\n";

foreach ($migration_files as $file) {
    echo "Applying $file... ";
    try {
        $sql = file_get_contents($migrations_dir . '/' . $file);
        
        // Multi-query execution can sometimes fail in PDO depending on connection settings,
        // so we run it directly.
        $pdo->exec($sql);
        echo "SUCCESS\n";
    } catch (PDOException $e) {
        // If a table or column already exists, it might throw an error. We report it and continue.
        echo "INFO / ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration run complete. You can now visit run_sync.php to populate the navigation bar!";
