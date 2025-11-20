<?php

/**
 * MANUAL TESTING SCRIPT FOR PRESTASHOP COMBINATIONS API
 *
 * This script is NOT part of automated tests. Run manually to test with real PrestaShop API.
 *
 * SETUP:
 * 1. Configure PrestaShop shop in database (prestashop_shops table)
 * 2. Set shop ID in $shopId variable below
 * 3. Set test product ID in $testProductId variable
 * 4. Run: php artisan tinker < tests/Manual/PrestaShopCombinationsManualTest.php
 *
 * REQUIREMENTS:
 * - Active PrestaShop shop with API key
 * - Product with combinations (or test creating one)
 * - Product attributes configured (Color, Size, etc.)
 *
 * IMPORTANT: This will create/modify/delete real data in PrestaShop!
 * Use only on development/staging environment.
 */

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

echo "\n========================================\n";
echo "PRESTASHOP COMBINATIONS API MANUAL TEST\n";
echo "========================================\n\n";

// CONFIGURATION - CHANGE THESE VALUES
$shopId = 1; // Your test shop ID
$testProductId = null; // Set existing product ID or leave null to create new

// Load shop
$shop = PrestaShopShop::find($shopId);

if (!$shop) {
    echo "ERROR: Shop with ID {$shopId} not found\n";
    exit(1);
}

echo "Testing with shop: {$shop->name}\n";
echo "Shop URL: {$shop->url}\n";
echo "Shop Version: {$shop->version}\n\n";

// Create client
$client = PrestaShopClientFactory::create($shop);

echo "Client version: " . $client->getVersion() . "\n\n";

// ============================================
// TEST 1: Get combinations for existing product
// ============================================
echo "TEST 1: Get combinations for existing product\n";
echo "----------------------------------------------\n";

if ($testProductId) {
    try {
        $combinations = $client->getProductCombinations($testProductId);

        echo "Product ID: {$testProductId}\n";
        echo "Combinations found: " . count($combinations) . "\n\n";

        foreach ($combinations as $combo) {
            echo "  Combination ID: {$combo['id']}\n";
            echo "  Reference: {$combo['reference']}\n";
            echo "  EAN13: {$combo['ean13']}\n";
            echo "  Quantity: {$combo['quantity']}\n";
            echo "  Price differential: {$combo['price']}\n";
            echo "  Attributes: " . implode(', ', $combo['attributes']) . "\n";
            echo "  Images: " . implode(', ', $combo['images']) . "\n";
            echo "  ---\n";
        }

        echo "✅ TEST 1 PASSED\n\n";
    } catch (\Exception $e) {
        echo "❌ TEST 1 FAILED: {$e->getMessage()}\n\n";
    }
} else {
    echo "⚠️  TEST 1 SKIPPED: No product ID provided\n\n";
}

// ============================================
// TEST 2: Create product with combinations
// ============================================
echo "TEST 2: Create product with combinations\n";
echo "----------------------------------------\n";

