<?php
// Test Block Registration

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\Blocks\Content\HeadingBlock;
use App\Services\VisualEditor\Blocks\Layout\HeroBannerBlock;

// Test 1: Check if class exists
echo "1. Class HeadingBlock exists: " . (class_exists(HeadingBlock::class) ? 'YES' : 'NO') . "\n";
echo "   Class HeroBannerBlock exists: " . (class_exists(HeroBannerBlock::class) ? 'YES' : 'NO') . "\n";

// Test 2: Try to instantiate
try {
    $block = new HeadingBlock();
    echo "2. HeadingBlock instantiated OK, type: " . $block->type . "\n";
} catch (Throwable $e) {
    echo "2. HeadingBlock error: " . $e->getMessage() . "\n";
}

// Test 3: Get registry
try {
    $registry = app(BlockRegistry::class);
    echo "3. BlockRegistry count: " . $registry->count() . "\n";

    if ($registry->count() > 0) {
        echo "4. Registered blocks:\n";
        foreach ($registry->all() as $type => $block) {
            echo "   - {$type} ({$block->name})\n";
        }
    }
} catch (Throwable $e) {
    echo "3. BlockRegistry error: " . $e->getMessage() . "\n";
}

// Test 4: Manually register and test
echo "\n--- Manual Registration Test ---\n";
try {
    // Create fresh registry
    $manualRegistry = new BlockRegistry();
    $manualRegistry->register(new HeadingBlock());
    $manualRegistry->register(new HeroBannerBlock());
    echo "4. Manual registry count: " . $manualRegistry->count() . "\n";
    echo "   Grouped categories:\n";
    foreach ($manualRegistry->groupedByCategory() as $category => $data) {
        echo "   - {$category}: " . count($data['blocks']) . " blocks\n";
    }
} catch (Throwable $e) {
    echo "4. Manual registry error: " . $e->getMessage() . "\n";
}

// Test 5: Check if ServiceProvider is loaded
echo "\n--- ServiceProvider Check ---\n";
$providers = app()->getLoadedProviders();
echo "5. VisualEditorServiceProvider loaded: " .
    (isset($providers['App\Providers\VisualEditorServiceProvider']) ? 'YES' : 'NO') . "\n";
