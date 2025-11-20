<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DETAILED PRODUCT 11018 DIAGNOSIS ===\n\n";

// Check if product exists
$product = DB::table('products')->where('id', 11018)->first();

if (!$product) {
    echo "❌ Product 11018 does NOT exist in database\n";
    exit(1);
}

echo "✅ Product 11018 EXISTS\n";
echo "SKU: {$product->sku}\n";
echo "Name: {$product->name}\n";
echo "---\n\n";

// Check product_shop_data
echo "=== PRODUCT_SHOP_DATA RECORDS ===\n";
$shopDataRecords = DB::table('product_shop_data')
    ->where('product_id', 11018)
    ->get();

if ($shopDataRecords->isEmpty()) {
    echo "❌ NO product_shop_data records for product 11018\n\n";
} else {
    echo "✅ Found " . count($shopDataRecords) . " product_shop_data record(s):\n\n";
    foreach ($shopDataRecords as $psd) {
        $shop = DB::table('prestashop_shops')->where('id', $psd->shop_id)->first();
        echo "Record ID: {$psd->id}\n";
        echo "Shop ID: {$psd->shop_id} ({$shop->name})\n";
        echo "PrestaShop Product ID: {$psd->prestashop_product_id}\n";
        echo "Sync Status: {$psd->sync_status}\n";
        echo "Last Pulled: {$psd->last_pulled_at}\n";
        echo "---\n";
    }
}

// Check all products that HAVE shop data
echo "\n=== PRODUCTS WITH SHOP DATA (sample 10) ===\n";
$productsWithShopData = DB::select("
    SELECT DISTINCT p.id, p.sku, p.name, COUNT(psd.id) as shop_count
    FROM products p
    INNER JOIN product_shop_data psd ON psd.product_id = p.id
    GROUP BY p.id, p.sku, p.name
    ORDER BY p.id DESC
    LIMIT 10
");

foreach ($productsWithShopData as $p) {
    echo "Product {$p->id}: {$p->sku} - {$p->name} ({$p->shop_count} shops)\n";
}

echo "\n=== END DIAGNOSIS ===\n";
