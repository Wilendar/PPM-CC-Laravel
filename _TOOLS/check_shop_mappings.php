<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;

echo "=== SHOP MAPPINGS (CATEGORY TYPE) ===\n\n";

$categoryMappings = ShopMapping::where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->where('is_active', true)
    ->orderBy('id')
    ->get();

echo "Total active category mappings: " . $categoryMappings->count() . "\n\n";

$orphanedCount = 0;

foreach ($categoryMappings as $mapping) {
    // Check if PPM category exists (ppm_value contains category ID as string)
    $ppmCategoryId = (int) $mapping->ppm_value;
    $categoryExists = Category::where('id', $ppmCategoryId)->exists();

    $status = $categoryExists ? '[OK]' : '[ORPHANED]';

    if (!$categoryExists) {
        $orphanedCount++;
    }

    echo "{$status} Mapping ID: {$mapping->id} | ";
    echo "Shop: {$mapping->shop_id} | ";
    echo "PrestaShop ID: {$mapping->prestashop_id} | ";
    echo "PPM Value: {$mapping->ppm_value} | ";
    echo "Exists: " . ($categoryExists ? 'YES' : 'NO') . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total mappings: " . $categoryMappings->count() . "\n";
echo "Valid mappings: " . ($categoryMappings->count() - $orphanedCount) . "\n";
echo "Orphaned mappings: {$orphanedCount}\n";

if ($orphanedCount > 0) {
    echo "\n⚠️ WARNING: {$orphanedCount} orphaned mappings found!\n";
    echo "These mappings reference non-existent PPM categories.\n";
}

echo "\n=== END ===\n";
