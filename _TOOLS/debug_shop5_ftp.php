<?php
// Debug FTP/CSS config for shop 5

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopCssFetcher;

$shop = PrestaShopShop::find(5);

echo "=== SHOP 5 FTP/CSS CONFIG ===\n\n";

echo "url: " . ($shop->url ?? 'NULL') . "\n";
echo "ftp_host: " . ($shop->ftp_host ?? 'NULL') . "\n";
echo "ftp_user: " . ($shop->ftp_user ?? 'NULL') . "\n";
echo "ftp_port: " . ($shop->ftp_port ?? 'NULL') . "\n";
echo "ftp_path: " . ($shop->ftp_path ?? 'NULL') . "\n";
echo "ftp_protocol: " . ($shop->ftp_protocol ?? 'NULL') . "\n";

echo "\n=== css_config ===\n";
print_r($shop->css_config ?? 'NULL');

echo "\n\n=== Checking isCssSyncEnabled via CssFetcher ===\n";
$fetcher = app(PrestaShopCssFetcher::class);
$reflection = new ReflectionClass($fetcher);
$method = $reflection->getMethod('getFtpConfig');
$method->setAccessible(true);
$config = $method->invoke($fetcher, $shop);
echo "getFtpConfig result:\n";
print_r($config);
