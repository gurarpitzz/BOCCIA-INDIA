<?php
// scratch/git-reset.php - Discards server-side git modifications to allow clean cPanel pulls
header('Content-Type: text/plain');

$output = [];
$return_val = 0;

// Execute git checkout to discard server-side modifications on includes/discovery.php
exec('git checkout -- includes/discovery.php 2>&1', $output, $return_val);

echo "Git Checkout Output:\n";
echo implode("\n", $output) . "\n\n";
echo "Exit Code: " . $return_val . "\n\n";

$pull_output = [];
exec('git pull origin main 2>&1', $pull_output, $return_val);
echo "Git Pull Output:\n";
echo implode("\n", $pull_output) . "\n\n";
echo "Exit Code: " . $return_val . "\n";
