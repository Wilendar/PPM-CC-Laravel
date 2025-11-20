<?php
/**
 * Test Category-Product Association via API
 *
 * ALTERNATIVE APPROACH 2025-11-05:
 * Instead of adding categories to product (which API ignores),
 * try adding product to category via PUT /api/categories/{id}
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST CATEGORY-PRODUCT ASSOCIATION VIA API ===\n\n";

// Get shop
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

$client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

$categoryId = 12; // PITGANG
$productId = 9760; // TEST-SYNC-001

echo "1. Getting category {$categoryId} from PrestaShop...\n";

try {
    $response = $client->makeRequest('GET', "/categories/{$categoryId}");
    $category = $response['category'];

    // Get category name (handle multilang)
    $categoryName = is_array($category['name'])
        ? (isset($category['name'][0]['value']) ? $category['name'][0]['value'] : $category['name'][0])
        : $category['name'];

    echo "✅ Category found: {$categoryName}\n";
    echo "   - ID: {$category['id']}\n";
    echo "   - Parent: {$category['id_parent']}\n";
    echo "   - Active: {$category['active']}\n\n";

    // Check existing product associations
    echo "2. Checking existing product associations...\n";

    $existingProducts = [];
    if (isset($category['associations']['products']['product'])) {
        $products = $category['associations']['products']['product'];

        // Normalize to array
        if (!isset($products[0])) {
            $products = [$products];
        }

        foreach ($products as $prod) {
            $existingProducts[] = (int) $prod['id'];
        }
    }

    echo "   Current products: " . (empty($existingProducts) ? "NONE" : implode(', ', $existingProducts)) . "\n\n";

    // Check if our product is already in the list
    if (in_array($productId, $existingProducts)) {
        echo "⚠️  Product {$productId} is ALREADY in category {$categoryId}\n";
        echo "   But GET /api/products/{$productId} shows NO categories!\n";
        echo "   This confirms PrestaShop API inconsistency.\n\n";
        exit(0);
    }

    // Add our product to the list
    echo "3. Adding product {$productId} to category associations...\n";

    $existingProducts[] = $productId;

    // Build updated associations (proper PrestaShop format)
    $productsArray = [];
    foreach ($existingProducts as $prodId) {
        $productsArray[] = [
            'product' => [
                'id' => $prodId
            ]
        ];
    }

    // Rebuild category with clean associations
    $updatedCategory = $category;

    // Remove readonly fields (similar to product readonly fields)
    $readonlyFields = [
        'level_depth',
        'nb_products_recursive',
        'date_add',
        'date_upd',
        'position', // might be readonly
    ];

    foreach ($readonlyFields as $field) {
        unset($updatedCategory[$field]);
    }

    // Convert multilang fields from string to proper format
    // GET returns simple string, but PUT requires array format
    $multilangFields = ['name', 'link_rewrite', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    foreach ($multilangFields as $field) {
        if (isset($updatedCategory[$field]) && is_string($updatedCategory[$field])) {
            $updatedCategory[$field] = [
                'language' => [
                    'id' => 1,
                    'value' => $updatedCategory[$field]
                ]
            ];
        }
    }

    // Clean up associations - remove numeric keys and rebuild properly
    unset($updatedCategory['associations']['products']);
    $updatedCategory['associations']['products'] = $productsArray;

    // Wrap in prestashop root
    $categoryData = ['category' => $updatedCategory];

    echo "   New products count: " . count($existingProducts) . "\n";
    echo "   Products: " . implode(', ', $existingProducts) . "\n\n";

    // Convert to XML and send PUT
    echo "4. Sending PUT /api/categories/{$categoryId}...\n";

    $xmlBody = $client->arrayToXml($categoryData);

    // DEBUG: Show XML preview
    echo "\n--- XML Preview (first 2000 chars) ---\n";
    echo substr($xmlBody, 0, 2000) . "\n";
    echo "--- End XML Preview ---\n\n";

    $updateResponse = $client->makeRequest('PUT', "/categories/{$categoryId}", [], [
        'body' => $xmlBody,
        'headers' => [
            'Content-Type' => 'application/xml',
        ],
    ]);

    echo "✅ Category updated successfully!\n\n";

    // Verify - check product
    echo "5. Verifying product {$productId} now has categories...\n";

    $productResponse = $client->getProduct($productId);
    $product = $productResponse['product'];

    if (isset($product['associations']['categories']['category'])) {
        $categories = $product['associations']['categories']['category'];

        // Normalize
        if (!isset($categories[0])) {
            $categories = [$categories];
        }

        echo "✅ ✅ ✅ SUCCESS! Product NOW HAS CATEGORIES!\n";
        echo "Categories count: " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "  - Category ID: {$cat['id']}\n";
        }

        echo "\n🎉 🎉 🎉 SOLUTION FOUND!\n";
        echo "→ Add product to category via PUT /api/categories/{id}\n";
        echo "→ This WORKS (unlike PUT /api/products/{id})\n";
        echo "→ No direct database access needed!\n";

    } else {
        echo "❌ Product STILL has NO categories\n";
        echo "→ This approach also doesn't work\n";
    }

} catch (\Exception $e) {
    echo "❌ FAILED:\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== END TEST ===\n";
