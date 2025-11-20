<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SyncJob;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;

echo "=== BUG #9 DIAGNOSIS: Sync Jobs Display ===\n\n";

// 1. Check total sync jobs
$totalJobs = SyncJob::count();
echo "📊 Total SyncJobs in database: {$totalJobs}\n\n";

// 2. Check recent sync jobs (last 7 days)
$recentJobs = SyncJob::where('created_at', '>=', now()->subDays(7))
                     ->orderBy('created_at', 'desc')
                     ->get();

echo "📅 Recent SyncJobs (last 7 days): {$recentJobs->count()}\n";
if ($recentJobs->count() > 0) {
    echo "\n   Latest 5:\n";
    foreach ($recentJobs->take(5) as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Created: {$job->created_at}\n";
    }
} else {
    echo "   ⚠️  NO sync jobs created in last 7 days!\n";
}
echo "\n";

// 3. Check jobs from 4 days ago (user mentioned "sprzed 4 dni")
$fourDaysAgo = SyncJob::where('created_at', '>=', now()->subDays(5))
                      ->where('created_at', '<=', now()->subDays(3))
                      ->orderBy('created_at', 'desc')
                      ->get();

echo "🕒 SyncJobs from 4 days ago: {$fourDaysAgo->count()}\n";
if ($fourDaysAgo->count() > 0) {
    foreach ($fourDaysAgo->take(3) as $job) {
        echo "   • ID: {$job->id} | Created: {$job->created_at}\n";
    }
}
echo "\n";

// 4. Check if any jobs were created today
$todayJobs = SyncJob::whereDate('created_at', today())->get();
echo "📆 SyncJobs created TODAY: {$todayJobs->count()}\n";
if ($todayJobs->count() > 0) {
    foreach ($todayJobs as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Time: {$job->created_at->format('H:i:s')}\n";
    }
} else {
    echo "   ⚠️  NO sync jobs created today!\n";
}
echo "\n";

// 5. Check status distribution
$statusCounts = SyncJob::select('status', DB::raw('count(*) as count'))
                       ->groupBy('status')
                       ->get();

echo "📊 Status Distribution:\n";
foreach ($statusCounts as $stat) {
    echo "   • {$stat->status}: {$stat->count}\n";
}
echo "\n";

// 6. Check job_type distribution
$typeCounts = SyncJob::select('job_type', DB::raw('count(*) as count'))
                     ->groupBy('job_type')
                     ->get();

echo "📊 Job Type Distribution:\n";
foreach ($typeCounts as $type) {
    echo "   • {$type->job_type}: {$type->count}\n";
}
echo "\n";

// 7. Check shops with sync jobs
$shops = PrestaShopShop::withCount('syncJobs')->get();
echo "🏪 Shops with SyncJobs:\n";
foreach ($shops as $shop) {
    echo "   • {$shop->name} (ID: {$shop->id}): {$shop->sync_jobs_count} jobs\n";
}
echo "\n";

// 8. Check getRecentSyncJobs() query simulation
echo "🔍 Simulating getRecentSyncJobs() query:\n";
$recentSyncJobs = SyncJob::with(['prestashopShop', 'user'])
                         ->where('job_type', SyncJob::JOB_PRODUCT_SYNC)
                         ->latest()
                         ->take(10)
                         ->get();

echo "   Query returned: {$recentSyncJobs->count()} jobs\n";
if ($recentSyncJobs->count() > 0) {
    echo "\n   Results:\n";
    foreach ($recentSyncJobs as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Created: {$job->created_at} | Status: {$job->status}\n";
    }
} else {
    echo "   ⚠️  Query returned ZERO jobs! This is the ROOT CAUSE!\n";
}
echo "\n";

// 9. Check if there are sync jobs with OTHER job types
echo "🔍 Checking non-product_sync jobs:\n";
$otherJobs = SyncJob::where('job_type', '!=', SyncJob::JOB_PRODUCT_SYNC)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

echo "   Found: {$otherJobs->count()} jobs with other types\n";
if ($otherJobs->count() > 0) {
    foreach ($otherJobs as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Created: {$job->created_at}\n";
    }
}
echo "\n";

// 10. Check last 10 sync jobs (ANY type)
echo "🔍 Last 10 SyncJobs (ANY type):\n";
$lastTen = SyncJob::orderBy('created_at', 'desc')->take(10)->get();
foreach ($lastTen as $job) {
    echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Created: {$job->created_at}\n";
}
echo "\n";

echo "=== END DIAGNOSIS ===\n";
