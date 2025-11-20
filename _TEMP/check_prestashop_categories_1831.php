<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PrestaShop Product 1831 Categories (Direct DB Check) ===\n\n";

try {
    $categories = DB::connection('prestashop_dev')
        ->table('ps_category_product')
        ->where('id_product', 1831)
        ->get(['id_category']);

    echo "PrestaShop Categories for Product 1831:\n";
    foreach ($categories as $cat) {
        // Get category name
        $categoryData = DB::connection('prestashop_dev')
            ->table('ps_category_lang')
            ->where('id_category', $cat->id_category)
            ->where('id_lang', 1) // Polish
            ->first(['name']);

        $name = $categoryData ? $categoryData->name : 'UNKNOWN';
        echo "  - PrestaShop Category ID: {$cat->id_category} ({$name})\n";
    }

    echo "\nTotal: " . $categories->count() . " categories\n\n";

    // Get default category
    $product = DB::connection('prestashop_dev')
        ->table('ps_product')
        ->where('id_product', 1831)
        ->first(['id_category_default']);

    $defaultName = DB::connection('prestashop_dev')
        ->table('ps_category_lang')
        ->where('id_category', $product->id_category_default)
        ->where('id_lang', 1)
        ->first(['name']);

    echo "Default Category: {$product->id_category_default} (" . ($defaultName ? $defaultName->name : 'UNKNOWN') . ")\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
