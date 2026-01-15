<?php
/**
 * Manual sync product to PrestaShop
 * Run: cd domains/ppm.mpptrade.pl/public_html && php _TOOLS/sync_product.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Models\Product;
use App\Models\PrestaShopShop;

$productId = 11181;
$shopId = 1;
$userId = 8;

echo "=== Manual Product Sync ===\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product) {
    echo "ERROR: Product {$productId} not found\n";
    exit(1);
}

if (!$shop) {
    echo "ERROR: Shop {$shopId} not found\n";
    exit(1);
}

echo "Product: {$product->sku} - {$product->name}\n";
echo "Shop: {$shop->name}\n\n";

echo "Dispatching sync job...\n";

try {
    // Dispatch synchronously for testing
    SyncProductToPrestaShop::dispatchSync($product, $shop, $userId);
    echo "Sync completed synchronously!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Done ===\n";
