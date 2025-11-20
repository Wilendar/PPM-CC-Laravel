<?php

namespace App\Services;

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\CategoryMapper;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * CategoryMappingsConverter Service
 *
 * Bidirectional converter between UI format and canonical Option A format
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
 *
 * Conversions:
 * - UI format ↔ Canonical Option A
 * - PrestaShop IDs list ↔ Canonical Option A (with CategoryMapper lookup)
 *
 * Usage:
 * ```php
 * $converter = app(CategoryMappingsConverter::class);
 *
 * // UI → Canonical (with shop context for mappings lookup)
 * $canonical = $converter->fromUiFormat($uiData, $shop);
 *
 * // PrestaShop IDs → Canonical (with shop context for reverse lookup)
 * $canonical = $converter->fromPrestaShopFormat($psData, $shop);
 *
 * // Canonical → UI (for Livewire)
 * $uiData = $converter->toUiFormat($canonical);
 *
 * // Canonical → PrestaShop IDs list (for sync)
 * $psIds = $converter->toPrestaShopIdsList($canonical);
 * ```
 *
 * @package App\Services
 * @version 2.0
 * @since 2025-11-18 (Category Mappings Architecture Refactoring)
 */
class CategoryMappingsConverter
{
    /**
     * @var CategoryMapper
     */
    private CategoryMapper $categoryMapper;

    /**
     * @var CategoryMappingsValidator
     */
    private CategoryMappingsValidator $validator;

    /**
     * Constructor
     */
    public function __construct(
        CategoryMapper $categoryMapper,
        CategoryMappingsValidator $validator
    ) {
        $this->categoryMapper = $categoryMapper;
        $this->validator = $validator;
    }

