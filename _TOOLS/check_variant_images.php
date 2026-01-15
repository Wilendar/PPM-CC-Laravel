<?php
/**
 * Debug script for variant images
 * Run: php check_variant_images.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VariantImage;
use Illuminate\Support\Facades\Storage;

echo "=== VARIANT IMAGE DIAGNOSTIC ===\n\n";

// Get some variant images
$variantImages = VariantImage::orderBy('id', 'desc')->limit(10)->get();

foreach ($variantImages as $vi) {
    echo "--- VariantImage ID: {$vi->id} ---\n";
    echo "  variant_id: {$vi->variant_id}\n";
    echo "  image_path: '{$vi->image_path}'\n";
    echo "  image_url: '{$vi->image_url}'\n";
    echo "  is_cached: " . ($vi->is_cached ? 'true' : 'false') . "\n";

    // Check if file exists
    if (!empty($vi->image_path)) {
        $exists = Storage::disk('public')->exists($vi->image_path);
        echo "  file_exists: " . ($exists ? 'YES' : 'NO') . "\n";

        if ($exists) {
            $fullPath = Storage::disk('public')->path($vi->image_path);
            echo "  full_path: '{$fullPath}'\n";
            echo "  file_size: " . filesize($fullPath) . " bytes\n";
        }
    }

    // Check URL accessor
    echo "  url accessor: '{$vi->url}'\n";
    echo "  thumbnail_url accessor: '{$vi->thumbnail_url}'\n";
    echo "\n";
}

// Check APP_URL config
echo "=== CONFIG ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "FILESYSTEM_DISK: " . config('filesystems.default') . "\n";
echo "PUBLIC_DISK_URL: " . config('filesystems.disks.public.url') . "\n";

// Check storage URL
echo "\n=== STORAGE URL GENERATION ===\n";
$testPath = 'variants/257/variant_257_ps27357_00.webp';
echo "Test path: '{$testPath}'\n";
echo "Storage::disk('public')->url(): '" . Storage::disk('public')->url($testPath) . "'\n";
echo "Storage::disk('public')->path(): '" . Storage::disk('public')->path($testPath) . "'\n";
echo "Storage::disk('public')->exists(): " . (Storage::disk('public')->exists($testPath) ? 'YES' : 'NO') . "\n";
