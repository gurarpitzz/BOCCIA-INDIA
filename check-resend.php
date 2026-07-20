<?php
// check-resend.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/config/app.php';

header('Content-Type: text/plain');

echo "--- RUNNING RESEND API TEST ---\n";
echo "RESEND_API_KEY: " . substr(RESEND_API_KEY, 0, 10) . "...\n";

// Let's call _mailerDispatch directly to capture the raw response!
$to = 'pritpal.shimla@gmail.com';
$subject = 'Test Resend API - BSFI';
$html = '<p>This is a test email to verify Resend API integration.</p>';

list($httpCode, $response) = _mailerDispatch($to, $subject, $html, null);

echo "HTTP Code: $httpCode\n";
echo "Response Body: $response\n";
