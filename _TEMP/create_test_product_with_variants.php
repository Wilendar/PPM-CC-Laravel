<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Log::info('=== CREATING TEST PRODUCT WITH VARIANTS ===');

$masterProduct = Product::create([
    'sku' => 'TEST-KONWERSJA-' . time(),
    'name' => 'Produkt Testowy - Konwersja Wariantow',
    'description' => 'Produkt testowy utworzony dla testowania konwersji wariantow',
    'is_variant_master' => true,
    'is_active' => true,
    'sort_order' => 0,
]);

Log::info('Master product created', [
    'id' => $masterProduct->id,
    'sku' => $masterProduct->sku,
    'is_variant_master' => $masterProduct->is_variant_master,
]);

echo "Master Product Created:\n";
echo "   ID: " . $masterProduct->id . "\n";
echo "   SKU: " . $masterProduct->sku . "\n";
echo "   is_variant_master: " . $masterProduct->is_variant_master . "\n\n";

$colors = ['Czerwony', 'Niebieski', 'Zielony'];
$variantIds = [];

foreach ($colors as $index => $color) {
    $variantId = DB::table('product_variants')->insertGetId([
        'product_id' => $masterProduct->id,
        'sku' => $masterProduct->sku . '-VAR-' . ($index + 1),
        'name' => $masterProduct->name . ' - ' . $color,
        'is_active' => true,
        'is_default' => ($index === 0),
        'position' => $index,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $variantIds[] = $variantId;

    Log::info('Variant created', [
        'variant_id' => $variantId,
        'product_id' => $masterProduct->id,
        'sku' => $masterProduct->sku . '-VAR-' . ($index + 1),
        'name' => $masterProduct->name . ' - ' . $color,
    ]);

    $num = $index + 1;
    echo "Variant #$num Created:\n";
    echo "   Variant ID: " . $variantId . "\n";
    echo "   SKU: " . $masterProduct->sku . '-VAR-' . ($index + 1) . "\n";
    echo "   Name: " . $masterProduct->name . ' - ' . $color . "\n";
    echo "   is_default: " . ($index === 0 ? '1' : '0') . "\n\n";
}

if (!empty($variantIds)) {
    $masterProduct->update(['default_variant_id' => $variantIds[0]]);
}

$masterProduct->refresh();
$variantsCount = $masterProduct->variants()->count();

echo "=== VERIFICATION ===\n";
echo "Master Product ID: " . $masterProduct->id . "\n";
echo "Variants Count: " . $variantsCount . "\n";
echo "is_variant_master: " . $masterProduct->is_variant_master . "\n";
echo "default_variant_id: " . $masterProduct->default_variant_id . "\n\n";

if ($variantsCount === 3) {
    echo "SUCCESS: All variants created correctly!\n";
    echo "Product ready for conversion testing!\n";
    echo "\nTest URL: https://ppm.mpptrade.pl/admin/products/" . $masterProduct->id . "/edit\n";
} else {
    echo "ERROR: Expected 3 variants, found " . $variantsCount . "\n";
}

Log::info('=== TEST PRODUCT CREATION COMPLETE ===', [
    'master_product_id' => $masterProduct->id,
    'variants_count' => $variantsCount,
    'variant_ids' => $variantIds,
]);
