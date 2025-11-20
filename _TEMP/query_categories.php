<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = (int)($_GET['product_id'] ?? 11034);
$shopId = (int)($_GET['shop_id'] ?? 1);

$psd = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first([
        'id',
        'product_id',
        'shop_id',
        'category_mappings',
        'last_success_sync_at',
        'last_pulled_at',
        'sync_status',
        'updated_at',
    ]);

$shopMappingsAll = DB::table('shop_mappings')
    ->where('shop_id', $shopId)
    ->where('mapping_type', 'category')
    ->get(['prestashop_id', 'ppm_value']);

// Decode category_mappings to find which Presta IDs are referenced (from mappings values)
$decoded = null;
$neededPsIds = [];
if ($psd && $psd->category_mappings) {
    $decoded = json_decode($psd->category_mappings, true);
    if (!empty($decoded['mappings'])) {
        $neededPsIds = array_values(array_map('intval', $decoded['mappings']));
    }
}

$shopMappings = DB::table('shop_mappings')
    ->where('shop_id', $shopId)
    ->where('mapping_type', 'category')
    ->whereIn('prestashop_id', array_merge([1, 2], $neededPsIds))
    ->get(['prestashop_id', 'ppm_value']);

header('Content-Type: application/json');
echo json_encode([
    'product_shop_data' => $psd,
    'decoded_mappings' => $decoded,
    'root_mappings' => $shopMappings,
    'shop_mappings_all' => $shopMappingsAll,
], JSON_PRETTY_PRINT);
