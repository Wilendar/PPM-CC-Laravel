<?php

/**
 * CHECK: Product Sync Status in PPM
 *
 * Purpose: Diagnose why sync is stuck at "Oczekuje" (pending)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== PPM SYNC STATUS DIAGNOSTICS ===\n\n";

try {
    // Step 1: Check product_shop_data table
    echo "Step 1: Checking product_shop_data (last 10 records)...\n";
    $syncData = DB::table('product_shop_data')
        ->select([
            'id',
            'product_id',
            'shop_id',
            'sync_status',
            'is_published',
            'prestashop_product_id',
            'last_success_sync_at',
            'sync_direction',
            'retry_count',
            'max_retries',
            'priority'
        ])
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();

    if ($syncData->isEmpty()) {
        echo "⚠️ No product_shop_data records found!\n\n";
    } else {
        echo "✓ Found " . $syncData->count() . " records:\n\n";

        foreach ($syncData as $data) {
            echo "Product ID: {$data->product_id} | Shop ID: {$data->shop_id}\n";
            echo "  Status: {$data->sync_status}\n";
            echo "  Published: " . ($data->is_published ? 'YES' : 'NO') . "\n";
            echo "  PrestaShop Product ID: " . ($data->prestashop_product_id ?? 'NULL') . "\n";
            echo "  Last Success Sync: " . ($data->last_success_sync_at ?? 'NEVER') . "\n";
            echo "  Sync Direction: " . ($data->sync_direction ?? 'N/A') . "\n";
            echo "  Retry Count: {$data->retry_count}/{$data->max_retries}\n";
            echo "  Priority: " . ($data->priority ?? 'default') . "\n";
            echo "\n";
        }
    }

    // Step 2: Check sync_jobs table
    echo "Step 2: Checking sync_jobs (last 10 jobs)...\n";
    $syncJobs = DB::table('sync_jobs')
        ->select([
            'id',
            'job_id',
            'job_type',
            'job_name',
            'source_type',
            'source_id',
            'target_type',
            'target_id',
            'status',
            'total_items',
            'processed_items',
            'successful_items',
            'failed_items',
            'progress_percentage',
            'started_at',
            'completed_at',
            'error_message'
        ])
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();

    if ($syncJobs->isEmpty()) {
        echo "⚠️ No sync_jobs records found!\n\n";
    } else {
        echo "✓ Found " . $syncJobs->count() . " jobs:\n\n";

        foreach ($syncJobs as $job) {
            echo "Job ID: {$job->id} | Type: {$job->job_type}\n";
            echo "  Name: {$job->job_name}\n";
            echo "  Source: {$job->source_type} (ID: {$job->source_id})\n";
            echo "  Target: {$job->target_type} (ID: {$job->target_id})\n";
            echo "  Status: {$job->status}\n";
            echo "  Progress: {$job->processed_items}/{$job->total_items} ({$job->progress_percentage}%)\n";
            echo "  Success/Failed: {$job->successful_items}/{$job->failed_items}\n";
            echo "  Started: " . ($job->started_at ?? 'NOT STARTED') . "\n";
            echo "  Completed: " . ($job->completed_at ?? 'NOT COMPLETED') . "\n";

            if ($job->error_message) {
                echo "  ❌ Error: {$job->error_message}\n";
            }
            echo "\n";
        }
    }

    // Step 3: Check Laravel logs for recent errors
    echo "Step 3: Checking recent Laravel logs...\n";
    $logFile = storage_path('logs/laravel.log');

    if (file_exists($logFile)) {
        $logLines = file($logFile);
        $recentErrors = [];

        // Get last 50 lines
        $lastLines = array_slice($logLines, -50);

        foreach ($lastLines as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                $recentErrors[] = $line;
            }
        }

        if (!empty($recentErrors)) {
            echo "⚠️ Found " . count($recentErrors) . " recent errors:\n";
            foreach (array_slice($recentErrors, -5) as $error) {
                echo "  " . trim($error) . "\n";
            }
            echo "\n";
        } else {
            echo "✓ No recent errors in Laravel log\n\n";
        }
    } else {
        echo "⚠️ Laravel log file not found: {$logFile}\n\n";
    }

    // Step 4: Check if queue workers are running
    echo "Step 4: Checking queue configuration...\n";
    $queueDriver = config('queue.default');
    echo "Queue Driver: {$queueDriver}\n";

    if ($queueDriver === 'sync') {
        echo "⚠️ Queue driver is 'sync' (synchronous) - jobs run immediately\n";
    } elseif ($queueDriver === 'database') {
        echo "✓ Queue driver is 'database' - checking jobs table...\n";

        $pendingJobs = DB::table('jobs')->count();
        echo "Pending jobs in queue: {$pendingJobs}\n";

        if ($pendingJobs > 0) {
            echo "⚠️ There are {$pendingJobs} jobs waiting to be processed!\n";
            echo "Run: php artisan queue:work\n";
        }
    }
    echo "\n";

    echo "=== DIAGNOSTICS COMPLETE ===\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
