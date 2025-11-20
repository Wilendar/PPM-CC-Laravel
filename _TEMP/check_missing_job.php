<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== BADANIE PROBLEMU: JOB DLA TEST-AUTOFIX-1762422508 ===\n\n";

// Find product
$product = DB::table('products')
    ->where('sku', 'TEST-AUTOFIX-1762422508')
    ->first();

if (!$product) {
    echo "❌ Product not found: TEST-AUTOFIX-1762422508\n";
    exit;
}

echo "✅ Product found:\n";
echo "  ID: {$product->id}\n";
echo "  SKU: {$product->sku}\n";
echo "  Name: {$product->name}\n\n";

// Find related sync jobs
echo "=== SYNC JOBS FOR THIS PRODUCT ===\n\n";
$jobs = DB::table('sync_jobs')
    ->where('product_id', $product->id)
    ->orderBy('created_at', 'desc')
    ->get();

if ($jobs->isEmpty()) {
    echo "❌ No sync jobs found for product_id={$product->id}\n";
} else {
    foreach ($jobs as $job) {
        echo "Job ID: {$job->id}\n";
        echo "  Status: {$job->status}\n";
        echo "  Shop ID: {$job->prestashop_shop_id}\n";
        echo "  Created: {$job->created_at}\n";
        echo "  Updated: {$job->updated_at}\n";
        echo "  User ID: " . ($job->user_id ?? 'NULL') . "\n";
        echo "\n";
    }
}

// Check what SyncController query would return
echo "=== SIMULACJA QUERY Z SyncController ===\n\n";

$controllerJobs = DB::table('sync_jobs as sj')
    ->join('prestashop_shops as ps', 'sj.prestashop_shop_id', '=', 'ps.id')
    ->leftJoin('products as p', 'sj.product_id', '=', 'p.id')
    ->select(
        'sj.id',
        'sj.status',
        'sj.prestashop_shop_id',
        'sj.product_id',
        'sj.created_at',
        'sj.updated_at',
        'ps.name as shop_name',
        'p.sku as product_sku',
        'p.name as product_name'
    )
    ->whereIn('sj.status', ['pending', 'running', 'paused'])
    ->orderBy('sj.created_at', 'desc')
    ->limit(50)
    ->get();

echo "Jobs returned by controller query (status IN pending/running/paused):\n";
echo "Total: " . $controllerJobs->count() . "\n\n";

foreach ($controllerJobs as $job) {
    $match = ($job->product_id == $product->id) ? '✅ MATCH' : '';
    echo "Job ID: {$job->id} - {$job->status} - Product: {$job->product_sku} {$match}\n";
}

if ($controllerJobs->where('product_id', $product->id)->isEmpty()) {
    echo "\n⚠️ PROBLEM: Job dla TEST-AUTOFIX-1762422508 NIE JEST w rezultatach query!\n";
}

echo "\n";
