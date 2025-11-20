<?php

require __DIR__ . '/../bootstrap/app.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check PrestaShop shops tax mappings
$shops = \App\Models\PrestaShopShop::all();

foreach ($shops as $shop) {
    echo "Shop ID: {$shop->id}\n";
    echo "  Name: {$shop->name}\n";
    echo "  tax_rules_group_id_23: " . ($shop->tax_rules_group_id_23 ?? 'NULL') . "\n";
    echo "  tax_rules_group_id_8: " . ($shop->tax_rules_group_id_8 ?? 'NULL') . "\n";
    echo "  tax_rules_group_id_5: " . ($shop->tax_rules_group_id_5 ?? 'NULL') . "\n";
    echo "  tax_rules_group_id_0: " . ($shop->tax_rules_group_id_0 ?? 'NULL') . "\n";
    echo "\n";
}
