<?php
// api-export-builder.php - Dynamic spreadsheet compilation engine for Master, Public, and Custom builder exports
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to authenticated roles
requireLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'custom';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv'; // 'csv' or 'xlsx'
$role = $_SESSION['role'] ?? 'viewer';

// Helper function to output CSV row
function outputCsvRow($output, $fields) {
    fputcsv($output, array_map(function($v) {
        return $v === null ? '' : $v;
    }, $fields));
}

// Helper function to output Excel Cell
function excelCell($val, $type = 'String') {
    return '    <Cell><Data ss:Type="' . htmlspecialchars($type) . '">' . htmlspecialchars($val ?? '') . '</Data></Cell>' . "\n";
}

// ----------------------------------------------------
// 0. EVENT PARTICIPANTS EXPORT
// ----------------------------------------------------
if ($action === 'event_participants') {
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

    // Verify event
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if (!$event) {
        die("Event not found.");
    }

    // Fetch dynamic custom fields for this event to generate headers
    $fStmt = $pdo->prepare("SELECT id, field_label FROM event_form_fields WHERE schedule_id = ? ORDER BY sort_order ASC, id ASC");
    $fStmt->execute([$event_id]);
    $customFields = $fStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build headers
    $headers = [
        'Registration ID',
        'Member Type',
        'Member Registration ID',
        'Name',
        'Email',
        'Mobile',
        'State',
        'DOB',
        'Gender',
        'Classification',
        'Payment Status',
        'Registration Status',
        'Transaction Ref',
        'Receipt Path',
        'Rejection Remarks',
        'Created At'
    ];
    foreach ($customFields as $cf) {
        $headers[] = $cf['field_label'];
    }

    // Fetch registrations
    $sql = "SELECT * FROM event_registrations WHERE schedule_id = ?";
    $params = [$event_id];
    if (!empty($statusFilter)) {
        $sql .= " AND registration_status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY created_at DESC";
    $regStmt = $pdo->prepare($sql);
    $regStmt->execute($params);
    $registrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'BSFI_Event_Participants_' . preg_replace('/[^a-zA-Z0-9]/', '_', $event['discipline']) . '_' . date('Y-m-d') . ($format === 'xlsx' ? '.xls' : '.csv');

    if ($format === 'xlsx') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo "<?xml version=\"1.0\"?>\n";
        echo "<?mso-application myexcel?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        echo " <Worksheet ss:Name=\"Participants\">\n";
        echo "  <Table>\n";
        echo "   <Row>\n";
        foreach ($headers as $h) {
            echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
        }
        echo "   </Row>\n";

        foreach ($registrations as $reg) {
            echo "   <Row>\n";
            $row_data = [
                $reg['registration_no'],
                $reg['member_type'],
                $reg['snapshot_regn_no'],
                $reg['snapshot_name'],
                $reg['snapshot_email'],
                $reg['snapshot_mobile'],
                $reg['snapshot_state'],
                $reg['snapshot_dob'],
                $reg['snapshot_gender'],
                $reg['snapshot_classification'],
                $reg['payment_status'],
                $reg['registration_status'],
                $reg['transaction_reference'],
                $reg['payment_receipt_path'],
                $reg['rejection_remarks'],
                $reg['created_at']
            ];
            // Fetch answers
            $aStmt = $pdo->prepare("SELECT answer_value FROM event_registration_answers WHERE registration_id = ? AND field_id = ?");
            foreach ($customFields as $cf) {
                $aStmt->execute([$reg['id'], $cf['id']]);
                $row_data[] = $aStmt->fetchColumn() ?: '';
            }

            foreach ($row_data as $val) {
                echo excelCell($val);
            }
            echo "   </Row>\n";
        }

        echo "  </Table>\n";
        echo " </Worksheet>\n";
        echo "</Workbook>\n";
        logAction($pdo, "Exported Event Participants Excel spreadsheet", "schedules", $event_id);
        exit();
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($registrations as $reg) {
            $row_data = [
                $reg['registration_no'],
                $reg['member_type'],
                $reg['snapshot_regn_no'],
                $reg['snapshot_name'],
                $reg['snapshot_email'],
                $reg['snapshot_mobile'],
                $reg['snapshot_state'],
                $reg['snapshot_dob'],
                $reg['snapshot_gender'],
                $reg['snapshot_classification'],
                $reg['payment_status'],
                $reg['registration_status'],
                $reg['transaction_reference'],
                $reg['payment_receipt_path'],
                $reg['rejection_remarks'],
                $reg['created_at']
            ];
            // Fetch answers
            $aStmt = $pdo->prepare("SELECT answer_value FROM event_registration_answers WHERE registration_id = ? AND field_id = ?");
            foreach ($customFields as $cf) {
                $aStmt->execute([$reg['id'], $cf['id']]);
                $row_data[] = $aStmt->fetchColumn() ?: '';
            }
            fputcsv($output, $row_data);
        }
        fclose($output);
        logAction($pdo, "Exported Event Participants CSV spreadsheet", "schedules", $event_id);
        exit();
    }
}

