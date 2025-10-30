#!/usr/bin/env php
<?php

/**
 * Check PrestaShop shops in database
 * Phase 5.5 E2E Testing - Task 1 helper
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

echo "=== PrestaShop Shops in Database ===" . PHP_EOL;
echo "Total shops: " . PrestaShopShop::count() . PHP_EOL . PHP_EOL;

if (PrestaShopShop::count() > 0) {
    echo "Shop Details:" . PHP_EOL;
    foreach (PrestaShopShop::all() as $shop) {
        echo "----------------------------------------" . PHP_EOL;
        echo "ID: {$shop->id}" . PHP_EOL;
        echo "Name: {$shop->name}" . PHP_EOL;
        echo "URL: {$shop->url}" . PHP_EOL;
        echo "Active: " . ($shop->is_active ? 'Yes' : 'No') . PHP_EOL;
        echo "Version: {$shop->prestashop_version}" . PHP_EOL;
        echo "API Key: " . (strlen($shop->api_key) > 0 ? '[SET]' : '[NOT SET]') . PHP_EOL;
        echo "Created: {$shop->created_at}" . PHP_EOL;
    }
} else {
    echo "⚠️  No PrestaShop shops found in database!" . PHP_EOL;
    echo "Please create test shop first." . PHP_EOL;
}
