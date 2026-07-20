<?php
// check-email-logs.php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

echo "--- RECENT EMAIL LOGS ---\n";
try {
    $stmt = $pdo->query("SELECT id, recipient, subject, status, response_code, response_body, attempts, sent_at FROM email_logs ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
