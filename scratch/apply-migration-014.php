<?php
// scratch/apply-migration-014.php - Locally runs migration 014 to add recognition-certificates row
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/014_add_recognition_certificates.sql');
    $pdo->exec($sql);
    echo "Migration 014 applied successfully!\n";
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
}
