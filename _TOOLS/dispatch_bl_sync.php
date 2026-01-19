<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\ERP\SyncProductToERP;
use App\Models\Product;
use App\Models\ERPConnection;

$sku = $argv[1] ?? 'BG-KAYO-S200';

$product = Product::where('sku', $sku)->first();
$connection = ERPConnection::where('erp_type', 'baselinker')->first();

if (!$product) {
    echo "Product not found: $sku\n";
    exit(1);
}

if (!$connection) {
    echo "Baselinker connection not found\n";
    exit(1);
}

echo "Dispatching sync job for: {$product->sku} (ID: {$product->id})\n";
echo "Connection: {$connection->instance_name} (ID: {$connection->id})\n";

SyncProductToERP::dispatch($product, $connection)->onQueue('erp_default');

echo "Job dispatched successfully!\n";
echo "Run 'php artisan queue:work --queue=erp_default --once' to process\n";
