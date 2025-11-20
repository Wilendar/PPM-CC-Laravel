<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== CONVERSION VERIFICATION ===\n\n";

echo "[1/3] NEW PRODUCTS FROM VARIANTS:\n";
$newProducts = Product::where('sku', 'like', 'TEST-KONWERSJA-1761919356-VAR-%')->get(['id', 'sku', 'name', 'is_variant_master']);
if ($newProducts->count() > 0) {
    foreach ($newProducts as $product) {
        echo "   Product ID: " . $product->id . "\n";
        echo "   SKU: " . $product->sku . "\n";
        echo "   Name: " . $product->name . "\n";
        echo "   is_variant_master: " . $product->is_variant_master . "\n\n";
    }
    echo "RESULT: " . $newProducts->count() . " new products created\n\n";
} else {
    echo "RESULT: NO NEW PRODUCTS FOUND!\n\n";
}

echo "[2/3] VARIANTS IN product_variants TABLE:\n";
$variants = ProductVariant::where('product_id', 10986)->get(['id', 'sku', 'name']);
if ($variants->count() > 0) {
    foreach ($variants as $variant) {
        echo "   Variant ID: " . $variant->id . "\n";
        echo "   SKU: " . $variant->sku . "\n";
        echo "   Name: " . $variant->name . "\n\n";
    }
    echo "RESULT: " . $variants->count() . " variants still exist (SHOULD BE 0!)\n\n";
} else {
    echo "RESULT: All variants deleted successfully\n\n";
}

echo "[3/3] MASTER PRODUCT STATUS:\n";
$master = Product::find(10986, ['id', 'sku', 'is_variant_master', 'default_variant_id']);
if ($master) {
    echo "   Product ID: " . $master->id . "\n";
    echo "   SKU: " . $master->sku . "\n";
    echo "   is_variant_master: " . $master->is_variant_master . "\n";
    echo "   default_variant_id: " . ($master->default_variant_id ?? 'NULL') . "\n\n";
    echo "RESULT: Master product updated\n\n";
} else {
    echo "RESULT: Master product NOT FOUND!\n\n";
}

echo "=== SUMMARY ===\n";
if ($newProducts->count() === 3 && $variants->count() === 0 && $master->is_variant_master == 0) {
    echo "SUCCESS: Conversion completed correctly!\n";
    echo "- 3 new products created from variants\n";
    echo "- All variants deleted\n";
    echo "- Master product is_variant_master set to false\n";
} else {
    echo "PROBLEM DETECTED:\n";
    echo "- New products: " . $newProducts->count() . " (expected 3)\n";
    echo "- Remaining variants: " . $variants->count() . " (expected 0)\n";
    echo "- Master is_variant_master: " . $master->is_variant_master . " (expected 0)\n";
}