    /**
     * Convert UI format to canonical Option A format
     *
     * UI format: {"selected": [100, 103, 42], "primary": 100}
     * Option A: {"ui": {...}, "mappings": {"100": 9, "103": 15, "42": 800}, "metadata": {...}}
     *
     * Requires shop context to lookup PrestaShop IDs via CategoryMapper
     *
     * @param array $uiData UI format data
     * @param PrestaShopShop $shop Shop instance for mapping lookup
     * @return array Canonical Option A format
     * @throws InvalidArgumentException If validation fails
     */
    public function fromUiFormat(array $uiData, PrestaShopShop $shop): array
    {
        // Extract UI fields
        $selected = $uiData['selected'] ?? [];
        $primary = $uiData['primary'] ?? null;

        // Validate and sanitize selected
        if (!is_array($selected)) {
            throw new InvalidArgumentException('UI format: selected must be an array');
        }

        $selected = array_map('intval', $selected);
        $selected = array_values(array_unique($selected));

        // Validate primary
        if ($primary !== null) {
            $primary = (int) $primary;
            if (!in_array($primary, $selected)) {
                throw new InvalidArgumentException('UI format: primary must be in selected array');
            }
        } else {
            $primary = $selected[0] ?? null;
        }

        // Lookup PrestaShop IDs via CategoryMapper
        $mappings = [];
        foreach ($selected as $ppmId) {
            $prestashopId = $this->categoryMapper->mapToPrestaShop($ppmId, $shop);

            if ($prestashopId === null) {
                Log::warning('CategoryMappingsConverter: PPM category not mapped to PrestaShop', [
                    'ppm_id' => $ppmId,
                    'shop_id' => $shop->id,
                ]);

                // Skip unmapped categories (don't include in mappings)
                continue;
            }

            $mappings[(string) $ppmId] = $prestashopId;
        }

        // Construct canonical format
        $canonical = [
            'ui' => [
                'selected' => $selected,
                'primary' => $primary,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Validate before returning
        return $this->validator->validate($canonical);
    }

    /**
     * Convert PrestaShop IDs format to canonical Option A format
     *
     * PrestaShop format: [9, 15, 800] (array of PrestaShop category IDs)
     * Option A: {"ui": {"selected": [100, 103, 42], ...}, "mappings": {...}, "metadata": {...}}
     *
     * UPDATED 2025-11-20 (ETAP_07b FAZA 1):
     * - Uses mapOrCreateFromPrestaShop() instead of mapFromPrestaShop()
     * - Automatically creates missing categories WITH HIERARCHY
     * - No longer skips unmapped PrestaShop categories
     * - Preserves PrestaShop parent→child relationships in PPM
     *
     * Requires shop context for category creation and mapping
     *
     * @param array $psData Array of PrestaShop category IDs
     * @param PrestaShopShop $shop Shop instance for mapping/creation
     * @return array Canonical Option A format
     * @throws InvalidArgumentException If validation fails
     * @throws \Exception If category creation fails
     */
    public function fromPrestaShopFormat(array $psData, PrestaShopShop $shop): array
    {
        // Validate input
        if (!is_array($psData)) {
            throw new InvalidArgumentException('PrestaShop format: data must be an array of IDs');
        }

        $prestashopIds = array_map('intval', $psData);
        $prestashopIds = array_values(array_unique($prestashopIds));

        // Auto-create missing categories with hierarchy via CategoryMapper
        $selected = [];
        $mappings = [];

        foreach ($prestashopIds as $psId) {
            // Skip PrestaShop root categories (1 = Home, 2 = Root catalog)
            if (in_array($psId, [1, 2], true)) {
                Log::debug('[ETAP_07b] Skipping PrestaShop root category', [
                    'prestashop_id' => $psId,
                    'shop_id' => $shop->id,
                    'reason' => 'PrestaShop roots (1, 2) are not created in PPM',
                ]);
                continue;
            }

            try {
                // ETAP_07b: Auto-create missing categories WITH HIERARCHY
                $ppmId = $this->categoryMapper->mapOrCreateFromPrestaShop($psId, $shop);

                $selected[] = $ppmId;
                $mappings[(string) $ppmId] = $psId;

                Log::debug('[ETAP_07b] Category mapped/created from PrestaShop', [
                    'prestashop_id' => $psId,
                    'ppm_id' => $ppmId,
                    'shop_id' => $shop->id,
                ]);
            } catch (\Exception $e) {
                // Category creation failed - log error but continue with other categories
                Log::error('[ETAP_07b] Failed to map/create category from PrestaShop', [
                    'prestashop_id' => $psId,
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Continue processing other categories
                continue;
            }
        }

        // Primary = first selected
        $primary = $selected[0] ?? null;

        // Construct canonical format
        $canonical = [
            'ui' => [
                'selected' => $selected,
                'primary' => $primary,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'pull',
            ],
        ];

        Log::info('[ETAP_07b] Categories converted from PrestaShop format with auto-creation', [
            'shop_id' => $shop->id,
            'prestashop_ids_count' => count($prestashopIds),
            'ppm_categories_created_mapped' => count($selected),
            'roots_skipped' => count(array_intersect($prestashopIds, [1, 2])),
        ]);

        // Validate before returning
        return $this->validator->validate($canonical);
    }

    /**
     * Convert pivot table data (PPM IDs) to canonical Option A format
     *
     * Pivot format: [59, 87] (array of PPM category IDs from product_categories WHERE shop_id = X)
     * Option A: {"ui": {"selected": [59, 87], ...}, "mappings": {"59": Y, "87": Z}, "metadata": {...}}
     *
     * Similar to fromPrestaShopFormat() but starts from PPM IDs (FRESH user selections)
     * Requires shop context to lookup PrestaShop IDs via CategoryMapper
     *
     * @param array $ppmCategoryIds PPM category IDs from pivot table
     * @param PrestaShopShop $shop Shop instance for mapping lookup
     * @return array Canonical Option A format
     * @throws InvalidArgumentException If validation fails
     */
    public function fromPivotData(array $ppmCategoryIds, PrestaShopShop $shop): array
    {
        // Validate input
        if (!is_array($ppmCategoryIds)) {
            throw new InvalidArgumentException('Pivot format: data must be an array of PPM category IDs');
        }

        $selected = array_map('intval', $ppmCategoryIds);
        $selected = array_values(array_unique($selected));

        // Lookup PrestaShop IDs via CategoryMapper
        $mappings = [];
        $prestashopIds = [];

        foreach ($selected as $ppmId) {
            $prestashopId = $this->categoryMapper->mapToPrestaShop($ppmId, $shop);

            if ($prestashopId !== null) {
                $mappings[(string) $ppmId] = (int) $prestashopId;
                $prestashopIds[] = (int) $prestashopId;
            } else {
                Log::warning('[CATEGORY CACHE] Unmapped category detected during cache sync', [
                    'ppm_category_id' => $ppmId,
                    'shop_id' => $shop->id,
                ]);

                // Skip unmapped categories (don't include in mappings)
                continue;
            }
        }

        // Primary = first selected
        $primary = !empty($selected) ? (int) $selected[0] : null;

        // Construct canonical format
        $canonical = [
            'ui' => [
                'selected' => $selected,
                'primary' => $primary,
            ],
            'mappings' => $mappings,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual', // User action via form
            ],
        ];

        // Validate before returning
        return $this->validator->validate($canonical);
    }

    /**
     * Convert canonical Option A to UI format
     *
     * Option A: {"ui": {"selected": [100, 103, 42], "primary": 100}, ...}
     * UI format: {"selected": [100, 103, 42], "primary": 100}
     *
     * Extracts only UI-relevant fields for Livewire components
     *
     * @param array $canonical Canonical Option A format
     * @return array UI format (selected + primary)
     */
    public function toUiFormat(array $canonical): array
    {
        // Validate input
        if (!isset($canonical['ui'])) {
            throw new InvalidArgumentException('Canonical format: missing ui section');
        }

        return [
            'selected' => $canonical['ui']['selected'] ?? [],
            'primary' => $canonical['ui']['primary'] ?? null,
        ];
    }

    /**
     * Convert canonical Option A to UI format with PrestaShop IDs
     *
     * FIX 2025-11-20 (ETAP_07b Fix #2): UI needs PrestaShop IDs, not PPM IDs
     *
     * Option A: {"ui": {"selected": [1,36,41], "primary": 1}, "mappings": {"1": 1, "36": 2, "41": 12}, ...}
     * UI format: {"selected": [1, 2, 12], "primary": 1}
     *
     * @param array $canonical Canonical Option A format
     * @return array UI format with PrestaShop IDs
     */
    public function toUiFormatPrestaShop(array $canonical): array
    {
        return [
            'selected' => $this->toPrestaShopIdsList($canonical),
            'primary' => $this->getPrimaryPrestaShopId($canonical),
        ];
    }

    /**
     * Convert canonical Option A to PrestaShop IDs list
     *
     * Option A: {"mappings": {"100": 9, "103": 15, "42": 800}, ...}
     * PrestaShop list: [9, 15, 800]
     *
     * Extracts PrestaShop IDs for sync operations
     *
     * @param array $canonical Canonical Option A format
     * @return array Array of PrestaShop category IDs
     */
    public function toPrestaShopIdsList(array $canonical): array
    {
        // Validate input
        if (!isset($canonical['mappings'])) {
            throw new InvalidArgumentException('Canonical format: missing mappings section');
        }

        // Extract PrestaShop IDs (values of mappings)
        $prestashopIds = array_values($canonical['mappings']);

        // Filter out placeholder values (0 = not mapped yet)
        $prestashopIds = array_filter($prestashopIds, fn($id) => $id > 0);

        // Return as indexed array
        return array_values($prestashopIds);
    }

    /**
     * Get primary PrestaShop category ID
     *
     * Resolves primary category from UI to PrestaShop ID
     *
     * @param array $canonical Canonical Option A format
     * @return int|null Primary PrestaShop category ID or null
     */
    public function getPrimaryPrestaShopId(array $canonical): ?int
    {
        $primaryPpmId = $canonical['ui']['primary'] ?? null;

        if ($primaryPpmId === null) {
            return null;
        }

        // Lookup in mappings
        $prestashopId = $canonical['mappings'][(string) $primaryPpmId] ?? null;

        // Filter out placeholder (0 = not mapped)
        if ($prestashopId === 0) {
            return null;
        }

        return $prestashopId;
    }

    /**
     * Check if canonical format has valid mappings
     *
     * Valid = at least one mapping with PrestaShop ID > 0
     *
     * @param array $canonical Canonical Option A format
     * @return bool True if has valid mappings
     */
    public function hasValidMappings(array $canonical): bool
    {
        $mappings = $canonical['mappings'] ?? [];

        if (empty($mappings)) {
            return false;
        }

        // Check if at least one mapping has PrestaShop ID > 0
        foreach ($mappings as $psId) {
            if ($psId > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of unmapped categories
     *
     * Counts categories in UI.selected that don't have valid PrestaShop mapping
     *
     * @param array $canonical Canonical Option A format
     * @return int Count of unmapped categories
     */
    public function getUnmappedCount(array $canonical): int
    {
        $selected = $canonical['ui']['selected'] ?? [];
        $mappings = $canonical['mappings'] ?? [];

        $unmappedCount = 0;

        foreach ($selected as $ppmId) {
            $prestashopId = $mappings[(string) $ppmId] ?? 0;

            if ($prestashopId === 0) {
                $unmappedCount++;
            }
        }

        return $unmappedCount;
    }

    /**
     * Update mappings with new PrestaShop IDs (after CategoryMapper update)
     *
     * Refreshes mappings based on current CategoryMapper state
     *
     * @param array $canonical Canonical Option A format
     * @param PrestaShopShop $shop Shop instance for mapping lookup
     * @return array Updated canonical format
     */
    public function refreshMappings(array $canonical, PrestaShopShop $shop): array
    {
        $selected = $canonical['ui']['selected'] ?? [];

        // Refresh mappings from CategoryMapper
        $mappings = [];
        foreach ($selected as $ppmId) {
            $prestashopId = $this->categoryMapper->mapToPrestaShop($ppmId, $shop);

            if ($prestashopId !== null) {
                $mappings[(string) $ppmId] = $prestashopId;
            } else {
                // Keep placeholder for unmapped
                $mappings[(string) $ppmId] = 0;
            }
        }

        // Update canonical structure
        $canonical['mappings'] = $mappings;
        $canonical['metadata']['last_updated'] = now()->toIso8601String();
        $canonical['metadata']['source'] = 'refresh';

        return $canonical;
    }
}
