<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$desc = DB::table('product_descriptions')
    ->where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

echo "=== RAW DATABASE RECORD ===\n";
echo "ID: " . ($desc->id ?? 'NULL') . "\n";
echo "blocks_json type: " . gettype($desc->blocks_json ?? null) . "\n";
echo "blocks_json length: " . strlen($desc->blocks_json ?? '') . "\n";
echo "blocks_json raw: " . ($desc->blocks_json ?? 'NULL') . "\n\n";

if ($desc->blocks_json) {
    $blocks = json_decode($desc->blocks_json, true);
    echo "Decoded blocks count: " . count($blocks ?? []) . "\n";
    echo "Decoded blocks: " . json_encode($blocks, JSON_PRETTY_PRINT) . "\n";
}
