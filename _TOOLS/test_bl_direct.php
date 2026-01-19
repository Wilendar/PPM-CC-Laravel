<?php
/**
 * Test sending DIRECT request to Baselinker to verify images format
 */
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ERPConnection;
use Illuminate\Support\Facades\Http;

$sku = $argv[1] ?? 'BG-KAYO-S200';
$product = Product::where('sku', $sku)->first();
$connection = ERPConnection::where('erp_type', 'baselinker')->first();

if (!$product || !$connection) {
    echo "Product or connection not found\n";
    exit(1);
}

$config = $connection->connection_config;
$token = $config['api_token'] ?? '';
$inventoryId = $config['default_inventory_id'] ?? '22652';

echo "=== DIRECT BASELINKER API TEST ===\n\n";

// Create images as stdClass
$imagesObject = new \stdClass();
$mediaCollection = $product->media()->active()->forGallery()->limit(2)->get();  // Only 2 for test
$imageIndex = 0;

foreach ($mediaCollection as $media) {
    $imageUrl = $media->url;
    if ($imageUrl && !empty($imageUrl)) {
        // CRITICAL: Baselinker requires "url:" prefix!
        $imagesObject->{(string)$imageIndex} = 'url:' . $imageUrl;
        $imageIndex++;
    }
}

echo "1. Images stdClass created:\n";
echo "   Type: " . gettype($imagesObject) . "\n";
echo "   Properties count: " . count(get_object_vars($imagesObject)) . "\n\n";

// Create request params exactly like in createBaselinkerProduct
$requestParams = [
    'inventory_id' => $inventoryId,
    'parent_id' => 0,
    'is_bundle' => false,
    'text_fields' => [
        'name' => 'TEST PRODUCT - ' . $product->sku,
        'description' => 'Test description',
    ],
    'sku' => 'TEST-' . $product->sku . '-' . time(),  // Unique SKU for test
    'ean' => '',
    'tax_rate' => 23,
    'weight' => 1,
    'height' => 10,
    'width' => 10,
    'length' => 10,
    'images' => $imagesObject,  // stdClass
];

echo "2. Request params prepared:\n";
echo "   requestParams['images'] type: " . gettype($requestParams['images']) . "\n\n";

// JSON encode exactly like in makeRequest()
$jsonEncodedParams = json_encode($requestParams);

echo "3. JSON encoded parameters (first 500 chars):\n";
echo substr($jsonEncodedParams, 0, 500) . "\n\n";

// Check if images is object or array in JSON
if (strpos($jsonEncodedParams, '"images":{') !== false) {
    echo "✅ JSON images is OBJECT format: {...}\n\n";
} else {
    echo "❌ JSON images is ARRAY format: [...]\n\n";
}

// Show just the images portion
preg_match('/"images":([^\]]+\]|[^\}]+\})/', $jsonEncodedParams, $matches);
echo "4. Images portion in JSON:\n";
echo "   " . ($matches[0] ?? 'not found') . "\n\n";

// Ask user if they want to actually send the request
echo "5. Ready to send to Baselinker API.\n";
echo "   Press Enter to continue, or Ctrl+C to cancel...\n";
// Skip interactive input for CLI script

// Send the actual request
echo "\n6. Sending request to Baselinker API...\n\n";

$response = Http::timeout(30)
    ->asForm()
    ->post('https://api.baselinker.com/connector.php', [
        'token' => $token,
        'method' => 'addInventoryProduct',
        'parameters' => $jsonEncodedParams
    ]);

echo "7. Response received:\n";
echo "   HTTP Status: " . $response->status() . "\n";
echo "   Body:\n";
$data = $response->json();
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (($data['status'] ?? '') === 'SUCCESS') {
    echo "✅ SUCCESS! Product created with ID: " . ($data['product_id'] ?? 'N/A') . "\n";
    echo "   Deleting test product...\n";

    // Clean up - delete the test product
    $deleteResponse = Http::timeout(30)
        ->asForm()
        ->post('https://api.baselinker.com/connector.php', [
            'token' => $token,
            'method' => 'deleteInventoryProduct',
            'parameters' => json_encode([
                'product_id' => $data['product_id']
            ])
        ]);

    echo "   Delete response: " . json_encode($deleteResponse->json()) . "\n";
} else {
    echo "❌ FAILED: " . ($data['error_message'] ?? 'Unknown error') . "\n";
    echo "   Error code: " . ($data['error_code'] ?? 'N/A') . "\n";
}
