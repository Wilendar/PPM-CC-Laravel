<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== SYNC JOBS STATUS DISTRIBUTION ===\n\n";

$stats = DB::table('sync_jobs')
    ->select('status', DB::raw('COUNT(*) as count'), DB::raw('MIN(created_at) as oldest'), DB::raw('MAX(created_at) as newest'))
    ->groupBy('status')
    ->get();

foreach ($stats as $stat) {
    printf("%-25s: %4d jobs (oldest: %s, newest: %s)\n",
        $stat->status,
        $stat->count,
        $stat->oldest,
        $stat->newest
    );
}

$total = DB::table('sync_jobs')->count();
printf("\nTOTAL: %d jobs\n\n", $total);
