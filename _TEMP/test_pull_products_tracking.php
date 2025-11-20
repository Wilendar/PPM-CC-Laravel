<?php

/**
 * Test PullProductsFromPrestaShop SyncJob Tracking
 *
 * FIX #1 Validation - BUG #7 (2025-11-12)
 *
 * Tests:
 * 1. SyncJob creation in constructor
 * 2. Job dispatch creates SyncJob record
 * 3. Default warehouse configuration (for stock import)
 * 4. Active shops availability
 *
 * Usage:
 *   php _TEMP/test_pull_products_tracking.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Jobs\PullProductsFromPrestaShop;
use App\Models\SyncJob;
use App\Models\Warehouse;

echo "=== PULL PRODUCTS TRACKING TEST ===\n\n";

// 1. Check active shops
$shops = PrestaShopShop::where('is_active', true)->get();
echo "Active shops: " . $shops->count() . "\n";

if ($shops->isEmpty()) {
    echo "⚠️  No active shops found\n";
    echo "   Run: UPDATE prestashop_shops SET is_active = true WHERE id = 1;\n";
    exit(1);
}

$shop = $shops->first();
echo "Testing with shop: {$shop->name} (ID: {$shop->id})\n";
echo "   - URL: {$shop->url}\n";
echo "   - PrestaShop Version: {$shop->prestashop_version}\n";
echo "   - Auto Sync Products: " . ($shop->auto_sync_products ? 'YES' : 'NO') . "\n";
echo "\n";

// 2. Check SyncJob before dispatch
$beforeCount = SyncJob::where('source_id', $shop->id)
    ->where('source_type', SyncJob::TYPE_PRESTASHOP)
    ->where('job_type', 'import_products')
    ->count();
echo "SyncJobs BEFORE dispatch: {$beforeCount}\n";

// 3. Check product_shop_data linkage
$linkedProductsCount = \App\Models\Product::whereHas('shopData', function($query) use ($shop) {
    $query->where('shop_id', $shop->id)
          ->whereNotNull('prestashop_product_id');
})->count();
echo "Linked products (with prestashop_product_id): {$linkedProductsCount}\n";

if ($linkedProductsCount === 0) {
    echo "⚠️  No products linked to this shop\n";
    echo "   Job will run but process 0 products\n";
}
echo "\n";

// 4. Dispatch job
echo "Dispatching PullProductsFromPrestaShop job...\n";
PullProductsFromPrestaShop::dispatch($shop);
echo "✓ Job dispatched\n\n";

// 5. Check SyncJob after dispatch
sleep(2); // Wait for DB write
$afterCount = SyncJob::where('source_id', $shop->id)
    ->where('source_type', SyncJob::TYPE_PRESTASHOP)
    ->where('job_type', 'import_products')
    ->count();
echo "SyncJobs AFTER dispatch: {$afterCount}\n";

$latestSyncJob = SyncJob::where('source_id', $shop->id)
    ->where('source_type', SyncJob::TYPE_PRESTASHOP)
    ->where('job_type', 'import_products')
    ->latest()
    ->first();

if ($latestSyncJob) {
    echo "\n✅ SyncJob created successfully!\n";
    echo "   - ID: {$latestSyncJob->id}\n";
    echo "   - Job ID (UUID): {$latestSyncJob->job_id}\n";
    echo "   - Status: {$latestSyncJob->status}\n";
    echo "   - Job Type: {$latestSyncJob->job_type}\n";
    echo "   - Job Name: {$latestSyncJob->job_name}\n";
    echo "   - Source: {$latestSyncJob->source_type} (ID: {$latestSyncJob->source_id})\n";
    echo "   - Target: {$latestSyncJob->target_type}\n";
    echo "   - Total Items: {$latestSyncJob->total_items}\n";
    echo "   - User ID: " . ($latestSyncJob->user_id ?: 'SYSTEM') . "\n";
    echo "   - Trigger Type: {$latestSyncJob->trigger_type}\n";
    echo "   - Scheduled At: {$latestSyncJob->scheduled_at}\n";
} else {
    echo "\n❌ SyncJob NOT created - FIX #1 not working!\n";
    echo "   Check constructor in PullProductsFromPrestaShop.php\n";
    exit(1);
}

// 6. Check warehouse mapping (for stock import)
echo "\n";
echo "--- STOCK IMPORT CONFIGURATION ---\n";
$warehouse = Warehouse::where('code', 'mpptrade')->first();
if ($warehouse) {
    echo "✓ MPPTRADE warehouse found (ID: {$warehouse->id})\n";
    echo "   - Is Default: " . ($warehouse->is_default ? 'YES' : 'NO') . "\n";

    if (!$warehouse->is_default) {
        echo "   ⚠️  Warning: MPPTRADE is not default warehouse\n";
        echo "   Stock import may fail\n";
    }
} else {
    echo "❌ MPPTRADE warehouse NOT found\n";
    echo "   Stock import WILL FAIL!\n";
    echo "   Create: INSERT INTO warehouses (code, name, is_default) VALUES ('mpptrade', 'MPP Trade', 1);\n";
}

// 7. Queue configuration check
echo "\n";
echo "--- QUEUE CONFIGURATION ---\n";
$queueConnection = config('queue.default');
echo "Default queue connection: {$queueConnection}\n";

if ($queueConnection === 'database') {
    $queuedJobsCount = \DB::table('jobs')->count();
    echo "Queued jobs in database: {$queuedJobsCount}\n";

    if ($queuedJobsCount > 0) {
        echo "✓ Job queued successfully\n";
        echo "  Run: php artisan queue:work\n";
    } else {
        echo "⚠️  No jobs in queue (sync mode?)\n";
    }
} else {
    echo "Queue driver: {$queueConnection}\n";
    echo "Check queue worker status: php artisan queue:work\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNext steps:\n";
echo "1. Run queue worker: php artisan queue:work\n";
echo "2. Monitor logs: tail -f storage/logs/laravel.log | grep PullProductsFromPrestaShop\n";
echo "3. Check UI: /admin/shops/sync (SyncController)\n";
echo "4. Query: SELECT * FROM sync_jobs WHERE job_type = 'import_products' ORDER BY id DESC LIMIT 5;\n";
echo "\n";
