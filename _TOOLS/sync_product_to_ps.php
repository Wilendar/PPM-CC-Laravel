<?php
/**
 * ETAP_07h: Sync product to PrestaShop
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;

$productId = $argv[1] ?? 11183;
$shopId = $argv[2] ?? 5;

echo "=== SYNC PRODUCT TO PRESTASHOP ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
if (!$product) {
    die("Product not found!\n");
}

$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    die("Shop not found!\n");
}

echo "Product SKU: {$product->sku}\n";
echo "Shop Name: {$shop->name}\n";
echo "Shop URL: {$shop->url}\n\n";

// Get shop product data - safely
$shopProducts = $product->shopProducts ?? collect();
$shopProduct = $shopProducts->where('shop_id', $shopId)->first();
echo "PrestaShop Product ID: " . ($shopProduct ? $shopProduct->prestashop_product_id : 'N/A') . "\n\n";

// Dispatch sync job (synchronous for testing)
echo "Dispatching SyncProductToPrestaShop job...\n";
try {
    $job = new SyncProductToPrestaShop($product, $shop, null, [], null);
    dispatch_sync($job);
    echo "Job completed!\n";
} catch (Exception $e) {
    echo "Job failed: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone!\n";
