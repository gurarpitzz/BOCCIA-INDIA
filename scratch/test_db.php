<?php
require __DIR__ . '/../includes/db.php';
echo "--- CATEGORIES ---\n";
foreach($pdo->query('SELECT * FROM gallery_categories')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    print_r($r);
}
echo "--- ALBUMS ---\n";
foreach($pdo->query('SELECT * FROM gallery_albums')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    print_r($r);
}
echo "--- NAVIGATION ITEMS ---\n";
try {
    foreach($pdo->query('SELECT * FROM navigation_items ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

