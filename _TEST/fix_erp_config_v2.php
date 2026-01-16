<?php
/**
 * Script to fix ERPConnection configuration for Baselinker
 *
 * Problem: connection_config is empty, which means api_token and inventory_id are missing
 * Solution: Update connection_config with proper values
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
echo "Type: {$connection->erp_type}\n\n";

echo "=== Current connection_config (decrypted) ===\n";
$config = $connection->connection_config;
print_r($config);

// Check if config is empty or missing required fields
if (empty($config) || empty($config['api_token']) || empty($config['inventory_id'])) {
    echo "\n=== Configuration is incomplete! ===\n";
    echo "Missing fields:\n";
    if (empty($config['api_token'])) echo " - api_token\n";
    if (empty($config['inventory_id'])) echo " - inventory_id\n";

    // Prompt for API token (you'll need to manually provide this)
    echo "\n=== Manual Fix Required ===\n";
    echo "You need to configure the connection via ERPManager UI:\n";
    echo "1. Go to https://ppm.mpptrade.pl/admin/integrations/erp\n";
    echo "2. Edit the BASE TEST connection\n";
    echo "3. Enter API token and select inventory_id\n";
    echo "4. Save the connection\n\n";

    // Alternative: If we have the API token in env or config, we could use it
    $envApiToken = getenv('BASELINKER_API_TOKEN');
    if ($envApiToken) {
        echo "Found BASELINKER_API_TOKEN in environment. Testing...\n";

        // Test authentication to get inventories
        $baselinkerService = new BaselinkerService();
        $result = $baselinkerService->testAuthentication([
            'api_token' => $envApiToken
        ]);

        if ($result['success']) {
            echo "Authentication successful!\n";
            $inventories = $result['details']['inventories'] ?? [];

            if (!empty($inventories)) {
                echo "Available inventories:\n";
                foreach ($inventories as $inv) {
                    echo " - ID: {$inv['inventory_id']} - {$inv['name']} (Default: " . ($inv['is_default'] ? 'Yes' : 'No') . ")\n";
                }

                // Use default inventory or first one
                $defaultInv = null;
                foreach ($inventories as $inv) {
                    if ($inv['is_default']) {
                        $defaultInv = $inv;
                        break;
                    }
                }
                if (!$defaultInv) $defaultInv = $inventories[0];

                echo "\nUpdating connection_config with:\n";
                echo " - api_token: " . substr($envApiToken, 0, 10) . "...\n";
                echo " - inventory_id: {$defaultInv['inventory_id']}\n";

                $connection->connection_config = [
                    'api_token' => $envApiToken,
                    'inventory_id' => (int)$defaultInv['inventory_id'],
                    'warehouse_mappings' => [],
                ];
                $connection->auth_status = 'authenticated';
                $connection->connection_status = 'connected';
                $connection->save();

                echo "\nConfiguration updated successfully!\n";

                // Verify
                $connection->refresh();
                echo "\nVerifying saved config:\n";
                print_r($connection->connection_config);
            } else {
                echo "No inventories found!\n";
            }
        } else {
            echo "Authentication failed: {$result['message']}\n";
        }
    } else {
        echo "BASELINKER_API_TOKEN not found in environment.\n";
        echo "Please configure the connection manually via UI.\n";
    }
} else {
    echo "\n=== Configuration looks OK ===\n";
    echo "api_token: SET (" . strlen($config['api_token']) . " chars)\n";
    echo "inventory_id: {$config['inventory_id']}\n";
}
