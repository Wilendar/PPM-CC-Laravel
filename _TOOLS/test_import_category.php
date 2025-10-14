<?php
/**
 * Test Import from Category Pit Bike
 *
 * ETAP_07 FAZA 3: Testing BulkImportProducts with 3-step solution
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\BulkImportProducts;

echo "=== TESTING BULK IMPORT FROM CATEGORY ===\n";
echo "Category: Pit Bike (ID=23)\n";
echo "Include subcategories: YES\n\n";

// Get shop
$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "ERROR: Shop with ID=1 not found!\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID={$shop->id})\n";
echo "URL: {$shop->shop_url}\n\n";

// Dispatch job
echo "Dispatching BulkImportProducts job...\n";
BulkImportProducts::dispatch($shop, 'category', [
    'category_id' => 23,
    'include_subcategories' => true,
]);

echo "Job dispatched to queue: default\n";
echo "\nTo execute the job, run:\n";
echo "  php artisan queue:work --once\n\n";
echo "To monitor logs:\n";
echo "  tail -f storage/logs/laravel.log | grep -i \"BulkImportProducts\"\n";
