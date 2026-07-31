<?php
// update_db_columns.php - Temporary self-deleting database schema update script
require_once __DIR__ . '/includes/db.php';

try {
    $added = [];
    
    // Add medical_certificate to athlete_applications
    $stmt = $pdo->query("SHOW COLUMNS FROM athlete_applications LIKE 'medical_certificate'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE athlete_applications ADD COLUMN medical_certificate VARCHAR(255) NULL AFTER receipt_path");
        $added[] = "Added medical_certificate to athlete_applications";
    } else {
        $added[] = "medical_certificate already exists in athlete_applications";
    }

    // Add medical_certificate to athletes
    $stmt = $pdo->query("SHOW COLUMNS FROM athletes LIKE 'medical_certificate'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE athletes ADD COLUMN medical_certificate VARCHAR(255) NULL AFTER receipt_path");
        $added[] = "Added medical_certificate to athletes";
    } else {
        $added[] = "medical_certificate already exists in athletes";
    }

    echo "<h3>Database Migration Successful</h3>";
    echo "<ul><li>" . implode("</li><li>", $added) . "</li></ul>";
} catch (Exception $e) {
    echo "<h3>Database Migration Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Self-delete
@unlink(__FILE__);
?>
