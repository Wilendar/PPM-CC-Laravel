<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\Category;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;

echo "=== NAPRAWA MAPOWAŃ KATEGORII 'WSZYSTKO' ===\n\n";

// Find correct PPM "Wszystko" category (level=1, child of Baza)
$wszystko = Category::where('name', 'Wszystko')->where('level', 1)->first();

if (!$wszystko) {
    echo "BŁĄD: Nie znaleziono kategorii 'Wszystko' (level=1)!\n";
    exit(1);
}

echo "Poprawna kategoria PPM 'Wszystko': ID={$wszystko->id}, Level={$wszystko->level}\n\n";

DB::beginTransaction();

try {
    $shops = PrestaShopShop::all();

    foreach ($shops as $shop) {
        echo "Shop: {$shop->name} (ID: {$shop->id})\n";

        // Find existing mapping for PS category id=2
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', 2)
            ->first();

        if ($mapping) {
            if ($mapping->ppm_value != $wszystko->id) {
                echo "  NAPRAWIAM: PS ID=2 -> PPM zmiana z {$mapping->ppm_value} na {$wszystko->id}\n";
                $mapping->update(['ppm_value' => (string) $wszystko->id]);
            } else {
                echo "  OK: PS ID=2 -> PPM ID={$mapping->ppm_value}\n";
            }
        } else {
            echo "  TWORZĘ: nowe mapowanie PS ID=2 -> PPM ID={$wszystko->id}\n";
            ShopMapping::create([
                'shop_id' => $shop->id,
                'mapping_type' => 'category',
                'ppm_value' => (string) $wszystko->id,
                'prestashop_id' => 2,
                'prestashop_value' => 'Wszystko',
                'is_active' => true,
            ]);
        }
    }

    DB::commit();
    echo "\n=== MAPOWANIA NAPRAWIONE ===\n";

} catch (\Exception $e) {
    DB::rollback();
    echo "BŁĄD: " . $e->getMessage() . "\n";
    exit(1);
}

// Delete orphaned categories (Wszystko with level=0)
echo "\n=== USUWANIE BŁĘDNYCH KATEGORII 'WSZYSTKO' (level=0) ===\n";
$badCategories = Category::where('name', 'Wszystko')->where('level', 0)->get();

foreach ($badCategories as $bad) {
    $productsCount = $bad->products()->count();
    $childrenCount = $bad->children()->count();

    if ($productsCount === 0 && $childrenCount === 0) {
        echo "Usuwam kategorię ID={$bad->id} (level=0, products={$productsCount}, children={$childrenCount})\n";
        $bad->forceDelete();
    } else {
        echo "NIE MOŻNA usunąć ID={$bad->id} - ma produkty ({$productsCount}) lub dzieci ({$childrenCount})\n";
    }
}

echo "\n=== GOTOWE ===\n";