if ($action === 'master') {
    if ($role !== 'admin') {
        http_response_code(403);
        die("Access Denied: Master export restricted to Administrator role.");
    }
    
    $filename = 'BSFI_Master_Federation_Export_' . date('Y-m-d') . ($format === 'xlsx' ? '.xls' : '.csv');
    
    if ($format === 'xlsx') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo "<?xml version=\"1.0\"?>\n";
        echo "<?mso-application myexcel?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        
        // Tab 1: Athletes
        echo " <Worksheet ss:Name=\"Athletes\">\n";
        echo "  <Table>\n";
        echo "   <Row>\n";
        $headers = ['Registration No', 'Full Name', 'Gender', 'DOB', 'Father\'s Name', 'Mother\'s Name', 'Age Category', 'State', 'District', 'Classification', 'Wheelchair Status', 'Aadhaar', 'Mobile', 'Email', 'Address', 'Pincode', 'Kit T-Shirt', 'Kit Tracksuit', 'Kit Shoe', 'Photo Path', 'Receipt Path', 'Status', 'Legacy Registry'];
        foreach ($headers as $h) echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
        echo "   </Row>\n";
        
        $stmt = $pdo->query("SELECT regn_no, full_name, gender, dob, father_name, mother_name, age_category, state, district, classification, wheelchair_status, aadhaar, mobile, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, photo_path, receipt_path, status, is_legacy_registry FROM athletes WHERE deleted_at IS NULL ORDER BY CAST(regn_no AS UNSIGNED) ASC, regn_no ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   <Row>\n";
            foreach ($row as $k => $v) {
                if ($k === 'is_legacy_registry') $v = $v ? 'Yes' : 'No';
                echo excelCell($v);
            }
            echo "   </Row>\n";
        }
        echo "  </Table>\n";
        echo " </Worksheet>\n";
        
        // Tab 2: Officials
        echo " <Worksheet ss:Name=\"Officials\">\n";
        echo "  <Table>\n";
        echo "   <Row>\n";
        $offHeaders = ['Official Reg No', 'Name', 'Role', 'Gender', 'DOB', 'Father\'s/Spouse\'s Name', 'State', 'Aadhaar', 'Phone', 'Email', 'Address', 'Pincode', 'Kit T-Shirt', 'Kit Tracksuit', 'Kit Shoe', 'Photo Path', 'Receipt Path', 'Status', 'Legacy Registry'];
        foreach ($offHeaders as $h) echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
        echo "   </Row>\n";
        
        $stmt = $pdo->query("SELECT official_reg_no, name, role, gender, dob, father_name, state, aadhaar, phone, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, photo_path, receipt_path, status, is_legacy_registry FROM officials WHERE deleted_at IS NULL ORDER BY name ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   <Row>\n";
            foreach ($row as $k => $v) {
                if ($k === 'is_legacy_registry') $v = $v ? 'Yes' : 'No';
                echo excelCell($v);
            }
            echo "   </Row>\n";
        }
        echo "  </Table>\n";
        echo " </Worksheet>\n";
        echo "</Workbook>\n";
        
        logAction($pdo, "Exported Master Excel spreadsheet");
        exit();
        
    } else {
        // CSV Format
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Type', 'Registration No / ID', 'Name', 'Gender', 'DOB', 'State', 'Role / Classification', 'Aadhaar', 'Phone', 'Email', 'Address', 'Kit Details', 'Status', 'Legacy Registry']);
        
        // Fetch athletes
        $stmt = $pdo->query("SELECT regn_no, full_name, gender, dob, state, classification, aadhaar, mobile, email, address, CONCAT('T-Shirt: ', COALESCE(kit_tshirt, 'N/A'), ', Tracksuit: ', COALESCE(kit_tracksuit, 'N/A'), ', Shoe: ', COALESCE(kit_shoe, 'N/A')) as kit, status, is_legacy_registry FROM athletes WHERE deleted_at IS NULL");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                'Athlete', $r['regn_no'], $r['full_name'], $r['gender'], $r['dob'], $r['state'], $r['classification'],
                $r['aadhaar'], $r['mobile'], $r['email'], $r['address'], $r['kit'], $r['status'], $r['is_legacy_registry'] ? 'Yes' : 'No'
            ]);
        }
        
        // Fetch officials
        $stmt = $pdo->query("SELECT official_reg_no, name, gender, dob, state, role, aadhaar, phone, email, address, CONCAT('T-Shirt: ', COALESCE(kit_tshirt, 'N/A'), ', Tracksuit: ', COALESCE(kit_tracksuit, 'N/A'), ', Shoe: ', COALESCE(kit_shoe, 'N/A')) as kit, status, is_legacy_registry FROM officials WHERE deleted_at IS NULL");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                'Official', $r['official_reg_no'], $r['name'], $r['gender'], $r['dob'], $r['state'], $r['role'],
                $r['aadhaar'], $r['phone'], $r['email'], $r['address'], $r['kit'], $r['status'], $r['is_legacy_registry'] ? 'Yes' : 'No'
            ]);
        }
        
        fclose($output);
        logAction($pdo, "Exported Master CSV spreadsheet");
        exit();
    }
}

