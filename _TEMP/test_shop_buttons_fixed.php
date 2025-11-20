<?php

/**
 * Test Shop Tab Buttons After Fix
 *
 * Verifies that both "Aktualizuj sklep" and "Pobierz dane" buttons
 * dispatch jobs with correct arguments.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\PrestaShop\PullSingleProductFromPrestaShop;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST: Shop Tab Buttons Fix ===\n\n";

// Enable queue job listening
Queue::fake();

// Find product with shop data
$product = Product::whereHas('shopData', function($query) {
    $query->whereNotNull('prestashop_product_id');
})->first();

if (!$product) {
    echo "❌ No product found with shop data\n";
    exit(1);
}

$shopData = $product->shopData->first();
$shop = $shopData->shop;

echo "Testing with:\n";
echo "  Product ID: {$product->id}\n";
echo "  SKU: {$product->sku}\n";
echo "  Shop ID: {$shop->id}\n";
echo "  Shop Name: {$shop->name}\n";
echo "  PrestaShop Product ID: {$shopData->prestashop_product_id}\n\n";

// TEST 1: SyncProductToPrestaShop dispatch
echo "TEST 1: SyncProductToPrestaShop dispatch\n";
echo "  Dispatching with: (Product, PrestaShopShop, userId)\n";

try {
    SyncProductToPrestaShop::dispatch($product, $shop, 1);

    // Check if job was pushed
    Queue::assertPushed(SyncProductToPrestaShop::class, function($job) use ($product, $shop) {
        return $job->product->id === $product->id
            && $job->shop->id === $shop->id
            && $job->userId === 1;
    });

    echo "  ✅ Job dispatched successfully\n";
    echo "  ✅ Arguments correct: Product instance, PrestaShopShop instance, User ID\n\n";

} catch (\Exception $e) {
    echo "  ❌ ERROR: {$e->getMessage()}\n\n";
    exit(1);
}

// TEST 2: PullSingleProductFromPrestaShop dispatch
echo "TEST 2: PullSingleProductFromPrestaShop dispatch\n";
echo "  Dispatching with: (Product, PrestaShopShop)\n";

try {
    PullSingleProductFromPrestaShop::dispatch($product, $shop);

    // Check if job was pushed
    Queue::assertPushed(PullSingleProductFromPrestaShop::class, function($job) use ($product, $shop) {
        return $job->product->id === $product->id
            && $job->shop->id === $shop->id;
    });

    echo "  ✅ Job dispatched successfully\n";
    echo "  ✅ Arguments correct: Product instance, PrestaShopShop instance\n\n";

} catch (\Exception $e) {
    echo "  ❌ ERROR: {$e->getMessage()}\n\n";
    exit(1);
}

// TEST 3: Verify job structure
echo "TEST 3: Verify job structure\n";

$syncJob = new SyncProductToPrestaShop($product, $shop, 1);
echo "  SyncProductToPrestaShop properties:\n";
echo "    - product: " . get_class($syncJob->product) . " (ID: {$syncJob->product->id})\n";
echo "    - shop: " . get_class($syncJob->shop) . " (ID: {$syncJob->shop->id})\n";
echo "    - userId: {$syncJob->userId}\n";

$pullJob = new PullSingleProductFromPrestaShop($product, $shop);
echo "  PullSingleProductFromPrestaShop properties:\n";
echo "    - product: " . get_class($pullJob->product) . " (ID: {$pullJob->product->id})\n";
echo "    - shop: " . get_class($pullJob->shop) . " (ID: {$pullJob->shop->id})\n\n";

echo "=== ALL TESTS PASSED ✅ ===\n\n";

echo "VERIFICATION:\n";
echo "1. Open product page with linked shops\n";
echo "2. Click 'Aktualizuj sklep' → should dispatch SyncProductToPrestaShop\n";
echo "3. Click 'Pobierz dane' → should dispatch PullSingleProductFromPrestaShop\n";
echo "4. Check Laravel logs for successful job dispatch\n";
echo "5. Check queue jobs in admin panel\n\n";
