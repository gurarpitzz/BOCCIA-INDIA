<?php
// includes/discovery.php - Idempotent Content Discovery Engine

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';

class ContentDiscoveryEngine {
    private $pdo;
    private $contentRegistry;
    private $allowedExtensions = ['pdf', 'docx', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'webp', 'mp4', 'webm'];
    private $uploadDirs = [
        'documents' => __DIR__ . '/../uploads/documents',
        'news' => __DIR__ . '/../uploads/news',
        'galleries' => __DIR__ . '/../uploads/galleries',
        'tenders' => __DIR__ . '/../uploads/tenders',
        'events' => __DIR__ . '/../uploads/events',
        'memberships' => __DIR__ . '/../uploads/memberships'
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Define paths relative to this file
        $baseAssetsDir = __DIR__ . '/../BSFI_Website_Revamp_Assets-20260616T051826Z-3-001/BSFI_Website_Revamp_Assets';
        
        $this->contentRegistry = [
            'about' => $baseAssetsDir . '/02_Page_Content/02_About_Us (1)',
            'myas' => $baseAssetsDir . '/02_Page_Content/03_MYAS_Disclosures (1)',
            'sport' => $baseAssetsDir . '/02_Page_Content/04_Our_Sport (1)',
            'competitions' => $baseAssetsDir . '/02_Page_Content/05_Competitions (1)',
            'news' => $baseAssetsDir . '/02_Page_Content/06_News_Media (1)'
        ];

        $this->initUploadDirs();
    }

