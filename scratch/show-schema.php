<?php
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("DESCRIBE athletes");
while ($row = $stmt->fetch()) {
    echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']}\n";
}
