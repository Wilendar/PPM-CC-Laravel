<?php
// Sync CSS for shop 5 (test.kayomoto.pl) product 11183

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;

echo "=== CSS SYNC FOR SHOP 5 ===\n\n";

$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if (!$desc) {
    echo "Description not found!\n";
    exit(1);
}

echo "Found description ID: {$desc->id}\n";
echo "Product: {$desc->product_id}\n";
echo "Shop: {$desc->shop_id}\n";

$sync = app(CssSyncOrchestrator::class);
$result = $sync->syncProductDescription($desc, true);

echo "\n=== SYNC RESULT ===\n";
echo "Status: " . ($result['status'] ?? 'null') . "\n";
echo "Message: " . ($result['message'] ?? 'null') . "\n";
echo "Error: " . ($result['error'] ?? 'null') . "\n";
echo "Progress: " . ($result['progress'] ?? 0) . "%\n";

if (isset($result['details'])) {
    echo "\nDetails:\n";
    print_r($result['details']);
}
