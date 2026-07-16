<?php
/**
 * convert_uploads_to_webp.php
 *
 * PRODUCTION SERVER UTILITY — run once via SSH or cPanel File Manager.
 * Scans all JPG/PNG images under /uploads/news/, /uploads/athletes/photos/,
 * and /uploads/officials/photos/, converts them to WebP (quality 82),
 * and deletes the originals.
 *
 * After running this script, run the SQL migration:
 *   database/migrations/012_update_news_image_paths_to_webp.sql
 * to update news thumbnail/cover/gallery paths stored in the database.
 *
 * NOTE: Athlete and official photo paths in athlete_applications /
 *       official_applications are NOT mass-updated here because they are
 *       document records. Those will naturally transition to WebP for all
 *       new uploads going forward.
 *
 * Usage (SSH):
 *   php convert_uploads_to_webp.php
 *
 * Usage (cURL / browser — set a strong secret):
 *   https://yoursite.com/convert_uploads_to_webp.php?secret=CHANGE_THIS
 */

define('ALLOWED_SECRET', 'CHANGE_THIS_TO_A_STRONG_RANDOM_STRING');

// Allow running from CLI or with secret token from browser
if (PHP_SAPI !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if ($secret !== ALLOWED_SECRET) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

if (!function_exists('imagewebp')) {
    exit("ERROR: PHP GD WebP support is not available on this server.\n");
}

$baseDir = __DIR__ . '/uploads/';
$dirs = [
    $baseDir . 'news',
    $baseDir . 'athletes/photos',
    $baseDir . 'officials/photos',
];

$converted = 0;
$skipped   = 0;
$errors    = 0;

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        echo "Skipping (not found): $dir\n";
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $skipped++;
            continue;
        }

        $src  = $file->getPathname();
        $dst  = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $src);

        if (file_exists($dst)) {
            echo "Already exists, skipping: $dst\n";
            $skipped++;
            continue;
        }

        // Load
        if ($ext === 'png') {
            $img = @imagecreatefrompng($src);
        } else {
            $img = @imagecreatefromjpeg($src);
        }

        if (!$img) {
            echo "ERROR loading: $src\n";
            $errors++;
            continue;
        }

        // Save WebP
        $ok = imagewebp($img, $dst, 82);
        imagedestroy($img);

        if ($ok) {
            $origKb = round(filesize($src) / 1024, 1);
            $newKb  = round(filesize($dst) / 1024, 1);
            echo "OK [$origKb KB → $newKb KB] $src\n";
            unlink($src); // remove original after successful conversion
            $converted++;
        } else {
            echo "ERROR writing WebP: $dst\n";
            $errors++;
        }
    }
}

echo "\n=== Done. Converted: $converted | Skipped: $skipped | Errors: $errors ===\n";
echo "Next step: Run database/migrations/012_update_news_image_paths_to_webp.sql in phpMyAdmin.\n";
