<?php

// Check product 4017 PrestaShop API response structure
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

echo "=== PRODUCT 4017 PrestaShop API CHECK ===\n\n";

// Get Test KAYO shop
$shop = PrestaShopShop::where('name', 'Test KAYO')->first();

if (!$shop) {
    echo "❌ Shop 'Test KAYO' not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "API URL: {$shop->api_url}\n\n";

// Create API client
$clientFactory = app(PrestaShopClientFactory::class);
$client = $clientFactory->create($shop);

// Fetch product 4017
echo "Fetching product 4017...\n";
$psProduct = $client->getProduct(4017);

echo "\n=== RAW API RESPONSE ===\n";
echo json_encode($psProduct, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== CATEGORY EXTRACTION ===\n";
$associations = $psProduct['associations'] ?? [];
$categories = $associations['categories'] ?? [];

echo "associations keys: " . json_encode(array_keys($associations)) . "\n";
echo "categories structure: " . json_encode($categories, JSON_PRETTY_PRINT) . "\n";

// Check if 'category' key exists (nested structure)
if (isset($categories['category'])) {
    echo "✅ Found nested 'category' key\n";
    $categoryList = $categories['category'];
} else {
    echo "⚠️  No 'category' key - using direct array\n";
    $categoryList = $categories;
}

echo "category list: " . json_encode($categoryList, JSON_PRETTY_PRINT) . "\n";

// Extract IDs
$categoryIds = [];
foreach ((array) $categoryList as $cat) {
    if (is_array($cat) && isset($cat['id'])) {
        $categoryIds[] = (int) $cat['id'];
    } elseif (is_numeric($cat)) {
        $categoryIds[] = (int) $cat;
    }
}

echo "\n=== EXTRACTED CATEGORY IDs ===\n";
echo "Category IDs: " . json_encode($categoryIds) . "\n";
echo "Count: " . count($categoryIds) . "\n";

echo "\n=== END ===\n";
