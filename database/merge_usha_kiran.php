<?php
// database/merge_usha_kiran.php - Auto-merges duplicate regn_no 0100 into legacy 0068
require_once __DIR__ . '/../includes/db.php';

try {
    // Match by Primary Key ID (Target ID = 68, Source ID = 199)
    $target = $pdo->query("SELECT * FROM athletes WHERE (id = 68 OR CAST(regn_no AS UNSIGNED) = 68 OR regn_no = '0068') AND deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $source = $pdo->query("SELECT * FROM athletes WHERE (id = 199 OR CAST(regn_no AS UNSIGNED) = 100 OR regn_no = '0100') AND deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($target && $source) {
        $targetId = (int)$target['id'];
        $sourceId = (int)$source['id'];

        $pdo->beginTransaction();

        // Copy missing details from 0100 into 0068
        $up = $pdo->prepare("
            UPDATE athletes SET
                full_name = COALESCE(NULLIF(?, ''), full_name),
                father_name = COALESCE(NULLIF(?, ''), father_name),
                mother_name = COALESCE(NULLIF(?, ''), mother_name),
                age_category = COALESCE(NULLIF(?, ''), age_category),
                state = COALESCE(NULLIF(?, ''), state),
                representing_for = COALESCE(NULLIF(?, ''), representing_for),
                district = COALESCE(NULLIF(?, ''), district),
                classification = COALESCE(NULLIF(?, ''), classification),
                impairment_type = COALESCE(NULLIF(?, ''), impairment_type),
                wheelchair_status = COALESCE(NULLIF(?, ''), wheelchair_status),
                nsrs_id = COALESCE(NULLIF(?, ''), nsrs_id),
                aadhaar = COALESCE(NULLIF(?, ''), aadhaar),
                mobile = COALESCE(NULLIF(?, ''), mobile),
                email = COALESCE(NULLIF(?, ''), email),
                address = COALESCE(NULLIF(?, ''), address),
                pincode = COALESCE(NULLIF(?, ''), pincode),
                kit_tshirt = COALESCE(NULLIF(?, ''), kit_tshirt),
                kit_tracksuit = COALESCE(NULLIF(?, ''), kit_tracksuit),
                kit_shoe = COALESCE(NULLIF(?, ''), kit_shoe),
                photo_path = COALESCE(NULLIF(?, ''), photo_path),
                passport_file = COALESCE(NULLIF(?, ''), passport_file),
                medical_cert_file = COALESCE(NULLIF(?, ''), medical_cert_file),
                receipt_path = COALESCE(NULLIF(?, ''), receipt_path),
                photo_status = COALESCE(NULLIF(?, ''), photo_status),
                status = 'approved'
            WHERE id = ?
        ");

        $up->execute([
            $source['full_name'], $source['father_name'], $source['mother_name'],
            $source['age_category'], $source['state'], $source['representing_for'],
            $source['district'], $source['classification'], $source['impairment_type'],
            $source['wheelchair_status'], $source['nsrs_id'], $source['aadhaar'],
            $source['mobile'], $source['email'], $source['address'], $source['pincode'],
            $source['kit_tshirt'], $source['kit_tracksuit'], $source['kit_shoe'],
            $source['photo_path'], $source['passport_file'], $source['medical_cert_file'],
            $source['receipt_path'], $source['photo_status'],
            $targetId
        ]);

        // Transfer child records
        $pdo->prepare("UPDATE athlete_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE athlete_status_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE athlete_registry_import SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE profile_update_requests SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);

        // Soft-delete duplicate entry 0100
        $pdo->prepare("UPDATE athletes SET deleted_at = NOW() WHERE id = ?")->execute([$sourceId]);

        $pdo->commit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
