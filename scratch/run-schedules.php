<?php
// scratch/run-schedules.php - Simulates a logged-in admin request to schedules.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['csrf_token'] = 'test';

$_SERVER['REQUEST_METHOD'] = 'GET';
require __DIR__ . '/../admin/schedules.php';
