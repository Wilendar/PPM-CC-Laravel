#!/usr/bin/env php
<?php

/**
 * List PrestaShop shops (safe - bez decrypt API keys)
 * Phase 5.5 E2E Testing - Task 1 helper
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PrestaShop Shops (Raw DB Data) ===" . PHP_EOL;

$shops = DB::table('prestashop_shops')->select('id', 'name', 'url', 'is_active', 'prestashop_version', 'created_at')->get();

echo "Total shops: " . $shops->count() . PHP_EOL . PHP_EOL;

foreach ($shops as $shop) {
    echo "----------------------------------------" . PHP_EOL;
    echo "ID: {$shop->id}" . PHP_EOL;
    echo "Name: {$shop->name}" . PHP_EOL;
    echo "URL: {$shop->url}" . PHP_EOL;
    echo "Active: " . ($shop->is_active ? 'Yes' : 'No') . PHP_EOL;
    echo "Version: {$shop->prestashop_version}" . PHP_EOL;
    echo "Created: {$shop->created_at}" . PHP_EOL;
}
