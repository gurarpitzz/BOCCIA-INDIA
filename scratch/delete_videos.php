<?php
require 'includes/db.php';
$deleted = $pdo->exec("DELETE FROM navigation_items WHERE title = 'Videos' AND section = 'news-media'");
echo "Deleted: " . $deleted . " row(s)\n";
