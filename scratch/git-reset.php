<?php
// scratch/git-reset.php - Utility to recover cPanel Git from force-push divergence
header('Content-Type: text/plain');
echo "Recovering production cPanel repository to match GitHub...\n\n";

$output = [];
$return_var = 0;

// Execute Git fetch and hard reset
exec('git fetch origin 2>&1', $output, $return_var);
exec('git reset --hard origin/main 2>&1', $output, $return_var);

echo implode("\n", $output);
echo "\n\nExit code: " . $return_var;
if ($return_var === 0) {
    echo "\nSuccess! cPanel is now fully synced with GitHub.";
} else {
    echo "\nFailed to reset. Please check permissions.";
}