    private function initUploadDirs() {
        foreach ($this->uploadDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    public function runSync() {
        $logs = [];
        $logs[] = "Starting content synchronization at " . date('Y-m-d H:i:s');

        try {
            // Seed parent navigation items if they don't exist
            $this->seedNavigationParents();

            // Scan all registered categories
            foreach ($this->contentRegistry as $section => $path) {
                if (!is_dir($path)) {
                    $logs[] = "Registry directory not found: $path";
                    continue;
                }

                $logs[] = "Scanning category '$section' at $path...";
                $this->scanDirectory($path, $section, $logs);
            }

            // Sync players/officials CSV if present
            $this->syncCsvDatabases($logs);

            // Clear cache to reflect new/updated content
            FileCache::clear();
            $logs[] = "Cache cleared successfully.";
            $logs[] = "Sync finished successfully.";

            $this->logActivity("Sync Assets", "Content synchronization executed successfully.");

        } catch (\Exception $e) {
            $logs[] = "CRITICAL ERROR: " . $e->getMessage();
            $this->logActivity("Sync Assets Failed", $e->getMessage());
        }

        return $logs;
    }

    private function scanDirectory($dirPath, $section, &$logs) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = str_replace('\\', '/', $file->getRealPath());
            $filename = $file->getFilename();

            // Skip hidden files or READMEs or helper txt guides starting with underscore
            if (strpos($filename, '.') === 0 || strpos($filename, '_') === 0 || $filename === 'thumbs.db') {
                continue;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $this->allowedExtensions) && $ext !== 'txt' && $ext !== 'md') {
                continue;
            }

            // Mitigate path traversal and sanitize filename
            $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
            
            $logs[] = "Processing file: $filename";

            if ($ext === 'txt' || $ext === 'md') {
                // Parse text file as a page content
                $this->syncTextPage($filePath, $sanitizedFilename, $section, $logs);
            } else {
                // Sync as document/media asset
                $this->syncMediaAsset($filePath, $sanitizedFilename, $section, $logs);
            }
        }
    }

    private function syncTextPage($filePath, $filename, $section, &$logs) {
        $rawContent = file_get_contents($filePath);
        // Basic sanitization
        $content = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');

        // Formulate Title from filename
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = str_replace(['_', '-'], ' ', $title);
        $title = ucwords($title);

        // Determine destination folder category and dynamic slug
        $slug = $this->generateUniqueSlug($title, $section);

        // Check if page already exists by file reference or slug
        $stmt = $this->pdo->prepare("SELECT id FROM site_pages WHERE section = ? AND (slug = ? OR title = ?)");
        $stmt->execute([$section, $slug, $title]);
        $page = $stmt->fetch();

        if ($page) {
            // Update existing page content
            $updateStmt = $this->pdo->prepare("UPDATE site_pages SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$content, $page['id']]);
            $pageId = $page['id'];
            $logs[] = "Updated text page: '$title' in section '$section'";
        } else {
            // Insert new page
            $insertStmt = $this->pdo->prepare("INSERT INTO site_pages (section, slug, title, content, status) VALUES (?, ?, ?, ?, 'published')");
            $insertStmt->execute([$section, $slug, $title, $content]);
            $pageId = $this->pdo->lastInsertId();
            $logs[] = "Created new page: '$title' (slug: $slug) in section '$section'";

            // Create dynamic navigation link
            $this->addNavigationLink($title, $slug, $section);
        }

        // Keep page version snapshot
        $this->savePageVersion($pageId, $content);

        // Update Search Index
        $url = "page.php?section=$section&slug=$slug";
        $this->updateSearchIndex($title, $rawContent, 'page', $url);
    }

    private function syncMediaAsset($filePath, $filename, $section, &$logs) {
        $filesize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        // Determine target upload subfolder based on file type
        $subfolder = 'documents';
        if (strpos($mimeType, 'image/') !== false) {
            $subfolder = 'galleries';
        } elseif (strpos($mimeType, 'video/') !== false) {
            $subfolder = 'galleries';
        }

        $destPath = $this->uploadDirs[$subfolder] . '/' . $filename;
        $relativeDestPath = 'uploads/' . $subfolder . '/' . $filename;

        // Copy file dynamically to keep original read-only
        if (!file_exists($destPath) || filesize($filePath) !== filesize($destPath)) {
            copy($filePath, $destPath);
        }

        // Check if media asset exists
        $stmt = $this->pdo->prepare("SELECT id FROM media_assets WHERE filepath = ?");
        $stmt->execute([$relativeDestPath]);
        $asset = $stmt->fetch();

        if ($asset) {
            // Update metadata
            $updateStmt = $this->pdo->prepare("UPDATE media_assets SET filesize = ?, mime_type = ? WHERE id = ?");
            $updateStmt->execute([$filesize, $mimeType, $asset['id']]);
            $assetId = $asset['id'];
        } else {
            // Insert asset registry
            $insertStmt = $this->pdo->prepare("INSERT INTO media_assets (filename, filepath, mime_type, filesize) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$filename, $relativeDestPath, $mimeType, $filesize]);
            $assetId = $this->pdo->lastInsertId();
            $logs[] = "Registered media asset: $filename";
        }

        // If it's a gallery subfolder and an image, register in gallery_images table
        if ($subfolder === 'galleries' && strpos($mimeType, 'image/') !== false) {
            $galTitle = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            $chkGal = $this->pdo->prepare("SELECT id FROM gallery_images WHERE image_path = ?");
            $chkGal->execute([$relativeDestPath]);
            if (!$chkGal->fetch()) {
                $insGal = $this->pdo->prepare("INSERT INTO gallery_images (title, category, event_name, image_path, active) VALUES (?, 'Discovered', 'BSFI Dynamic Sync', ?, 1)");
                $insGal->execute([$galTitle, $relativeDestPath]);
                $logs[] = "Registered gallery image: $galTitle";
            }
        }

        // If it's a document category, make it queryable under events or site pages
        // E.g., if section is 'competitions' and PDF contains circular/results, map to event system
        if ($section === 'competitions' && strpos($mimeType, 'application/pdf') !== false) {
            $this->syncEventDocument($filename, $relativeDestPath, $assetId, $logs);
        }

        // Update Search Index for document types
        if ($subfolder === 'documents') {
            $title = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            $this->updateSearchIndex($title, "Document: $filename. Type: $mimeType", 'document', $relativeDestPath);
        }
    }

    private function syncEventDocument($filename, $filepath, $assetId, &$logs) {
        // Find or create default National Championship event to attach documents
        $eventTitle = "National Championship & Trials";
        $stmt = $pdo = $this->pdo->prepare("SELECT id FROM events WHERE title = ?");
        $stmt->execute([$eventTitle]);
        $event = $stmt->fetch();

        if ($event) {
            $eventId = $event['id'];
        } else {
            $insertEvent = $this->pdo->prepare("INSERT INTO events (title, location, start_date, end_date, status) VALUES (?, 'TBD', CURRENT_DATE, CURRENT_DATE, 'upcoming')");
            $insertEvent->execute([$eventTitle]);
            $eventId = $this->pdo->lastInsertId();
        }

        // Determine doc type from filename keywords
        $docType = 'other';
        if (stripos($filename, 'circular') !== false || stripos($filename, 'policy') !== false) {
            $docType = 'circular';
        } elseif (stripos($filename, 'results') !== false) {
            $docType = 'results';
        } elseif (stripos($filename, 'schedule') !== false) {
            $docType = 'schedule';
        }

        // Check if document already mapped
        $chk = $this->pdo->prepare("SELECT id FROM event_documents WHERE event_id = ? AND media_asset_id = ?");
        $chk->execute([$eventId, $assetId]);
        if (!$chk->fetch()) {
            $cleanTitle = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            $insDoc = $this->pdo->prepare("INSERT INTO event_documents (event_id, media_asset_id, doc_type, title) VALUES (?, ?, ?, ?)");
            $insDoc->execute([$eventId, $assetId, $docType, $cleanTitle]);
            $logs[] = "Attached circular document '$cleanTitle' to event '$eventTitle'";
        }
    }

    private function syncCsvDatabases(&$logs) {
        $csvPath = __DIR__ . '/../BSFI_Website_Revamp_Assets-20260616T051826Z-3-001/BSFI_Website_Revamp_Assets/03_Database_and_Forms/03_Database_and_Forms/_DROP_EXISTING_CSV_DATABASES_HERE';
        
        if (!is_dir($csvPath)) {
            return;
        }

        $files = glob($csvPath . '/*.csv');
        foreach ($files as $file) {
            $filename = basename($file);
            $logs[] = "Found CSV Database file: $filename";
            
            // Auto import athletes/officials table
            if (stripos($filename, 'athlete') !== false || stripos($filename, 'player') !== false) {
                $this->importAthletesCsv($file, $logs);
            }
        }
    }

    private function importAthletesCsv($file, &$logs) {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            
            // Clean headers (remove BOM if present)
            $headers[0] = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}]/u', '', $headers[0]);
            
            $rowCount = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < count($headers)) continue;
                
                $row = array_combine($headers, $data);
                
                // Read row keys safely
                $fullName = trim($row['Name'] ?? $row['Full Name'] ?? '');
                $regnNo = trim($row['Regn No'] ?? $row['Reg No'] ?? '');
                $gender = strtoupper(trim($row['Gender'] ?? 'MALE'));
                $state = trim($row['State'] ?? '');
                $classification = trim($row['Classification'] ?? $row['Boccia Category'] ?? 'BC1');
                
                if (empty($fullName) || empty($regnNo)) {
                    continue;
                }

                // Check if athlete already exists
                $stmt = $this->pdo->prepare("SELECT id FROM athletes WHERE regn_no = ?");
                $stmt->execute([$regnNo]);
                
                if (!$stmt->fetch()) {
                    $ins = $this->pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, state, classification, representing_for, status) VALUES (?, ?, ?, '2000-01-01', ?, ?, ?, 'approved')");
                    $ins->execute([$regnNo, $fullName, $gender, $state, $classification, $state]);
                    $rowCount++;
                }
            }
            fclose($handle);
            $logs[] = "Imported $rowCount athlete record(s) from CSV.";
        }
    }

    private function seedNavigationParents() {
        $sections = [
            'About' => 'about',
            'Our Sport' => 'our-sport',
            'Get Involved' => 'get-involved',
            'Competitions' => 'competitions',
            'News & Media' => 'news-media',
            'Selection Guidelines' => 'selection-guidelines'
        ];

        $order = 1;
        foreach ($sections as $title => $sec) {
            $stmt = $this->pdo->prepare("SELECT id FROM navigation_items WHERE title = ? AND parent_id IS NULL");
            $stmt->execute([$title]);
            $parent = $stmt->fetch();
            
            if (!$parent) {
                $ins = $this->pdo->prepare("INSERT INTO navigation_items (title, section, sort_order) VALUES (?, ?, ?)");
                $ins->execute([$title, $sec, $order++]);
                $parentId = $this->pdo->lastInsertId();
            } else {
                $parentId = $parent['id'];
            }

            // Seed specific child links for Get Involved section
            if ($sec === 'get-involved') {
                $children = [
                    'Membership' => 'membership',
                    'Players Database 2024' => 'players-database',
                    'Officials Database 2024' => 'officials-database'
                ];
                $cOrder = 1;
                foreach ($children as $cTitle => $cSlug) {
                    $cStmt = $this->pdo->prepare("SELECT id FROM navigation_items WHERE parent_id = ? AND slug = ?");
                    $cStmt->execute([$parentId, $cSlug]);
                    if (!$cStmt->fetch()) {
                        $cIns = $this->pdo->prepare("INSERT INTO navigation_items (parent_id, title, slug, section, sort_order) VALUES (?, ?, ?, ?, ?)");
                        $cIns->execute([$parentId, $cTitle, $cSlug, 'get-involved', $cOrder++]);
                    }
                }
            }

            // Seed specific child links for News & Media section
            if ($sec === 'news-media') {
                $children = [
                    'News' => 'news',
                    'Gallery' => 'gallery',
                    'Videos' => 'videos',
                    'BSFI Tender' => 'tenders'
                ];
                $cOrder = 1;
                foreach ($children as $cTitle => $cSlug) {
                    $cStmt = $this->pdo->prepare("SELECT id FROM navigation_items WHERE parent_id = ? AND slug = ?");
                    $cStmt->execute([$parentId, $cSlug]);
                    if (!$cStmt->fetch()) {
                        $cIns = $this->pdo->prepare("INSERT INTO navigation_items (parent_id, title, slug, section, sort_order) VALUES (?, ?, ?, ?, ?)");
                        $cIns->execute([$parentId, $cTitle, $cSlug, 'news-media', $cOrder++]);
                    }
                }
            }
        }
    }

    private function addNavigationLink($title, $slug, $section) {
        // Find parent nav item matching section
        $parentSectionMap = [
            'about' => 'About',
            'myas' => 'About', // MYAS is sub of About
            'sport' => 'Our Sport',
            'competitions' => 'Competitions',
            'news' => 'News & Media'
        ];

        $parentTitle = $parentSectionMap[$section] ?? 'About';
        $stmt = $this->pdo->prepare("SELECT id FROM navigation_items WHERE title = ? AND parent_id IS NULL");
        $stmt->execute([$parentTitle]);
        $parent = $stmt->fetch();

        if ($parent) {
            $parentId = $parent['id'];
            
            // Check if already in navigation
            $chk = $this->pdo->prepare("SELECT id FROM navigation_items WHERE parent_id = ? AND slug = ?");
            $chk->execute([$parentId, $slug]);
            
            if (!$chk->fetch()) {
                // Get next sort order
                $sortStmt = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM navigation_items WHERE parent_id = ?");
                $sortStmt->execute([$parentId]);
                $nextSort = $sortStmt->fetchColumn();

                $ins = $this->pdo->prepare("INSERT INTO navigation_items (parent_id, title, slug, section, sort_order) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$parentId, $title, $slug, $section, $nextSort]);
            }
        }
    }

    private function generateUniqueSlug($title, $section) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        if (empty($slug)) { $slug = 'page'; }
        
        $originalSlug = $slug;
        $i = 1;
        while (true) {
            $stmt = $this->pdo->prepare("SELECT id FROM site_pages WHERE section = ? AND slug = ?");
            $stmt->execute([$section, $slug]);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $originalSlug . '-' . $i;
            $i++;
        }
    }

    private function savePageVersion($pageId, $content) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(version), 0) + 1 FROM page_versions WHERE page_id = ?");
        $stmt->execute([$pageId]);
        $nextVer = $stmt->fetchColumn();

        $ins = $this->pdo->prepare("INSERT INTO page_versions (page_id, version, content_snapshot) VALUES (?, ?, ?)");
        $ins->execute([$pageId, $nextVer, $content]);
    }

    private function updateSearchIndex($title, $content, $type, $url) {
        // Standardize tags/content indexing
        $cleanContent = strip_tags($content);
        
        $stmt = $this->pdo->prepare("INSERT INTO search_index (title, content, type, url) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$title, $cleanContent, $type, $url, $title, $cleanContent]);
    }

    private function logActivity($action, $details) {
        $stmt = $this->pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
        $stmt->execute([$action, $details]);
    }
}
