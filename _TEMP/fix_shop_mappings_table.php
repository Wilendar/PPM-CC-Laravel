<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

echo "=== FIX SHOP_MAPPINGS TABLE ===\n\n";

// 1. DELETE GHOST MAPPING: PPM 36 → PS 2
echo "STEP 1: Delete ghost mapping PPM 36 → PS 2\n";
$deleted = ShopMapping::where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->where('ppm_value', '36')
    ->where('prestashop_id', 2)
    ->delete();

echo "  Deleted: {$deleted} rows\n\n";

// 2. CREATE CORRECT MAPPING: PPM 2 → PS 2
echo "STEP 2: Create correct mapping PPM 2 → PS 2\n";

// Check if PPM 2 exists
$ppmCat = Category::find(2);
if (!$ppmCat) {
    die("❌ PPM category 2 NOT FOUND!\n");
}

echo "  PPM 2: {$ppmCat->name}\n";

// Create or update mapping
$mapping = ShopMapping::createOrUpdateMapping(
    shopId: 1,
    type: 'category',
    ppmValue: '2',
    prestashopId: 2,
    prestashopValue: 'Wszystko'
);

echo "  ✅ Mapping created/updated: ID {$mapping->id}\n\n";

// 3. VERIFY MAPPINGS
echo "STEP 3: Verify critical mappings\n\n";

$criticalMappings = [
    2 => 2,      // Wszystko
    41 => 12,    // PITGANG
    43 => 23,    // Pit Bike
    42 => 800,   // Pojazdy
    57 => 801,   // Quad
];

foreach ($criticalMappings as $ppmId => $psId) {
    $mapping = ShopMapping::where('shop_id', 1)
        ->where('mapping_type', 'category')
        ->where('ppm_value', (string) $ppmId)
        ->where('is_active', true)
        ->first();

    $ppmCat = Category::find($ppmId);
    $ppmName = $ppmCat ? $ppmCat->name : 'NOT FOUND';

    if ($mapping && $mapping->prestashop_id == $psId) {
        echo "  ✅ PPM {$ppmId} ({$ppmName}) → PS {$psId}\n";
    } else {
        echo "  ❌ PPM {$ppmId} ({$ppmName}) → INCORRECT or MISSING\n";
    }
}

echo "\n=== COMPLETE ===\n";
