<?php
// scratch/test-registration.php - Dynamic verification script for Event Registration System
require_once __DIR__ . '/../includes/db.php';

echo "BSFI Event Registration System Upgrade - Verification Report\n";
echo "========================================================\n\n";

// 1. Table schema verification
$tables = ['schedules', 'event_form_fields', 'event_registrations', 'event_registration_answers', 'site_settings'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("DESCRIBE `$t`");
        echo "✅ Table '$t' exists and schema loaded.\n";
    } catch (PDOException $e) {
        echo "❌ Table '$t' verification failed: " . $e->getMessage() . "\n";
    }
}

echo "\n2. Centralized Bank Settings Verification\n";
try {
    $stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'payment_%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 5) {
        echo "✅ All 5 centralized federation bank settings are seeded.\n";
        foreach ($rows as $r) {
            echo "   - " . $r['setting_key'] . ": " . $r['setting_value'] . "\n";
        }
    } else {
        echo "⚠️ Seeding incomplete: Found " . count($rows) . " of 5 settings.\n";
    }
} catch (PDOException $e) {
    echo "❌ Bank settings query failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing database transactions integrity\n";
try {
    $pdo->beginTransaction();
    
    // Add temp schedule
    $stmt = $pdo->prepare("INSERT INTO schedules (discipline, date_text, venue, active, registration_mode, registration_fee) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Para Boccia Test Event', '10-12 Aug 2026', 'Verification Court', 1, 'internal', 1500.00]);
    $test_event_id = $pdo->lastInsertId();
    
    // Add custom field
    $stmt = $pdo->prepare("INSERT INTO event_form_fields (schedule_id, field_label, field_type, is_required) VALUES (?, ?, ?, ?)");
    $stmt->execute([$test_event_id, 'Verification ID Code', 'text', 1]);
    
    $pdo->commit();
    echo "✅ Transaction committed successfully. Created test event ID: $test_event_id\n";
    
    // Clean up
    $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$test_event_id]);
    echo "✅ Test event cleaned up.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Database transaction failed: " . $e->getMessage() . "\n";
}
