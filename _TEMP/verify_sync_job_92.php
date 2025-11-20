<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== WERYFIKACJA SYNC_JOB #92 ===\n\n";

$job = DB::table('sync_jobs')->where('id', 92)->first();

if (!$job) {
    echo "❌ SyncJob #92 not found\n";
    exit;
}

echo "SyncJob #92 Details:\n";
echo "  ID: {$job->id}\n";
echo "  Job ID (UUID): {$job->job_id}\n";
echo "  Type: {$job->job_type}\n";
echo "  Name: {$job->job_name}\n";
echo "  Status: {$job->status}\n";
echo "  Source: {$job->source_type} (ID: {$job->source_id})\n";
echo "  Target: {$job->target_type} (ID: {$job->target_id})\n";
echo "  User ID: " . ($job->user_id ?? 'NULL') . "\n";
echo "  Progress: {$job->progress_percentage}%\n";
echo "  Items: {$job->processed_items}/{$job->total_items}\n";
echo "  Success: {$job->successful_items}\n";
echo "  Failed: {$job->failed_items}\n";
echo "  Created: {$job->created_at}\n";
echo "  Started: " . ($job->started_at ?? 'NULL') . "\n";
echo "  Completed: " . ($job->completed_at ?? 'NULL') . "\n";
echo "  Duration: " . ($job->duration_seconds ?? 0) . "s\n";
echo "  Queue Job ID: " . ($job->queue_job_id ?? 'NULL') . "\n";
echo "  Error: " . ($job->error_message ?? 'NULL') . "\n\n";

// Check product_shop_data sync status
echo "=== PRODUCT_SHOP_DATA STATUS ===\n\n";

$shopData = DB::table('product_shop_data')
    ->where('product_id', 11017)
    ->where('shop_id', 1)
    ->first();

if ($shopData) {
    echo "Product ID 11017, Shop ID 1:\n";
    echo "  Sync Status: {$shopData->sync_status}\n";
    echo "  Last Sync: " . ($shopData->last_sync_at ?? 'NULL') . "\n";
    echo "  PrestaShop Product ID: " . ($shopData->prestashop_product_id ?? 'NULL') . "\n";
    echo "  Error: " . ($shopData->error_message ?? 'NULL') . "\n";
} else {
    echo "❌ No product_shop_data found\n";
}

echo "\n";
