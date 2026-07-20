<?php
// check-image-album.php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

echo "--- ALBUMS ---\n";
$stmt = $pdo->query("SELECT id, title, slug FROM gallery_albums");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- IMAGE #38 ---\n";
$stmt = $pdo->prepare("SELECT id, caption, album_id FROM gallery_images WHERE id = 38");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
