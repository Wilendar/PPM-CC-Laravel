<?php
// HOTFIX #2 Verification: Test CategoryMappingsValidator numeric sort fix
require_once 'bootstrap/app.php';

use App\Services\CategoryMappingsValidator;
use Illuminate\Support\Facades\Log;

echo "=== HOTFIX #2 VERIFICATION: CategoryMappingsValidator Numeric Sort ===\n\n";

$validator = app(CategoryMappingsValidator::class);

// Test data: Numeric IDs that fail alphabetical sort
$testData = [
    'ui' => [
        'selected' => [100, 103, 42, 44, 94, 104, 92],
        'primary' => 100,
    ],
    'mappings' => [
        '100' => 9,
        '103' => 15,
        '42' => 800,
        '44' => 981,
        '94' => 983,
        '104' => 985,
        '92' => 2350,
    ],
    'metadata' => [
        'last_updated' => '2025-11-18T14:00:00+00:00',
        'source' => 'manual',
    ],
];

echo "Test Data:\n";
echo "  - UI selected: " . implode(', ', $testData['ui']['selected']) . "\n";
echo "  - Mappings keys: " . implode(', ', array_keys($testData['mappings'])) . "\n\n";

echo "Expected Result: Validation PASSES (numeric sort: 42 < 92 < 94 < 100 < 103 < 104)\n";
echo "OLD Bug: Validation FAILS (alphabetical sort: \"100\" < \"103\" < \"104\" < \"42\" < \"44\" < \"92\" < \"94\")\n\n";

try {
    $result = $validator->validate($testData);
    echo "✅ VALIDATION PASSED!\n\n";
    echo "Result:\n";
    echo "  - UI selected: " . implode(', ', $result['ui']['selected']) . "\n";
    echo "  - Mappings keys: " . implode(', ', array_keys($result['mappings'])) . "\n\n";
    echo "🎉 HOTFIX #2 SUCCESSFUL!\n";
    echo "   - Validator now uses NUMERIC sort\n";
    echo "   - Arrays with same elements but different order now match\n";
} catch (\Exception $e) {
    echo "❌ VALIDATION FAILED!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "⚠️ HOTFIX #2 DID NOT FIX THE ISSUE\n";
    echo "   - Validator still using ALPHABETICAL sort\n";
    echo "   - Arrays with same elements but different order still fail\n";
}
