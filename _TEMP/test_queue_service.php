<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\QueueJobsService;

echo "=== TESTING QueueJobsService ===" . PHP_EOL . PHP_EOL;

$queueService = app(QueueJobsService::class);

// Test getActiveJobs()
echo "1. Testing getActiveJobs()..." . PHP_EOL;
$activeJobs = $queueService->getActiveJobs();
echo "   Result: " . $activeJobs->count() . " active jobs" . PHP_EOL;

if ($activeJobs->count() > 0) {
    echo "   First job: " . json_encode($activeJobs->first()) . PHP_EOL;
}

echo PHP_EOL;

// Test getFailedJobs()
echo "2. Testing getFailedJobs()..." . PHP_EOL;
$failedJobs = $queueService->getFailedJobs();
echo "   Result: " . $failedJobs->count() . " failed jobs" . PHP_EOL;

if ($failedJobs->count() > 0) {
    echo "   First job: " . json_encode($failedJobs->first()) . PHP_EOL;
}

echo PHP_EOL;

// Test getStuckJobs()
echo "3. Testing getStuckJobs()..." . PHP_EOL;
$stuckJobs = $queueService->getStuckJobs();
echo "   Result: " . $stuckJobs->count() . " stuck jobs" . PHP_EOL;

if ($stuckJobs->count() > 0) {
    echo "   First job: " . json_encode($stuckJobs->first()) . PHP_EOL;
}

echo PHP_EOL;

// Test calculateQueueHealth logic
echo "4. Testing Queue Health Calculation..." . PHP_EOL;
$active = $activeJobs->count();
$failed = $failedJobs->count();
$stuck = $stuckJobs->count();
$total = $active + $failed + $stuck;

echo "   Active: {$active}" . PHP_EOL;
echo "   Failed: {$failed}" . PHP_EOL;
echo "   Stuck: {$stuck}" . PHP_EOL;
echo "   Total: {$total}" . PHP_EOL;

if ($total === 0) {
    $health = 100;
} else {
    $problems = $failed + $stuck;
    $health = (int) round(100 - (($problems / $total) * 100));
}

echo "   Queue Health: {$health}%" . PHP_EOL;

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
