<?php
require 'includes/db.php';
try {
    $sql = file_get_contents('database/migrations/008_gallery_hierarchy.sql');
    $pdo->exec($sql);
    echo "SQL migration executed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
