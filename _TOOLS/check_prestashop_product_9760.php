/**
 * Check PrestaShop Product 9760 Details
 *
 * Sprawdza dlaczego produkt ID 9760 nie jest widoczny w admin panelu
 */

echo "=== PRESTASHOP PRODUCT 9760 CHECK ===\n\n";

// Get shop
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name} ({$shop->url})\n\n";

try {
    // Create client
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    echo "1. Fetching product 9760 from PrestaShop API...\n";

    // Get product details
    $response = $client->getProduct(9760);

    if (!$response) {
        echo "❌ Failed to fetch product\n";
        exit(1);
    }

    $product = $response['product'] ?? null;

    if (!$product) {
        echo "❌ Product not found in response\n";
        exit(1);
    }

    echo "✅ Product fetched successfully\n\n";

    echo "2. Product Details:\n";
    echo "   ID: {$product['id']}\n";
    echo "   Reference: " . ($product['reference'] ?? 'N/A') . "\n";
    echo "   Active: " . ($product['active'] ?? 'N/A') . "\n";
    echo "   Available for order: " . ($product['available_for_order'] ?? 'N/A') . "\n";
    echo "   Visibility: " . ($product['visibility'] ?? 'N/A') . "\n";

    // Name
    if (isset($product['name']['language'])) {
        $names = is_array($product['name']['language']) ? $product['name']['language'] : [$product['name']['language']];
        foreach ($names as $name) {
            echo "   Name [id={$name['@attributes']['id']}]: {$name['value']}\n";
        }
    }

    echo "\n3. Associations:\n";

    // Categories
    if (isset($product['associations']['categories']['category'])) {
        $categories = $product['associations']['categories']['category'];
        if (!isset($categories[0])) {
            $categories = [$categories];
        }
        echo "   Categories: ";
        foreach ($categories as $cat) {
            echo $cat['id'] . " ";
        }
        echo "\n";
    } else {
        echo "   Categories: NONE (❌ This may be the problem!)\n";
    }

    // Shops (multi-store)
    if (isset($product['associations']['shop'])) {
        echo "   Shops: ";
        $shops = $product['associations']['shop'];
        if (!isset($shops[0])) {
            $shops = [$shops];
        }
        foreach ($shops as $s) {
            echo $s['id'] . " ";
        }
        echo "\n";
    }

    echo "\n4. Stock Info:\n";
    if (isset($product['associations']['stock_availables']['stock_available'])) {
        $stocks = $product['associations']['stock_availables']['stock_available'];
        if (!isset($stocks[0])) {
            $stocks = [$stocks];
        }
        foreach ($stocks as $stock) {
            echo "   Stock ID: {$stock['id']}, Quantity: {$stock['quantity']}\n";
        }
    }

    echo "\n5. Analysis:\n";

    // Check if product is active
    if (isset($product['active']) && $product['active'] == '0') {
        echo "   ⚠️  Product is INACTIVE (active=0)\n";
        echo "   → This may hide it from admin list\n";
    } else {
        echo "   ✅ Product is active\n";
    }

    // Check categories
    if (!isset($product['associations']['categories']['category']) || empty($product['associations']['categories']['category'])) {
        echo "   ❌ Product has NO CATEGORIES\n";
        echo "   → This may hide it from admin product list!\n";
        echo "   → PrestaShop admin filters products by category\n";
    }

    // Check visibility
    if (isset($product['visibility']) && $product['visibility'] !== 'both') {
        echo "   ⚠️  Product visibility is: {$product['visibility']}\n";
        echo "   → Should be 'both' for full visibility\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== END CHECK ===\n";
