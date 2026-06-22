<?php
require_once __DIR__ . '/../includes/db.php';

echo "Starting Gallery Hierarchy Data Migration...\n";

try {
    $pdo->beginTransaction();

    // 1. Fetch all existing images
    $stmt = $pdo->query("SELECT * FROM gallery_images");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($images) . " images to migrate.\n";

    // Helper to get or create an album
    $albumsMap = []; // cache of created albums: slug => id

    function getOrCreateAlbum($pdo, $title, $slug, $catId, $date, $location, $desc, &$albumsMap) {
        if (isset($albumsMap[$slug])) {
            return $albumsMap[$slug];
        }

        // Insert new album
        $ins = $pdo->prepare("
            INSERT INTO gallery_albums (category_id, title, slug, description, event_date, event_location, album_type, is_published, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, 'event', 1, 1)
        ");
        $ins->execute([$catId, $title, $slug, $desc, $date, $location]);
        $albumId = $pdo->lastInsertId();
        $albumsMap[$slug] = $albumId;

        echo "Created Album: '$title' in category $catId\n";
        return $albumId;
    }

    // 2. Clear album_id temporarily to bypass constraints if any
    $pdo->exec("UPDATE gallery_images SET album_id = NULL");

    // 3. Process each image and assign to appropriate album
    foreach ($images as $img) {
        $caption = $img['caption'] ?? '';
        $oldAlbumId = $img['album_id_old'] ?? $img['album_id']; // Use fallback if we already renamed

        // Logic to determine Album
        if (stripos($caption, 'Award') !== false || stripos($caption, 'Ceremony') !== false) {
            $albumId = getOrCreateAlbum(
                $pdo, 
                'Award Ceremony June 2026', 
                'award-ceremony-june-2026', 
                1, // National
                '2026-06-06', 
                'Bathinda, Punjab', 
                'Celebrations, medals, and podium moments.', 
                $albumsMap
            );
        } elseif (stripos($caption, 'Competition') !== false || stripos($caption, 'Day') !== false) {
            $albumId = getOrCreateAlbum(
                $pdo, 
                'Competition Day June 2026', 
                'competition-day-june-2026', 
                1, // National
                '2026-06-05', 
                'Bathinda, Punjab', 
                'Action-packed matches and competitive matches.', 
                $albumsMap
            );
        } elseif (stripos($caption, 'National') !== false || stripos($caption, 'BSFI') !== false) {
            $albumId = getOrCreateAlbum(
                $pdo, 
                'National Championship June 2026', 
                'national-championship-june-2026', 
                1, // National
                '2026-06-03', 
                'Bathinda, Punjab', 
                'Opening ceremony and tournament proceedings.', 
                $albumsMap
            );
        } else {
            // General or other category fallback
            $catId = $oldAlbumId ?: 5; // default to General category
            $catNameQuery = $pdo->prepare("SELECT name FROM gallery_categories WHERE id = ?");
            $catNameQuery->execute([$catId]);
            $catName = $catNameQuery->fetchColumn() ?: 'General';

            $title = $catName . " Showcase";
            $slug = strtolower(str_replace(' ', '-', $title));
            
            $albumId = getOrCreateAlbum(
                $pdo, 
                $title, 
                $slug, 
                $catId, 
                date('Y-m-d'), 
                'National', 
                'Official collection of media and promotional gallery resources.', 
                $albumsMap
            );
        }

        // Link image to the new album
        $up = $pdo->prepare("UPDATE gallery_images SET album_id = ? WHERE id = ?");
        $up->execute([$albumId, $img['id']]);
    }

    // 4. Set cover images for all newly created albums
    foreach ($albumsMap as $slug => $albumId) {
        // Find first image in that album
        $firstImgStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
        $firstImgStmt->execute([$albumId]);
        $firstImgId = $firstImgStmt->fetchColumn();

        if ($firstImgId) {
            $upAlbum = $pdo->prepare("UPDATE gallery_albums SET cover_image_id = ? WHERE id = ?");
            $upAlbum->execute([$firstImgId, $albumId]);
            echo "Set cover_image_id = $firstImgId for Album ID $albumId\n";
        }
    }

    // 5. Update foreign key constraint on gallery_images
    // First, let's try dropping the old constraint if it exists.
    // In standard MySQL, let's just attempt to add a new constraint.
    try {
        $pdo->exec("ALTER TABLE gallery_images ADD CONSTRAINT fk_gallery_images_albums FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE SET NULL");
        echo "Foreign key constraint fk_gallery_images_albums added successfully.\n";
    } catch (PDOException $ex) {
        // Already exists or key conflict
        echo "Note on constraint: " . $ex->getMessage() . "\n";
    }

    $pdo->commit();
    echo "Gallery Data Migration completed successfully!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
