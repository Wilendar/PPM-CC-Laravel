<?php
/**
 * Debug HTML structure to understand why CSS classes are not being applied
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;

$productId = $argv[1] ?? 11183;
$shopId = $argv[2] ?? 5;

$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

echo "=== CSS CLASS MAP ===\n";
$cssClassMap = $desc->css_class_map ?? [];
print_r($cssClassMap);

echo "\n=== BLOCKS JSON (first 3000 chars) ===\n";
$blocksJson = $desc->blocks_json ?? [];
echo substr(json_encode($blocksJson, JSON_PRETTY_PRINT), 0, 3000) . "\n";

echo "\n=== SEARCHING FOR data-uve-id IN BLOCKS JSON ===\n";
$jsonStr = json_encode($blocksJson);
if (preg_match_all('/data-uve-id=\\\\?"([^"\\\\]+)\\\\?"/', $jsonStr, $matches)) {
    echo "Found data-uve-id values:\n";
    foreach (array_unique($matches[1]) as $id) {
        echo "  - {$id}\n";
    }
} else {
    echo "NO data-uve-id attributes found in blocks_json!\n";
}

// Check blocks_v2 as well
echo "\n=== BLOCKS V2 ===\n";
$blocksV2 = $desc->blocks_v2 ?? [];
if (!empty($blocksV2)) {
    echo "blocks_v2 has " . count($blocksV2) . " blocks\n";
    $v2Str = json_encode($blocksV2);
    if (preg_match_all('/data-uve-id[=:]\\\\?"([^"\\\\]+)/', $v2Str, $matches)) {
        echo "Found data-uve-id values in blocks_v2:\n";
        foreach (array_unique($matches[1]) as $id) {
            echo "  - {$id}\n";
        }
    }
} else {
    echo "blocks_v2 is empty\n";
}

// Check rendered HTML directly
echo "\n=== RENDERED HTML (search for data-uve-id) ===\n";
$html = $desc->rendered_html ?? '';
echo "HTML length: " . strlen($html) . "\n";

if (preg_match_all('/data-uve-id="([^"]+)"/', $html, $matches)) {
    echo "Found data-uve-id in rendered HTML:\n";
    foreach (array_unique($matches[1]) as $id) {
        echo "  - {$id}\n";
        // Show the element
        if (preg_match('/<[^>]*data-uve-id="' . preg_quote($id) . '"[^>]*>/', $html, $elem)) {
            echo "    Element: " . substr($elem[0], 0, 200) . "\n";
        }
    }
} else {
    echo "NO data-uve-id found in rendered HTML!\n";
}

// Check UVE blocks format
echo "\n=== UVE BLOCKS STRUCTURE ===\n";
$blocks = $desc->blocks ?? [];
foreach ($blocks as $idx => $block) {
    echo "Block {$idx}:\n";
    echo "  type: " . ($block['type'] ?? 'N/A') . "\n";
    echo "  has document: " . (isset($block['document']) ? 'yes' : 'no') . "\n";
    echo "  has compiled_html: " . (isset($block['compiled_html']) ? 'yes (' . strlen($block['compiled_html'] ?? '') . ' chars)' : 'no') . "\n";

    // Check compiled_html for data-uve-id
    if (!empty($block['compiled_html'])) {
        $compiledHtml = $block['compiled_html'];
        if (preg_match_all('/data-uve-id="([^"]+)"/', $compiledHtml, $m)) {
            echo "  data-uve-id in compiled_html:\n";
            foreach (array_unique($m[1]) as $id) {
                echo "    - {$id}\n";
            }
        }
    }
}
