<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;

echo "=== CATEGORIES IN DATABASE ===\n\n";
echo "Total categories: " . Category::count() . "\n\n";

$categories = Category::orderBy('id')->get(['id', 'name', 'parent_id']);

foreach ($categories as $cat) {
    echo "ID: {$cat->id} | Name: {$cat->name} | Parent: {$cat->parent_id}\n";
}

echo "\n=== END ===\n";
