<?php
// Re-sync test products to push corrected tax rules

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;

echo "=== RE-SYNC TEST PRODUCTS ===\n\n";

$testSKUs = ['PB-KAYO-E-KMB', 'Q-KAYO-EA70'];
$shop = PrestaShopShop::where('name', 'B2B Test DEV')->first();

if (!$shop) {
    echo "❌ Shop 'B2B Test DEV' not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "Tax Rules Mapping:\n";
echo "  23% VAT → Group ID: " . ($shop->tax_rules_group_id_23 ?? 'NULL') . "\n\n";

foreach ($testSKUs as $sku) {
    echo str_repeat('-', 80) . "\n";
    echo "PRODUCT: {$sku}\n";

    $product = Product::where('sku', $sku)->first();

    if (!$product) {
        echo "  ❌ Not found in PPM\n";
        continue;
    }

    echo "  ✅ Found (ID: {$product->id})\n";
    echo "  Tax Rate: {$product->tax_rate}%\n";

    try {
        // Dispatch sync job
        SyncProductToPrestaShop::dispatch($product, $shop)
            ->onQueue('default');

        echo "  ✅ Sync job dispatched to queue\n";
    } catch (\Exception $e) {
        echo "  ❌ Failed to dispatch: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "✅ Sync jobs dispatched. Run queue worker to process:\n";
echo "   php artisan queue:work --once --queue=default\n\n";
