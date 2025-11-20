<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;

echo "=== TESTING PRODUCT_VARIANTS TABLE ===\n\n";

// 1. Check table structure
echo "1. Table Structure:\n";
$columns = DB::select('DESCRIBE product_variants');
foreach ($columns as $col) {
    echo "  - {$col->Field}: {$col->Type} " . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n2. Test Direct DB Insert:\n";
try {
    $insertId = DB::table('product_variants')->insertGetId([
        'product_id' => 10969,
        'sku' => 'TEST-DIRECT-INSERT',
        'name' => 'Test Direct Insert',
        'is_active' => true,
        'is_default' => false,
        'position' => 999,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✅ Direct insert SUCCESS! ID: $insertId\n";

    // Clean up
    DB::table('product_variants')->where('id', $insertId)->delete();
    echo "  🧹 Cleaned up test record\n";
} catch (\Exception $e) {
    echo "  ❌ Direct insert FAILED: " . $e->getMessage() . "\n";
}

echo "\n3. Test Eloquent Create:\n";
try {
    $variant = ProductVariant::create([
        'product_id' => 10969,
        'sku' => 'TEST-ELOQUENT-CREATE',
        'name' => 'Test Eloquent Create',
        'is_active' => true,
        'is_default' => false,
        'position' => 999,
    ]);
    echo "  ✅ Eloquent create SUCCESS! ID: {$variant->id}\n";

    // Clean up
    $variant->delete();
    echo "  🧹 Cleaned up test record\n";
} catch (\Exception $e) {
    echo "  ❌ Eloquent create FAILED: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
