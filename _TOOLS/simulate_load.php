<?php

/**
 * Simulate loadExistingDescription() logic step by step
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductDescription;
use App\Services\VisualEditor\HtmlToBlocksParser;

$productId = 11183;
$shopId = 5;

echo "=== SIMULATING loadExistingDescription() ===\n\n";

// Step 1: Try to load from ProductDescription
echo "[STEP 1] Loading ProductDescription...\n";
$description = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

echo "  Found: " . ($description ? "YES (ID: {$description->id})" : "NO") . "\n";

if ($description) {
    echo "  blocks_json type: " . gettype($description->blocks_json) . "\n";
    echo "  blocks_json count: " . count($description->blocks_json ?? []) . "\n";
    echo "  empty(blocks_json): " . (empty($description->blocks_json) ? "TRUE" : "FALSE") . "\n";

    // The critical condition check
    $condition = $description && !empty($description->blocks_json);
    echo "\n  CONDITION (\$description && !empty(\$description->blocks_json)): " . ($condition ? "TRUE" : "FALSE") . "\n";

    if ($condition) {
        echo "\n[RESULT] Would load from ProductDescription - blocks already exist\n";
        echo "  This is the PROBLEM - we should NOT be here with empty blocks!\n";
        exit(0);
    }
}

echo "\n[STEP 2] Fallback to loadFromProductDescription()...\n";

$product = Product::find($productId);
if (!$product) {
    echo "  ERROR: Product not found!\n";
    exit(1);
}

echo "  Product found: {$product->sku}\n";

// Step 2a: Get shop-specific description
echo "\n[STEP 2a] Looking for shop-specific description...\n";
$shopData = $product->dataForShop($shopId)->first();
if ($shopData) {
    echo "  ProductShopData found (ID: {$shopData->id})\n";
    $shopLongDesc = $shopData->long_description ?? '';
    echo "  long_description length: " . strlen($shopLongDesc) . " chars\n";
} else {
    echo "  No ProductShopData found\n";
}

// Step 2b: Get product's default description
echo "\n[STEP 2b] Product's default long_description...\n";
$productLongDesc = $product->long_description ?? '';
echo "  Length: " . strlen($productLongDesc) . " chars\n";

// Step 2c: Determine final description to parse
$htmlDescription = ($shopData && !empty($shopData->long_description))
    ? $shopData->long_description
    : $product->long_description;

echo "\n[STEP 2c] Final HTML description to parse:\n";
echo "  Length: " . strlen($htmlDescription ?? '') . " chars\n";

if (empty($htmlDescription)) {
    echo "\n[RESULT] No description to import - EMPTY!\n";
    exit(0);
}

// Step 3: Parse HTML to blocks
echo "\n[STEP 3] Parsing HTML with HtmlToBlocksParser...\n";
$parser = new HtmlToBlocksParser();
$importedBlocks = $parser->parse($htmlDescription);

echo "  Blocks count: " . count($importedBlocks) . "\n";

if (!empty($importedBlocks)) {
    echo "\n[SUCCESS] Would import " . count($importedBlocks) . " blocks!\n";
    echo "\nBlock types:\n";
    foreach ($importedBlocks as $idx => $block) {
        $type = $block['type'] ?? 'unknown';
        echo "  [{$idx}] {$type}\n";
    }
} else {
    echo "\n[RESULT] Parser returned 0 blocks - something wrong with parsing?\n";
}
