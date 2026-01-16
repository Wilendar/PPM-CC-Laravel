<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$c = DB::table('erp_connections')->first();

echo "=== ERPConnection ===\n";
echo "ID: {$c->id}\n";
echo "Name: {$c->instance_name}\n";
echo "Type: {$c->erp_type}\n\n";

echo "=== connection_config ===\n";
$config = json_decode($c->connection_config, true);
print_r($config);

echo "\n=== Checking inventory_id ===\n";
if (!empty($config['inventory_id'])) {
    echo "inventory_id: {$config['inventory_id']}\n";
} else {
    echo "inventory_id: NOT SET - THIS IS THE PROBLEM!\n";
}
