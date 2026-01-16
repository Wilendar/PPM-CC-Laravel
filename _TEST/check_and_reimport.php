<?php
/**
 * Check product 11217 and re-import with variants
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ERPConnection;
use App\Services\ERP\BaselinkerService;

echo "=== Checking Product 11217 ===\n";

$product = Product::find(11217);
if ($product) {
    echo "Product found!\n";
    echo "  SKU: {$product->sku}\n";
    echo "  Name: {$product->name}\n";
    echo "  is_variant_master: " . ($product->is_variant_master ? 'YES' : 'NO') . "\n";
    echo "  Variants count: " . $product->variants()->count() . "\n";

    if ($product->variants()->count() > 0) {
        echo "\nExisting variants:\n";
        foreach ($product->variants as $v) {
            echo "  - {$v->sku}: {$v->name}\n";
        }
    }
} else {
    echo "Product 11217 NOT found!\n";
}

echo "\n=== Re-importing product 323429561 from Baselinker ===\n";

$connection = ERPConnection::find(1);
if (!$connection) {
    echo "ERPConnection not found!\n";
    exit(1);
}

echo "Connection: {$connection->instance_name}\n";

$service = new BaselinkerService();

try {
    $result = $service->syncProductFromERP($connection, '323429561');

    if ($result['success']) {
        echo "\nSUCCESS: {$result['message']}\n";
        $imported = $result['product'];
        echo "  Product ID: {$imported->id}\n";
        echo "  SKU: {$imported->sku}\n";
        echo "  Name: {$imported->name}\n";
        echo "  is_variant_master: " . ($imported->is_variant_master ? 'YES' : 'NO') . "\n";

        // Reload to get fresh variant count
        $imported->refresh();
        $variantsCount = $imported->variants()->count();
        echo "  Variants count: {$variantsCount}\n";

        if ($variantsCount > 0) {
            echo "\nImported variants:\n";
            foreach ($imported->variants as $v) {
                echo "  - {$v->sku}: {$v->name} (position: {$v->position})\n";
            }
        }

        echo "\nMedia count: " . $imported->media()->count() . "\n";
    } else {
        echo "\nFAILED: {$result['message']}\n";
    }
} catch (\Exception $e) {
    echo "\nEXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
