<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\BlockDefinition;
use App\Services\VisualEditor\BlockRegistry;
$shopId = 5;
echo "=== Testing BlockDefinition Query ===\n";
$definitions = BlockDefinition::forShop($shopId)->active()->ordered()->get();
echo "Found: " . $definitions->count() . " definitions\n";
foreach ($definitions as $d) {
    echo "  - Type: {$d->type}, Name: {$d->name}, Category: {$d->category}\n";
}
echo "\n=== Testing BlockRegistry ===\n";
$registry = app(BlockRegistry::class);
echo "Before loadShopBlocks: " . $registry->count() . " blocks\n";
$registry->loadShopBlocks($shopId);
echo "After loadShopBlocks($shopId): " . $registry->count() . " blocks\n";
$dynamicBlocks = $registry->getDynamicBlocksForShop($shopId);
echo "Dynamic blocks count: " . count($dynamicBlocks) . "\n";
foreach ($dynamicBlocks as $type => $block) {
    echo "  - Dynamic: {$type}\n";
}
echo "\n=== Grouped by Category ===\n";
$grouped = $registry->groupedByCategory();
foreach ($grouped as $category => $data) {
    echo "Category: {$category} - " . count($data["blocks"]) . " blocks\n";
}
echo "\n=== Done ===\n";

