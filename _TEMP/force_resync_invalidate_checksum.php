<?php
// Force re-sync by invalidating checksum

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductShopData;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Models\PrestaShopShop;

echo "=== FORCE RE-SYNC BY INVALIDATING CHECKSUM ===\n\n";

$testSKUs = ['PB-KAYO-E-KMB', 'Q-KAYO-EA70'];
$shop = PrestaShopShop::where('name', 'B2B Test DEV')->first();

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

foreach ($testSKUs as $sku) {
    echo str_repeat('-', 80) . "\n";
    echo "PRODUCT: {$sku}\n";

    $product = Product::where('sku', $sku)->first();

    if (!$product) {
        echo "  ❌ Not found\n";
        continue;
    }

    $psd = ProductShopData::where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->first();

    if (!$psd) {
        echo "  ⚠️ No ProductShopData record\n";
        continue;
    }

    echo "  Current state:\n";
    echo "    sync_status: {$psd->sync_status}\n";
    echo "    sync_checksum: " . ($psd->sync_checksum ?? 'NULL') . "\n";
    echo "    prestashop_product_id: " . ($psd->prestashop_product_id ?? 'NULL') . "\n\n";

    // Invalidate checksum to force re-sync
    $psd->update([
        'sync_checksum' => null,
        'sync_status' => 'pending',
    ]);

    echo "  ✅ Checksum invalidated, sync_status set to pending\n";

    // Dispatch sync job
    SyncProductToPrestaShop::dispatch($product, $shop)
        ->onQueue('default');

    echo "  ✅ Sync job dispatched\n\n";
}

echo "✅ Ready to process. Run:\n";
echo "   php artisan queue:work --once --queue=default\n\n";
