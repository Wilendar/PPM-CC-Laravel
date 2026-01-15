<?php

/**
 * Script to restore original custom.css to PrestaShop shop
 * Run via: php artisan tinker < restore_original_css.php
 */

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopCssFetcher;

$shopId = 5; // test.kayomoto.pl

// Original CSS content (first part to verify structure)
$originalCssPath = base_path('../References/css/custom.css');

if (!file_exists($originalCssPath)) {
    echo "ERROR: Original CSS file not found at: {$originalCssPath}\n";
    exit(1);
}

$originalCss = file_get_contents($originalCssPath);
echo "Loaded original CSS: " . strlen($originalCss) . " bytes\n";
echo "First 100 chars: " . substr($originalCss, 0, 100) . "\n";

// Get shop
$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    echo "ERROR: Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name} ({$shop->url})\n";

// Upload via FTP
$fetcher = app(PrestaShopCssFetcher::class);
$result = $fetcher->saveCustomCss($shop, $originalCss);

if ($result['success']) {
    echo "SUCCESS: Original CSS restored!\n";

    // Clear the cache to force fresh fetch
    $shop->update(['cached_custom_css' => null, 'css_last_fetched_at' => null]);
    echo "Cache cleared.\n";
} else {
    echo "ERROR: " . ($result['error'] ?? 'Unknown error') . "\n";
}
