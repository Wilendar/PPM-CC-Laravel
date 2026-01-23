<?php
// Reset stuck ERP job for product 11216
// Run with: php reset_stuck_job.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$updated = DB::table('product_erp_data')
    ->where('product_id', 11216)
    ->where('sync_status', 'syncing')
    ->update([
        'sync_status' => 'error',
        'error_message' => 'Queue worker nie dziala - naprawiono dispatchSync',
    ]);

echo "Updated rows: " . $updated . "\n";
