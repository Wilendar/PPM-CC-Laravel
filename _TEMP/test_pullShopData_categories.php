<?php
// Manually test pullShopData() logic to see if category_mappings is saved
// This simulates what happens in ProductForm::pullShopData()

use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductShopData;

$productId = 11033;
$shopId = 1;

echo "=== TESTING pullShopData() CATEGORY LOGIC ===\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    echo "Product or Shop NOT FOUND\n";
    exit(1);
}

echo "Product: {$product->name} (ID: {$product->id}, SKU: {$product->sku})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

try {
    // Create API client
    $client = PrestaShopClientFactory::create($shop);

    // Get ProductShopData
    $productShopData = ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shopId)
        ->first();

    echo "ProductShopData BEFORE:\n";
    echo "  - ID: " . ($productShopData->id ?? 'NULL') . "\n";
    echo "  - category_mappings: " . json_encode($productShopData->category_mappings ?? null) . "\n\n";

    // Fetch from PrestaShop
    echo "Fetching from PrestaShop API...\n";
    $prestashopData = $client->getProduct($productShopData->prestashop_product_id);

    // Unwrap nested response
    if (isset($prestashopData['product'])) {
        $prestashopData = $prestashopData['product'];
    }

    // Extract essential data (FIX #10.2 logic)
    $productData = [
        'id' => $prestashopData['id'] ?? null,
        'name' => data_get($prestashopData, 'name.0.value') ?? data_get($prestashopData, 'name'),
        'description_short' => data_get($prestashopData, 'description_short.0.value') ?? data_get($prestashopData, 'description_short'),
        'description' => data_get($prestashopData, 'description.0.value') ?? data_get($prestashopData, 'description'),
        'price' => $prestashopData['price'] ?? null,
        'active' => $prestashopData['active'] ?? null,
        // FIX 2025-11-18 (#10.2): Extract categories from PrestaShop API response
        'categories' => data_get($prestashopData, 'associations.categories') ?? [],
    ];

    echo "Extracted productData:\n";
    echo "  - id: " . ($productData['id'] ?? 'NULL') . "\n";
    echo "  - categories: " . json_encode($productData['categories']) . "\n";
    echo "  - categories empty: " . (empty($productData['categories']) ? 'YES' : 'NO') . "\n\n";

    // Map PrestaShop categories to category_mappings JSON (FIX #10.2 logic)
    $categoryMappings = [];
    if (!empty($productData['categories'])) {
        foreach ($productData['categories'] as $categoryAssoc) {
            $prestashopCategoryId = $categoryAssoc['id'] ?? null;
            if ($prestashopCategoryId) {
                // Store as string key (JSON standard) → int value
                $categoryMappings[(string) $prestashopCategoryId] = (int) $prestashopCategoryId;
            }
        }

        echo "Built category_mappings:\n";
        echo "  - " . json_encode($categoryMappings) . "\n\n";
    }

    // Update ProductShopData (FIX #10.2 logic)
    $productShopData->fill([
        'prestashop_product_id' => $productData['id'],
        'name' => $productData['name'] ?? $productShopData->name,
        'short_description' => $productData['description_short'] ?? $productShopData->short_description,
        'long_description' => $productData['description'] ?? $productShopData->long_description,
        'sync_status' => 'synced',
        'last_success_sync_at' => now(),
        'last_pulled_at' => now(),
        // FIX 2025-11-18 (#10.2): Save category_mappings (only update if categories were returned)
        'category_mappings' => !empty($categoryMappings) ? $categoryMappings : $productShopData->category_mappings,
    ]);

    echo "Calling save()...\n";
    $saveResult = $productShopData->save();
    echo "Save result: " . ($saveResult ? 'SUCCESS' : 'FAILED') . "\n\n";

    // Reload from database to verify
    $productShopData->refresh();

    echo "ProductShopData AFTER:\n";
    echo "  - ID: " . ($productShopData->id ?? 'NULL') . "\n";
    echo "  - category_mappings: " . json_encode($productShopData->category_mappings) . "\n";
    echo "  - category_mappings type: " . gettype($productShopData->category_mappings) . "\n";
    echo "  - category_mappings count: " . (is_array($productShopData->category_mappings) ? count($productShopData->category_mappings) : 0) . "\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
