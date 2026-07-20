<?php
header('Content-Type: text/plain');
$log = __DIR__ . '/uploads/gallery_post.log';
if (file_exists($log)) {
    echo file_get_contents($log);
} else {
    echo "Log file does not exist.";
}
