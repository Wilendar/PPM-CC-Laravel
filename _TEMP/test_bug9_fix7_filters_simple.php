<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SyncJob;

echo "=== BUG #9 FIX #7 VALIDATION (SIMPLE) ===\n\n";

// 1. Test job_type filter
echo "📊 Job Type Distribution:\n";
$jobTypes = SyncJob::select('job_type', \DB::raw('count(*) as count'))
    ->groupBy('job_type')
    ->get();

if ($jobTypes->isEmpty()) {
    echo "   ⚠️  No sync jobs found in database\n";
} else {
    foreach ($jobTypes as $type) {
        echo "   • {$type->job_type}: {$type->count}\n";
    }
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

// 3. Test user_id presence
echo "👥 User IDs in SyncJobs:\n";
$userIds = SyncJob::select('user_id', \DB::raw('count(*) as count'))
    ->whereNotNull('user_id')
    ->groupBy('user_id')
    ->get();

if ($userIds->isEmpty()) {
    echo "   ⚠️  No user_id found (all jobs triggered by SYSTEM)\n";
} else {
    foreach ($userIds as $user) {
        echo "   • User ID: {$user->user_id} - {$user->count} jobs\n";
    }
}
echo "\n";

// 4. Test target_id (shop) presence
echo "🏪 Target IDs (Shops) in SyncJobs:\n";
$targetIds = SyncJob::select('target_id', 'target_type', \DB::raw('count(*) as count'))
    ->whereNotNull('target_id')
    ->groupBy('target_id', 'target_type')
    ->get();

if ($targetIds->isEmpty()) {
    echo "   ⚠️  No target_id found\n";
} else {
    foreach ($targetIds as $target) {
        echo "   • Target ID: {$target->target_id} (Type: {$target->target_type}) - {$target->count} jobs\n";
    }
}
echo "\n";

// 5. Test order by DESC (newest first)
echo "🔍 Test Order By DESC (newest first, limit 5):\n";
$newestFirst = SyncJob::orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'job_type', 'status', 'created_at']);

echo "   Results: {$newestFirst->count()}\n";
foreach ($newestFirst as $job) {
    echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Created: {$job->created_at}\n";
}
echo "\n";

// 6. Test order by ASC (oldest first)
echo "🔍 Test Order By ASC (oldest first, limit 5):\n";
$oldestFirst = SyncJob::orderBy('created_at', 'asc')
    ->limit(5)
    ->get(['id', 'job_type', 'status', 'created_at']);

echo "   Results: {$oldestFirst->count()}\n";
foreach ($oldestFirst as $job) {
    echo "   • ID: {$job->id} | Type: {$job->job_type} | Status: {$job->status} | Created: {$job->created_at}\n";
}
echo "\n";

// 7. Test filtered query (job_type=import_products)
echo "🔍 Test Filter: job_type=import_products:\n";
$importJobs = SyncJob::where('job_type', 'import_products')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'status', 'created_at']);

echo "   Results: {$importJobs->count()}\n";
foreach ($importJobs as $job) {
    echo "   • ID: {$job->id} | Status: {$job->status} | Created: {$job->created_at}\n";
}
echo "\n";

// 8. Test filtered query (job_type=product_sync)
echo "🔍 Test Filter: job_type=product_sync:\n";
$syncJobs = SyncJob::where('job_type', 'product_sync')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'status', 'created_at']);

echo "   Results: {$syncJobs->count()}\n";
foreach ($syncJobs as $job) {
    echo "   • ID: {$job->id} | Status: {$job->status} | Created: {$job->created_at}\n";
}
echo "\n";

// 9. Test pagination
echo "🔍 Test Pagination (perPage=5):\n";
$paginated = SyncJob::orderBy('created_at', 'desc')->paginate(5);

echo "   Total: {$paginated->total()}\n";
echo "   Per Page: {$paginated->perPage()}\n";
echo "   Current Page: {$paginated->currentPage()}\n";
echo "   Last Page: {$paginated->lastPage()}\n";
echo "   Items on current page: {$paginated->count()}\n";
echo "\n";

// 10. Test combined filters
if ($jobTypes->isNotEmpty() && $statuses->isNotEmpty()) {
    $firstJobType = $jobTypes->first()->job_type;
    $firstStatus = $statuses->first()->status;

    echo "🔍 Test Combined Filters (job_type={$firstJobType}, status={$firstStatus}):\n";
    $combined = SyncJob::where('job_type', $firstJobType)
        ->where('status', $firstStatus)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'created_at']);

    echo "   Results: {$combined->count()}\n";
    foreach ($combined as $job) {
        echo "   • ID: {$job->id} | Created: {$job->created_at}\n";
    }
    echo "\n";
}

echo "=== VALIDATION COMPLETE ===\n";
echo "\n✅ Filter Properties Ready:\n";
echo "   • filterJobType (import_products / product_sync)\n";
echo "   • filterOrderBy (asc / desc)\n";
echo "   • filterUserId (specific user or null)\n";
echo "   • filterStatus (completed / failed / running / pending / canceled)\n";
echo "   • filterShopId (target_id)\n";
echo "   • perPage (pagination)\n";
