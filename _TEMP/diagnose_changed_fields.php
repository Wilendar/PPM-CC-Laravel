<?php
/**
 * Diagnostic Script: Changed Fields Not Displaying Issue
 *
 * Checks:
 * 1. Latest completed sync jobs and their result_summary structure
 * 2. Whether synced_data and changed_fields are present
 * 3. Previous sync availability for comparison
 *
 * Run: php artisan tinker < _TEMP/diagnose_changed_fields.php
 */

use App\Models\SyncJob;
use App\Models\ProductShopData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "==============================================\n";
echo "  DIAGNOSIS: Changed Fields Not Displaying\n";
echo "==============================================\n\n";

// 1. Get latest completed sync jobs (last 5)
echo "[1] LATEST COMPLETED SYNC JOBS\n";
echo str_repeat("-", 50) . "\n";

$latestJobs = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
    ->where('source_type', SyncJob::TYPE_PPM)
    ->orderBy('completed_at', 'desc')
    ->limit(5)
    ->get();

if ($latestJobs->isEmpty()) {
    echo "❌ NO COMPLETED JOBS FOUND\n";
    echo "   → This explains why changed fields don't show\n";
    echo "   → Need at least one completed sync for baseline\n\n";
} else {
    echo "✅ Found {$latestJobs->count()} completed jobs\n\n";

    foreach ($latestJobs as $index => $job) {
        echo "Job #" . ($index + 1) . " (ID: {$job->id})\n";
        echo "  Product ID: {$job->product_id}\n";
        echo "  Shop ID: {$job->shop_id}\n";
        echo "  Completed: " . $job->completed_at->format('Y-m-d H:i:s') . "\n";
        echo "  Duration: {$job->duration_seconds}s\n";

        // Check result_summary structure
        $resultSummary = $job->result_summary;

        if (empty($resultSummary)) {
            echo "  ❌ result_summary: EMPTY\n";
        } else {
            echo "  ✅ result_summary: EXISTS\n";

            // Check for synced_data
            if (isset($resultSummary['synced_data'])) {
                $fieldCount = count($resultSummary['synced_data']);
                echo "     ✅ synced_data: {$fieldCount} fields tracked\n";

                // Show some tracked fields
                $sampleFields = array_keys(array_slice($resultSummary['synced_data'], 0, 5));
                echo "        Sample fields: " . implode(', ', $sampleFields) . "\n";
            } else {
                echo "     ❌ synced_data: MISSING\n";
                echo "        → This prevents future change detection\n";
            }

            // Check for changed_fields
            if (isset($resultSummary['changed_fields'])) {
                $changeCount = count($resultSummary['changed_fields']);
                echo "     ✅ changed_fields: {$changeCount} changes detected\n";

                if ($changeCount > 0) {
                    echo "        Changes:\n";
                    foreach ($resultSummary['changed_fields'] as $field => $change) {
                        $old = is_array($change['old']) ? json_encode($change['old']) : $change['old'];
                        $new = is_array($change['new']) ? json_encode($change['new']) : $change['new'];
                        echo "          - {$field}: {$old} → {$new}\n";
                    }
                }
            } else {
                echo "     ⚠️  changed_fields: NOT PRESENT\n";
                echo "        → Could be first sync OR no changes detected\n";
            }

            // Show other result_summary keys
            $otherKeys = array_diff(array_keys($resultSummary), ['synced_data', 'changed_fields']);
            if (!empty($otherKeys)) {
                echo "     Other keys: " . implode(', ', $otherKeys) . "\n";
            }
        }

        echo "\n";
    }
}

// 2. Check if products have previous sync baseline
echo "[2] PREVIOUS SYNC AVAILABILITY CHECK\n";
echo str_repeat("-", 50) . "\n";

// Get a product that was recently synced
$recentProduct = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
    ->where('source_type', SyncJob::TYPE_PPM)
    ->orderBy('completed_at', 'desc')
    ->first();

