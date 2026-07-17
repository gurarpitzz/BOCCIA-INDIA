<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT id, discipline, date_text, sort_order FROM schedules");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | Title: {$row['discipline']} | DateText: {$row['date_text']} | SortOrder: {$row['sort_order']}\n";
}
