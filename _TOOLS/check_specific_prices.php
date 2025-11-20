<?php

/**
 * CHECK: ps_specific_price for product 9755
 */

echo "=== SPECIFIC PRICES DETAILS ===\n\n";

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

    // Get all specific prices for product 9755
    echo "Step 1: Getting specific prices for product 9755...\n";
    $stmt = $pdo->prepare("
        SELECT *
        FROM ps_specific_price
        WHERE id_product = 9755
    ");
    $stmt->execute();
    $prices = $stmt->fetchAll();

    echo "✓ Found " . count($prices) . " specific prices:\n\n";

    foreach ($prices as $price) {
        echo "Price ID: {$price['id_specific_price']}\n";
        echo "  Product: {$price['id_product']}\n";
        echo "  Shop: {$price['id_shop']}\n";
        echo "  Group: {$price['id_group']}\n";
        echo "  Customer: {$price['id_customer']}\n";
        echo "  Product Attribute: {$price['id_product_attribute']}\n";
        echo "  Price: {$price['price']}\n";
        echo "  From Quantity: {$price['from_quantity']}\n";
        echo "  Reduction: {$price['reduction']}\n";
        echo "  Reduction Type: {$price['reduction_type']}\n";
        echo "  From: {$price['from']}\n";
        echo "  To: {$price['to']}\n";
        echo "\n";
    }

    // Check if this is required for ALL products
    echo "Step 2: Checking how many products have specific prices...\n";
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT id_product)
        FROM ps_specific_price
    ");
    $productsWithSpecificPrice = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM ps_product WHERE active = 1");
    $totalActiveProducts = $stmt->fetchColumn();

    echo "✓ Products with specific prices: {$productsWithSpecificPrice}\n";
    echo "✓ Total active products: {$totalActiveProducts}\n";
    echo "✓ Percentage: " . round(($productsWithSpecificPrice / $totalActiveProducts) * 100, 1) . "%\n\n";

    if ($productsWithSpecificPrice / $totalActiveProducts > 0.9) {
        echo "⚠️ CRITICAL: Most products have specific prices!\n";
        echo "This is likely REQUIRED for products to be visible in admin.\n\n";
    }

    // Check customer groups
    echo "Step 3: Checking customer groups...\n";
    $stmt = $pdo->query("
        SELECT
            id_group,
            name,
            reduction,
            price_display_method
        FROM ps_group_lang
        WHERE id_lang = 1
    ");
    $groups = $stmt->fetchAll();

    echo "✓ Available customer groups:\n";
    foreach ($groups as $group) {
        echo "  - [ID: {$group['id_group']}] {$group['name']}\n";
    }
    echo "\n";

    // Show sample INSERT statement
    echo "Step 4: Sample INSERT for product 9762...\n\n";
    echo "To add specific price for product 9762, use:\n\n";

    // Get base price from product
    $stmt = $pdo->prepare("SELECT price FROM ps_product WHERE id_product = 9762");
    $stmt->execute();
    $basePrice = $stmt->fetchColumn();

    if ($basePrice == 0) {
        $basePrice = 0.01; // Minimal price
    }

    echo "INSERT INTO ps_specific_price (\n";
    echo "    id_product, id_shop, id_currency, id_country, id_group,\n";
    echo "    id_customer, id_product_attribute, price, from_quantity,\n";
    echo "    reduction, reduction_type, `from`, `to`\n";
    echo ") VALUES (\n";
    echo "    9762, 1, 0, 0, 0,\n";
    echo "    0, 0, {$basePrice}, 1,\n";
    echo "    0.000000, 'amount', '0000-00-00 00:00:00', '0000-00-00 00:00:00'\n";
    echo ");\n\n";

    echo "This will set a specific price of {$basePrice} EUR for all customer groups.\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
