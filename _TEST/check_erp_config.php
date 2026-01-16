<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$connection = \App\Models\ERPConnection::first();

if (!$connection) {
    echo "No ERPConnection found!\n";
    exit(1);
}

echo "=== ERPConnection Details ===\n";
echo "ID: {$connection->id}\n";
echo "Name: {$connection->instance_name}\n";
echo "ERP Type: {$connection->erp_type}\n";
echo "Is Active: " . ($connection->is_active ? 'Yes' : 'No') . "\n";
echo "\n=== Connection Config ===\n";
print_r($connection->connection_config);

echo "\n=== Checking inventory_id ===\n";
$inventoryId = $connection->connection_config['inventory_id'] ?? null;
if ($inventoryId) {
    echo "inventory_id: {$inventoryId}\n";
} else {
    echo "inventory_id: NOT SET!\n";
    echo "This is why import returns 0 products!\n";
}

// Sprawdźmy też czy mamy tokeny
echo "\n=== API Token ===\n";
if (!empty($connection->api_token)) {
    echo "api_token: SET (length: " . strlen($connection->api_token) . ")\n";
} else {
    echo "api_token: NOT SET\n";
}
