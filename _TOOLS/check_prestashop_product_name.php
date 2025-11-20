<?php

require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;

$shop = PrestaShopShop::find(1);
$prestashopId = 9764;

echo "=== PRESTASHOP API FETCH ===\n\n";
echo "Shop: {$shop->name}\n";
echo "PrestaShop Product ID: {$prestashopId}\n\n";

try {
    $client = new PrestaShop8Client($shop);
    $productData = $client->getProduct($prestashopId);

    echo "=== RAW API RESPONSE ===\n";
    echo json_encode($productData, JSON_PRETTY_PRINT) . "\n\n";

    // Try different paths for name
    $name = $productData['name']
        ?? $productData['product']['name']
        ?? $productData['product']['name'][0]['value']
        ?? 'N/A';

    echo "PrestaShop Product Name: {$name}\n\n";
    echo "Expected (PPM default): [ZMIANA] Test Auto-Fix Required Fields 1762422647\n";
    echo "Actual (PrestaShop): {$name}\n\n";

    if (strpos((string) $name, "[ZMIANA]") === false) {
        echo "✅ CONFIRMED: PrestaShop has OLD name (without '[ZMIANA]' prefix)\n";
        echo "   PPM UI shows inherited default name = creates ILLUSION of match\n";
        echo "   But PrestaShop has DIFFERENT data!\n\n";
    } else {
        echo "❌ PrestaShop has NEW name (with '[ZMIANA]')\n";
        echo "   This means sync actually worked?\n\n";
    }
} catch (\Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
