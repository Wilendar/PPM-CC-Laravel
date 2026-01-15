<?php
// Check imported product features and vehicle mappings

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$productId = 11198; // Airbox filtr powietrza buggy S70 KAYO

$product = \App\Models\Product::find($productId);

echo "=== PRODUCT INFO ===\n";
echo "ID: {$product->id}\n";
echo "SKU: {$product->sku}\n";
echo "Name: {$product->name}\n\n";

echo "=== PRODUCT FEATURES ===\n";
$features = $product->features()->with('featureType')->get();
foreach ($features as $f) {
    echo "- [{$f->feature_type_id}] {$f->featureType->name}: {$f->value}\n";
}
echo "Total features: " . $features->count() . "\n\n";

echo "=== VEHICLE COMPATIBILITIES ===\n";
$compatibilities = \App\Models\VehicleCompatibility::where('product_id', $productId)->with('vehicle')->get();
foreach ($compatibilities as $c) {
    $vehicleName = $c->vehicle ? $c->vehicle->name : 'NULL';
    echo "- [{$c->vehicle_id}] {$vehicleName} ({$c->compatibility_type})\n";
}
echo "Total compatibilities: " . $compatibilities->count() . "\n\n";

echo "=== VEHICLE FEATURE VALUE MAPPINGS ===\n";
$mappings = \App\Models\VehicleFeatureValueMapping::with(['vehicle', 'featureValue'])->get();
echo "Total mappings in system: " . $mappings->count() . "\n";
foreach ($mappings as $m) {
    $vehicleName = $m->vehicle ? $m->vehicle->name : 'NULL';
    $featureValueId = $m->feature_value_id;
    echo "- Mapping ID {$m->id}: Vehicle '{$vehicleName}' <- PS Feature Value ID: {$featureValueId}\n";
}

echo "\n=== PRESTASHOP FEATURE DATA FOR PRODUCT ===\n";
$shopData = \App\Models\ProductShopData::where('product_id', $productId)->first();
if ($shopData) {
    echo "Shop ID: {$shopData->shop_id}\n";
    echo "PS Product ID: {$shopData->prestashop_product_id}\n";

    // Check if we stored features_snapshot
    if (!empty($shopData->features_snapshot)) {
        echo "Features snapshot:\n";
        print_r(json_decode($shopData->features_snapshot, true));
    } else {
        echo "No features_snapshot stored\n";
    }
}

echo "\n=== AVAILABLE VEHICLES IN PPM ===\n";
$vehicles = \App\Models\Product::where('product_type_id', 1)->get(['id', 'sku', 'name']);
foreach ($vehicles as $v) {
    echo "- [{$v->id}] {$v->sku}: {$v->name}\n";
}
