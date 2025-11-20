<?php

namespace App\Services;

use App\Jobs\PrestaShop\CategoryCreationJob;
use App\Models\Category;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Category Auto-Create Service
 *
 * ETAP_07b FAZA 3: Auto-Create Missing Categories
 *
 * Detects PrestaShop categories that don't exist in PPM and prepares them for creation.
 * This service solves the critical foreign key constraint violation when users select
 * PrestaShop categories that don't have corresponding entries in PPM's categories table.
 *
 * Business Logic:
 * 1. User selects PrestaShop category ID 800 in shop tab
 * 2. This service checks if mapping exists: shop_mappings (prestashop_id=800 → ppm_value=X)
 * 3. If no mapping → category is "missing" and needs to be created
 * 4. CategoryCreationJob creates the category in PPM + mapping
 * 5. Product save proceeds with the PPM category ID (not PrestaShop ID)
 *
 * Architecture:
 * - product_categories.category_id → PPM categories.id (NOT PrestaShop category ID)
 * - shop_mappings links PrestaShop ID to PPM ID
 * - Translation: PrestaShop ID → shop_mappings → PPM ID
 *
 * Performance:
 * - Single query to check all mappings at once
 * - Batch detection for efficiency
 * - No API calls (CategoryCreationJob handles PrestaShop API)
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_07b FAZA 3
 */
class CategoryAutoCreateService
{
    /**
     * Detect missing categories that need to be created
     *
     * Checks which PrestaShop category IDs don't have mappings in shop_mappings.
     * Missing categories will cause foreign key constraint violations if not handled.
     *
     * Algorithm:
     * 1. Get all selected PrestaShop category IDs from user selection
     * 2. Query shop_mappings for existing mappings (prestashop_id IN (...))
     * 3. Diff: selected IDs - existing mappings = missing IDs
     * 4. Return structured data for job creation
     *
     * @param array $prestashopCategoryIds PrestaShop category IDs selected by user
     * @param int $shopId Shop ID for mapping context
     * @return array ['missing' => [...], 'existing' => [...], 'mappings' => [...]]
     */
    public function detectMissingCategories(array $prestashopCategoryIds, int $shopId): array
    {
        // Validate input
        if (empty($prestashopCategoryIds)) {
            return [
                'missing' => [],
                'existing' => [],
                'mappings' => [],
            ];
        }

        // Get shop instance for validation
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            Log::error('CATEGORY AUTO-CREATE: Shop not found', [
                'shop_id' => $shopId,
                'prestashop_ids' => $prestashopCategoryIds,
            ]);

            throw new \InvalidArgumentException("Shop ID {$shopId} not found");
        }

        // Query existing mappings for this shop and these PrestaShop IDs
        $existingMappings = DB::table('shop_mappings')
            ->where('shop_id', $shopId)
            ->where('mapping_type', 'category')
            ->whereIn('prestashop_id', $prestashopCategoryIds)
            ->get(['prestashop_id', 'ppm_value', 'prestashop_value'])
            ->keyBy('prestashop_id')
            ->toArray();

        // Extract PrestaShop IDs that have mappings
        $existingPrestashopIds = array_keys($existingMappings);

        // Calculate missing PrestaShop IDs (selected - existing)
        $missingPrestashopIds = array_values(
            array_diff($prestashopCategoryIds, $existingPrestashopIds)
        );

        Log::info('CATEGORY AUTO-CREATE: Detection complete', [
            'shop_id' => $shopId,
            'shop_name' => $shop->name,
            'total_selected' => count($prestashopCategoryIds),
            'existing_count' => count($existingPrestashopIds),
            'missing_count' => count($missingPrestashopIds),
            'missing_ids' => $missingPrestashopIds,
            'existing_ids' => $existingPrestashopIds,
        ]);