// ----------------------------------------------------
// 2. PUBLIC EXPORT: Available to all staff roles
// ----------------------------------------------------
if ($action === 'public') {
    $filename = 'BSFI_Public_Registry_Export_' . date('Y-m-d') . ($format === 'xlsx' ? '.xls' : '.csv');
    
    if ($format === 'xlsx') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo "<?xml version=\"1.0\"?>\n";
        echo "<?mso-application myexcel?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        echo " <Worksheet ss:Name=\"Directory\">\n";
        echo "  <Table>\n";
        echo "   <Row>\n";
        $headers = ['Registration No / ID', 'Full Name', 'Gender', 'State', 'Classification / Role', 'Registry Type'];
        foreach ($headers as $h) echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
        echo "   </Row>\n";
        
        // Fetch athletes
        $stmt = $pdo->query("SELECT regn_no, full_name, gender, state, classification FROM athletes WHERE status = 'approved' AND deleted_at IS NULL ORDER BY CAST(regn_no AS UNSIGNED) ASC, regn_no ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   <Row>\n";
            foreach ($row as $v) echo excelCell($v);
            echo excelCell('Athlete');
            echo "   </Row>\n";
        }
        // Fetch officials
        $stmt = $pdo->query("SELECT official_reg_no, name, gender, state, role FROM officials WHERE status = 'approved' AND deleted_at IS NULL ORDER BY name ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   <Row>\n";
            foreach ($row as $v) echo excelCell($v);
            echo excelCell('Official');
            echo "   </Row>\n";
        }
        
        echo "  </Table>\n";
        echo " </Worksheet>\n";
        echo "</Workbook>\n";
        
        logAction($pdo, "Exported Public Registry Excel spreadsheet");
        exit();
        
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Registration No / ID', 'Full Name', 'Gender', 'State', 'Classification / Role', 'Registry Type']);
        
        // Fetch athletes
        $stmt = $pdo->query("SELECT regn_no, full_name, gender, state, classification FROM athletes WHERE status = 'approved' AND deleted_at IS NULL ORDER BY CAST(regn_no AS UNSIGNED) ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['regn_no'], $row['full_name'], $row['gender'], $row['state'], $row['classification'], 'Athlete']);
        }
        // Fetch officials
        $stmt = $pdo->query("SELECT official_reg_no, name, gender, state, role FROM officials WHERE status = 'approved' AND deleted_at IS NULL ORDER BY name ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['official_reg_no'], $row['name'], $row['gender'], $row['state'], $row['role'], 'Official']);
        }
        
        fclose($output);
        logAction($pdo, "Exported Public Registry CSV spreadsheet");
        exit();
    }
}

