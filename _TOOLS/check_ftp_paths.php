<?php
// Check both possible paths
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = App\Models\PrestaShopShop::find(5);
$config = $shop->ftp_config ?? [];

$ftp = @ftp_connect($config['host'], (int)($config['port'] ?? 21), 30);
$password = Illuminate\Support\Facades\Crypt::decryptString($config['password'] ?? '');
@ftp_login($ftp, $config['user'] ?? '', $password);
ftp_pasv($ftp, true);

echo "=== CHECKING BOTH POSSIBLE PATHS ===\n\n";

// Path 1: Direct /themes/ (current)
$path1 = "/themes/warehouse/assets/css/custom.css";
$size1 = @ftp_size($ftp, $path1);
echo "1. {$path1}\n";
echo "   Size: " . ($size1 === -1 ? "NOT FOUND" : "{$size1} bytes") . "\n";

// Content check for path 1
if ($size1 !== -1) {
    $temp = tmpfile();
    $tempPath = stream_get_meta_data($temp)['uri'];
    if (@ftp_get($ftp, $tempPath, $path1, FTP_BINARY)) {
        $content = file_get_contents($tempPath);
        $hasUve = strpos($content, 'uve-styles') !== false;
        echo "   Has UVE markers: " . ($hasUve ? "YES" : "NO") . "\n";
        echo "   First 100 chars: " . substr($content, 0, 100) . "\n";
    }
    fclose($temp);
}

echo "\n";

// Path 2: /public_html/themes/
$path2 = "/public_html/themes/warehouse/assets/css/custom.css";
$size2 = @ftp_size($ftp, $path2);
echo "2. {$path2}\n";
echo "   Size: " . ($size2 === -1 ? "NOT FOUND" : "{$size2} bytes") . "\n";

// Content check for path 2
if ($size2 !== -1) {
    $temp = tmpfile();
    $tempPath = stream_get_meta_data($temp)['uri'];
    if (@ftp_get($ftp, $tempPath, $path2, FTP_BINARY)) {
        $content = file_get_contents($tempPath);
        $hasUve = strpos($content, 'uve-styles') !== false;
        echo "   Has UVE markers: " . ($hasUve ? "YES" : "NO") . "\n";
        echo "   First 100 chars: " . substr($content, 0, 100) . "\n";
    }
    fclose($temp);
}

ftp_close($ftp);
