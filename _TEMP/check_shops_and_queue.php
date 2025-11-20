<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;

echo "=== AVAILABLE PRESTASHOP SHOPS ===" . PHP_EOL . PHP_EOL;

$shops = PrestaShopShop::select('id', 'name', 'connection_status', 'last_sync_at')
    ->orderBy('id')
    ->get();

echo "Total shops: " . $shops->count() . PHP_EOL . PHP_EOL;

foreach ($shops as $shop) {
    echo sprintf(
        "ID: %d | Name: %s | Status: %s | Last Sync: %s" . PHP_EOL,
        $shop->id,
        $shop->name,
        $shop->connection_status,
        $shop->last_sync_at ? $shop->last_sync_at->format('Y-m-d H:i') : 'Never'
    );
}

echo PHP_EOL . "=== QUEUE STATUS ===" . PHP_EOL . PHP_EOL;

$jobsCount = DB::table('jobs')->count();
$failedCount = DB::table('failed_jobs')->count();

echo "Active jobs in queue: {$jobsCount}" . PHP_EOL;
echo "Failed jobs: {$failedCount}" . PHP_EOL;