// ----------------------------------------------------
// 3. CUSTOM EXPORT BUILDER
// ----------------------------------------------------
if ($action === 'custom') {
    $scope = isset($_GET['scope']) ? $_GET['scope'] : 'athletes';
    $stateFilter = isset($_GET['state']) ? trim($_GET['state']) : '';
    $classFilter = isset($_GET['classification']) ? trim($_GET['classification']) : '';
    $genderFilter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $legacyFilter = isset($_GET['legacy']) ? trim($_GET['legacy']) : '';
    
    $colsRequested = isset($_GET['cols']) ? $_GET['cols'] : ['regn_no', 'full_name', 'gender', 'state', 'classification'];
    
    // Check if any sensitive columns are requested
    $sensitiveCols = ['email', 'mobile', 'phone', 'aadhaar', 'address', 'kit_tshirt', 'father_name', 'photo_path', 'receipt_path'];
    $requestedSensitive = array_intersect($colsRequested, $sensitiveCols);
    
    if (!empty($requestedSensitive) && $role !== 'admin') {
        http_response_code(403);
        die("Access Denied: Custom export requesting PII / sensitive fields is restricted to Administrator role.");
    }
    
    // Build Query
    $whereClauses = ["deleted_at IS NULL"];
    $params = [];
    
    if ($stateFilter !== '') {
        $whereClauses[] = "state = ?";
        $params[] = $stateFilter;
    }
    
    if ($genderFilter !== '') {
        $whereClauses[] = "gender = ?";
        $params[] = $genderFilter;
    }
    
    if ($statusFilter !== '') {
        $whereClauses[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    if ($legacyFilter !== '') {
        $whereClauses[] = "is_legacy_registry = ?";
        $params[] = (int)$legacyFilter;
    }
    
    // Column mappings and select list construction
    $selectFields = [];
    $columnHeaders = [];
    
    if ($scope === 'athletes') {
        if ($classFilter !== '') {
            $whereClauses[] = "classification = ?";
            $params[] = $classFilter;
        }
        
        foreach ($colsRequested as $c) {
            switch ($c) {
                case 'regn_no': 
                    $selectFields[] = 'regn_no'; 
                    $columnHeaders[] = 'Registration No'; 
                    break;
                case 'full_name': 
                    $selectFields[] = 'full_name'; 
                    $columnHeaders[] = 'Full Name'; 
                    break;
                case 'gender': 
                    $selectFields[] = 'gender'; 
                    $columnHeaders[] = 'Gender'; 
                    break;
                case 'dob': 
                    $selectFields[] = 'dob'; 
                    $columnHeaders[] = 'Date of Birth'; 
                    break;
                case 'state': 
                    $selectFields[] = 'state'; 
                    $columnHeaders[] = 'State'; 
                    break;
                case 'classification': 
                    $selectFields[] = 'classification'; 
                    $columnHeaders[] = 'Classification'; 
                    break;
                case 'email': 
                    $selectFields[] = 'email'; 
                    $columnHeaders[] = 'Email'; 
                    break;
                case 'mobile': 
                    $selectFields[] = 'mobile'; 
                    $columnHeaders[] = 'Phone Number'; 
                    break;
                case 'aadhaar': 
                    $selectFields[] = 'aadhaar'; 
                    $columnHeaders[] = 'Aadhaar'; 
                    break;
                case 'address': 
                    $selectFields[] = 'address'; 
                    $columnHeaders[] = 'Permanent Address'; 
                    $selectFields[] = 'pincode';
                    $columnHeaders[] = 'Pin Code';
                    break;
                case 'kit_tshirt': 
                    $selectFields[] = 'kit_tshirt'; 
                    $columnHeaders[] = 'Kit T-Shirt'; 
                    $selectFields[] = 'kit_tracksuit';
                    $columnHeaders[] = 'Kit Tracksuit';
                    $selectFields[] = 'kit_shoe';
                    $columnHeaders[] = 'Kit Shoe';
                    break;
                case 'father_name': 
                    $selectFields[] = 'father_name'; 
                    $columnHeaders[] = 'Father\'s Name'; 
                    $selectFields[] = 'mother_name';
                    $columnHeaders[] = 'Mother\'s Name';
                    $selectFields[] = 'age_category';
                    $columnHeaders[] = 'Age Category';
                    $selectFields[] = 'impairment_type';
                    $columnHeaders[] = 'Impairment Type';
                    break;
                case 'photo_path':
                    $selectFields[] = 'photo_path';
                    $columnHeaders[] = 'Profile Photo Path';
                    break;
                case 'receipt_path':
                    $selectFields[] = 'receipt_path';
                    $columnHeaders[] = 'Gov Doc Path';
                    break;
            }
        }
        
        $sql = "SELECT " . implode(', ', $selectFields) . " FROM athletes WHERE " . implode(' AND ', $whereClauses) . " ORDER BY CAST(regn_no AS UNSIGNED) ASC, regn_no ASC";
        
    } else {
        // Officials
        if ($classFilter !== '') {
            $whereClauses[] = "role = ?";
            $params[] = $classFilter;
        }
        
        foreach ($colsRequested as $c) {
            switch ($c) {
                case 'regn_no': 
                    $selectFields[] = 'official_reg_no'; 
                    $columnHeaders[] = 'Official Reg No'; 
                    break;
                case 'full_name': 
                    $selectFields[] = 'name'; 
                    $columnHeaders[] = 'Full Name'; 
                    break;
                case 'gender': 
                    $selectFields[] = 'gender'; 
                    $columnHeaders[] = 'Gender'; 
                    break;
                case 'dob': 
                    $selectFields[] = 'dob'; 
                    $columnHeaders[] = 'Date of Birth'; 
                    break;
                case 'state': 
                    $selectFields[] = 'state'; 
                    $columnHeaders[] = 'State'; 
                    break;
                case 'classification': 
                    $selectFields[] = 'role'; 
                    $columnHeaders[] = 'Official Role'; 
                    break;
                case 'email': 
                    $selectFields[] = 'email'; 
                    $columnHeaders[] = 'Email'; 
                    break;
                case 'mobile': 
                    $selectFields[] = 'phone'; 
                    $columnHeaders[] = 'Phone Number'; 
                    break;
                case 'aadhaar': 
                    $selectFields[] = 'aadhaar'; 
                    $columnHeaders[] = 'Aadhaar'; 
                    break;
                case 'address': 
                    $selectFields[] = 'address'; 
                    $columnHeaders[] = 'Permanent Address'; 
                    $selectFields[] = 'pincode';
                    $columnHeaders[] = 'Pin Code';
                    break;
                case 'kit_tshirt': 
                    $selectFields[] = 'kit_tshirt'; 
                    $columnHeaders[] = 'Kit T-Shirt'; 
                    $selectFields[] = 'kit_tracksuit';
                    $columnHeaders[] = 'Kit Tracksuit';
                    $selectFields[] = 'kit_shoe';
                    $columnHeaders[] = 'Kit Shoe';
                    break;
                case 'father_name': 
                    $selectFields[] = 'father_name'; 
                    $columnHeaders[] = 'Father\'s/Spouse\'s Name'; 
                    break;
                case 'photo_path':
                    $selectFields[] = 'photo_path';
                    $columnHeaders[] = 'Profile Photo Path';
                    break;
                case 'receipt_path':
                    $selectFields[] = 'receipt_path';
                    $columnHeaders[] = 'Gov Doc Path';
                    break;
            }
        }
        
        $sql = "SELECT " . implode(', ', $selectFields) . " FROM officials WHERE " . implode(' AND ', $whereClauses) . " ORDER BY name ASC";
    }
    
    $filename = 'BSFI_Custom_Export_' . date('Y-m-d') . ($format === 'xlsx' ? '.xls' : '.csv');
    
    if ($format === 'xlsx') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo "<?xml version=\"1.0\"?>\n";
        echo "<?mso-application myexcel?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        echo " <Worksheet ss:Name=\"Custom Export\">\n";
        echo "  <Table>\n";
        echo "   <Row>\n";
        foreach ($columnHeaders as $h) echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
        echo "   </Row>\n";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   <Row>\n";
            foreach ($row as $v) echo excelCell($v);
            echo "   </Row>\n";
        }
        
        echo "  </Table>\n";
        echo " </Worksheet>\n";
        echo "</Workbook>\n";
        
        logAction($pdo, "Custom Builder Excel export generated");
        exit();
        
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        
        fputcsv($output, $columnHeaders);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
        logAction($pdo, "Custom Builder CSV export generated");
        exit();
    }
}
