<?php
/**
 * ETAP_07h: Sync CSS to PrestaShop
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;
use Illuminate\Support\Facades\Log;

$productId = $argv[1] ?? 11183;
$shopId = $argv[2] ?? 5;

echo "=== SYNC CSS TO PRESTASHOP ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

echo "css_mode: " . ($desc->css_mode ?? 'N/A') . "\n";
echo "css_rules count: " . count($desc->css_rules ?? []) . "\n";
echo "css_class_map count: " . count($desc->css_class_map ?? []) . "\n";
echo "css_synced_at: " . ($desc->css_synced_at ?? 'NEVER') . "\n\n";

// Dispatch CSS sync via orchestrator
echo "Running CSS sync...\n";
try {
    $orchestrator = app(CssSyncOrchestrator::class);
    $result = $orchestrator->syncProductDescription($desc);

    echo "Sync result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // Refresh and show new synced_at
    $desc->refresh();
    echo "New css_synced_at: " . ($desc->css_synced_at ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "Sync failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nDone!\n";
