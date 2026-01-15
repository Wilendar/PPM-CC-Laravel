<?php
/**
 * Force sync product 11183 to PrestaShop shop 5
 * This will push the visual description with CSS classes
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;

$productId = 11183;
$shopId = 5;

echo "=== FORCING SYNC ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    die("Product or Shop not found!\n");
}

echo "Dispatching SyncProductToPrestaShop job...\n";

// Dispatch sync job
SyncProductToPrestaShop::dispatch($product, $shop, null, []);

echo "Job dispatched! Check logs for sync status.\n";
echo "\nMonitor with: grep -E 'sync|11183|transformed' storage/logs/laravel.log | tail -50\n";
