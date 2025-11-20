<?php

// Cleanup ghost categories for product 11034, shop 1

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\Category;

echo "=== CLEANUP GHOST CATEGORIES - Product 11034, Shop 1 ===\n\n";

// Load ProductShopData
$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "❌ ProductShopData NOT FOUND\n";
    exit(1);
}

echo "STEP 1: CURRENT STATE\n";
$cm = $psd->category_mappings;
$selected = $cm['ui']['selected'] ?? [];
$primary = $cm['ui']['primary'] ?? null;

echo "Selected: " . json_encode($selected) . "\n";
echo "Primary: " . ($primary ?? 'NULL') . "\n\n";

echo "STEP 2: VERIFY WHICH CATEGORIES EXIST\n";
foreach ($selected as $catId) {
    $cat = Category::find($catId);
    if ($cat) {
        echo "  ✅ Category $catId EXISTS: {$cat->name}\n";
    } else {
        echo "  ❌ Category $catId DOES NOT EXIST (ghost)\n";
    }
}
echo "\n";

echo "STEP 3: CLEANUP - Remove ghost categories\n";
$validSelected = [];
foreach ($selected as $catId) {
    if (Category::find($catId)) {
        $validSelected[] = $catId;
        echo "  ✅ KEEP: Category $catId\n";
    } else {
        echo "  🗑️  REMOVE: Ghost category $catId\n";
    }
}

// Check if primary is ghost
if ($primary && !Category::find($primary)) {
    echo "  🗑️  REMOVE: Ghost primary $primary\n";
    $primary = count($validSelected) > 0 ? $validSelected[0] : null;
    echo "  ✅ NEW PRIMARY: " . ($primary ?? 'NULL') . "\n";
}

// Update category_mappings - BOTH ui AND mappings sections
$cm['ui']['selected'] = $validSelected;
$cm['ui']['primary'] = $primary;

echo "\n";
echo "STEP 3.5: CLEANUP MAPPINGS SECTION (validator requirement)\n";

// CRITICAL: Also clean mappings section (validator requirement)
$oldMappings = $cm['mappings'] ?? [];
$newMappings = [];

foreach ($validSelected as $catId) {
    // Keep existing mapping data if exists, otherwise create empty
    if (isset($oldMappings[$catId])) {
        $newMappings[$catId] = $oldMappings[$catId];
        echo "  ✅ KEEP MAPPING: Category $catId\n";
    } else {
        $newMappings[$catId] = [];
        echo "  ➕ CREATE MAPPING: Category $catId (empty)\n";
    }
}

foreach (array_keys($oldMappings) as $catId) {
    if (!in_array($catId, $validSelected)) {
        echo "  🗑️  REMOVE MAPPING: Ghost category $catId\n";
    }
}

$cm['mappings'] = $newMappings;
$psd->category_mappings = $cm;
$psd->save();

echo "\nSTEP 4: FINAL STATE\n";
echo "Selected: " . json_encode($validSelected) . "\n";
echo "Primary: " . ($primary ?? 'NULL') . "\n";
echo "Updated at: {$psd->updated_at}\n\n";

echo "✅ CLEANUP COMPLETE\n\n";
echo "TEST IN BROWSER:\n";
echo "1. Hard refresh (Ctrl+F5)\n";
echo "2. Open product 11034\n";
echo "3. Click Shop Tab 'B2B Test DEV'\n";
echo "4. Should show ONLY categories 1 and 2 (without ghost 36)\n";
