<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "=== Updating external_reference (link_rewrite) for existing products ===" . PHP_EOL . PHP_EOL;

// Get all ProductShopData records that have external_id but missing external_reference
$products = ProductShopData::whereNotNull('external_id')
    ->whereNull('external_reference')
    ->with('shop')
    ->get();

echo "Found {$products->count()} products without link_rewrite" . PHP_EOL . PHP_EOL;

$updated = 0;
$errors = 0;

foreach ($products as $shopData) {
    try {
        $shop = $shopData->shop;

        if (!$shop) {
            echo "❌ Product ID {$shopData->product_id}: Shop not found" . PHP_EOL;
            $errors++;
            continue;
        }

        echo "Processing Product ID {$shopData->product_id} (PrestaShop ID: {$shopData->external_id}) from shop {$shop->name}...";

        $client = PrestaShopClientFactory::create($shop);
        $data = $client->getProduct($shopData->external_id);

        // Unwrap if needed
        if (isset($data['product'])) {
            $data = $data['product'];
        }

        $linkRewrite = $data['link_rewrite'] ?? null;

        if ($linkRewrite) {
            $shopData->external_reference = $linkRewrite;
            $shopData->save();
            echo " ✅ Updated: {$linkRewrite}" . PHP_EOL;
            $updated++;
        } else {
            echo " ⚠️ No link_rewrite in API response" . PHP_EOL;
            $errors++;
        }

    } catch (\Exception $e) {
        echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
        $errors++;
    }

    // Small delay to not overload API
    usleep(100000); // 0.1 second
}

echo PHP_EOL . "=== SUMMARY ===" . PHP_EOL;
echo "✅ Updated: {$updated}" . PHP_EOL;
echo "❌ Errors: {$errors}" . PHP_EOL;
echo "📊 Total processed: " . ($updated + $errors) . PHP_EOL;
