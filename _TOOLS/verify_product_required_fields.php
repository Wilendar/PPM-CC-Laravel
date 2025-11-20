<?php

/**
 * Verify Product Required Fields
 *
 * BUGFIX 2025-11-06: Verification script for PrestaShop admin panel visibility
 * Reference: _DOCS/PRESTASHOP_REQUIRED_FIELDS.md
 *
 * Checks all 7 required fields for product visibility in admin panel:
 * 1. id_manufacturer (NOT NULL, NOT 0)
 * 2. ps_specific_price record
 * 3. minimal_quantity = 1
 * 4. redirect_type = '301-category'
 * 5. state = 1
 * 6. additional_delivery_times = 1
 * 7. price > 0
 *
 * Usage:
 *   php _TOOLS/verify_product_required_fields.php <product_id>
 *   php _TOOLS/verify_product_required_fields.php 9762
 */

// Get product ID from command line
if ($argc < 2) {
    echo "Usage: php verify_product_required_fields.php <product_id>\n";
    echo "Example: php verify_product_required_fields.php 9762\n";
    exit(1);
}

$productId = (int) $argv[1];

if ($productId <= 0) {
    echo "Error: Invalid product ID\n";
    exit(1);
}

// Database credentials
// TODO: Read from .env or config instead of hardcoding
$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

