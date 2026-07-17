<?php
// scratch/run-schedules-post.php - Simulates a logged-in admin POST request to schedules.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['csrf_token'] = 'test';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => 'test',
    'save_schedule' => '1',
    'id' => '0',
    'discipline' => 'Para Boccia Camp',
    'event_type' => 'State Level',
    'date_text' => '25-26 Aug',
    'venue' => 'Court 1',
    'registration_mode' => 'internal',
    'registration_fee' => '500.00',
    'registration_deadline' => '2026-08-20T12:00',
    'max_participants' => '50',
    'allow_waiting_list' => '1',
    'sort_order' => '0',
    'active' => '1'
];
require __DIR__ . '/../admin/schedules.php';
