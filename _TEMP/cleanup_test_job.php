<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== CLEANUP TEST JOB ===\n\n";

$deleted = DB::table('jobs')->delete();

echo "Deleted {$deleted} job(s) from Laravel queue\n";

// Reset product_shop_data sync_status back to 'synced'
$updated = DB::table('product_shop_data')
    ->where('product_id', 11017)
    ->where('shop_id', 1)
    ->update(['sync_status' => 'synced']);

echo "Reset sync_status for Product 11017, Shop 1\n";

echo "\n✅ Cleanup complete\n\n";
