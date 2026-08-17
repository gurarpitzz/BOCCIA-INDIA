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
} catch (\PDOException $e) {
     die("PDO Error: " . $e->getMessage());
}
