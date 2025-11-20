<?php
// Verify FIX #12 Migration - Check category_mappings Option A structure
use Illuminate\Support\Facades\DB;

echo "=== FIX #12 Migration Verification ===\n\n";

// Count total records with category_mappings
$total = DB::table('product_shop_data')
    ->whereNotNull('category_mappings')
    ->count();

echo "Total records with category_mappings: $total\n\n";

// Get 5 samples
$samples = DB::table('product_shop_data')
    ->whereNotNull('category_mappings')
    ->select('id', 'product_id', 'shop_id', 'category_mappings')
    ->limit(5)
    ->get();

echo "=== Sample records (5) ===\n";
foreach ($samples as $sample) {
    echo "\nProduct Shop Data ID: {$sample->id}\n";
    echo "Product ID: {$sample->product_id} | Shop ID: {$sample->shop_id}\n";

    $mappings = json_decode($sample->category_mappings, true);

    if (isset($mappings['ui'], $mappings['mappings'], $mappings['metadata'])) {
        echo "✅ Format: Option A (correct)\n";
        echo "  - UI selected: " . count($mappings['ui']['selected'] ?? []) . " categories\n";
        echo "  - Mappings: " . count($mappings['mappings'] ?? []) . " pairs\n";
        echo "  - Metadata source: " . ($mappings['metadata']['source'] ?? 'N/A') . "\n";
        echo "  - Metadata updated: " . ($mappings['metadata']['last_updated'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Format: INCORRECT (not Option A)\n";
        echo "  - Raw: " . substr($sample->category_mappings, 0, 200) . "...\n";
    }
}

echo "\n=== Validation Summary ===\n";
$validCount = DB::table('product_shop_data')
    ->whereNotNull('category_mappings')
    ->whereRaw("JSON_EXTRACT(category_mappings, '$.ui') IS NOT NULL")
    ->whereRaw("JSON_EXTRACT(category_mappings, '$.mappings') IS NOT NULL")
    ->whereRaw("JSON_EXTRACT(category_mappings, '$.metadata') IS NOT NULL")
    ->count();

echo "Valid Option A records: $validCount / $total\n";
echo "Success rate: " . ($total > 0 ? round(($validCount / $total) * 100, 2) : 0) . "%\n";

if ($validCount === $total) {
    echo "\n✅ ALL RECORDS CONVERTED SUCCESSFULLY!\n";
} else {
    $invalidCount = $total - $validCount;
    echo "\n⚠️ WARNING: $invalidCount records NOT in Option A format!\n";
}
