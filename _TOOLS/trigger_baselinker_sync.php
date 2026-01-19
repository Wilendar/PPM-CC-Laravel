<?php
/**
 * Trigger Baselinker Sync Job for Testing
 * ETAP_08.5 - Test script for sync diagnostics
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ERPConnection;
use App\Jobs\ERP\SyncProductToERP;
use Illuminate\Support\Facades\DB;

echo "=== TRIGGER BASELINKER SYNC TEST ===\n\n";

// Get ERP Connection
$connection = ERPConnection::where('erp_type', 'baselinker')->first();
if (!$connection) {
    die("ERROR: No Baselinker connection found!\n");
}
echo "Connection: {$connection->instance_name} (ID: {$connection->id})\n";

// Get a product to sync
$product = Product::first();
if (!$product) {
    die("ERROR: No products found!\n");
}
echo "Product: {$product->sku} - {$product->name} (ID: {$product->id})\n\n";

// Check current queue status
$jobsBefore = DB::table('jobs')->count();
echo "Jobs in queue BEFORE: {$jobsBefore}\n";

// Dispatch the sync job
echo "Dispatching SyncProductToERP job...\n";
SyncProductToERP::dispatch($product, $connection);

// Check queue again
sleep(1);
$jobsAfter = DB::table('jobs')->count();
echo "Jobs in queue AFTER: {$jobsAfter}\n\n";

if ($jobsAfter > $jobsBefore) {
    echo "SUCCESS: Job dispatched! Run queue worker to process:\n";
    echo "php artisan queue:work database --queue=erp_default,erp_high,default --once --verbose\n";
} else {
    echo "WARNING: Job count unchanged - may have been processed synchronously\n";
}

echo "\n=== CHECK RECENT LOGS ===\n";
$logs = DB::table('integration_logs')
    ->where('integration_type', 'baselinker')
    ->orderBy('logged_at', 'desc')
    ->limit(3)
    ->get();

foreach ($logs as $log) {
    echo "[{$log->logged_at}] {$log->log_level} | {$log->operation}\n";
    echo "  {$log->description}\n";
    if ($log->error_message) {
        echo "  ERROR: " . substr($log->error_message, 0, 100) . "\n";
    }
}
