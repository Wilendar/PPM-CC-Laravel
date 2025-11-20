<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use Illuminate\Support\Facades\DB;

echo "=== CHECK SHOP_MAPPINGS TABLE ===\n\n";

// Check if table exists
try {
    $exists = DB::select("SHOW TABLES LIKE 'shop_mappings'");

    if (empty($exists)) {
        echo "❌ TABLE shop_mappings DOES NOT EXIST\n\n";
        echo "Available tables:\n";
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            echo "  - {$tableName}\n";
        }
        die();
    }

    echo "✅ Table shop_mappings exists\n\n";

    // Get category mappings for shop 1
    $mappings = ShopMapping::where('shop_id', 1)
        ->where('mapping_type', 'category')
        ->where('is_active', true)
        ->get();

    echo "SHOP 1 CATEGORY MAPPINGS (from shop_mappings table):\n";
    echo "Count: " . $mappings->count() . "\n\n";

    if ($mappings->count() > 0) {
        foreach ($mappings as $mapping) {
            $ppmCat = DB::table('categories')->where('id', $mapping->ppm_value)->first();
            $ppmName = $ppmCat ? $ppmCat->name : 'NOT FOUND';

            echo "  PPM {$mapping->ppm_value} ({$ppmName}) → PrestaShop {$mapping->prestashop_id}";

            if ($mapping->prestashop_value) {
                echo " ({$mapping->prestashop_value})";
            }

            echo "\n";
        }
    } else {
        echo "  ❌ NO MAPPINGS FOUND\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== COMPLETE ===\n";
