<?php

// Test conflict detection logic step by step
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CategoryPreview;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopClientFactory;

echo "\n=== CONFLICT DETECTION TEST ===\n\n";

$previewId = 116; // Latest preview
$prestashopProductId = 4017;

echo "Testing preview #{$previewId} with product #{$prestashopProductId}\n\n";

// Load preview
$preview = CategoryPreview::with('shop')->find($previewId);

if (!$preview) {
    echo "❌ Preview not found\n";
    exit(1);
}

echo "✅ Preview loaded\n";
echo "   Shop ID: {$preview->shop_id}\n";
echo "   Shop Name: {$preview->shop->name}\n\n";

try {
    // Get PrestaShop API client
    $clientFactory = app(PrestaShopClientFactory::class);
    $client = $clientFactory->create($preview->shop);

    echo "Step 1: Fetching product from PrestaShop API...\n";
    $prestashopData = $client->getProduct($prestashopProductId);
    echo "✅ Product fetched\n\n";

    echo "Step 2: Unwrapping 'product' key...\n";
    if (isset($prestashopData['product']) && is_array($prestashopData['product'])) {
        $psProduct = $prestashopData['product'];
        echo "✅ Unwrapped 'product' key\n";
    } else {
        $psProduct = $prestashopData;
        echo "⚠️  No 'product' key to unwrap\n";
    }
    echo "   SKU: " . ($psProduct['reference'] ?? 'NULL') . "\n\n";

    echo "Step 3: Extract PrestaShop categories...\n";
    $associations = $psProduct['associations'] ?? [];
    $categories = $associations['categories'] ?? [];

    if (isset($categories['category'])) {
        $categories = $categories['category'];
    }

    $categoryIds = [];
    foreach ((array)$categories as $cat) {
        $categoryIds[] = is_array($cat) ? ($cat['id'] ?? 0) : $cat;
    }

    echo "   PS Categories: " . json_encode($categoryIds) . "\n\n";

    echo "Step 4: Map categories via ShopMapping...\n";
    $mappedIds = [];
    foreach ($categoryIds as $psCatId) {
        if ($psCatId <= 2) {
            echo "   - PS:{$psCatId} SKIPPED (root)\n";
            continue;
        }

        $mapping = \App\Models\ShopMapping::where('shop_id', $preview->shop_id)
            ->where('prestashop_id', $psCatId)
            ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->first();

        if ($mapping && $mapping->ppm_id) {
            $mappedIds[] = (int)$mapping->ppm_id;
            echo "   - PS:{$psCatId} → PPM:{$mapping->ppm_id} ✅\n";
        } else {
            echo "   - PS:{$psCatId} → NOT MAPPED ⚠️\n";
        }
    }
    echo "   Mapped PPM IDs: " . json_encode($mappedIds) . "\n\n";

    echo "Step 5: Search product in PPM by SKU...\n";
    $sku = $psProduct['reference'] ?? null;
    $product = $sku ? Product::where('sku', $sku)->first() : null;

    if ($product) {
        echo "✅ Product found by SKU\n";
        echo "   PPM ID: {$product->id}\n";
        echo "   Name: {$product->name}\n\n";

        echo "Step 6: Get PPM categories...\n";
        $defaultCategories = \DB::table('product_categories')
            ->where('product_id', $product->id)
            ->whereNull('shop_id')
            ->pluck('category_id')
            ->toArray();

        $shopCategories = \DB::table('product_categories')
            ->where('product_id', $product->id)
            ->where('shop_id', $preview->shop_id)
            ->pluck('category_id')
            ->toArray();

        echo "   Default categories (shop_id=NULL): " . json_encode($defaultCategories) . "\n";
        echo "   Shop categories (shop_id={$preview->shop_id}): " . json_encode($shopCategories) . "\n\n";

        echo "Step 7: Compare categories...\n";
        sort($mappedIds);
        sort($defaultCategories);
        sort($shopCategories);

        $defaultDiff = array_diff($mappedIds, $defaultCategories);
        $shopDiff = array_diff($mappedIds, $shopCategories);

        echo "   Mapped: " . json_encode($mappedIds) . "\n";
        echo "   Default: " . json_encode($defaultCategories) . "\n";
        echo "   Shop: " . json_encode($shopCategories) . "\n";
        echo "   Default diff: " . json_encode(array_values($defaultDiff)) . "\n";
        echo "   Shop diff: " . json_encode(array_values($shopDiff)) . "\n\n";

        if (!empty($defaultDiff) || !empty($shopDiff)) {
            echo "🚨 CONFLICT DETECTED!\n";
            echo "   Has default conflict: " . (!empty($defaultDiff) ? 'YES' : 'NO') . "\n";
            echo "   Has shop conflict: " . (!empty($shopDiff) ? 'YES' : 'NO') . "\n";
        } else {
            echo "✅ No conflicts (categories match)\n";
        }

    } else {
        echo "❌ Product NOT found by SKU\n";
        echo "   (This is a first import - no conflict expected)\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== END ===\n";
