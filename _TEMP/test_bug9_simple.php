<?php
// Simple validation without full bootstrap
// Just verify the code changes are present

echo "=== BUG #9 FIX #1 + FIX #2 CODE VALIDATION ===\n\n";

// 1. Check SyncController.php - verify job_type filter removed
echo "1. Verifying FIX #1 in SyncController.php:\n";
echo str_repeat('-', 70) . "\n";

$syncControllerPath = __DIR__ . '/../app/Http/Livewire/Admin/Shops/SyncController.php';
$syncControllerContent = file_get_contents($syncControllerPath);

// Check that the WHERE clause is commented out or removed
$hasOldFilter = strpos($syncControllerContent, "->where('job_type', SyncJob::JOB_PRODUCT_SYNC)") !== false;
$hasRemovalComment = strpos($syncControllerContent, '// REMOVED:') !== false;
$hasBugComment = strpos($syncControllerContent, 'BUG #9 FIX #1') !== false;

echo "   File: {$syncControllerPath}\n";
echo "   Has old filter line: " . ($hasOldFilter ? '❌ YES (still present!)' : '✅ NO (removed)') . "\n";
echo "   Has REMOVED comment: " . ($hasRemovalComment ? '✅ YES' : '❌ NO') . "\n";
echo "   Has BUG #9 FIX #1 comment: " . ($hasBugComment ? '✅ YES' : '❌ NO') . "\n";

// Extract getRecentSyncJobs method for inspection
preg_match(
    '/protected function getRecentSyncJobs\(\).*?\{(.*?)\n    \}/s',
    $syncControllerContent,
    $matches
);

if (isset($matches[1])) {
    echo "\n   Method content preview:\n";
    $methodLines = explode("\n", trim($matches[1]));
    foreach ($methodLines as $line) {
        echo "     " . $line . "\n";
    }
}

echo "\n";
if (!$hasOldFilter && $hasRemovalComment && $hasBugComment) {
    echo "   ✅ FIX #1 VERIFIED: job_type filter successfully removed!\n";
} else {
    echo "   ⚠️  FIX #1 INCOMPLETE: Check the modifications.\n";
}

echo "\n";
echo str_repeat('=', 70) . "\n\n";

// 2. Check sync-controller.blade.php - verify wire:poll.5s added
echo "2. Verifying FIX #2 in sync-controller.blade.php:\n";
echo str_repeat('-', 70) . "\n";

$bladePath = __DIR__ . '/../resources/views/livewire/admin/shops/sync-controller.blade.php';
$bladeContent = file_get_contents($bladePath);

// Check for wire:poll.5s attribute
$hasWirePoll = strpos($bladeContent, 'wire:poll.5s') !== false;
$hasLoadingIndicator = strpos($bladeContent, 'wire:loading wire:target="$refresh"') !== false;
$hasBugComment2 = strpos($bladeContent, 'BUG #9 FIX #2') !== false;
$hasRefreshText = strpos($bladeContent, '(odświeżanie...)') !== false;

echo "   File: {$bladePath}\n";
echo "   Has wire:poll.5s: " . ($hasWirePoll ? '✅ YES' : '❌ NO') . "\n";
echo "   Has loading indicator: " . ($hasLoadingIndicator ? '✅ YES' : '❌ NO') . "\n";
echo "   Has BUG #9 FIX #2 comment: " . ($hasBugComment2 ? '✅ YES' : '❌ NO') . "\n";
echo "   Has refresh text: " . ($hasRefreshText ? '✅ YES' : '❌ NO') . "\n";

// Extract Recent Sync Jobs section
preg_match(
    '/<!-- Recent Sync Jobs.*?wire:poll\.5s>.*?<h3.*?>(.*?)<\/h3>/s',
    $bladeContent,
    $matches2
);

if (isset($matches2[0])) {
    echo "\n   Section preview (first 400 chars):\n";
    echo "     " . substr($matches2[0], 0, 400) . "...\n";
}

echo "\n";
if ($hasWirePoll && $hasLoadingIndicator && $hasBugComment2) {
    echo "   ✅ FIX #2 VERIFIED: wire:poll.5s and loading indicator added!\n";
} else {
    echo "   ⚠️  FIX #2 INCOMPLETE: Check the modifications.\n";
}

echo "\n";
echo str_repeat('=', 70) . "\n\n";

// 3. Summary
echo "3. VALIDATION SUMMARY:\n";
echo str_repeat('-', 70) . "\n";

$fix1Complete = !$hasOldFilter && $hasRemovalComment && $hasBugComment;
$fix2Complete = $hasWirePoll && $hasLoadingIndicator && $hasBugComment2;

echo "   FIX #1 (Remove job_type filter): " . ($fix1Complete ? '✅ COMPLETE' : '⚠️  INCOMPLETE') . "\n";
echo "   FIX #2 (Add wire:poll.5s): " . ($fix2Complete ? '✅ COMPLETE' : '⚠️  INCOMPLETE') . "\n";

echo "\n";

if ($fix1Complete && $fix2Complete) {
    echo "   ✅✅ BOTH FIXES SUCCESSFULLY IMPLEMENTED! ✅✅\n";
    echo "\n";
    echo "   Next steps:\n";
    echo "   1. Deploy to production (upload modified files)\n";
    echo "   2. Clear cache: php artisan view:clear && cache:clear\n";
    echo "   3. Test UI: Click 'Import' button and verify job appears in Recent list\n";
    echo "   4. Verify auto-refresh: Check that list updates every 5 seconds\n";
} else {
    echo "   ⚠️  Some fixes incomplete. Review the code changes.\n";
}

echo "\n";
echo str_repeat('=', 70) . "\n\n";
echo "=== VALIDATION COMPLETE ===\n";
