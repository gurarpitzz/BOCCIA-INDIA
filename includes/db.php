<?php
// db.php - MySQL PDO Connection

$host = 'localhost';
$db   = 'boccia_india';
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
     // Don't leak DB secrets in production, log it or print a simple message
     die("Database connection failed. Please make sure MySQL is running in XAMPP.");
}
