<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Let's count them first
    $stmt = $pdo->query("SELECT COUNT(*) FROM athletes WHERE CAST(regn_no AS UNSIGNED) > 99");
    $countBefore = $stmt->fetchColumn();
    
    // Perform delete
    $deleteStmt = $pdo->query("DELETE FROM athletes WHERE CAST(regn_no AS UNSIGNED) > 99");
    $deletedRows = $deleteStmt->rowCount();
    
    echo "Success: Found $countBefore athletes with regn_no > 99. Deleted $deletedRows athletes.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
