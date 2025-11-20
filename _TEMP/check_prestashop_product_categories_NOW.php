<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK PRESTASHOP DATABASE - PRODUCT 1831 (Q-KAYO-EA70) ===\n\n";

// Connect to dev.mpptrade.pl PrestaShop database
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=host379076_devmpp",
        "host379076_devmpp",
        "CxtsfyV4nWyGct5LTZrb"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to PrestaShop database\n\n";

    // Find product ID by reference (SKU)
    $stmt = $pdo->prepare("SELECT id_product, reference FROM ps_product WHERE reference = 'Q-KAYO-EA70'");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("❌ Product Q-KAYO-EA70 NOT FOUND in PrestaShop!\n");
    }

    $psProductId = $product['id_product'];
    echo "Product ID: {$psProductId}\n";
    echo "SKU: {$product['reference']}\n\n";

    // Get product categories
    $stmt = $pdo->prepare("
        SELECT
            cp.id_category,
            c.id_parent,
            c.level_depth,
            c.active,
            COALESCE(cl.name, 'NO NAME') as name,
            cp.position
        FROM ps_category_product cp
        LEFT JOIN ps_category c ON cp.id_category = c.id_category
        LEFT JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
        WHERE cp.id_product = ?
        ORDER BY cp.id_category
    ");
    $stmt->execute([$psProductId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "CATEGORIES IN PRESTASHOP (CURRENT):\n";
    if (count($categories) > 0) {
        foreach ($categories as $cat) {
            $active = $cat['active'] ? '✅' : '❌';
            echo "  {$active} PrestaShop ID: {$cat['id_category']} - {$cat['name']} (level {$cat['level_depth']}, parent {$cat['id_parent']})\n";
        }
    } else {
        echo "  ❌ NO CATEGORIES ASSIGNED\n";
    }

    echo "\n";

    // Get default category
    $stmt = $pdo->prepare("SELECT id_category_default FROM ps_product WHERE id_product = ?");
    $stmt->execute([$psProductId]);
    $defaultCat = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "DEFAULT CATEGORY: {$defaultCat['id_category_default']}\n";

    // Get default category name
    $stmt = $pdo->prepare("SELECT name FROM ps_category_lang WHERE id_category = ? AND id_lang = 1");
    $stmt->execute([$defaultCat['id_category_default']]);
    $defaultCatName = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "DEFAULT CATEGORY NAME: {$defaultCatName['name']}\n\n";

    // Extract IDs only
    $catIds = array_column($categories, 'id_category');
    echo "CATEGORY IDs: " . json_encode($catIds) . "\n";

} catch (PDOException $e) {
    die("❌ DATABASE ERROR: " . $e->getMessage() . "\n");
}

echo "\n=== COMPLETE ===\n";
