<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;

echo "\n=== TEST BUG #12 FIX: hasPendingSyncJob() ===\n\n";

// Product 11017, Shop 1
$productId = 11017;
$shopId = 1;

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product) {
    echo "❌ Product #{$productId} not found\n";
    exit;
}

if (!$shop) {
    echo "❌ Shop #{$shopId} not found\n";
    exit;
}

echo "Product: #{$product->id} - {$product->name}\n";
echo "Shop: #{$shop->id} - {$shop->name}\n\n";

// BEFORE state
echo "=== BEFORE DISPATCH ===\n";
$jobsCountBefore = DB::table('jobs')->count();
$syncJobsCountBefore = DB::table('sync_jobs')->count();
$hasPendingBefore = $shop->hasPendingSyncJob();

echo "Laravel jobs table: {$jobsCountBefore}\n";
echo "sync_jobs table: {$syncJobsCountBefore}\n";
echo "hasPendingSyncJob(): " . ($hasPendingBefore ? 'TRUE ✅' : 'FALSE ❌') . "\n\n";

// Change price (add 1 PLN to trigger sync)
$shopData = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$shopData) {
    echo "❌ ProductShopData not found\n";
    exit;
}

echo "=== TRIGGERING SYNC (setting status to 'pending') ===\n\n";

$shopData->sync_status = 'pending';
$shopData->save();

// Dispatch job
echo "Dispatching SyncProductToPrestaShop job...\n\n";
SyncProductToPrestaShop::dispatch($product, $shop, 8);

// AFTER state (IMMEDIATE - before queue worker executes)
echo "=== AFTER DISPATCH (IMMEDIATE) ===\n";
$jobsCountAfter = DB::table('jobs')->count();
$syncJobsCountAfter = DB::table('sync_jobs')->count();
$hasPendingAfter = $shop->fresh()->hasPendingSyncJob(); // Fresh model instance

echo "Laravel jobs table: {$jobsCountAfter} (+" . ($jobsCountAfter - $jobsCountBefore) . ")\n";
echo "sync_jobs table: {$syncJobsCountAfter} (+" . ($syncJobsCountAfter - $syncJobsCountBefore) . ")\n";
echo "hasPendingSyncJob(): " . ($hasPendingAfter ? 'TRUE ✅' : 'FALSE ❌') . "\n\n";

// Test result
echo "=== TEST RESULT ===\n";
if ($jobsCountAfter > $jobsCountBefore) {
    echo "✅ Job added to Laravel queue\n";
} else {
    echo "❌ No job added to Laravel queue\n";
}

if ($hasPendingAfter) {
    echo "✅ hasPendingSyncJob() returns TRUE (button will be ENABLED)\n";
    echo "✅ BUG #12 FIXED!\n";
} else {
    echo "❌ hasPendingSyncJob() returns FALSE (button will be DISABLED)\n";
    echo "❌ BUG #12 NOT FIXED - further investigation needed\n";
}

echo "\n";
