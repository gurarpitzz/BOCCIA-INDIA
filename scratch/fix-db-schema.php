<?php
// scratch/fix-db-schema.php - Safely recreates gallery tables with correct schema
header('Content-Type: text/plain');
echo "Recreating gallery database tables with correct schema...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // 1. Temporarily disable foreign keys to allow dropping
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    echo "Disabled foreign key checks.\n";
    
    // 2. Drop old tables
    $pdo->exec("DROP TABLE IF EXISTS `gallery_images`;");
    echo "Dropped old gallery_images table.\n";
    $pdo->exec("DROP TABLE IF EXISTS `gallery_albums`;");
    echo "Dropped old gallery_albums table.\n";
    
    // 3. Re-enable foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Re-enabled foreign key checks.\n\n";
    
    // 4. Run migration 006
    echo "Applying 006_gallery_revamp_v1_3.sql... ";
    $sql006 = file_get_contents(dirname(__DIR__) . '/database/migrations/006_gallery_revamp_v1_3.sql');
    $pdo->exec($sql006);
    echo "SUCCESS\n";
    
    // 5. Run migration 008
    echo "Applying 008_gallery_hierarchy.sql... ";
    $sql008 = file_get_contents(dirname(__DIR__) . '/database/migrations/008_gallery_hierarchy.sql');
    $pdo->exec($sql008);
    echo "SUCCESS\n";
    
    echo "\nDatabase schema fix complete! You can now visit run_sync.php to synchronize the gallery and navbar.";
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage();
}
