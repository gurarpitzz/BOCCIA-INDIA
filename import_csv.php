<?php
// import_csv.php - Secure, Non-Destructive Database Seeder for Athletes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

function parseCsvDate($dobStr) {
    $dobStr = trim($dobStr);
    if (empty($dobStr)) {
        return null;
    }
    
    // Parse formats: MM/DD/YYYY, M/D/YYYY, MM/DD/YY, M/D/YY
    $parts = explode('/', $dobStr);
    if (count($parts) === 3) {
        $month = (int)$parts[0];
        $day = (int)$parts[1];
        $year = (int)$parts[2];
        
        if ($year < 100) {
            if ($year > 26) {
                $year += 1900;
            } else {
                $year += 2000;
            }
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    $ts = strtotime($dobStr);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    return null;
}

function cleanClassification($classStr) {
    $classStr = strtoupper(trim($classStr));
    if (strpos($classStr, 'BC-1') !== false || strpos($classStr, 'BC1') !== false) {
        return 'BC1';
    }
    if (strpos($classStr, 'BC-2') !== false || strpos($classStr, 'BC2') !== false) {
        return 'BC2';
    }
    if (strpos($classStr, 'BC-3') !== false || strpos($classStr, 'BC3') !== false) {
        return 'BC3';
    }
    if (strpos($classStr, 'BC-4') !== false || strpos($classStr, 'BC4') !== false) {
        return 'BC4';
    }
    return null;
}

function cleanStateName($stateStr) {
    $state = trim($stateStr);
    $stateUpper = strtoupper($state);
    
    $map = [
        'ANDHRA PRADESH' => 'Andhra Pradesh',
        'ASSAM' => 'Assam',
        'BIHAR' => 'Bihar',
        'CHANDIGARH' => 'Chandigarh',
        'CHHATTISGARH' => 'Chhattisgarh',
        'DELHI' => 'Delhi',
        'GOA' => 'Goa',
        'GUJARAT' => 'Gujarat',
        'HARYANA' => 'Haryana',
        'HIMACHAL PRADESH' => 'Himachal Pradesh',
        'JHARKHAND' => 'Jharkhand',
        'KARNATKA' => 'Karnataka',
        'KARNATAKA' => 'Karnataka',
        'MADHYA PRADESH' => 'Madhya Pradesh',
        'MAHARASHTRA' => 'Maharashtra',
        'ODISHA' => 'Odisha',
        'ORISSA' => 'Odisha',
        'PUNJAB' => 'Punjab',
        'RAJASTHAN' => 'Rajasthan',
        'TAMIL NADU' => 'Tamil Nadu',
        'TELANGANA' => 'Telangana',
        'UTTAR PRADESH' => 'Uttar Pradesh',
        'UTTARAKHAND' => 'Uttarakhand',
        'UTTARANCHAL' => 'Uttarakhand',
        'WEST BENGAL' => 'West Bengal',
        'JAMMU AND KASHMIR' => 'Jammu and Kashmir',
        'LADAKH' => 'Ladakh',
        'PUDUCHERRY' => 'Puducherry',
        'LAKSHADWEEP' => 'Lakshadweep',
        'SIKKIM' => 'Sikkim',
        'NAGALAND' => 'Nagaland',
        'MIZORAM' => 'Mizoram',
        'MEGHALAYA' => 'Meghalaya',
        'MANIPUR' => 'Manipur',
        'TRIPURA' => 'Tripura',
        'DADRA AND NAGAR HAVELI AND DAMAN AND DIU' => 'Dadra and Nagar Haveli and Daman and Diu',
    ];
    
    return isset($map[$stateUpper]) ? $map[$stateUpper] : ucwords(strtolower($state));
}

$csvFile = __DIR__ . '/database.csv';
if (!file_exists($csvFile)) {
    die("Error: database.csv not found in " . __DIR__ . "\n");
}

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("Error: Failed to open database.csv\n");
}

$headers = fgetcsv($handle);
$reg_idx = array_search('REGN_NO', $headers);
$cname_idx = array_search('CNAME', $headers);
$gender_idx = array_search('GENDER', $headers);
$dob_idx = array_search('DOB', $headers);
$state_idx = array_search('REPRESENTING_FOR', $headers);
$disc1_idx = array_search('DISCIPLINE1_NAME', $headers);

if ($reg_idx === false || $cname_idx === false || $gender_idx === false || $dob_idx === false || $state_idx === false || $disc1_idx === false) {
    die("Error: Missing required column headers in database.csv\n");
}

$recordsRead = 0;
$inserted = 0;
$updated = 0;
$skipped = 0;
$errors = [];

$stateCache = [];
$stateAssocCache = [];

// Prepare query for state lookup
$stateStmt = $pdo->prepare("SELECT id FROM states WHERE name = ?");
$assocStmt = $pdo->prepare("SELECT id FROM state_associations WHERE state_id = ? LIMIT 1");

$insertStmt = $pdo->prepare("INSERT INTO athletes 
    (regn_no, full_name, gender, dob, state, classification, representing_for, state_association_id, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved') 
    ON DUPLICATE KEY UPDATE 
    full_name=VALUES(full_name), 
    gender=VALUES(gender), 
    dob=VALUES(dob), 
    state=VALUES(state), 
    classification=VALUES(classification), 
    representing_for=VALUES(representing_for), 
    state_association_id=VALUES(state_association_id), 
    status=VALUES(status)");

$rowNo = 1; // Row 1 is headers
while (($row = fgetcsv($handle)) !== false) {
    $rowNo++;
    if (empty(array_filter($row))) {
        continue;
    }
    
    $recordsRead++;
    
    try {
        $regn_no = trim($row[$reg_idx]);
        if (empty($regn_no)) {
            throw new Exception("Registration number (REGN_NO) is empty.");
        }
        
        $full_name = trim($row[$cname_idx]);
        if (empty($full_name)) {
            throw new Exception("Athlete name (CNAME) is empty.");
        }
        
        $gender_raw = strtoupper(trim($row[$gender_idx]));
        if ($gender_raw !== 'MALE' && $gender_raw !== 'FEMALE') {
            throw new Exception("Invalid gender value: '$gender_raw'. Only MALE/FEMALE allowed.");
        }
        $gender = $gender_raw;
        
        $dob_raw = trim($row[$dob_idx]);
        $dob = parseCsvDate($dob_raw);
        if (!$dob) {
            throw new Exception("Invalid Date of Birth format: '$dob_raw'.");
        }
        
        $state_raw = trim($row[$state_idx]);
        $cleanState = cleanStateName($state_raw);
        
        // Lookup state ID
        if (isset($stateCache[$cleanState])) {
            $stateId = $stateCache[$cleanState];
        } else {
            $stateStmt->execute([$cleanState]);
            $stateRow = $stateStmt->fetch();
            if (!$stateRow) {
                throw new Exception("State name '$cleanState' not found in states lookup table.");
            }
            $stateId = $stateRow['id'];
            $stateCache[$cleanState] = $stateId;
        }
        
        // Lookup state association ID
        if (isset($stateAssocCache[$stateId])) {
            $assocId = $stateAssocCache[$stateId];
        } else {
            $assocStmt->execute([$stateId]);
            $assocRow = $assocStmt->fetch();
            $assocId = $assocRow ? $assocRow['id'] : null;
            $stateAssocCache[$stateId] = $assocId;
        }
        
        $classification_raw = trim($row[$disc1_idx]);
        $classification = cleanClassification($classification_raw);
        if (!$classification) {
            throw new Exception("Invalid classification code: '$classification_raw'.");
        }
        
        // Execute dynamic seeder
        $insertStmt->execute([
            $regn_no,
            $full_name,
            $gender,
            $dob,
            $cleanState,
            $classification,
            $cleanState,
            $assocId
        ]);
        
        $effect = $insertStmt->rowCount();
        if ($effect === 1) {
            $inserted++;
        } elseif ($effect === 2) {
            $updated++;
        } else {
            $skipped++;
        }
        
    } catch (Exception $e) {
        $errors[] = "Row $rowNo - " . $e->getMessage();
    }
}

fclose($handle);

// Regenerate Map Caching
try {
    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0755, true);
    }
    
    // Aggregation query
    $countStmt = $pdo->query("SELECT state, COUNT(*) as count FROM athletes WHERE status = 'approved' GROUP BY state");
    $stateCounts = [];
    while ($r = $countStmt->fetch()) {
        $stateCounts[$r['state']] = (int)$r['count'];
    }
    
    // Detailed stats query
    $detailsQuery = $pdo->query("SELECT state, status, classification, COUNT(*) as count FROM athletes GROUP BY state, status, classification");
    $stats = [];
    while ($r = $detailsQuery->fetch()) {
        $st = $r['state'];
        $stStatus = $r['status'];
        $class = $r['classification'];
        $cnt = (int)$r['count'];
        
        if (!isset($stats[$st])) {
            $stats[$st] = [
                'approved' => 0,
                'pending' => 0,
                'bc1' => 0,
                'bc2' => 0,
                'bc3' => 0,
                'bc4' => 0,
            ];
        }
        
        if ($stStatus === 'approved') {
            $stats[$st]['approved'] += $cnt;
            $classKey = strtolower($class);
            if (isset($stats[$st][$classKey])) {
                $stats[$st][$classKey] += $cnt;
            }
        } elseif ($stStatus === 'pending') {
            $stats[$st]['pending'] += $cnt;
        }
    }
    
    file_put_contents(__DIR__ . '/cache/state-stats.json', json_encode($stats, JSON_PRETTY_PRINT));
    echo "State stats cache regenerated successfully.\n";
} catch (Exception $e) {
    echo "Warning: Map cache regeneration failed - " . $e->getMessage() . "\n";
}

echo "\nImport Summary\n";
echo "--------------\n";
echo "Records Read: $recordsRead\n";
echo "Inserted:     $inserted\n";
echo "Updated:      $updated\n";
echo "Skipped:      $skipped\n";
echo "Errors:       " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nDetail Errors:\n";
    foreach ($errors as $err) {
        echo "  $err\n";
    }
}
?>
