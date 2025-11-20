<?php
// Reset tax rules for shop to trigger auto-detection

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;

echo "=== RESET TAX RULES FOR AUTO-DETECTION ===\n\n";

$shop = PrestaShopShop::where('name', 'B2B Test DEV')->first();

if (!$shop) {
    echo "❌ Shop 'B2B Test DEV' not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "Before reset:\n";
echo "  tax_rules_group_id_23: " . ($shop->tax_rules_group_id_23 ?? 'NULL') . "\n";
echo "  tax_rules_last_fetched_at: " . ($shop->tax_rules_last_fetched_at ?? 'NULL') . "\n\n";

$updated = $shop->update([
    'tax_rules_group_id_23' => null,
    'tax_rules_group_id_8' => null,
    'tax_rules_group_id_5' => null,
    'tax_rules_group_id_0' => null,
    'tax_rules_last_fetched_at' => null,
]);

echo "Update result: " . ($updated ? '✅ SUCCESS' : '❌ FAILED') . "\n\n";

$shop->refresh();

echo "After reset:\n";
echo "  tax_rules_group_id_23: " . ($shop->tax_rules_group_id_23 ?? 'NULL') . " ✅\n";
echo "  tax_rules_last_fetched_at: " . ($shop->tax_rules_last_fetched_at ?? 'NULL') . " ✅\n\n";

echo "✅ Shop ready for auto-detection on next product sync!\n";
