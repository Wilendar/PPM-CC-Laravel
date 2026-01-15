<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VehicleCompatibility;
use Illuminate\Support\Facades\DB;

$productId = 11190;

echo "=== VERIFY IMPORT RESULT ===\n\n";

// Check compatibilities
$compats = VehicleCompatibility::where('product_id', $productId)
    ->with(['vehicleProduct', 'compatibilityAttribute'])
    ->get();

echo "Compatibilities for product {$productId}:\n";
echo "Count: " . $compats->count() . "\n\n";

foreach ($compats as $c) {
    $vname = $c->vehicleProduct ? $c->vehicleProduct->name : 'Unknown';
    $aname = $c->compatibilityAttribute ? $c->compatibilityAttribute->name : 'Unknown';
    echo "  - {$aname}: {$vname}\n";
    echo "    Vehicle ID: {$c->vehicle_model_id}\n";
    echo "    Shop ID: {$c->shop_id}\n";
    echo "    Metadata: " . json_encode($c->metadata) . "\n\n";
}

// Check mappings created
echo "Vehicle feature mappings:\n";
$mappings = DB::table('vehicle_feature_value_mappings')
    ->where('shop_id', 1)
    ->get();
echo "Count: " . $mappings->count() . "\n";
foreach ($mappings as $m) {
    echo "  - Vehicle {$m->vehicle_product_id} -> Feature {$m->prestashop_feature_id}, Value {$m->prestashop_feature_value_id}\n";
}

echo "\n=== DONE ===\n";
