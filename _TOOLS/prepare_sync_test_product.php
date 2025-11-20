<?php

/**
 * Prepare Sync Test Product
 *
 * Creates a test product with all required relationships:
 * - Categories
 * - Prices (price groups)
 * - Stock (warehouses)
 *
 * This product will be used for sync logic verification (FAZA 3B.3)
 *
 * Usage:
 *   php _TOOLS/prepare_sync_test_product.php
 *
 * Output:
 *   Product ID, SKU, and relationships created
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductType;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== PREPARE SYNC TEST PRODUCT ===\n\n";

try {
    DB::beginTransaction();

    // Get product type (default to "Czesc zamiennicza" if exists)
    $productType = ProductType::where('slug', 'czesc-zamiennicza')
        ->orWhere('id', 2)
        ->first();

    if (!$productType) {
        echo "⚠️ WARNING: No product type found, using ID 1 as fallback\n";
        $productTypeId = 1;
    } else {
        $productTypeId = $productType->id;
        echo "✅ Product Type: {$productType->name} (ID: {$productTypeId})\n";
    }

    // Generate unique SKU
    $timestamp = time();
    $sku = "TEST-SYNC-{$timestamp}";

    // Create test product
    $product = Product::create([
        'sku' => $sku,
        'name' => 'Test Product For Sync Verification',
        'product_type_id' => $productTypeId,
        'short_description' => 'Short description for sync test',
        'long_description' => '<p>This is a detailed description for sync verification testing. It includes HTML formatting.</p>',
        'is_active' => true,
        'weight' => 1.5,
        'width' => 10.0,
        'height' => 20.0,
        'length' => 30.0,
        'ean' => "590{$timestamp}",
        'tax_rate' => 23.00,
        'manufacturer' => 'Test Manufacturer',
    ]);

    echo "✅ Product created: ID {$product->id}, SKU: {$product->sku}\n";

    // Add price (Detaliczna group)
    $priceGroup = PriceGroup::where('code', 'detaliczna')
        ->orWhere('name', 'like', '%Detaliczna%')
        ->orWhere('id', 1)
        ->first();

    if (!$priceGroup) {
        echo "⚠️ WARNING: No 'detaliczna' price group found, using ID 1 as fallback\n";
        $priceGroupId = 1;
    } else {
        $priceGroupId = $priceGroup->id;
        echo "✅ Price Group: {$priceGroup->name} (ID: {$priceGroupId})\n";
    }

    $product->prices()->create([
        'price_group_id' => $priceGroupId,
        'price' => 99.99,
        'price_net' => 99.99,
        'price_gross' => 122.99, // 23% VAT
        'currency' => 'PLN',
    ]);

    echo "✅ Price added: 99.99 PLN (net)\n";

    // Add stock (MPPTRADE warehouse)
    $warehouse = Warehouse::where('code', 'MPPTRADE')
        ->orWhere('name', 'like', '%MPPTRADE%')
        ->orWhere('id', 1)
        ->first();

    if (!$warehouse) {
        echo "⚠️ WARNING: No 'MPPTRADE' warehouse found, using ID 1 as fallback\n";
        $warehouseId = 1;
    } else {
        $warehouseId = $warehouse->id;
        echo "✅ Warehouse: {$warehouse->name} (ID: {$warehouseId})\n";
    }

    $product->stock()->create([
        'warehouse_id' => $warehouseId,
        'quantity' => 50,
        'reserved' => 0,
        'available' => 50,
    ]);

    $warehouseName = $warehouse ? $warehouse->name : 'warehouse ID ' . $warehouseId;
    echo "✅ Stock added: 50 units in {$warehouseName}\n";

    // Add category
    $category = Category::where('parent_id', null) // Root category
        ->orWhere('level', 1)
        ->orWhere('id', 1)
        ->first();

    if (!$category) {
        echo "⚠️ WARNING: No root category found, using ID 1 as fallback\n";
        $categoryId = 1;
    } else {
        $categoryId = $category->id;
        echo "✅ Category: {$category->name} (ID: {$categoryId})\n";
    }

    $product->categories()->attach($categoryId);
    $categoryName = $category ? $category->name : 'category ID ' . $categoryId;
    echo "✅ Category attached: {$categoryName}\n";

    DB::commit();

    echo "\n=== PRODUCT CREATED SUCCESSFULLY ===\n";
    echo "Product ID: {$product->id}\n";
    echo "SKU: {$product->sku}\n";
    echo "Name: {$product->name}\n";
    echo "Price: 99.99 PLN (net)\n";
    echo "Stock: 50 units\n";
    $categoryNameDisplay = $category ? $category->name : 'ID ' . $categoryId;
    echo "Category: {$categoryNameDisplay}\n";
    echo "\n";
    echo "Use this product for sync testing:\n";
    echo "  Product ID: {$product->id}\n";
    echo "  SKU: {$product->sku}\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: Failed to create test product\n";
    echo "Message: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "=== VERIFICATION ===\n";
echo "Check product in database:\n";
echo "  SELECT * FROM products WHERE sku = '{$product->sku}';\n";
echo "  SELECT * FROM product_prices WHERE product_id = {$product->id};\n";
echo "  SELECT * FROM stocks WHERE product_id = {$product->id};\n";
echo "  SELECT * FROM product_categories WHERE product_id = {$product->id};\n";
echo "\n";
echo "Next step: php _TOOLS/test_sync_job_dispatch.php {$product->id}\n";
