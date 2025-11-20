// Diagnostic: Quantity/Stock Sync Analysis
//
// Checks:
// 1. Latest completed job - what's in synced_data and changed_fields
// 2. ProductTransformer output - is quantity included in payload
// 3. PrestaShop API request - is quantity sent
//
// Run: php artisan tinker < _TEMP/analyze_quantity_sync.php

use App\Models\SyncJob;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\ProductTransformer;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "==============================================\n";
echo "  QUANTITY/STOCK SYNC ANALYSIS\n";
echo "==============================================\n\n";

// 1. Check latest completed job
echo "[1] LATEST COMPLETED JOB ANALYSIS\n";
echo str_repeat("-", 50) . "\n";

$latestJob = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
    ->where('source_type', SyncJob::TYPE_PPM)
    ->orderBy('completed_at', 'desc')
    ->first();

if (!$latestJob) {
    echo "❌ NO COMPLETED JOBS FOUND\n\n";
    exit;
}

echo "Job ID: {$latestJob->id}\n";
echo "Product ID: {$latestJob->product_id}\n";
echo "Shop ID: {$latestJob->shop_id}\n";
echo "Completed: " . $latestJob->completed_at->format('Y-m-d H:i:s') . "\n";
echo "Duration: {$latestJob->duration_seconds}s\n\n";

// Check result_summary
$resultSummary = $latestJob->result_summary;

if (empty($resultSummary)) {
    echo "❌ result_summary: EMPTY\n\n";
    exit;
}

echo "Result Summary Keys: " . implode(', ', array_keys($resultSummary)) . "\n\n";

// Check synced_data for quantity
if (isset($resultSummary['synced_data'])) {
    $syncedData = $resultSummary['synced_data'];

    echo "SYNCED DATA:\n";
    echo "  Total fields: " . count($syncedData) . "\n";

    // Check for quantity field
    if (isset($syncedData['quantity'])) {
        echo "  ✅ quantity: {$syncedData['quantity']}\n";
    } else {
        echo "  ❌ quantity: NOT PRESENT\n";
    }

    // Check for price fields
    if (isset($syncedData['price (netto)'])) {
        echo "  ✅ price (netto): {$syncedData['price (netto)']}\n";
    }
    if (isset($syncedData['price (brutto)'])) {
        echo "  ✅ price (brutto): {$syncedData['price (brutto)']}\n";
    }

    // Show all synced fields
    echo "\n  All synced fields:\n";
    foreach ($syncedData as $field => $value) {
        if (is_array($value)) {
            echo "    - {$field}: [" . count($value) . " items]\n";
        } else {
            $displayValue = strlen((string)$value) > 50 ? substr((string)$value, 0, 50) . '...' : $value;
            echo "    - {$field}: {$displayValue}\n";
        }
    }
    echo "\n";
} else {
    echo "❌ synced_data: NOT PRESENT\n\n";
}

// Check changed_fields
if (isset($resultSummary['changed_fields'])) {
    $changedFields = $resultSummary['changed_fields'];

    echo "CHANGED FIELDS:\n";
    echo "  Total changes: " . count($changedFields) . "\n\n";

    if (count($changedFields) > 0) {
        foreach ($changedFields as $field => $change) {
            $old = is_array($change['old']) ? json_encode($change['old']) : $change['old'];
            $new = is_array($change['new']) ? json_encode($change['new']) : $change['new'];

            echo "  - {$field}:\n";
            echo "      OLD: {$old}\n";
            echo "      NEW: {$new}\n";
        }
    } else {
        echo "  (No changes detected)\n";
    }
    echo "\n";
} else {
    echo "⚠️  changed_fields: NOT PRESENT\n";
    echo "   (This is normal for first sync or CREATE operation)\n\n";
}

// 2. Test ProductTransformer with this product
echo "[2] PRODUCT TRANSFORMER OUTPUT\n";
echo str_repeat("-", 50) . "\n";

