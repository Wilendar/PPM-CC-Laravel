<?php
// Check custom.css path for shop 5
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = App\Models\PrestaShopShop::find(5);

echo "Shop ID: {$shop->id}\n";
echo "Shop URL: {$shop->url}\n\n";

// Check css_files
$cssFiles = $shop->css_files ?? [];
echo "CSS Files found: " . count($cssFiles) . "\n\n";

foreach ($cssFiles as $f) {
    $name = $f['name'] ?? $f['filename'] ?? 'unknown';
    if (str_contains(strtolower($name), 'custom')) {
        echo "=== CUSTOM CSS FILE ===\n";
        echo "Name: {$name}\n";
        echo "URL: " . ($f['url'] ?? 'N/A') . "\n";
        echo "Category: " . ($f['category'] ?? 'N/A') . "\n";
        echo "Enabled: " . (($f['enabled'] ?? false) ? 'yes' : 'no') . "\n";
        echo "\n";
    }
}

// Check FTP config
$ftpConfig = $shop->ftp_config ?? [];
echo "=== FTP CONFIG ===\n";
echo "Host: " . ($ftpConfig['host'] ?? 'N/A') . "\n";
echo "Theme: " . ($ftpConfig['theme_name'] ?? 'N/A') . "\n";
echo "CSS Path: " . ($ftpConfig['css_path'] ?? 'N/A') . "\n";
