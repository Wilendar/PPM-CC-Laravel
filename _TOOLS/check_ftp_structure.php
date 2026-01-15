<?php
// Check FTP structure for shop 5
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = App\Models\PrestaShopShop::find(5);
$config = $shop->ftp_config ?? [];

echo "FTP Host: " . ($config['host'] ?? 'N/A') . "\n";
echo "FTP User: " . ($config['user'] ?? 'N/A') . "\n";
echo "FTP Port: " . ($config['port'] ?? 21) . "\n\n";

if (empty($config['host'])) {
    die("FTP not configured\n");
}

// Connect to FTP
$ftp = @ftp_connect($config['host'], (int)($config['port'] ?? 21), 30);
if (!$ftp) {
    die("Cannot connect to FTP\n");
}

// Decrypt password
$password = $config['password'] ?? '';
try {
    $password = Illuminate\Support\Facades\Crypt::decryptString($password);
} catch (Exception $e) {
    // Not encrypted
}

if (!@ftp_login($ftp, $config['user'] ?? '', $password)) {
    die("FTP login failed\n");
}

ftp_pasv($ftp, true);

// Get current directory (FTP root for user)
$root = ftp_pwd($ftp);
echo "FTP Root: {$root}\n\n";

// List root
echo "=== FTP ROOT CONTENTS ===\n";
$list = ftp_nlist($ftp, $root);
foreach ($list as $item) {
    echo "  " . basename($item) . "\n";
}
echo "\n";

// Check if themes dir exists
$themesPath = "/themes";
echo "Checking {$themesPath}...\n";
if (@ftp_chdir($ftp, $themesPath)) {
    echo "themes/ directory EXISTS\n";
    $themes = ftp_nlist($ftp, ".");
    foreach ($themes as $theme) {
        echo "  - " . basename($theme) . "\n";
    }
} else {
    echo "themes/ directory NOT FOUND at root\n";
}

// Check if custom.css exists
$cssPath = "/themes/warehouse/assets/css/custom.css";
echo "\nChecking {$cssPath}...\n";
$size = @ftp_size($ftp, $cssPath);
if ($size !== -1) {
    echo "File EXISTS, size: {$size} bytes\n";
} else {
    echo "File DOES NOT EXIST\n";
}

ftp_close($ftp);
