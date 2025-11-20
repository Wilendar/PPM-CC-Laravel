<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   CREATE MAPPING: PS ID 1 'Baza' → PPM ID 1\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check if mapping already exists
$existing = DB::table('shop_mappings')
    ->where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->where('prestashop_id', 1)
    ->first();

if ($existing) {
    echo "⚠️  MAPPING ALREADY EXISTS:\n";
    echo "   PS ID: {$existing->prestashop_id}\n";
    echo "   PPM ID: {$existing->ppm_value}\n";
    echo "   Name: {$existing->prestashop_value}\n";
    echo "   Active: " . ($existing->is_active ? 'YES' : 'NO') . "\n\n";
    echo "❌ SKIPPING - mapping already exists\n\n";
    exit(0);
}

// Get PPM category name
$ppmCategory = DB::table('categories')->where('id', 1)->first();

if (!$ppmCategory) {
    echo "❌ ERROR: PPM category ID 1 NOT FOUND\n\n";
    exit(1);
}

// Create mapping
try {
    DB::table('shop_mappings')->insert([
        'shop_id' => 1,
        'mapping_type' => 'category',
        'prestashop_id' => 1,
        'ppm_value' => '1',
        'prestashop_value' => $ppmCategory->name,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "✅ MAPPING CREATED SUCCESSFULLY:\n\n";
    echo "   Shop ID: 1 (B2B Test DEV)\n";
    echo "   PrestaShop ID: 1 (Baza)\n";
    echo "   PPM ID: 1 ({$ppmCategory->name})\n";
    echo "   Active: YES\n\n";

    // Verify creation
    $newMapping = DB::table('shop_mappings')
        ->where('shop_id', 1)
        ->where('mapping_type', 'category')
        ->where('prestashop_id', 1)
        ->first();

    if ($newMapping) {
        echo "🔍 VERIFICATION:\n";
        echo "   Mapping ID: {$newMapping->id}\n";
        echo "   Created: {$newMapping->created_at}\n\n";
    }

    echo "🎯 RESULT: Auto-inject logic [1, 2] will now work correctly!\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR CREATING MAPPING:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n\n";
