<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX SHOP 1 CATEGORY MAPPINGS ===\n\n";

$shop = DB::table('prestashop_shops')->where('id', 1)->first();

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

echo "BEFORE:\n";
$oldMappings = json_decode($shop->category_mappings, true);
echo json_encode($oldMappings, JSON_PRETTY_PRINT) . "\n\n";

// Correct mappings: PrestaShop ID => PPM ID
$correctMappings = [
    '2' => 2,      // Wszystko → Wszystko
    '12' => 41,    // PITGANG (PS) → PITGANG (PPM ID 41, not 32!)
    '23' => 43,    // Pit Bike (PS) → Pit Bike (PPM ID 43, not 34!)
    '800' => 42,   // Pojazdy (PS) → Pojazdy (PPM ID 42, not 33!)
    '801' => 57,   // Quad → Quad
];

echo "CORRECT MAPPINGS:\n";
foreach ($correctMappings as $psId => $ppmId) {
    $ppmCat = DB::table('categories')->where('id', $ppmId)->first();
    $ppmName = $ppmCat ? $ppmCat->name : 'NOT FOUND';
    echo "  PrestaShop {$psId} → PPM {$ppmId} ({$ppmName})\n";
}
echo "\n";

// Update shop mappings
DB::table('prestashop_shops')
    ->where('id', 1)
    ->update(['category_mappings' => json_encode($correctMappings)]);

echo "✅ SHOP MAPPINGS UPDATED\n\n";

$shopAfter = DB::table('prestashop_shops')->where('id', 1)->first();
echo "AFTER:\n";
echo json_encode(json_decode($shopAfter->category_mappings, true), JSON_PRETTY_PRINT) . "\n\n";

echo "=== COMPLETE ===\n";
