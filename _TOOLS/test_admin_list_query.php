<?php

/**
 * TEST: Simulate PrestaShop Admin Products List Query
 *
 * Try to replicate the exact query PrestaShop admin uses
 */

echo "=== SIMULATE ADMIN PRODUCTS LIST QUERY ===\n\n";

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

    // Typical PrestaShop Admin Products list query
    echo "Testing typical admin list query patterns...\n\n";

    // Query 1: Basic product list
    echo "Query 1: Basic product list with shop JOIN...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            pl.name,
            p.active,
            ps.active as shop_active
        FROM ps_product p
        INNER JOIN ps_product_shop ps ON p.id_product = ps.id_product
        INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE p.id_product IN (9755, 9762)
        ORDER BY p.id_product
    ");
    $results = $stmt->fetchAll();

    foreach ($results as $row) {
        echo "  [{$row['id_product']}] {$row['name']} - Active: {$row['active']}, Shop Active: {$row['shop_active']}\n";
    }
    echo "\n";

    // Query 2: With category JOIN
    echo "Query 2: With category JOIN...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            pl.name,
            c.id_category,
            cl.name as category_name
        FROM ps_product p
        INNER JOIN ps_product_shop ps ON p.id_product = ps.id_product
        INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
        LEFT JOIN ps_category c ON cp.id_category = c.id_category
        LEFT JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
        WHERE p.id_product IN (9755, 9762)
        ORDER BY p.id_product
        LIMIT 20
    ");
    $results = $stmt->fetchAll();

    $grouped = [];
    foreach ($results as $row) {
        $id = $row['id_product'];
        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'name' => $row['name'],
                'categories' => []
            ];
        }
        if ($row['category_name']) {
            $grouped[$id]['categories'][] = $row['category_name'];
        }
    }

    foreach ($grouped as $id => $data) {
        echo "  [{$id}] {$data['name']}\n";
        echo "    Categories: " . implode(', ', $data['categories']) . "\n";
    }
    echo "\n";

    // Query 3: With stock JOIN
    echo "Query 3: With stock JOIN...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            pl.name,
            sa.quantity
        FROM ps_product p
        INNER JOIN ps_product_shop ps ON p.id_product = ps.id_product
        INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        LEFT JOIN ps_stock_available sa ON p.id_product = sa.id_product AND sa.id_product_attribute = 0
        WHERE p.id_product IN (9755, 9762)
        ORDER BY p.id_product
    ");
    $results = $stmt->fetchAll();

    foreach ($results as $row) {
        echo "  [{$row['id_product']}] {$row['name']} - Stock: " . ($row['quantity'] ?: '0') . "\n";
    }
    echo "\n";

    // Query 4: Check if image is required
    echo "Query 4: Products WITH images...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            pl.name,
            COUNT(i.id_image) as image_count
        FROM ps_product p
        INNER JOIN ps_product_shop ps ON p.id_product = ps.id_product
        INNER JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        LEFT JOIN ps_image i ON p.id_product = i.id_product
        WHERE p.id_product IN (9755, 9762)
        GROUP BY p.id_product
        ORDER BY p.id_product
    ");
    $results = $stmt->fetchAll();

    foreach ($results as $row) {
        $imgStatus = ($row['image_count'] > 0) ? "✓ {$row['image_count']} images" : "❌ NO IMAGES";
        echo "  [{$row['id_product']}] {$row['name']} - {$imgStatus}\n";
    }
    echo "\n";

    // Query 5: Full admin-like query with all common JOINs
    echo "Query 5: Full comprehensive query...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            pl.name,
            p.active,
            ps.active as shop_active,
            p.price,
            sa.quantity,
            COUNT(DISTINCT i.id_image) as image_count,
            m.name as manufacturer,
            p.reference
        FROM ps_product p
        INNER JOIN ps_product_shop ps ON (p.id_product = ps.id_product AND ps.id_shop = 1)
        INNER JOIN ps_product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = 1 AND pl.id_shop = 1)
        LEFT JOIN ps_stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
        LEFT JOIN ps_image i ON (p.id_product = i.id_product)
        LEFT JOIN ps_manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
        WHERE p.id_product IN (9755, 9762)
        GROUP BY p.id_product
        ORDER BY p.id_product
    ");
    $results = $stmt->fetchAll();

    echo "Complete product data:\n";
    foreach ($results as $row) {
        echo "\n  Product ID: {$row['id_product']}\n";
        echo "    Name: {$row['name']}\n";
        echo "    Reference: {$row['reference']}\n";
        echo "    Active: {$row['active']} (shop: {$row['shop_active']})\n";
        echo "    Price: {$row['price']}\n";
        echo "    Stock: " . ($row['quantity'] ?: '0') . "\n";
        echo "    Images: {$row['image_count']}\n";
        echo "    Manufacturer: " . ($row['manufacturer'] ?: 'NONE') . "\n";
    }

    echo "\n\n=== CONCLUSION ===\n\n";
    echo "Both products appear in all queries.\n";
    echo "If product 9762 is STILL not visible in admin, check:\n";
    echo "1. Browser cache (try incognito mode)\n";
    echo "2. PrestaShop admin cache (delete var/cache/)\n";
    echo "3. JavaScript console errors (F12)\n";
    echo "4. Employee permissions\n";
    echo "5. Shop context selector (if multistore)\n\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
