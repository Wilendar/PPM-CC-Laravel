<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productId = 11183;
$shopId = 5; // Test KAYO

// Test blocks JSON with simple heading and text
$blocksJson = json_encode([
    [
        'type' => 'heading',
        'data' => [
            'text' => 'Buggy KAYO S200 - Test opisu wizualnego',
            'level' => 2,
        ],
        'order' => 0,
    ],
    [
        'type' => 'text',
        'data' => [
            'text' => '<p>To jest <strong>testowy opis wizualny</strong> produktu. Ten opis powinien byc wyswietlony ze stylami CSS sklepu PrestaShop.</p><p>Jesli widzisz ten tekst w podgladzie z CSS sklepu - funkcja dziala poprawnie!</p>',
        ],
        'order' => 1,
    ],
    [
        'type' => 'features',
        'data' => [
            'title' => 'Cechy produktu',
            'items' => [
                'Silnik 200cc',
                'Napęd 4x2',
                'Hamulce tarczowe',
            ],
        ],
        'order' => 2,
    ],
]);

// Check if description exists
$existing = DB::table('product_descriptions')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if ($existing) {
    // Update existing
    DB::table('product_descriptions')
        ->where('id', $existing->id)
        ->update([
            'blocks_json' => $blocksJson,
            'updated_at' => now(),
        ]);
    echo "Updated existing description ID: " . $existing->id . PHP_EOL;
} else {
    // Create new
    $id = DB::table('product_descriptions')->insertGetId([
        'product_id' => $productId,
        'shop_id' => $shopId,
        'blocks_json' => $blocksJson,
        'sync_to_prestashop' => false,
        'target_field' => 'description',
        'include_inline_css' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created new description ID: " . $id . PHP_EOL;
}

echo "Test visual description created for Product ID: $productId, Shop ID: $shopId" . PHP_EOL;
