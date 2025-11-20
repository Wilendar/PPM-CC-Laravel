<?php
/**
 * Variant CRUD Testing Script
 *
 * Tests ProductFormVariants trait methods directly
 * Run: php _TEMP/test_variant_crud.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== VARIANT CRUD TESTING SCRIPT ===\n\n";

$productId = 10969;
$product = Product::find($productId);

if (!$product) {
    echo "❌ Product $productId not found!\n";
    exit(1);
}

echo "✅ Product found: {$product->sku} - {$product->name}\n\n";

// Test 1: CREATE VARIANT
echo "--- TEST 1: CREATE VARIANT ---\n";
$timestamp = date('YmdHis');
$testSku = "TEST_VARIANT_{$timestamp}";

try {
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $testSku,
        'name' => "Test Variant {$timestamp}",
        'base_price' => 100.00,
        'stock_quantity' => 10,
        'status' => 'active',
    ]);

    echo "✅ Variant created: ID={$variant->id}, SKU={$variant->sku}\n";

    // Check has_variants flag
    $product->refresh();
    echo "✅ has_variants flag: " . ($product->has_variants ? 'true' : 'false') . "\n";

} catch (\Exception $e) {
    echo "❌ CREATE failed: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";

// Test 2: READ VARIANT
echo "--- TEST 2: READ VARIANT ---\n";
$readVariant = ProductVariant::where('sku', $testSku)->first();
if ($readVariant) {
    echo "✅ Variant read: ID={$readVariant->id}, SKU={$readVariant->sku}\n";
    echo "   Name: {$readVariant->name}\n";
    echo "   Price: {$readVariant->base_price}\n";
    echo "   Stock: {$readVariant->stock_quantity}\n";
} else {
    echo "❌ READ failed: Variant not found\n";
}

echo "\n";

// Test 3: UPDATE VARIANT
echo "--- TEST 3: UPDATE VARIANT ---\n";
try {
    $variant->update([
        'name' => "Test Variant {$timestamp} EDITED",
        'base_price' => 150.00,
    ]);

    $variant->refresh();
    echo "✅ Variant updated:\n";
    echo "   New name: {$variant->name}\n";
    echo "   New price: {$variant->base_price}\n";

} catch (\Exception $e) {
    echo "❌ UPDATE failed: {$e->getMessage()}\n";
}

echo "\n";

// Test 4: SET DEFAULT VARIANT
echo "--- TEST 4: SET DEFAULT VARIANT ---\n";
try {
    $product->update(['default_variant_id' => $variant->id]);
    $product->refresh();

    echo "✅ Default variant set: ID={$product->default_variant_id}\n";

} catch (\Exception $e) {
    echo "❌ SET DEFAULT failed: {$e->getMessage()}\n";
}

echo "\n";

// Test 5: DUPLICATE VARIANT
echo "--- TEST 5: DUPLICATE VARIANT ---\n";
try {
    $duplicated = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $variant->sku . '_COPY',
        'name' => $variant->name . ' (Copy)',
        'base_price' => $variant->base_price,
        'stock_quantity' => $variant->stock_quantity,
        'status' => $variant->status,
    ]);

    echo "✅ Variant duplicated: ID={$duplicated->id}, SKU={$duplicated->sku}\n";

} catch (\Exception $e) {
    echo "❌ DUPLICATE failed: {$e->getMessage()}\n";
}

echo "\n";

// Test 6: LIST ALL VARIANTS
echo "--- TEST 6: LIST ALL VARIANTS ---\n";
$allVariants = ProductVariant::where('product_id', $product->id)->get();
echo "Total variants for product: {$allVariants->count()}\n";
foreach ($allVariants as $v) {
    $isDefault = ($v->id === $product->default_variant_id) ? '⭐' : '';
    echo "  {$isDefault} [{$v->id}] {$v->sku} - {$v->name} (Price: {$v->base_price})\n";
}

echo "\n";

// Test 7: DELETE VARIANTS (cleanup)
echo "--- TEST 7: CLEANUP (DELETE TEST VARIANTS) ---\n";
$deletedCount = 0;
foreach ($allVariants as $v) {
    if (str_starts_with($v->sku, 'TEST_VARIANT_')) {
        try {
            $v->delete();
            echo "✅ Deleted variant: {$v->sku}\n";
            $deletedCount++;
        } catch (\Exception $e) {
            echo "❌ DELETE failed for {$v->sku}: {$e->getMessage()}\n";
        }
    }
}

echo "Total deleted: {$deletedCount}\n\n";

// Final check
$product->refresh();
$remainingVariants = ProductVariant::where('product_id', $product->id)->count();
echo "Remaining variants: {$remainingVariants}\n";
echo "has_variants flag: " . ($product->has_variants ? 'true' : 'false') . "\n";

echo "\n=== TESTING COMPLETED ===\n";
