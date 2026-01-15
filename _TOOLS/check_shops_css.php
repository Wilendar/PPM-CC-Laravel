<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shops = DB::table('prestashop_shops')->select('id', 'name', 'custom_css_url', 'cached_custom_css')->get();
foreach ($shops as $shop) {
    echo $shop->id . ' | ' . $shop->name . ' | CSS URL: ' . ($shop->custom_css_url ?? 'NULL');
    if ($shop->cached_custom_css) {
        echo ' | Cache: ' . strlen($shop->cached_custom_css) . ' bytes';
    }
    echo PHP_EOL;
}
