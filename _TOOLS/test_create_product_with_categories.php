/**
 * Test CREATE Product with Categories
 *
 * Sprawdza czy kategorie działają przy tworzeniu NOWEGO produktu
 */

echo "=== TEST CREATE PRODUCT WITH CATEGORIES ===\n\n";

// Get shop
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n\n";

// Create NEW test product
echo "1. Creating NEW test product in PPM...\n";

$newProduct = new \App\Models\Product();
$newProduct->sku = 'TEST-CREATE-' . time();
$newProduct->name = 'Test CREATE with Categories';
$newProduct->short_description = 'Test czy kategorie działają przy CREATE';
$newProduct->long_description = 'Ten produkt testuje czy kategorie są zapisywane przy tworzeniu nowego produktu.';
$newProduct->product_type_id = 1; // Simple product
$newProduct->is_active = true;
$newProduct->weight = 1.0;
$newProduct->ean = '1234567890124';
$newProduct->tax_rate = 23.0; // VAT 23%
$newProduct->save();

echo "✅ Product created: ID {$newProduct->id}, SKU: {$newProduct->sku}\n\n";

// Attach category
echo "2. Attaching category to product...\n";
$category = \App\Models\Category::find(41); // PITGANG
if ($category) {
    $newProduct->categories()->attach($category->id);
    echo "✅ Category attached: {$category->name} (ID: {$category->id})\n\n";
} else {
    echo "⚠️  Category 41 not found, using default\n\n";
}

// Sync to PrestaShop (synchronously)
echo "3. Syncing NEW product to PrestaShop (CREATE operation)...\n";

try {
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    $transformer = app(\App\Services\PrestaShop\ProductTransformer::class);
    $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
    $priceMapper = app(\App\Services\PrestaShop\PriceGroupMapper::class);
    $warehouseMapper = app(\App\Services\PrestaShop\WarehouseMapper::class);

    $syncStrategy = new \App\Services\PrestaShop\Sync\ProductSyncStrategy(
        $transformer,
        $categoryMapper,
        $priceMapper,
        $warehouseMapper
    );

    $result = $syncStrategy->syncToPrestaShop($newProduct, $client, $shop);

    echo "✅ SYNC SUCCESSFUL!\n";
    echo "Operation: {$result['operation']}\n";
    echo "PrestaShop Product ID: {$result['external_id']}\n\n";

    $prestashopProductId = $result['external_id'];

    // Verify in PrestaShop
    echo "4. Verifying product in PrestaShop...\n";

    $response = $client->getProduct($prestashopProductId);
    $product = $response['product'];

    echo "Product ID: {$product['id']}\n";
    echo "Reference: {$product['reference']}\n";
    echo "Active: {$product['active']}\n\n";

    if (isset($product['associations']['categories']['category'])) {
        $categories = $product['associations']['categories']['category'];
        if (!isset($categories[0])) {
            $categories = [$categories];
        }

        echo "✅ ✅ ✅ SUCCESS! Product HAS CATEGORIES in PrestaShop!\n";
        echo "Categories count: " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "  - Category ID: {$cat['id']}\n";
        }

        echo "\n🎉 KATEGORIE DZIAŁAJĄ przy CREATE!\n";
        echo "→ Problem dotyczy TYLKO UPDATE operations\n";

    } else {
        echo "❌ Product has NO CATEGORIES\n";
        echo "→ Problem dotyczy zarówno CREATE jak i UPDATE\n";
    }

} catch (\Exception $e) {
    echo "\n❌ SYNC FAILED:\n";
    echo "Error: {$e->getMessage()}\n";
}

echo "\n=== END TEST ===\n";
