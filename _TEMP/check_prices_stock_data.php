<?php

// Check Prices & Stock Data
// 2025-11-07

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\ProductPrice;
use App\Models\ProductStock;

echo "\n=== PRICES & STOCK DATA CHECK ===\n\n";

echo "[1] Price Groups: " . PriceGroup::count() . "\n";
if (PriceGroup::count() > 0) {
    echo "    - Active: " . PriceGroup::where('is_active', true)->count() . "\n";
    echo "    - Groups: " . PriceGroup::pluck('name')->implode(', ') . "\n";
}

echo "\n[2] Warehouses: " . Warehouse::count() . "\n";
if (Warehouse::count() > 0) {
    echo "    - Active: " . Warehouse::where('is_active', true)->count() . "\n";
    echo "    - Warehouses: " . Warehouse::pluck('name')->implode(', ') . "\n";
}

echo "\n[3] Product Prices: " . ProductPrice::count() . "\n";
if (ProductPrice::count() > 0) {
    echo "    - Active: " . ProductPrice::where('is_active', true)->count() . "\n";
}

echo "\n[4] Product Stock: " . ProductStock::count() . "\n";
if (ProductStock::count() > 0) {
    echo "    - Active: " . ProductStock::where('is_active', true)->count() . "\n";
    echo "    - Total quantity: " . ProductStock::sum('quantity') . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
