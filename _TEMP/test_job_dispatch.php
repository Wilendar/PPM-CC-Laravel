<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;

echo "\n=== TEST JOB DISPATCH ===\n\n";

// Count BEFORE
$jobsBefore = DB::table('jobs')->count();
echo "[BEFORE] Jobs in queue: $jobsBefore\n\n";

// Find product and shop
$product = Product::find(11017); // TEST-AUTOFIX-1762422508
$shop = PrestaShopShop::find(1);  // B2B Test DEV

if (!$product) {
    echo "❌ Product 11017 not found!\n";
    exit;
}

if (!$shop) {
    echo "❌ Shop 1 not found!\n";
    exit;
}

echo "Product: {$product->sku} (ID: {$product->id})\n";
echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "Shop Status: {$shop->connection_status}\n";
echo "Shop Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n\n";

// Dispatch job
echo "Dispatching SyncProductToPrestaShop job...\n";

try {
    SyncProductToPrestaShop::dispatch($product, $shop, 8); // user_id = 8 (admin)
    echo "✅ dispatch() completed without errors\n\n";
} catch (\Exception $e) {
    echo "❌ dispatch() FAILED: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit;
}

// Wait a moment
sleep(1);

// Count AFTER
$jobsAfter = DB::table('jobs')->count();
echo "[AFTER] Jobs in queue: $jobsAfter\n\n";

// Result
$difference = $jobsAfter - $jobsBefore;

if ($difference > 0) {
    echo "✅ SUCCESS: $difference job(s) added to queue!\n";

    // Show latest job
    $latestJob = DB::table('jobs')
        ->orderBy('id', 'desc')
        ->first();

    if ($latestJob) {
        $payload = json_decode($latestJob->payload, true);
        echo "\nLatest job:\n";
        echo "  ID: {$latestJob->id}\n";
        echo "  Queue: {$latestJob->queue}\n";
        echo "  Available at: " . date('Y-m-d H:i:s', $latestJob->available_at) . "\n";
        echo "  Display name: " . ($payload['displayName'] ?? 'unknown') . "\n";
    }
} else {
    echo "❌ FAILED: No jobs added to queue!\n";
    echo "⚠️ PROBLEM: dispatch() runs but doesn't create job record\n";
    echo "\n";
    echo "DIAGNOSTIC INFO:\n";
    echo "  Queue connection: " . config('queue.default') . "\n";
    echo "  Queue driver: " . config('queue.connections.database.driver') . "\n";
    echo "  Jobs table: " . config('queue.connections.database.table') . "\n";
}

echo "\n";
