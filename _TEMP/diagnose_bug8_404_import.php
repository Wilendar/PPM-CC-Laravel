<?php

/**
 * BUG #8 DIAGNOSIS: 404 PrestaShop API Error During Import
 *
 * This script diagnoses the root cause of 404 errors when clicking
 * "← Import" button in /admin/shops.
 *
 * Tests:
 * 1. Shop configuration (URL, API key, version)
 * 2. Products with prestashop_product_id
 * 3. API endpoint construction
 * 4. Recent error logs
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "\n=== BUG #8 DIAGNOSIS: 404 Import Error ===\n\n";

// 1. Check active shop configuration
echo "1. CHECKING ACTIVE SHOPS\n";
echo str_repeat("-", 60) . "\n";

$shops = PrestaShopShop::where('is_active', true)->get();

if ($shops->isEmpty()) {
    echo "❌ No active shops found\n";
    exit(1);
}

foreach ($shops as $shop) {
    echo "✅ Active Shop Found:\n";
    echo "   ID: {$shop->id}\n";
    echo "   Name: {$shop->name}\n";
    echo "   URL: {$shop->url}\n";
    echo "   Version: {$shop->prestashop_version}\n";
    echo "   API Key: " . substr($shop->api_key, 0, 10) . "...\n";
    echo "   is_active: " . ($shop->is_active ? 'YES' : 'NO') . "\n\n";
}

$shop = $shops->first(); // Use first active shop for testing

// 2. Check products with prestashop_product_id
echo "\n2. CHECKING PRODUCTS WITH PRESTASHOP ID\n";
echo str_repeat("-", 60) . "\n";

$products = Product::whereHas('shopData', function($query) use ($shop) {
    $query->where('shop_id', $shop->id)
          ->whereNotNull('prestashop_product_id');
})->limit(5)->get();

echo "✅ Products with PrestaShop ID: {$products->count()}\n\n";

if ($products->isEmpty()) {
    echo "⚠️  NO PRODUCTS LINKED TO PRESTASHOP!\n";
    echo "    This means the import will not find any products to sync.\n";
    echo "    Recommendation: First sync products TO PrestaShop before importing.\n\n";
} else {
    foreach ($products as $product) {
        $shopData = $product->shopData()->where('shop_id', $shop->id)->first();
        echo "   • Product {$product->id} (SKU: {$product->sku})\n";
        echo "     → PS ID: {$shopData->prestashop_product_id}\n";
        echo "     → Sync Status: {$shopData->sync_status}\n";
    }
    echo "\n";
}

// 3. Test API endpoint construction
echo "\n3. TESTING API ENDPOINT CONSTRUCTION\n";
echo str_repeat("-", 60) . "\n";

try {
    $client = PrestaShopClientFactory::create($shop);

    echo "✅ Client Created:\n";
    echo "   Version: " . $client->getVersion() . "\n";
    echo "   Shop URL: {$shop->url}\n";

    // Manually construct endpoints to show what SHOULD be called
    $baseUrl = rtrim($shop->url, '/');
    $version = $shop->prestashop_version;
    $basePath = ($version === '9') ? '/api/v1' : '/api';

    echo "\n📍 EXPECTED ENDPOINTS:\n";

    if ($products->isNotEmpty()) {
        $testProduct = $products->first();
        $shopData = $testProduct->shopData()->where('shop_id', $shop->id)->first();
        $psId = $shopData->prestashop_product_id;

        echo "   • getProduct({$psId}):\n";
        echo "     {$baseUrl}{$basePath}/products/{$psId}?output_format=JSON\n\n";

        echo "   • getSpecificPrices({$psId}):\n";
        echo "     {$baseUrl}{$basePath}/specific_prices?filter[id_product]={$psId}&display=full&output_format=JSON\n\n";

        echo "   • getStock({$psId}):\n";
        echo "     {$baseUrl}{$basePath}/stock_availables?filter[id_product]={$psId}&output_format=JSON\n\n";
    }

    echo "✅ API client methods exist:\n";
    echo "   • getProduct(): " . (method_exists($client, 'getProduct') ? 'YES' : 'NO') . "\n";
    echo "   • getSpecificPrices(): " . (method_exists($client, 'getSpecificPrices') ? 'YES' : 'NO') . "\n";
    echo "   • getStock(): " . (method_exists($client, 'getStock') ? 'YES' : 'NO') . "\n";

} catch (\Exception $e) {
    echo "❌ Client Creation Failed:\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
}

// 4. Check recent laravel.log for 404 errors
echo "\n\n4. CHECKING RECENT LARAVEL LOGS\n";
echo str_repeat("-", 60) . "\n";

$logPath = storage_path('logs/laravel.log');

if (file_exists($logPath)) {
    $logContent = file_get_contents($logPath);
    $logLines = explode("\n", $logContent);

    $relevant = array_filter($logLines, fn($line) =>
        str_contains($line, '404') ||
        str_contains($line, 'PrestaShop API') ||
        str_contains($line, 'PullProductsFromPrestaShop') ||
        str_contains($line, 'PrestaShopPriceImporter') ||
        str_contains($line, 'PrestaShopStockImporter')
    );

    echo "📋 Recent Log Entries (last 20):\n\n";

    $recent = array_slice($relevant, -20);

    if (empty($recent)) {
        echo "   (No relevant log entries found)\n";
    } else {
        foreach ($recent as $line) {
            // Truncate very long lines
            $truncated = substr($line, 0, 150);
            if (strlen($line) > 150) {
                $truncated .= "...";
            }
            echo "   " . $truncated . "\n";
        }
    }
} else {
    echo "⚠️  Log file not found: {$logPath}\n";
}

// 5. ROOT CAUSE ANALYSIS
echo "\n\n5. ROOT CAUSE ANALYSIS\n";
echo str_repeat("=", 60) . "\n";

echo "\n🔍 IDENTIFIED ROOT CAUSE:\n\n";

if ($products->isEmpty()) {
    echo "❌ PRIMARY ISSUE: NO PRODUCTS LINKED TO PRESTASHOP\n\n";
    echo "The import job expects products with prestashop_product_id, but none exist.\n";
    echo "This causes the job to complete with 0 products processed.\n\n";
    echo "If 404 error occurred, it's likely because:\n";
    echo "  • A product WAS linked but was deleted from PrestaShop\n";
    echo "  • The prestashop_product_id points to non-existent product\n\n";
} else {
    echo "✅ Products are linked to PrestaShop\n\n";
    echo "Possible 404 causes:\n";
    echo "  1. Product deleted from PrestaShop (prestashop_product_id invalid)\n";
    echo "  2. Incorrect shop URL in database\n";
    echo "  3. API endpoint construction error\n";
    echo "  4. PrestaShop API authentication failure\n\n";
}

echo "NEXT STEPS:\n";
echo "  1. Review the EXPECTED ENDPOINTS above\n";
echo "  2. Manually test one endpoint with curl to verify shop connectivity\n";
echo "  3. Check if prestashop_product_id exists in PrestaShop database\n";
echo "  4. Review error logs for exact endpoint that returned 404\n\n";

echo "\n=== END DIAGNOSIS ===\n\n";
