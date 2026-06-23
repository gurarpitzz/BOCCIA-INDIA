<?php
// db.php - MySQL PDO Connection

if (!defined('PRIVATE_UPLOADS_DIR')) {
    define('PRIVATE_UPLOADS_DIR', dirname(__DIR__, 2) . '/private_uploads/');
}


$host = 'localhost';
$db = 'boccia_india';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("PDO Error: " . $e->getMessage());
}
