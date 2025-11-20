<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('jobs')->count();
$syncJobs = DB::table('sync_jobs')->count();

echo "Jobs: $jobs\n";
echo "SyncJobs: $syncJobs\n";
