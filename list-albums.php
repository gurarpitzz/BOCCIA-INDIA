<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
$albums = $pdo->query("SELECT id, title FROM gallery_albums")->fetchAll(PDO::FETCH_ASSOC);
foreach ($albums as $alb) {
    echo "ID: " . $alb['id'] . " - Title: " . $alb['title'] . "\n";
}
