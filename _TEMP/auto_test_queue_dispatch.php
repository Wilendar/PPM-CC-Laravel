<?php

/**
 * Automated Queue Job Test - NO UI REQUIRED
 * Dispatches SyncProductToPrestaShop job and monitors execution
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "=== AUTOMATED QUEUE JOB HANG TEST ===\n\n";

// Clear logs and old jobs
file_put_contents(storage_path('logs/laravel.log'), '');
DB::table('jobs')->delete();
DB::table('failed_jobs')->delete();
echo "✓ Cleaned logs and jobs table\n\n";

// Step 1: Find a product with active shop sync
echo "1. Finding test product with active shop sync...\n";

$productShopData = ProductShopData::whereHas('shop', function($q) {
    $q->where('is_active', true);
})
->whereHas('product', function($q) {
    $q->where('is_active', true);
})
->whereNotNull('prestashop_product_id')
->first();

if (!$productShopData) {
    echo "   ❌ No suitable test product found!\n";
    echo "   Need: Active product with active shop and prestashop_product_id\n\n";
    exit(1);
}

$product = $productShopData->product;
$shop = $productShopData->shop;

echo "   ✓ Found test product:\n";
echo "     Product ID: {$product->id}\n";
echo "     SKU: {$product->sku}\n";
echo "     Name: {$product->name}\n";
echo "     Shop: {$shop->name}\n";
echo "     PrestaShop ID: {$productShopData->prestashop_product_id}\n\n";

// Step 2: Dispatch job
echo "2. Dispatching SyncProductToPrestaShop job...\n";
Log::info('[AUTO TEST] Dispatching job', [
    'product_id' => $product->id,
    'shop_id' => $shop->id,
]);

try {
    SyncProductToPrestaShop::dispatch($product, $shop, auth()->id() ?? 1);
    echo "   ✓ Job dispatched successfully\n\n";
} catch (\Exception $e) {
    echo "   ❌ Failed to dispatch job: {$e->getMessage()}\n\n";
    exit(1);
}

// Step 3: Get job ID
$job = DB::table('jobs')->orderBy('id', 'desc')->first();
if (!$job) {
    echo "   ❌ Job not found in queue!\n\n";
    exit(1);
}

$jobId = $job->id;
$payload = json_decode($job->payload, true);
$jobClass = $payload['displayName'] ?? 'Unknown';

echo "3. Job queued:\n";
echo "   Job ID: {$jobId}\n";
echo "   Class: {$jobClass}\n";
echo "   Queue: {$job->queue}\n";
echo "   Available at: " . date('Y-m-d H:i:s', $job->available_at) . "\n\n";

// Step 4: Wait for queue worker to pick it up
echo "4. Waiting for queue worker to process job...\n";
echo "   (monitoring for 60 seconds)\n\n";

$startTime = time();
$maxWait = 60; // 60 seconds
$lastStatus = null;
$logCheckpoint = null;

while ((time() - $startTime) < $maxWait) {
    // Check job status
    $currentJob = DB::table('jobs')->where('id', $jobId)->first();
    $failedJob = DB::table('failed_jobs')
        ->where('payload', 'LIKE', "%{$product->id}%")
        ->where('payload', 'LIKE', "%{$shop->id}%")
        ->orderBy('id', 'desc')
        ->first();

    $status = null;
    if (!$currentJob && !$failedJob) {
        $status = 'COMPLETED';
    } elseif ($failedJob) {
        $status = 'FAILED';
    } elseif ($currentJob->reserved_at) {
        $status = 'PROCESSING (attempt ' . $currentJob->attempts . ')';
    } else {
        $status = 'PENDING';
    }

    // Print status change
    if ($status !== $lastStatus) {
        $elapsed = time() - $startTime;
        echo "   [{$elapsed}s] Status: {$status}\n";
        $lastStatus = $status;

        // Check logs for checkpoint
        $logs = file_get_contents(storage_path('logs/laravel.log'));
        $lines = explode("\n", $logs);
        $syncDebugLines = array_filter($lines, fn($l) => stripos($l, '[SYNC DEBUG]') !== false);
        if (!empty($syncDebugLines)) {
            $lastCheckpoint = end($syncDebugLines);
            if ($lastCheckpoint !== $logCheckpoint) {
                echo "        Last checkpoint: " . substr($lastCheckpoint, strrpos($lastCheckpoint, '[SYNC DEBUG]')) . "\n";
                $logCheckpoint = $lastCheckpoint;
            }
        }
    }

    // Exit if completed or failed
    if ($status === 'COMPLETED' || $status === 'FAILED') {
        break;
    }

    sleep(2);
}

echo "\n";

// Step 5: Final status
echo "5. Final Status:\n";
echo str_repeat('=', 80) . "\n";

$finalJob = DB::table('jobs')->where('id', $jobId)->first();
$finalFailed = DB::table('failed_jobs')
    ->where('payload', 'LIKE', "%{$product->id}%")
    ->orderBy('id', 'desc')
    ->first();

if (!$finalJob && !$finalFailed) {
    echo "✅ JOB COMPLETED SUCCESSFULLY\n";
    echo "   Duration: " . (time() - $startTime) . " seconds\n";
    $exitCode = 0;
} elseif ($finalFailed) {
    echo "❌ JOB FAILED\n";
    echo "   Exception: " . substr($finalFailed->exception, 0, 200) . "...\n";
    $exitCode = 1;
} elseif ($finalJob->reserved_at) {
    echo "❌ JOB HUNG (still processing after {$maxWait}s)\n";
    echo "   Attempts: {$finalJob->attempts}\n";
    echo "   Reserved at: " . date('Y-m-d H:i:s', $finalJob->reserved_at) . "\n";
    $exitCode = 1;
} else {
    echo "⚠️  JOB STILL PENDING (not picked up by worker)\n";
    echo "   Check if queue worker is running!\n";
    $exitCode = 1;
}

echo str_repeat('=', 80) . "\n\n";

// Step 6: Show logs
echo "6. Sync Debug Logs:\n";
echo str_repeat('=', 80) . "\n";
$logs = file_get_contents(storage_path('logs/laravel.log'));
$syncLines = array_filter(
    explode("\n", $logs),
    fn($line) => stripos($line, '[SYNC DEBUG]') !== false
        || stripos($line, 'SyncProductToPrestaShop') !== false
        || stripos($line, '[AUTO TEST]') !== false
);

if (!empty($syncLines)) {
    foreach ($syncLines as $line) {
        echo $line . "\n";
    }
} else {
    echo "⚠️  No sync debug logs found!\n";
    echo "\nShowing last 20 lines of log:\n";
    $allLines = explode("\n", $logs);
    foreach (array_slice($allLines, -20) as $line) {
        echo $line . "\n";
    }
}
echo str_repeat('=', 80) . "\n\n";

echo "=== TEST COMPLETE ===\n";
exit($exitCode);
