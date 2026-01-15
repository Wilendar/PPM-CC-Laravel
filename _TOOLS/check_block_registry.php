<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\VisualEditor\BlockRegistry;
use Illuminate\Support\Facades\File;
use App\Services\VisualEditor\Blocks\BaseBlock;

echo "=== Block Registry Debug ===\n\n";

// Test auto-discovery manually
$basePath = app_path('Services/VisualEditor/Blocks');
$subdirectories = ['Layout', 'Content', 'Media', 'Interactive', 'PrestaShop'];

echo "Scanning directories:\n";
foreach ($subdirectories as $subdir) {
    $path = $basePath . DIRECTORY_SEPARATOR . $subdir;
    echo "  {$subdir}: " . (File::isDirectory($path) ? 'EXISTS' : 'MISSING') . "\n";

    if (File::isDirectory($path)) {
        $files = File::files($path);
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $className = $file->getFilenameWithoutExtension();
            if (str_starts_with($className, 'Base') || str_starts_with($className, 'Abstract')) continue;

            $fullClassName = "App\\Services\\VisualEditor\\Blocks\\{$subdir}\\{$className}";
            echo "    - {$className}: ";

            if (!class_exists($fullClassName)) {
                echo "CLASS NOT FOUND ({$fullClassName})\n";
                continue;
            }

            $reflection = new ReflectionClass($fullClassName);
            if ($reflection->isAbstract()) {
                echo "ABSTRACT\n";
                continue;
            }
            if (!$reflection->isSubclassOf(BaseBlock::class)) {
                echo "NOT A BASEBLOCK\n";
                continue;
            }

            try {
                $instance = new $fullClassName();
                echo "OK (type: {$instance->type})\n";
            } catch (\Throwable $e) {
                echo "INSTANTIATION ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n=== Registry After discoverBlocks ===\n";
$registry = app(BlockRegistry::class);
echo "Total blocks: " . $registry->count() . "\n";
echo "Has 'prestashop-section': " . ($registry->has('prestashop-section') ? 'YES' : 'NO') . "\n";
