/**
 * Check PrestaShop Shops Status
 */

echo "=== PRESTASHOP SHOPS STATUS ===\n\n";

$shops = DB::table('prestashop_shops')
    ->select('id', 'name', 'url', 'is_active', 'connection_status', 'api_key')
    ->get();

foreach ($shops as $shop) {
    echo "Shop #{$shop->id}: {$shop->name}\n";
    echo "  URL: {$shop->url}\n";
    echo "  Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
    echo "  Connection: {$shop->connection_status}\n";
    echo "  Has API Key: " . ($shop->api_key ? 'YES' : 'NO') . "\n";

    // Try decrypt
    if ($shop->api_key) {
        try {
            decrypt($shop->api_key);
            echo "  Decrypt: ✅ OK\n";
        } catch (\Exception $e) {
            echo "  Decrypt: ❌ FAILED\n";
        }
    }

    echo "\n";
}
