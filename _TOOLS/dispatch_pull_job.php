<?php

/**
 * Manual job dispatcher for testing PullProductsFromPrestaShop
 * Usage: php dispatch_pull_job.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\PullProductsFromPrestaShop;
use App\Models\PrestaShopShop;

echo "=== Manual PullProductsFromPrestaShop Dispatcher ===\n\n";

// Get first active shop with auto_sync
$shop = PrestaShopShop::where('is_active', true)
    ->where('auto_sync_products', true)
    ->first();

if ($shop) {
    echo "Found shop: {$shop->name} (ID: {$shop->id})\n";
    echo "Connection status: {$shop->connection_status}\n";
    echo "API URL: {$shop->api_url}\n\n";

    echo "Dispatching PullProductsFromPrestaShop job...\n";
    PullProductsFromPrestaShop::dispatch($shop);
    echo "Job dispatched successfully!\n\n";

    echo "Monitor logs with:\n";
    echo "tail -f storage/logs/laravel.log | grep -E '(PullProducts|skipped|date_upd)'\n";
} else {
    echo "ERROR: No active shop with auto_sync_products=true found!\n";

    // List available shops
    $allShops = PrestaShopShop::all(['id', 'name', 'is_active', 'auto_sync_products', 'connection_status']);
    echo "\nAvailable shops:\n";
    foreach ($allShops as $s) {
        echo "  - [{$s->id}] {$s->name} | active: " . ($s->is_active ? 'yes' : 'no') .
             " | auto_sync: " . ($s->auto_sync_products ? 'yes' : 'no') .
             " | status: {$s->connection_status}\n";
    }
}

echo "\nDone.\n";
