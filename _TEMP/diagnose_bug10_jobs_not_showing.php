<?php

/**
 * BUG #10 Diagnosis: New sync jobs not showing in Recent Sync Jobs
 *
 * User reports: Lista pokazuje job'y sprzed 4 dni, nowe nie pojawiają się
 *
 * Possible causes:
 * 1. SyncJob not created during dispatch
 * 2. getRecentSyncJobs() not called in render()
 * 3. Livewire component cache
 * 4. wire:poll not working
 * 5. Deployment incomplete
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SyncJob;
use App\Models\PrestaShopShop;
use App\Jobs\PullProductsFromPrestaShop;
use App\Jobs\SyncProductsJob;
use Illuminate\Support\Facades\DB;

echo "=== BUG #10 DIAGNOSIS: Jobs Not Showing ===\n\n";

// 1. Check total sync jobs in database
$totalJobs = SyncJob::count();
echo "📊 Total SyncJobs in database: {$totalJobs}\n\n";

// 2. Check recent jobs (last 7 days)
$recent = SyncJob::where('created_at', '>=', now()->subDays(7))
    ->orderBy('created_at', 'desc')
    ->get();

echo "📅 SyncJobs from last 7 days: {$recent->count()}\n";
if ($recent->count() > 0) {
    echo "\n   Latest 5:\n";
    foreach ($recent->take(5) as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Created: {$job->created_at}\n";
    }
} else {
    echo "   ❌ NO jobs created in last 7 days!\n";
}
echo "\n";

// 3. Check jobs created TODAY
$today = SyncJob::whereDate('created_at', today())->get();
echo "📆 SyncJobs created TODAY: {$today->count()}\n";
if ($today->count() > 0) {
    foreach ($today as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Time: {$job->created_at->format('H:i:s')}\n";
    }
} else {
    echo "   ❌ NO jobs created today!\n";
}
echo "\n";

// 4. Check jobs from 4 days ago (user mentioned "sprzed 4 dni")
$fourDaysAgo = SyncJob::whereDate('created_at', today()->subDays(4))->get();
echo "🕒 SyncJobs from 4 days ago: {$fourDaysAgo->count()}\n";
if ($fourDaysAgo->count() > 0) {
    foreach ($fourDaysAgo->take(3) as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Created: {$job->created_at}\n";
    }
}
echo "\n";

// 5. Simulate getRecentSyncJobs() query WITHOUT filter (as should be after FIX #1)
echo "🔍 Simulating getRecentSyncJobs() query (NO filter):\n";
$simulated = SyncJob::with(['prestashopShop', 'user'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "   Returned: {$simulated->count()} jobs\n";
if ($simulated->count() > 0) {
    echo "\n   Latest 3:\n";
    foreach ($simulated->take(3) as $job) {
        $shopName = $job->prestashopShop ? $job->prestashopShop->name : 'N/A';
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Shop: {$shopName} | Created: {$job->created_at}\n";
    }
}
echo "\n";

// 6. Check if SyncJob is created when dispatching jobs
echo "🧪 Testing SyncJob creation:\n";

$shop = PrestaShopShop::where('is_active', true)->first();
if (!$shop) {
    echo "   ⚠️  No active shops found - cannot test job dispatch\n";
} else {
    echo "   Using shop: {$shop->name} (ID: {$shop->id})\n";

    // Count jobs before dispatch
    $beforeCount = SyncJob::count();
    echo "   SyncJobs before dispatch: {$beforeCount}\n";

    // Dispatch PullProductsFromPrestaShop (import job)
    echo "   Dispatching PullProductsFromPrestaShop...\n";
    try {
        PullProductsFromPrestaShop::dispatch($shop);

        // Count jobs after dispatch
        $afterCount = SyncJob::count();
        echo "   SyncJobs after dispatch: {$afterCount}\n";

        if ($afterCount > $beforeCount) {
            $newJob = SyncJob::latest()->first();
            echo "   ✅ SUCCESS: New SyncJob created!\n";
            echo "      ID: {$newJob->id}\n";
            echo "      Type: {$newJob->job_type}\n";
            echo "      Status: {$newJob->status}\n";
        } else {
            echo "   ❌ FAIL: No SyncJob created during dispatch!\n";
            echo "      This is the ROOT CAUSE of BUG #10!\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR during dispatch: {$e->getMessage()}\n";
    }
}

echo "\n=== END DIAGNOSIS ===\n";
