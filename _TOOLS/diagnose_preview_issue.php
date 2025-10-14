<?php

// Diagnose CategoryPreview conflict detection issue
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CategoryPreview;
use App\Models\Product;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "\n=== CATEGORYPREVIEW DIAGNOSTIC ===\n\n";

// Get latest preview
$preview = CategoryPreview::latest()->first();

if (!$preview) {
    echo "❌ No CategoryPreview found in database\n";
    exit(1);
}

echo "📋 Latest CategoryPreview:\n";
echo "   ID: {$preview->id}\n";
echo "   Shop ID: {$preview->shop_id}\n";
echo "   Status: {$preview->status}\n";
echo "   Created: {$preview->created_at}\n";
echo "   Expires: {$preview->expires_at}\n";
echo "   Shown count: {$preview->shown_count}\n\n";

echo "📦 Import Context:\n";
$importContext = $preview->import_context_json ?? [];
echo json_encode($importContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Get product IDs from context
$mode = $importContext['mode'] ?? null;
$options = $importContext['options'] ?? [];
$productIds = $options['product_ids'] ?? [];

if (empty($productIds)) {
    echo "⚠️  No product_ids in import context\n";
    exit(0);
}

echo "🔍 Products to check: " . implode(', ', $productIds) . "\n\n";

// Check each product
foreach ($productIds as $prestashopProductId) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Checking Product #{$prestashopProductId}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    try {
        // Fetch from PrestaShop API
        $clientFactory = app(PrestaShopClientFactory::class);
        $client = $clientFactory->create($preview->shop);

        echo "1️⃣ Fetching from PrestaShop API...\n";
        $prestashopData = $client->getProduct($prestashopProductId);

        // Unwrap product key
        if (isset($prestashopData['product']) && is_array($prestashopData['product'])) {
            $psProduct = $prestashopData['product'];
            echo "   ✅ Unwrapped 'product' key\n";
        } else {
            $psProduct = $prestashopData;
            echo "   ⚠️  No 'product' key to unwrap\n";
        }

        $sku = $psProduct['reference'] ?? null;
        echo "   SKU (reference): " . ($sku ?: 'NULL') . "\n\n";

        // Check categories
        $categories = data_get($psProduct, 'associations.categories', []);
        if (isset($categories['category'])) {
            $categories = $categories['category'];
        }

        $categoryIds = [];
        foreach ((array)$categories as $cat) {
            $categoryIds[] = is_array($cat) ? ($cat['id'] ?? 0) : $cat;
        }

        echo "   PrestaShop categories: " . json_encode($categoryIds) . "\n\n";

        // Search by SKU (PRIMARY)
        echo "2️⃣ Searching in PPM by SKU...\n";
        if ($sku) {
            $product = Product::where('sku', $sku)->first();

            if ($product) {
                echo "   ✅ Product found by SKU!\n";
                echo "   PPM Product ID: {$product->id}\n";
                echo "   Name: {$product->name}\n\n";

                // Check ProductShopData
                echo "3️⃣ Checking ProductShopData...\n";
                $shopData = ProductShopData::where('product_id', $product->id)->get();

                if ($shopData->count() > 0) {
                    foreach ($shopData as $sd) {
                        echo "   - Shop ID: {$sd->shop_id} | PrestaShop ID: {$sd->prestashop_product_id}\n";
                    }

                    $existingShopId = $shopData->first()->shop_id;
                    echo "\n   Scenario: " . ($existingShopId === $preview->shop_id ? 'SAME_SHOP_REIMPORT' : 'CROSS_SHOP_IMPORT') . "\n\n";
                } else {
                    echo "   ⚠️  No ProductShopData (MANUAL_PRODUCT_IMPORT)\n\n";
                }

                // Check categories in PPM
                echo "4️⃣ Checking PPM categories...\n";
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

                // Check ShopMapping for PrestaShop categories
                echo "5️⃣ Mapping PrestaShop categories to PPM...\n";
                $mappedPpmIds = [];
                foreach ($categoryIds as $psCatId) {
                    if ($psCatId <= 2) continue; // Skip root

                    $mapping = \App\Models\ShopMapping::where('shop_id', $preview->shop_id)
                        ->where('prestashop_id', $psCatId)
                        ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
                        ->where('is_active', true)
                        ->first();

                    if ($mapping) {
                        $mappedPpmIds[] = (int)$mapping->ppm_id;
                        echo "   PS:{$psCatId} → PPM:{$mapping->ppm_id} ✅\n";
                    } else {
                        echo "   PS:{$psCatId} → Not mapped ⚠️\n";
                    }
                }

                echo "\n   Mapped PPM categories: " . json_encode($mappedPpmIds) . "\n\n";

                // Compare
                echo "6️⃣ Conflict detection...\n";
                sort($mappedPpmIds);
                sort($defaultCategories);
                sort($shopCategories);

                $defaultDiff = array_diff($mappedPpmIds, $defaultCategories);
                $shopDiff = array_diff($mappedPpmIds, $shopCategories);

                if (!empty($defaultDiff) || !empty($shopDiff)) {
                    echo "   🚨 CONFLICT DETECTED!\n";
                    echo "   Default diff: " . json_encode(array_values($defaultDiff)) . "\n";
                    echo "   Shop diff: " . json_encode(array_values($shopDiff)) . "\n";
                } else {
                    echo "   ✅ No conflicts (categories match)\n";
                }

            } else {
                echo "   ❌ Product NOT found by SKU\n";
                echo "   (This would be a first import - no conflict)\n";
            }
        } else {
            echo "   ❌ No SKU in PrestaShop product\n";
            echo "   Falling back to prestashop_product_id search...\n\n";

            // FALLBACK: Search by prestashop_product_id
            $anyShopData = ProductShopData::where('prestashop_product_id', $prestashopProductId)->first();

            if ($anyShopData) {
                echo "   ✅ Found via ProductShopData\n";
                echo "   PPM Product ID: {$anyShopData->product_id}\n";
                $product = Product::find($anyShopData->product_id);
                echo "   Name: {$product->name}\n";
            } else {
                echo "   ❌ Not found (first import)\n";
            }
        }

    } catch (\Exception $e) {
        echo "   ❌ ERROR: {$e->getMessage()}\n";
        echo "   Trace: {$e->getTraceAsString()}\n";
    }

    echo "\n";
}

echo "\n=== CODE VERIFICATION ===\n\n";

// Check if CategoryPreviewModal.php has the new SKU-based code
$modalFile = __DIR__ . '/../app/Http/Livewire/Components/CategoryPreviewModal.php';
$modalContent = file_get_contents($modalFile);

echo "7️⃣ Checking CategoryPreviewModal.php code...\n";
if (strpos($modalContent, 'SKU-based PRIMARY lookup') !== false) {
    echo "   ✅ Has SKU-based lookup code\n";
} else {
    echo "   ❌ Missing SKU-based lookup code (OLD VERSION!)\n";
}

if (strpos($modalContent, 'extractAndMapCategories') !== false) {
    echo "   ✅ Has extractAndMapCategories() method\n";
} else {
    echo "   ❌ Missing extractAndMapCategories() method\n";
}

if (strpos($modalContent, 'METODA 1 (PRIMARY): Search by SKU') !== false) {
    echo "   ✅ Has PRIMARY SKU search logic\n";
} else {
    echo "   ❌ Missing PRIMARY SKU search logic\n";
}

echo "\n=== END ===\n";
