<?php
// Check recent sync jobs

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncJob;
use Illuminate\Support\Facades\DB;

echo "=== RECENT SYNC JOBS ===\n\n";

$jobs = SyncJob::orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'product_id', 'shop_id', 'status', 'operation', 'external_id', 'created_at', 'updated_at']);

foreach ($jobs as $job) {
    echo "ID: {$job->id} | Product: {$job->product_id} | Shop: {$job->shop_id}\n";
    echo "  Status: {$job->status} | Operation: {$job->operation}\n";
    echo "  External ID: " . ($job->external_id ?? 'NULL') . "\n";
    echo "  Created: {$job->created_at} | Updated: {$job->updated_at}\n";
    echo str_repeat('-', 80) . "\n";
}

echo "\n=== PRODUCT_SHOP_DATA STATUS ===\n\n";

$testProducts = [11033, 11034];
$shopId = 1;

foreach ($testProducts as $productId) {
    $psd = DB::table('product_shop_data')
        ->where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->first(['product_id', 'prestashop_product_id', 'sync_status', 'sync_checksum', 'last_sync_at']);

    if ($psd) {
        echo "Product {$productId}:\n";
        echo "  PrestaShop ID: " . ($psd->prestashop_product_id ?? 'NULL') . "\n";
        echo "  Sync Status: " . ($psd->sync_status ?? 'NULL') . "\n";
        echo "  Checksum: " . ($psd->sync_checksum ?? 'NULL') . "\n";
        echo "  Last Sync: " . ($psd->last_sync_at ?? 'NULL') . "\n";
        echo str_repeat('-', 80) . "\n";
    }
}
