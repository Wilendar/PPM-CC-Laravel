<?php
// HOTFIX #2: CategoryMappingsValidator numeric sort comparison
// Problem: sort() on strings causes alphabetical ordering ("100" < "42")
// Solution: Use SORT_NUMERIC flag + intval() for proper numeric comparison

echo "=== HOTFIX #2: CategoryMappingsValidator numeric sort ===\n\n";

$file = 'app/Services/CategoryMappingsValidator.php';

// Read file
$content = file_get_contents($file);

// Find and replace the broken comparison logic (lines 55-68)
$oldCode = <<<'PHP'
        // Custom validation: mappings keys must match selected
        $selectedIds = array_map('strval', $data['ui']['selected']);
        $mappingKeys = array_keys($data['mappings']);

        sort($selectedIds);
        sort($mappingKeys);

        if ($selectedIds !== $mappingKeys) {
            throw new InvalidArgumentException(
                'Mappings keys must match selected categories. ' .
                'Selected: [' . implode(', ', $selectedIds) . '], ' .
                'Mappings: [' . implode(', ', $mappingKeys) . ']'
            );
        }
PHP;

$newCode = <<<'PHP'
        // Custom validation: mappings keys must match selected
        // FIX #12 HOTFIX #2: Use numeric comparison (not alphabetical string sort)
        $selectedIds = array_map('intval', $data['ui']['selected']);
        $mappingKeys = array_map('intval', array_keys($data['mappings']));

        sort($selectedIds, SORT_NUMERIC);
        sort($mappingKeys, SORT_NUMERIC);

        if ($selectedIds !== $mappingKeys) {
            throw new InvalidArgumentException(
                'Mappings keys must match selected categories. ' .
                'Selected: [' . implode(', ', $selectedIds) . '], ' .
                'Mappings: [' . implode(', ', $mappingKeys) . ']'
            );
        }
PHP;

if (strpos($content, $oldCode) === false) {
    echo "❌ Old code NOT FOUND\n";
    echo "Searching for: array_map('strval', ...\n";

    // Show current line 55-70 for debugging
    $lines = explode("\n", $content);
    echo "\nCurrent lines 54-70:\n";
    for ($i = 53; $i < 70 && $i < count($lines); $i++) {
        echo ($i + 1) . ": " . $lines[$i] . "\n";
    }

    exit(1);
}

$content = str_replace($oldCode, $newCode, $content);

// Save
file_put_contents($file, $content);

echo "✅ Validation comparison logic FIXED:\n";
echo "   - Changed: array_map('strval', ...) → array_map('intval', ...)\n";
echo "   - Changed: sort() → sort(..., SORT_NUMERIC)\n";
echo "   - Result: Numeric comparison (100 > 42) instead of alphabetical (\"100\" < \"42\")\n\n";

echo "✅ HOTFIX #2 COMPLETE\n";