        return [
            'missing' => $missingPrestashopIds,
            'existing' => $existingPrestashopIds,
            'mappings' => $existingMappings,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'version' => $shop->version,
            ],
        ];
    }

    /**
     * Create and dispatch CategoryCreationJob for missing categories
     *
     * This is a wyprzedzający (precedent) JOB that runs BEFORE ProductSyncJob.
     * It ensures all selected PrestaShop categories exist in PPM before product save.
     *
     * Workflow:
     * 1. Call detectMissingCategories() to identify what needs to be created
     * 2. If missing categories exist → dispatch CategoryCreationJob
     * 3. CategoryCreationJob creates categories + mappings
     * 4. CategoryCreationJob chains to ProductSyncJob
     * 5. ProductSyncJob proceeds with all categories available
     *
     * Dependency Chain:
     * ```
     * CategoryCreationJob (this method dispatches)
     *   ↓
     * ProductSyncJob (chained by CategoryCreationJob)
     * ```
     *
     * Error Handling:
     * - If no missing categories → returns null (no job needed)
     * - If job dispatch fails → throws exception
     * - Job failures handled by queue retry mechanism
     *
     * @param array $prestashopCategoryIds PrestaShop category IDs selected by user
     * @param int $shopId Shop ID
     * @param int $productId Product ID (for chaining to ProductSyncJob)
     * @param int $userId User ID (for audit trail)
     * @return CategoryCreationJob|null Dispatched job or null if no missing categories
     * @throws \InvalidArgumentException If shop not found
     */
    public function createMissingCategoriesJob(
        array $prestashopCategoryIds,
        int $shopId,
        int $productId,
        int $userId
    ): ?CategoryCreationJob {
        // Detect missing categories
        $detection = $this->detectMissingCategories($prestashopCategoryIds, $shopId);

        // If no missing categories, no job needed
        if (empty($detection['missing'])) {
            Log::info('CATEGORY AUTO-CREATE: No missing categories, skipping job', [
                'shop_id' => $shopId,
                'product_id' => $productId,
                'prestashop_ids' => $prestashopCategoryIds,
                'all_exist' => true,
            ]);

            return null;
        }

        // Dispatch CategoryCreationJob (wyprzedzający)
        $job = CategoryCreationJob::dispatch(
            $detection['missing'],
            $shopId,
            $productId,
            $userId
        )->onQueue('sync');

        Log::info('CATEGORY AUTO-CREATE: CategoryCreationJob dispatched', [
            'shop_id' => $shopId,
            'product_id' => $productId,
            'missing_count' => count($detection['missing']),
            'missing_ids' => $detection['missing'],
            'job_dispatched' => true,
        ]);

        return $job;
    }

    /**
     * Translate PrestaShop category IDs to PPM category IDs using mappings
     *
     * This method is used by ProductCategoryManager to convert PrestaShop IDs
     * to PPM IDs before inserting into product_categories table.
     *
     * Usage:
     * ```php
     * $prestashopIds = [800, 801, 802];
     * $ppmIds = $service->translateToPpmIds($prestashopIds, $shopId);
     * // Returns: [150, 151, 152] (PPM category IDs)
     * ```
     *
     * @param array $prestashopCategoryIds PrestaShop category IDs
     * @param int $shopId Shop ID for mapping context
     * @return array PPM category IDs (same order as input)
     * @throws \RuntimeException If any PrestaShop ID has no mapping
     */
    public function translateToPpmIds(array $prestashopCategoryIds, int $shopId): array
    {
        if (empty($prestashopCategoryIds)) {
            return [];
        }

        // Get mappings
        $mappings = DB::table('shop_mappings')
            ->where('shop_id', $shopId)
            ->where('mapping_type', 'category')
            ->whereIn('prestashop_id', $prestashopCategoryIds)
            ->pluck('ppm_value', 'prestashop_id')
            ->toArray();

        // Check for missing mappings
        $missingIds = array_diff($prestashopCategoryIds, array_keys($mappings));
        if (!empty($missingIds)) {
            Log::error('CATEGORY AUTO-CREATE: Cannot translate - missing mappings', [
                'shop_id' => $shopId,
                'prestashop_ids' => $prestashopCategoryIds,
                'missing_ids' => array_values($missingIds),
            ]);

            throw new \RuntimeException(
                'Cannot translate PrestaShop category IDs to PPM IDs: missing mappings for IDs ' .
                implode(', ', $missingIds)
            );
        }

        // Translate PrestaShop IDs to PPM IDs (preserve order)
        $ppmIds = [];
        foreach ($prestashopCategoryIds as $prestashopId) {
            $ppmIds[] = (int) $mappings[$prestashopId];
        }

        Log::debug('CATEGORY AUTO-CREATE: Translation complete', [
            'shop_id' => $shopId,
            'prestashop_ids' => $prestashopCategoryIds,
            'ppm_ids' => $ppmIds,
        ]);

        return $ppmIds;
    }

    /**
     * Check if category exists in PPM categories table
     *
     * This is a simple existence check used for validation.
     *
     * @param int $ppmCategoryId PPM category ID
     * @return bool True if category exists
     */
    public function categoryExistsInPpm(int $ppmCategoryId): bool
    {
        return Category::where('id', $ppmCategoryId)->exists();
    }

    /**
     * Get mapping for PrestaShop category
     *
     * Returns the shop_mappings record for a specific PrestaShop category ID.
     * Used to check if mapping exists before attempting translation.
     *
     * @param int $prestashopCategoryId PrestaShop category ID
     * @param int $shopId Shop ID
     * @return array|null Mapping data or null if not found
     */
    public function getMappingForPrestashopCategory(int $prestashopCategoryId, int $shopId): ?array
    {
        $mapping = DB::table('shop_mappings')
            ->where('shop_id', $shopId)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $prestashopCategoryId)
            ->first();

        return $mapping ? (array) $mapping : null;
    }

    /**
     * Validate category hierarchy before creation
     *
     * Checks if parent categories exist and are valid.
     * Used by CategoryCreationJob to ensure hierarchy integrity.
     *
     * @param int $parentPrestashopId Parent PrestaShop category ID
     * @param int $shopId Shop ID
     * @return bool True if parent is valid (exists or is root)
     * @throws \RuntimeException If parent is missing
     */
    public function validateCategoryHierarchy(int $parentPrestashopId, int $shopId): bool
    {
        // Root category (ID 2 in PrestaShop) is always valid
        if ($parentPrestashopId === 2) {
            return true;
        }

        // Check if parent mapping exists
        $parentMapping = $this->getMappingForPrestashopCategory($parentPrestashopId, $shopId);

        if (!$parentMapping) {
            throw new \RuntimeException(
                "Parent category (PrestaShop ID {$parentPrestashopId}) has no mapping. " .
                "Parent categories must be created before child categories."
            );
        }

        // Check if parent exists in PPM
        $parentPpmId = (int) $parentMapping['ppm_value'];
        if (!$this->categoryExistsInPpm($parentPpmId)) {
            throw new \RuntimeException(
                "Parent category (PPM ID {$parentPpmId}) doesn't exist in categories table. " .
                "Data integrity issue detected."
            );
        }

        return true;
    }
}
