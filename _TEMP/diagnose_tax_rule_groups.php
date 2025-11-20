<?php
/**
 * DIAGNOSTIC: Check availableTaxRuleGroups content for shop_id=1
 *
 * Purpose: Verify if tax rule groups contain rate 8.00
 * Root Cause Analysis: Dropdown not showing saved tax_rate_override value
 */

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TaxRateService;
use App\Models\PrestaShopShop;

echo "\n=== TAX RULE GROUPS DIAGNOSTIC ===\n";

// Get shop
$shopId = 1;
$shop = PrestaShopShop::find($shopId);

if (!$shop) {
    echo "❌ ERROR: Shop not found (id: $shopId)\n";
    exit(1);
}

echo "✅ Shop found: {$shop->name} (ID: {$shop->id})\n";
echo "   URL: {$shop->shop_url}\n\n";

// Get tax rule groups using TaxRateService
try {
    $taxRateService = app(TaxRateService::class);
    $taxRuleGroups = $taxRateService->getAvailableTaxRatesForShop($shop);

    echo "📊 Tax Rule Groups (count: " . count($taxRuleGroups) . "):\n";
    echo str_repeat("-", 80) . "\n";

    $has8 = false;

    foreach ($taxRuleGroups as $index => $group) {
        $rate = $group['rate'] ?? 'N/A';
        $label = $group['label'] ?? 'N/A';
        $id = $group['id'] ?? 'N/A';

        echo sprintf(
            "[%d] ID: %s | Rate: %s | Label: %s\n",
            $index + 1,
            $id,
            $rate,
            $label
        );

        if ($rate == 8.00 || $rate == '8.00' || $rate == 8) {
            $has8 = true;
            echo "     ✅ MATCH: This is the 8.00% rate!\n";
        }
    }

    echo str_repeat("-", 80) . "\n";

    // Summary
    echo "\n=== ANALYSIS ===\n";

    if ($has8) {
        echo "✅ Rate 8.00% EXISTS in tax rule groups\n";
        echo "   → Blade template SHOULD generate <option value=\"8.00\">\n";
        echo "   → Problem is ELSEWHERE (Livewire reactivity issue?)\n";
    } else {
        echo "❌ Rate 8.00% NOT FOUND in tax rule groups\n";
        echo "   → Blade template CANNOT generate <option value=\"8.00\">\n";
        echo "   → User saved 8.00% but it's not mapped in PrestaShop!\n";
        echo "   → Dropdown shows 'use_default' because 8.00 option doesn't exist in DOM\n";
        echo "\n";
        echo "💡 SOLUTION: Add 8.00% tax rule group to PrestaShop, OR\n";
        echo "              Allow custom rates in dropdown (not just PrestaShop mapped)\n";
    }

    echo "\n=== RAW DATA ===\n";
    print_r($taxRuleGroups);

} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n✅ Diagnostic complete\n\n";
