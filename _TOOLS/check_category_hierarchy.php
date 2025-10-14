<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;

echo "=== CATEGORY HIERARCHY ANALYSIS ===\n\n";

$categories = Category::orderBy('level')
    ->orderBy('parent_id')
    ->orderBy('sort_order')
    ->get();

echo "Total categories: " . $categories->count() . "\n\n";

echo "=== FLAT LIST (current sorting: level, parent_id, sort_order) ===\n";
foreach ($categories as $cat) {
    $indent = str_repeat('  ', $cat->level);
    $parentName = $cat->parent_id ? Category::find($cat->parent_id)?->name : 'NULL';

    echo $indent . "ID: {$cat->id} | Level: {$cat->level} | Parent: {$cat->parent_id} ({$parentName}) | Name: {$cat->name}\n";
}

echo "\n=== HIERARCHICAL TREE (correct parent-child structure) ===\n";

function printTree($parentId, $level, $categories) {
    $children = $categories->where('parent_id', $parentId)->sortBy('sort_order');

    foreach ($children as $cat) {
        $indent = str_repeat('  ', $level);
        echo $indent . "└─ {$cat->name} (ID: {$cat->id}, Level: {$cat->level})\n";

        // Recursively print children
        printTree($cat->id, $level + 1, $categories);
    }
}

// Print root categories (parent_id = null)
$roots = $categories->whereNull('parent_id')->sortBy('sort_order');
foreach ($roots as $root) {
    echo "{$root->name} (ID: {$root->id}, Level: {$root->level})\n";
    printTree($root->id, 1, $categories);
}

echo "\n=== PARENT-CHILD RELATIONSHIPS ===\n";
foreach ($categories as $cat) {
    $children = $categories->where('parent_id', $cat->id);
    if ($children->count() > 0) {
        echo "\n{$cat->name} (ID: {$cat->id}) has {$children->count()} children:\n";
        foreach ($children as $child) {
            echo "  - {$child->name} (ID: {$child->id})\n";
        }
    }
}

echo "\n=== END ===\n";
