<?php

/**
 * FIX: Add ps_specific_price for test products
 */

echo "=== ADD SPECIFIC PRICES TO TEST PRODUCTS ===\n\n";

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

    // Get all TEST products without specific prices
    echo "Step 1: Finding TEST products without specific prices...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            p.price,
            pl.name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE p.reference LIKE 'TEST-%'
        AND p.id_product NOT IN (SELECT id_product FROM ps_specific_price)
        ORDER BY p.id_product
    ");
    $products = $stmt->fetchAll();

    if (empty($products)) {
        echo "✓ All TEST products already have specific prices!\n";
        exit(0);
    }

    echo "❌ Found " . count($products) . " TEST products without specific prices:\n\n";
    foreach ($products as $prod) {
        $price = $prod['price'];
        if ($price == 0) {
            $price = 0.01; // Set minimal price
        }
        echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
        echo "    Reference: {$prod['reference']}\n";
        echo "    Current price: {$prod['price']} → Will use: {$price}\n";
    }
    echo "\n";

    // Add specific prices
    echo "Step 2: Adding specific prices...\n\n";

    $pdo->beginTransaction();

    $addedCount = 0;
    $updatedPrices = 0;

    foreach ($products as $prod) {
        $productId = $prod['id_product'];
        $price = $prod['price'];

        // Update product price if it's 0
        if ($price == 0) {
            $price = 0.01;
            $stmt = $pdo->prepare("UPDATE ps_product SET price = ? WHERE id_product = ?");
            $stmt->execute([$price, $productId]);

            $stmt = $pdo->prepare("UPDATE ps_product_shop SET price = ? WHERE id_product = ?");
            $stmt->execute([$price, $productId]);

            $updatedPrices++;
            echo "  ✓ Updated price for product {$productId}: 0.00 → {$price}\n";
        }

        // Add specific price record
        $stmt = $pdo->prepare("
            INSERT INTO ps_specific_price (
                id_product,
                id_shop,
                id_currency,
                id_country,
                id_group,
                id_customer,
                id_product_attribute,
                price,
                from_quantity,
                reduction,
                reduction_type,
                `from`,
                `to`
            ) VALUES (
                ?, 0, 0, 0, 0, 0, 0, ?, 1, 0.000000, 'amount',
                '0000-00-00 00:00:00', '0000-00-00 00:00:00'
            )
        ");
        $stmt->execute([$productId, $price]);
        $addedCount++;

        echo "  ✓ Added specific price for product {$productId}\n";
    }

    $pdo->commit();

    echo "\n=== SUCCESS ===\n\n";
    echo "Summary:\n";
    echo "  - Added specific prices: {$addedCount} products\n";
    echo "  - Updated base prices: {$updatedPrices} products\n\n";

    echo "Modified products:\n";
    foreach ($products as $prod) {
        echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
    }
    echo "\n";

    echo "=== NEXT STEPS ===\n\n";
    echo "1. Refresh PrestaShop admin panel (Ctrl+F5)\n";
    echo "2. Catalog → Products\n";
    echo "3. Search for: TEST-CREATE-1762351961\n";
    echo "4. Product should NOW be VISIBLE! ✨\n\n";

    echo "If still not visible, there may be additional issues.\n";
    echo "Check JavaScript console (F12) for errors.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "✗ TRANSACTION ROLLED BACK!\n\n";
    }
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
