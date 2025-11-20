<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SyncJob;
use App\Models\User;
use App\Models\PrestaShopShop;

echo "=== BUG #9 FIX #7 VALIDATION ===\n\n";

// 1. Test job_type filter
echo "📊 Job Type Distribution:\n";
$jobTypes = SyncJob::select('job_type', \DB::raw('count(*) as count'))
    ->groupBy('job_type')
    ->get();
foreach ($jobTypes as $type) {
    echo "   • {$type->job_type}: {$type->count}\n";
}
echo "\n";

// 2. Test status filter
echo "📊 Status Distribution:\n";
$statuses = SyncJob::select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();
foreach ($statuses as $status) {
    echo "   • {$status->status}: {$status->count}\n";
}
echo "\n";

// 3. Test user filter options
$users = SyncJob::with('user')
    ->select('user_id')
    ->distinct()
    ->whereNotNull('user_id')
    ->get()
    ->pluck('user')
    ->filter()
    ->unique('id');

echo "👥 Users who triggered syncs ({$users->count()}):\n";
foreach ($users as $user) {
    echo "   • {$user->name} (ID: {$user->id})\n";
}
echo "\n";

// 4. Test shop filter options
$shops = SyncJob::with('prestashopShop')
    ->select('target_id')
    ->distinct()
    ->whereNotNull('target_id')
    ->where('target_type', 'prestashop')
    ->get()
    ->pluck('prestashopShop')
    ->filter()
    ->unique('id');

echo "🏪 Shops with sync jobs ({$shops->count()}):\n";
foreach ($shops as $shop) {
    echo "   • {$shop->name} (ID: {$shop->id})\n";
}
echo "\n";

// 5. Test filtered query (job_type=import_products, status=failed)
echo "🔍 Test Filtered Query (job_type=import_products, status=failed):\n";
$filtered = SyncJob::where('job_type', 'import_products')
    ->where('status', 'failed')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "   Results: {$filtered->count()}\n";
foreach ($filtered as $job) {
    echo "   • ID: {$job->id} | Created: {$job->created_at}\n";
}
echo "\n";

// 6. Test filtered query (job_type=product_sync, status=completed)
echo "🔍 Test Filtered Query (job_type=product_sync, status=completed):\n";
$filtered2 = SyncJob::where('job_type', 'product_sync')
    ->where('status', 'completed')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "   Results: {$filtered2->count()}\n";
foreach ($filtered2 as $job) {
    echo "   • ID: {$job->id} | Created: {$job->created_at}\n";
}
echo "\n";

// 7. Test order by ASC
echo "🔍 Test Order By ASC (oldest first, limit 3):\n";
$oldestFirst = SyncJob::orderBy('created_at', 'asc')
    ->limit(3)
    ->get();

echo "   Results: {$oldestFirst->count()}\n";
foreach ($oldestFirst as $job) {
    echo "   • ID: {$job->id} | Created: {$job->created_at} | Status: {$job->status}\n";
}
echo "\n";

// 8. Test order by DESC (default)
echo "🔍 Test Order By DESC (newest first, limit 3):\n";
$newestFirst = SyncJob::orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

echo "   Results: {$newestFirst->count()}\n";
foreach ($newestFirst as $job) {
    echo "   • ID: {$job->id} | Created: {$job->created_at} | Status: {$job->status}\n";
}
echo "\n";

// 9. Test pagination
echo "🔍 Test Pagination (perPage=5, page 1):\n";
$paginated = SyncJob::orderBy('created_at', 'desc')->paginate(5);

echo "   Total: {$paginated->total()}\n";
echo "   Per Page: {$paginated->perPage()}\n";
echo "   Current Page: {$paginated->currentPage()}\n";
echo "   Last Page: {$paginated->lastPage()}\n";
echo "   Showing {$paginated->count()} items on current page\n";
echo "\n";

// 10. Test combined filters (user + shop + status)
$firstUser = $users->first();
$firstShop = $shops->first();

if ($firstUser && $firstShop) {
    echo "🔍 Test Combined Filters (user={$firstUser->name}, shop={$firstShop->name}, status=completed):\n";
    $combined = SyncJob::where('user_id', $firstUser->id)
        ->where('target_id', $firstShop->id)
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    echo "   Results: {$combined->count()}\n";
    foreach ($combined as $job) {
        echo "   • ID: {$job->id} | Type: {$job->job_type} | Created: {$job->created_at}\n";
    }
    echo "\n";
} else {
    echo "⚠️  Skipping combined filter test (no user or shop data)\n\n";
}

echo "=== VALIDATION COMPLETE ===\n";
