<?php
// database/audit_and_merge.php - Normalizes athlete regn numbers and merges duplicates
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "Starting Athlete Registration Number Audit & Normalization...\n";

try {
    // 1. First, find all groups of duplicates based on normalized registration number
    $dupStmt = $pdo->query("
        SELECT 
            CAST(regn_no AS UNSIGNED) AS normalized_regn, 
            COUNT(*) AS total_records,
            GROUP_CONCAT(id ORDER BY id ASC) AS ids,
            GROUP_CONCAT(regn_no ORDER BY id ASC) AS regn_nos
        FROM athletes 
        GROUP BY normalized_regn 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $dupStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($duplicates) . " duplicate registration number collision groups.\n";

    foreach ($duplicates as $group) {
        $ids = array_map('intval', explode(',', $group['ids']));
        $regnNos = explode(',', $group['regn_nos']);
        $normalizedReg = str_pad($group['normalized_regn'], 4, '0', STR_PAD_LEFT);

        echo "Merging Group (Normalized: $normalizedReg):\n";
        echo "  - Athlete IDs: " . implode(', ', $ids) . "\n";
        
        // The first ID (lowest ID) is the original/canonical record
        $canonicalId = $ids[0];
        $recordsToDelete = array_slice($ids, 1);

        $pdo->beginTransaction();

        // Merge children tables first
        foreach ($recordsToDelete as $delId) {
            // Transfer history records
            $upHist = $pdo->prepare("UPDATE athlete_history SET athlete_id = ? WHERE athlete_id = ?");
            $upHist->execute([$canonicalId, $delId]);

            // Transfer registry import rows
            $upImport = $pdo->prepare("UPDATE athlete_registry_import SET athlete_id = ? WHERE athlete_id = ?");
            $upImport->execute([$canonicalId, $delId]);

            // Transfer status history rows
            $upStatus = $pdo->prepare("UPDATE athlete_status_history SET athlete_id = ? WHERE athlete_id = ?");
            $upStatus->execute([$canonicalId, $delId]);

            // Update pending registrations existing_athlete_id reference
            $upApps = $pdo->prepare("UPDATE athlete_applications SET existing_athlete_id = ? WHERE existing_athlete_id = ?");
            $upApps->execute([$canonicalId, $delId]);

            // Delete the duplicate athlete record first to free up the unique constraint key
            $delAth = $pdo->prepare("DELETE FROM athletes WHERE id = ?");
            $delAth->execute([$delId]);
            echo "  - Deleted duplicate ID: $delId\n";
        }

        // Now update canonical record to normalized representation and marked as imported
        // (This is now safe since the duplicate registration number string '0001' has been deleted)
        $upCanonical = $pdo->prepare("UPDATE athletes SET regn_no = ?, digilocker_imported = 1 WHERE id = ?");
        $upCanonical->execute([$normalizedReg, $canonicalId]);
        echo "  - Updated canonical ID: $canonicalId to normalized REGN_NO: $normalizedReg\n";

        $pdo->commit();
        echo "Group merged successfully.\n\n";
    }

    // 2. Normalize all remaining non-duplicate athlete registration numbers
    echo "Normalizing remaining athlete registration numbers...\n";
    $allStmt = $pdo->query("SELECT id, regn_no FROM athletes");
    $athletes = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $normalizedCount = 0;
    foreach ($athletes as $ath) {
        $reg = trim($ath['regn_no']);
        if (is_numeric($reg)) {
            $norm = str_pad((int)$reg, 4, '0', STR_PAD_LEFT);
            if ($reg !== $norm) {
                // To prevent unique constraint issues, let's verify if $norm already exists
                $chk = $pdo->prepare("SELECT id FROM athletes WHERE regn_no = ? AND id != ?");
                $chk->execute([$norm, $ath['id']]);
                if (!$chk->fetchColumn()) {
                    $up = $pdo->prepare("UPDATE athletes SET regn_no = ? WHERE id = ?");
                    $up->execute([$norm, $ath['id']]);
                    $normalizedCount++;
                }
            }
        }
    }
    echo "Normalized $normalizedCount registration numbers to standard 4-digit strings.\n";
    echo "Audit and Merge completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error during audit and merge: " . $e->getMessage() . "\n");
}
