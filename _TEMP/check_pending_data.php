<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING PENDING DATA FOR PRODUCT 11033, SHOP 5 ===\n\n";

// Check ProductShopData
$psd = DB::table('product_shop_data')
    ->where('product_id', 11033)
    ->where('shop_id', 5)
    ->first();

if ($psd) {
    echo "[OK] ProductShopData exists\n";
    echo "  sync_status: " . $psd->sync_status . "\n";
    echo "  pending_fields: " . ($psd->pending_fields ?? 'NULL') . "\n\n";

    if ($psd->pending_fields) {
        $decoded = json_decode($psd->pending_fields, true);
        echo "  Decoded pending_fields:\n";
        print_r($decoded);
        echo "\n";

        if (in_array('Kategorie', $decoded)) {
            echo "  [OK] 'Kategorie' is in pending_fields array!\n";
        } else {
            echo "  [ERROR] 'Kategorie' NOT in pending_fields array!\n";
            echo "  Available fields: " . implode(', ', $decoded) . "\n";
        }
    } else {
        echo "  [WARNING] pending_fields is NULL or empty\n";
    }
} else {
    echo "[ERROR] ProductShopData NOT FOUND\n";
}

echo "\n=== END ===\n";
