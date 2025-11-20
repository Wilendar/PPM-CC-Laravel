<?php

/**
 * VERIFY: Manufacturer fix for product 9762
 */

echo "=== VERIFY MANUFACTURER FIX ===\n\n";

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

    // Check product 9762
    $stmt = $pdo->prepare("
        SELECT
            p.id_product,
            p.reference,
            p.id_manufacturer,
            m.name as manufacturer_name,
            pl.name as product_name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer
        WHERE p.id_product = 9762
    ");
    $stmt->execute();
    $product = $stmt->fetch();

    if (!$product) {
        echo "❌ Product 9762 not found!\n";
        exit(1);
    }

    echo "Product 9762 Status:\n";
    echo "  - Name: {$product['product_name']}\n";
    echo "  - Reference: {$product['reference']}\n";
    echo "  - Manufacturer ID: " . ($product['id_manufacturer'] ?: 'NULL ❌') . "\n";
    echo "  - Manufacturer Name: " . ($product['manufacturer_name'] ?: 'N/A') . "\n\n";

    if ($product['id_manufacturer']) {
        echo "✅ Product now has manufacturer assigned!\n\n";
        echo "=== NEXT STEPS ===\n\n";
        echo "1. Go to PrestaShop admin panel:\n";
        echo "   https://dev.mpptrade.pl/admin/\n\n";
        echo "2. Navigate to: Catalog → Products\n\n";
        echo "3. Search for product:\n";
        echo "   - By SKU: TEST-CREATE-1762351961\n";
        echo "   - By ID: 9762\n";
        echo "   - By Name: Test CREATE with Categories\n\n";
        echo "4. Product should now be VISIBLE in the list!\n\n";
        echo "5. If still not visible:\n";
        echo "   - Clear browser cache (Ctrl+Shift+Del)\n";
        echo "   - Try incognito mode\n";
        echo "   - Check if manufacturer filter is active\n\n";
    } else {
        echo "❌ Product still has NO manufacturer!\n";
        echo "The fix did not work properly.\n";
    }

    // Check all test products
    echo "Status of all TEST products:\n\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            p.id_manufacturer,
            m.name as manufacturer_name,
            pl.name as product_name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer
        WHERE p.reference LIKE 'TEST-%'
        ORDER BY p.id_product DESC
    ");
    $testProducts = $stmt->fetchAll();

    foreach ($testProducts as $tp) {
        $mfrStatus = $tp['id_manufacturer'] ? '✓' : '❌';
        echo "  [{$tp['id_product']}] {$tp['product_name']}\n";
        echo "    Manufacturer: " . ($tp['manufacturer_name'] ?: 'NONE') . " {$mfrStatus}\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
