<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Category Validator Service
 *
 * ETAP_07b FAZA 2: Category Validator
 *
 * Validates and compares product categories between default and shop-specific assignments.
 * Provides status indicators and detailed diff reports for UI display.
 *
 * Business Logic:
 * - "zgodne" (identical) = shop categories match default categories exactly
 * - "własne" (custom) = shop has custom categories different from default
 * - "dziedziczone" (inherited) = no shop-specific categories, inherits from default
 *
 * Features:
 * - Compare shop categories with default categories
 * - Status badge generation (zgodne/własne/dziedziczone)
 * - Detailed diff reports (added/removed/changed)
 * - Support for primary category validation
 *
 * Architecture: Works with HasCategories trait (Product model)
 * Performance: Optimized for real-time UI display
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_07b FAZA 2
 */
class CategoryValidatorService
{
    /**
     * Status constants
     */
    const STATUS_IDENTICAL = 'zgodne';      // Shop categories identical to default
    const STATUS_CUSTOM = 'własne';         // Shop has custom categories
    const STATUS_INHERITED = 'dziedziczone'; // Inherits from default (no shop-specific)

    /**
     * Compare shop categories with default categories
     *
     * Business Logic:
     * 1. Get default categories (shop_id=NULL)
     * 2. Get shop-specific categories (shop_id=X)
     * 3. Compare and determine status
     * 4. Generate detailed diff report
     *
     * Performance: Single query per category type (default + shop)
     *
     * @param Product $product Product to validate
     * @param int $shopId Shop ID to compare
     * @return array ['status' => string, 'diff' => array, 'metadata' => array]
     */
    public function compareWithDefault(Product $product, int $shopId): array
    {
        // Get default categories (shop_id=NULL)
        $defaultCategories = $product->categories()->pluck('categories.id')->toArray();
        $defaultPrimary = $product->primaryCategory()->first()?->id;

        // Get shop-specific categories (shop_id=X)
        $shopCategories = $product->categoriesForShop($shopId, false)->pluck('categories.id')->toArray();
        $shopPrimary = $product->primaryCategoryForShop($shopId)->first()?->id;

        // Determine status
        if (empty($shopCategories)) {
            // No shop-specific categories → inherits from default
            $status = self::STATUS_INHERITED;
            $diff = [];
        } elseif ($this->areCategoriesIdentical($defaultCategories, $shopCategories, $defaultPrimary, $shopPrimary)) {
            // Shop categories identical to default
            $status = self::STATUS_IDENTICAL;
            $diff = [];
        } else {
            // Shop has custom categories
            $status = self::STATUS_CUSTOM;
            $diff = $this->generateDiffReport($defaultCategories, $shopCategories, $defaultPrimary, $shopPrimary);
        }

        return [
            'status' => $status,
            'diff' => $diff,
            'metadata' => [
                'default_count' => count($defaultCategories),
                'shop_count' => count($shopCategories),
                'default_primary' => $defaultPrimary,
                'shop_primary' => $shopPrimary,
                'validated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Check if categories are identical
     *
     * Categories are identical if:
     * - Same category IDs (order doesn't matter)
     * - Same primary category
     *
     * @param array $default Default category IDs
     * @param array $shop Shop category IDs
     * @param int|null $defaultPrimary Default primary category ID
     * @param int|null $shopPrimary Shop primary category ID
     * @return bool True if identical
     */
    private function areCategoriesIdentical(
        array $default,
        array $shop,
        ?int $defaultPrimary,
        ?int $shopPrimary
    ): bool {
        // Sort both arrays for comparison (order doesn't matter)
        sort($default, SORT_NUMERIC);
        sort($shop, SORT_NUMERIC);

        // Compare category IDs
        if ($default !== $shop) {
            return false;
        }

        // Compare primary categories
        if ($defaultPrimary !== $shopPrimary) {
            return false;
        }

        return true;
    }

    /**
     * Generate detailed diff report
     *
     * Report structure:
     * {
     *   "added": [1, 2, 3],        // Categories in shop but not in default
     *   "removed": [4, 5],         // Categories in default but not in shop
     *   "primary_changed": true,   // Primary category changed
     *   "details": {
     *     "default_primary": 1,
     *     "shop_primary": 2
     *   }
     * }
     *
     * @param array $default Default category IDs
     * @param array $shop Shop category IDs
     * @param int|null $defaultPrimary Default primary category ID
     * @param int|null $shopPrimary Shop primary category ID
     * @return array Diff report
     */
    private function generateDiffReport(
        array $default,
        array $shop,
        ?int $defaultPrimary,
        ?int $shopPrimary
    ): array {
        // Calculate added/removed categories
        $added = array_values(array_diff($shop, $default));
        $removed = array_values(array_diff($default, $shop));

        // Check primary category change
        $primaryChanged = $defaultPrimary !== $shopPrimary;

        return [
            'added' => $added,
            'removed' => $removed,
            'primary_changed' => $primaryChanged,
            'details' => [
                'default_primary' => $defaultPrimary,
                'shop_primary' => $shopPrimary,
                'added_count' => count($added),
                'removed_count' => count($removed),
            ],
        ];
    }

    /**
     * Get status badge data for UI
     *
     * Returns badge configuration for frontend display:
     * - color: CSS class suffix (green/blue/gray)
     * - text: Display text
     * - icon: Icon identifier (optional)
     *
     * @param string $status Status constant (zgodne/własne/dziedziczone)
     * @return array Badge configuration
     */
    public function getStatusBadge(string $status): array
    {
        return match ($status) {
            self::STATUS_IDENTICAL => [
                'color' => 'green',
                'text' => 'Zgodne z domyślnymi',
                'icon' => 'check-circle',
                'description' => 'Kategorie tego sklepu są identyczne z kategoriami domyślnymi',
            ],
            self::STATUS_CUSTOM => [
                'color' => 'blue',
                'text' => 'Własne kategorie',
                'icon' => 'adjustments',
                'description' => 'Ten sklep ma własne, niestandardowe kategorie',
            ],
            self::STATUS_INHERITED => [
                'color' => 'gray',
                'text' => 'Dziedziczone',
                'icon' => 'arrow-down',
                'description' => 'Brak własnych kategorii - używa domyślnych',
            ],
            default => [
                'color' => 'gray',
                'text' => 'Nieznany',
                'icon' => 'question',
                'description' => 'Nie można określić statusu kategorii',
            ],
        };
    }

    /**
     * Get human-readable diff summary for tooltip
     *
     * Generates tooltip text summarizing category differences:
     * "Dodano: Kategoria A, Kategoria B | Usunięto: Kategoria C | Zmieniono główną"
     *
     * @param array $diff Diff report from compareWithDefault()
     * @param Product $product Product instance (for category name lookup)
     * @return string|null Tooltip text or null if no diff
     */
    public function getDiffTooltip(array $diff, Product $product): ?string
    {
        if (empty($diff)) {
            return null;
        }

        $parts = [];

        // Added categories
        if (!empty($diff['added'])) {
            $names = $this->getCategoryNames($diff['added']);
            $parts[] = 'Dodano: ' . implode(', ', $names);
        }

        // Removed categories
        if (!empty($diff['removed'])) {
            $names = $this->getCategoryNames($diff['removed']);
            $parts[] = 'Usunięto: ' . implode(', ', $names);
        }

        // Primary category changed
        if ($diff['primary_changed'] ?? false) {
            $defaultPrimary = $diff['details']['default_primary'] ?? null;
            $shopPrimary = $diff['details']['shop_primary'] ?? null;

            if ($defaultPrimary && $shopPrimary) {
                $defaultName = $this->getCategoryNames([$defaultPrimary])[0] ?? "ID {$defaultPrimary}";
                $shopName = $this->getCategoryNames([$shopPrimary])[0] ?? "ID {$shopPrimary}";
                $parts[] = "Zmieniono główną: {$defaultName} → {$shopName}";
            } elseif ($shopPrimary) {
                $shopName = $this->getCategoryNames([$shopPrimary])[0] ?? "ID {$shopPrimary}";
                $parts[] = "Ustawiono główną: {$shopName}";
            }
        }

        return empty($parts) ? null : implode(' | ', $parts);
    }

    /**
     * Get category names by IDs
     *
     * Performance: Single query for all IDs
     *
     * @param array $categoryIds Category IDs
     * @return array Category names indexed by ID
     */
    private function getCategoryNames(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        return \App\Models\Category::whereIn('id', $categoryIds)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Validate category consistency across all shops for product
     *
     * Checks if product has consistent categories across all shops.
     * Useful for bulk validation and reporting.
     *
     * @param Product $product Product to validate
     * @return array ['consistent' => bool, 'shops' => array]
     */
    public function validateAllShops(Product $product): array
    {
        $shops = \App\Models\PrestaShopShop::where('is_active', true)->get();
        $results = [];
        $allIdentical = true;

        foreach ($shops as $shop) {
            $comparison = $this->compareWithDefault($product, $shop->id);
            $results[$shop->id] = [
                'shop_name' => $shop->name,
                'status' => $comparison['status'],
                'diff' => $comparison['diff'],
            ];

            if ($comparison['status'] !== self::STATUS_IDENTICAL && $comparison['status'] !== self::STATUS_INHERITED) {
                $allIdentical = false;
            }
        }

        return [
            'consistent' => $allIdentical,
            'shops' => $results,
            'total_shops' => count($shops),
            'validated_at' => now()->toIso8601String(),
        ];
    }
}
