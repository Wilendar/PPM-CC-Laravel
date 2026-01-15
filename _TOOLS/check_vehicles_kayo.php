<?php
// Check KAYO vehicles in PPM

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VEHICLES WITH 'KAYO' OR 'S70' IN NAME ===\n\n";

$vehicles = \App\Models\Product::where('product_type_id', 1)
    ->where(function ($q) {
        $q->where('name', 'LIKE', '%KAYO%')
          ->orWhere('name', 'LIKE', '%S70%');
    })
    ->get(['id', 'sku', 'name']);

if ($vehicles->isEmpty()) {
    echo "No vehicles found matching KAYO or S70!\n\n";
} else {
    foreach ($vehicles as $v) {
        echo "[{$v->id}] {$v->sku}: {$v->name}\n";
    }
    echo "\nTotal: " . $vehicles->count() . "\n\n";
}

echo "=== ALL VEHICLES (product_type_id=1) ===\n";
$allVehicles = \App\Models\Product::where('product_type_id', 1)->get(['id', 'sku', 'name']);
echo "Total vehicles: " . $allVehicles->count() . "\n\n";

foreach ($allVehicles->take(20) as $v) {
    echo "[{$v->id}] {$v->sku}: {$v->name}\n";
}
if ($allVehicles->count() > 20) {
    echo "... and " . ($allVehicles->count() - 20) . " more\n";
}

echo "\n=== VEHICLE FEATURE VALUE MAPPINGS ===\n";
// Raw query to check table content
$mappings = \DB::table('vehicle_feature_value_mappings')->get();
echo "Total mappings: " . $mappings->count() . "\n\n";

foreach ($mappings as $m) {
    $vehicle = \App\Models\Product::find($m->vehicle_product_id);
    $vehicleName = $vehicle ? $vehicle->name : 'NULL';
    echo "Mapping {$m->id}: PS Feature Value {$m->prestashop_feature_value_id} (Feature {$m->prestashop_feature_id}) -> Vehicle '{$vehicleName}' (ID: {$m->vehicle_product_id})\n";
}

echo "\n=== LOOKING FOR MAPPING TO FEATURE VALUE 2430 OR 2431 ===\n";
$targetMapping = \DB::table('vehicle_feature_value_mappings')
    ->whereIn('prestashop_feature_value_id', [2430, 2431])
    ->first();
if ($targetMapping) {
    echo "FOUND: Mapping for PS Feature Value {$targetMapping->prestashop_feature_value_id} -> Vehicle ID {$targetMapping->vehicle_product_id}\n";
} else {
    echo "NOT FOUND: No mapping for Feature Values 2430 or 2431 (KAYO S70)\n";
    echo "This is why import didn't create compatibilities!\n";
}
