<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$searchName = "KAYO AU150 CVT";

echo "=== FIND KAYO VEHICLE ===\n\n";

echo "1. Searching for vehicles matching '{$searchName}':\n";

// Search by name
$vehicles = Product::where('product_type_id', 1) // Pojazd
    ->where(function($q) use ($searchName) {
        $q->where('name', 'LIKE', "%{$searchName}%")
          ->orWhere('name', 'LIKE', "%KAYO%AU150%")
          ->orWhere('sku', 'LIKE', "%AU150%");
    })
    ->get(['id', 'name', 'sku', 'product_type_id']);

echo "   Found: " . count($vehicles) . " vehicles\n";
foreach ($vehicles as $v) {
    echo "   - ID: {$v->id}, SKU: {$v->sku}, Name: {$v->name}\n";
}

// Try broader search
echo "\n2. All vehicles with 'KAYO' in name:\n";
$kayoVehicles = Product::where('product_type_id', 1)
    ->where('name', 'LIKE', '%KAYO%')
    ->get(['id', 'name', 'sku']);

echo "   Found: " . count($kayoVehicles) . " vehicles\n";
foreach ($kayoVehicles as $v) {
    echo "   - ID: {$v->id}, SKU: {$v->sku}, Name: " . substr($v->name, 0, 60) . "\n";
}

// Check all product types
echo "\n3. Products with 'AU150' anywhere:\n";
$au150Products = Product::where('name', 'LIKE', '%AU150%')
    ->orWhere('sku', 'LIKE', '%AU150%')
    ->get(['id', 'name', 'sku', 'product_type_id']);

echo "   Found: " . count($au150Products) . " products\n";
foreach ($au150Products as $p) {
    echo "   - ID: {$p->id}, Type: {$p->product_type_id}, SKU: {$p->sku}, Name: " . substr($p->name, 0, 50) . "\n";
}

echo "\n=== DONE ===\n";
