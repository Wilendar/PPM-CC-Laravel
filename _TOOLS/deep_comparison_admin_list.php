<?php

/**
 * DEEP COMPARISON: What makes products invisible in admin list
 */

echo "=== DEEP ADMIN LIST COMPARISON ===\n\n";

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

    // Get one VISIBLE product (old, created in PS)
    $visibleId = 1828;
    // Get one INVISIBLE product (new, from PPM)
    $invisibleId = 9762;

    echo "Comparing:\n";
    echo "  VISIBLE: Product ID {$visibleId}\n";
    echo "  INVISIBLE: Product ID {$invisibleId}\n\n";

    // Get ALL columns from ps_product
    echo "Step 1: Full ps_product comparison...\n";
    $stmt = $pdo->prepare("SELECT * FROM ps_product WHERE id_product IN (?, ?) ORDER BY id_product");
    $stmt->execute([$visibleId, $invisibleId]);
    $products = $stmt->fetchAll();

    $visible = $products[0];
    $invisible = $products[1];

    echo "ALL DIFFERENCES in ps_product:\n\n";
    foreach ($visible as $key => $vVal) {
        if (is_numeric($key)) continue;

        $iVal = $invisible[$key];

        // Skip obvious differences
        if (in_array($key, ['id_product', 'reference', 'date_add', 'date_upd'])) continue;

        if ($vVal != $iVal) {
            $vDisplay = ($vVal === null) ? 'NULL' : (($vVal === '') ? 'EMPTY' : $vVal);
            $iDisplay = ($iVal === null) ? 'NULL' : (($iVal === '') ? 'EMPTY' : $iVal);

            echo "  {$key}:\n";
            echo "    VISIBLE: {$vDisplay}\n";
            echo "    INVISIBLE: {$iDisplay}\n";

            // Highlight critical differences
            if ($vVal !== null && $vVal !== '' && ($iVal === null || $iVal === '' || $iVal === '0')) {
                echo "    ⚠️ INVISIBLE MISSING/NULL!\n";
            }
            echo "\n";
        }
    }

    // Check ps_product_shop differences
    echo "\nStep 2: ps_product_shop comparison...\n";
    $stmt = $pdo->prepare("SELECT * FROM ps_product_shop WHERE id_product IN (?, ?) ORDER BY id_product");
    $stmt->execute([$visibleId, $invisibleId]);
    $shopData = $stmt->fetchAll();

    if (count($shopData) < 2) {
        echo "❌ CRITICAL: One product missing from ps_product_shop!\n";
    } else {
        $visibleShop = $shopData[0];
        $invisibleShop = $shopData[1];

        echo "DIFFERENCES in ps_product_shop:\n\n";
        foreach ($visibleShop as $key => $vVal) {
            if (is_numeric($key)) continue;
            if (in_array($key, ['id_product', 'date_add', 'date_upd'])) continue;

            $iVal = $invisibleShop[$key];

            if ($vVal != $iVal) {
                $vDisplay = ($vVal === null) ? 'NULL' : (($vVal === '') ? 'EMPTY' : $vVal);
                $iDisplay = ($iVal === null) ? 'NULL' : (($iVal === '') ? 'EMPTY' : $iVal);

                echo "  {$key}:\n";
                echo "    VISIBLE: {$vDisplay}\n";
                echo "    INVISIBLE: {$iDisplay}\n";

                if ($vVal !== null && $vVal !== '' && ($iVal === null || $iVal === '' || $iVal === '0')) {
                    echo "    ⚠️ INVISIBLE MISSING/NULL!\n";
                }
                echo "\n";
            }
        }
    }

    // Check if there's a ps_product_supplier issue
    echo "\nStep 3: Checking ps_product_supplier...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ps_product_supplier WHERE id_product = ?");

    $stmt->execute([$visibleId]);
    $visibleSuppliers = $stmt->fetchColumn();

    $stmt->execute([$invisibleId]);
    $invisibleSuppliers = $stmt->fetchColumn();

    echo "  VISIBLE product: {$visibleSuppliers} supplier(s)\n";
    echo "  INVISIBLE product: {$invisibleSuppliers} supplier(s)\n";

    if ($visibleSuppliers > 0 && $invisibleSuppliers == 0) {
        echo "  ⚠️ INVISIBLE product has NO suppliers!\n";
    }

    // Check admin employee restrictions
    echo "\nStep 4: Checking product visibility restrictions...\n";

    // ps_category_group (category access by group)
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM ps_category_product cp
        LEFT JOIN ps_category_group cg ON cp.id_category = cg.id_category
        WHERE cp.id_product = ? AND cg.id_group IS NOT NULL
    ");

    $stmt->execute([$visibleId]);
    $visibleGroups = $stmt->fetchColumn();

    $stmt->execute([$invisibleId]);
    $invisibleGroups = $stmt->fetchColumn();

    echo "  VISIBLE product: {$visibleGroups} category group restrictions\n";
    echo "  INVISIBLE product: {$invisibleGroups} category group restrictions\n";

    echo "\n=== SUMMARY OF CRITICAL FIELDS ===\n\n";

    $criticalFields = [
        'id_manufacturer',
        'id_supplier',
        'id_category_default',
        'price',
        'active',
        'visibility'
    ];

    echo "Critical fields comparison:\n";
    foreach ($criticalFields as $field) {
        $vVal = $visible[$field];
        $iVal = $invisible[$field];

        $vDisplay = ($vVal === null) ? 'NULL' : $vVal;
        $iDisplay = ($iVal === null) ? 'NULL' : $iVal;

        $status = ($vVal == $iVal) ? '✓' : '❌';

        echo "  {$field}: V={$vDisplay} vs I={$iDisplay} {$status}\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
