<?php
// apply_aadhaar_unique.php - Database audit and UNIQUE constraint migration
require_once __DIR__ . '/../includes/db.php';

echo "Checking athletes for duplicate Aadhaar numbers...\n";
$stmt = $pdo->query("
    SELECT aadhaar, COUNT(*) as count, GROUP_CONCAT(full_name SEPARATOR ', ') as names
    FROM athletes
    WHERE aadhaar IS NOT NULL
      AND aadhaar <> ''
    GROUP BY aadhaar
    HAVING COUNT(*) > 1
");
$athlete_dups = $stmt->fetchAll();

if (count($athlete_dups) > 0) {
    echo "WARNING: Duplicate Aadhaar numbers found in athletes table:\n";
    foreach ($athlete_dups as $row) {
        echo "  Aadhaar: {$row['aadhaar']} - Count: {$row['count']} - Names: [{$row['names']}]\n";
    }
} else {
    echo "  No duplicate Aadhaar numbers found in athletes table.\n";
}

echo "\nChecking officials for duplicate Aadhaar numbers...\n";
$stmt = $pdo->query("
    SELECT aadhaar, COUNT(*) as count, GROUP_CONCAT(name SEPARATOR ', ') as names
    FROM officials
    WHERE aadhaar IS NOT NULL
      AND aadhaar <> ''
    GROUP BY aadhaar
    HAVING COUNT(*) > 1
");
$official_dups = $stmt->fetchAll();

if (count($official_dups) > 0) {
    echo "WARNING: Duplicate Aadhaar numbers found in officials table:\n";
    foreach ($official_dups as $row) {
        echo "  Aadhaar: {$row['aadhaar']} - Count: {$row['count']} - Names: [{$row['names']}]\n";
    }
} else {
    echo "  No duplicate Aadhaar numbers found in officials table.\n";
}

if (count($athlete_dups) === 0 && count($official_dups) === 0) {
    echo "\nApplying UNIQUE constraints...\n";
    
    // Check if uq_athletes_aadhaar already exists
    $stmt = $pdo->query("SHOW KEYS FROM athletes WHERE Key_name = 'uq_athletes_aadhaar'");
    if ($stmt->fetch()) {
        echo "  UNIQUE key 'uq_athletes_aadhaar' already exists on athletes table.\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE athletes ADD UNIQUE KEY uq_athletes_aadhaar (aadhaar)");
            echo "  Successfully applied UNIQUE constraint to athletes(aadhaar).\n";
        } catch (PDOException $e) {
            echo "  Error applying UNIQUE constraint to athletes: " . $e->getMessage() . "\n";
        }
    }
    
    // Check if uq_officials_aadhaar already exists
    $stmt = $pdo->query("SHOW KEYS FROM officials WHERE Key_name = 'uq_officials_aadhaar'");
    if ($stmt->fetch()) {
        echo "  UNIQUE key 'uq_officials_aadhaar' already exists on officials table.\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE officials ADD UNIQUE KEY uq_officials_aadhaar (aadhaar)");
            echo "  Successfully applied UNIQUE constraint to officials(aadhaar).\n";
        } catch (PDOException $e) {
            echo "  Error applying UNIQUE constraint to officials: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "\nMIGRATION BLOCKED: Please resolve duplicate Aadhaar numbers before applying the UNIQUE key.\n";
}
