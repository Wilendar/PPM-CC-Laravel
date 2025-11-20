$validator = app(\App\Services\CategoryMappingsValidator::class);

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

echo "Testing validator with numeric IDs that fail alphabetical sort:\n";
echo "UI selected: " . implode(', ', $testData['ui']['selected']) . "\n";
echo "Mappings keys: " . implode(', ', array_keys($testData['mappings'])) . "\n\n";

try {
    $result = $validator->validate($testData);
    echo "✅ VALIDATION PASSED! HOTFIX #2 SUCCESSFUL!\n";
} catch (\Exception $e) {
    echo "❌ VALIDATION FAILED: " . $e->getMessage() . "\n";
}
