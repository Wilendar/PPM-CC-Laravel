<?php
/**
 * Check which categories exist in PPM database
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   PPM CATEGORIES CHECK\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check if categories 1, 2, 12, 23, 800 exist
$testIds = [1, 2, 12, 23, 800];

foreach ($testIds as $id) {
    $category = DB::table('categories')->where('id', $id)->first(['id', 'name']);

    if ($category) {
        echo "✅ ID $id exists: {$category->name}\n";
    } else {
        echo "❌ ID $id DOES NOT EXIST\n";
    }
}

echo "\n";

// Show all categories (first 20)
$categories = DB::table('categories')->orderBy('id')->limit(20)->get(['id', 'name', 'parent_id']);

echo "First 20 categories in PPM:\n\n";
foreach ($categories as $cat) {
    echo sprintf("  [%d] %s (parent: %s)\n", $cat->id, $cat->name, $cat->parent_id ?? 'NULL');
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";
