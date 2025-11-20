<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;

echo "=== VERIFY PPM CATEGORIES EXIST ===\n\n";

$categoryIds = [2, 32, 34, 33, 57];

foreach ($categoryIds as $id) {
    $cat = Category::find($id);

    if ($cat) {
        echo "✅ PPM {$id}: {$cat->name}\n";
    } else {
        echo "❌ PPM {$id}: NOT FOUND\n";
    }
}

echo "\n=== SEARCHING BY NAME ===\n\n";

$names = ['Wszystko', 'PITGANG', 'Pit Bike', 'Pojazdy', 'Quad'];

foreach ($names as $name) {
    $cat = Category::where('name', $name)->first();

    if ($cat) {
        echo "✅ '{$name}': ID {$cat->id}\n";
    } else {
        echo "❌ '{$name}': NOT FOUND\n";
    }
}

echo "\n=== COMPLETE ===\n";
