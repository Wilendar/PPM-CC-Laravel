<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find first shop (regardless of status)
$shop = \App\Models\PrestaShopShop::first();

if (!$shop) {
    echo "No active shops found\n";
    exit(1);
}

echo "=== DISPATCH PullProductsFromPrestaShop ===\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n";

// Dispatch job
$job = new \App\Jobs\PullProductsFromPrestaShop($shop);
dispatch($job);

echo "Job dispatched to queue!\n";

// Check jobs table
sleep(2);

$pendingJobs = DB::table('jobs')->where('queue', 'default')->count();
echo "Pending jobs in queue: {$pendingJobs}\n";

if ($pendingJobs > 0) {
    echo "\n✅ SUCCESS: Job queued successfully\n";
    echo "Run queue worker manually: php artisan queue:work database --stop-when-empty\n";
} else {
    echo "\n⚠️ WARNING: Job not found in queue (may have been processed already)\n";
}
