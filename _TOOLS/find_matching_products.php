<?php
/**
 * Find PS products with compatibility matching our PPM vehicles
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

$shopId = 1;

echo "=== FIND MATCHING PS PRODUCTS ===\n\n";

// Our PPM vehicles
$vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);
echo "1. PPM Vehicles:\n";
foreach ($vehicles as $v) {
    echo "   - {$v->sku}: {$v->name}\n";
}

// Keywords to search in PS feature values
$searchKeywords = ['S200', 'S70', 'AU150', 'eDIRT', 'edirt', 'MRF', 'YCF', 'W50', 'W88'];

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "\n2. Searching PS for products with matching compatibility features...\n\n";

// Get products with compatibility features
try {
    $response = $client->getProducts([
        'display' => 'full',
        'limit' => 200,
    ]);

    $products = $response['products']['product'] ?? $response['products'] ?? [];
    if (isset($products['id'])) {
        $products = [$products];
    }

    echo "   Total products fetched: " . count($products) . "\n\n";

    $matchingProducts = [];

    foreach ($products as $p) {
        $features = $p['associations']['product_features'] ?? [];

        if (isset($features['product_feature'])) {
            $pf = $features['product_feature'];
            if (isset($pf['id'])) {
                $features = [$pf];
            } else {
                $features = $pf;
            }
        }

        $compatFeatures = array_filter($features, fn($f) => in_array((int)$f['id'], [431, 433]));

        if (empty($compatFeatures)) {
            continue;
        }

        // Check each feature value
        foreach ($compatFeatures as $cf) {
            $fvid = (int) $cf['id_feature_value'];

            try {
                $fvResponse = $client->getProductFeatureValue($fvid);
                $fvData = $fvResponse['product_feature_value'] ?? $fvResponse;
                $valueField = $fvData['value'] ?? [];

                $valueName = '';
                if (is_string($valueField)) {
                    $valueName = $valueField;
                } elseif (is_array($valueField)) {
                    if (isset($valueField['language'])) {
                        $lang = $valueField['language'];
                        if (is_array($lang)) {
                            $valueName = $lang[0]['value'] ?? $lang['value'] ?? '';
                        } else {
                            $valueName = $lang;
                        }
                    }
                }

                // Check if any keyword matches
                foreach ($searchKeywords as $keyword) {
                    if (stripos($valueName, $keyword) !== false) {
                        $name = is_array($p['name'])
                            ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A')
                            : ($p['name'] ?? 'N/A');

                        $matchingProducts[] = [
                            'ps_id' => $p['id'],
                            'reference' => $p['reference'],
                            'name' => $name,
                            'vehicle' => $valueName,
                            'keyword' => $keyword,
                        ];
                        break 2; // Found match, next product
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }

    echo "3. Products with matching compatibility:\n\n";

    if (empty($matchingProducts)) {
        echo "   No products found with compatibility to our vehicles!\n";
    } else {
        foreach (array_slice($matchingProducts, 0, 20) as $mp) {
            echo "   PS ID: {$mp['ps_id']} | Ref: {$mp['reference']}\n";
            echo "      Name: {$mp['name']}\n";
            echo "      Vehicle: {$mp['vehicle']} (matched: {$mp['keyword']})\n\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
