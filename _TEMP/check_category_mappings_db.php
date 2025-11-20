<?php
// Check ProductShopData.category_mappings in database
// Product ID: 11033
// Shop IDs: 1 (B2B Test DEV), 5 (Test KAYO)

$productId = 11033;
$shopIds = [1, 5];

echo "=== CHECKING PRODUCT_SHOP_DATA.CATEGORY_MAPPINGS ===\n\n";

foreach ($shopIds as $shopId) {
    $shopData = \App\Models\ProductShopData::where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData) {
        echo "Shop ID {$shopId}: NO ProductShopData record\n";
        continue;
    }

    echo "Shop ID {$shopId}:\n";
    echo "  - ProductShopData ID: {$shopData->id}\n";
    echo "  - category_mappings: " . json_encode($shopData->category_mappings) . "\n";
    echo "  - category_mappings type: " . gettype($shopData->category_mappings) . "\n";
    echo "  - category_mappings count: " . (is_array($shopData->category_mappings) ? count($shopData->category_mappings) : 0) . "\n";
    echo "  - prestashop_product_id: " . ($shopData->prestashop_product_id ?? 'NULL') . "\n";
    echo "  - sync_status: " . ($shopData->sync_status ?? 'NULL') . "\n\n";
}

// Check product global categories
$product = \App\Models\Product::find($productId);
if ($product) {
    echo "=== PRODUCT GLOBAL CATEGORIES ===\n";
    echo "Product ID {$productId}:\n";
    $categories = $product->categories;
    echo "  - Categories count: " . $categories->count() . "\n";
    foreach ($categories as $category) {
        echo "  - Category ID: {$category->id}, Name: {$category->name}\n";
    }
}
