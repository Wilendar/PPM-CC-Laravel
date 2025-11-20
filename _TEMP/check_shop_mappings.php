<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   SHOP MAPPINGS CHECK (shop_id=1, B2B Test DEV)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check if shop_mappings table exists
$tableExists = DB::select("SHOW TABLES LIKE 'shop_mappings'");

if (empty($tableExists)) {
    echo "❌ Table 'shop_mappings' DOES NOT EXIST!\n\n";
    exit(1);
}

echo "✅ Table 'shop_mappings' exists\n\n";

// Count category mappings for shop_id=1
$count = DB::table('shop_mappings')
    ->where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->count();

echo "Total category mappings for shop_id=1: {$count}\n\n";

if ($count === 0) {
    echo "⚠️  NO CATEGORY MAPPINGS FOUND for shop_id=1!\n";
    echo "    This means PrestaShop categories are NOT mapped to PPM categories.\n";
    echo "    User needs to PULL categories from PrestaShop first.\n\n";
} else {
    echo "First 20 category mappings:\n\n";

    $mappings = DB::table('shop_mappings')
        ->where('shop_id', 1)
        ->where('mapping_type', 'category')
        ->orderBy('prestashop_id')
        ->limit(20)
        ->get(['id', 'prestashop_id', 'ppm_value', 'prestashop_value', 'is_active']);

    foreach ($mappings as $mapping) {
        $status = $mapping->is_active ? '✅' : '❌';
        echo "  $status [PS: {$mapping->prestashop_id}] → [PPM: {$mapping->ppm_value}] ({$mapping->prestashop_value})\n";
    }

    echo "\n";

    // Check specific PrestaShop IDs that user tried to save (12, 23, 800)
    echo "Check user's selected PrestaShop IDs (12, 23, 800):\n\n";

    $testIds = [1, 2, 12, 23, 800];
    foreach ($testIds as $psId) {
        $mapping = DB::table('shop_mappings')
            ->where('shop_id', 1)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $psId)
            ->first();

        if ($mapping) {
            echo "  ✅ PS ID {$psId} → PPM ID {$mapping->ppm_value} ({$mapping->prestashop_value})\n";
        } else {
            echo "  ❌ PS ID {$psId} → NO MAPPING FOUND\n";
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
