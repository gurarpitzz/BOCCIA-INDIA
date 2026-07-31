<?php
// scratch/convert-logo.php - Convert WebP logo to PNG for social previews compatibility
header('Content-Type: text/plain');
echo "Converting WebP logo to PNG...\n\n";

$webp_path = dirname(__DIR__) . '/boccia-india-logo.webp';
$png_path = dirname(__DIR__) . '/boccia-india-logo.png';

if (file_exists($webp_path)) {
    if (function_exists('imagecreatefromwebp') && function_exists('imagepng')) {
        $im = @imagecreatefromwebp($webp_path);
        if ($im) {
            // Preserve transparency if any
            imagealphablending($im, false);
            imagesavealpha($im, true);
            
            if (imagepng($im, $png_path)) {
                echo "SUCCESS: Converted logo to PNG format at:\n$png_path\n";
            } else {
                echo "ERROR: Failed to write PNG file.\n";
            }
            imagedestroy($im);
        } else {
            echo "ERROR: Failed to load WebP image. GD library may have issues reading this WebP file.\n";
        }
    } else {
        echo "ERROR: GD library functions (imagecreatefromwebp / imagepng) are not available in this PHP environment.\n";
    }
} else {
    echo "ERROR: WebP logo not found at: $webp_path\n";
}
