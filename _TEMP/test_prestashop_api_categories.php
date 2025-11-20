<?php
// Test PrestaShop API - czy zwraca categories dla produktu
// Shop ID 1 (B2B Test DEV), Product PrestaShop ID: 1830

use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Models\PrestaShopShop;

$shopId = 1;
$prestashopProductId = 1830;

echo "=== TESTING PRESTASHOP API - CATEGORIES ===\n\n";

$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    echo "Shop ID {$shopId} NOT FOUND\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "PrestaShop Version: {$shop->prestashop_version}\n";
echo "Product PrestaShop ID: {$prestashopProductId}\n\n";

try {
    $client = PrestaShopClientFactory::create($shop);

    echo "Fetching product data from PrestaShop API...\n";
    $response = $client->getProduct($prestashopProductId);

    // Unwrap nested response
    $prestashopData = $response['product'] ?? $response;

    echo "\n=== PRODUCT DATA STRUCTURE ===\n";
    echo "Product ID: " . ($prestashopData['id'] ?? 'NULL') . "\n";
    echo "Product Name: " . (data_get($prestashopData, 'name.0.value') ?? 'NULL') . "\n";

    echo "\n=== CATEGORIES IN API RESPONSE ===\n";
    if (isset($prestashopData['associations']['categories'])) {
        $categories = $prestashopData['associations']['categories'];
        echo "Categories found: YES\n";
        echo "Categories count: " . count($categories) . "\n";
        echo "Categories data:\n";
        print_r($categories);
    } else {
        echo "Categories found: NO\n";
        echo "Checking alternative paths...\n";
        echo "  - prestashopData['categories']: " . (isset($prestashopData['categories']) ? 'YES' : 'NO') . "\n";
        echo "  - prestashopData['associations']: " . (isset($prestashopData['associations']) ? 'YES' : 'NO') . "\n";

        if (isset($prestashopData['associations'])) {
            echo "\nAssociations keys:\n";
            print_r(array_keys($prestashopData['associations']));
        }
    }

    echo "\n=== TEST EXTRACTION (FIX #10.2 LOGIC) ===\n";
    $categories = data_get($prestashopData, 'associations.categories') ?? [];
    echo "Extracted categories: " . json_encode($categories) . "\n";
    echo "Is empty: " . (empty($categories) ? 'YES' : 'NO') . "\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
