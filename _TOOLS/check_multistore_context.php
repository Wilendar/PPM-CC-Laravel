<?php

/**
 * CHECK: Multistore configuration for product 9762
 */

echo "=== MULTISTORE CONTEXT CHECK ===\n\n";

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

    // Check ps_shop table
    echo "Step 1: Checking all shops in PrestaShop...\n";
    $stmt = $pdo->query("
        SELECT
            id_shop,
            id_shop_group,
            name,
            active
        FROM ps_shop
    ");
    $shops = $stmt->fetchAll();

    echo "✓ Found " . count($shops) . " shop(s):\n";
    foreach ($shops as $shop) {
        $activeStatus = $shop['active'] ? 'ACTIVE' : 'INACTIVE';
        echo "  - [ID: {$shop['id_shop']}] {$shop['name']} (Group: {$shop['id_shop_group']}, {$activeStatus})\n";
    }
    echo "\n";

    // Check ps_shop_group
    echo "Step 2: Checking shop groups...\n";
    $stmt = $pdo->query("
        SELECT
            id_shop_group,
            name,
            active
        FROM ps_shop_group
    ");
    $groups = $stmt->fetchAll();

    echo "✓ Found " . count($groups) . " shop group(s):\n";
    foreach ($groups as $group) {
        $activeStatus = $group['active'] ? 'ACTIVE' : 'INACTIVE';
        echo "  - [ID: {$group['id_shop_group']}] {$group['name']} ({$activeStatus})\n";
    }
    echo "\n";

    // Check product 9762 shop assignments
    echo "Step 3: Checking product 9762 shop assignments...\n";
    $stmt = $pdo->prepare("
        SELECT
            ps.id_shop,
            ps.active,
            s.name as shop_name,
            s.id_shop_group
        FROM ps_product_shop ps
        JOIN ps_shop s ON ps.id_shop = s.id_shop
        WHERE ps.id_product = 9762
    ");
    $stmt->execute();
    $productShops = $stmt->fetchAll();

    echo "✓ Product 9762 assigned to " . count($productShops) . " shop(s):\n";
    foreach ($productShops as $ps) {
        $activeStatus = $ps['active'] ? 'ACTIVE ✓' : 'INACTIVE ❌';
        echo "  - [Shop ID: {$ps['id_shop']}] {$ps['shop_name']} (Group: {$ps['id_shop_group']}, {$activeStatus})\n";
    }
    echo "\n";

    // Check if product is in correct category for each shop
    echo "Step 4: Checking category-shop relationships...\n";
    $stmt = $pdo->prepare("
        SELECT
            c.id_category,
            c.active as cat_active,
            cl.name,
            cs.id_shop
        FROM ps_category_product cp
        JOIN ps_category c ON cp.id_category = c.id_category
        JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
        LEFT JOIN ps_category_shop cs ON c.id_category = cs.id_category
        WHERE cp.id_product = 9762
    ");
    $stmt->execute();
    $catShops = $stmt->fetchAll();

    if (empty($catShops)) {
        echo "❌ No category-shop relationships found!\n\n";
    } else {
        echo "✓ Category assignments with shop context:\n";
        foreach ($catShops as $cs) {
            $shopInfo = $cs['id_shop'] ?: 'ALL SHOPS';
            $activeStatus = $cs['cat_active'] ? '✓' : '❌';
            echo "  - Category [{$cs['id_category']}] {$cs['name']} → Shop: {$shopInfo} (Active: {$activeStatus})\n";
        }
    }
    echo "\n";

    // Check if there are any shop restrictions
    echo "Step 5: Checking for shop restrictions...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM ps_shop");
    $shopCount = $stmt->fetchColumn();

    if ($shopCount > 1) {
        echo "⚠️ MULTISTORE DETECTED ({$shopCount} shops)!\n";
        echo "Make sure you're viewing the CORRECT shop in admin panel!\n";
        echo "Check: Top right corner in admin → Shop dropdown\n\n";
    } else {
        echo "✓ Single shop configuration\n\n";
    }

    // Summary
    echo "=== TROUBLESHOOTING STEPS ===\n\n";
    echo "In PrestaShop Admin Panel:\n";
    echo "1. Check shop context (top right corner)\n";
    echo "   - If multistore: Select specific shop or 'All shops'\n\n";
    echo "2. Go to: Catalog → Products\n";
    echo "   - Search by ID: 9762\n";
    echo "   - Or by SKU: TEST-CREATE-1762351961\n";
    echo "   - Or by Name: Test CREATE with Categories\n\n";
    echo "3. Check if any filters are active:\n";
    echo "   - Category filter\n";
    echo "   - Status filter (Active/Inactive)\n";
    echo "   - Shop filter (in multistore)\n\n";
    echo "4. Try direct URL:\n";
    echo "   - https://dev.mpptrade.pl/admin/index.php?controller=AdminProducts&id_product=9762&updateproduct\n\n";
    echo "5. Check admin user permissions:\n";
    echo "   - Advanced Parameters → Team → Permissions\n";
    echo "   - Make sure your user has 'View' permission for Products\n\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
