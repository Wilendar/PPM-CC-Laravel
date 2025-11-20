<?php
/**
 * Variant Database Schema Verification
 *
 * Checks table structure, indexes, relationships
 * Run: php _TEMP/verify_variant_schema.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VARIANT DATABASE SCHEMA VERIFICATION ===\n\n";

// Test 1: Check product_variants table exists
echo "--- TEST 1: TABLE EXISTENCE ---\n";
if (Schema::hasTable('product_variants')) {
    echo "✅ product_variants table exists\n";
} else {
    echo "❌ product_variants table NOT FOUND\n";
    exit(1);
}

// Test 2: Check columns
echo "\n--- TEST 2: TABLE COLUMNS ---\n";
$expectedColumns = [
    'id', 'product_id', 'sku', 'name', 'base_price', 'stock_quantity',
    'status', 'created_at', 'updated_at'
];

$actualColumns = Schema::getColumnListing('product_variants');

foreach ($expectedColumns as $col) {
    if (in_array($col, $actualColumns)) {
        echo "✅ Column '{$col}' exists\n";
    } else {
        echo "❌ Column '{$col}' MISSING\n";
    }
}

// Additional columns (not in expectedColumns)
$extraColumns = array_diff($actualColumns, $expectedColumns);
if (!empty($extraColumns)) {
    echo "\n📋 Additional columns found: " . implode(', ', $extraColumns) . "\n";
}

// Test 3: Check indexes
echo "\n--- TEST 3: INDEXES ---\n";
$indexes = DB::select("SHOW INDEX FROM product_variants");
$indexNames = array_unique(array_column($indexes, 'Key_name'));

echo "Indexes found:\n";
foreach ($indexNames as $idx) {
    echo "  - {$idx}\n";
}

// Test 4: Check foreign keys
echo "\n--- TEST 4: FOREIGN KEYS ---\n";
$fks = DB::select("
    SELECT
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'product_variants'
      AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (!empty($fks)) {
    foreach ($fks as $fk) {
        echo "✅ FK: {$fk->CONSTRAINT_NAME}\n";
        echo "   {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    }
} else {
    echo "⚠️ No foreign keys found\n";
}

// Test 5: Check products.has_variants column
echo "\n--- TEST 5: PRODUCTS TABLE COLUMNS ---\n";
if (Schema::hasColumn('products', 'has_variants')) {
    echo "✅ products.has_variants column exists\n";
} else {
    echo "❌ products.has_variants column MISSING\n";
}

if (Schema::hasColumn('products', 'default_variant_id')) {
    echo "✅ products.default_variant_id column exists\n";
} else {
    echo "❌ products.default_variant_id column MISSING\n";
}

// Test 6: Sample data check
echo "\n--- TEST 6: SAMPLE DATA ---\n";
$variantCount = DB::table('product_variants')->count();
echo "Total variants in database: {$variantCount}\n";

if ($variantCount > 0) {
    $sample = DB::table('product_variants')->first();
    echo "\nSample variant:\n";
    echo "  ID: {$sample->id}\n";
    echo "  Product ID: {$sample->product_id}\n";
    echo "  SKU: {$sample->sku}\n";
    echo "  Name: {$sample->name}\n";
    echo "  Base Price: {$sample->base_price}\n";
    echo "  Stock: {$sample->stock_quantity}\n";
}

// Test 7: Products with variants
echo "\n--- TEST 7: PRODUCTS WITH VARIANTS ---\n";
$productsWithVariants = DB::table('products')
    ->where('has_variants', true)
    ->count();

echo "Products with has_variants=true: {$productsWithVariants}\n";

$productsWithDefaultVariant = DB::table('products')
    ->whereNotNull('default_variant_id')
    ->count();

echo "Products with default_variant_id set: {$productsWithDefaultVariant}\n";

echo "\n=== VERIFICATION COMPLETED ===\n";