try {
    // NOTE: You need to provide valid attribute IDs from your PrestaShop
    // Get these by calling: $client->getAttributeValues()

    $productData = [
        'name' => [
            'language' => [
                ['id' => 1, 'value' => 'Test Product With Variants - ' . date('Y-m-d H:i:s')]
            ]
        ],
        'description' => [
            'language' => [
                ['id' => 1, 'value' => 'This is a test product created by API with combinations']
            ]
        ],
        'price' => 100.00,
        'id_category_default' => 2, // Change to valid category ID
        'active' => 1,
    ];

    $combinations = [
        [
            'reference' => 'TEST-RED-S-' . time(),
            'ean13' => '1234567890123',
            'quantity' => 10,
            'price' => 0, // No price differential
            'attribute_ids' => [1, 5], // CHANGE: Use valid attribute IDs (e.g., Color: Red, Size: S)
        ],
        [
            'reference' => 'TEST-RED-M-' . time(),
            'ean13' => '1234567890124',
            'quantity' => 15,
            'price' => 5.00, // +5 EUR differential
            'attribute_ids' => [1, 6], // CHANGE: Use valid attribute IDs (e.g., Color: Red, Size: M)
        ],
        [
            'reference' => 'TEST-BLUE-S-' . time(),
            'ean13' => '1234567890125',
            'quantity' => 8,
            'price' => 2.50, // +2.50 EUR differential
            'attribute_ids' => [2, 5], // CHANGE: Use valid attribute IDs (e.g., Color: Blue, Size: S)
        ],
    ];

    echo "Creating product with " . count($combinations) . " combinations...\n";

    $result = $client->createProductWithCombinations($productData, $combinations);

    echo "Product created successfully!\n";
    echo "Product ID: {$result['product_id']}\n";
    echo "Combination IDs: " . implode(', ', $result['combination_ids']) . "\n";

    // Store for next tests
    $createdProductId = $result['product_id'];
    $createdCombinationId = $result['combination_ids'][0];

    echo "✅ TEST 2 PASSED\n\n";

} catch (\Exception $e) {
    echo "❌ TEST 2 FAILED: {$e->getMessage()}\n";
    echo "Error context: " . ($e instanceof \App\Exceptions\PrestaShopAPIException ? json_encode($e->getContext(), JSON_PRETTY_PRINT) : 'N/A') . "\n\n";
    exit(1);
}

// ============================================
// TEST 3: Update combination
// ============================================
echo "TEST 3: Update combination\n";
echo "--------------------------\n";

try {
    echo "Updating combination {$createdCombinationId}...\n";

    $updateData = [
        'quantity' => 50,
        'price' => 10.00,
        'reference' => 'UPDATED-' . time(),
    ];

    $client->updateCombination($createdCombinationId, $updateData);

    echo "Combination updated successfully!\n";

    // Verify update
    $combinations = $client->getProductCombinations($createdProductId);
    $updated = collect($combinations)->firstWhere('id', $createdCombinationId);

    echo "Verified updated values:\n";
    echo "  Quantity: {$updated['quantity']}\n";
    echo "  Price: {$updated['price']}\n";
    echo "  Reference: {$updated['reference']}\n";

    echo "✅ TEST 3 PASSED\n\n";

} catch (\Exception $e) {
    echo "❌ TEST 3 FAILED: {$e->getMessage()}\n\n";
}

// ============================================
// TEST 4: Delete combination
// ============================================
echo "TEST 4: Delete combination\n";
echo "--------------------------\n";

try {
    echo "Deleting combination {$createdCombinationId}...\n";

    $client->deleteCombination($createdCombinationId);

    echo "Combination deleted successfully!\n";

    // Verify deletion
    $combinations = $client->getProductCombinations($createdProductId);
    $deleted = collect($combinations)->firstWhere('id', $createdCombinationId);

    if ($deleted) {
        echo "⚠️  WARNING: Combination still exists after deletion\n";
    } else {
        echo "Verified: Combination removed from product\n";
    }

    echo "✅ TEST 4 PASSED\n\n";

} catch (\Exception $e) {
    echo "❌ TEST 4 FAILED: {$e->getMessage()}\n\n";
}

// ============================================
// CLEANUP: Delete test product
// ============================================
echo "CLEANUP: Deleting test product\n";
echo "------------------------------\n";

try {
    echo "Deleting test product {$createdProductId}...\n";

    $client->deleteProduct($createdProductId);

    echo "Test product deleted successfully!\n";
    echo "✅ CLEANUP COMPLETED\n\n";

} catch (\Exception $e) {
    echo "⚠️  CLEANUP WARNING: Failed to delete test product: {$e->getMessage()}\n";
    echo "You may need to manually delete product ID: {$createdProductId}\n\n";
}

// ============================================
// SUMMARY
// ============================================
echo "========================================\n";
echo "ALL TESTS COMPLETED\n";
echo "========================================\n";
echo "Review results above for any failures.\n";
echo "Check PrestaShop admin panel to verify changes.\n\n";
