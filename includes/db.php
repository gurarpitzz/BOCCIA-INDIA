<?php
// db.php - MySQL PDO Connection

$host = 'localhost';
$db   = 'tstpllmy_boccia_india';
$user = 'tstpllmy_boccia_user';
$pass = 'Boccia@2026!India#DB';
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
