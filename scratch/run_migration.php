<?php
require 'includes/db.php';
try {
    $sql = file_get_contents('database/migrations/007_document_pages_v1_0.sql');
    $pdo->exec($sql);
    echo "Migration 007 run successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
