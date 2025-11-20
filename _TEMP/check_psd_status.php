<?php
// Check ProductShopData status after forced sync

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRODUCT_SHOP_DATA STATUS ===\n\n";

$testProducts = [11033, 11034];
$shopId = 1;

foreach ($testProducts as $productId) {
    $product = DB::table('products')->where('id', $productId)->first(['id', 'sku', 'name']);

    echo str_repeat('=', 80) . "\n";
    echo "PRODUCT: {$product->sku} (ID: {$productId})\n";
    echo "Name: {$product->name}\n";
    echo str_repeat('-', 80) . "\n";

    $psd = DB::table('product_shop_data')
        ->where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->first();

    if ($psd) {
        echo "ProductShopData Status:\n";
        echo "  PrestaShop ID: " . ($psd->prestashop_product_id ?? 'NULL') . "\n";
        echo "  Sync Status: " . ($psd->sync_status ?? 'NULL') . "\n";
        echo "  Checksum: " . ($psd->sync_checksum ?? 'NULL') . "\n";
        echo "  Last Sync: " . ($psd->last_sync_at ?? 'NULL') . "\n";
        echo "  Last Success Sync: " . ($psd->last_success_sync_at ?? 'NULL') . "\n\n";
    } else {
        echo "  ❌ NO ProductShopData record found!\n\n";
    }
}

echo "\n=== RECENT SYNC JOBS ===\n\n";

$jobs = DB::table('sync_jobs')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'job_type', 'source_type', 'source_id', 'target_id', 'status', 'created_at', 'completed_at']);

foreach ($jobs as $job) {
    $sourceId = is_numeric($job->source_id) ? (int)$job->source_id : $job->source_id;

    if ($job->source_type === 'product' && in_array($sourceId, $testProducts)) {
        echo "SyncJob ID: {$job->id}\n";
        echo "  Type: {$job->job_type}\n";
        echo "  Source: {$job->source_type} #{$job->source_id} → Target: Shop #{$job->target_id}\n";
        echo "  Status: {$job->status}\n";
        echo "  Created: {$job->created_at}\n";
        echo "  Completed: " . ($job->completed_at ?? 'NULL') . "\n";
        echo str_repeat('-', 80) . "\n";
    }
}
