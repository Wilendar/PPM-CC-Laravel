<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check ProductErpData for product 11216
$erpData = DB::table('product_erp_data')
    ->where('product_id', 11216)
    ->get(['id', 'product_id', 'erp_connection_id', 'sync_status', 'error_message', 'updated_at']);

echo "ProductErpData for product 11216:\n";
foreach ($erpData as $row) {
    echo "  ID: {$row->id}, Connection: {$row->erp_connection_id}, Status: {$row->sync_status}, Updated: {$row->updated_at}\n";
    if ($row->error_message) {
        echo "  Error: {$row->error_message}\n";
    }
}

// Check jobs table
$jobs = DB::table('jobs')->count();
echo "\nJobs in queue: {$jobs}\n";

// Check sync_jobs table
$syncJobs = DB::table('sync_jobs')
    ->where('source_id', 11216)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['id', 'job_name', 'status', 'created_at', 'completed_at']);

echo "\nRecent SyncJobs for product 11216:\n";
foreach ($syncJobs as $job) {
    echo "  {$job->id}: {$job->job_name} - {$job->status} ({$job->created_at})\n";
}
