<?php
// database/migrate.php - Database Migration Runner

require_once __DIR__ . '/../includes/db.php';

// Disable timeout limits for database updates
set_time_limit(0);

echo "Starting Database Migrations...\n";

try {
    // 1. Create migrations tracking table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration_name` VARCHAR(255) NOT NULL UNIQUE,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. Fetch ran migrations
    $stmt = $pdo->query("SELECT migration_name FROM migrations_log");
    $ranMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Scan migrations directory
    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        die("Migrations directory not found.\n");
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files); // Ensure chronological execution order

    $executedCount = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        if (in_array($filename, $ranMigrations)) {
            continue;
        }

        echo "Executing migration: $filename...\n";
        $sql = file_get_contents($file);
        
        // Execute the SQL queries
        // Note: exec() cannot run multiple queries containing delimiter changes easily in some configurations,
        // but standard multi-query commands work with execute() or by splitting by ";"
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        try {
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }
            // Log the migration execution
            $logStmt = $pdo->prepare("INSERT INTO migrations_log (migration_name) VALUES (?)");
            $logStmt->execute([$filename]);
            echo "Successfully executed $filename.\n";
            $executedCount++;
        } catch (\Exception $e) {
            throw new \Exception("Failed executing migration $filename: " . $e->getMessage());
        }
    }

    echo "Migrations finished successfully. Executed $executedCount migration(s).\n";

} catch (\Exception $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}
