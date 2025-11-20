&lt;?php

require __DIR__ . '/../vendor/autoload.php';

\ = require_once __DIR__ . '/../bootstrap/app.php';
\->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK PPM DATABASE - PRODUCT 11034 CATEGORY_MAPPINGS ===\n\n";

\ = App\Models\ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (\!\) {
    die("ProductShopData NOT FOUND\!\n");
}

echo "Category Mappings:\n";
echo json_encode(\->category_mappings, JSON_PRETTY_PRINT);
echo "\n\n";

echo "=== COMPLETE ===\n";
