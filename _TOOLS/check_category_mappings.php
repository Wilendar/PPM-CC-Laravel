<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;
use App\Models\PrestaShopShop;

echo "=== MAPOWANIA KATEGORII DLA PS ID=2 (Wszystko/Home) ===\n\n";

$shops = PrestaShopShop::all();

foreach ($shops as $shop) {
    echo "Shop: {$shop->name} (ID: {$shop->id})\n";

    // Check mapping for PS category id=2
    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', 'category')
        ->where('prestashop_id', 2)
        ->first();

    if ($mapping) {
        $ppmCategory = Category::find($mapping->ppm_value);
        echo "  PS ID=2 -> PPM ID={$mapping->ppm_value} ({$ppmCategory?->name})\n";
    } else {
        echo "  PS ID=2 -> BRAK MAPOWANIA!\n";
    }

    // Check if PPM "Wszystko" (level=1) has mapping
    $wszystko = Category::where('name', 'Wszystko')->where('level', 1)->first();
    if ($wszystko) {
        $wszMapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('ppm_value', $wszystko->id)
            ->first();

        if ($wszMapping) {
            echo "  PPM 'Wszystko' (ID={$wszystko->id}) -> PS ID={$wszMapping->prestashop_id}\n";
        } else {
            echo "  PPM 'Wszystko' (ID={$wszystko->id}) -> BRAK MAPOWANIA!\n";
        }
    }

    echo "\n";
}

echo "=== PODSUMOWANIE ===\n";
$wszystko = Category::where('name', 'Wszystko')->where('level', 1)->first();
echo "PPM 'Wszystko' ID: " . ($wszystko ? $wszystko->id : "NIE ZNALEZIONO") . "\n";
echo "PPM 'Wszystko' Level: " . ($wszystko ? $wszystko->level : "N/A") . "\n";
