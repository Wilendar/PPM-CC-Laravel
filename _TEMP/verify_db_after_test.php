<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   DATABASE VERIFICATION AFTER TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$productId = 11034;
$shopId = 1;

// 1. Check product_shop_data.category_mappings
echo "1️⃣ CHECK PRODUCT_SHOP_DATA.CATEGORY_MAPPINGS:\n\n";

$psd = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$psd) {
    echo "   ❌ Product shop data NOT FOUND (product_id={$productId}, shop_id={$shopId})\n\n";
} else {
    echo "   ✅ Product shop data exists\n";
    echo "   Record ID: {$psd->id}\n";
    echo "   Updated at: {$psd->updated_at}\n\n";

    if ($psd->category_mappings) {
        echo "   category_mappings (raw JSON):\n";
        echo "   " . str_repeat('─', 60) . "\n";
        $mappingsFormatted = json_encode(json_decode($psd->category_mappings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "   " . str_replace("\n", "\n   ", $mappingsFormatted) . "\n";
        echo "   " . str_repeat('─', 60) . "\n\n";

        // Parse and analyze
        $mappings = json_decode($psd->category_mappings, true);

        if (isset($mappings['ui']['selected'])) {
            echo "   📊 ANALYSIS:\n";
            echo "   Selected PPM IDs: " . implode(', ', $mappings['ui']['selected']) . "\n";
            echo "   Primary PPM ID: " . ($mappings['ui']['primary'] ?? 'NULL') . "\n\n";

            echo "   Mappings (PPM → PrestaShop):\n";
            foreach ($mappings['mappings'] as $ppmId => $psId) {
                // Get category name from PPM
                $category = DB::table('categories')->where('id', $ppmId)->first();
                $categoryName = $category ? $category->name : 'Unknown';
                echo "   • PPM {$ppmId} ({$categoryName}) → PS {$psId}\n";
            }
            echo "\n";

            // Check if PITGANG (PPM 41, PS 12) is present
            if (in_array(41, $mappings['ui']['selected']) && isset($mappings['mappings']['41']) && $mappings['mappings']['41'] == 12) {
                echo "   ✅ SUCCESS: PITGANG (PPM 41 → PS 12) is saved!\n";
            } else {
                echo "   ❌ FAIL: PITGANG (PPM 41 → PS 12) NOT FOUND in mappings\n";
            }

            // Check auto-injected roots
            if (in_array(1, $mappings['ui']['selected']) && in_array(36, $mappings['ui']['selected'])) {
                echo "   ✅ SUCCESS: Auto-injected roots (PPM 1 + 36) present\n";
            } else {
                echo "   ❌ FAIL: Auto-injected roots NOT PRESENT\n";
            }
            echo "\n";

            // Metadata
            if (isset($mappings['metadata'])) {
                echo "   Metadata:\n";
                echo "   • Last updated: " . ($mappings['metadata']['last_updated'] ?? 'N/A') . "\n";
                echo "   • Source: " . ($mappings['metadata']['source'] ?? 'N/A') . "\n";
                echo "\n";
            }
        } else {
            echo "   ⚠️  Invalid structure - missing 'ui.selected'\n\n";
        }
    } else {
        echo "   ❌ category_mappings is NULL or empty\n\n";
    }
}

// 2. Check sync_jobs table
echo "2️⃣ CHECK SYNC_JOBS (last 5 jobs for product 11034):\n\n";

$syncJobs = DB::table('sync_jobs')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'status', 'fields_to_sync', 'created_at', 'updated_at']);

if ($syncJobs->isEmpty()) {
    echo "   ❌ NO SYNC JOBS FOUND for product {$productId} + shop {$shopId}\n\n";
} else {
    echo "   ✅ Found {$syncJobs->count()} sync jobs:\n\n";

    foreach ($syncJobs as $job) {
        $fieldsToSync = json_decode($job->fields_to_sync, true);
        $fieldsStr = is_array($fieldsToSync) ? implode(', ', $fieldsToSync) : $job->fields_to_sync;

        echo "   Job ID {$job->id}:\n";
        echo "   • Status: {$job->status}\n";
        echo "   • Fields to sync: {$fieldsStr}\n";
        echo "   • Created: {$job->created_at}\n";
        echo "   • Updated: {$job->updated_at}\n";

        // Check if categories field is present
        if (is_array($fieldsToSync) && in_array('categories', $fieldsToSync)) {
            echo "   ✅ Contains 'categories' field\n";
        } else {
            echo "   ⚠️  Does NOT contain 'categories' field\n";
        }
        echo "\n";
    }
}

// 3. Check product_categories table (should NOT contain PrestaShop IDs)
echo "3️⃣ CHECK PRODUCT_CATEGORIES (verify no PrestaShop IDs):\n\n";

$productCategories = DB::table('product_categories')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->orderBy('category_id')
    ->get(['product_id', 'category_id', 'shop_id']);

if ($productCategories->isEmpty()) {
    echo "   ℹ️  No records in product_categories (expected - categories in product_shop_data.category_mappings)\n\n";
} else {
    echo "   Found {$productCategories->count()} records:\n\n";

    foreach ($productCategories as $pc) {
        // Check if this is a PPM category or PrestaShop ID
        $category = DB::table('categories')->where('id', $pc->category_id)->first();

        if ($category) {
            echo "   ✅ Category ID {$pc->category_id}: {$category->name} (VALID PPM category)\n";
        } else {
            echo "   ❌ Category ID {$pc->category_id}: NOT FOUND in categories table (FOREIGN KEY VIOLATION!)\n";
        }
    }
    echo "\n";
}

// 4. Check Laravel logs for errors
echo "4️⃣ CHECK RECENT LARAVEL LOGS (last 50 lines with ETAP_07b):\n\n";

exec('tail -50 ' . escapeshellarg(storage_path('logs/laravel.log')) . ' | grep "ETAP_07b"', $output, $returnCode);

if (empty($output)) {
    echo "   ℹ️  No ETAP_07b logs in last 50 lines\n\n";
} else {
    echo "   Recent ETAP_07b logs:\n\n";
    foreach ($output as $line) {
        echo "   " . $line . "\n";
    }
    echo "\n";
}

// 5. Final verdict
echo "5️⃣ FINAL VERDICT:\n\n";

$categoryMappingsValid = $psd && $psd->category_mappings && strpos($psd->category_mappings, '"41":12') !== false;
$rootsPresent = $psd && $psd->category_mappings && strpos($psd->category_mappings, '"1":1') !== false && strpos($psd->category_mappings, '"36":2') !== false;
$syncJobCreated = !$syncJobs->isEmpty();
$noCategoryTableRecords = $productCategories->isEmpty();

echo "   Category mappings saved: " . ($categoryMappingsValid ? '✅ YES' : '❌ NO') . "\n";
echo "   Auto-injected roots present: " . ($rootsPresent ? '✅ YES' : '❌ NO') . "\n";
echo "   Sync job created: " . ($syncJobCreated ? '✅ YES' : '❌ NO') . "\n";
echo "   No foreign key violations: " . ($noCategoryTableRecords ? '✅ YES' : '⚠️  CHECK NEEDED') . "\n\n";

if ($categoryMappingsValid && $rootsPresent && $syncJobCreated && $noCategoryTableRecords) {
    echo "   🎉 ALL CHECKS PASSED - FIX IS WORKING!\n\n";
} else {
    echo "   ⚠️  SOME CHECKS FAILED - INVESTIGATION NEEDED\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n\n";
