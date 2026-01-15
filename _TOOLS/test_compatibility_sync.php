<?php
/**
 * Test script for FAZA 4.5 - Vehicle Compatibility Sync
 * Run on production: cd domains/ppm.mpptrade.pl/public_html && php artisan tinker < _TOOLS/test_compatibility_sync.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VehicleCompatibility;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\VehicleCompatibilitySyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Constants
$PART_ID = 11181;      // Nakladki na szprychy MRF
$VEHICLE_ID = 11183;   // Buggy KAYO S200
$SHOP_ID = 1;          // B2B Test DEV

echo "=== FAZA 4.5 Compatibility Sync Test ===\n\n";

// Step 1: Check current state
echo "1. Current state:\n";
$existing = VehicleCompatibility::where('product_id', $PART_ID)
    ->where('vehicle_model_id', $VEHICLE_ID)
    ->first();

if ($existing) {
    echo "   - Compatibility already exists (ID: {$existing->id})\n";
} else {
    echo "   - No existing compatibility\n";
}

// Step 2: Create test compatibility
echo "\n2. Creating test compatibility...\n";
try {
    $compat = VehicleCompatibility::updateOrCreate(
        [
            'product_id' => $PART_ID,
            'vehicle_model_id' => $VEHICLE_ID,
            'shop_id' => $SHOP_ID,
        ],
        [
            'compatibility_attribute_id' => 1, // Oryginal
            'compatibility_source_id' => 1,
            'part_sku' => 'MRF13-68-003',
            'vehicle_sku' => 'BG-KAYO-S200',
            'notes' => 'Test sync FAZA 4.5 - ' . now()->format('Y-m-d H:i:s'),
        ]
    );
    echo "   - Created/Updated compatibility ID: {$compat->id}\n";
} catch (Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Verify compatibility in DB
echo "\n3. Verification:\n";
$count = VehicleCompatibility::where('product_id', $PART_ID)->count();
echo "   - Total compatibilities for product {$PART_ID}: {$count}\n";

// Step 4: Test transformation service
echo "\n4. Testing VehicleCompatibilitySyncService...\n";
try {
    $product = Product::find($PART_ID);
    $shop = PrestaShopShop::find($SHOP_ID);

    if (!$product) {
        echo "   - ERROR: Product {$PART_ID} not found\n";
        exit(1);
    }

    if (!$shop) {
        echo "   - ERROR: Shop {$SHOP_ID} not found\n";
        exit(1);
    }

    // Use factory to create client
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
    echo "   - Client connected (version: {$shop->version})\n";

    $service = new VehicleCompatibilitySyncService();
    $service->setShop($shop);
    $service->setClient($client);

    $features = $service->transformToPrestaShopFeatures($product, $SHOP_ID);

    echo "   - Transformed features count: " . count($features) . "\n";
    foreach ($features as $f) {
        $featureNames = [431 => 'Oryginal', 432 => 'Model', 433 => 'Zamiennik'];
        $name = $featureNames[$f['id']] ?? 'Unknown';
        echo "     * Feature {$f['id']} ({$name}) -> value {$f['id_feature_value']}\n";
    }
} catch (Exception $e) {
    echo "   - ERROR in transformation: " . $e->getMessage() . "\n";
    echo "   - Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Next steps:\n";
echo "1. Check Laravel logs for [COMPAT SYNC] entries\n";
echo "2. Run: php artisan queue:work --once (to process sync job)\n";
echo "3. Verify in PrestaShop that product has features 431/432/433\n";
