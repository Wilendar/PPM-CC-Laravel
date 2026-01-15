<?php
/**
 * Test import compatibility from PrestaShop
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use App\Jobs\PrestaShop\PullSingleProductFromPrestaShop;

$productId = 11181;
$shopId = 1;

echo "=== Test Import Compatibility from PrestaShop ===\n\n";

// Step 1: Delete existing compatibility to test import
echo "1. Clearing existing compatibilities for product {$productId}...\n";
$deleted = VehicleCompatibility::where('product_id', $productId)->delete();
echo "   - Deleted {$deleted} records\n";

// Step 2: Verify cleared
$count = VehicleCompatibility::where('product_id', $productId)->count();
echo "   - Current count: {$count}\n";

// Step 3: Run import
echo "\n2. Running PullSingleProductFromPrestaShop...\n";
$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    echo "   - ERROR: Product or shop not found\n";
    exit(1);
}

try {
    PullSingleProductFromPrestaShop::dispatchSync($product, $shop);
    echo "   - Pull completed!\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// Step 4: Verify imported compatibilities
echo "\n3. Checking imported compatibilities...\n";
$imported = VehicleCompatibility::where('product_id', $productId)
    ->with(['vehicleProduct', 'compatibilityAttribute'])
    ->get();

echo "   - Found {$imported->count()} compatibilities\n";

foreach ($imported as $compat) {
    $vehicleName = $compat->vehicleProduct ? $compat->vehicleProduct->name : 'Unknown';
    $typeName = $compat->compatibilityAttribute ? $compat->compatibilityAttribute->name : 'Unknown';
    echo "     * {$typeName}: {$vehicleName} (vehicle_id: {$compat->vehicle_model_id})\n";
}

echo "\n=== Test Complete ===\n";
