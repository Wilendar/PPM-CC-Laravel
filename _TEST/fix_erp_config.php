<?php
/**
 * Script to fix ERPConnection configuration
 * 1. Get list of inventories from Baselinker
 * 2. Set first inventory as default
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ERPConnection;
use App\Services\ERP\BaselinkerService;

$connection = ERPConnection::first();

if (!$connection) {
    echo "No ERPConnection found!\n";
    exit(1);
}

echo "=== ERPConnection ===\n";
echo "ID: {$connection->id}\n";
echo "Name: {$connection->instance_name}\n";
echo "API Token: " . (empty($connection->api_token) ? 'NOT SET' : 'SET (' . strlen($connection->api_token) . ' chars)') . "\n\n";

if (empty($connection->api_token)) {
    echo "ERROR: API token not set!\n";
    exit(1);
}

// Get inventories from Baselinker
$baselinkerService = new BaselinkerService();
$result = $baselinkerService->testAuthentication([
    'api_token' => $connection->api_token
]);

if (!$result['success']) {
    echo "ERROR: Authentication failed: {$result['message']}\n";
    exit(1);
}

echo "=== Available Inventories ===\n";
$inventories = $result['details']['inventories'] ?? [];

if (empty($inventories)) {
    echo "No inventories found!\n";
    exit(1);
}

foreach ($inventories as $inv) {
    echo "ID: {$inv['inventory_id']} - {$inv['name']} (Default: " . ($inv['is_default'] ? 'Yes' : 'No') . ")\n";
}

// Find default inventory or use first one
$defaultInventory = null;
foreach ($inventories as $inv) {
    if ($inv['is_default']) {
        $defaultInventory = $inv;
        break;
    }
}

if (!$defaultInventory) {
    $defaultInventory = $inventories[0];
}

echo "\n=== Setting inventory_id ===\n";
echo "Using inventory: {$defaultInventory['inventory_id']} - {$defaultInventory['name']}\n";

// Update connection_config
$config = $connection->connection_config ?? [];
$config['inventory_id'] = (int) $defaultInventory['inventory_id'];

$connection->connection_config = $config;
$connection->save();

echo "\nUpdated connection_config:\n";
print_r($connection->connection_config);

echo "\nDONE! Now retry the import.\n";
