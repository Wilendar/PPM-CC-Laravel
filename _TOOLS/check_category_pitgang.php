<?php

/**
 * CHECK: Category PITGANG (ID 12) configuration
 */

echo "=== CATEGORY PITGANG (ID 12) DIAGNOSTICS ===\n\n";

$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check ps_category
    echo "Step 1: Checking ps_category table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_category,
            id_parent,
            level_depth,
            active,
            nleft,
            nright
        FROM ps_category
        WHERE id_category = 12
    ");
    $stmt->execute();
    $category = $stmt->fetch();

    if (!$category) {
        echo "❌ Category 12 NOT FOUND!\n";
        exit(1);
    }

    echo "✓ Category found:\n";
    echo "  - ID: {$category['id_category']}\n";
    echo "  - Parent: {$category['id_parent']}\n";
    echo "  - Level: {$category['level_depth']}\n";
    echo "  - Active: " . ($category['active'] ? 'YES ✓' : 'NO ❌') . "\n";
    echo "  - NLeft: {$category['nleft']}\n";
    echo "  - NRight: {$category['nright']}\n";
    echo "\n";

    // Check ps_category_lang
    echo "Step 2: Checking ps_category_lang table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_lang,
            id_shop,
            name,
            link_rewrite
        FROM ps_category_lang
        WHERE id_category = 12
    ");
    $stmt->execute();
    $langs = $stmt->fetchAll();

    echo "✓ Language data (" . count($langs) . " records):\n";
    foreach ($langs as $lang) {
        echo "  - Lang: {$lang['id_lang']}, Shop: {$lang['id_shop']}\n";
        echo "    Name: {$lang['name']}\n";
        echo "    Link: {$lang['link_rewrite']}\n";
    }
    echo "\n";

    // Check ps_category_shop
    echo "Step 3: Checking ps_category_shop table...\n";
    $stmt = $pdo->prepare("
        SELECT
            id_shop,
            position
        FROM ps_category_shop
        WHERE id_category = 12
    ");
    $stmt->execute();
    $shops = $stmt->fetchAll();

    if (empty($shops)) {
        echo "❌ Category NOT assigned to any shop!\n";
        echo "This is why products in this category are invisible!\n\n";
        echo "FIX: INSERT INTO ps_category_shop (id_category, id_shop, position) VALUES (12, 1, 0);\n";
    } else {
        echo "✓ Category assigned to " . count($shops) . " shop(s):\n";
        foreach ($shops as $shop) {
            echo "  - Shop ID: {$shop['id_shop']}, Position: {$shop['position']}\n";
        }
    }
    echo "\n";

    // Check products in this category
    echo "Step 4: Checking products in this category...\n";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM ps_category_product
        WHERE id_category = 12
    ");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    echo "✓ Category has {$count} product(s)\n\n";

    echo "=== SUMMARY ===\n";
    if ($category['active'] && !empty($shops)) {
        echo "✅ Category is properly configured!\n";
        echo "If product still invisible, check:\n";
        echo "1. Clear PrestaShop cache again\n";
        echo "2. Check if you're viewing correct shop in multistore\n";
        echo "3. Check admin user permissions\n";
    } else {
        echo "❌ Category has configuration issues!\n";
        if (!$category['active']) {
            echo "- Category is INACTIVE\n";
        }
        if (empty($shops)) {
            echo "- Category is NOT assigned to any shop\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
