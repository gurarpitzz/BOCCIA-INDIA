<?php
// scratch/sync-diagnostic.php - Diagnostic to check database navigation items state
header('Content-Type: text/plain');
echo "Checking database navigation items state...\n\n";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM navigation_items ORDER BY parent_id ASC, sort_order ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($items) . " navigation items in the database.\n\n";
    
    foreach ($items as $item) {
        echo "ID: " . $item['id'] . " | Parent: " . ($item['parent_id'] ?? 'NULL') . 
             " | Title: " . $item['title'] . " | Slug: " . ($item['slug'] ?? 'NULL') . 
             " | Section: " . ($item['section'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
