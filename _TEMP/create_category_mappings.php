<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREATE CATEGORY MAPPINGS ===\n\n";

// PrestaShop categories (from database)
$psCategories = [
    2 => 'Wszystko',
    12 => 'PITGANG',
    23 => 'Pit Bike',
    800 => 'Pojazdy',
    801 => 'Quad',
];

echo "STEP 1: Find matching PPM categories\n\n";

$mappings = [];

foreach ($psCategories as $psId => $psName) {
    echo "PrestaShop {$psId} ({$psName}):\n";

    // Try exact name match
    $ppmCat = DB::table('categories')
        ->where('name', $psName)
        ->first();

    if ($ppmCat) {
        echo "  -> PPM {$ppmCat->id} ({$ppmCat->name}) ✅ EXACT MATCH\n";
        $mappings[$psId] = $ppmCat->id;
    } else {
        // Try LIKE match
        $ppmCat = DB::table('categories')
            ->where('name', 'LIKE', "%{$psName}%")
            ->first();

        if ($ppmCat) {
            echo "  -> PPM {$ppmCat->id} ({$ppmCat->name}) ⚠️ PARTIAL MATCH\n";
            $mappings[$psId] = $ppmCat->id;
        } else {
            echo "  -> ❌ NO MATCH FOUND\n";
            echo "     ACTION: Need to create PPM category or manually map\n";
        }
    }
}

echo "\n\nSTEP 2: Proposed mappings\n\n";
echo json_encode($mappings, JSON_PRETTY_PRINT) . "\n\n";

if (count($mappings) < count($psCategories)) {
    echo "⚠️ WARNING: Not all categories mapped!\n";
    echo "Missing: " . implode(', ', array_diff(array_keys($psCategories), array_keys($mappings))) . "\n\n";

    echo "List all PPM categories for manual mapping:\n";
    $allCategories = DB::table('categories')
        ->orderBy('name')
        ->get(['id', 'name']);

    foreach ($allCategories as $cat) {
        echo "  PPM {$cat->id}: {$cat->name}\n";
    }
}

echo "\n\nSTEP 3: Apply mappings to Shop 1\n\n";

if (count($mappings) > 0) {
    DB::table('prestashop_shops')
        ->where('id', 1)
        ->update([
            'category_mappings' => json_encode($mappings)
        ]);

    echo "✅ Mappings saved to prestashop_shops.category_mappings\n";

    // Verify
    $shop = DB::table('prestashop_shops')->where('id', 1)->first();
    $saved = json_decode($shop->category_mappings, true);
    echo "Verified: " . json_encode($saved) . "\n";

} else {
    echo "❌ No mappings to save\n";
}

echo "\n=== COMPLETE ===\n";
