<?php
// db.php - MySQL PDO Connection

if (!defined('PRIVATE_UPLOADS_DIR')) {
    define('PRIVATE_UPLOADS_DIR', dirname(__DIR__, 2) . '/private_uploads/');
}


// Load local configuration overrides if available
$local_config_path = dirname(__DIR__) . '/config/local.php';
if (file_exists($local_config_path)) {
    require_once $local_config_path;
}

$host    = defined('DB_HOST') ? DB_HOST : 'localhost';
$db      = defined('DB_NAME') ? DB_NAME : 'boccia_india';
$user    = defined('DB_USER') ? DB_USER : 'root';
$pass    = defined('DB_PASS') ? DB_PASS : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     // Self-healing check for nsrs_id columns
     try {
         $pdo->exec("ALTER TABLE `athletes` ADD COLUMN `nsrs_id` VARCHAR(100) NULL DEFAULT NULL AFTER `regn_no`");
     } catch (\Throwable $t) {}
     try {
         $pdo->exec("ALTER TABLE `officials` ADD COLUMN `nsrs_id` VARCHAR(100) NULL DEFAULT NULL AFTER `official_reg_no`");
     } catch (\Throwable $t) {}
     try {
         $pdo->exec("ALTER TABLE `athletes` ADD UNIQUE KEY `uq_athletes_nsrs_id` (`nsrs_id`)");
     } catch (\Throwable $t) {}
     try {
         $pdo->exec("ALTER TABLE `officials` ADD UNIQUE KEY `uq_officials_nsrs_id` (`nsrs_id`)");
     } catch (\Throwable $t) {}
} catch (\PDOException $e) {
     die("PDO Error: " . $e->getMessage());
}

if (!function_exists('isNsrsIdUnique')) {
    function isNsrsIdUnique($pdo, $nsrsId, $excludeAthleteId = null, $excludeOfficialId = null) {
        $nsrsId = trim((string)$nsrsId);
        if ($nsrsId === '') return true;

        // Check in athletes table
        $sql1 = "SELECT id, full_name, regn_no FROM athletes WHERE nsrs_id = ?";
        $params1 = [$nsrsId];
        if ($excludeAthleteId) {
            $sql1 .= " AND id != ?";
            $params1[] = (int)$excludeAthleteId;
        }
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute($params1);
        $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        if ($row1) {
            return "NSRS ID '{$nsrsId}' is already assigned to Athlete: " . htmlspecialchars($row1['full_name']) . " (Reg No: " . htmlspecialchars($row1['regn_no']) . "). Please enter a unique NSRS ID.";
        }

        // Check in officials table
        $sql2 = "SELECT id, name, official_reg_no FROM officials WHERE nsrs_id = ?";
        $params2 = [$nsrsId];
        if ($excludeOfficialId) {
            $sql2 .= " AND id != ?";
            $params2[] = (int)$excludeOfficialId;
        }
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($row2) {
            return "NSRS ID '{$nsrsId}' is already assigned to Official: " . htmlspecialchars($row2['name']) . " (Reg No: " . htmlspecialchars($row2['official_reg_no']) . "). Please enter a unique NSRS ID.";
        }

        return true;
    }
}
