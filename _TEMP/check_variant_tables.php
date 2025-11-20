<?php
/**
 * Check Variant Tables Existence
 *
 * Verifies all variant-related tables exist on production
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== VARIANT TABLES CHECK ===\n\n";

$tables = [
    'product_variants',
    'variant_attributes',
    'variant_prices',
    'variant_stock',
    'variant_images',
];

foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo ($exists ? '✅' : '❌') . " Table '{$table}' " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n=== COMPLETED ===\n";
