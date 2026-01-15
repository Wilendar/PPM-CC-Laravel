<?php
/**
 * Fix BUGGY vehicle product types
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== FIXING BUGGY VEHICLE TYPES ===\n\n";

// Find BUGGY products
$buggyProducts = Product::where('name', 'LIKE', '%Buggy%')
    ->orWhere('sku', 'LIKE', 'BG-%')
    ->get(['id', 'name', 'sku', 'product_type_id']);

echo "Found " . $buggyProducts->count() . " BUGGY products:\n\n";

foreach ($buggyProducts as $p) {
    echo "ID: {$p->id} | SKU: {$p->sku} | Name: {$p->name} | Current Type: {$p->product_type_id}\n";

    if ($p->product_type_id != 1) {
        $p->product_type_id = 1; // Pojazd
        $p->save();
        echo "  -> FIXED: Changed to product_type_id = 1 (Pojazd)\n";
    } else {
        echo "  -> OK: Already type 1 (Pojazd)\n";
    }
}

echo "\n=== VERIFICATION ===\n\n";

// List all vehicles now
$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
echo "Total vehicles (type=1): " . $vehicles->count() . "\n\n";

foreach ($vehicles as $v) {
    echo "ID: {$v->id} | SKU: {$v->sku} | Name: {$v->name}\n";
}

echo "\n=== DONE ===\n";
