<?php
// database/merge_usha_kiran.php - Copies all data from regn 0100 (id 199) into legacy regn 0068 (id 68), keeps 0068 active, and deletes 0100
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Copy all non-empty fields from 0100 (id 199 / regn 0100) into 0068 (id 68 / regn 0068)
    $pdo->exec("
        UPDATE athletes target
        JOIN athletes source ON (source.id = 199 OR source.id = 100 OR source.regn_no = '0100' OR CAST(source.regn_no AS UNSIGNED) = 100)
        SET 
            target.full_name = COALESCE(NULLIF(source.full_name, ''), target.full_name),
            target.father_name = COALESCE(NULLIF(source.father_name, ''), target.father_name),
            target.mother_name = COALESCE(NULLIF(source.mother_name, ''), target.mother_name),
            target.age_category = COALESCE(NULLIF(source.age_category, ''), target.age_category),
            target.state = COALESCE(NULLIF(source.state, ''), target.state),
            target.representing_for = COALESCE(NULLIF(source.representing_for, ''), target.representing_for),
            target.district = COALESCE(NULLIF(source.district, ''), target.district),
            target.classification = COALESCE(NULLIF(source.classification, ''), target.classification),
            target.impairment_type = COALESCE(NULLIF(source.impairment_type, ''), target.impairment_type),
            target.wheelchair_status = COALESCE(NULLIF(source.wheelchair_status, ''), target.wheelchair_status),
            target.nsrs_id = COALESCE(NULLIF(source.nsrs_id, ''), target.nsrs_id),
            target.aadhaar = COALESCE(NULLIF(source.aadhaar, ''), target.aadhaar),
            target.mobile = COALESCE(NULLIF(source.mobile, ''), target.mobile),
            target.email = COALESCE(NULLIF(source.email, ''), target.email),
            target.address = COALESCE(NULLIF(source.address, ''), target.address),
            target.pincode = COALESCE(NULLIF(source.pincode, ''), target.pincode),
            target.kit_tshirt = COALESCE(NULLIF(source.kit_tshirt, ''), target.kit_tshirt),
            target.kit_tracksuit = COALESCE(NULLIF(source.kit_tracksuit, ''), target.kit_tracksuit),
            target.kit_shoe = COALESCE(NULLIF(source.kit_shoe, ''), target.kit_shoe),
            target.photo_path = COALESCE(NULLIF(source.photo_path, ''), target.photo_path),
            target.passport_file = COALESCE(NULLIF(source.passport_file, ''), target.passport_file),
            target.medical_cert_file = COALESCE(NULLIF(source.medical_cert_file, ''), target.medical_cert_file),
            target.receipt_path = COALESCE(NULLIF(source.receipt_path, ''), target.receipt_path),
            target.photo_status = COALESCE(NULLIF(source.photo_status, ''), target.photo_status),
            target.status = 'approved',
            target.deleted_at = NULL
        WHERE (target.id = 68 OR target.regn_no = '0068' OR CAST(target.regn_no AS UNSIGNED) = 68)
          AND source.id != target.id;
    ");

    // 2. Transfer child history records
    $pdo->exec("
        UPDATE athlete_history h
        JOIN athletes target ON (target.id = 68 OR target.regn_no = '0068' OR CAST(target.regn_no AS UNSIGNED) = 68)
        JOIN athletes source ON (source.id = 199 OR source.id = 100 OR source.regn_no = '0100' OR CAST(source.regn_no AS UNSIGNED) = 100)
        SET h.athlete_id = target.id
        WHERE h.athlete_id = source.id AND target.id != source.id;
    ");

    // 3. Soft-delete 0100 (id 199 / 100)
    $pdo->exec("
        UPDATE athletes 
        SET deleted_at = NOW() 
        WHERE (id = 199 OR id = 100 OR regn_no = '0100' OR CAST(regn_no AS UNSIGNED) = 100)
          AND id != 68 AND CAST(regn_no AS UNSIGNED) != 68;
    ");
} catch (Exception $e) {}
