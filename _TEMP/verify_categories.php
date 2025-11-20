<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;

$categoryIds = [2, 32, 34, 33, 57];

echo "=== VERIFY CATEGORIES ===\n\n";

foreach ($categoryIds as $id) {
    $cat = Category::find($id);
    if ($cat) {
        echo "PPM {$id}: ✅ {$cat->name}\n";
    } else {
        echo "PPM {$id}: ❌ NOT FOUND\n";
    }
}

echo "\n=== COMPLETE ===\n";
