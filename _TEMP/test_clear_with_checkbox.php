<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\SyncJobCleanupService;
use Illuminate\Support\Facades\DB;

echo "\n=== TEST CLEANUP WITH clearAllAges = TRUE ===\n\n";

// Show current cancelled jobs
echo "BEFORE cleanup:\n";
$beforeCancelled = DB::table('sync_jobs')->where('status', 'cancelled')->count();
$beforePending = DB::table('sync_jobs')->where('status', 'pending')->count();
printf("  Cancelled: %d\n", $beforeCancelled);
printf("  Pending:   %d\n\n", $beforePending);

// Test cleanup with clearAllAges = TRUE
$cleanupService = app(SyncJobCleanupService::class);

echo "Running cleanupCustom(type='all', days=30, clearAllAges=TRUE, dryRun=FALSE)...\n\n";

$stats = $cleanupService->cleanupCustom(
    type: 'all',
    days: 30,
    clearAllAges: true,  // ← IGNORE AGE!
    dryRun: false
);

echo "RESULT:\n";
print_r($stats);

// Show after cleanup
echo "\nAFTER cleanup:\n";
$afterCancelled = DB::table('sync_jobs')->where('status', 'cancelled')->count();
$afterPending = DB::table('sync_jobs')->where('status', 'pending')->count();
printf("  Cancelled: %d (deleted: %d)\n", $afterCancelled, $beforeCancelled - $afterCancelled);
printf("  Pending:   %d (should be unchanged)\n\n", $afterPending);

if ($afterCancelled === 0 && $afterPending === $beforePending) {
    echo "✅ SUCCESS: Cancelled deleted, Pending protected!\n";
} else {
    echo "❌ FAIL: Something went wrong!\n";
}
