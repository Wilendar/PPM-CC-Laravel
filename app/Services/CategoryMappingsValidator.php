<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Category Mappings Validator Service
 *
 * Validates category_mappings JSON structure for ProductShopData
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md
 * Version: 2.0 (2025-11-18)
 *
 * @package App\Services
 */
class CategoryMappingsValidator
{
    /**
     * Validate category_mappings structure
     *
     * @param array $data Category mappings data
     * @return array Validated data (same as input if valid)
     * @throws InvalidArgumentException On validation failure
     */
    public function validate(array $data): array
    {
        // Validate basic structure
        $validator = Validator::make($data, [
            'ui' => 'required|array',
            'ui.selected' => 'required|array|min:1|max:10',
            'ui.selected.*' => 'required|integer|min:1',
            'ui.primary' => 'nullable|integer|min:1',

            'mappings' => 'required|array|min:1|max:10',
            'mappings.*' => 'required|integer|min:1',

            'metadata' => 'nullable|array',
            'metadata.last_updated' => 'nullable|date_format:Y-m-d\TH:i:sP',
            'metadata.source' => 'nullable|in:manual,pull,sync,migration',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                'Invalid category_mappings structure: ' . $validator->errors()->first()
            );
        }

        // Custom validation: primary must be in selected
        if (isset($data['ui']['primary']) && !in_array($data['ui']['primary'], $data['ui']['selected'])) {
            throw new InvalidArgumentException('Primary category must be in selected categories');
        }

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

        return $data;
    }

    /**
     * Validate and sanitize category_mappings (lenient mode)
     *
     * Attempts to fix minor issues instead of throwing exceptions
     *
     * @param array $data Category mappings data
     * @return array Sanitized data
     */
    public function validateAndSanitize(array $data): array
    {
        // Ensure required keys exist
        if (!isset($data['ui'])) {
            $data['ui'] = [];
        }

        if (!isset($data['mappings'])) {
            $data['mappings'] = [];
        }

        // Ensure selected is array
        if (!isset($data['ui']['selected']) || !is_array($data['ui']['selected'])) {
            $data['ui']['selected'] = [];
        }

        // Sanitize selected (remove duplicates, ensure integers)
        $data['ui']['selected'] = array_values(array_unique(array_map('intval', $data['ui']['selected'])));

        // Limit to 10 categories
        if (count($data['ui']['selected']) > 10) {
            $data['ui']['selected'] = array_slice($data['ui']['selected'], 0, 10);
        }

        // Sanitize primary (must be in selected)
        if (isset($data['ui']['primary'])) {
            $primary = (int) $data['ui']['primary'];
            if (!in_array($primary, $data['ui']['selected'])) {
                $data['ui']['primary'] = $data['ui']['selected'][0] ?? null;
            } else {
                $data['ui']['primary'] = $primary;
            }
        } else {
            $data['ui']['primary'] = $data['ui']['selected'][0] ?? null;
        }

        // Sanitize mappings (ensure string keys, integer values)
        $sanitizedMappings = [];
        foreach ($data['mappings'] as $key => $value) {
            $sanitizedMappings[(string) $key] = (int) $value;
        }
        $data['mappings'] = $sanitizedMappings;

        // Ensure metadata exists
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }

        // Set last_updated if missing
        if (!isset($data['metadata']['last_updated'])) {
            $data['metadata']['last_updated'] = now()->toIso8601String();
        }

        // Set source if missing
        if (!isset($data['metadata']['source'])) {
            $data['metadata']['source'] = 'unknown';
        }

