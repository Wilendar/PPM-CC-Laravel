<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\SyncJobCleanupService;
use App\Models\SyncJob;

echo "=== BUG #9 FIX #4 + FIX #6 VALIDATION ===\n\n";

// 1. Check config exists
if (!config()->has('sync.retention')) {
    echo "❌ Config sync.php not found\n";
    exit(1);
}

echo "✅ Config sync.php found:\n";
echo "   Completed retention: " . config('sync.retention.completed_days') . " days\n";
echo "   Failed retention: " . config('sync.retention.failed_days') . " days\n";
echo "   Canceled retention: " . config('sync.retention.canceled_days') . " days\n";
echo "   Auto cleanup enabled: " . (config('sync.cleanup.auto_cleanup_enabled') ? 'YES' : 'NO') . "\n";
echo "   Batch size: " . config('sync.cleanup.batch_size') . "\n\n";

// 2. Check service exists
if (!class_exists(App\Services\SyncJobCleanupService::class)) {
    echo "❌ SyncJobCleanupService not found\n";
    exit(1);
}

echo "✅ SyncJobCleanupService found\n\n";

// 3. Test dry run
$service = app(SyncJobCleanupService::class);

echo "📊 Running cleanup preview (dry run)...\n";
$preview = $service->preview();

echo "\n📈 Cleanup Preview Results:\n";
echo "   Completed jobs to delete: {$preview['completed']}\n";
echo "   Failed jobs to delete: {$preview['failed']}\n";
echo "   Canceled jobs to delete: {$preview['canceled']}\n";
echo "   Total jobs to delete: {$preview['total']}\n\n";

// 4. Show current sync jobs stats
echo "📋 Current SyncJobs Statistics:\n";
$completedCount = SyncJob::where('status', 'completed')->count();
$failedCount = SyncJob::where('status', 'failed')->count();
$canceledCount = SyncJob::where('status', 'canceled')->count();
$pendingCount = SyncJob::where('status', 'pending')->count();
$runningCount = SyncJob::where('status', 'running')->count();
$total = SyncJob::count();

echo "   Completed: $completedCount\n";
echo "   Failed: $failedCount\n";
echo "   Canceled: $canceledCount\n";
echo "   Pending: $pendingCount (NEVER deleted)\n";
echo "   Running: $runningCount (NEVER deleted)\n";
echo "   Total: $total\n\n";

// 5. Check command exists
try {
    $output = shell_exec('php artisan list | findstr sync:cleanup');
    if (strpos($output, 'sync:cleanup') !== false) {
        echo "✅ Artisan command sync:cleanup registered\n";
    } else {
        echo "⚠️  Artisan command sync:cleanup not found in list\n";
    }
} catch (\Exception $e) {
    echo "⚠️  Could not check artisan commands: {$e->getMessage()}\n";
}

// 6. Check SyncController method exists
try {
    $reflection = new ReflectionClass(\App\Http\Livewire\Admin\Shops\SyncController::class);
    if ($reflection->hasMethod('clearOldLogs')) {
        echo "✅ SyncController::clearOldLogs() method exists\n";
    } else {
        echo "❌ SyncController::clearOldLogs() method NOT FOUND\n";
    }
} catch (\Exception $e) {
    echo "❌ Could not check SyncController: {$e->getMessage()}\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";
echo "\n📝 Next Steps:\n";
echo "   1. Test command: php artisan sync:cleanup --dry-run\n";
echo "   2. Test UI button: Add button in sync-controller.blade.php\n";
echo "   3. Enable auto cleanup: Set SYNC_AUTO_CLEANUP=true in .env\n";
echo "   4. Deploy to production\n";
