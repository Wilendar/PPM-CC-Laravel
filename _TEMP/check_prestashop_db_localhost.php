<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRESTASHOP DB CHECK (LOCALHOST) ===\n\n";

// Get shop data
$shop = DB::table('prestashop_shops')->where('id', 1)->first();
$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

echo "Shop: {$shop->name}\n";
echo "URL: " . ($shop->url ?? 'N/A') . "\n";
echo "PrestaShop Product ID: {$psd->prestashop_product_id}\n\n";

// Parse database name from URL
// dev.mpptrade.pl might use database: host379076_dev_mpptrade or similar

echo "OPTION 1: Try common database names for dev.mpptrade.pl\n\n";

$possibleDatabases = [
    'host379076_dev',
    'host379076_dev_mpptrade',
    'host379076_devmpptrade',
    'host379076_mpptrade',
];

$connected = false;
$workingDb = null;

foreach ($possibleDatabases as $dbName) {
    echo "Trying database: {$dbName}\n";

    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname={$dbName}",
            'host379076_ppm', // Same user as PPM
            'qkS4FuXMMDDN4DJhatg6'
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "  ✅ Connected!\n";

        // Check if ps_product table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'ps_product'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ ps_product table found!\n";
            $connected = true;
            $workingDb = $pdo;
            break;
        } else {
            echo "  ❌ No ps_product table\n";
        }

    } catch (PDOException $e) {
        echo "  ❌ Connection failed\n";
    }
}

if (!$connected) {
    echo "\n❌ No PrestaShop database found\n";
    echo "\nFallback: List all databases accessible by user:\n";

    try {
        $pdo = new PDO(
            "mysql:host=localhost",
            'host379076_ppm',
            'qkS4FuXMMDDN4DJhatg6'
        );
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($databases as $db) {
            echo "  - {$db}\n";
        }

    } catch (PDOException $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }

    exit(1);
}

echo "\n\n=== CHECKING PRODUCT CATEGORIES ===\n\n";

// Check if product exists
$stmt = $workingDb->prepare("SELECT id_product, reference, id_category_default FROM ps_product WHERE id_product = ? OR reference = ?");
$stmt->execute([1831, 'Q-KAYO-EA70']);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "❌ Product NOT found (id=1831 OR reference='Q-KAYO-EA70')\n";
    exit(1);
}

echo "✅ Product found!\n";
echo "  id_product: {$product['id_product']}\n";
echo "  reference: {$product['reference']}\n";
echo "  id_category_default: {$product['id_category_default']}\n\n";

echo "CATEGORIES (from ps_category_product):\n";
$stmt = $workingDb->prepare("
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

echo "Total: " . count($categories) . " categories\n\n";

echo "=== PPM CATEGORY MAPPING ===\n\n";

$mappings = DB::table('category_mappings')
    ->where('shop_id', 1)
    ->get();

echo "Total mappings for Shop 1: " . $mappings->count() . "\n\n";

foreach ($categories as $cat) {
    $psCatId = $cat['id_category'];

    $mapping = $mappings->firstWhere('prestashop_category_id', $psCatId);

    if ($mapping) {
        $ppmCat = DB::table('categories')->where('id', $mapping->ppm_category_id)->first();
        echo "PrestaShop {$psCatId} ({$cat['name']}) -> PPM {$mapping->ppm_category_id}";
        if ($ppmCat) {
            echo " ({$ppmCat->name}) ✅\n";
        } else {
            echo " ❌ GHOST CATEGORY (doesn't exist in PPM)\n";
        }
    } else {
        echo "PrestaShop {$psCatId} ({$cat['name']}) -> ❌ NO MAPPING\n";
    }
}

echo "\n=== COMPARISON ===\n\n";

$ppmMappings = $psd->category_mappings ?? json_decode($psd->category_mappings, true);
if (is_string($ppmMappings)) {
    $ppmMappings = json_decode($ppmMappings, true);
}

$ppmSelected = $ppmMappings['ui']['selected'] ?? [];

echo "PPM has: " . json_encode($ppmSelected) . "\n";
echo "PrestaShop has: " . json_encode(array_column($categories, 'id_category')) . "\n\n";

echo "=== DIAGNOSTIC COMPLETE ===\n";
