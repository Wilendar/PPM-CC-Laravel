<?php

/**
 * DIAGNOSE: Product 9762 in PrestaShop
 *
 * Purpose: Sprawdzenie dlaczego produkt jest w bazie ale niewidoczny w admin panelu
 *
 * Checks:
 * 1. ps_product - główne dane (active, visibility)
 * 2. ps_product_shop - przypisanie do sklepu
 * 3. ps_product_lang - dane językowe
 * 4. ps_category_product - kategorie
 * 5. ps_stock_available - stan magazynowy
 */

echo "=== PRESTASHOP PRODUCT 9762 DIAGNOSTICS ===\n\n";

// Database credentials (B2B Test DEV)
$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

$productId = 9762;

try {
    echo "Step 1: Connecting to PrestaShop database...\n";
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connected successfully!\n\n";

    // Check ps_product
    echo "Step 2: Checking ps_product table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_product,
            reference,
            id_category_default,
            active,
            visibility,
            available_for_order,
            show_price,
            indexed,
            date_add,
            date_upd
        FROM ps_product
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo "❌ Product ID {$productId} NOT FOUND in ps_product!\n";
        exit(1);
    }

    echo "✓ Product found in ps_product:\n";
    echo "  - Reference: {$product['reference']}\n";
    echo "  - Default Category: {$product['id_category_default']}\n";
    echo "  - Active: {$product['active']} " . ($product['active'] == 1 ? '✓' : '❌ INACTIVE') . "\n";
    echo "  - Visibility: {$product['visibility']}\n";
    echo "  - Available for Order: {$product['available_for_order']}\n";
    echo "  - Show Price: {$product['show_price']}\n";
    echo "  - Indexed: {$product['indexed']}\n";
    echo "  - Date Added: {$product['date_add']}\n";
    echo "  - Date Updated: {$product['date_upd']}\n";
    echo "\n";

    // Check ps_product_shop
    echo "Step 3: Checking ps_product_shop table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_shop,
            active,
            visibility,
            available_for_order,
            show_price,
            indexed
        FROM ps_product_shop
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $productShops = $stmt->fetchAll();

    if (empty($productShops)) {
        echo "❌ Product NOT assigned to any shop in ps_product_shop!\n";
        echo "This is why it's invisible in admin panel!\n\n";
    } else {
        echo "✓ Product assigned to " . count($productShops) . " shop(s):\n";
        foreach ($productShops as $ps) {
            echo "  - Shop ID: {$ps['id_shop']}\n";
            echo "    Active: {$ps['active']} " . ($ps['active'] == 1 ? '✓' : '❌') . "\n";
            echo "    Visibility: {$ps['visibility']}\n";
            echo "    Available for Order: {$ps['available_for_order']}\n";
            echo "    Indexed: {$ps['indexed']}\n";
        }
        echo "\n";
    }

    // Check ps_product_lang
    echo "Step 4: Checking ps_product_lang table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_lang,
            id_shop,
            name,
            description,
            link_rewrite
        FROM ps_product_lang
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $productLangs = $stmt->fetchAll();

    if (empty($productLangs)) {
        echo "❌ Product has NO language data in ps_product_lang!\n";
        echo "This will cause visibility issues!\n\n";
    } else {
        echo "✓ Product has language data for " . count($productLangs) . " language(s):\n";
        foreach ($productLangs as $pl) {
            echo "  - Lang ID: {$pl['id_lang']}, Shop ID: {$pl['id_shop']}\n";
            echo "    Name: " . ($pl['name'] ?: '❌ EMPTY') . "\n";
            echo "    Link Rewrite: " . ($pl['link_rewrite'] ?: '❌ EMPTY') . "\n";
            echo "    Has Description: " . (!empty($pl['description']) ? 'YES' : 'NO') . "\n";
        }
        echo "\n";
    }

    // Check ps_category_product
    echo "Step 5: Checking ps_category_product table...\n";
    $stmt = $pdo->prepare("
        SELECT
            cp.id_category,
            cp.position,
            cl.name as category_name,
            c.active as category_active
        FROM ps_category_product cp
        LEFT JOIN ps_category_lang cl ON cp.id_category = cl.id_category AND cl.id_lang = 1
        LEFT JOIN ps_category c ON cp.id_category = c.id_category
        WHERE cp.id_product = ?
        ORDER BY cp.position
    ");
    $stmt->execute([$productId]);
    $categories = $stmt->fetchAll();

    if (empty($categories)) {
        echo "❌ Product NOT assigned to any category!\n";
        echo "PrestaShop requires products to be in at least one category!\n\n";
    } else {
        echo "✓ Product assigned to " . count($categories) . " category/categories:\n";
        foreach ($categories as $cat) {
            $activeStatus = ($cat['category_active'] == 1) ? '✓' : '❌ INACTIVE';
            echo "  - [{$cat['id_category']}] {$cat['category_name']} (position: {$cat['position']}, active: {$activeStatus})\n";
        }
        echo "\n";
    }

    // Check ps_stock_available
    echo "Step 6: Checking ps_stock_available table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_shop,
            id_shop_group,
            quantity,
            depends_on_stock,
            out_of_stock
        FROM ps_stock_available
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $stocks = $stmt->fetchAll();

    if (empty($stocks)) {
        echo "⚠️ Product has NO stock records!\n\n";
    } else {
        echo "✓ Product has stock records for " . count($stocks) . " shop(s):\n";
        foreach ($stocks as $stock) {
            echo "  - Shop ID: {$stock['id_shop']}, Shop Group: {$stock['id_shop_group']}\n";
            echo "    Quantity: {$stock['quantity']}\n";
            echo "    Depends on Stock: {$stock['depends_on_stock']}\n";
            echo "    Out of Stock: {$stock['out_of_stock']}\n";
        }
        echo "\n";
    }

    echo "=== DIAGNOSTICS COMPLETE ===\n\n";

    // Summary and recommendations
    echo "SUMMARY:\n";
    $issues = [];

    if ($product['active'] != 1) {
        $issues[] = "❌ Product is INACTIVE (active = 0)";
    }

    if (empty($productShops)) {
        $issues[] = "❌ Product NOT in ps_product_shop (not assigned to any shop)";
    }

    if (empty($productLangs)) {
        $issues[] = "❌ Product has NO language data (ps_product_lang empty)";
    } else {
        $missingNames = array_filter($productLangs, function($pl) {
            return empty($pl['name']);
        });
        if (!empty($missingNames)) {
            $issues[] = "⚠️ Product has empty names in some languages";
        }
    }

    if (empty($categories)) {
        $issues[] = "❌ Product NOT assigned to any category";
    }

    if (empty($issues)) {
        echo "✅ No critical issues found!\n";
        echo "Product should be visible in admin panel.\n";
        echo "Try clearing PrestaShop cache:\n";
        echo "  - Clear Cache in admin panel (Advanced Parameters → Performance)\n";
        echo "  - Or delete: var/cache/* manually\n";
    } else {
        echo "Found " . count($issues) . " issue(s):\n\n";
        foreach ($issues as $issue) {
            echo $issue . "\n";
        }
        echo "\n";

        echo "RECOMMENDED FIXES:\n";
        if ($product['active'] != 1) {
            echo "1. Activate product: UPDATE ps_product SET active = 1 WHERE id_product = {$productId};\n";
        }
        if (empty($productShops)) {
            echo "2. Add to shop: INSERT INTO ps_product_shop (id_product, id_shop, active, visibility) VALUES ({$productId}, 1, 1, 'both');\n";
        }
        if (empty($categories)) {
            echo "3. Assign to category (e.g. Home/ID 2): INSERT INTO ps_category_product (id_product, id_category, position) VALUES ({$productId}, 2, 0);\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ DATABASE ERROR:\n";
    echo "Message: {$e->getMessage()}\n";
    echo "Code: {$e->getCode()}\n\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "Message: {$e->getMessage()}\n\n";
    exit(1);
}