        // Final strict validation
        return $this->validate($data);
    }

    /**
     * Check if data is valid without throwing exception
     *
     * @param array $data Category mappings data
     * @return bool True if valid, false otherwise
     */
    public function isValid(array $data): bool
    {
        try {
            $this->validate($data);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Detect category_mappings format
     *
     * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
     *
     * Detects which format the data is in:
     * - 'option_a' - Canonical format (ui + mappings + metadata)
     * - 'ui_format' - Legacy UI format (selected + primary)
     * - 'prestashop_format' - Legacy PrestaShop format (id => id)
     * - 'unknown' - Cannot determine format
     *
     * @param mixed $data Data to analyze
     * @return string Format identifier
     */
    public function detectFormat(mixed $data): string
    {
        // Not an array
        if (!is_array($data)) {
            return 'unknown';
        }

        // Empty array
        if (empty($data)) {
            return 'unknown';
        }

        // Check for Option A (canonical format)
        if (isset($data['ui']) && isset($data['mappings'])) {
            return 'option_a';
        }

        // Check for UI format (selected/primary)
        if (isset($data['selected']) || isset($data['primary'])) {
            return 'ui_format';
        }

        // Check for PrestaShop format (numeric keys mapping to themselves)
        // Example: {"9": 9, "15": 15}
        $isPrestaShopFormat = true;
        foreach ($data as $key => $value) {
            // Skip metadata keys
            if (in_array($key, ['last_updated', 'source'])) {
                continue;
            }

            // All keys must be numeric strings, values must be integers
            if (!is_numeric($key) || !is_int($value)) {
                $isPrestaShopFormat = false;
                break;
            }
        }

        if ($isPrestaShopFormat && count($data) > 0) {
            return 'prestashop_format';
        }

        return 'unknown';
    }

    /**
     * Convert legacy format to Option A canonical format
     *
     * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
     *
     * Converts old formats to canonical Option A structure:
     * - UI format → Option A
     * - PrestaShop format → Option A (mappings only, empty UI)
     * - Unknown format → Empty structure
     *
     * @param mixed $data Legacy data
     * @return array Canonical Option A format
     * @throws InvalidArgumentException If conversion fails
     */
    public function convertLegacyFormat(mixed $data): array
    {
        // Already in Option A format
        $format = $this->detectFormat($data);

        if ($format === 'option_a') {
            // Validate and return (might need sanitization)
            return $this->validateAndSanitize($data);
        }

        // Convert based on detected format
        switch ($format) {
            case 'ui_format':
                return $this->convertFromUiFormat($data);

            case 'prestashop_format':
                return $this->convertFromPrestaShopFormat($data);

            case 'unknown':
            default:
                // Return empty structure
                return $this->getEmptyStructure();
        }
    }

    /**
     * Convert from UI format to Option A
     *
     * UI format: {"selected": [1,2,3], "primary": 1}
     * Option A: {"ui": {"selected": [1,2,3], "primary": 1}, "mappings": {}, "metadata": {...}}
     *
     * Note: Mappings will be empty (need to be filled by CategoryMappingsConverter with shop context)
     *
     * @param array $data UI format data
     * @return array Option A format
     */
    private function convertFromUiFormat(array $data): array
    {
        $selected = $data['selected'] ?? [];
        $primary = $data['primary'] ?? null;

        // Ensure selected is array of integers
        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = array_map('intval', $selected);
        $selected = array_values(array_unique($selected));

        // Ensure primary is in selected
        if ($primary !== null) {
            $primary = (int) $primary;
            if (!in_array($primary, $selected)) {
                $primary = $selected[0] ?? null;
            }
        } else {
            $primary = $selected[0] ?? null;
        }

        // Create empty mappings (will need to be filled later)
        $mappings = [];
        foreach ($selected as $ppmId) {
            $mappings[(string) $ppmId] = 0; // Placeholder (0 = not mapped yet)
        }

        return [
            'ui' => [
                'selected' => $selected,
                'primary' => $primary,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'migration_ui_format',
            ],
        ];
    }

    /**
     * Convert from PrestaShop format to Option A
     *
     * PrestaShop format: {"9": 9, "15": 15, "800": 800}
     * Option A: {"ui": {"selected": [], "primary": null}, "mappings": {"?": 9, "?": 15}, "metadata": {...}}
     *
     * Note: Cannot determine PPM IDs from PrestaShop IDs alone (need CategoryMapper lookup)
     * UI will be empty until user selects categories again
     *
     * @param array $data PrestaShop format data
     * @return array Option A format
     */
    private function convertFromPrestaShopFormat(array $data): array
    {
        // Extract PrestaShop IDs
        $prestashopIds = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key) && is_int($value)) {
                $prestashopIds[] = $value;
            }
        }

        // Cannot determine PPM IDs without CategoryMapper context
        // Return structure with empty UI and mappings with PrestaShop IDs as values
        // (will need to be resolved later by CategoryMappingsConverter)
        $mappings = [];
        foreach ($prestashopIds as $psId) {
            // Use negative key to indicate "needs PPM ID lookup"
            $mappings["_ps_{$psId}"] = $psId;
        }

        return [
            'ui' => [
                'selected' => [],
                'primary' => null,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'migration_prestashop_format',
            ],
        ];
    }

    /**
     * Get empty canonical structure
     *
     * @return array Empty Option A structure
     */
    private function getEmptyStructure(): array
    {
        return [
            'ui' => [
                'selected' => [],
                'primary' => null,
            ],
            'mappings' => [],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'empty',
            ],
        ];
    }
}
