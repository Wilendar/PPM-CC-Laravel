<?php
// Check shop 5 FULL configuration for CSS sync

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);
if (!$shop) {
    echo "Shop 5 not found\n";
    exit(1);
}

echo "=== SHOP 5 CONFIGURATION ===\n\n";

echo "Shop Name: {$shop->name}\n";
echo "Shop URL: {$shop->url}\n";

echo "\n--- FTP CONFIG ---\n";
$ftpConfig = $shop->ftp_config ?? [];
if (!empty($ftpConfig)) {
    echo "Protocol: " . ($ftpConfig['protocol'] ?? 'not set') . "\n";
    echo "Host: " . ($ftpConfig['host'] ?? 'not set') . "\n";
    echo "Port: " . ($ftpConfig['port'] ?? 'not set') . "\n";
    echo "User: " . ($ftpConfig['user'] ?? 'not set') . "\n";
    echo "Password: " . (isset($ftpConfig['password']) ? '[SET]' : '[NOT SET]') . "\n";
    echo "CSS Path: " . ($ftpConfig['css_path'] ?? 'not set') . "\n";
    echo "Theme Name: " . ($ftpConfig['theme_name'] ?? 'not set') . "\n";
} else {
    echo "FTP NOT CONFIGURED!\n";
}

echo "\n--- CSS FILES (scanned) ---\n";
$cssFiles = $shop->css_files ?? [];
if (empty($cssFiles)) {
    echo "NO CSS FILES SCANNED!\n";
} else {
    echo "Found " . count($cssFiles) . " CSS files:\n";
    foreach ($cssFiles as $i => $file) {
        $filename = $file['filename'] ?? $file['name'] ?? basename($file['url'] ?? '');
        $url = $file['url'] ?? '';
        $category = $file['category'] ?? 'unknown';
        $enabled = $file['enabled'] ?? false;
        echo "  [{$i}] {$filename}\n";
        echo "      URL: {$url}\n";
        echo "      Category: {$category}, Enabled: " . ($enabled ? 'YES' : 'NO') . "\n";
    }
}

echo "\n--- CACHED CSS ---\n";
echo "custom_css_url: " . ($shop->custom_css_url ?? 'null') . "\n";
echo "cached_custom_css length: " . strlen($shop->cached_custom_css ?? '') . " bytes\n";
echo "css_last_fetched_at: " . ($shop->css_last_fetched_at ?? 'null') . "\n";
echo "css_last_deployed_at: " . ($shop->css_last_deployed_at ?? 'null') . "\n";
echo "css_deploy_status: " . ($shop->css_deploy_status ?? 'null') . "\n";
echo "css_deploy_message: " . ($shop->css_deploy_message ?? 'null') . "\n";

echo "\n--- HAS SCANNED FILES ---\n";
echo "hasScannedFiles(): " . ($shop->hasScannedFiles() ? 'YES' : 'NO') . "\n";
if (method_exists($shop, 'getEnabledCssFilesCount')) {
    echo "getEnabledCssFilesCount(): " . $shop->getEnabledCssFilesCount() . "\n";
}

echo "\n--- DETECT CUSTOM.CSS PATH ---\n";
// Try to find theme custom.css
foreach ($cssFiles as $file) {
    $filename = strtolower($file['filename'] ?? $file['name'] ?? '');
    $url = $file['url'] ?? '';
    if ($filename === 'custom.css' && str_contains($url, '/themes/')) {
        echo "Found THEME custom.css:\n";
        echo "  URL: {$url}\n";

        // Convert URL to FTP path
        $shopUrl = rtrim($shop->url, '/');
        $ftpPath = str_replace($shopUrl, '', $url);
        if (!str_starts_with($ftpPath, '/')) {
            $ftpPath = '/' . $ftpPath;
        }
        echo "  FTP Path: {$ftpPath}\n";
        echo "  With /public_html: /public_html{$ftpPath}\n";
    }
}

// Check if cached_custom_css contains UVE markers
echo "\n--- UVE MARKERS IN CACHED CSS ---\n";
$cachedCss = $shop->cached_custom_css ?? '';
if (str_contains($cachedCss, '@uve-styles-start')) {
    echo "UVE markers FOUND in cached CSS!\n";
    $start = strpos($cachedCss, '/* @uve-styles-start */');
    $end = strpos($cachedCss, '/* @uve-styles-end */');
    if ($start !== false && $end !== false) {
        echo "  Marker start position: {$start}\n";
        echo "  Marker end position: {$end}\n";
        $uveSection = substr($cachedCss, $start, $end - $start + 25);
        echo "  UVE section size: " . strlen($uveSection) . " bytes\n";
    }
} else {
    echo "NO UVE markers in cached CSS\n";
}
