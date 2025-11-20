<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SPRAWDZENIE TABEL Z 'shop' ===\n\n";

$tables = DB::select('SHOW TABLES');
$shopTables = [];

foreach ($tables as $table) {
    $tableName = array_values(get_object_vars($table))[0];
    if (stripos($tableName, 'shop') !== false) {
        $shopTables[] = $tableName;
    }
}

echo "Tabele zawierające 'shop':\n";
print_r($shopTables);

echo "\n=== SPRAWDZENIE CZY ISTNIEJE prestashop_shops ===\n";
$exists = in_array('prestashop_shops', $shopTables);
echo $exists ? "✅ Tabela prestashop_shops istnieje\n" : "❌ Tabela prestashop_shops NIE istnieje\n";

if ($exists) {
    echo "\n=== LISTA SKLEPÓW ===\n";
    $shops = DB::table('prestashop_shops')->get();
    foreach ($shops as $shop) {
        echo "\n";
        echo "ID: {$shop->id}\n";
        echo "Name: {$shop->name}\n";
        echo "URL: {$shop->url}\n";
        echo "Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
}
