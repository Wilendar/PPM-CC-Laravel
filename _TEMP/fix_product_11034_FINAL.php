<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;

echo "=== FIX PRODUCT 11034 CATEGORIES - FINAL ===\n\n";

$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    die("ProductShopData NOT FOUND!\n");
}

echo "BEFORE:\n";
$cm = $psd->category_mappings;
echo "  Selected: " . json_encode($cm['ui']['selected'] ?? []) . "\n";
echo "  Primary: " . ($cm['ui']['primary'] ?? 'NULL') . "\n\n";

// Correct PPM categories: [2, 41, 43, 42, 57]
// PrestaShop categories: [2, 12, 23, 800, 801]

$correctCategories = [2, 41, 43, 42, 57];
$mappings = [
    2 => 2,      // Wszystko (PPM) → Wszystko (PS)
    41 => 12,    // PITGANG (PPM) → PITGANG (PS)
    43 => 23,    // Pit Bike (PPM) → Pit Bike (PS)
    42 => 800,   // Pojazdy (PPM) → Pojazdy (PS)
    57 => 801,   // Quad (PPM) → Quad (PS) - DEFAULT
];

echo "CORRECT CATEGORIES (PPM IDs): " . json_encode($correctCategories) . "\n";
echo "MAPPINGS (PPM ID → PrestaShop ID): " . json_encode($mappings) . "\n\n";

// Update category_mappings
$cm['ui']['selected'] = $correctCategories;
$cm['ui']['primary'] = 57; // Quad (default in PrestaShop: 801)

// Rebuild mappings section (PPM ID → PrestaShop ID)
$cm['mappings'] = $mappings;

// Add metadata
$cm['metadata'] = [
    'last_updated' => now()->toIso8601String(),
    'source' => 'manual',
];

$psd->category_mappings = $cm;
$psd->save();

echo "AFTER:\n";
echo "  Selected: " . json_encode($cm['ui']['selected']) . "\n";
echo "  Primary: " . ($cm['ui']['primary'] ?? 'NULL') . "\n";
echo "  Updated at: {$psd->updated_at}\n\n";

echo "✅ CATEGORIES FIXED\n\n";

echo "=== COMPLETE ===\n";
