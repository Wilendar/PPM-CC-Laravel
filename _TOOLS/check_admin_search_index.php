<?php

/**
 * CHECK: Admin search index for product 9762
 */

echo "=== ADMIN SEARCH INDEX CHECK ===\n\n";

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

    // Check ps_search_index
    echo "Step 1: Checking ps_search_index table...\n";
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM ps_search_index
        WHERE id_product = 9762
    ");
    $stmt->execute();
    $indexCount = $stmt->fetchColumn();

    if ($indexCount == 0) {
        echo "❌ Product 9762 NOT in search index!\n";
        echo "This may affect searchability in both admin and frontend.\n\n";
    } else {
        echo "✓ Product 9762 has {$indexCount} search index entries\n\n";
    }

    // Check ps_search_word
    echo "Step 2: Checking indexed words for product 9762...\n";
    $stmt = $pdo->prepare("
        SELECT
            sw.word,
            si.weight
        FROM ps_search_index si
        JOIN ps_search_word sw ON si.id_word = sw.id_word
        WHERE si.id_product = 9762
        LIMIT 10
    ");
    $stmt->execute();
    $words = $stmt->fetchAll();

    if (empty($words)) {
        echo "⚠️ No indexed words found\n\n";
    } else {
        echo "✓ Sample indexed words:\n";
        foreach ($words as $word) {
            echo "  - '{$word['word']}' (weight: {$word['weight']})\n";
        }
        echo "\n";
    }

    // Check comparison with another product from same category
    echo "Step 3: Comparing with other products in PITGANG category...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            pl.name,
            p.active
        FROM ps_category_product cp
        JOIN ps_product p ON cp.id_product = p.id_product
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE cp.id_category = 12
        ORDER BY p.id_product DESC
        LIMIT 5
    ");
    $products = $stmt->fetchAll();

    echo "✓ Other products in PITGANG:\n";
    foreach ($products as $prod) {
        $marker = ($prod['id_product'] == 9762) ? ' ← TARGET' : '';
        $activeStatus = $prod['active'] ? '✓' : '❌';
        echo "  - [ID: {$prod['id_product']}] {$prod['name']} (Active: {$activeStatus}){$marker}\n";
    }
    echo "\n";

    // Summary
    echo "=== RECOMMENDATION ===\n\n";

    if ($indexCount == 0) {
        echo "❌ Product needs to be reindexed!\n\n";
        echo "SOLUTION OPTIONS:\n\n";
        echo "Option 1: Trigger reindex via product edit\n";
        echo "  1. Open product in admin: https://dev.mpptrade.pl/admin/index.php?controller=AdminProducts&id_product=9762&updateproduct\n";
        echo "  2. Make small change (e.g. add space to description)\n";
        echo "  3. Save product\n";
        echo "  4. This will trigger automatic reindex\n\n";

        echo "Option 2: Manual SQL reindex trigger\n";
        echo "  UPDATE ps_product SET indexed = 0 WHERE id_product = 9762;\n";
        echo "  (Then visit product page on frontend to trigger indexing)\n\n";

        echo "Option 3: Full search rebuild (use with caution!)\n";
        echo "  In admin: Preferences → Search → Click 'Rebuild entire index'\n";
        echo "  (This may take time if you have many products)\n\n";
    } else {
        echo "✅ Product IS indexed for search.\n\n";
        echo "Try these steps in admin panel:\n";
        echo "1. Catalog → Products\n";
        echo "2. Clear all filters (click Reset button)\n";
        echo "3. Search by exact SKU: TEST-CREATE-1762351961\n";
        echo "4. Or try direct URL: https://dev.mpptrade.pl/admin/index.php?controller=AdminProducts&id_product=9762&updateproduct\n\n";

        echo "If STILL not visible:\n";
        echo "- Check JavaScript console errors (F12)\n";
        echo "- Try different browser/incognito mode\n";
        echo "- Check if admin user has Products permission\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
