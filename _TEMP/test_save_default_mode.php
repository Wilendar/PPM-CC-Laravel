<?php
/**
 * Test Save Default Mode - Verify NO sync jobs dispatched
 *
 * BUGFIX 2025-11-06: Test that saving in default mode does NOT dispatch sync jobs
 *
 * Test scenario:
 * 1. Find existing product
 * 2. Update name in default mode (activeShopId = null)
 * 3. Check logs for "NO sync job dispatched"
 * 4. Verify no jobs in queue
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

// Test configuration
$testProductSku = 'TEST-SYNC-1762328754'; // Use existing product

echo "\n=== TEST: Save Default Mode - NO Sync Jobs ===\n\n";

// Find product
$product = Product::where('sku', $testProductSku)->first();

if (!$product) {
    echo "❌ Product not found: {$testProductSku}\n";
    echo "Please create test product first or change SKU\n";
    exit(1);
}

echo "✓ Found product: {$product->name} (ID: {$product->id})\n";
echo "  Current name: {$product->name}\n\n";

// Count jobs BEFORE save
$jobsBeforeCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
echo "📊 Jobs in queue BEFORE: {$jobsBeforeCount}\n\n";

// Update product in DEFAULT MODE (simulate activeShopId = null)
$originalName = $product->name;
$newName = $originalName . ' [UPDATED ' . date('H:i:s') . ']';

echo "🔧 Updating product name...\n";
echo "  FROM: {$originalName}\n";
echo "  TO:   {$newName}\n\n";

Log::info('TEST: Starting save default mode test', [
    'product_id' => $product->id,
    'sku' => $product->sku,
]);

// Update product (simulating ProductFormSaver::saveDefaultMode)
$product->update([
    'name' => $newName,
]);

echo "✓ Product updated successfully\n\n";

// Count jobs AFTER save
$jobsAfterCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
echo "📊 Jobs in queue AFTER: {$jobsAfterCount}\n";

$newJobs = $jobsAfterCount - $jobsBeforeCount;
if ($newJobs > 0) {
    echo "❌ FAIL: {$newJobs} NEW jobs were dispatched!\n";
    echo "   This should NOT happen in default mode!\n\n";

    // Show new jobs
    $jobs = \Illuminate\Support\Facades\DB::table('jobs')
        ->orderBy('id', 'desc')
        ->limit($newJobs)
        ->get();

    echo "📋 New jobs:\n";
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "  - ID: {$job->id}, Queue: {$job->queue}, Job: {$displayName}\n";
    }
} else {
    echo "✅ PASS: NO new jobs dispatched (as expected)\n";
}

echo "\n📝 Check logs for:\n";
echo "  - 'Saved default data (local only, NO sync job dispatched)'\n";
echo "  - 'saveDefaultMode() completed - NO sync jobs dispatched'\n\n";

// Revert name change
$product->update(['name' => $originalName]);
echo "↩️  Reverted product name to original\n\n";

echo "=== TEST COMPLETED ===\n\n";
