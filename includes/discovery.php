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
            'news' => $baseAssetsDir . '/02_Page_Content/06_News_Media (1)',
            'gallery' => __DIR__ . '/../gallery'
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
            // Clear any previously discovered temporary gallery images to clean the database
            $this->pdo->exec("DELETE FROM gallery_images WHERE category = 'Discovered'");
            $logs[] = "Cleared old discovered gallery images from database.";

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

        // Determine target upload subfolder based on file type and section
        $subfolder = 'documents';
        if ($section === 'news') {
            $subfolder = 'news';
        } elseif ($section === 'competitions') {
            $subfolder = 'events';
        } elseif ($section === 'gallery') {
            $subfolder = 'galleries';
        } else {
            // General pages and board headshots - store under documents if image/video
            $subfolder = 'documents';
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

        // If it is from the gallery section and an image, register in gallery_images table
        if ($section === 'gallery' && strpos($mimeType, 'image/') !== false) {
            $galTitle = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            $chkGal = $this->pdo->prepare("SELECT id FROM gallery_images WHERE image_path = ?");
            $chkGal->execute([$relativeDestPath]);
            if (!$chkGal->fetch()) {
                $insGal = $this->pdo->prepare("INSERT INTO gallery_images (title, category, event_name, image_path, active) VALUES (?, 'Collage', 'Federation Gallery', ?, 1)");
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
        // Clear all existing navigation items first to ensure a clean, fixed slate
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE navigation_items;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $navTree = [
            [
                'title' => 'About',
                'section' => 'about',
                'slug' => null,
                'children' => [
                    ['title' => 'About Boccia', 'slug' => 'about-boccia', 'section' => 'about'],
                    ['title' => 'Board', 'slug' => 'board', 'section' => 'about'],
                    ['title' => 'Affiliation With PCI', 'slug' => 'affiliation-pci', 'section' => 'about'],
                    ['title' => 'Affiliation World Boccia', 'slug' => 'affiliation-world-boccia', 'section' => 'about'],
                    [
                        'title' => 'MYAS Disclosures',
                        'slug' => 'myas-disclosures',
                        'section' => 'about',
                        'children' => [
                            ['title' => 'Administrative Sanction', 'slug' => 'administrative-sanction', 'section' => 'myas'],
                            ['title' => 'Financial Sanctions', 'slug' => 'financial-sanctions', 'section' => 'myas'],
                            ['title' => 'Mandatory Disclosures', 'slug' => 'mandatory-disclosures', 'section' => 'myas'],
                            ['title' => 'Regulation Of Prevention Fraud By The Athletes', 'slug' => 'athlete-prevention', 'section' => 'myas'],
                            ['title' => 'Elections', 'slug' => 'elections', 'section' => 'myas'],
                            ['title' => 'Minutes Of Meetings', 'slug' => 'minutes-of-meetings', 'section' => 'myas']
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Our Sport',
                'section' => 'our-sport',
                'slug' => null,
                'children' => [
                    ['title' => 'Rules', 'slug' => 'rules', 'section' => 'sport'],
                    ['title' => 'Anti-Doping', 'slug' => 'anti-doping', 'section' => 'sport'],
                    ['title' => 'Classification', 'slug' => 'classification', 'section' => 'sport'],
                    ['title' => 'Equipment', 'slug' => 'equipment', 'section' => 'sport']
                ]
            ],
            [
                'title' => 'Get Involved',
                'section' => 'get-involved',
                'slug' => null,
                'children' => [
                    ['title' => 'Membership', 'slug' => 'membership', 'section' => 'get-involved'],
                    ['title' => 'Player Database 2026', 'slug' => 'players-database', 'section' => 'get-involved'],
                    ['title' => 'Officials Database 2026', 'slug' => 'officials-database', 'section' => 'get-involved']
                ]
            ],
            [
                'title' => 'Competitions',
                'section' => 'competitions',
                'slug' => null,
                'children' => [
                    ['title' => 'International Events', 'slug' => 'international-events', 'section' => 'competitions']
                ]
            ],
            [
                'title' => 'News & Media',
                'section' => 'news-media',
                'slug' => null,
                'children' => [
                    ['title' => 'News', 'slug' => 'news', 'section' => 'news-media'],
                    ['title' => 'Gallery', 'slug' => 'gallery', 'section' => 'news-media'],
                    ['title' => 'BSFI Tender', 'slug' => 'tenders', 'section' => 'news-media']
                ]
            ],
            [
                'title' => 'Selection Guidelines',
                'section' => 'selection-guidelines',
                'slug' => null,
                'children' => [
                    ['title' => 'Selection Policy', 'slug' => 'selection-policy', 'section' => 'selection-guidelines'],
                    ['title' => 'Boccia Asian Para Games 2026', 'slug' => 'apg-2026', 'section' => 'selection-guidelines'],
                    ['title' => 'Selection Trials APG 2026', 'slug' => 'apg-trials-2026', 'section' => 'selection-guidelines']
                ]
            ]
        ];

        $this->insertNavNodes(null, $navTree);
    }

    private function insertNavNodes($parentId, $nodes) {
        $order = 1;
        foreach ($nodes as $node) {
            $slug = $node['slug'] ?? null;
            $section = $node['section'] ?? null;
            
            $stmt = $this->pdo->prepare("INSERT INTO navigation_items (parent_id, title, slug, section, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$parentId, $node['title'], $slug, $section, $order++]);
            $nodeId = $this->pdo->lastInsertId();

            if (!empty($node['children'])) {
                $this->insertNavNodes($nodeId, $node['children']);
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
