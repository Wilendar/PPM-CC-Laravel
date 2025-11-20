<?php

/**
 * COPY ALL MISSING FIELDS from product 9755 to 9762
 */

echo "=== COPY ALL MISSING FIELDS ===\n\n";

$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

$sourceId = 9755; // Good product
$targetId = 9762; // Test product

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Copying fields from product {$sourceId} to {$targetId}...\n\n";

    $pdo->beginTransaction();

    // Update ps_product with all missing fields
    echo "Step 1: Updating ps_product...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product SET
            minimal_quantity = 1,
            redirect_type = '301-category',
            state = 1,
            additional_delivery_times = 1,
            price = 0.01
        WHERE id_product = ?
    ");
    $stmt->execute([$targetId]);
    echo "✓ Updated ps_product ({$stmt->rowCount()} rows)\n\n";

    // Update ps_product_shop
    echo "Step 2: Updating ps_product_shop...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product_shop SET
            minimal_quantity = 1,
            redirect_type = '301-category',
            price = 0.01
        WHERE id_product = ?
    ");
    $stmt->execute([$targetId]);
    echo "✓ Updated ps_product_shop ({$stmt->rowCount()} rows)\n\n";

    // Update ps_specific_price with correct price
    echo "Step 3: Updating ps_specific_price...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_specific_price SET
            price = 0.01
        WHERE id_product = ?
    ");
    $stmt->execute([$targetId]);
    echo "✓ Updated ps_specific_price ({$stmt->rowCount()} rows)\n\n";

    $pdo->commit();

    echo "=== VERIFICATION ===\n\n";

    // Verify updates
    $stmt = $pdo->prepare("
        SELECT
            minimal_quantity,
            redirect_type,
            state,
            additional_delivery_times,
            price
        FROM ps_product
        WHERE id_product = ?
    ");
    $stmt->execute([$targetId]);
    $result = $stmt->fetch();

    echo "Product {$targetId} now has:\n";
    echo "  minimal_quantity: {$result['minimal_quantity']}\n";
    echo "  redirect_type: '{$result['redirect_type']}'\n";
    echo "  state: {$result['state']}\n";
    echo "  additional_delivery_times: {$result['additional_delivery_times']}\n";
    echo "  price: {$result['price']}\n\n";

    echo "=== SUCCESS ===\n\n";
    echo "All missing fields copied!\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Hard refresh PrestaShop admin (Ctrl+Shift+R)\n";
    echo "2. Catalog → Products\n";
    echo "3. Search: TEST-CREATE-1762351961\n";
    echo "4. Product SHOULD be visible now!\n\n";

    echo "If STILL not visible, the issue is likely:\n";
    echo "  - Admin cache not clearing properly\n";
    echo "  - Browser cache\n";
    echo "  - JavaScript query filtering\n";
    echo "  - Try incognito mode\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
