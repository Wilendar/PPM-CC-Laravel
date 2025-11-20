<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRESTASHOP DATABASE - PRODUCT CATEGORIES ===\n\n";

// Shop 1: B2B Test DEV (dev.mpptrade.pl)
// Shop 5: Test KAYO (test.kayomoto.pl)

// Check which PrestaShop database to use
$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "ERROR: ProductShopData not found\n";
    exit(1);
}

$shop = DB::table('prestashop_shops')->where('id', 1)->first();

echo "PPM Product: 11034\n";
echo "PPM Shop: {$shop->name}\n";
echo "PrestaShop Product ID: {$psd->prestashop_product_id}\n";
echo "Shop URL: {$shop->url}\n\n";

// Shop 1 uses dev.mpptrade.pl - need to check which database
// Shop 5 uses test.kayomoto.pl - host226673_test_kayoshop

echo "CHECKING DATABASE ACCESS OPTIONS:\n\n";

// Option 1: Try KAYO database (Shop 5)
echo "OPTION 1: KAYO Database (test.kayomoto.pl)\n";
echo "Host: host226673.hostido.net.pl\n";
echo "Database: host226673_test_kayoshop\n\n";

try {
    $kayoDb = new PDO(
        'mysql:host=host226673.hostido.net.pl;dbname=host226673_test_kayoshop',
        'host226673_test_kayoshop',
        'hnMnzhGaCEhcKArm7U4v'
    );
    $kayoDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connection successful\n";

    // Check if product 1831 exists
    $stmt = $kayoDb->prepare("SELECT id_product, reference, id_category_default FROM ps_product WHERE id_product = ? OR reference = ?");
    $stmt->execute([1831, 'Q-KAYO-EA70']);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo "✅ Product found in KAYO database!\n";
        echo "  id_product: {$product['id_product']}\n";
        echo "  reference: {$product['reference']}\n";
        echo "  id_category_default: {$product['id_category_default']}\n\n";

        echo "CATEGORIES FOR PRODUCT {$product['id_product']}:\n";
        $stmt = $kayoDb->prepare("
            SELECT cp.id_category, c.id_parent, c.level_depth,
                   COALESCE(cl.name, 'NO NAME') as name
            FROM ps_category_product cp
            LEFT JOIN ps_category c ON cp.id_category = c.id_category
            LEFT JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
            WHERE cp.id_product = ?
            ORDER BY cp.id_category
        ");
        $stmt->execute([$product['id_product']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $cat) {
            echo "  PrestaShop Category ID: {$cat['id_category']}\n";
            echo "    Name: {$cat['name']}\n";
            echo "    Parent: {$cat['id_parent']}\n";
            echo "    Depth: {$cat['level_depth']}\n\n";
        }

        echo "Total categories: " . count($categories) . "\n\n";

        // Now check PPM mapping
        echo "PPM CATEGORY MAPPING (Shop 1 -> PrestaShop categories):\n";
        $mappings = DB::table('category_mappings')
            ->where('shop_id', 1)
            ->get();

        echo "Total mappings for Shop 1: " . $mappings->count() . "\n\n";

        foreach ($categories as $cat) {
            $psCatId = $cat['id_category'];

            // Find PPM category that maps to this PrestaShop category
            $mapping = $mappings->firstWhere('prestashop_category_id', $psCatId);

            if ($mapping) {
                $ppmCat = DB::table('categories')->where('id', $mapping->ppm_category_id)->first();
                echo "  PrestaShop {$psCatId} ({$cat['name']}) -> PPM {$mapping->ppm_category_id}";
                if ($ppmCat) {
                    echo " ({$ppmCat->name})\n";
                } else {
                    echo " (GHOST - category doesn't exist in PPM!)\n";
                }
            } else {
                echo "  PrestaShop {$psCatId} ({$cat['name']}) -> NO MAPPING FOUND\n";
            }
        }

    } else {
        echo "❌ Product NOT found in KAYO database\n";
        echo "  Tried: id_product=1831 OR reference='Q-KAYO-EA70'\n";
    }

} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
