<?php
// scratch/seed_test_members.php - Dynamically generate test members for development purposes.
// This script is restricted to local development environments.

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();

    echo "--- Dynamic Seeding of Test Members ---\n";

    // 1. GENERATE TEST ATHLETES
    // Find highest approved live athlete number
    $stmt = $pdo->query("SELECT MAX(CAST(regn_no AS UNSIGNED)) FROM athletes");
    $maxAthNo = (int)$stmt->fetchColumn();
    if ($maxAthNo < 99) {
        $maxAthNo = 99;
    }

    $athletesToSeed = [
        ['name' => 'Test Athlete Alpha', 'email' => 'gurarpit.sml@gmail.com', 'gender' => 'MALE', 'class' => 'BC1'],
        ['name' => 'Test Athlete Beta', 'email' => 'mehardeep.sim@gmail.com', 'gender' => 'FEMALE', 'class' => 'BC2']
    ];

    foreach ($athletesToSeed as $index => $ath) {
        $nextNo = $maxAthNo + 1 + $index;
        $regnNo = str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        
        $ins = $pdo->prepare("
            INSERT INTO athletes 
            (regn_no, full_name, gender, dob, email, state, representing_for, classification, status, photo_status) 
            VALUES 
            (?, ?, ?, '1996-01-01', ?, 'Punjab', 'Punjab', ?, 'approved', 'verified')
        ");
        $ins->execute([$regnNo, "{$ath['name']} ({$regnNo})", $ath['gender'], $ath['email'], $ath['class']]);
        echo "Created Test Athlete: Name: {$ath['name']} ({$regnNo}) | Reg No: $regnNo | Email: {$ath['email']}\n";
    }

    // 2. GENERATE TEST OFFICIALS
    // Find highest approved live official suffix
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(official_reg_no, 4) AS UNSIGNED)) FROM officials WHERE official_reg_no LIKE 'OF-%'");
    $maxOffNo = (int)$stmt->fetchColumn();
    $nextOffNo = $maxOffNo + 1;
    $officialId = "OF-" . str_pad($nextOffNo, 4, '0', STR_PAD_LEFT);

    $insOff = $pdo->prepare("
        INSERT INTO officials 
        (official_reg_no, name, role, gender, dob, state, phone, email, status, photo_status) 
        VALUES 
        (?, ?, 'referee', 'MALE', '1990-01-01', 'Punjab', '9999999999', 'testofficial@example.local', 'approved', 'verified')
    ");
    $insOff->execute([$officialId, "Test Official ({$officialId})"]);
    echo "Created Test Official: Name: Test Official ({$officialId}) | Official Reg No: $officialId | Email: testofficial@example.local\n";

    $pdo->commit();
    echo "\nSuccess: Dynamic test seeding completed successfully!\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error seeding test members: " . $e->getMessage() . "\n";
}
