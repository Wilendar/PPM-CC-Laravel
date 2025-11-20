<?php
// Diagnose PrestaShop Tax Rules and Specific Prices Issues

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "=== PRESTASHOP TAX & SPECIFIC PRICES DIAGNOSTIC ===\n\n";

// Get active shops
$shops = PrestaShopShop::where('is_active', true)->get();

if ($shops->isEmpty()) {
    echo "❌ No active PrestaShop shops found\n";
    exit(1);
}

foreach ($shops as $shop) {
    echo str_repeat('=', 100) . "\n";
    echo "SHOP: {$shop->name} (ID: {$shop->id})\n";
    echo "API URL: {$shop->api_url}\n";
    echo "Version: {$shop->version}\n";
    echo str_repeat('=', 100) . "\n\n";

    try {
        $client = PrestaShopClientFactory::create($shop);

        // 1. Get recent products
        echo "📦 RECENT PRODUCTS (last 10 updated):\n";
        echo str_repeat('-', 100) . "\n";

        $productsResponse = $client->getProducts([
            'display' => 'full',
            'limit' => 10
        ]);

        if (isset($productsResponse['products']) && is_array($productsResponse['products'])) {
            $productIds = [];

            printf("%-10s %-20s %-30s %-12s %-18s %-20s\n",
                'ID', 'Reference', 'Name', 'Price', 'Tax Rules Group', 'Last Updated');
            echo str_repeat('-', 100) . "\n";

            foreach ($productsResponse['products'] as $productData) {
                // Handle both array and object responses
                $product = is_array($productData) ? $productData : (array) $productData;

                $productId = $product['id'] ?? null;
                $reference = $product['reference'] ?? 'N/A';
                $name = is_array($product['name'] ?? null) ?
                    ($product['name']['language'][0]['value'] ?? 'N/A') :
                    ($product['name'] ?? 'N/A');
                $price = $product['price'] ?? '0.00';
                $taxRulesGroup = $product['id_tax_rules_group'] ?? 'NULL';
                $dateUpd = $product['date_upd'] ?? 'N/A';

                printf("%-10s %-20s %-30s %-12s %-18s %-20s\n",
                    $productId,
                    substr($reference, 0, 20),
                    substr($name, 0, 30),
                    number_format((float)$price, 2),
                    $taxRulesGroup,
                    $dateUpd
                );

                if ($productId) {
                    $productIds[] = $productId;
                }
            }

            echo "\n";

            // 2. Check specific_prices for these products
            echo "💰 SPECIFIC PRICES FOR RECENT PRODUCTS:\n";
            echo str_repeat('-', 100) . "\n";

            $hasAnySpecificPrices = false;

            foreach ($productIds as $productId) {
                try {
                    $specificPricesResponse = $client->getSpecificPrices($productId);

                    if (isset($specificPricesResponse['specific_prices']) &&
                        is_array($specificPricesResponse['specific_prices']) &&
                        !empty($specificPricesResponse['specific_prices'])) {

                        $hasAnySpecificPrices = true;

                        echo "\n📌 Product ID: {$productId}\n";
                        printf("   %-10s %-10s %-10s %-15s %-10s %-15s\n",
                            'SP ID', 'Shop', 'Group', 'Price Override', 'Reduction', 'Type');
                        echo "   " . str_repeat('-', 90) . "\n";

                        foreach ($specificPricesResponse['specific_prices'] as $spData) {
                            $sp = is_array($spData) ? $spData : (array) $spData;

                            $spId = $sp['id'] ?? 'N/A';
                            $idShop = $sp['id_shop'] ?? '0';
                            $idGroup = $sp['id_group'] ?? '0';
                            $priceOverride = $sp['price'] ?? '-1';
                            $reduction = $sp['reduction'] ?? '0';
                            $reductionType = $sp['reduction_type'] ?? 'N/A';

                            printf("   %-10s %-10s %-10s %-15s %-10s %-15s\n",
                                $spId,
                                $idShop,
                                $idGroup,
                                $priceOverride == -1 ? 'Use base' : number_format((float)$priceOverride, 2),
                                $reduction,
                                $reductionType
                            );
                        }
                    }
                } catch (\Exception $e) {
                    // Specific prices not found is normal - ignore 404
                    if (!str_contains($e->getMessage(), '404')) {
                        echo "   ⚠️  Product {$productId}: Error - " . $e->getMessage() . "\n";
                    }
                }
            }

            if (!$hasAnySpecificPrices) {
                echo "\n❌ NO SPECIFIC PRICES FOUND FOR ANY RECENT PRODUCTS\n";
                echo "   This means specific_prices are NOT being created by PrestaShopPriceExporter!\n";
            }

        } else {
            echo "⚠️  No products found or invalid response\n";
        }

        echo "\n";

        // 3. Get tax rules groups
        echo "📋 TAX RULES GROUPS:\n";
        echo str_repeat('-', 100) . "\n";

        try {
            $taxRulesResponse = $client->makeRequest('GET', '/tax_rule_groups?display=full');

            if (isset($taxRulesResponse['tax_rule_groups']) && is_array($taxRulesResponse['tax_rule_groups'])) {
                printf("%-10s %-40s %-10s\n", 'ID', 'Name', 'Active');
                echo str_repeat('-', 100) . "\n";

                foreach ($taxRulesResponse['tax_rule_groups'] as $trgData) {
                    $trg = is_array($trgData) ? $trgData : (array) $trgData;

                    $id = $trg['id'] ?? 'N/A';
                    $name = is_array($trg['name'] ?? null) ?
                        ($trg['name']['language'][0]['value'] ?? 'N/A') :
                        ($trg['name'] ?? 'N/A');
                    $active = ($trg['active'] ?? '0') == '1' ? 'Yes' : 'No';

                    printf("%-10s %-40s %-10s\n", $id, substr($name, 0, 40), $active);
                }
            }
        } catch (\Exception $e) {
            echo "⚠️  Failed to fetch tax rules: " . $e->getMessage() . "\n";
        }

        echo "\n";

        // 4. Get customer groups (for specific_prices mapping)
        echo "👥 CUSTOMER GROUPS:\n";
        echo str_repeat('-', 100) . "\n";

        try {
            $groupsResponse = $client->getPriceGroups();

            if (isset($groupsResponse['groups']) && is_array($groupsResponse['groups'])) {
                printf("%-10s %-40s %-15s %-10s\n", 'ID', 'Name', 'Price Display', 'Reduction');
                echo str_repeat('-', 100) . "\n";

                foreach ($groupsResponse['groups'] as $groupData) {
                    $group = is_array($groupData) ? $groupData : (array) $groupData;

                    $id = $group['id'] ?? 'N/A';
                    $name = is_array($group['name'] ?? null) ?
                        ($group['name']['language'][0]['value'] ?? 'N/A') :
                        ($group['name'] ?? 'N/A');
                    $priceDisplay = $group['price_display_method'] ?? 'N/A';
                    $reduction = $group['reduction'] ?? '0';

                    printf("%-10s %-40s %-15s %-10s\n",
                        $id,
                        substr($name, 0, 40),
                        $priceDisplay == 0 ? 'Tax excluded' : 'Tax included',
                        $reduction
                    );
                }
            }
        } catch (\Exception $e) {
            echo "⚠️  Failed to fetch customer groups: " . $e->getMessage() . "\n";
        }

    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

    echo "\n\n";
}

echo "=== DIAGNOSTIC COMPLETED ===\n\n";

// ANALYSIS SUMMARY
echo "🔍 KEY FINDINGS TO CHECK:\n";
echo "1. Products with id_tax_rules_group = NULL or 0 → Tax rules NOT being preserved during update\n";
echo "2. No specific_prices found → PrestaShopPriceExporter NOT creating specific_prices\n";
echo "3. Tax rules groups list → Verify correct mapping in ProductTransformer::mapTaxRate()\n";
echo "4. Customer groups list → Verify mapping in PriceGroupMapper\n";
echo "\n";
