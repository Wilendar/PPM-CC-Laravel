#!/usr/bin/env php
<?php
// Quick check of category tables
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CATEGORY TABLES ANALYSIS ===\n\n";

$oldCount = DB::table('product_shop_categories')->count();
$newPerShop = DB::table('product_categories')->whereNotNull('shop_id')->count();
$newDefault = DB::table('product_categories')->whereNull('shop_id')->count();

echo "1. product_shop_categories (OLD): {$oldCount} records\n";
echo "2. product_categories (shop_id NOT NULL): {$newPerShop} records\n";
echo "3. product_categories (shop_id NULL): {$newDefault} records\n\n";

if ($oldCount > 0) {
    echo "Sample from product_shop_categories:\n";
    $sample = DB::table('product_shop_categories')
        ->select('product_id', 'shop_id', 'category_id', 'is_primary')
        ->limit(3)
        ->get();
    foreach ($sample as $row) {
        echo "  Product {$row->product_id}, Shop {$row->shop_id}, Category {$row->category_id}, Primary: " . ($row->is_primary ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
}

if ($newPerShop > 0) {
    echo "Sample from product_categories (per-shop):\n";
    $sample = DB::table('product_categories')
        ->select('product_id', 'shop_id', 'category_id', 'is_primary')
        ->whereNotNull('shop_id')
        ->limit(3)
        ->get();
    foreach ($sample as $row) {
        echo "  Product {$row->product_id}, Shop {$row->shop_id}, Category {$row->category_id}, Primary: " . ($row->is_primary ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
}

// Check overlaps
if ($oldCount > 0 && $newPerShop > 0) {
    $overlaps = DB::table('product_shop_categories as psc')
        ->join('product_categories as pc', function($join) {
            $join->on('psc.product_id', '=', 'pc.product_id')
                 ->on('psc.shop_id', '=', 'pc.shop_id')
                 ->on('psc.category_id', '=', 'pc.category_id');
        })
        ->whereNotNull('pc.shop_id')
        ->count();

    echo "Overlaps (same product+shop+category in both): {$overlaps}\n\n";
}

echo "RECOMMENDATION:\n";
if ($oldCount > 0 && $newPerShop == 0) {
    echo "  => SIMPLE MIGRATION: Move all from OLD to NEW table\n";
} elseif ($oldCount == 0) {
    echo "  => CLEAN: OLD table empty, just drop it\n";
} else {
    echo "  => MERGE NEEDED: Both tables have data\n";
}
