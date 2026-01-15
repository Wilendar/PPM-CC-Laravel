<?php
/**
 * Find BUGGY products in PrestaShop
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

$shopId = 1;

echo "=== FIND BUGGY PRODUCTS IN PRESTASHOP ===\n\n";

$shop = PrestaShopShop::find($shopId);
$client = PrestaShopClientFactory::create($shop);

echo "Shop: {$shop->name}\n\n";

// Search for products with KAYO S in name or reference
echo "1. Searching for products with 'KAYO' in reference...\n";

try {
    // Get products with KAYO in reference
    $response = $client->getProducts([
        'filter[reference]' => '[kayo]%',
        'display' => '[id,reference,name]',
        'limit' => 20,
    ]);

    $products = $response['products']['product'] ?? $response['products'] ?? [];
    if (isset($products['id'])) {
        $products = [$products];
    }

    echo "   Found: " . count($products) . " products\n\n";

    if (!empty($products)) {
        foreach ($products as $p) {
            $name = is_array($p['name'])
                ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A')
                : ($p['name'] ?? 'N/A');
            echo "   PS ID: {$p['id']} | Ref: {$p['reference']} | {$name}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n2. Checking PS categories for buggy...\n";

try {
    // Get all categories
    $response = $client->getCategories([
        'display' => '[id,name,id_parent]',
        'limit' => 500,
    ]);

    $categories = $response['categories']['category'] ?? $response['categories'] ?? [];
    if (isset($categories['id'])) {
        $categories = [$categories];
    }

    // Find buggy categories
    $buggyCategories = [];
    foreach ($categories as $c) {
        $name = is_array($c['name'])
            ? ($c['name']['language'][0]['value'] ?? $c['name']['language']['value'] ?? '')
            : ($c['name'] ?? '');

        if (stripos($name, 'buggy') !== false || stripos($name, 'kayo') !== false) {
            $buggyCategories[] = [
                'id' => $c['id'],
                'name' => $name,
                'parent' => $c['id_parent'] ?? 'N/A',
            ];
        }
    }

    echo "   Found " . count($buggyCategories) . " buggy-related categories:\n";
    foreach ($buggyCategories as $bc) {
        echo "   - ID: {$bc['id']} | Parent: {$bc['parent']} | {$bc['name']}\n";
    }

    // Try to get products from first buggy category
    if (!empty($buggyCategories)) {
        $testCatId = $buggyCategories[0]['id'];
        echo "\n3. Testing category {$testCatId} ({$buggyCategories[0]['name']})...\n";

        $response = $client->getProducts([
            'filter[id_category_default]' => $testCatId,
            'display' => '[id,reference,name]',
            'limit' => 10,
        ]);

        $products = $response['products']['product'] ?? $response['products'] ?? [];
        if (isset($products['id'])) {
            $products = [$products];
        }

        echo "   Products in category: " . count($products) . "\n";

        foreach (array_slice($products, 0, 5) as $p) {
            $name = is_array($p['name'])
                ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A')
                : ($p['name'] ?? 'N/A');
            echo "   - PS ID: {$p['id']} | Ref: {$p['reference']} | {$name}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// 4. Get any product with compatibility features 431/433
echo "\n4. Finding any product with compatibility features...\n";

try {
    // Get some products
    $response = $client->getProducts([
        'display' => 'full',
        'limit' => 50,
    ]);

    $products = $response['products']['product'] ?? $response['products'] ?? [];
    if (isset($products['id'])) {
        $products = [$products];
    }

    $foundWithCompat = 0;
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

        if (!empty($compatFeatures)) {
            $name = is_array($p['name'])
                ? ($p['name']['language'][0]['value'] ?? $p['name']['language']['value'] ?? 'N/A')
                : ($p['name'] ?? 'N/A');

            echo "   PS ID: {$p['id']} | Ref: {$p['reference']} | Compat Features: " . count($compatFeatures) . "\n";
            echo "      Name: {$name}\n";

            $foundWithCompat++;
            if ($foundWithCompat >= 5) {
                break;
            }
        }
    }

    echo "\n   Total products with compatibility: {$foundWithCompat}\n";

} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
