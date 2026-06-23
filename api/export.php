<?php
// export.php - Secure administration backup exporter

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// strictly restricted to Admin role
checkRole('admin');

$type = isset($_GET['type']) ? $_GET['type'] : 'csv';

if ($type === 'csv') {
    // Export Athletes to CSV
    $state = isset($_GET['state']) ? trim($_GET['state']) : '';
    $filename = 'athletes_export_' . ($state !== '' ? str_replace(' ', '_', $state) . '_' : '') . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'ID', 'Registration No', 'Full Name', 'Gender', 'DOB', 'Mobile', 
        'Email', 'State', 'District', 'Classification', 'Representing For', 
        'Wheelchair Status', 'Status', 'Created At'
    ]);
    
    $query = "SELECT id, regn_no, full_name, gender, dob, mobile, email, state, district, classification, representing_for, wheelchair_status, status, created_at FROM athletes";
    $params = [];
    if ($state !== '') {
        $query .= " WHERE representing_for = ?";
        $params[] = $state;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    
    logAction($pdo, "Exported Athletes to CSV" . ($state !== '' ? " for state $state" : ""));
    fclose($output);
    exit();

} elseif ($type === 'xlsx') {
    // Generate clean XML spreadsheet format which opens in Excel natively without external dependencies
    $state = isset($_GET['state']) ? trim($_GET['state']) : '';
    $filename = 'athletes_export_' . ($state !== '' ? str_replace(' ', '_', $state) . '_' : '') . date('Y-m-d') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    echo "<?xml version=\"1.0\"?>\n";
    echo "<?mso-application myexcel?>\n";
    echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
    echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
    echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
    echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
    echo " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
    echo " <Worksheet ss:Name=\"Athletes\">\n";
    echo "  <Table>\n";
    
    // Header
    echo "   <Row>\n";
    $headers = ['ID', 'Reg No', 'Full Name', 'Gender', 'DOB', 'State', 'Classification', 'Status'];
    foreach ($headers as $header) {
        echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($header) . "</Data></Cell>\n";
    }
    echo "   </Row>\n";
    
    $query = "SELECT id, regn_no, full_name, gender, dob, state, classification, status FROM athletes";
    $params = [];
    if ($state !== '') {
        $query .= " WHERE representing_for = ?";
        $params[] = $state;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   <Row>\n";
        foreach ($row as $val) {
            echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($val) . "</Data></Cell>\n";
        }
        echo "   </Row>\n";
    }
    
    echo "  </Table>\n";
    echo " </Worksheet>\n";
    echo "</Workbook>\n";
    
    logAction($pdo, "Exported Athletes to XML-XLSX" . ($state !== '' ? " for state $state" : ""));
    exit();
} elseif ($type === 'sql') {
    // Database SQL dump export
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=db_backup_' . date('Y-m-d_H-i-s') . '.sql');
    
    $tables = [
        'users', 'states', 'state_associations', 'athletes', 'athlete_applications', 
        'athlete_history', 'athlete_registry_import', 'athlete_status_history', 
        'registration_sequences', 'officials', 'official_applications', 
        'profile_update_requests', 'events', 'event_documents', 'event_gallery', 
        'news', 'news_categories', 'news_images', 'schedules', 'gallery_albums', 
        'gallery_categories', 'gallery_images', 'media_assets', 'site_pages', 
        'page_versions', 'navigation_items', 'document_pages', 'search_index', 
        'site_settings', 'audit_logs', 'activity_logs'
    ];
    
    echo "-- BSFI Database Backup\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Drop syntax
        echo "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create Table Syntax
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        echo $createStmt['Create Table'] . ";\n\n";
        
        // Data Inserts
        $dataStmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            echo "INSERT INTO `$table` VALUES \n";
            $inserts = [];
            foreach ($rows as $row) {
                $values = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, $row);
                $inserts[] = "(" . implode(", ", $values) . ")";
            }
            echo implode(",\n", $inserts) . ";\n\n";
        }
    }
    
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    logAction($pdo, "Exported Full Database SQL Backup");
    exit();
}
