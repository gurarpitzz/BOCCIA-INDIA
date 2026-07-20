<?php
// check-emails.php
require_once __DIR__ . '/includes/db.php';

$emails = ['gurarpit.sml@gmail.com', 'mehardeep.sim@gmail.com'];

header('Content-Type: text/plain');

echo "--- ATHLETES TABLE ---\n";
foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, full_name, email, deleted_at FROM athletes WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        print_r($row);
    } else {
        echo "$email not found\n";
    }
}

echo "\n--- ATHLETE APPLICATIONS TABLE ---\n";
foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, full_name, email, status, possible_duplicate FROM athlete_applications WHERE email = ?");
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll();
    if ($rows) {
        print_r($rows);
    } else {
        echo "$email not found\n";
    }
}

echo "\n--- OFFICIALS TABLE ---\n";
foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, name, email, deleted_at FROM officials WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        print_r($row);
    } else {
        echo "$email not found\n";
    }
}

echo "\n--- OFFICIAL APPLICATIONS TABLE ---\n";
foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, name, email, status FROM official_applications WHERE email = ?");
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll();
    if ($rows) {
        print_r($rows);
    } else {
        echo "$email not found\n";
    }
}
