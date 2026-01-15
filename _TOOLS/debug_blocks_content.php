<?php

/**
 * Debug blocks content in detail
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\VisualEditor\HtmlToBlocksParser;

$shop = PrestaShopShop::find(5);
$product = Product::where('sku', 'BG-KAYO-S200')->first();
$psProductId = $product->getPrestashopProductId($shop);

$client = new PrestaShop8Client($shop);
$psProductRaw = $client->getProduct($psProductId);
$psProduct = $psProductRaw['product'] ?? $psProductRaw;

$description = $psProduct['description'] ?? '';

echo "=== ORIGINAL DESCRIPTION STRUCTURE ===\n";
echo "Total length: " . strlen($description) . " chars\n\n";

// Find all top-level divs
preg_match_all('/<div class="([^"]+)"/', $description, $matches);
echo "Top-level div classes found:\n";
foreach ($matches[1] as $class) {
    echo "  - {$class}\n";
}

echo "\n=== PARSING ===\n";
$parser = new HtmlToBlocksParser();
$blocks = $parser->parse($description);

echo "Blocks count: " . count($blocks) . "\n\n";

// Show each block content
foreach ($blocks as $idx => $block) {
    echo "=== BLOCK [{$idx}] TYPE: {$block['type']} ===\n";

    $content = $block['data']['content'] ?? [];

    if ($block['type'] === 'raw-html') {
        $html = $content['html'] ?? '';
        echo "HTML length: " . strlen($html) . " chars\n";
        echo "Preview: " . substr(strip_tags($html), 0, 200) . "...\n";

        // Check what's inside
        if (preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s', $html, $headings)) {
            echo "Headings: " . implode(' | ', array_map('strip_tags', $headings[0])) . "\n";
        }
    } elseif ($block['type'] === 'grid-section' || $block['type'] === 'full-width') {
        $columns = $content['columns'] ?? [];
        if (is_array($columns)) {
            echo "Columns: " . count($columns) . "\n";
            foreach ($columns as $colIdx => $col) {
                $colHtml = is_array($col) ? ($col['html'] ?? '') : $col;
                echo "  Column [{$colIdx}]: " . strlen($colHtml) . " chars\n";
            }
        } else {
            echo "Content: " . json_encode(array_keys($content)) . "\n";
        }
    } else {
        echo "Content keys: " . implode(', ', array_keys($content)) . "\n";
    }

    echo "\n";
}

echo "=== CHECKING FOR MISSING CONTENT ===\n";

// Key sections that should be in the description
$expectedSections = [
    'pd-intro' => 'KAYO S200 intro',
    'pd-cover' => 'Cover image',
    'pd-asset-list' => 'Asset list (200cm3, R-N-F, etc)',
    'pd-pseudo-parallax' => 'Parallax image',
    'pd-slider' => 'Slider/Twarde fakty',
    'pd-benefit' => 'Benefits section',
    'pd-specs' => 'Specifications table',
    'pd-cta' => 'Call to action',
];

$blocksHtml = '';
foreach ($blocks as $block) {
    if (isset($block['data']['content']['html'])) {
        $blocksHtml .= $block['data']['content']['html'];
    }
    if (isset($block['data']['content']['columns'])) {
        foreach ($block['data']['content']['columns'] as $col) {
            $blocksHtml .= $col['html'] ?? '';
        }
    }
}

echo "Total parsed HTML length: " . strlen($blocksHtml) . " chars\n";
echo "Original description length: " . strlen($description) . " chars\n";
echo "Difference: " . (strlen($description) - strlen($blocksHtml)) . " chars\n\n";

foreach ($expectedSections as $class => $name) {
    $inOriginal = str_contains($description, $class);
    $inBlocks = str_contains($blocksHtml, $class);

    $status = $inOriginal && $inBlocks ? '✅' : ($inOriginal ? '❌ MISSING' : '⚠️ not in original');
    echo "{$status} {$name} ({$class})\n";
}
