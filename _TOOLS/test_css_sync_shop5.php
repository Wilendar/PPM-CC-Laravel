<?php
// Test CSS sync for shop 5 product 11183 with detailed debugging

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\CssSyncOrchestrator;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use Illuminate\Support\Facades\Log;

echo "=== CSS SYNC DEBUG TEST ===\n\n";

// Get description
$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if (!$desc) {
    echo "Description not found!\n";
    exit(1);
}

echo "Description found: ID {$desc->id}\n";
echo "Product: {$desc->product_id}\n";
echo "Shop: {$desc->shop_id}\n";
echo "CSS Mode: " . ($desc->css_mode ?? 'null') . "\n";
echo "CSS Rules count: " . count($desc->css_rules ?? []) . "\n";

// Get shop
$shop = PrestaShopShop::find(5);
if (!$shop) {
    echo "Shop 5 not found!\n";
    exit(1);
}

// Test FTP connection
echo "\n--- FTP CONNECTION TEST ---\n";
$fetcher = app(PrestaShopCssFetcher::class);
$ftpConfig = $shop->ftp_config ?? [];
$ftpTest = $fetcher->testFtpConnection($ftpConfig);
echo "FTP Test Success: " . ($ftpTest['success'] ? 'YES' : 'NO') . "\n";
if (!$ftpTest['success']) {
    echo "FTP Error: " . ($ftpTest['error'] ?? 'unknown') . "\n";
} else {
    echo "Server Info: " . json_encode($ftpTest['server_info']) . "\n";
}

// Test CSS read
echo "\n--- CSS READ TEST ---\n";
$cssResult = $fetcher->getCustomCss($shop);
echo "Read Success: " . ($cssResult['success'] ? 'YES' : 'NO') . "\n";
if ($cssResult['success']) {
    echo "File Path: " . ($cssResult['filePath'] ?? 'null') . "\n";
    echo "Content length: " . strlen($cssResult['content'] ?? '') . " bytes\n";
    // Check for UVE markers
    if (str_contains($cssResult['content'] ?? '', '@uve-styles-start')) {
        echo "UVE markers: FOUND\n";
    } else {
        echo "UVE markers: NOT FOUND\n";
    }
} else {
    echo "Read Error: " . ($cssResult['error'] ?? 'unknown') . "\n";
}

// Run actual sync
echo "\n--- RUNNING CSS SYNC ---\n";
$sync = app(CssSyncOrchestrator::class);

// First validate config
$validation = $sync->validateShopConfig($shop);
echo "Config Valid: " . ($validation['valid'] ? 'YES' : 'NO') . "\n";
if (!$validation['valid']) {
    echo "Validation Issues:\n";
    foreach ($validation['issues'] as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "Has FTP: " . ($validation['has_ftp'] ? 'YES' : 'NO') . "\n";
echo "Has CSS Files: " . ($validation['has_css_files'] ? 'YES' : 'NO') . "\n";

// Now run the actual sync
echo "\nExecuting sync...\n";
$result = $sync->syncProductDescription($desc, true);

echo "\n--- SYNC RESULT ---\n";
echo "Status: " . ($result['status'] ?? 'null') . "\n";
echo "Message: " . ($result['message'] ?? 'null') . "\n";
echo "Error: " . ($result['error'] ?? 'null') . "\n";
echo "Progress: " . ($result['progress'] ?? 0) . "%\n";

if (isset($result['details'])) {
    echo "Details:\n";
    foreach ($result['details'] as $key => $value) {
        if (is_array($value)) {
            echo "  {$key}: " . json_encode($value) . "\n";
        } else {
            echo "  {$key}: {$value}\n";
        }
    }
}

// Verify upload by re-reading
echo "\n--- VERIFY UPLOAD ---\n";
$verifyResult = $fetcher->getCustomCss($shop);
if ($verifyResult['success']) {
    if (str_contains($verifyResult['content'] ?? '', '@uve-styles-start')) {
        echo "SUCCESS! UVE markers NOW FOUND in custom.css\n";
        $start = strpos($verifyResult['content'], '/* @uve-styles-start */');
        $end = strpos($verifyResult['content'], '/* @uve-styles-end */');
        if ($start !== false && $end !== false) {
            $uveSection = substr($verifyResult['content'], $start, min(500, $end - $start));
            echo "UVE CSS Preview (first 500 chars):\n{$uveSection}\n";
        }
    } else {
        echo "FAILED: UVE markers still NOT in custom.css!\n";
    }
} else {
    echo "Verify read failed: " . ($verifyResult['error'] ?? 'unknown') . "\n";
}

// Check shop deploy status
echo "\n--- SHOP DEPLOY STATUS (after sync) ---\n";
$shop->refresh();
echo "css_last_deployed_at: " . ($shop->css_last_deployed_at ?? 'null') . "\n";
echo "css_deploy_status: " . ($shop->css_deploy_status ?? 'null') . "\n";
echo "css_deploy_message: " . ($shop->css_deploy_message ?? 'null') . "\n";
