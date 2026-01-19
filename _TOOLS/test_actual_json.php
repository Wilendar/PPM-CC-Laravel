<?php
/**
 * Test EXACT json_encode behavior as in BaselinkerService::makeRequest()
 */
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

$sku = $argv[1] ?? 'BG-KAYO-S200';
$product = Product::where('sku', $sku)->first();

if (!$product) {
    echo "Product not found: $sku\n";
    exit(1);
}

echo "=== TESTING EXACT JSON ENCODING ===\n\n";

// 1. Create stdClass just like in buildBaselinkerProductData()
$imagesObject = new \stdClass();
$mediaCollection = $product->media()->active()->forGallery()->get();
$imageIndex = 0;

foreach ($mediaCollection as $media) {
    $imageUrl = $media->url;
    if ($imageUrl && !empty($imageUrl) && !str_contains($imageUrl, 'placeholder')) {
        $imagesObject->{(string)$imageIndex} = $imageUrl;
        $imageIndex++;
    }
}

echo "Step 1: Created stdClass\n";
echo "  Type: " . gettype($imagesObject) . "\n";
echo "  Class: " . (is_object($imagesObject) ? get_class($imagesObject) : 'N/A') . "\n";
echo "  Properties: " . json_encode(get_object_vars($imagesObject)) . "\n\n";

// 2. Create $requestParams array with images
$requestParams = [
    'inventory_id' => '22652',
    'sku' => $product->sku,
    'images' => $imagesObject,  // <-- stdClass directly
];

echo "Step 2: Created requestParams array\n";
echo "  requestParams type: " . gettype($requestParams) . "\n";
echo "  requestParams['images'] type: " . gettype($requestParams['images']) . "\n";
echo "  requestParams['images'] class: " . (is_object($requestParams['images']) ? get_class($requestParams['images']) : 'N/A') . "\n\n";

// 3. json_encode EXACTLY like in makeRequest()
$jsonEncoded = json_encode($requestParams);

echo "Step 3: json_encode(requestParams)\n";
echo "  Result: " . substr($jsonEncoded, 0, 500) . "\n";

// Check if images is object {} or array []
if (strpos($jsonEncoded, '"images":{') !== false) {
    echo "\n✅ SUCCESS: images encoded as JSON OBJECT {}\n";
} elseif (strpos($jsonEncoded, '"images":[') !== false) {
    echo "\n❌ FAILURE: images encoded as JSON ARRAY []\n";
} else {
    echo "\n❓ UNKNOWN: Could not detect images format\n";
}

// 4. Extract just the images part
preg_match('/"images":([\{\[].+?[\}\]])(,|$)/', $jsonEncoded, $matches);
echo "\nImages portion: " . ($matches[1] ?? 'not found') . "\n";

// 5. Try alternate approach - JSON_FORCE_OBJECT
echo "\n=== ALTERNATIVE: JSON_FORCE_OBJECT ===\n";
$altJson = json_encode($requestParams, JSON_FORCE_OBJECT);
preg_match('/"images":([\{\[].+?[\}\]])(,|$)/', $altJson, $matches2);
echo "Images with JSON_FORCE_OBJECT: " . ($matches2[1] ?? 'not found') . "\n";
