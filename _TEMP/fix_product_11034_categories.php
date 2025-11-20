<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;

echo "=== FIX PRODUCT 11034 CATEGORIES ===\n\n";

$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "ERROR: ProductShopData NOT FOUND\n";
    exit(1);
}

echo "BEFORE:\n";
$cm = $psd->category_mappings;
echo "  Selected: " . json_encode($cm['ui']['selected']) . "\n";
echo "  Primary: " . ($cm['ui']['primary'] ?? 'NULL') . "\n\n";

// Correct categories based on PrestaShop -> PPM mapping
// From PrestaShop [2, 12, 23, 800, 801] -> PPM [2, 32, 34, 33, 57]
$correctCategories = [2, 32, 34, 33, 57];
$mappings = [
    2 => 2,     // Wszystko
    32 => 12,   // PITGANG
    34 => 23,   // Pit Bike (default)
    33 => 800,  // Pojazdy
    57 => 801,  // Quad
];

echo "CORRECT CATEGORIES (from PrestaShop):\n";
echo "  PPM: " . json_encode($correctCategories) . "\n";
echo "  Mappings (PPM -> PrestaShop): " . json_encode($mappings) . "\n\n";

// Update category_mappings
$cm['ui']['selected'] = $correctCategories;
$cm['ui']['primary'] = 34; // Pit Bike (default category in PrestaShop)

// Rebuild mappings section (PPM ID -> PrestaShop ID)
$cm['mappings'] = $mappings;

// Add metadata
$cm['metadata'] = [
    'last_updated' => now()->toIso8601String(),
    'source' => 'pull',
];

$psd->category_mappings = $cm;
$psd->save();

echo "AFTER:\n";
echo "  Selected: " . json_encode($cm['ui']['selected']) . "\n";
echo "  Primary: " . ($cm['ui']['primary'] ?? 'NULL') . "\n";
echo "  Updated at: {$psd->updated_at}\n\n";

echo "✅ CATEGORIES FIXED\n\n";

echo "VERIFICATION:\n";
echo "  1. Open product 11034 in browser\n";
echo "  2. Click 'B2B Test DEV' shop tab\n";
echo "  3. Should show categories:\n";
foreach ($correctCategories as $catId) {
    $cat = \App\Models\Category::find($catId);
    echo "     - {$catId}: " . ($cat ? $cat->name : 'NOT FOUND') . "\n";
}

echo "\n=== COMPLETE ===\n";
