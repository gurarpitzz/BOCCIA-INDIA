<?php
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT CAST(regn_no AS UNSIGNED) as num FROM athletes WHERE CAST(regn_no AS UNSIGNED) BETWEEN 1 AND 99 ORDER BY num ASC");
$taken = $stmt->fetchAll(PDO::FETCH_COLUMN);

$gaps = [];
for ($i = 1; $i <= 99; $i++) {
    if (!in_array($i, $taken)) {
        $gaps[] = $i;
    }
}

echo "Available gaps in regn_no: " . implode(', ', $gaps) . "\n";
