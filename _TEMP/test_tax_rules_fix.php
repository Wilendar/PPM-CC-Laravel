<?php
// Quick test of tax rules fix

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ProductTransformer;
use Illuminate\Support\Facades\DB;

echo "=== TAX RULES FIX VERIFICATION ===\n\n";

// 1. Check shop configuration
$shop = PrestaShopShop::find(1);

echo "SHOP: {$shop->name} (ID: {$shop->id})\n";
echo str_repeat('-', 80) . "\n";
echo "tax_rules_group_id_23: " . ($shop->tax_rules_group_id_23 ?? 'NULL') . "\n";
echo "tax_rules_group_id_8:  " . ($shop->tax_rules_group_id_8 ?? 'NULL') . "\n";
echo "tax_rules_group_id_5:  " . ($shop->tax_rules_group_id_5 ?? 'NULL') . "\n";
echo "tax_rules_group_id_0:  " . ($shop->tax_rules_group_id_0 ?? 'NULL') . "\n";
echo "tax_rules_last_fetched_at: " . ($shop->tax_rules_last_fetched_at ?? 'NULL') . "\n\n";

// 2. Test ProductTransformer
$product = Product::where('sku', 'PB-KAYO-E-KMB')->first();

if (!$product) {
    echo "❌ Product PB-KAYO-E-KMB not found\n";
    exit(1);
}

echo "PRODUCT: {$product->sku}\n";
echo "Tax Rate: {$product->tax_rate}%\n";
echo str_repeat('-', 80) . "\n";

$client = PrestaShopClientFactory::create($shop);
$transformer = app(ProductTransformer::class);

try {
    $prestashopData = $transformer->transformForPrestaShop($product, $client);
    $psProduct = $prestashopData['product'] ?? [];

    echo "ProductTransformer Output:\n";
    echo "  id_tax_rules_group: " . ($psProduct['id_tax_rules_group'] ?? 'MISSING') . "";

    if (($psProduct['id_tax_rules_group'] ?? null) == 6) {
        echo " ✅ CORRECT!\n";
    } elseif (($psProduct['id_tax_rules_group'] ?? null) == 1) {
        echo " ❌ WRONG (still using fallback/old value)\n";
    } else {
        echo " ⚠️ UNEXPECTED VALUE\n";
    }

    echo "\n";

    // Reload shop and check again
    $shop->refresh();
    echo "SHOP CONFIG AFTER TRANSFORM:\n";
    echo "tax_rules_group_id_23: " . ($shop->tax_rules_group_id_23 ?? 'NULL');

    if ($shop->tax_rules_group_id_23 == 6) {
        echo " ✅ CORRECT (auto-detected to 6)\n";
    } elseif ($shop->tax_rules_group_id_23 == 1) {
        echo " ❌ WRONG (detected to 1)\n";
    } else {
        echo " ⚠️ " . ($shop->tax_rules_group_id_23 === null ? "NULL (not detected yet)" : "unexpected") . "\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
