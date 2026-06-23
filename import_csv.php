<?php
// import_csv.php - Secure, Non-Destructive Database Seeder for Athletes with History and Sequences
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

function cleanUtf8($string) {
    if (is_array($string)) {
        return array_map('cleanUtf8', $string);
    }
    // Remove control characters (except tab/linefeed)
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    // Convert encoding to UTF-8
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8, ISO-8859-1');
}

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

// Extra indexing for history
$championship_name_idx = array_search('CHAMPIONSHIP_NAME', $headers);
$year_idx = array_search('YEAR', $headers);
$disc2_idx = array_search('DISCIPLINE2_NAME', $headers);
$disc1_result_idx = array_search('DISCIPLINE1_EVENT1_RESULT', $headers);
$disc2_result_idx = array_search('DISCIPLINE2_EVENT1_RESULT', $headers);
$cert_name_idx = array_search('CERT_NAME', $headers);

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
        
        // Pad numerical regn_no to match standard 4-digit code e.g. "0003"
        if (is_numeric($regn_no)) {
            $regn_no = str_pad((int)$regn_no, 4, '0', STR_PAD_LEFT);
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
        
        // Check if athlete already exists
        $checkStmt = $pdo->prepare("SELECT id FROM athletes WHERE regn_no = ?");
        $checkStmt->execute([$regn_no]);
        $athRow = $checkStmt->fetch();
        
        if ($athRow) {
            $athlete_id = $athRow['id'];
            // Update details (marking as legacy registry if they are in the range 1-99)
            $isLegacy = (is_numeric($regn_no) && (int)$regn_no >= 1 && (int)$regn_no <= 99) ? 1 : 0;
            $updateStmt = $pdo->prepare("UPDATE athletes SET full_name = ?, gender = ?, dob = ?, state = ?, classification = ?, representing_for = ?, state_association_id = ?, status = 'approved', digilocker_imported = 1, photo_status = 'missing', is_legacy_registry = ? WHERE id = ?");
            $updateStmt->execute([$full_name, $gender, $dob, $cleanState, $classification, $cleanState, $assocId, $isLegacy, $athlete_id]);
            $updated++;
        } else {
            // Insert new athlete
            $isLegacy = (is_numeric($regn_no) && (int)$regn_no >= 1 && (int)$regn_no <= 99) ? 1 : 0;
            $insertStmt = $pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, state, classification, representing_for, state_association_id, status, digilocker_imported, photo_status, is_legacy_registry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1, 'missing', ?)");
            $insertStmt->execute([$regn_no, $full_name, $gender, $dob, $cleanState, $classification, $cleanState, $assocId, $isLegacy]);
            $athlete_id = $pdo->lastInsertId();
            $inserted++;
        }
        
        // Clear previous history and import logs for this athlete to avoid duplicates
        $pdo->prepare("DELETE FROM athlete_history WHERE athlete_id = ?")->execute([$athlete_id]);
        $pdo->prepare("DELETE FROM athlete_registry_import WHERE athlete_id = ?")->execute([$athlete_id]);
        
        // Populate Athlete History
        $eventName = trim($row[$championship_name_idx] ?? 'National Boccia Championship');
        $eventYear = (int)($row[$year_idx] ?? date('Y'));
        
        // Event 1
        $rank1 = trim($row[$disc1_result_idx] ?? '');
        $remarks = trim($row[$cert_name_idx] ?? '');
        $historyStmt = $pdo->prepare("INSERT INTO athlete_history (athlete_id, event_name, event_year, classification, event_level, state_represented, rank, remarks) VALUES (?, ?, ?, ?, 'National', ?, ?, ?)");
        $historyStmt->execute([$athlete_id, $eventName, $eventYear, $classification, $cleanState, $rank1, $remarks]);
        
        // Event 2 (if exists)
        $disc2 = cleanClassification($row[$disc2_idx] ?? '');
        if ($disc2) {
            $rank2 = trim($row[$disc2_result_idx] ?? '');
            $historyStmt->execute([$athlete_id, $eventName, $eventYear, $disc2, $cleanState, $rank2, $remarks]);
        }
        
        // Populate athlete_registry_import (raw JSON row)
        $assocRow = array_combine($headers, $row);
        $cleanedAssocRow = cleanUtf8($assocRow);
        $jsonRow = json_encode($cleanedAssocRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $importStmt = $pdo->prepare("INSERT INTO athlete_registry_import (athlete_id, source_file, import_batch, original_csv_row) VALUES (?, 'database.csv', 'Initial DigiLocker Import', ?)");
        $importStmt->execute([$athlete_id, $jsonRow]);
        
    } catch (Exception $e) {
        $errors[] = "Row $rowNo - " . $e->getMessage();
    }
}

fclose($handle);

// Regenerate Sequences Table
try {
    $maxQuery = $pdo->query("SELECT MAX(CAST(regn_no AS UNSIGNED)) as max_no FROM athletes WHERE regn_no REGEXP '^[0-9]+$'");
    $maxVal = (int)$maxQuery->fetchColumn();
    if ($maxVal > 0) {
        $pdo->prepare("UPDATE registration_sequences SET athlete_last_no = ? WHERE id = 1")->execute([$maxVal]);
        echo "Registration sequence initialized to: $maxVal\n";
    }
} catch (Exception $e) {
    echo "Warning: Registration sequence initialization failed - " . $e->getMessage() . "\n";
}

// Regenerate Map Caching
try {
    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0755, true);
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
