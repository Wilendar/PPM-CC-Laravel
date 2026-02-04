<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check ATV-473 media mappings (user reported issue)
$product = App\Models\Product::where('sku', 'ATV-473')->with('media')->first();
if (!$product) {
    echo "Product GANG-035 not found\n";
    exit;
}

echo "Product: {$product->sku} (ID: {$product->id})\n";
echo "Media count: " . $product->media->count() . "\n\n";

foreach ($product->media as $media) {
    echo "Media ID: {$media->id}\n";
    echo "  Context: " . ($media->context ?? 'null') . "\n";
    echo "  is_active: " . ($media->is_active ? 'true' : 'false') . "\n";
    echo "  prestashop_mapping: " . json_encode($media->prestashop_mapping, JSON_PRETTY_PRINT) . "\n";
    echo "\n";
}

// Check PrestaShop shops
$shops = App\Models\PrestaShopShop::all();
echo "Available shops:\n";
foreach ($shops as $shop) {
    echo "  Shop ID: {$shop->id}, Name: {$shop->name}\n";
}
