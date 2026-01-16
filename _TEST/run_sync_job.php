<?php
/**
 * Manually run SyncJob #3190 for product 323429561
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncJob;
use App\Models\ERPConnection;
use App\Services\ERP\BaselinkerService;

echo "=== Running SyncJob #3190 manually ===\n";

$syncJob = SyncJob::find(3190);
if (!$syncJob) {
    echo "SyncJob not found!\n";
    exit(1);
}

echo "Job: {$syncJob->job_name}\n";
echo "Filters: " . json_encode($syncJob->filters) . "\n";
echo "Status: {$syncJob->status}\n";

$connection = ERPConnection::find($syncJob->source_id);
if (!$connection) {
    echo "ERPConnection not found!\n";
    exit(1);
}

echo "Connection: {$connection->instance_name}\n";

// Get product IDs from filters
$productIds = $syncJob->filters['product_ids'] ?? [];
echo "Product IDs to import: " . implode(', ', $productIds) . "\n";

// Update job status
$syncJob->update(['status' => 'running']);

$service = new BaselinkerService();

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($productIds as $productId) {
    echo "\n--- Processing product ID: {$productId} ---\n";

    try {
        $result = $service->syncProductFromERP($connection, (string) $productId);

        if ($result['success']) {
            $successCount++;
            $product = $result['product'];
            echo "SUCCESS: {$result['message']}\n";
            echo "  Product ID: {$product->id}\n";
            echo "  SKU: {$product->sku}\n";
            echo "  Name: {$product->name}\n";
            echo "  Media count: " . $product->media()->count() . "\n";
        } else {
            $errorCount++;
            $errors[] = "Product {$productId}: {$result['message']}";
            echo "FAILED: {$result['message']}\n";
        }
    } catch (\Exception $e) {
        $errorCount++;
        $errors[] = "Product {$productId}: " . $e->getMessage();
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
}

// Update job status
$syncJob->update([
    'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
    'result_summary' => [
        'imported' => $successCount,
        'errors' => $errorCount,
    ],
    'error_log' => $errors,
    'completed_at' => now(),
]);

echo "\n=== SUMMARY ===\n";
echo "Success: {$successCount}\n";
echo "Errors: {$errorCount}\n";
if ($errors) {
    echo "Error details:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}
