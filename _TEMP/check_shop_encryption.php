<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;

echo "=== SHOP ENCRYPTION DIAGNOSTIC ===\n\n";

$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "ERROR: Shop ID 1 NOT FOUND\n";
    exit(1);
}

echo "Shop ID: {$shop->id}\n";
echo "Shop Name: {$shop->name}\n";
echo "API URL: {$shop->api_url}\n";
echo "Version: {$shop->version}\n";
echo "Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n\n";

echo "ENCRYPTION TEST:\n";
echo "Raw api_key (encrypted): " . substr($shop->getAttributes()['api_key'], 0, 50) . "...\n\n";

try {
    $decrypted = decrypt($shop->api_key);
    echo "✅ DECRYPTION SUCCESSFUL\n";
    echo "Decrypted API Key: " . substr($decrypted, 0, 20) . "..." . substr($decrypted, -10) . "\n";
} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
    echo "❌ DECRYPTION FAILED: {$e->getMessage()}\n\n";

    echo "SOLUTION OPTIONS:\n";
    echo "1. Re-enter API key via Edit Shop form (will re-encrypt with current APP_KEY)\n";
    echo "2. Restore old APP_KEY from backup (if available)\n";
    echo "3. Manually fix encrypted value in database\n\n";

    echo "AFFECTED SHOPS:\n";
    $shops = PrestaShopShop::all();
    foreach ($shops as $s) {
        try {
            decrypt($s->api_key);
            echo "  ✅ Shop {$s->id} ({$s->name}): OK\n";
        } catch (\Exception $e) {
            echo "  ❌ Shop {$s->id} ({$s->name}): ENCRYPTION ERROR\n";
        }
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
