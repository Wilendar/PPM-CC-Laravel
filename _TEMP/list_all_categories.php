<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL CATEGORIES ===\n\n";

$categories = DB::table('categories')
    ->where('name', 'LIKE', '%pit%')
    ->orWhere('name', 'LIKE', '%pojazd%')
    ->orWhere('name', 'LIKE', '%quad%')
    ->orWhere('name', 'LIKE', '%wszystko%')
    ->orderBy('id')
    ->get();

foreach ($categories as $cat) {
    echo "ID {$cat->id}: {$cat->name}\n";
}

echo "\n=== COMPLETE ===\n";
