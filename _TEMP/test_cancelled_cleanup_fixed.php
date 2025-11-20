<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\SyncJobCleanupService;

echo "\n=== TEST CANCELLED JOBS CLEANUP (AFTER FIX) ===\n\n";

// Count BEFORE
$cancelledBefore = DB::table('sync_jobs')->where('status', 'cancelled')->count();
$pendingBefore = DB::table('sync_jobs')->where('status', 'pending')->count();

echo "BEFORE cleanup:\n";
echo "  Cancelled: $cancelledBefore\n";
echo "  Pending:   $pendingBefore\n";

// Run cleanup with clearAllAges=TRUE
echo "\nRunning cleanupCustom(type='all', days=30, clearAllAges=TRUE, dryRun=FALSE)...\n";

$service = new SyncJobCleanupService();
$result = $service->cleanupCustom('all', 30, true, false);

echo "\nRESULT:\n";
print_r($result);

// Count AFTER
$cancelledAfter = DB::table('sync_jobs')->where('status', 'cancelled')->count();
$pendingAfter = DB::table('sync_jobs')->where('status', 'pending')->count();

echo "\nAFTER cleanup:\n";
echo "  Cancelled: $cancelledAfter (deleted: " . ($cancelledBefore - $cancelledAfter) . ")\n";
echo "  Pending:   $pendingAfter (deleted: " . ($pendingBefore - $pendingAfter) . ")\n";

// VERIFY SUCCESS
if ($cancelledBefore > 0 && $cancelledAfter === 0) {
    echo "\n✅ SUCCESS: All cancelled jobs deleted!\n";
} elseif ($cancelledAfter === $cancelledBefore) {
    echo "\n❌ FAILED: No cancelled jobs deleted!\n";
} else {
    echo "\n⚠️ PARTIAL: Some cancelled jobs deleted\n";
}

echo "\n";
