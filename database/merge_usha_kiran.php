<?php
// database/merge_usha_kiran.php - Copies all data from 0100 into legacy regn 0068, keeps 0068 active, and deletes 0100
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Find source athlete (regn 0100 or regn 100 or email ushakiran)
    $stmtS = $pdo->query("SELECT * FROM athletes WHERE (regn_no = '0100' OR regn_no = '100' OR email LIKE '%ushakiran%') AND deleted_at IS NULL LIMIT 1");
    $source = $stmtS->fetch(PDO::FETCH_ASSOC);

    // 2. Find target athlete (regn 0068 or regn 68)
    $sourceIdCond = $source ? "AND id != " . (int)$source['id'] : "";
    $stmtT = $pdo->query("SELECT * FROM athletes WHERE (regn_no = '0068' OR regn_no = '68' OR full_name LIKE '%usha%kiran%') $sourceIdCond AND deleted_at IS NULL LIMIT 1");
    $target = $stmtT->fetch(PDO::FETCH_ASSOC);

    if ($source && $target && (int)$source['id'] !== (int)$target['id']) {
        $sourceId = (int)$source['id'];
        $targetId = (int)$target['id'];

        // Copy source details to target 0068
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
                medical_certificate = COALESCE(NULLIF(?, ''), medical_certificate),
                receipt_path = COALESCE(NULLIF(?, ''), receipt_path),
                photo_status = COALESCE(NULLIF(?, ''), photo_status),
                status = 'approved',
                deleted_at = NULL
            WHERE id = ?
        ");

        $up->execute([
            $source['full_name'], $source['father_name'], $source['mother_name'],
            $source['age_category'], $source['state'], $source['representing_for'],
            $source['district'], $source['classification'], $source['impairment_type'],
            $source['wheelchair_status'], $source['nsrs_id'], $source['aadhaar'],
            $source['mobile'], $source['email'], $source['address'], $source['pincode'],
            $source['kit_tshirt'], $source['kit_tracksuit'], $source['kit_shoe'],
            $source['photo_path'], $source['passport_file'], $source['medical_certificate'],
            $source['receipt_path'], $source['photo_status'],
            $targetId
        ]);

        // Transfer child history records
        $pdo->prepare("UPDATE athlete_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE athlete_status_history SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE athlete_registry_import SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);
        $pdo->prepare("UPDATE profile_update_requests SET athlete_id = ? WHERE athlete_id = ?")->execute([$targetId, $sourceId]);

        // Soft-delete source record 0100
        $pdo->prepare("UPDATE athletes SET deleted_at = NOW() WHERE id = ?")->execute([$sourceId]);
    }
} catch (Throwable $e) {}
