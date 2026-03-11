<?php

namespace App\Services\PrestaShop;

use App\Models\Category;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\CategoryMapper;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Category Transformer for PrestaShop API
 *
 * ETAP_07 FAZA 1D - Data Layer
 *
 * Transforms PPM Category model to PrestaShop API format
 *
 * Features:
 * - Hierarchical category structure support
 * - Parent category mapping
 * - Multilingual field handling
 * - Version-specific formatting (PS 8.x vs 9.x)
 * - Validation before transformation
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 1D
 */
class CategoryTransformer
{
    /**
     * Constructor with dependency injection
     *
     * @param CategoryMapper $categoryMapper
     */
    public function __construct(
        private readonly CategoryMapper $categoryMapper
    ) {}

    /**
     * Transform Category model to PrestaShop API format
     *
     * @param Category $category PPM Category instance
     * @param BasePrestaShopClient $client PrestaShop API client
     * @return array PrestaShop category data structure
     * @throws InvalidArgumentException On validation failure
     */
    public function transformForPrestaShop(Category $category, BasePrestaShopClient $client): array
    {
        // Validate category before transformation
        $this->validateCategory($category);

        $shop = $client->getShop();

        // Get default language ID (PrestaShop default: 1)
        $defaultLangId = 1;

        // Get parent category ID (mapped to PrestaShop)
        $prestashopParentId = $this->getParentCategoryId($category, $shop);

        // Build PrestaShop category structure
        $prestashopCategory = [
            'category' => [
                // Multilingual fields
                'name' => $this->buildMultilangField($category->name, $defaultLangId),
                'description' => $this->buildMultilangField(
                    $category->description ?? '',
                    $defaultLangId
                ),
                'link_rewrite' => $this->buildMultilangField(
                    $category->slug ?? \Illuminate\Support\Str::slug($category->name),
                    $defaultLangId
                ),

                // SEO fields
                'meta_title' => $this->buildMultilangField(
                    $category->meta_title ?? $category->name,
                    $defaultLangId
                ),
                'meta_description' => $this->buildMultilangField(
                    $category->meta_description ?? '',
                    $defaultLangId
                ),
                'meta_keywords' => $this->buildMultilangField(
                    $category->meta_keywords ?? '',
                    $defaultLangId
                ),

                // Parent category relationship
                'id_parent' => $prestashopParentId,

                // Status and visibility
                'active' => $category->is_active ? 1 : 0,

                // Position in category tree
                'position' => $category->sort_order ?? 0,

                // Display settings
                'is_root_category' => $category->parent_id === null ? 1 : 0,
            ]
        ];

        // Version-specific adjustments
        if ($client->getVersion() === '9') {
            $prestashopCategory = $this->applyVersion9Adjustments($prestashopCategory);
        }

        Log::info('Category transformed for PrestaShop', [
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'prestashop_version' => $client->getVersion(),
            'parent_id_prestashop' => $prestashopParentId,
        ]);

        return $prestashopCategory;
    }

    /**
     * Get parent category ID for PrestaShop
     *
     * @param Category $category Category instance
     * @param \App\Models\PrestaShopShop $shop Shop instance
     * @return int PrestaShop parent category ID
     */
    private function getParentCategoryId(Category $category, $shop): int
    {
        // Root category → PrestaShop Home category (id = 2)
        if ($category->parent_id === null) {
            return 2; // PrestaShop Home category
        }

        // Try to map parent category
        $prestashopParentId = $this->categoryMapper->mapToPrestaShop($category->parent_id, $shop);

        if ($prestashopParentId) {
            return $prestashopParentId;
        }

        // Fallback: If parent not mapped, use Home category
        Log::warning('Parent category not mapped, using Home', [
            'category_id' => $category->id,
            'parent_id' => $category->parent_id,
            'shop_id' => $shop->id,
        ]);

        return 2; // PrestaShop Home category
    }

    /**
     * Build multilingual field structure for PrestaShop
     *
     * PrestaShop format: [['id' => 1, 'value' => 'Text']]
     *
     * @param string $value Field value
     * @param int $languageId Language ID
     * @return array Multilingual structure
     */
    private function buildMultilangField(string $value, int $languageId = 1): array
    {
        return [
            [
                'id' => $languageId,
                'value' => $value,
            ]
        ];
    }

    /**
     * Apply PrestaShop 9.x specific adjustments
     *
     * @param array $categoryData Category data
     * @return array Adjusted category data
     */
    private function applyVersion9Adjustments(array $categoryData): array
    {
        // PrestaShop 9.x requires additional fields or different structure
        // Currently no specific adjustments needed, but ready for future changes

        Log::debug('Applied PrestaShop 9.x adjustments for category');

        return $categoryData;
    }

