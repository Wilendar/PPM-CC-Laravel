<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SHOP 1 CATEGORY MAPPINGS ===\n\n";

$shop = DB::table('prestashop_shops')->where('id', 1)->first();

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

$mappings = json_decode($shop->category_mappings, true);

echo "CURRENT MAPPINGS:\n";
echo json_encode($mappings, JSON_PRETTY_PRINT) . "\n\n";

echo "MAPPING DETAILS:\n";
foreach ($mappings as $psId => $ppmId) {
    $ppmCat = DB::table('categories')->where('id', $ppmId)->first();
    $ppmName = $ppmCat ? $ppmCat->name : 'NOT FOUND';

    echo "  PrestaShop {$psId} → PPM {$ppmId} ({$ppmName})\n";
}

echo "\n=== COMPLETE ===\n";
