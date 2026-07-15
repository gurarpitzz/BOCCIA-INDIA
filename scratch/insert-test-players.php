<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();

    // 1. Delete existing
    $pdo->query("DELETE FROM athletes WHERE regn_no IN ('0098', '0099')");

    // 2. Insert Player 1
    $stmt1 = $pdo->prepare("
        INSERT INTO athletes 
        (regn_no, full_name, gender, dob, email, state, representing_for, classification, status, photo_status) 
        VALUES 
        ('0098', 'Test Player One', 'MALE', '1995-05-15', 'gurarpit.sml@gmail.com', 'Punjab', 'Punjab', 'BC1', 'approved', 'verified')
    ");
    $stmt1->execute();

    // 3. Insert Player 2
    $stmt2 = $pdo->prepare("
        INSERT INTO athletes 
        (regn_no, full_name, gender, dob, email, state, representing_for, classification, status, photo_status) 
        VALUES 
        ('0099', 'Test Player Two', 'FEMALE', '1997-08-20', 'mehardeep.sim@gmail.com', 'Punjab', 'Punjab', 'BC2', 'approved', 'verified')
    ");
    $stmt2->execute();

    $pdo->commit();
    echo "Success: Created two test players (0098 and 0099).\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
