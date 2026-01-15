<?php
/**
 * List all vehicles in PPM database
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== ALL VEHICLES IN PPM ===\n\n";

$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);

echo "Total vehicles: " . $vehicles->count() . "\n\n";

foreach ($vehicles as $v) {
    echo "ID: {$v->id} | SKU: {$v->sku} | Name: {$v->name}\n";
}

echo "\n=== DONE ===\n";