$product = Product::find($latestJob->product_id);
$shop = PrestaShopShop::find($latestJob->shop_id);

if (!$product || !$shop) {
    echo "❌ Product or Shop not found\n\n";
    exit;
}

echo "Product: {$product->sku} - {$product->name}\n";
echo "Shop: {$shop->name}\n\n";

try {
    $transformer = app(ProductTransformer::class);
    $productData = $transformer->toPrestaShop($product, $shop);

    echo "Transformed Product Data:\n";

    // Check if quantity is in payload
    $mainProduct = $productData['product'] ?? [];

    if (isset($mainProduct['quantity'])) {
        echo "  ✅ quantity IN PAYLOAD: {$mainProduct['quantity']}\n";
    } else {
        echo "  ❌ quantity NOT IN PAYLOAD\n";
    }

    // Check other fields
    echo "  reference (SKU): " . ($mainProduct['reference'] ?? 'N/A') . "\n";
    echo "  price (netto): " . ($mainProduct['price'] ?? 'N/A') . "\n";
    echo "  active: " . ($mainProduct['active'] ?? 'N/A') . "\n";
    echo "  ean13: " . ($mainProduct['ean13'] ?? 'N/A') . "\n";

    echo "\n  All product keys in payload:\n";
    foreach (array_keys($mainProduct) as $key) {
        echo "    - {$key}\n";
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ TRANSFORMER ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
}

// 3. Check warehouse stock for this product
echo "[3] WAREHOUSE STOCK CHECK\n";
echo str_repeat("-", 50) . "\n";

try {
    $warehouseMapper = app(\App\Services\PrestaShop\Mappers\WarehouseMapper::class);
    $calculatedStock = $warehouseMapper->calculateStockForShop($product, $shop);

    echo "Calculated stock for shop: {$calculatedStock}\n";

    // Get individual warehouse stocks
    $stocks = $product->stock()->with('warehouse')->get();

    if ($stocks->isEmpty()) {
        echo "⚠️  No warehouse stocks found for this product\n";
    } else {
        echo "\nIndividual warehouse stocks:\n";
        foreach ($stocks as $stock) {
            echo "  - {$stock->warehouse->name}: {$stock->available_quantity}\n";
        }
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ STOCK CALCULATION ERROR: {$e->getMessage()}\n\n";
}

// 4. Summary
echo "[4] DIAGNOSIS SUMMARY\n";
echo str_repeat("-", 50) . "\n";

$hasQuantityInSyncedData = isset($resultSummary['synced_data']['quantity']);
$hasQuantityChange = isset($resultSummary['changed_fields']['quantity']);

if (!$hasQuantityInSyncedData) {
    echo "🔴 CRITICAL: quantity NOT tracked in synced_data\n";
    echo "   → extractTrackableFields() may not be extracting quantity\n";
    echo "   → Check ProductSyncStrategy::extractTrackableFields()\n\n";
} else {
    echo "🟢 quantity IS tracked in synced_data\n\n";
}

if ($hasQuantityChange) {
    echo "🟢 quantity change WAS detected\n\n";
} else {
    echo "🟡 quantity change NOT detected\n";
    echo "   Possible reasons:\n";
    echo "   - Quantity didn't actually change between syncs\n";
    echo "   - This was first sync (no previous baseline)\n";
    echo "   - Stock value is same as previous sync\n\n";
}

echo "RECOMMENDATIONS:\n";
if (!$hasQuantityInSyncedData) {
    echo "1. FIX extractTrackableFields() to include quantity\n";
    echo "2. Ensure quantity is in ProductTransformer output\n";
}
if (!$hasQuantityChange) {
    echo "3. Change product stock and trigger new sync\n";
    echo "4. Verify quantity appears in changed_fields\n";
}

echo "\n";
echo "==============================================\n";
echo "  END OF ANALYSIS\n";
echo "==============================================\n\n";
