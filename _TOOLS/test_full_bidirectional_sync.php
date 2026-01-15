<?php
/**
 * Test Full Bidirectional Sync: PPM -> PS -> PPM
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\PrestaShop\PullSingleProductFromPrestaShop;
use Illuminate\Support\Facades\DB;

$productId = 11181;  // MRF Nakladki
$vehicleId = 11183;  // Buggy KAYO S200
$shopId = 1;

echo "=== FULL BIDIRECTIONAL SYNC TEST ===\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

// Step 1: Clean state
echo "1. Cleaning up...\n";
VehicleCompatibility::where('product_id', $productId)->delete();
DB::table('vehicle_feature_value_mappings')->where('vehicle_product_id', $vehicleId)->delete();
echo "   - Cleared compatibilities and mappings\n";

// Step 2: Create compatibility
echo "\n2. Creating compatibility PPM...\n";
$compat = VehicleCompatibility::create([
    'product_id' => $productId,
    'vehicle_model_id' => $vehicleId,
    'shop_id' => $shopId,
    'compatibility_attribute_id' => 1, // Oryginal
    'compatibility_source_id' => 1,
    'part_sku' => 'MRF13-68-003',
    'vehicle_sku' => 'BG-KAYO-S200',
]);
echo "   - Created compatibility ID: {$compat->id}\n";

// Step 3: Sync to PrestaShop (this should save mappings)
echo "\n3. Syncing to PrestaShop...\n";
try {
    SyncProductToPrestaShop::dispatchSync($product, $shop, 8);
    echo "   - Sync completed!\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// Step 4: Verify mappings saved
echo "\n4. Checking mappings...\n";
$mappings = DB::table('vehicle_feature_value_mappings')
    ->where('vehicle_product_id', $vehicleId)
    ->where('shop_id', $shopId)
    ->get();
echo "   - Found " . count($mappings) . " mappings\n";
foreach ($mappings as $m) {
    echo "     * Feature {$m->prestashop_feature_id} -> value {$m->prestashop_feature_value_id}\n";
}

// Step 5: Delete compatibility in PPM
echo "\n5. Deleting compatibility in PPM...\n";
VehicleCompatibility::where('product_id', $productId)->delete();
$count = VehicleCompatibility::where('product_id', $productId)->count();
echo "   - Compatibilities count: {$count}\n";

// Step 6: Import from PrestaShop
echo "\n6. Importing from PrestaShop...\n";
try {
    PullSingleProductFromPrestaShop::dispatchSync($product, $shop);
    echo "   - Import completed!\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// Step 7: Verify imported
echo "\n7. Checking imported compatibilities...\n";
$imported = VehicleCompatibility::where('product_id', $productId)
    ->with(['vehicleProduct', 'compatibilityAttribute'])
    ->get();
echo "   - Found " . count($imported) . " compatibilities\n";
foreach ($imported as $c) {
    $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
    $type = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
    echo "     * {$type}: {$vname}\n";
}

echo "\n=== TEST COMPLETE ===\n";
if (count($imported) > 0) {
    echo "RESULT: SUCCESS - Bidirectional sync working!\n";
} else {
    echo "RESULT: FAILED - Import did not recreate compatibility\n";
    echo "Check logs: grep 'COMPAT SYNC' storage/logs/laravel.log | tail -20\n";
}
