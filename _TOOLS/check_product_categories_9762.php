<?php

/**
 * CHECK: All categories for product 9762
 */

echo "=== PRODUCT 9762 CATEGORIES ===\n\n";

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

    $stmt = $pdo->prepare("
        SELECT
            cp.id_category,
            cp.position,
            cl.name,
            c.active,
            c.level_depth
        FROM ps_category_product cp
        JOIN ps_category_lang cl ON cp.id_category = cl.id_category AND cl.id_lang = 1
        JOIN ps_category c ON cp.id_category = c.id_category
        WHERE cp.id_product = 9762
        ORDER BY cp.position
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    echo "Product 9762 is assigned to " . count($categories) . " categories:\n\n";
    foreach ($categories as $cat) {
        $marker = ($cat['id_category'] == 2352) ? ' ← TEST PPM CATEGORY' : '';
        echo "  [{$cat['id_category']}] {$cat['name']}\n";
        echo "    Position: {$cat['position']}\n";
        echo "    Active: " . ($cat['active'] ? 'YES' : 'NO') . "\n";
        echo "    Level: {$cat['level_depth']}{$marker}\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
