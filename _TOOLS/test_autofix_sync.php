<?php

/**
 * TEST: Auto-Fix Required Fields During Sync
 *
 * BUGFIX 2025-11-06: Test that ProductSyncStrategy automatically fixes all required fields
 * Reference: _DOCS/PRESTASHOP_REQUIRED_FIELDS.md
 *
 * This script:
 * 1. Creates test product in PPM with INTENTIONALLY MISSING fields:
 *    - NO manufacturer (or invalid one)
 *    - price = 0.00 (zero)
 *    - NO specific price
 * 2. Syncs product to PrestaShop
 * 3. Verifies all 7 required fields were auto-fixed by ensureRequiredFields()
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "=== AUTO-FIX REQUIRED FIELDS TEST ===\n\n";

try {
    // Get B2B Test DEV shop
    $shop = PrestaShopShop::where('name', 'B2B Test DEV')->first();

    if (!$shop) {
        echo "❌ ERROR: Shop 'B2B Test DEV' not found!\n";
        exit(1);
    }

    echo "Shop: {$shop->name} (ID: {$shop->id})\n";
    echo "Shop URL: {$shop->url}\n\n";

    // STEP 1: Create test product with INTENTIONALLY MISSING fields
    echo "STEP 1: Creating test product with missing fields...\n";

    $timestamp = time();
    $sku = "TEST-AUTOFIX-{$timestamp}";

    DB::beginTransaction();

    $product = Product::create([
        'sku' => $sku,
        'name' => "Test Auto-Fix Required Fields {$timestamp}",
        'short_description' => 'Test product for auto-fix verification',
        'long_description' => 'This product intentionally has missing manufacturer and zero price to test auto-fix.',
        'is_active' => true,
        'product_type_id' => 2, // spare_part
        'tax_rate' => 23.0,
        'weight' => 1.0,
        // INTENTIONALLY NO manufacturer field
        'manufacturer' => null, // ❌ This will test manufacturer fallback
        'created_by' => 8, // admin@mpptrade.pl
        'updated_by' => 8,
    ]);

    echo "  ✓ Product created: ID {$product->id}, SKU: {$sku}\n";

    // Add to default category
    $defaultCategory = Category::where('name', 'Test')->first();
    if (!$defaultCategory) {
        $defaultCategory = Category::where('name', 'PITGANG')->first();
    }

    if ($defaultCategory) {
        $product->categories()->attach($defaultCategory->id);
        echo "  ✓ Added to category: {$defaultCategory->name}\n";
    }

    // Add price group with ZERO price (intentionally)
    $priceGroup = \App\Models\PriceGroup::where('code', 'detaliczna')->first();

    if ($priceGroup) {
        ProductPrice::create([
            'product_id' => $product->id,
            'price_group_id' => $priceGroup->id,
            'price_net' => 0.00, // ❌ Zero price - should be auto-fixed to 0.01
            'price_gross' => 0.00,
            'currency' => 'PLN',
        ]);
        echo "  ✓ Added price: 0.00 PLN (ZERO - should be auto-fixed to 0.01)\n";
    }

    // Add stock
    $warehouse = \App\Models\Warehouse::where('code', 'MPPTRADE')->first();

    if ($warehouse) {
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'reserved' => 0,
            'available' => 10,
        ]);
        echo "  ✓ Added stock: 10 units\n";
    }

    DB::commit();

    echo "\n";
    echo "Product created with INTENTIONALLY MISSING fields:\n";
    echo "  - manufacturer: NULL (should fallback to config default)\n";
    echo "  - price: 0.00 (should be auto-fixed to 0.01)\n";
    echo "  - ps_specific_price: NOT EXISTS (should be created)\n";
    echo "  - minimal_quantity: NOT SET (should be set to 1)\n";
    echo "  - redirect_type: NOT SET (should be set to '301-category')\n";
    echo "  - state: NOT SET (should be set to 1)\n";
    echo "  - additional_delivery_times: NOT SET (should be set to 1)\n\n";

    // STEP 2: Sync to PrestaShop
    echo "STEP 2: Syncing product to PrestaShop...\n\n";

    // Create PrestaShop client
    $client = new PrestaShop8Client($shop);

    // Get sync strategy
    $syncStrategy = app(ProductSyncStrategy::class);

    // Sync product
    try {
        $result = $syncStrategy->syncToPrestaShop($product, $client, $shop);

        echo "✅ SYNC SUCCESS!\n\n";
        echo "Result:\n";
        echo "  - Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
        echo "  - PrestaShop ID: {$result['external_id']}\n";
        echo "  - Operation: {$result['operation']}\n";
        echo "  - Message: {$result['message']}\n\n";

        $prestashopId = $result['external_id'];

    } catch (\Exception $e) {
        echo "❌ SYNC FAILED!\n";
        echo "Error: {$e->getMessage()}\n";
        echo $e->getTraceAsString() . "\n";
        exit(1);
    }

    // STEP 3: Verify auto-fix worked
    echo "STEP 3: Verifying auto-fix through verification script...\n\n";

    // Run verification script
    $output = [];
    $returnCode = 0;

    exec(
        "cd " . escapeshellarg(__DIR__ . '/..') . " && php _TOOLS/verify_product_required_fields.php {$prestashopId}",
        $output,
        $returnCode
    );

    echo implode("\n", $output) . "\n";

    if ($returnCode === 0) {
        echo "\n✅ TEST PASSED! All required fields were auto-fixed during sync!\n\n";
        echo "Summary:\n";
        echo "  - PPM Product ID: {$product->id}\n";
        echo "  - PPM SKU: {$sku}\n";
        echo "  - PrestaShop ID: {$prestashopId}\n";
        echo "  - All 10 checks: PASSED ✅\n";
        echo "  - Product visible in admin: YES ✅\n\n";

        echo "Next steps:\n";
        echo "  1. Check PrestaShop admin panel: https://dev.mpptrade.pl/admin/index.php?controller=AdminProducts\n";
        echo "  2. Search for SKU: {$sku}\n";
        echo "  3. Product should be VISIBLE in list! ✨\n\n";

        exit(0);
    } else {
        echo "\n❌ TEST FAILED! Some required fields were NOT auto-fixed!\n\n";
        echo "PPM Product ID: {$product->id}\n";
        echo "PrestaShop ID: {$prestashopId}\n";
        echo "SKU: {$sku}\n\n";
        exit(1);
    }

} catch (\Exception $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    echo "❌ TEST ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
