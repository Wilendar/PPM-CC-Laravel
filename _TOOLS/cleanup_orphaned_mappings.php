<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

echo "=== CLEANUP ORPHANED CATEGORY MAPPINGS ===\n\n";

// Find all category mappings
$categoryMappings = ShopMapping::where('mapping_type', ShopMapping::TYPE_CATEGORY)
    ->where('is_active', true)
    ->orderBy('id')
    ->get();

echo "Total active category mappings: " . $categoryMappings->count() . "\n\n";

$orphanedMappings = [];

foreach ($categoryMappings as $mapping) {
    // Check if PPM category exists (ppm_value contains category ID as string)
    $ppmCategoryId = (int) $mapping->ppm_value;
    $categoryExists = Category::where('id', $ppmCategoryId)->exists();

    if (!$categoryExists) {
        $orphanedMappings[] = $mapping;
        echo "[ORPHANED] Mapping ID: {$mapping->id} | ";
        echo "Shop: {$mapping->shop_id} | ";
        echo "PrestaShop ID: {$mapping->prestashop_id} | ";
        echo "PPM Value: {$mapping->ppm_value}\n";
    }
}

echo "\n=== CLEANUP SUMMARY ===\n";
echo "Orphaned mappings found: " . count($orphanedMappings) . "\n";

if (empty($orphanedMappings)) {
    echo "✅ No orphaned mappings to clean up.\n";
    exit(0);
}

// Ask for confirmation (auto-confirm for script)
echo "\n⚠️ WARNING: This will DELETE " . count($orphanedMappings) . " orphaned mappings!\n";
echo "Proceeding with cleanup...\n\n";

DB::transaction(function () use ($orphanedMappings) {
    $deletedCount = 0;

    foreach ($orphanedMappings as $mapping) {
        try {
            $mapping->delete();
            $deletedCount++;
            echo "✓ Deleted mapping ID: {$mapping->id} (PrestaShop ID: {$mapping->prestashop_id})\n";
        } catch (\Exception $e) {
            echo "✗ Failed to delete mapping ID: {$mapping->id} - Error: {$e->getMessage()}\n";
        }
    }

    echo "\n✅ Transaction completed successfully!\n";
    echo "Deleted {$deletedCount} orphaned mappings.\n";
});

echo "\n=== CLEANUP COMPLETED ===\n";
