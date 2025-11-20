<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRESTASHOP CATEGORIES - DIRECT DB CHECK ===\n\n";

// Get PPM data
$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

echo "PPM Product: 11034\n";
echo "PPM Shop: B2B Test DEV (Shop ID: 1)\n";
echo "PrestaShop Product ID: {$psd->prestashop_product_id}\n\n";

// Try both databases
$databases = [
    'dev.mpptrade.pl' => [
        'name' => 'host379076_devmpp',
        'user' => 'host379076_devmpp',
        'pass' => 'CxtsfyV4nWyGct5LTZrb',
    ],
    'b2b (from dane_hostingu.md)' => [
        'name' => 'host379076_b2b',
        'user' => 'host379076_b2b',
        'pass' => 'YvpQMNnCSj69Wu2qjJNc',
    ],
];

$workingPdo = null;
$workingDbName = null;

foreach ($databases as $label => $dbConfig) {
    echo "Trying {$label} (database: {$dbConfig['name']})...\n";

    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname={$dbConfig['name']}",
            $dbConfig['user'],
            $dbConfig['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if ps_product table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'ps_product'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ Connected! ps_product table found\n";

            // Check if product 1831 exists
            $stmt = $pdo->prepare("SELECT id_product, reference FROM ps_product WHERE id_product = ?");
            $stmt->execute([$psd->prestashop_product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                echo "  ✅ Product {$psd->prestashop_product_id} found! (reference: {$product['reference']})\n";
                $workingPdo = $pdo;
                $workingDbName = $label;
                break;
            } else {
                echo "  ❌ Product {$psd->prestashop_product_id} NOT found\n";
            }
        } else {
            echo "  ❌ No ps_product table\n";
        }

    } catch (PDOException $e) {
        echo "  ❌ Connection failed: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

if (!$workingPdo) {
    echo "\n❌ NO WORKING DATABASE FOUND\n";
    exit(1);
}

echo "\n=== USING DATABASE: {$workingDbName} ===\n\n";

// Get product details
$stmt = $workingPdo->prepare("SELECT id_product, reference, id_category_default FROM ps_product WHERE id_product = ?");
$stmt->execute([$psd->prestashop_product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

echo "PRODUCT DETAILS:\n";
echo "  id_product: {$product['id_product']}\n";
echo "  reference: {$product['reference']}\n";
echo "  id_category_default: {$product['id_category_default']}\n\n";

// Get categories
echo "CATEGORIES IN PRESTASHOP (ps_category_product):\n";
$stmt = $workingPdo->prepare("
    SELECT cp.id_category, c.id_parent, c.level_depth,
           COALESCE(cl.name, 'NO NAME') as name, c.active
    FROM ps_category_product cp
    LEFT JOIN ps_category c ON cp.id_category = c.id_category
    LEFT JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
    WHERE cp.id_product = ?
    ORDER BY cp.id_category
");
$stmt->execute([$product['id_product']]);
$psCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($psCategories as $cat) {
    echo "  PrestaShop ID: {$cat['id_category']}\n";
    echo "    Name: {$cat['name']}\n";
    echo "    Parent: {$cat['id_parent']}\n";
    echo "    Depth: {$cat['level_depth']}\n";
    echo "    Active: " . ($cat['active'] ? 'YES' : 'NO') . "\n\n";
}

echo "Total: " . count($psCategories) . " categories\n\n";

// Get PPM data
$categoryMappings = json_decode($psd->category_mappings, true);
$ppmSelected = $categoryMappings['ui']['selected'] ?? [];

echo "=== COMPARISON ===\n\n";
echo "PPM Selected Categories: " . json_encode($ppmSelected) . " (" . count($ppmSelected) . " total)\n";
echo "PrestaShop Categories: " . json_encode(array_column($psCategories, 'id_category')) . " (" . count($psCategories) . " total)\n\n";

// Check category mappings
echo "=== CATEGORY MAPPING (PrestaShop -> PPM) ===\n\n";

$mappings = DB::table('category_mappings')
    ->where('shop_id', 1)
    ->get();

echo "Total mappings for Shop 1: " . $mappings->count() . "\n\n";

foreach ($psCategories as $cat) {
    $psCatId = $cat['id_category'];

    $mapping = $mappings->firstWhere('prestashop_category_id', $psCatId);

    if ($mapping) {
        $ppmCat = DB::table('categories')->where('id', $mapping->ppm_category_id)->first();
        echo "PrestaShop {$psCatId} ({$cat['name']})  ->  PPM {$mapping->ppm_category_id}";
        if ($ppmCat) {
            echo " ({$ppmCat->name}) ✅\n";
        } else {
            echo " ❌ GHOST CATEGORY (doesn't exist in PPM!)\n";
        }
    } else {
        echo "PrestaShop {$psCatId} ({$cat['name']})  ->  ❌ NO MAPPING FOUND\n";
    }
}

echo "\n=== REVERSE CHECK: PPM Categories ===\n\n";

foreach ($ppmSelected as $ppmCatId) {
    $ppmCat = DB::table('categories')->where('id', $ppmCatId)->first();
    echo "PPM {$ppmCatId}";

    if ($ppmCat) {
        echo " ({$ppmCat->name})";
    } else {
        echo " ❌ GHOST CATEGORY IN PPM!";
    }

    // Find mapping
    $mapping = $mappings->firstWhere('ppm_category_id', $ppmCatId);

    if ($mapping) {
        echo "  ->  PrestaShop {$mapping->prestashop_category_id}";

        // Check if it's in actual PrestaShop product categories
        $found = false;
        foreach ($psCategories as $cat) {
            if ($cat['id_category'] == $mapping->prestashop_category_id) {
                echo " ✅ (in PrestaShop product)\n";
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo " ❌ (NOT in PrestaShop product categories!)\n";
        }

    } else {
        echo "  ->  ❌ NO MAPPING\n";
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
