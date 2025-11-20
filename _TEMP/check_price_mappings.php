<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC: Price Group Mappings ===\n\n";

// Get all shops
$shops = \App\Models\PrestaShopShop::all();

foreach ($shops as $shop) {
    echo "Shop ID: {$shop->id} - {$shop->name}\n";
    echo "URL: {$shop->url}\n";
    echo "---\n";

    // Check mappings table
    $mappings = DB::table('prestashop_shop_price_mappings')
        ->where('prestashop_shop_id', $shop->id)
        ->get();

    if ($mappings->count() > 0) {
        echo "Mappings found: {$mappings->count()}\n";
        foreach ($mappings as $mapping) {
            echo "  PS Group ID: {$mapping->prestashop_price_group_id}\n";
            echo "  PS Group Name: {$mapping->prestashop_price_group_name}\n";
            echo "  PPM Group: {$mapping->ppm_price_group_name}\n";
            echo "  Created: {$mapping->created_at}\n";
            echo "  ---\n";
        }
    } else {
        echo "No mappings found.\n";
    }
    echo "\n\n";
}

echo "=== DIAGNOSTIC COMPLETE ===\n";
