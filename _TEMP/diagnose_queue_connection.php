<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PHASE 1: SYNC_JOBS TABLE ===\n";
$syncJobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'job_id', 'job_type', 'job_name', 'source_type', 'source_id', 'target_type', 'target_id', 'status', 'created_at', 'completed_at', 'total_items', 'processed_items', 'successful_items', 'failed_items']);

echo "Total sync_jobs entries: " . DB::table('sync_jobs')->count() . "\n";
echo "Recent sync_jobs:\n";
echo json_encode($syncJobs->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== PHASE 2: WAREHOUSES TABLE ===\n";
$warehouses = DB::table('warehouses')
    ->get(['id', 'code', 'name', 'is_default', 'is_active', 'prestashop_mapping']);

echo "Total warehouses: " . DB::table('warehouses')->count() . "\n";
echo "Warehouses data:\n";
echo json_encode($warehouses->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== PHASE 3: LARAVEL JOBS TABLE ===\n";
$laravelJobs = DB::table('jobs')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'queue', 'attempts', 'created_at']);

echo "Total jobs in queue: " . DB::table('jobs')->count() . "\n";
echo "Recent Laravel jobs:\n";
foreach ($laravelJobs as $job) {
    $payload = DB::table('jobs')->where('id', $job->id)->value('payload');
    $payloadData = json_decode($payload, true);
    echo "Job ID: {$job->id}, Queue: {$job->queue}, Attempts: {$job->attempts}, Created: {$job->created_at}\n";
    if (isset($payloadData['displayName'])) {
        echo "  Type: {$payloadData['displayName']}\n";
    }
}
echo "\n";

echo "=== PHASE 4: PRODUCT_STOCK RECENT UPDATES ===\n";
$recentStockUpdates = DB::table('product_stock')
    ->where('updated_at', '>', now()->subHours(24))
    ->limit(10)
    ->get(['id', 'product_id', 'warehouse_id', 'quantity', 'erp_mapping', 'updated_at']);

echo "Recent stock updates (last 24h): " . DB::table('product_stock')->where('updated_at', '>', now()->subHours(24))->count() . "\n";
if ($recentStockUpdates->count() > 0) {
    foreach ($recentStockUpdates as $stock) {
        $product = DB::table('products')->where('id', $stock->product_id)->first(['sku', 'name']);
        echo "Product: {$product->sku} - {$product->name}\n";
        echo "  Warehouse ID: {$stock->warehouse_id}, Quantity: {$stock->quantity}\n";
        echo "  ERP Mapping: {$stock->erp_mapping}\n";
        echo "  Updated: {$stock->updated_at}\n";
    }
} else {
    echo "No stock updates in last 24 hours\n";
}
echo "\n";

echo "=== PHASE 5: FAILED_JOBS TABLE ===\n";
$failedJobs = DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get(['id', 'queue', 'exception', 'failed_at']);

echo "Total failed jobs: " . DB::table('failed_jobs')->count() . "\n";
if ($failedJobs->count() > 0) {
    echo "Recent failed jobs:\n";
    foreach ($failedJobs as $job) {
        $payload = DB::table('failed_jobs')->where('id', $job->id)->value('payload');
        $payloadData = json_decode($payload, true);
        echo "Failed Job ID: {$job->id}, Queue: {$job->queue}, Failed: {$job->failed_at}\n";
        if (isset($payloadData['displayName'])) {
            echo "  Type: {$payloadData['displayName']}\n";
        }
        echo "  Exception (first 200 chars): " . substr($job->exception, 0, 200) . "...\n";
    }
} else {
    echo "No failed jobs\n";
}
echo "\n";

echo "=== PHASE 6: CHECK PRESTASHOP_SHOPS ===\n";
$shops = DB::table('prestashop_shops')
    ->get(['id', 'name', 'api_url', 'is_active']);

echo "PrestaShop shops:\n";
echo json_encode($shops->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== DIAGNOSTICS COMPLETE ===\n";
