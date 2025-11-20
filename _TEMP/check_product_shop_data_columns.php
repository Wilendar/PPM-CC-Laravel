<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== PRODUCT_SHOP_DATA TABLE STRUCTURE ===\n\n";

$columns = Schema::getColumnListing('product_shop_data');

echo "Total columns: " . count($columns) . "\n\n";

// Filter price-related columns
$priceColumns = array_filter($columns, function($col) {
    return str_contains(strtolower($col), 'price') || str_contains(strtolower($col), 'pending');
});

echo "PRICE & PENDING COLUMNS:\n";
foreach ($priceColumns as $col) {
    echo "  - {$col}\n";
}

echo "\n";

// Get sample record
$sample = DB::table('product_shop_data')
    ->where('product_id', 11017)
    ->where('shop_id', 1)
    ->first();

if ($sample) {
    echo "Sample record (Product 11017, Shop 1):\n";
    foreach ($sample as $key => $value) {
        if (str_contains(strtolower($key), 'price') || str_contains(strtolower($key), 'pending')) {
            echo "  {$key}: " . ($value ?? 'NULL') . "\n";
        }
    }
}

echo "\n";
