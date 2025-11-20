<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;

echo "\n=== FIXING INCORRECT GROSS PRICES ===\n";
echo "Finding prices where price_gross = price_net (missing VAT calculation)...\n\n";

// Find all ProductPrice records where price_gross equals price_net
$incorrectPrices = ProductPrice::whereColumn('price_gross', '=', 'price_net')
    ->whereHas('product', function($query) {
        $query->where('tax_rate', '>', 0); // Only products with VAT
    })
    ->with('product', 'priceGroup')
    ->get();

echo "Found " . $incorrectPrices->count() . " prices with incorrect gross values.\n\n";

if ($incorrectPrices->isEmpty()) {
    echo "No incorrect prices found. All prices are correct!\n";
    exit(0);
}

$fixed = 0;
$errors = 0;

DB::beginTransaction();

try {
    foreach ($incorrectPrices as $price) {
        $product = $price->product;
        $oldGross = $price->price_gross;
        $correctGross = $price->price_net * (1 + ($product->tax_rate / 100));

        echo "Product: {$product->sku} (ID: {$product->id})\n";
        echo "  Price Group: {$price->priceGroup->code}\n";
        echo "  Tax Rate: {$product->tax_rate}%\n";
        echo "  Net: {$price->price_net} PLN\n";
        echo "  Old Gross (INCORRECT): {$oldGross} PLN\n";
        echo "  New Gross (CORRECT): " . number_format($correctGross, 2) . " PLN\n";

        // Update price_gross
        $price->update([
            'price_gross' => $correctGross,
        ]);

        echo "  ✅ FIXED!\n\n";
        $fixed++;
    }

    DB::commit();

    echo "\n=== FIX COMPLETED ===\n";
    echo "Fixed: {$fixed} prices\n";
    echo "Errors: {$errors}\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n❌ ERROR during fix:\n";
    echo $e->getMessage() . "\n";
    echo "\nTransaction rolled back. No changes were made.\n";
    exit(1);
}
