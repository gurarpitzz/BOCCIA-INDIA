-- Migration: Update DB paths for images already converted to WebP on the server.
-- Run this ONLY after running the companion PHP script `convert_uploads_to_webp.php`
-- which physically converts the files on disk.
--
-- This script handles news thumbnails, cover images, and gallery images.
-- Athlete and official photos stored in uploads/ retain their original paths in
-- athlete_applications / official_applications; those are document records and
-- should NOT be batch-renamed unless the physical files have also been renamed.

-- Update news thumbnail paths (e.g. thumb_xxx.jpg -> thumb_xxx.webp)
UPDATE news
SET thumbnail_image = CONCAT(
    SUBSTRING_INDEX(thumbnail_image, '.', 1),
    '.webp'
)
WHERE thumbnail_image IS NOT NULL
  AND thumbnail_image REGEXP '\.(jpg|jpeg|png)$';

-- Update news cover image paths
UPDATE news
SET cover_image = CONCAT(
    SUBSTRING_INDEX(cover_image, '.', 1),
    '.webp'
)
WHERE cover_image IS NOT NULL
  AND cover_image REGEXP '\.(jpg|jpeg|png)$';

-- Update news gallery image paths
UPDATE news_images
SET image_path = CONCAT(
    SUBSTRING_INDEX(image_path, '.', 1),
    '.webp'
)
WHERE image_path IS NOT NULL
  AND image_path REGEXP '\.(jpg|jpeg|png)$';
