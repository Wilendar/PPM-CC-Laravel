<?php
/**
 * Check product types and vehicles
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductType;

echo "=== PRODUCT TYPES ===\n\n";

$types = ProductType::all(['id', 'name', 'slug']);
foreach ($types as $t) {
    $count = Product::where('product_type_id', $t->id)->count();
    echo "ID: {$t->id} | Slug: {$t->slug} | Name: {$t->name} | Products: {$count}\n";
}

echo "\n=== VEHICLES (product_type with 'pojazd' or 'vehicle' in name) ===\n\n";

// Find vehicle type(s)
$vehicleTypes = ProductType::where('slug', 'LIKE', '%pojazd%')
    ->orWhere('slug', 'LIKE', '%vehicle%')
    ->orWhere('name', 'LIKE', '%pojazd%')
    ->orWhere('name', 'LIKE', '%vehicle%')
    ->pluck('id');

echo "Vehicle type IDs: " . $vehicleTypes->implode(', ') . "\n\n";

// List all potential vehicles
$vehicles = Product::whereIn('product_type_id', $vehicleTypes)->get(['id', 'name', 'sku', 'product_type_id']);
echo "Vehicles found: " . $vehicles->count() . "\n\n";

foreach ($vehicles as $v) {
    echo "ID: {$v->id} | Type: {$v->product_type_id} | SKU: {$v->sku} | Name: {$v->name}\n";
}

echo "\n=== DONE ===\n";
