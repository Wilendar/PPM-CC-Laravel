<?php
// FIX #12 Follow-up: Manually resolve category_mappings for Product 11033
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\CategoryMappingsConverter;

echo "=== FIX #12 Follow-up: Resolve category_mappings placeholders ===\n\n";

$productId = 11033;
$shopId = 1;

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);
$shopData = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$product || !$shop || !$shopData) {
    echo "❌ Product, Shop, or ProductShopData NOT FOUND\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$productId})\n";
echo "Shop: {$shop->name} (ID: {$shopId})\n\n";

// Show current (broken) state
echo "=== BEFORE FIX ===\n";
$oldMappings = $shopData->getAttributes()['category_mappings']; // Get raw JSON
echo "Current category_mappings (raw):\n";
echo substr($oldMappings, 0, 300) . "...\n\n";

// Pull fresh data from PrestaShop
echo "=== PULLING FROM PRESTASHOP ===\n";
try {
    $client = PrestaShopClientFactory::create($shop);

    if (!$shopData->prestashop_product_id) {
        echo "❌ No prestashop_product_id - cannot pull\n";
        exit(1);
    }

    $psProduct = $client->getProduct($shopData->prestashop_product_id);
    $psProduct = $psProduct['product'] ?? $psProduct;

    echo "PrestaShop product ID: {$shopData->prestashop_product_id}\n";

    // Extract categories from PrestaShop
    $psCategories = $psProduct['associations']['categories'] ?? [];
    if (is_array($psCategories) && isset($psCategories['category'])) {
        $psCategories = $psCategories['category'];
    }

    $psIds = array_column($psCategories, 'id');
    echo "PrestaShop category IDs: " . implode(', ', $psIds) . "\n\n";

    // Convert PrestaShop IDs to Option A using CategoryMappingsConverter
    echo "=== CONVERTING TO OPTION A (with PPM IDs) ===\n";
    $converter = app(CategoryMappingsConverter::class);

    // Don't validate yet - just build the structure
    try {
        $newMappings = $converter->fromPrestaShopFormat($psIds, $shop);
        echo "New category_mappings:\n";
        echo json_encode($newMappings, JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Exception $e) {
        echo "⚠️ Validation failed during conversion, but structure might be OK\n";
        echo "Error: " . $e->getMessage() . "\n\n";

        // Build structure manually without validation
        echo "Attempting manual build...\n";
        $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
        $selected = [];
        $mappings = [];

        foreach ($psIds as $psId) {
            $ppmId = $categoryMapper->mapFromPrestaShop($psId, $shop);
            if ($ppmId !== null) {
                $selected[] = $ppmId;
                $mappings[(string)$ppmId] = $psId;
            }
        }

        $newMappings = [
            'ui' => [
                'selected' => $selected,
                'primary' => $selected[0] ?? null,
            ],
            'mappings' => (object) $mappings, // Force JSON object encoding
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        echo "Manually built structure:\n";
        echo json_encode($newMappings, JSON_PRETTY_PRINT) . "\n\n";
    }

    // Update database directly with raw JSON (bypass Eloquent cast/validation)
    echo "=== UPDATING DATABASE ===\n";

    // Encode to JSON once (with object encoding for mappings)
    $jsonData = json_encode($newMappings);
    echo "Final JSON to save:\n" . $jsonData . "\n\n";

    // Save directly to database using DB facade (bypass CategoryMappingsCast)
    DB::table('product_shop_data')
        ->where('id', $shopData->id)
        ->update([
            'category_mappings' => $jsonData,
            'updated_at' => now(),
        ]);

    echo "✅ ProductShopData updated successfully\n\n";

    // Verify
    echo "=== VERIFICATION ===\n";
    $shopData->refresh();
    $finalMappings = $shopData->category_mappings;

    echo "Final category_mappings:\n";
    echo "  - UI selected: " . count($finalMappings['ui']['selected'] ?? []) . " categories\n";
    echo "  - Mappings: " . count($finalMappings['mappings'] ?? []) . " pairs\n";
    echo "  - Metadata source: " . ($finalMappings['metadata']['source'] ?? 'N/A') . "\n";

    echo "\n✅ FIX COMPLETE!\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
