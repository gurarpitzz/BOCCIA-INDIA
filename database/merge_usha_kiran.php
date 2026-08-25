<?php
// database/merge_usha_kiran.php - Keeps registration 0100 as the active profile and soft-deletes 0068
require_once __DIR__ . '/../includes/db.php';

try {
    // Target = 0100 (ID 199), Legacy = 0068 (ID 68)
    $target = $pdo->query("SELECT * FROM athletes WHERE (id = 199 OR CAST(regn_no AS UNSIGNED) = 100 OR regn_no = '0100') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $legacy = $pdo->query("SELECT * FROM athletes WHERE (id = 68 OR CAST(regn_no AS UNSIGNED) = 68 OR regn_no = '0068') LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($target && $legacy && (int)$target['id'] !== (int)$legacy['id']) {
        $targetId = (int)$target['id'];
        $legacyId = (int)$legacy['id'];

        $pdo->beginTransaction();

        // Restore target 0100 and ensure status is approved
        $pdo->prepare("UPDATE athletes SET deleted_at = NULL, status = 'approved' WHERE id = ?")->execute([$targetId]);

        // Transfer any child records from 0068 to 0100
        $pdo->prepare("UPDATE athlete_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $legacyId]);
        $pdo->prepare("UPDATE athlete_status_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $legacyId]);
        $pdo->prepare("UPDATE athlete_registry_import SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $legacyId]);
        $pdo->prepare("UPDATE profile_update_requests SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $legacyId]);

        // Soft-delete legacy 0068
        $pdo->prepare("UPDATE athletes SET deleted_at = NOW() WHERE id = ?")->execute([$legacyId]);

        $pdo->commit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
