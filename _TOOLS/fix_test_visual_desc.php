<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productId = 11183;
$shopId = 5; // Test KAYO

// CORRECT format for BlockRenderer - using 'content' and 'settings' keys
$blocksJson = json_encode([
    [
        'type' => 'heading',
        'content' => [
            'text' => 'Buggy KAYO S200 - Test opisu wizualnego',
        ],
        'settings' => [
            'level' => 'h2',
            'alignment' => 'left',
            'style' => 'default',
            'color' => 'inherit',
            'margin_bottom' => '1.5rem',
        ],
        'order' => 0,
    ],
    [
        'type' => 'text',
        'content' => [
            'text' => '<p>To jest <strong>testowy opis wizualny</strong> produktu. Ten opis powinien byc wyswietlony ze stylami CSS sklepu PrestaShop.</p><p>Jesli widzisz ten tekst w podgladzie z CSS sklepu - funkcja dziala poprawnie!</p>',
        ],
        'settings' => [
            'alignment' => 'left',
        ],
        'order' => 1,
    ],
    [
        'type' => 'feature-list',
        'content' => [
            'title' => 'Cechy produktu',
            'items' => [
                ['text' => 'Silnik 200cc'],
                ['text' => 'Naped 4x2'],
                ['text' => 'Hamulce tarczowe'],
            ],
        ],
        'settings' => [],
        'order' => 2,
    ],
]);

// Update existing description
$updated = DB::table('product_descriptions')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->update([
        'blocks_json' => $blocksJson,
        'updated_at' => now(),
    ]);

if ($updated) {
    echo "Updated visual description for Product ID: $productId, Shop ID: $shopId" . PHP_EOL;
    echo "Blocks JSON format corrected (content/settings structure)" . PHP_EOL;
} else {
    echo "No description found to update!" . PHP_EOL;
}