    /**
     * Validate category before transformation
     *
     * @param Category $category Category instance
     * @throws InvalidArgumentException On validation failure
     */
    private function validateCategory(Category $category): void
    {
        if (empty($category->name)) {
            throw new InvalidArgumentException("Category name is required for PrestaShop sync (category ID: {$category->id})");
        }

        if (empty($category->slug)) {
            throw new InvalidArgumentException("Category slug is required for PrestaShop sync (category ID: {$category->id})");
        }

        // Validate category level (PrestaShop supports deep hierarchies)
        if ($category->level > 10) {
            Log::warning('Category level exceeds recommended depth', [
                'category_id' => $category->id,
                'level' => $category->level,
            ]);
        }
    }

    /**
     * Transform PrestaShop API category data to PPM format
     *
     * ETAP_07 FAZA 2A.1 - Reverse Transformation (PrestaShop → PPM)
     *
     * Converts PrestaShop category structure to PPM Category model format.
     * Handles multilingual fields, parent category mapping, and hierarchy.
     *
     * PrestaShop category structure example:
     * [
     *     'id' => 7,
     *     'id_parent' => 2,
     *     'name' => [['id' => 1, 'value' => 'Kategoria PL'], ['id' => 2, 'value' => 'Category EN']],
     *     'description' => [...],
     *     'active' => '1',
     *     'position' => 3,
     *     'level_depth' => 2
     * ]
     *
     * @param array $prestashopCategory PrestaShop API category data
     * @param \App\Models\PrestaShopShop $shop Shop instance
     * @return array PPM Category format (ready for create/update)
     */
    public function transformToPPM(array $prestashopCategory, $shop): array
    {
        Log::debug('CategoryTransformer: transformToPPM CALLED', [
            'prestashop_category_id' => data_get($prestashopCategory, 'id'),
            'shop_id' => $shop->id,
            'category_keys' => array_keys($prestashopCategory),
        ]);

        try {
            // Extract multilingual fields (language ID 1 = Polish, 2 = English)
            $namePL = $this->extractMultilangValue($prestashopCategory, 'name', 1);
            $nameEN = $this->extractMultilangValue($prestashopCategory, 'name', 2);
            $descriptionPL = $this->extractMultilangValue($prestashopCategory, 'description', 1);
            $descriptionEN = $this->extractMultilangValue($prestashopCategory, 'description', 2);

            // Map parent category (recursive mapping)
            // FIX 2025-12-22: DO NOT hardcode id=2! Find "Wszystko" dynamically
            $parentId = null;

            // Helper: Find "Wszystko" category dynamically (level=1 = child of root "Baza")
            $getWszystkoId = function () {
                $wszystko = Category::where('name', 'Wszystko')
                    ->where('level', 1)
                    ->first();
                return $wszystko?->id;
            };

            if (isset($prestashopCategory['id_parent'])) {
                $prestashopParentId = (int) $prestashopCategory['id_parent'];

                // PrestaShop root categories:
                // id_parent = 1: Root of all categories → PPM: null (root)
                // id_parent = 2: Home category (Wszystko) → PPM: "Wszystko" (level=1)
                // id_parent > 2: Specific category → Map via CategoryMapper

                if ($prestashopParentId === 2) {
                    // PrestaShop Home (id=2) → PPM "Wszystko" (level=1)
                    // Categories with id_parent=2 in PrestaShop are children of "Home/Wszystko"
                    $parentId = $getWszystkoId();

                    Log::debug('PrestaShop id_parent=2 mapped to PPM "Wszystko"', [
                        'prestashop_category_id' => $prestashopCategory['id'],
                        'shop_id' => $shop->id,
                        'wszystko_id' => $parentId,
                    ]);
                } elseif ($prestashopParentId > 2) {
                    // Map parent category from PrestaShop ID to PPM ID
                    $parentId = $this->categoryMapper->mapFromPrestaShop($prestashopParentId, $shop);

                    if ($parentId === null) {
                        // Fallback to "Wszystko" if parent not mapped
                        $parentId = $getWszystkoId();

                        Log::warning('PrestaShop parent category not mapped to PPM - using Wszystko fallback', [
                            'prestashop_category_id' => $prestashopCategory['id'],
                            'prestashop_parent_id' => $prestashopParentId,
                            'shop_id' => $shop->id,
                            'fallback_parent_id' => $parentId,
                        ]);
                    }
                }
                // id_parent = 1 → parentId remains null (root in PPM hierarchy, but will get default in import)
            }

            // Build PPM category data
            $ppmCategory = [
                // Identifiers
                'prestashop_category_id' => (int) ($prestashopCategory['id'] ?? 0),

                // FIX 2025-12-09: Include original PrestaShop parent ID for root detection
                'prestashop_parent_id' => (int) ($prestashopCategory['id_parent'] ?? 0),

                // Names (multilingual)
                'name' => $namePL ?? 'Unnamed Category',
                'name_en' => $nameEN,

                // Descriptions (multilingual)
                'description' => $descriptionPL,
                'description_en' => $descriptionEN,

                // Parent category mapping (null for root categories)
                'parent_id' => $parentId,

                // Status (PrestaShop uses '0'/'1' strings, convert to bool)
                'is_active' => $this->convertPrestaShopBoolean($prestashopCategory['active'] ?? '1'),

                // Position in category tree
                'sort_order' => isset($prestashopCategory['position']) ? (int) $prestashopCategory['position'] : 0,

                // 🔧 FIX: DON'T set level manually - Category model auto-calculates from parent_id!
                // Category::boot() creating event calls setLevelAndPath() which sets level based on parent
                // If we force-set level here, it will be overwritten anyway, causing confusion
                // Let the model handle hierarchy automatically for correct parent->child relationships

                // Auto-generate slug from Polish name
                'slug' => $this->generateSlug($namePL ?? 'unnamed-category'),

                // SEO fields (extract from multilingual)
                'meta_title' => $this->extractMultilangValue($prestashopCategory, 'meta_title', 1),
                'meta_description' => $this->extractMultilangValue($prestashopCategory, 'meta_description', 1),
                'meta_keywords' => $this->extractMultilangValue($prestashopCategory, 'meta_keywords', 1),

                // Timestamps (preserve PrestaShop dates)
                'created_at' => $prestashopCategory['date_add'] ?? now(),
                'updated_at' => $prestashopCategory['date_upd'] ?? now(),
            ];

            Log::info('Category transformed from PrestaShop to PPM', [
                'prestashop_category_id' => $ppmCategory['prestashop_category_id'],
                'name' => $ppmCategory['name'],
                'shop_id' => $shop->id,
                'parent_mapped' => $parentId !== null,
                'parent_id' => $parentId,
            ]);

            return $ppmCategory;

        } catch (\Exception $e) {
            Log::error('Category transformation from PrestaShop failed', [
                'prestashop_category_id' => data_get($prestashopCategory, 'id'),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new InvalidArgumentException(
                "Failed to transform PrestaShop category to PPM: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Extract multilingual field value from PrestaShop structure
     *
     * PrestaShop multilingual format: [['id' => 1, 'value' => 'Text PL'], ['id' => 2, 'value' => 'Text EN']]
     *
     * @param array $prestashopCategory PrestaShop category data
     * @param string $fieldName Field name (e.g., 'name', 'description')
     * @param int $languageId Language ID (1 = Polish, 2 = English)
     * @return string|null Field value or null if not found
     */
    private function extractMultilangValue(array $prestashopCategory, string $fieldName, int $languageId): ?string
    {
        $field = $prestashopCategory[$fieldName] ?? null;

        // If field doesn't exist, return null
        if ($field === null) {
            return null;
        }

        // If field is string (single language mode), return as-is
        if (is_string($field)) {
            return $field;
        }

        // If field is array (multilingual mode), find matching language
        if (is_array($field)) {
            foreach ($field as $langData) {
                if (isset($langData['id']) && (int) $langData['id'] === $languageId) {
                    return $langData['value'] ?? null;
                }
            }
        }

        // Language not found
        return null;
    }

    /**
     * Convert PrestaShop boolean string to PHP boolean
     *
     * PrestaShop uses '0'/'1' strings for boolean values
     *
     * @param mixed $value PrestaShop boolean value
     * @return bool PHP boolean
     */
    private function convertPrestaShopBoolean(mixed $value): bool
    {
        // Handle string '0'/'1'
        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        if ($value === '0' || $value === 0 || $value === false) {
            return false;
        }

        // Default to true for categories (active by default)
        return true;
    }

    /**
     * Generate URL-friendly slug from category name
     *
     * Uses Laravel Str::slug() helper for consistency
     *
     * @param string $name Category name
     * @return string URL-friendly slug
     */
    private function generateSlug(string $name): string
    {
        return \Illuminate\Support\Str::slug($name);
    }
}