if ($recentProduct) {
    $productId = $recentProduct->product_id;
    $shopId = $recentProduct->shop_id;

    echo "Testing with Product ID: {$productId}, Shop ID: {$shopId}\n\n";

    // Count how many completed syncs exist for this product+shop
    $syncCount = SyncJob::where('product_id', $productId)
        ->where('shop_id', $shopId)
        ->where('source_type', SyncJob::TYPE_PPM)
        ->where('status', SyncJob::STATUS_COMPLETED)
        ->whereNotNull('result_summary->synced_data')
        ->count();

    echo "Completed syncs with synced_data: {$syncCount}\n";

    if ($syncCount === 0) {
        echo "❌ NO PREVIOUS BASELINE\n";
        echo "   → First sync after tracking implementation\n";
        echo "   → Changed fields will start showing on NEXT sync\n\n";
    } elseif ($syncCount === 1) {
        echo "⚠️  ONLY ONE SYNC WITH BASELINE\n";
        echo "   → Changed fields will show on NEXT sync\n";
        echo "   → Current sync is the baseline for future comparisons\n\n";
    } else {
        echo "✅ MULTIPLE SYNCS WITH BASELINE ({$syncCount})\n";
        echo "   → Changed fields SHOULD be working\n\n";

        // Get the two most recent to show comparison
        $twoRecent = SyncJob::where('product_id', $productId)
            ->where('shop_id', $shopId)
            ->where('source_type', SyncJob::TYPE_PPM)
            ->where('status', SyncJob::STATUS_COMPLETED)
            ->whereNotNull('result_summary->synced_data')
            ->orderBy('completed_at', 'desc')
            ->limit(2)
            ->get();

        if ($twoRecent->count() === 2) {
            $latest = $twoRecent[0];
            $previous = $twoRecent[1];

            echo "Comparing two most recent syncs:\n";
            echo "  Previous: " . $previous->completed_at->format('Y-m-d H:i:s') . "\n";
            echo "  Latest:   " . $latest->completed_at->format('Y-m-d H:i:s') . "\n\n";

            // Compare price fields
            $prevPrice = $previous->result_summary['synced_data']['price (netto)'] ?? null;
            $latestPrice = $latest->result_summary['synced_data']['price (netto)'] ?? null;

            echo "  Price (netto) comparison:\n";
            echo "    Previous: " . ($prevPrice ?? 'NULL') . "\n";
            echo "    Latest:   " . ($latestPrice ?? 'NULL') . "\n";

            if ($prevPrice !== $latestPrice) {
                echo "    ✅ PRICE CHANGED - should be tracked\n";
            } else {
                echo "    → No price change detected\n";
            }

            // Check if latest has price (brutto)
            $latestBrutto = $latest->result_summary['synced_data']['price (brutto)'] ?? null;
            echo "\n  Price (brutto) in latest:\n";
            echo "    Latest:   " . ($latestBrutto ?? 'NULL') . "\n";

            if ($latestBrutto === null) {
                echo "    ❌ BRUTTO PRICE NOT TRACKED\n";
                echo "       → BUG #13 fix may not be applied properly\n";
            }
        }
    }
} else {
    echo "❌ NO COMPLETED JOBS TO ANALYZE\n\n";
}

// 3. Check ProductShopData for pending products
echo "[3] PENDING SYNC STATUS CHECK\n";
echo str_repeat("-", 50) . "\n";

$pendingCount = ProductShopData::where('sync_status', 'pending')->count();
echo "Products with pending sync: {$pendingCount}\n";

if ($pendingCount > 0) {
    echo "✅ Pending products available for sync\n";
    echo "   → Trigger sync to test changed fields detection\n\n";

    // Show one pending product
    $pending = ProductShopData::where('sync_status', 'pending')
        ->with('product', 'shop')
        ->first();

    if ($pending) {
        echo "Sample pending product:\n";
        echo "  Product ID: {$pending->product_id} (SKU: " . ($pending->product->sku ?? 'N/A') . ")\n";
        echo "  Shop ID: {$pending->shop_id} (" . ($pending->shop->name ?? 'N/A') . ")\n";
        echo "  Last Modified: " . ($pending->updated_at ? $pending->updated_at->format('Y-m-d H:i:s') : 'N/A') . "\n\n";
    }
} else {
    echo "⚠️  NO PENDING PRODUCTS\n";
    echo "   → Change a product price to trigger sync\n\n";
}

// 4. Summary and Recommendations
echo "[4] DIAGNOSIS SUMMARY\n";
echo str_repeat("-", 50) . "\n";

$completedWithData = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
    ->where('source_type', SyncJob::TYPE_PPM)
    ->whereNotNull('result_summary->synced_data')
    ->count();

$completedWithChanges = SyncJob::where('status', SyncJob::STATUS_COMPLETED)
    ->where('source_type', SyncJob::TYPE_PPM)
    ->whereNotNull('result_summary->changed_fields')
    ->count();

echo "Total completed syncs: " . SyncJob::where('status', SyncJob::STATUS_COMPLETED)->count() . "\n";
echo "  With synced_data: {$completedWithData}\n";
echo "  With changed_fields: {$completedWithChanges}\n\n";

if ($completedWithData === 0) {
    echo "🔴 ROOT CAUSE: No syncs with synced_data baseline\n";
    echo "   SOLUTION: Trigger first sync to establish baseline\n";
} elseif ($completedWithChanges === 0) {
    echo "🟡 LIKELY CAUSE: All syncs are baselines (first sync per product)\n";
    echo "   SOLUTION: Trigger SECOND sync after changing product data\n";
} else {
    echo "🟢 TRACKING IS WORKING: {$completedWithChanges} syncs have detected changes\n";
    echo "   CHECK: UI rendering in sync-controller.blade.php\n";
}

echo "\n";
echo "==============================================\n";
echo "  END OF DIAGNOSIS\n";
echo "==============================================\n\n";
