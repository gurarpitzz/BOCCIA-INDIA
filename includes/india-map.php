<?php
// includes/india-map.php - Vector India Heatmap component with file caching

// Ensure cache folder exists
$cache_dir = __DIR__ . '/../cache';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$cache_file = $cache_dir . '/state-stats.json';
$stats = [];

// Try loading cached stats (Refresh cached file if older than 5 minutes)
if (file_exists($cache_file) && (time() - filemtime($cache_file) < 300)) {
    $stats = json_decode(file_get_contents($cache_file), true);
} else {
    // Regenerate cache dynamically on request timeout
    try {
        $detailsQuery = $pdo->query("SELECT state, status, classification, COUNT(*) as count FROM athletes GROUP BY state, status, classification");
        while ($r = $detailsQuery->fetch()) {
            $st = $r['state'];
            $stStatus = $r['status'];
            $class = $r['classification'];
            $cnt = (int)$r['count'];
            
            if (!isset($stats[$st])) {
                $stats[$st] = [
                    'approved' => 0,
                    'pending' => 0,
                    'bc1' => 0,
                    'bc2' => 0,
                    'bc3' => 0,
                    'bc4' => 0,
                ];
            }
            
            if ($stStatus === 'approved') {
                $stats[$st]['approved'] += $cnt;
                $classKey = strtolower($class);
                if (isset($stats[$st][$classKey])) {
                    $stats[$st][$classKey] += $cnt;
                }
            } elseif ($stStatus === 'pending') {
                $stats[$st]['pending'] += $cnt;
            }
        }
        file_put_contents($cache_file, json_encode($stats, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        // Fallback to empty array on DB lock
        $stats = [];
    }
}

// Map SVG standard names (lookup keys) to schema values
function getHeatmapColor($count) {
    if ($count == 0) return '#e05a10'; // Capped Orange (0 athletes)
    if ($count >= 1 && $count <= 5) return '#6b82b8'; // Light Navy
    if ($count >= 6 && $count <= 15) return '#3b5a9a'; // Medium Navy
    if ($count >= 16 && $count <= 30) return '#16295a'; // Dark Navy
    return '#0b1b3d'; // 30+ Deep Navy
}

// Translate SVG name alias to standardized database key name
function standardizeStateKey($name) {
    $map = [
        'Orissa' => 'Odisha',
        'Uttaranchal' => 'Uttarakhand',
        'Andaman and Nicobar' => 'Andaman and Nicobar Islands',
        'Dādra and Nagar Haveli and Damān and Diu' => 'Dadra and Nagar Haveli and Daman and Diu'
    ];
    return isset($map[$name]) ? $map[$name] : $name;
}

// Load vector geometry paths directly from XML file safely without SimpleXML memory issues
$svg_raw = file_get_contents(__DIR__ . '/../india svg.svg');
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadXML($svg_raw);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
$paths = $xpath->query('//svg:path');
?>

<div class="map-card-wrapper">
    <div class="map-svg-container" style="position: relative;">
        <svg baseprofile="tiny" viewBox="0 0 1000 1000" width="100%" height="420" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" xmlns="http://www.w3.org/2000/svg">
            <g id="features">
                <?php
                foreach ($paths as $path) {
                    $id = $path->getAttribute('id');
                    $svgName = $path->getAttribute('name');
                    $d = $path->getAttribute('d');
                    
                    $dbStateName = standardizeStateKey($svgName);
                    $approvedCount = isset($stats[$dbStateName]) ? $stats[$dbStateName]['approved'] : 0;
                    $fillColor = getHeatmapColor($approvedCount);
                    
                    echo sprintf(
                        '<path d="%s" id="%s" name="%s" class="india-state-path" style="fill:%s; transition: fill 0.3s ease;" data-id="%s" data-name="%s" data-approved="%d" />' . "\n",
                        htmlspecialchars($d),
                        htmlspecialchars($id),
                        htmlspecialchars($svgName),
                        $fillColor,
                        htmlspecialchars($id),
                        htmlspecialchars($dbStateName),
                        $approvedCount
                    );
                }
                ?>
            </g>
        </svg>
    </div>
</div>
