<?php
// Clear elementStyles for product 11183, shop 5

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductDescription;

$description = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if ($description) {
    $blocks = $description->blocks_v2;
    if (isset($blocks[0])) {
        $blocks[0]['elementStyles'] = [];
        $description->blocks_v2 = $blocks;
        $description->save();
        echo "Cleared elementStyles for block 0\n";
    }
} else {
    echo "Description not found\n";
}