echo "=== PRESTASHOP PRODUCT REQUIRED FIELDS VERIFICATION ===\n\n";
echo "Product ID: {$productId}\n";
echo "Database: {$dbName}\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Fetch product data from ps_product
    $stmt = $pdo->prepare("
        SELECT
            id_product,
            reference,
            id_manufacturer,
            minimal_quantity,
            redirect_type,
            state,
            additional_delivery_times,
            price
        FROM ps_product
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "❌ PRODUCT NOT FOUND in ps_product table!\n";
        exit(1);
    }

    echo "Product: {$product['reference']}\n\n";

    // Fetch product data from ps_product_shop
    $stmt = $pdo->prepare("
        SELECT
            minimal_quantity,
            redirect_type,
            price
        FROM ps_product_shop
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $productShop = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productShop) {
        echo "⚠️  WARNING: Product NOT FOUND in ps_product_shop table!\n\n";
    }

    // Check ps_specific_price
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM ps_specific_price
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $specificPriceCount = $stmt->fetchColumn();

    // Verification results
    $allPassed = true;
    $results = [];

    // CHECK 1: id_manufacturer
    $check1 = $product['id_manufacturer'] > 0;
    $results[] = [
        'field' => 'id_manufacturer',
        'table' => 'ps_product',
        'value' => $product['id_manufacturer'],
        'expected' => '> 0 (NOT NULL, NOT 0)',
        'status' => $check1 ? 'PASS' : 'FAIL',
    ];
    if (!$check1) $allPassed = false;

    // CHECK 2: ps_specific_price exists
    $check2 = $specificPriceCount > 0;
    $results[] = [
        'field' => 'ps_specific_price',
        'table' => 'ps_specific_price',
        'value' => "{$specificPriceCount} record(s)",
        'expected' => '> 0 records',
        'status' => $check2 ? 'PASS' : 'FAIL',
    ];
    if (!$check2) $allPassed = false;

    // CHECK 3: minimal_quantity (ps_product)
    $check3 = $product['minimal_quantity'] == 1;
    $results[] = [
        'field' => 'minimal_quantity',
        'table' => 'ps_product',
        'value' => $product['minimal_quantity'],
        'expected' => '1',
        'status' => $check3 ? 'PASS' : 'FAIL',
    ];
    if (!$check3) $allPassed = false;

    // CHECK 4: minimal_quantity (ps_product_shop)
    if ($productShop) {
        $check4 = $productShop['minimal_quantity'] == 1;
        $results[] = [
            'field' => 'minimal_quantity',
            'table' => 'ps_product_shop',
            'value' => $productShop['minimal_quantity'],
            'expected' => '1',
            'status' => $check4 ? 'PASS' : 'FAIL',
        ];
        if (!$check4) $allPassed = false;
    } else {
        $results[] = [
            'field' => 'minimal_quantity',
            'table' => 'ps_product_shop',
            'value' => 'N/A',
            'expected' => '1',
            'status' => 'SKIP',
        ];
        $allPassed = false;
    }

    // CHECK 5: redirect_type (ps_product)
    $check5 = $product['redirect_type'] === '301-category';
    $results[] = [
        'field' => 'redirect_type',
        'table' => 'ps_product',
        'value' => "'{$product['redirect_type']}'",
        'expected' => "'301-category'",
        'status' => $check5 ? 'PASS' : 'FAIL',
    ];
    if (!$check5) $allPassed = false;

    // CHECK 6: redirect_type (ps_product_shop)
    if ($productShop) {
        $check6 = $productShop['redirect_type'] === '301-category';
        $results[] = [
            'field' => 'redirect_type',
            'table' => 'ps_product_shop',
            'value' => "'{$productShop['redirect_type']}'",
            'expected' => "'301-category'",
            'status' => $check6 ? 'PASS' : 'FAIL',
        ];
        if (!$check6) $allPassed = false;
    } else {
        $results[] = [
            'field' => 'redirect_type',
            'table' => 'ps_product_shop',
            'value' => 'N/A',
            'expected' => "'301-category'",
            'status' => 'SKIP',
        ];
        $allPassed = false;
    }

    // CHECK 7: state
    $check7 = $product['state'] == 1;
    $results[] = [
        'field' => 'state',
        'table' => 'ps_product',
        'value' => $product['state'],
        'expected' => '1',
        'status' => $check7 ? 'PASS' : 'FAIL',
    ];
    if (!$check7) $allPassed = false;

    // CHECK 8: additional_delivery_times
    $check8 = $product['additional_delivery_times'] == 1;
    $results[] = [
        'field' => 'additional_delivery_times',
        'table' => 'ps_product',
        'value' => $product['additional_delivery_times'],
        'expected' => '1',
        'status' => $check8 ? 'PASS' : 'FAIL',
    ];
    if (!$check8) $allPassed = false;

    // CHECK 9: price (ps_product)
    $check9 = $product['price'] > 0;
    $results[] = [
        'field' => 'price',
        'table' => 'ps_product',
        'value' => $product['price'],
        'expected' => '> 0',
        'status' => $check9 ? 'PASS' : 'FAIL',
    ];
    if (!$check9) $allPassed = false;

    // CHECK 10: price (ps_product_shop)
    if ($productShop) {
        $check10 = $productShop['price'] > 0;
        $results[] = [
            'field' => 'price',
            'table' => 'ps_product_shop',
            'value' => $productShop['price'],
            'expected' => '> 0',
            'status' => $check10 ? 'PASS' : 'FAIL',
        ];
        if (!$check10) $allPassed = false;
    } else {
        $results[] = [
            'field' => 'price',
            'table' => 'ps_product_shop',
            'value' => 'N/A',
            'expected' => '> 0',
            'status' => 'SKIP',
        ];
        $allPassed = false;
    }

    // Display results
    echo "=== VERIFICATION RESULTS ===\n\n";

    $maxFieldLen = max(array_map(fn($r) => strlen($r['field']), $results));
    $maxTableLen = max(array_map(fn($r) => strlen($r['table']), $results));
    $maxValueLen = max(array_map(fn($r) => strlen((string) $r['value']), $results));
    $maxExpectedLen = max(array_map(fn($r) => strlen($r['expected']), $results));

    // Header
    printf(
        "%-{$maxFieldLen}s  %-{$maxTableLen}s  %-{$maxValueLen}s  %-{$maxExpectedLen}s  %s\n",
        'FIELD',
        'TABLE',
        'VALUE',
        'EXPECTED',
        'STATUS'
    );
    echo str_repeat('-', $maxFieldLen + $maxTableLen + $maxValueLen + $maxExpectedLen + 12) . "\n";

    // Results
    foreach ($results as $result) {
        $statusIcon = match ($result['status']) {
            'PASS' => '✅',
            'FAIL' => '❌',
            'SKIP' => '⚠️ ',
            default => '  ',
        };

        printf(
            "%-{$maxFieldLen}s  %-{$maxTableLen}s  %-{$maxValueLen}s  %-{$maxExpectedLen}s  %s %s\n",
            $result['field'],
            $result['table'],
            $result['value'],
            $result['expected'],
            $statusIcon,
            $result['status']
        );
    }

    echo "\n";

    // Final verdict
    if ($allPassed) {
        echo "✅ ALL CHECKS PASSED!\n\n";
        echo "Product should be VISIBLE in PrestaShop admin panel.\n";
        exit(0);
    } else {
        echo "❌ SOME CHECKS FAILED!\n\n";
        echo "Product will be INVISIBLE in PrestaShop admin panel until all fields are fixed.\n\n";
        echo "To fix, run:\n";
        echo "  UPDATE ps_product SET\n";

        $updates = [];
        if (!$check1) $updates[] = "    id_manufacturer = 1  -- or valid manufacturer ID";
        if (!$check3) $updates[] = "    minimal_quantity = 1";
        if (!$check5) $updates[] = "    redirect_type = '301-category'";
        if (!$check7) $updates[] = "    state = 1";
        if (!$check8) $updates[] = "    additional_delivery_times = 1";
        if (!$check9) $updates[] = "    price = GREATEST(price, 0.01)";

        if (!empty($updates)) {
            echo implode(",\n", $updates) . "\n";
            echo "  WHERE id_product = {$productId};\n\n";
        }

        if (!$check2) {
            echo "  -- Create ps_specific_price\n";
            echo "  INSERT INTO ps_specific_price (\n";
            echo "    id_product, id_shop, id_currency, id_country, id_group,\n";
            echo "    id_customer, id_product_attribute, price, from_quantity,\n";
            echo "    reduction, reduction_type, `from`, `to`\n";
            echo "  ) VALUES (\n";
            echo "    {$productId}, 0, 0, 0, 0, 0, 0, {$product['price']}, 1,\n";
            echo "    0.000000, 'amount', '0000-00-00 00:00:00', '0000-00-00 00:00:00'\n";
            echo "  );\n\n";
        }

        exit(1);
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
