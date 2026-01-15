<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select("SHOW TABLES LIKE '%shop%'");
echo "Tables with 'shop':\n";
foreach($tables as $t) { 
    echo "  " . array_values((array)$t)[0] . "\n"; 
}

$tables2 = DB::select("SHOW TABLES LIKE '%product%'");
echo "\nTables with 'product':\n";
foreach($tables2 as $t) { 
    echo "  " . array_values((array)$t)[0] . "\n"; 
}
