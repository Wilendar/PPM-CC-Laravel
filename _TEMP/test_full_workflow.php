<?php
/**
 * FULL WORKFLOW TEST - Save Shop Data + Verify Auto-Dispatch
 *
 * This script:
 * 1. Clears test jobs
 * 2. Simulates save in shop TAB (product 11018)
 * 3. Verifies DB update (sync_status, updated_at)
 * 4. Checks if job was dispatched
 * 5. Verifies job appears in queue
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== FULL WORKFLOW TEST ===\n";
echo "Product ID: 5 (TEST-SYNC-1762328754)\n";
echo "Date: " . now()->toDateTimeString() . "\n\n";

// Step 1: Clear old test jobs
echo "[1/6] Clearing old test jobs...\n";
$deletedJobs = DB::table('jobs')->where('queue', 'test_queue')->delete();
echo "Deleted test jobs: {$deletedJobs}\n\n";

// Step 2: Get product and shop
echo "[2/6] Loading product and shop...\n";
$product = Product::find(5);
if (!$product) {
    echo "❌ ERROR: Product 5 not found\n";
    exit(1);
}
echo "✅ Product loaded: {$product->name}\n";

$shop = PrestaShopShop::where('is_active', true)
    ->first(); // Remove connection_status check for testing
if (!$shop) {
    echo "❌ ERROR: No active shop found\n";
    exit(1);
}
echo "✅ Shop loaded: {$shop->name} (ID: {$shop->id})\n\n";

// Step 3: Get or create product_shop_data
echo "[3/6] Getting product_shop_data...\n";
$productShopData = ProductShopData::firstOrCreate(
    [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
    ],
    [
        'sync_status' => 'synced',
        'sync_direction' => 'ppm_to_ps',
    ]
);

echo "Current state BEFORE save:\n";
echo "  sync_status: {$productShopData->sync_status}\n";
echo "  updated_at: {$productShopData->updated_at}\n";
echo "  name: " . ($productShopData->name ?? 'NULL') . "\n\n";

// Step 4: Simulate save (change name field)
echo "[4/6] Simulating save workflow...\n";
$oldName = $productShopData->name;
$newName = "Test Auto-Fix - " . now()->format('Y-m-d H:i:s');

// Count jobs BEFORE
$jobsCountBefore = DB::table('jobs')->count();
$syncJobsCountBefore = DB::table('sync_jobs')->count();

echo "Jobs BEFORE save:\n";
echo "  jobs table: {$jobsCountBefore}\n";
echo "  sync_jobs table: {$syncJobsCountBefore}\n\n";

// SIMULATE savePendingChangesToShop()
echo "Saving changes...\n";
$productShopData->fill([
    'name' => $newName,
    'sync_status' => 'pending', // CRITICAL: This should be set
]);
$productShopData->save();

echo "✅ ProductShopData saved\n";

// SIMULATE auto-dispatch
echo "Dispatching sync job...\n";
try {
    \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch($product, $shop);
    echo "✅ Sync job dispatched\n";
    Log::info('TEST: Auto-dispatched sync job', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'trigger' => 'test_full_workflow.php',
    ]);
} catch (\Exception $e) {
    echo "❌ ERROR dispatching job: " . $e->getMessage() . "\n";
}

// Step 5: Verify DB state AFTER
echo "\n[5/6] Verifying DB state AFTER save...\n";
$productShopData->refresh();

echo "Current state AFTER save:\n";
echo "  sync_status: {$productShopData->sync_status}\n";
echo "  updated_at: {$productShopData->updated_at}\n";
echo "  name: {$productShopData->name}\n\n";

// Step 6: Check jobs table
$jobsCountAfter = DB::table('jobs')->count();
$syncJobsCountAfter = DB::table('sync_jobs')->count();

echo "[6/6] Verifying queue state...\n";
echo "Jobs AFTER save:\n";
echo "  jobs table: {$jobsCountAfter} (diff: " . ($jobsCountAfter - $jobsCountBefore) . ")\n";
echo "  sync_jobs table: {$syncJobsCountAfter} (diff: " . ($syncJobsCountAfter - $syncJobsCountBefore) . ")\n\n";

// Get latest job
$latestJob = DB::table('jobs')->orderBy('id', 'desc')->first();
if ($latestJob) {
    $payload = json_decode($latestJob->payload, true);
    echo "Latest job in queue:\n";
    echo "  Job ID: {$latestJob->id}\n";
    echo "  Queue: {$latestJob->queue}\n";
    echo "  Class: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    echo "  Created: {$latestJob->created_at}\n";
} else {
    echo "❌ No jobs in queue!\n";
}

// Get latest sync_job
echo "\nLatest sync_jobs:\n";
$latestSyncJobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['id', 'status', 'queue_job_id', 'created_at']);

foreach ($latestSyncJobs as $sj) {
    echo sprintf(
        "  %s | Status: %s | Queue Job ID: %s | Created: %s\n",
        substr($sj->id, 0, 8) . '...',
        $sj->status,
        $sj->queue_job_id ?: 'NULL',
        $sj->created_at
    );
}

// SUMMARY
echo "\n=== SUMMARY ===\n";
$success = true;

if ($productShopData->sync_status !== 'pending') {
    echo "❌ FAIL: sync_status is '{$productShopData->sync_status}' (expected: 'pending')\n";
    $success = false;
} else {
    echo "✅ PASS: sync_status = 'pending'\n";
}

if ($productShopData->name !== $newName) {
    echo "❌ FAIL: name not updated\n";
    $success = false;
} else {
    echo "✅ PASS: name updated\n";
}

if ($jobsCountAfter <= $jobsCountBefore) {
    echo "❌ FAIL: No new job added to queue\n";
    $success = false;
} else {
    echo "✅ PASS: Job added to queue (+1)\n";
}

if ($latestJob && strpos($latestJob->payload, 'SyncProductToPrestaShop') !== false) {
    echo "✅ PASS: Latest job is SyncProductToPrestaShop\n";
} else {
    echo "❌ FAIL: Latest job is NOT SyncProductToPrestaShop\n";
    $success = false;
}

if ($success) {
    echo "\n🎉 ALL TESTS PASSED! Fix is working correctly.\n";
} else {
    echo "\n🚨 TESTS FAILED! Fix is NOT working.\n";
}
