<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== PENDING_FIELDS FORMAT CHECK ===\n\n";

// Find any product_shop_data with pending_fields
$data = DB::table('product_shop_data')
    ->whereNotNull('pending_fields')
    ->where('pending_fields', '!=', '{}')
    ->where('pending_fields', '!=', '[]')
    ->where('pending_fields', '!=', 'null')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'product_id', 'shop_id', 'pending_fields']);

if ($data->isEmpty()) {
    echo "No records with pending_fields found\n";
    echo "\nLet's check if pending_fields column exists...\n";

    $columns = DB::select("SHOW COLUMNS FROM product_shop_data LIKE 'pending%'");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
} else {
    echo "Found " . count($data) . " records with pending_fields:\n\n";

    foreach ($data as $row) {
        echo "ProductShopData #{$row->id} (Product: {$row->product_id}, Shop: {$row->shop_id})\n";

        $pendingFields = json_decode($row->pending_fields, true);

        if (is_array($pendingFields)) {
            echo "  Pending Fields:\n";
            foreach ($pendingFields as $field => $value) {
                echo "    {$field}: " . json_encode($value) . "\n";
            }
        } else {
            echo "  Pending Fields (raw): {$row->pending_fields}\n";
        }

        echo "\n";
    }
}

echo "\n";
