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

    public static function clear() {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
