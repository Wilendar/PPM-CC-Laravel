<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix ProductErpData status - match SyncJob status
$updated = DB::table('product_erp_data')
    ->where('product_id', 11216)
    ->where('sync_status', 'syncing')
    ->update([
        'sync_status' => 'error',
        'error_message' => 'Produkt nie istnieje w Subiekt GT. Tworzenie produktow wymaga Sfera API.',
    ]);

echo "Fixed ProductErpData records: {$updated}\n";
