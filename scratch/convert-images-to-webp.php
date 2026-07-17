<?php
$conversions = [
    'our starts bg.png' => 'our_starts_bg.webp',
    'about boccia/Antidoping.png' => 'about boccia/Antidoping.webp',
    'header reference.png' => null, // design reference only, skip
    'mobile heqader.png' => null,   // design reference only, skip
    'ref out star players.png' => null, // design reference only, skip
    'support us reference\'.png' => null, // design reference only, skip
    'ChatGPT Image Jun 30, 2026, 02_46_46 AM.png' => null, // asset ref only, skip
    'ChatGPT Image Jun 30, 2026, 02_47_21 AM.png' => null, // asset ref only, skip
];

foreach ($conversions as $src => $dst) {
    if ($dst === null) {
        echo "Skipping (design reference): $src\n";
        continue;
    }
    if (!file_exists($src)) {
        echo "Not found, skipping: $src\n";
        continue;
    }
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $img = imagecreatefrompng($src);
    } elseif (in_array($ext, ['jpg', 'jpeg'])) {
        $img = imagecreatefromjpeg($src);
    } else {
        echo "Unsupported format: $src\n";
        continue;
    }

    if (!$img) {
        echo "Failed to load: $src\n";
        continue;
    }

    $result = imagewebp($img, $dst, 85);
    imagedestroy($img);

    if ($result) {
        $origSize = round(filesize($src) / 1024, 1);
        $newSize = round(filesize($dst) / 1024, 1);
        echo "Converted: $src ($origSize KB) -> $dst ($newSize KB)\n";
    } else {
        echo "Failed to write: $dst\n";
    }
}

echo "\nDone.\n";
