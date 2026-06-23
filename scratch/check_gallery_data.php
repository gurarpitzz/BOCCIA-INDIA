<?php
$cacheFile = __DIR__ . '/../cache/gallery_homepage.json';
if (file_exists($cacheFile)) {
    echo "Cache file exists\n";
    $content = file_get_contents($cacheFile);
    echo "Content length: " . strlen($content) . "\n";
    $cacheData = json_decode($content, true);
    if ($cacheData === null) {
        echo "JSON decode failed: " . json_last_error_msg() . "\n";
    } else {
        echo "Images count: " . count($cacheData['images'] ?? []) . "\n";
        echo "Hero count: " . count($cacheData['hero'] ?? []) . "\n";
        echo "Albums count: " . count($cacheData['albums'] ?? []) . "\n";
    }
} else {
    echo "Cache file does not exist\n";
}
