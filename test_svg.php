<?php
$svgFile = __DIR__ . '/india svg.svg';
echo "File exists: " . (file_exists($svgFile) ? 'yes' : 'no') . "\n";

$xml = simplexml_load_file($svgFile);
if ($xml === false) {
    echo "Failed to load XML\n";
    foreach(libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
    exit;
}

echo "Root element: " . $xml->getName() . "\n";

// SimpleXML can access elements directly if they have a default namespace, 
// but sometimes we need to register namespace and use xpath, or use children().
// Let's test standard children access:
if (isset($xml->g)) {
    echo "Found g directly\n";
    echo "g children count: " . count($xml->g->children()) . "\n";
} else {
    echo "g not found directly. Let's try children()...\n";
    $ns_children = $xml->children();
    echo "ns children count: " . count($ns_children) . "\n";
    foreach ($ns_children as $child) {
        echo "child name: " . $child->getName() . "\n";
        if ($child->getName() == 'g') {
            echo "Found g in children, paths count: " . count($child->path) . "\n";
        }
    }
}

// Let's test with xpath
$xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
$paths = $xml->xpath('//svg:path');
echo "XPath paths count: " . count($paths) . "\n";
if (count($paths) > 0) {
    $first = $paths[0];
    echo "First path: id=" . $first['id'] . ", name=" . $first['name'] . "\n";
}
?>
