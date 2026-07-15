<?php
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT regn_no, full_name, email FROM athletes ORDER BY CAST(regn_no AS UNSIGNED) DESC LIMIT 10");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo "Regn: {$row['regn_no']} | Name: {$row['full_name']} | Email: {$row['email']}\n";
}
