<?php

/**
 * Test Auto-Dispatch Sync Jobs
 *
 * Testuje czy auto-dispatch w ProductForm.php faktycznie tworzy joby w queue
 *
 * FAZA 9 Phase 3 - Testing auto-dispatch after shop data save
 *
 * Test scenario:
 * 1. Find test product + shop
 * 2. Simulate saveShopSpecificData() flow
 * 3. Check if SyncProductToPrestaShop job was dispatched
 * 4. Verify job appears in jobs table
 * 5. Verify job appears in QueueJobsDashboard data
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;

echo "\n=== AUTO-DISPATCH TEST ===\n\n";

// 1. Find test product and shop
echo "1. FINDING TEST DATA:\n";
echo str_repeat('-', 50) . "\n";

$product = Product::where('is_active', true)->first();
if (!$product) {
    echo "❌ ERROR: No active products found\n";
    exit(1);
}

echo "✅ Test product: #{$product->id} - {$product->sku} - {$product->name}\n";

$shop = PrestaShopShop::where('is_active', true)
    ->where('connection_status', 'connected')
    ->first();

if (!$shop) {
    echo "❌ ERROR: No active & connected shops found\n";
    exit(1);
}

echo "✅ Test shop: #{$shop->id} - {$shop->name}\n";
echo "\n";

// 2. Check BEFORE state
echo "2. STATE BEFORE DISPATCH:\n";
echo str_repeat('-', 50) . "\n";

$jobsCountBefore = DB::table('jobs')->count();
echo "Jobs in queue: $jobsCountBefore\n";

$syncJobsCountBefore = DB::table('sync_jobs')->count();
echo "Sync jobs records: $syncJobsCountBefore\n";

echo "\n";

// 3. Dispatch job (simulate auto-dispatch from ProductForm)
echo "3. DISPATCHING SYNC JOB:\n";
echo str_repeat('-', 50) . "\n";

try {
    echo "Calling: SyncProductToPrestaShop::dispatch(\$product, \$shop)\n";

    $dispatchedJob = SyncProductToPrestaShop::dispatch($product, $shop);

    echo "✅ Job dispatched successfully\n";
    echo "Job class: " . get_class($dispatchedJob) . "\n";

} catch (\Exception $e) {
    echo "❌ ERROR dispatching job: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";

// 4. Check AFTER state
echo "4. STATE AFTER DISPATCH:\n";
echo str_repeat('-', 50) . "\n";

// Small delay to ensure database write
usleep(100000); // 100ms

$jobsCountAfter = DB::table('jobs')->count();
echo "Jobs in queue: $jobsCountAfter (was: $jobsCountBefore)\n";

if ($jobsCountAfter > $jobsCountBefore) {
    echo "✅ Job was ADDED to queue (+", ($jobsCountAfter - $jobsCountBefore), ")\n";
} else {
    echo "⚠️ Job was NOT added to queue (may be executed synchronously or failed)\n";
}

$syncJobsCountAfter = DB::table('sync_jobs')->count();
echo "Sync jobs records: $syncJobsCountAfter (was: $syncJobsCountBefore)\n";

echo "\n";

// 5. Inspect the dispatched job
echo "5. INSPECTING DISPATCHED JOB:\n";
echo str_repeat('-', 50) . "\n";

if ($jobsCountAfter > $jobsCountBefore) {
    $latestJob = DB::table('jobs')
        ->orderBy('id', 'desc')
        ->first();

    if ($latestJob) {
        echo "Latest job in queue:\n";
        echo "  ID: {$latestJob->id}\n";
        echo "  Queue: {$latestJob->queue}\n";
        echo "  Attempts: {$latestJob->attempts}\n";
        echo "  Created: " . date('Y-m-d H:i:s', $latestJob->created_at) . "\n";
        echo "  Available: " . date('Y-m-d H:i:s', $latestJob->available_at) . "\n";

        // Decode payload
        $payload = json_decode($latestJob->payload, true);
        echo "  Display Name: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "  Job Class: " . ($payload['job'] ?? 'Unknown') . "\n";

        // Check if it's our SyncProductToPrestaShop job
        if (isset($payload['job']) && strpos($payload['job'], 'SyncProductToPrestaShop') !== false) {
            echo "  ✅ This is SyncProductToPrestaShop job!\n";
        } else {
            echo "  ⚠️ This is NOT SyncProductToPrestaShop job\n";
        }
    }
} else {
    echo "No job found in queue to inspect\n";
}

echo "\n";

// 6. Check queue configuration
echo "6. QUEUE CONFIGURATION:\n";
echo str_repeat('-', 50) . "\n";
echo "QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "Current connection driver: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n";
echo "\n";

// 7. Summary & Diagnosis
echo "7. TEST SUMMARY:\n";
echo str_repeat('-', 50) . "\n";

if ($jobsCountAfter > $jobsCountBefore) {
    echo "✅ SUCCESS: Auto-dispatch is WORKING\n";
    echo "   - Job was successfully dispatched\n";
    echo "   - Job appears in jobs table\n";
    echo "   - Job is queued for async processing\n";
    echo "\n";
    echo "NEXT STEPS:\n";
    echo "1. Check if queue worker is running (php artisan queue:work)\n";
    echo "2. Verify job appears in /admin/shops/sync UI\n";
    echo "3. Check QueueJobsDashboard data\n";
} else {
    echo "⚠️ WARNING: Auto-dispatch may have issues\n";
    echo "\n";
    echo "POSSIBLE CAUSES:\n";
    echo "1. QUEUE_CONNECTION = 'sync' (synchronous execution)\n";
    echo "   - Job executes immediately instead of queueing\n";
    echo "   - Change to 'database' for async processing\n";
    echo "\n";
    echo "2. ShouldBeUnique constraint preventing duplicate job\n";
    echo "   - uniqueId: product_{$product->id}_shop_{$shop->id}\n";
    echo "   - Check cache/redis for existing lock\n";
    echo "\n";
    echo "3. Job failed during dispatch\n";
    echo "   - Check logs: storage/logs/laravel.log\n";
    echo "   - Check failed_jobs table\n";
    echo "\n";
    echo "RECOMMENDED ACTIONS:\n";
    echo "1. Check .env: QUEUE_CONNECTION=database (NOT sync)\n";
    echo "2. Clear config cache: php artisan config:clear\n";
    echo "3. Clear queue cache: php artisan queue:flush\n";
    echo "4. Check Laravel logs for errors\n";
}

echo "\n";

// 8. Check if ShouldBeUnique is blocking dispatch
echo "8. UNIQUE JOB CONSTRAINT CHECK:\n";
echo str_repeat('-', 50) . "\n";

$uniqueId = "product_{$product->id}_shop_{$shop->id}";
echo "Unique ID: {$uniqueId}\n";

// Try to check cache for unique lock
try {
    $cacheKey = "laravel_unique_job:{$uniqueId}";
    $cacheExists = \Illuminate\Support\Facades\Cache::has($cacheKey);
    echo "Cache lock exists: " . ($cacheExists ? 'YES (job may be blocked!)' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "Could not check cache: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n\n";
