<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PrestaShopShop;
use App\Models\CategoryPreview;
use App\Models\ShopMapping;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\JobProgressService;
use App\Events\PrestaShop\CategoryPreviewReady;

/**
 * AnalyzeMissingCategories Job
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - Jobs Layer
 *
 * Purpose: Analyze which categories used by products DON'T exist in PPM
 *
 * Workflow:
 * 1. Fetch products from PrestaShop API (id + categories)
 * 2. Extract ALL category IDs (id_default_category + associations)
 * 3. Check which categories exist in PPM (via shop_mappings)
 * 4. Missing IDs = All Category IDs - Existing in PPM
 * 5. If missing IDs found:
 *    → Fetch category details from PrestaShop API
 *    → Build hierarchical tree (sort by level_depth)
 *    → Store in CategoryPreview table
 *    → Dispatch CategoryPreviewReady event
 * 6. If NO missing categories:
 *    → Proceed directly to BulkImportProducts
 *
 * Features:
 * - Background queue processing
 * - PrestaShop API integration (products + categories)
 * - ShopMapping validation (existing categories)
 * - Hierarchical tree building
 * - Event broadcasting dla UI notification
 * - Comprehensive error handling
 *
 * Usage:
 * ```php
 * AnalyzeMissingCategories::dispatch($productIds, $shop, $jobId);
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 3D
 */
class AnalyzeMissingCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Array of PrestaShop product IDs to analyze
     *
     * @var array
     */
    protected array $productIds;

    /**
     * PrestaShop shop instance
     *
     * @var PrestaShopShop
     */
    protected PrestaShopShop $shop;

    /**
     * Job ID (UUID) from job_progress table
     *
     * @var string
     */
    protected string $jobId;

    /**
     * Original import options dla re-dispatch BulkImportProducts
     *
     * @var array
     */
    protected array $originalImportOptions;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Timeout for the job (10 minutes)
     *
     * @var int
     */
    public int $timeout = 600;

    /**
     * Create a new job instance
     *
     * @param array $productIds PrestaShop product IDs to analyze
     * @param PrestaShopShop $shop Shop instance
     * @param string $jobId Job progress UUID
     * @param array $originalImportOptions Original import mode and options
     */
    public function __construct(
        array $productIds,
        PrestaShopShop $shop,
        string $jobId,
        array $originalImportOptions = []
    ) {
        $this->productIds = $productIds;
        $this->shop = $shop;
        $this->jobId = $jobId;
        $this->originalImportOptions = $originalImportOptions;
    }

    /**
     * Execute the job
     *
     * @param JobProgressService $progressService
     * @param PrestaShopClientFactory $clientFactory
     * @return void
     */
    public function handle(
        JobProgressService $progressService,
        PrestaShopClientFactory $clientFactory
    ): void {
        $startTime = microtime(true);

        Log::info('AnalyzeMissingCategories job started', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'product_count' => count($this->productIds),
            'job_id' => $this->jobId,
        ]);

        // ETAP_07c: Get or create JobProgress for this analysis
        $progress = \App\Models\JobProgress::where('job_id', $this->jobId)->first();
        $progressId = $progress?->id;

        // Update status to running
        if ($progressId) {
            $progressService->updateProgress($progressId, 0);
            $progressService->updateMetadata($progressId, [
                'phase' => 'extracting_categories',
                'phase_label' => 'Pobieranie kategorii z produktow',
            ]);
        }

        try {
            $client = $clientFactory->create($this->shop);

            // STEP 1: Fetch products from PrestaShop API (lightweight - only IDs + categories)
            $categoryIds = $this->extractCategoryIdsFromProducts($client);

            Log::info('Category IDs extracted from products', [
                'total_categories' => count($categoryIds),
                'category_ids' => $categoryIds,
            ]);

            // ETAP_07c: Update progress - categories extracted
            if ($progressId) {
                $progressService->updateProgress($progressId, (int)(count($this->productIds) * 0.3));
                $progressService->updateMetadata($progressId, [
                    'phase' => 'checking_existing',
                    'phase_label' => 'Sprawdzanie istniejacych kategorii',
                    'total_categories_found' => count($categoryIds),
                ]);
            }

            // STEP 2: Check which categories exist in PPM via ShopMappings
            $existingCategoryIds = $this->getExistingCategoryIds($categoryIds);

            Log::info('Existing categories found in PPM', [
                'existing_count' => count($existingCategoryIds),
                'existing_ids' => $existingCategoryIds,
            ]);

            // STEP 3: Calculate missing category IDs
            $missingCategoryIds = array_diff($categoryIds, $existingCategoryIds);

            Log::info('Missing categories detected', [
                'missing_count' => count($missingCategoryIds),
                'missing_ids' => $missingCategoryIds,
            ]);

            // ETAP_07c: Update progress - missing categories found
            if ($progressId) {
                $progressService->updateProgress($progressId, (int)(count($this->productIds) * 0.5));
                $progressService->updateMetadata($progressId, [
                    'phase' => 'analyzing_missing',
                    'phase_label' => 'Analiza brakujacych kategorii',
                    'existing_count' => count($existingCategoryIds),
                    'missing_count' => count($missingCategoryIds),
                ]);
            }

            // STEP 4: If NO missing categories → STILL create preview for user approval!
            // 🔧 FIX: User MUST approve import via modal (even when all categories exist)
            if (empty($missingCategoryIds)) {
                Log::info('No missing categories - creating empty preview for user approval', [
                    'shop_id' => $this->shop->id,
                    'job_id' => $this->jobId,
                ]);

                // Create EMPTY preview with info message + import context
                $preview = CategoryPreview::create([
                    'job_id' => $this->jobId,
                    'shop_id' => $this->shop->id,
                    'category_tree_json' => [
                        'categories' => [],
                        'total_count' => 0,
                        'max_depth' => 0,
                        'message' => 'Wszystkie kategorie już istnieją w PPM. Możesz kontynuować import produktów.',
                    ],
                    'total_categories' => 0,
                    'import_context_json' => $this->originalImportOptions,  // Store original import context
                    'status' => CategoryPreview::STATUS_PENDING,
                ]);

                // Dispatch event → Modal opens with "Ready to import" message
                event(new CategoryPreviewReady($this->jobId, $this->shop->id, $preview->id));

                // Dispatch timeout job (expire preview after 15 minutes if no user action)
                ExpirePendingCategoryPreview::dispatch($preview->id)
                    ->delay(now()->addMinutes(CategoryPreview::EXPIRATION_HOURS * 60));

                // ETAP_07c: Mark job as awaiting_user with action button
                if ($progressId) {
                    $progressService->markAwaitingUser(
                        $progressId,
                        'preview',
                        'Zobacz podglad kategorii',
                        'open_category_preview_modal',
                        ['preview_id' => $preview->id, 'shop_id' => $this->shop->id],
                        'Analiza zakonczona - wszystkie kategorie istnieja w PPM. Kliknij aby kontynuowac import.'
                    );
                }

                Log::info('Empty preview created - waiting for user approval', [
                    'preview_id' => $preview->id,
                    'job_id' => $this->jobId,
                    'expires_at' => now()->addMinutes(CategoryPreview::EXPIRATION_HOURS * 60)->toDateTimeString(),
                ]);

                return; // ✅ Wait for user approval via modal!
            }

            // ETAP_07c: Update progress - fetching category details
            if ($progressId) {
                $progressService->updateProgress($progressId, (int)(count($this->productIds) * 0.6));
                $progressService->updateMetadata($progressId, [
                    'phase' => 'fetching_details',
                    'phase_label' => 'Pobieranie szczegolowych danych kategorii',
                ]);
            }

            // STEP 5: Fetch missing category details from PrestaShop API
            $categories = $this->fetchCategoryDetails($client, $missingCategoryIds);

            Log::info('Missing category details fetched', [
                'fetched_count' => count($categories),
            ]);

            // STEP 6: Sort by level_depth (parents first - CRITICAL for hierarchy)
            usort($categories, function ($a, $b) {
                return ($a['level_depth'] ?? 0) <=> ($b['level_depth'] ?? 0);
            });

            // ETAP_07c: Update progress - building tree
            if ($progressId) {
                $progressService->updateProgress($progressId, (int)(count($this->productIds) * 0.8));
                $progressService->updateMetadata($progressId, [
                    'phase' => 'building_tree',
                    'phase_label' => 'Budowanie drzewa kategorii',
                    'categories_to_process' => count($categories),
                ]);
            }

            // STEP 7: Build hierarchical tree structure
            $tree = $this->buildCategoryTree($categories);

            // ETAP_07c: Update progress - storing preview
            if ($progressId) {
                $progressService->updateProgress($progressId, (int)(count($this->productIds) * 0.95));
                $progressService->updateMetadata($progressId, [
                    'phase' => 'storing_preview',
                    'phase_label' => 'Zapisywanie podgladu',
                ]);
            }

            // STEP 8: Store preview in database
            $preview = $this->storePreview($tree, count($categories));

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            $depths = array_column($categories, 'level_depth');
            Log::info('Category preview created successfully', [
                'preview_id' => $preview->id,
                'total_categories' => count($categories),
                'max_depth' => !empty($depths) ? max($depths) : 0,
                'execution_time_ms' => $executionTime,
            ]);

            // STEP 9: Dispatch events dla UI notification
            // Laravel event (broadcasting to WebSocket if configured)
            event(new CategoryPreviewReady($this->jobId, $this->shop->id, $preview->id));

            // NOTE: Livewire events DO NOT WORK from queue jobs!
            // CategoryPreview is detected via polling mechanism in ProductList component (wire:poll.3s)
            // See: ProductList::checkForPendingCategoryPreviews()

            Log::info('CategoryPreviewReady event dispatched', [
                'preview_id' => $preview->id,
                'job_id' => $this->jobId,
                'shop_id' => $this->shop->id,
            ]);

            // Dispatch timeout job (expire preview after 15 minutes if no user action)
            ExpirePendingCategoryPreview::dispatch($preview->id)
                ->delay(now()->addMinutes(CategoryPreview::EXPIRATION_HOURS * 60));

            // ETAP_07c: Mark job as awaiting_user with action button (missing categories found)
            if ($progressId) {
                $progressService->markAwaitingUser(
                    $progressId,
                    'preview',
                    'Zobacz brakujace kategorie (' . count($missingCategoryIds) . ')',
                    'open_category_preview_modal',
                    ['preview_id' => $preview->id, 'shop_id' => $this->shop->id],
                    'Analiza zakonczona - znaleziono ' . count($missingCategoryIds) . ' brakujacych kategorii. Kliknij aby zobaczyc podglad.'
                );
            }

            Log::info('Timeout job dispatched for normal preview', [
                'preview_id' => $preview->id,
                'job_id' => $this->jobId,
                'total_missing_categories' => count($missingCategoryIds),
                'expires_at' => now()->addMinutes(CategoryPreview::EXPIRATION_HOURS * 60)->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('AnalyzeMissingCategories job failed', [
                'shop_id' => $this->shop->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => $executionTime,
            ]);

            // Update job progress to failed
            // Find JobProgress by job_id (UUID) to get progress ID (int)
            $progress = \App\Models\JobProgress::where('job_id', $this->jobId)->first();
            if ($progress) {
                $progressService->markFailed($progress->id, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Extract category IDs from products
     *
     * Fetches products from PrestaShop API and extracts all category IDs
     * (both default and associations)
     *
     * @param mixed $client PrestaShop client
     * @return array Unique category IDs
     */
    protected function extractCategoryIdsFromProducts($client): array
    {
        $categoryIds = [];

        // PrestaShop API: Filter by product IDs using OR filter
        // Format: filter[id]=[1|2|3|4]
        $idsFilter = '[' . implode('|', $this->productIds) . ']';

        $params = [
            'display' => 'full',  // PrestaShop API doesn't support associations in display param
            'filter[id]' => $idsFilter,
        ];

        Log::debug('Fetching products for category analysis', [
            'product_ids_count' => count($this->productIds),
            'filter' => $idsFilter,
        ]);

        $response = $client->getProducts($params);

        // Parse response structure
        $products = [];
        if (isset($response['products']) && is_array($response['products'])) {
            $products = $response['products'];
        } elseif (isset($response[0])) {
            $products = $response;
        }

        // Extract category IDs from each product
        foreach ($products as $product) {
            // Unwrap 'product' key if nested
            $productData = $product['product'] ?? $product;

            // Add default category (PrestaShop API field is 'id_category_default' not 'id_default_category')
            if (isset($productData['id_category_default'])) {
                $categoryIds[] = (int) $productData['id_category_default'];
            }

            // Add associated categories
            if (isset($productData['associations']['categories'])) {
                foreach ($productData['associations']['categories'] as $category) {
                    if (isset($category['id'])) {
                        $categoryIds[] = (int) $category['id'];
                    }
                }
            }
        }

        // Remove duplicates and return
        return array_unique($categoryIds);
    }

    /**
     * Get existing category IDs from PPM (via ShopMappings)
     *
     * @param array $categoryIds PrestaShop category IDs to check
     * @return array Existing category IDs
     */
    protected function getExistingCategoryIds(array $categoryIds): array
    {
        // FIX 2025-12-08: Must check BOTH:
        // 1. ShopMapping exists for this prestashop_id
        // 2. AND the actual Category in PPM (ppm_value) exists in categories table
        // Without this, orphaned mappings (pointing to deleted categories) would show as "existing"

        $mappings = ShopMapping::where('shop_id', $this->shop->id)
            ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->whereIn('prestashop_id', $categoryIds)
            ->get(['prestashop_id', 'ppm_value']);

        if ($mappings->isEmpty()) {
            return [];
        }

        // Get PPM category IDs from mappings
        $ppmCategoryIds = $mappings->pluck('ppm_value')->unique()->toArray();

        // Check which PPM categories actually exist in the database
        $existingPpmIds = \App\Models\Category::whereIn('id', $ppmCategoryIds)
            ->pluck('id')
            ->toArray();

        // Return only prestashop_ids where the PPM category actually exists
        return $mappings
            ->filter(fn($mapping) => in_array($mapping->ppm_value, $existingPpmIds))
            ->pluck('prestashop_id')
            ->toArray();
    }

    /**
     * Fetch category details from PrestaShop API
     *
     * @param mixed $client PrestaShop client
     * @param array $categoryIds Missing category IDs
     * @return array Category details
     */
    protected function fetchCategoryDetails($client, array $categoryIds): array
    {
        $categories = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $response = $client->getCategory($categoryId);

                // Unwrap 'category' key if nested
                $categoryData = $response['category'] ?? $response;

                // Extract essential fields dla tree building
                $categories[] = [
                    'prestashop_id' => (int) $categoryData['id'],
                    'id_parent' => (int) ($categoryData['id_parent'] ?? 0),
                    'level_depth' => (int) ($categoryData['level_depth'] ?? 0),
                    'name' => $this->extractMultilangValue($categoryData['name'] ?? []),
                    'description' => $this->extractMultilangValue($categoryData['description'] ?? []),
                    'active' => (bool) ($categoryData['active'] ?? true),
                ];

                Log::debug('Category fetched', [
                    'category_id' => $categoryId,
                    'name' => $categories[count($categories) - 1]['name'],
                ]);

            } catch (\Exception $e) {
                Log::warning('Failed to fetch category', [
                    'category_id' => $categoryId,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other categories
            }
        }

        return $categories;
    }

    /**
     * Extract value from PrestaShop multilang array or string
     *
     * PrestaShop format can be:
     * - Array: [{id: 1, value: "Name"}, {id: 2, value: "Name EN"}]
     * - String: "Name" (when fetching single category with display=full)
     *
     * @param array|string $multilangValue
     * @return string
     */
    protected function extractMultilangValue(array|string $multilangValue): string
    {
        // If already a string, return it directly
        if (is_string($multilangValue)) {
            return $multilangValue;
        }

        // If empty array, return empty string
        if (empty($multilangValue)) {
            return '';
        }

        // If array, extract first language value
        $firstValue = reset($multilangValue);
        return $firstValue['value'] ?? (is_string($firstValue) ? $firstValue : '');
    }

    /**
     * Build hierarchical category tree
     *
     * Converts flat array of categories to nested tree structure
     *
     * CRITICAL FIX: Use proper reference-based tree building
     *
     * @param array $categories Flat array of categories (sorted by level_depth)
     * @return array Hierarchical tree
     */
    protected function buildCategoryTree(array $categories): array
    {
        // Index categories by ID
        $nodes = [];
        foreach ($categories as $category) {
            $nodes[$category['prestashop_id']] = $category;
            $nodes[$category['prestashop_id']]['children'] = [];
        }

        // Build tree by attaching children to parents
        $tree = [];
        foreach ($nodes as $id => $node) {
            $parentId = $node['id_parent'];

            // Root categories (parent_id <= 2 in PrestaShop)
            if ($parentId <= 2) {
                // Will be added to tree at the end
                continue;
            }

            // Attach to parent if exists
            if (isset($nodes[$parentId])) {
                // Add reference to parent's children
                $nodes[$parentId]['children'][] = $id;
            }
        }

        // Recursive function to build nested structure
        $buildNode = function($id) use (&$buildNode, &$nodes) {
            $node = $nodes[$id];
            $children = [];

            foreach ($node['children'] as $childId) {
                $children[] = $buildNode($childId);
            }

            $node['children'] = $children;
            return $node;
        };

        // Build tree from root nodes
        foreach ($nodes as $id => $node) {
            if ($node['id_parent'] <= 2) {
                $tree[] = $buildNode($id);
            }
        }

        return $tree;
    }

    /**
     * Store category preview in database
     *
     * @param array $tree Hierarchical category tree
     * @param int $totalCount Total category count
     * @return CategoryPreview
     */
    protected function storePreview(array $tree, int $totalCount): CategoryPreview
    {
        // FIX: Handle empty tree case - calculate max_depth safely
        $flattened = $this->flattenTree($tree);
        $depths = array_column($flattened, 'level_depth');
        $maxDepth = !empty($depths) ? max($depths) : 0;

        return CategoryPreview::create([
            'job_id' => $this->jobId,
            'shop_id' => $this->shop->id,
            'category_tree_json' => [
                'categories' => $tree,
                'total_count' => $totalCount,
                'max_depth' => $maxDepth,
            ],
            'total_categories' => $totalCount,
            'import_context_json' => $this->originalImportOptions,  // Store original import context
            'status' => CategoryPreview::STATUS_PENDING,
        ]);
    }

    /**
     * Flatten tree for max_depth calculation
     *
     * @param array $tree
     * @return array
     */
    protected function flattenTree(array $tree): array
    {
        $flattened = [];

        foreach ($tree as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $flattened[] = $node;

            if (!empty($children)) {
                $flattened = array_merge($flattened, $this->flattenTree($children));
            }
        }

        return $flattened;
    }

    /**
     * Dispatch BulkImportProducts job (when no missing categories)
     *
     * @return void
     */
    protected function dispatchProductImport(): void
    {
        // 🔧 FIX: Flatten nested options structure
        // originalImportOptions has: ['mode' => 'category', 'options' => ['category_id' => 12, ...]]
        // BulkImportProducts expects: $mode = 'category', $options = ['category_id' => 12, 'product_ids' => [...]]
        $mode = $this->originalImportOptions['mode'] ?? 'individual';
        $options = array_merge(
            $this->originalImportOptions['options'] ?? [],  // ✅ FIXED: Use inner options, not whole array
            [
                'product_ids' => $this->productIds,
                'skip_category_analysis' => true,  // 🔧 FIX: Prevent infinite loop!
            ]
        );

        BulkImportProducts::dispatch(
            $this->shop,
            $mode,
            $options,  // ✅ FIXED: Pass flattened options with skip flag
            $this->jobId
        );

        Log::info('BulkImportProducts dispatched from AnalyzeMissingCategories', [
            'shop_id' => $this->shop->id,
            'job_id' => $this->jobId,
            'mode' => $mode,
            'options' => $options,
        ]);
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeMissingCategories job failed permanently', [
            'shop_id' => $this->shop->id,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send failure notification to user
    }
}
