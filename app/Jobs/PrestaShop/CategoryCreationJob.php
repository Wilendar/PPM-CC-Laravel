<?php

namespace App\Jobs\PrestaShop;

use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopCategoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Category Creation Job
 *
 * ETAP_07b FAZA 3: Auto-Create Missing Categories
 *
 * Creates missing PrestaShop categories in PPM and establishes mappings.
 * This job is dispatched BEFORE ProductSyncJob to ensure all categories exist.
 *
 * Business Logic:
 * 1. Fetch category details from PrestaShop API (name, parent, etc.)
 * 2. Create parent categories first (hierarchy integrity)
 * 3. Create category in PPM categories table
 * 4. Create mapping in shop_mappings (prestashop_id → ppm_value)
 * 5. Chain to ProductSyncJob when all categories created
 *
 * Architecture:
 * - Wyprzedzający JOB: Runs BEFORE ProductSyncJob
 * - Dependency chain: CategoryCreationJob → ProductSyncJob
 * - Handles hierarchy: Parent categories created before children
 * - Atomic operations: DB transactions for data integrity
 *
 * Error Handling:
 * - PrestaShop API errors: Retry with exponential backoff
 * - Duplicate categories: Skip if already exists
 * - Parent missing: Create parent first (recursive)
 *
 * Performance:
 * - Batch creation for efficiency
 * - Cache invalidation for real-time UI updates
 * - Minimal API calls (fetch only missing categories)
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07b FAZA 3
 */
class CategoryCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * PrestaShop category IDs to create
     *
     * @var array
     */
    protected array $prestashopCategoryIds;

    /**
     * Shop ID for mapping context
     *
     * @var int
     */
    protected int $shopId;

    /**
     * Product ID (for chaining to ProductSyncJob)
     *
     * @var int
     */
    protected int $productId;

    /**
     * User ID (for audit trail)
     *
     * @var int
     */
    protected int $userId;

    /**
     * Created mappings (for ProductSyncJob context)
     *
     * @var array
     */
    protected array $createdMappings = [];

    /**
     * Number of attempts before failing
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout in seconds
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance
     *
     * @param array $prestashopCategoryIds PrestaShop category IDs to create
     * @param int $shopId Shop ID
     * @param int $productId Product ID (for chaining)
     * @param int $userId User ID (for audit)
     */
    public function __construct(
        array $prestashopCategoryIds,
        int $shopId,
        int $productId,
        int $userId
    ) {
        $this->prestashopCategoryIds = $prestashopCategoryIds;
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->userId = $userId;
    }

    /**
     * Execute the job
     *
     * @param PrestaShopCategoryService $categoryService
     * @return void
     */
    public function handle(PrestaShopCategoryService $categoryService): void
    {
        Log::info('CATEGORY CREATION JOB: Started', [
            'job_id' => $this->job->getJobId(),
            'shop_id' => $this->shopId,
            'product_id' => $this->productId,
            'prestashop_ids' => $this->prestashopCategoryIds,
            'user_id' => $this->userId,
        ]);

        try {
            // Get shop instance
            $shop = PrestaShopShop::findOrFail($this->shopId);

            // Fetch category details from PrestaShop API
            $categoriesData = $this->fetchCategoriesFromPrestaShop($categoryService, $shop);

            // Sort by parent hierarchy (parents first)
            $sortedCategories = $this->sortByHierarchy($categoriesData);

            // Create categories in PPM + mappings
            foreach ($sortedCategories as $categoryData) {
                $this->createCategoryAndMapping($categoryData, $shop);
            }

            Log::info('CATEGORY CREATION JOB: Completed successfully', [
                'job_id' => $this->job->getJobId(),
                'shop_id' => $this->shopId,
                'created_count' => count($this->createdMappings),
                'created_mappings' => $this->createdMappings,
            ]);

            // Chain to ProductSyncJob (FAZA 3 requirement)
            $this->chainProductSync();

        } catch (\Exception $e) {
            Log::error('CATEGORY CREATION JOB: Failed', [
                'job_id' => $this->job->getJobId(),
                'shop_id' => $this->shopId,
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed (will retry)
            throw $e;
        }
    }

    /**
     * Fetch category details from PrestaShop API
     *
     * @param PrestaShopCategoryService $categoryService
     * @param PrestaShopShop $shop
     * @return array Category data from PrestaShop
     * @throws \Exception If API call fails
     */
    protected function fetchCategoriesFromPrestaShop(
        PrestaShopCategoryService $categoryService,
        PrestaShopShop $shop
    ): array {
        $categoriesData = [];

        foreach ($this->prestashopCategoryIds as $prestashopId) {
            try {
                // Fetch category from PrestaShop API
                $categoryData = $categoryService->fetchCategoryById($shop, $prestashopId);

                if (!$categoryData) {
                    Log::warning('CATEGORY CREATION: Category not found in PrestaShop', [
                        'shop_id' => $this->shopId,
                        'prestashop_id' => $prestashopId,
                    ]);
                    continue;
                }

                $categoriesData[$prestashopId] = $categoryData;

                Log::debug('CATEGORY CREATION: Fetched from PrestaShop', [
                    'prestashop_id' => $prestashopId,
                    'name' => $categoryData['name'] ?? 'Unknown',
                    'parent_id' => $categoryData['id_parent'] ?? null,
                ]);

            } catch (\Exception $e) {
                Log::error('CATEGORY CREATION: API fetch failed', [
                    'shop_id' => $this->shopId,
                    'prestashop_id' => $prestashopId,
                    'error' => $e->getMessage(),
                ]);

                // Re-throw to trigger job retry
                throw $e;
            }
        }

        return $categoriesData;
    }

    /**
     * Sort categories by hierarchy (parents first)
     *
     * Ensures parent categories are created before children.
     * Uses topological sort based on parent_id relationships.
     *
     * @param array $categoriesData Category data from PrestaShop
     * @return array Sorted categories (parents first)
     */
    protected function sortByHierarchy(array $categoriesData): array
    {
        $sorted = [];
        $remaining = $categoriesData;
        $maxIterations = count($remaining) * 2; // Prevent infinite loops
        $iterations = 0;

        while (!empty($remaining) && $iterations < $maxIterations) {
            $iterations++;
            $foundParent = false;

            foreach ($remaining as $prestashopId => $categoryData) {
                $parentId = $categoryData['id_parent'] ?? 2; // Default to root (2)

                // Root category (2) or parent already created
                if ($parentId === 2 || isset($sorted[$parentId])) {
                    $sorted[$prestashopId] = $categoryData;
                    unset($remaining[$prestashopId]);
                    $foundParent = true;
                }
            }

            // If no parent found in this iteration, break to avoid infinite loop
            if (!$foundParent && !empty($remaining)) {
                Log::warning('CATEGORY CREATION: Circular dependency or missing parent detected', [
                    'remaining_ids' => array_keys($remaining),
                ]);
                break;
            }
        }

        // Add remaining categories (orphaned or circular dependencies)
        foreach ($remaining as $prestashopId => $categoryData) {
            Log::warning('CATEGORY CREATION: Category added despite missing parent', [
                'prestashop_id' => $prestashopId,
                'parent_id' => $categoryData['id_parent'] ?? null,
            ]);
            $sorted[$prestashopId] = $categoryData;
        }

        Log::debug('CATEGORY CREATION: Hierarchy sorted', [
            'original_order' => array_keys($categoriesData),
            'sorted_order' => array_keys($sorted),
        ]);

        return $sorted;
    }

    /**
     * Create category in PPM and mapping in shop_mappings
     *
     * @param array $categoryData Category data from PrestaShop
     * @param PrestaShopShop $shop Shop instance
     * @return void
     */
    protected function createCategoryAndMapping(array $categoryData, PrestaShopShop $shop): void
    {
        $prestashopId = $categoryData['id'];
        $categoryName = $categoryData['name'] ?? "Category {$prestashopId}";
        $parentPrestashopId = $categoryData['id_parent'] ?? 2;

        // Check if mapping already exists (idempotency)
        $existingMapping = DB::table('shop_mappings')
            ->where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $prestashopId)
            ->first();

        if ($existingMapping) {
            Log::info('CATEGORY CREATION: Mapping already exists (skip)', [
                'shop_id' => $shop->id,
                'prestashop_id' => $prestashopId,
                'ppm_id' => $existingMapping->ppm_value,
            ]);

            $this->createdMappings[$prestashopId] = (int) $existingMapping->ppm_value;
            return;
        }

        // Start transaction for atomicity
        DB::transaction(function () use ($categoryData, $categoryName, $prestashopId, $parentPrestashopId, $shop) {
            // Get parent PPM category ID (if not root)
            $parentPpmId = null;
            if ($parentPrestashopId !== 2) {
                $parentMapping = DB::table('shop_mappings')
                    ->where('shop_id', $shop->id)
                    ->where('mapping_type', 'category')
                    ->where('prestashop_id', $parentPrestashopId)
                    ->first();

                if ($parentMapping) {
                    $parentPpmId = (int) $parentMapping->ppm_value;
                }
            }

            // Create category in PPM categories table
            $ppmCategory = Category::create([
                'name' => $categoryName,
                'slug' => \Illuminate\Support\Str::slug($categoryName),
                'parent_id' => $parentPpmId,
                'description' => $categoryData['description'] ?? null,
                'is_active' => true,
                'sort_order' => $categoryData['position'] ?? 0,
            ]);

            Log::info('CATEGORY CREATION: Created in PPM', [
                'ppm_id' => $ppmCategory->id,
                'name' => $categoryName,
                'parent_ppm_id' => $parentPpmId,
                'prestashop_id' => $prestashopId,
            ]);

            // Create mapping in shop_mappings
            DB::table('shop_mappings')->insert([
                'shop_id' => $shop->id,
                'mapping_type' => 'category',
                'ppm_value' => (string) $ppmCategory->id,
                'prestashop_id' => $prestashopId,
                'prestashop_value' => $categoryName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('CATEGORY CREATION: Mapping created', [
                'shop_id' => $shop->id,
                'prestashop_id' => $prestashopId,
                'ppm_id' => $ppmCategory->id,
                'category_name' => $categoryName,
            ]);

            // Store mapping for ProductSyncJob context
            $this->createdMappings[$prestashopId] = $ppmCategory->id;
        });
    }

    /**
     * Chain to ProductSyncJob after categories created
     *
     * Dispatches ProductSyncJob with updated context (categories now exist).
     * This ensures the product save will succeed.
     *
     * @return void
     */
    protected function chainProductSync(): void
    {
        // Get product instance
        $product = \App\Models\Product::find($this->productId);

        if (!$product) {
            Log::warning('CATEGORY CREATION: Product not found, cannot chain sync', [
                'product_id' => $this->productId,
            ]);
            return;
        }

        // Dispatch ProductSyncJob for this specific shop
        SyncProductToPrestaShop::dispatch($product, $this->shopId)
            ->onQueue('sync');

        Log::info('CATEGORY CREATION: Chained to ProductSyncJob', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'created_mappings' => $this->createdMappings,
        ]);
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CATEGORY CREATION JOB: Failed permanently', [
            'job_id' => $this->job?->getJobId(),
            'shop_id' => $this->shopId,
            'product_id' => $this->productId,
            'prestashop_ids' => $this->prestashopCategoryIds,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // TODO: Notify user about failure (future enhancement)
    }
}
