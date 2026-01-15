<?php

/**
 * Debug script for BlockRegistry auto-discovery
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\Blocks\BaseBlock;
use Illuminate\Support\Facades\File;

echo "=== DEBUG BLOCK REGISTRY ===" . PHP_EOL . PHP_EOL;

$registry = new BlockRegistry();

$basePath = app_path('Services/VisualEditor/Blocks');
$baseNamespace = 'App\\Services\\VisualEditor\\Blocks';

echo "Base path: {$basePath}" . PHP_EOL;
echo "Base namespace: {$baseNamespace}" . PHP_EOL . PHP_EOL;

$subdirectories = ['Layout', 'Content', 'Media', 'Interactive'];

foreach ($subdirectories as $subdir) {
    $path = $basePath . DIRECTORY_SEPARATOR . $subdir;

    echo "=== {$subdir} ===" . PHP_EOL;
    echo "Path: {$path}" . PHP_EOL;

    if (!File::isDirectory($path)) {
        echo "  NOT A DIRECTORY!" . PHP_EOL;
        continue;
    }

    $files = File::files($path);
    echo "Files found: " . count($files) . PHP_EOL;

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $className = $file->getFilenameWithoutExtension();
        echo "  [{$className}]" . PHP_EOL;

        // Skip base/abstract classes
        if (str_starts_with($className, 'Base') || str_starts_with($className, 'Abstract')) {
            echo "    -> SKIPPED (base/abstract)" . PHP_EOL;
            continue;
        }

        $fullClassName = "{$baseNamespace}\\{$subdir}\\{$className}";
        echo "    Full class: {$fullClassName}" . PHP_EOL;

        if (!class_exists($fullClassName)) {
            echo "    -> CLASS NOT FOUND!" . PHP_EOL;
            continue;
        }

        $reflection = new ReflectionClass($fullClassName);

        if ($reflection->isAbstract()) {
            echo "    -> SKIPPED (abstract)" . PHP_EOL;
            continue;
        }

        if (!$reflection->isSubclassOf(BaseBlock::class)) {
            echo "    -> SKIPPED (not subclass of BaseBlock)" . PHP_EOL;
            continue;
        }

        try {
            $instance = new $fullClassName();
            $registry->register($instance);
            echo "    -> REGISTERED: {$instance->type} ({$instance->category})" . PHP_EOL;
        } catch (Throwable $e) {
            echo "    -> ERROR: " . $e->getMessage() . PHP_EOL;
        }
    }

    echo PHP_EOL;
}

echo "=== SUMMARY ===" . PHP_EOL;
echo "Total registered: " . $registry->count() . PHP_EOL . PHP_EOL;

echo "=== BLOCKS BY CATEGORY ===" . PHP_EOL;
foreach ($registry->getCategories() as $catId => $catLabel) {
    $blocks = $registry->byCategory($catId);
    echo "{$catLabel}: " . count($blocks) . " blocks" . PHP_EOL;
}
