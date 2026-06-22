<?php
// includes/cache.php - Lightweight File Cache Manager

class FileCache {
    private static $cacheDir = __DIR__ . '/../cache';

    private static function init() {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
    }

    public static function set($key, $data, $ttl = 3600) {
        self::init();
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        $cacheData = [
            'expires' => time() + $ttl,
            'data' => $data
        ];
        file_put_contents($cacheFile, serialize($cacheData));
    }

    public static function get($key) {
        self::init();
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        if (!file_exists($cacheFile)) {
            return null;
        }
        $content = file_get_contents($cacheFile);
        $cacheData = unserialize($content);
        if (time() > $cacheData['expires']) {
            unlink($cacheFile);
            return null;
        }
        return $cacheData['data'];
    }

    public static function delete($key) {
        self::init();
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public static function writeHomepageGallery($pdo) {
        self::init();
        try {
            // Fetch all active gallery images
            $stmt = $pdo->query("
                SELECT g.*, a.title AS album_title, a.slug AS album_slug
                FROM gallery_images g
                LEFT JOIN gallery_albums a ON g.album_id = a.id
                WHERE g.is_deleted = 0 AND g.status = 'published'
                ORDER BY g.is_featured DESC, g.sort_order ASC, g.created_at DESC
                LIMIT 50
            ");
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch albums list
            $albumStmt = $pdo->query("SELECT * FROM gallery_albums ORDER BY id ASC");
            $albums = $albumStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch hero slide images
            $heroStmt = $pdo->query("
                SELECT * FROM gallery_images
                WHERE show_in_hero = 1 AND status = 'published' AND is_deleted = 0
                ORDER BY sort_order ASC
                LIMIT 5
            ");
            $heroImages = $heroStmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'images' => $images,
                'albums' => $albums,
                'hero' => $heroImages,
                'updated_at' => time()
            ];

            file_put_contents(self::$cacheDir . '/gallery_homepage.json', json_encode($data));
        } catch (Exception $e) {
            // Fail silently
        }
    }

    public static function clear() {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        if (file_exists(self::$cacheDir . '/gallery_homepage.json')) {
            unlink(self::$cacheDir . '/gallery_homepage.json');
        }
    }
}
