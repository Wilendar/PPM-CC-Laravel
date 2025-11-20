<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = (int)($_GET['product_id'] ?? 11034);

$rows = DB::table('product_categories')
    ->where('product_id', $productId)
    ->orderBy('shop_id')
    ->orderBy('category_id')
    ->get(['product_id', 'category_id', 'shop_id', 'is_primary']);

header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
