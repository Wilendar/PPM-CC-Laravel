<?php

/**
 * Test Validation System
 *
 * Tests ValidationService with different scenarios:
 * 1. Name difference (warning)
 * 2. Price diff > 10% (error)
 * 3. Stock difference (info)
 * 4. No categories (warning)
 * 5. Inactive status (info)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\ValidationService;
use Illuminate\Support\Facades\Log;

echo "\n=== VALIDATION SYSTEM TEST ===\n";

// Find a test product with shop data
$shopData = ProductShopData::whereNotNull('prestashop_product_id')
    ->with(['product.prices', 'product.stocks'])
    ->first();

if (!$shopData) {
    echo "ERROR: No product_shop_data found with prestashop_product_id\n";
    exit(1);
}

echo "\nTest Product:\n";
echo "  Product ID: {$shopData->product_id}\n";
echo "  SKU: {$shopData->product->sku}\n";
echo "  Shop ID: {$shopData->shop_id}\n";
echo "  PrestaShop Product ID: {$shopData->prestashop_product_id}\n";

$validator = new ValidationService();

// TEST 1: Name difference (warning)
echo "\n--- TEST 1: Name Difference (warning) ---\n";
$psDataNameDiff = [
    'name' => 'Different Product Name', // Changed
    'description_short' => $shopData->short_description,
    'description' => $shopData->description,
    'price' => $shopData->product->prices->first()?->price ?? 100,
    'quantity' => $shopData->product->stocks->sum('quantity'),
    'associations' => [
        'categories' => [
            ['id' => 1],
            ['id' => 2],
        ],
    ],
    'active' => true,
];

$warnings1 = $validator->validateProductData($shopData, $psDataNameDiff);
echo "Warnings: " . count($warnings1) . "\n";
foreach ($warnings1 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// TEST 2: Price diff > 10% (error)
echo "\n--- TEST 2: Price Difference > 10% (error) ---\n";
$ppmPrice = $shopData->product->prices->where('price_group_id', 1)->first()?->price ?? 100;
$psPriceHighDiff = $ppmPrice * 1.20; // 20% difference

$psDataPriceDiff = [
    'name' => $shopData->name,
    'description_short' => $shopData->short_description,
    'description' => $shopData->description,
    'price' => $psPriceHighDiff,
    'quantity' => $shopData->product->stocks->sum('quantity'),
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => true,
];

$warnings2 = $validator->validateProductData($shopData, $psDataPriceDiff);
echo "Warnings: " . count($warnings2) . "\n";
foreach ($warnings2 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// TEST 3: Stock difference (info)
echo "\n--- TEST 3: Stock Difference (info) ---\n";
$ppmStock = $shopData->product->stocks->sum('quantity');
$psStockDiff = $ppmStock + 20; // 20 units difference

$psDataStockDiff = [
    'name' => $shopData->name,
    'description_short' => $shopData->short_description,
    'description' => $shopData->description,
    'price' => $ppmPrice,
    'quantity' => $psStockDiff,
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => true,
];

$warnings3 = $validator->validateProductData($shopData, $psDataStockDiff);
echo "Warnings: " . count($warnings3) . "\n";
foreach ($warnings3 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// TEST 4: No categories (warning)
echo "\n--- TEST 4: No Categories (warning) ---\n";
$psDataNoCategories = [
    'name' => $shopData->name,
    'description_short' => $shopData->short_description,
    'description' => $shopData->description,
    'price' => $ppmPrice,
    'quantity' => $ppmStock,
    'associations' => [
        'categories' => [], // No categories
    ],
    'active' => true,
];

$warnings4 = $validator->validateProductData($shopData, $psDataNoCategories);
echo "Warnings: " . count($warnings4) . "\n";
foreach ($warnings4 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// TEST 5: Inactive status (info)
echo "\n--- TEST 5: Inactive Status (info) ---\n";
$psDataInactive = [
    'name' => $shopData->name,
    'description_short' => $shopData->short_description,
    'description' => $shopData->description,
    'price' => $ppmPrice,
    'quantity' => $ppmStock,
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => false, // Inactive
];

$warnings5 = $validator->validateProductData($shopData, $psDataInactive);
echo "Warnings: " . count($warnings5) . "\n";
foreach ($warnings5 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// TEST 6: Store validation warnings
echo "\n--- TEST 6: Store Validation Warnings ---\n";
$validator->storeValidationWarnings($shopData, $warnings2);

echo "Stored warnings to database\n";
echo "  has_validation_warnings: " . ($shopData->has_validation_warnings ? 'true' : 'false') . "\n";
echo "  validation_checked_at: {$shopData->validation_checked_at}\n";
echo "  warnings_count: " . count($shopData->validation_warnings ?? []) . "\n";

// TEST 7: Verify database storage
echo "\n--- TEST 7: Verify Database Storage ---\n";
$shopDataReloaded = ProductShopData::find($shopData->id);
echo "Reloaded from database:\n";
echo "  has_validation_warnings: " . ($shopDataReloaded->has_validation_warnings ? 'true' : 'false') . "\n";
echo "  validation_checked_at: {$shopDataReloaded->validation_checked_at}\n";
echo "  warnings_count: " . count($shopDataReloaded->validation_warnings ?? []) . "\n";

if ($shopDataReloaded->validation_warnings) {
    echo "\nStored warnings:\n";
    foreach ($shopDataReloaded->validation_warnings as $warning) {
        echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    }
}

echo "\n=== TEST COMPLETED SUCCESSFULLY ===\n";
