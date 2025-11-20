<?php
// Check ProductShopData categories for product 11033, shop 1

require 'vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  CHECK: Product 11033 Categories (Shop ID 1)                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$productId = 11033;
$shopId = 1;

// ═══════════════════════════════════════════════════════════════════
// STEP 1: ProductShopData.category_mappings
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 1: ProductShopData.category_mappings ═══\n";

$psd = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$psd) {
    echo "❌ ProductShopData NOT FOUND!\n";
    exit(1);
}

echo "✅ ProductShopData ID: {$psd->id}\n";
echo "   Sync Status: {$psd->sync_status}\n";
echo "   Last Sync: {$psd->last_sync_at}\n";
echo "   Last Pulled: {$psd->last_pulled_at}\n\n";

echo "📦 category_mappings structure:\n";
if ($psd->category_mappings) {
    echo json_encode($psd->category_mappings, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($psd->category_mappings['mappings'])) {
        $ppmPsIds = array_values($psd->category_mappings['mappings']);
        sort($ppmPsIds);
        echo "   PrestaShop IDs (from mappings values): " . implode(', ', $ppmPsIds) . "\n\n";
    }
} else {
    echo "   NULL or empty\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// STEP 2: Job ID 5277 Payload
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Job ID 5277 Payload ═══\n";

// Check jobs table
$job = DB::table('jobs')->where('id', 5277)->first();

if ($job) {
    echo "✅ Job found in 'jobs' table\n";
    echo "   Queue: {$job->queue}\n";
    echo "   Attempts: {$job->attempts}\n";
    echo "   Reserved: {$job->reserved_at}\n";
    echo "   Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n\n";

    // Decode payload
    $payload = json_decode($job->payload, true);

    if (isset($payload['data']['command'])) {
        // Unserialize Laravel job command
        $command = unserialize($payload['data']['command']);

        echo "📋 Job Class: " . get_class($command) . "\n\n";

        // Try to extract product data if available
        if (method_exists($command, 'getProductData')) {
            $productData = $command->getProductData();
            echo "📦 Product Data from Job:\n";
            print_r($productData);
        } else {
            echo "⚠️  Cannot extract product data (method not available)\n";
            echo "   Raw command properties:\n";

            // Use reflection to access properties
            $reflection = new \ReflectionClass($command);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($command);

                if ($property->getName() === 'product' && is_object($value)) {
                    echo "   product->id: {$value->id}\n";
                    echo "   product->sku: {$value->sku}\n";
                    echo "   product->name: {$value->name}\n";
                } elseif ($property->getName() === 'shop' && is_object($value)) {
                    echo "   shop->id: {$value->id}\n";
                    echo "   shop->name: {$value->name}\n";
                } elseif (!is_object($value) || method_exists($value, '__toString')) {
                    echo "   {$property->getName()}: {$value}\n";
                }
            }
        }
    }
} else {
    echo "❌ Job ID 5277 NOT found in 'jobs' table\n";
    echo "   (Job may have already been processed and removed)\n\n";
}

// Check sync_jobs table (if exists)
if (DB::getSchemaBuilder()->hasTable('sync_jobs')) {
    echo "\n═══ Checking 'sync_jobs' table ═══\n";

    $syncJob = DB::table('sync_jobs')->where('id', 5277)->first();

    if ($syncJob) {
        echo "✅ SyncJob found\n";
        echo "   Product ID: {$syncJob->product_id}\n";
        echo "   Shop ID: {$syncJob->shop_id}\n";
        echo "   Type: {$syncJob->sync_type}\n";
        echo "   Status: {$syncJob->status}\n";
        echo "   Started: {$syncJob->started_at}\n";
        echo "   Completed: {$syncJob->completed_at}\n\n";

        if ($syncJob->payload) {
            echo "📦 Payload:\n";
            $payload = json_decode($syncJob->payload, true);

            if (isset($payload['categories'])) {
                echo "   Categories sent: " . implode(', ', $payload['categories']) . "\n\n";
            } else {
                echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
            }
        }

        if ($syncJob->response_data) {
            echo "📬 Response:\n";
            echo json_encode(json_decode($syncJob->response_data, true), JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "❌ SyncJob ID 5277 NOT found\n\n";
    }
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: PrestaShop Current State (via API)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: PrestaShop Current State (via API) ═══\n";

try {
    $shop = PrestaShopShop::find($shopId);
    $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

    $psProduct = $client->getProduct($psd->prestashop_product_id);

    if (isset($psProduct['product'])) {
        $psProduct = $psProduct['product'];
    }

    $psCategories = $psProduct['associations']['categories'] ?? [];
    if (isset($psCategories['category'])) {
        $psCategories = $psCategories['category'];
    }

    $psCategoryIds = [];
    foreach ($psCategories as $cat) {
        if (isset($cat['id'])) {
            $psCategoryIds[] = (int) $cat['id'];
        }
    }
    sort($psCategoryIds);

    echo "✅ PrestaShop Product ID: {$psd->prestashop_product_id}\n";
    echo "   Categories NOW: " . implode(', ', $psCategoryIds) . "\n\n";

} catch (\Exception $e) {
    echo "❌ Error fetching from PrestaShop: {$e->getMessage()}\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  CHECK COMPLETE                                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
