<?php

use App\Models\PrestaShopShop;

echo "\n=== PRESTASHOP SHOPS ===\n\n";

$shops = PrestaShopShop::all();

if ($shops->isEmpty()) {
    echo "No shops found in database.\n";
    return;
}

foreach ($shops as $shop) {
    echo "[{$shop->id}] {$shop->name}\n";
    echo "    URL: {$shop->url}\n";
    echo "    Version: {$shop->version}\n";
    echo "    API Key: " . substr($shop->api_key, 0, 20) . "...\n";
    echo "    Sync Enabled: " . ($shop->sync_enabled ? 'Yes' : 'No') . "\n";
    echo "\n";
}

echo "Total: " . $shops->count() . " shops\n\n";
