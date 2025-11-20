/**
 * Fix Encrypted API Keys
 *
 * Problem: APP_KEY changed between local and production
 * Result: decrypt() fails with "The payload is invalid"
 *
 * This script re-encrypts API keys using current production APP_KEY
 *
 * Usage: php artisan tinker < _TOOLS/fix_encrypted_api_keys.php
 */

echo "=== FIX ENCRYPTED API KEYS ===\n\n";

// Get all shops
$shops = \App\Models\PrestaShopShop::all();

echo "Found " . $shops->count() . " PrestaShop shops\n\n";

foreach ($shops as $shop) {
    echo "Shop #{$shop->id}: {$shop->name}\n";
    echo "  URL: {$shop->url}\n";

    // Try to get raw encrypted value
    $rawApiKey = DB::table('prestashop_shops')
        ->where('id', $shop->id)
        ->value('api_key');

    if (!$rawApiKey) {
        echo "  ⚠️  No API key in database\n\n";
        continue;
    }

    // Try to decrypt - will fail if wrong APP_KEY
    try {
        $decrypted = decrypt($rawApiKey);
        echo "  ✅ API key decrypts successfully\n";
        echo "  Key preview: " . substr($decrypted, 0, 10) . "...\n\n";
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        echo "  ❌ DECRYPT FAILED: {$e->getMessage()}\n";
        echo "  🔧 ACTION REQUIRED: Provide API key for re-encryption\n\n";

        // You'll need to manually provide the API key here
        // Example:
        // $newApiKey = 'YOUR_API_KEY_HERE';
        // DB::table('prestashop_shops')
        //     ->where('id', $shop->id)
        //     ->update(['api_key' => encrypt($newApiKey)]);
    }
}

echo "\n=== SUMMARY ===\n";
echo "If decryption failed, you need to:\n";
echo "1. Get API keys from PrestaShop admin panels\n";
echo "2. Update database manually with re-encrypted values\n";
echo "3. Or modify this script to set new values\n\n";

echo "Example manual update:\n";
echo "DB::table('prestashop_shops')->where('id', 1)->update(['api_key' => encrypt('NEW_KEY_HERE')]);\n\n";
