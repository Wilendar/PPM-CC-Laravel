<?php

/**
 * Check what blocks are saved in database vs PrestaShop
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductDescription;
use App\Models\Product;
use App\Models\PrestaShopShop;

$product = Product::where('sku', 'BG-KAYO-S200')->first();
$shopId = 5;

echo "=== PRODUCT ===\n";
echo "ID: {$product->id}, SKU: {$product->sku}\n\n";

// Check ProductDescription table
$desc = ProductDescription::where('product_id', $product->id)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    echo "NO ProductDescription record found!\n";
    echo "This means editor has not saved yet - it loads from product's long_description\n\n";

    // Check product's long_description
    $longDesc = $product->long_description ?? '';
    echo "Product long_description length: " . strlen($longDesc) . "\n";

    // Check shop-specific data
    $shopData = $product->dataForShop($shopId)->first();
    if ($shopData) {
        echo "Shop-specific long_description length: " . strlen($shopData->long_description ?? '') . "\n";
    }
} else {
    echo "=== ProductDescription RECORD ===\n";
    echo "ID: {$desc->id}\n";
    echo "Updated at: {$desc->updated_at}\n";

    $blocks = $desc->blocks_json ?? [];
    echo "Blocks count: " . count($blocks) . "\n\n";

    echo "=== SAVED BLOCKS ===\n";
    foreach ($blocks as $idx => $block) {
        $type = $block['type'] ?? 'unknown';

        echo "[{$idx}] {$type}";

        if ($type === 'raw-html') {
            $html = $block['data']['content']['html'] ?? '';
            echo " - " . strlen($html) . " chars";

            // Show snippet
            $text = strip_tags($html);
            $text = preg_replace('/\s+/', ' ', $text);
            echo " - \"" . substr(trim($text), 0, 60) . "...\"";
        }

        echo "\n";
    }

    echo "\n=== COMPILED HTML LENGTH ===\n";
    echo strlen($desc->compiled_html ?? '') . " chars\n";
}

// Compare with fresh import from PrestaShop
echo "\n=== FRESH IMPORT FROM PRESTASHOP ===\n";

$shop = PrestaShopShop::find($shopId);
$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
$psProductId = $product->getPrestashopProductId($shop);
$psProduct = $client->getProduct($psProductId);
$psProduct = $psProduct['product'] ?? $psProduct;
$freshDescription = $psProduct['description'] ?? '';

echo "PrestaShop description length: " . strlen($freshDescription) . " chars\n";

$parser = new \App\Services\VisualEditor\HtmlToBlocksParser();
$freshBlocks = $parser->parse($freshDescription);
echo "Fresh parsed blocks: " . count($freshBlocks) . "\n\n";

echo "=== FRESH BLOCKS ===\n";
foreach ($freshBlocks as $idx => $block) {
    $type = $block['type'] ?? 'unknown';
    echo "[{$idx}] {$type}";

    if ($type === 'raw-html') {
        $html = $block['data']['content']['html'] ?? '';
        echo " - " . strlen($html) . " chars";
    }
    echo "\n";
}
