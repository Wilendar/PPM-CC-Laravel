/**
 * Disable Shops with Broken API Keys
 *
 * Dezaktywuje shops #2, #3, #4 które mają uszkodzone (niezdeszyfrowane) API keys
 * To odblokuje synchronizacje dla działających shopów (#1, #5)
 */

echo "=== DISABLE BROKEN SHOPS ===\n\n";

// Shops with broken encryption
$brokenShops = [2, 3, 4];

echo "Disabling shops with broken API keys:\n";

foreach ($brokenShops as $shopId) {
    $shop = DB::table('prestashop_shops')->where('id', $shopId)->first();

    if (!$shop) {
        echo "  Shop #{$shopId}: NOT FOUND\n";
        continue;
    }

    echo "  Shop #{$shopId}: {$shop->name} ({$shop->url})\n";

    if (!$shop->is_active) {
        echo "    Already disabled\n";
        continue;
    }

    // Disable shop
    DB::table('prestashop_shops')
        ->where('id', $shopId)
        ->update(['is_active' => false]);

    echo "    ✅ DISABLED\n";
}

echo "\n=== RESULT ===\n";

$activeShops = DB::table('prestashop_shops')
    ->where('is_active', true)
    ->get();

echo "Active shops remaining: " . $activeShops->count() . "\n";

foreach ($activeShops as $shop) {
    echo "  Shop #{$shop->id}: {$shop->name}\n";
}

echo "\n✅ Synchronizacje powinny teraz działać dla aktywnych shopów!\n\n";
