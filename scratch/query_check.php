<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- MIGRATIONS RUN ---\n";
try {
    $stmt = $pdo->query("SELECT * FROM migrations_log");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- ATHLETES INDEXES ---\n";
try {
    $stmt = $pdo->query("SHOW INDEXES FROM athletes");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- ATHLETES COLUMNS ---\n";
try {
    $stmt = $pdo->query("DESCRIBE athletes");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
