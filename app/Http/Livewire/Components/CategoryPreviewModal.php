<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use App\Models\CategoryPreview;
use App\Jobs\PrestaShop\BulkCreateCategories;

/**
 * CategoryPreviewModal Component
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - UI Layer
 *
 * Purpose: Display hierarchical category tree modal for user approval
 *
 * Workflow:
 * 1. Listen for 'show-category-preview' event (dispatched by AnalyzeMissingCategories)
 * 2. Load CategoryPreview from database
 * 3. Display hierarchical tree with checkboxes
 * 4. Allow user to select/deselect categories
 * 5. On approval → dispatch BulkCreateCategories job
 * 6. On rejection → mark preview as rejected
 *
 * Features:
 * - Modal dialog with enterprise styling
 * - Hierarchical tree rendering (recursive component)
 * - Select All / Deselect All functionality
 * - Auto-select all categories by default
 * - Approve / Reject actions
 * - Preview expiration handling (1h)
 * - Loading states during approval
 * - Error handling and notifications
 *
 * Usage:
 * <livewire:components.category-preview-modal />
 *
 * Events:
 * - Listens: 'show-category-preview' (from AnalyzeMissingCategories job)
 * - Dispatches: 'success', 'error', 'info', 'warning' (notifications)
 *
 * @package App\Http\Livewire\Components
 * @version 1.0
 * @since ETAP_07 FAZA 3D
 */
class CategoryPreviewModal extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Modal visibility state
     *
     * @var bool
     */
    public bool $isOpen = false;

    /**
     * CategoryPreview ID
     *
     * @var int|null
     */
    public ?int $previewId = null;

    /**
     * Hierarchical category tree
     *
     * Format: [
     *   {
     *     prestashop_id: 1,
     *     name: "Category Name",
     *     level_depth: 0,
     *     is_active: true,
     *     children: [...]
     *   },
     *   ...
     * ]
     *
     * @var array
     */
    public array $categoryTree = [];

    /**
     * Selected category IDs (PrestaShop IDs)
     *
     * @var array
     */
    public array $selectedCategoryIds = [];

    /**
     * Approval in progress flag
     *
     * @var bool
     */
    public bool $isApproving = false;

    /**
     * Shop ID for existing category detection
     *
     * @var int|null
     */
    public ?int $shopId = null;

    /**
     * Shop name for display
     *
     * @var string
     */
    public string $shopName = '';

    /**
     * Total category count
     *
     * @var int
     */
    public int $totalCount = 0;

    /**
     * Skip categories option (import products without categories)
     *
     * @var bool
     */
    public bool $skipCategories = false;

    /**
     * Source PrestaShop category name (where products are imported from)
     *
     * @var string|null
     */
    public ?string $sourceCategoryName = null;

    /**
     * Target PPM category name (where products will be assigned)
     *
     * @var string|null
     */
    public ?string $targetCategoryName = null;

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Show modal with category preview
     *
     * Triggered by AnalyzeMissingCategories job via CategoryPreviewReady event
     *
     * @param int $previewId CategoryPreview ID
     */
    #[On('show-category-preview')]
    public function show(int $previewId): void
    {
        // DEBUG: List all public methods to verify approve() is accessible
        $publicMethods = array_filter(
            get_class_methods($this),
            fn($method) => !str_starts_with($method, '__') && !str_starts_with($method, 'get')
        );

        Log::info('🎯 CategoryPreviewModal::show() CALLED', [
            'component_id' => $this->getId(),
            'preview_id' => $previewId,
            'is_open_before' => $this->isOpen,
            'public_methods_count' => count($publicMethods),
            'has_approve_method' => method_exists($this, 'approve'),
            'has_selectAll_method' => method_exists($this, 'selectAll'),
            'has_close_method' => method_exists($this, 'close'),
            'all_public_methods' => $publicMethods,
        ]);

        try {
            // Load preview with shop relationship
            $preview = CategoryPreview::with('shop')->find($previewId);

            if (!$preview) {
                Log::warning('CategoryPreviewModal: Preview not found', [
                    'preview_id' => $previewId,
                ]);

                $this->dispatch('error', message: 'Preview nie został znaleziony');
                return;
            }

            // Check if preview expired
            if ($preview->isExpired()) {
                Log::warning('CategoryPreviewModal: Preview expired', [
                    'preview_id' => $previewId,
                    'expires_at' => $preview->expires_at->toDateTimeString(),
                ]);

                $this->dispatch('error', message: 'Preview wygasł. Rozpocznij import ponownie.');
                return;
            }

            // Validate business rules
            $errors = $preview->validateBusinessRules();
            if (!empty($errors)) {
                Log::error('CategoryPreviewModal: Preview validation failed', [
                    'preview_id' => $previewId,
                    'errors' => $errors,
                ]);

                $this->dispatch('error', message: 'Preview zawiera błędy: ' . implode(', ', $errors));
                return;
            }

            // Load preview data
            $this->previewId = $previewId;
            $this->shopId = $preview->shop_id;
            $this->categoryTree = $preview->category_tree_json['categories'] ?? [];
            $this->totalCount = $preview->total_categories;
            $this->shopName = $preview->shop->name ?? 'Unknown Shop';

            // Mark existing categories in tree
            $this->categoryTree = $this->checkExistingCategories($this->categoryTree);

            // Auto-select only NEW categories by default (existing are disabled)
            $this->selectedCategoryIds = $this->extractNewCategoryIds($this->categoryTree);

            // Load category mapping info (source PrestaShop → target PPM)
            $this->loadCategoryMappingInfo($preview);

            // Open modal
            $this->isOpen = true;

            Log::info('CategoryPreviewModal: Opened successfully', [
                'preview_id' => $previewId,
                'shop_name' => $this->shopName,
                'total_categories' => $this->totalCount,
                'selected_count' => count($this->selectedCategoryIds),
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Failed to open', [
                'preview_id' => $previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas otwierania preview: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Close modal and reset state
     */
    public function close(): void
    {
        $this->reset([
            'isOpen',
            'previewId',
            'shopId',
            'categoryTree',
            'selectedCategoryIds',
            'shopName',
            'totalCount',
            'isApproving',
            'skipCategories',
            'sourceCategoryName',
            'targetCategoryName',
        ]);

        Log::info('CategoryPreviewModal: Closed');
    }

    /**
     * Toggle category selection (individual checkbox)
     *
     * CRITICAL FIX: wire:model doesn't work in nested Blade components
     * Solution: Use wire:click with manual toggle
     *
     * @param int $categoryId PrestaShop category ID
     * @return void
     */
    public function toggleCategory(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategoryIds, true)) {
            // Remove from selection
            $this->selectedCategoryIds = array_values(
                array_filter($this->selectedCategoryIds, fn($id) => $id !== $categoryId)
            );
        } else {
            // Add to selection
            $this->selectedCategoryIds[] = $categoryId;
        }

        Log::debug('CategoryPreviewModal: toggleCategory', [
            'category_id' => $categoryId,
            'selected_count' => count($this->selectedCategoryIds),
        ]);
    }

    /**
     * Check if category is selected (for :checked attribute)
     *
     * @param int $categoryId
     * @return bool
     */
    public function isCategorySelected(int $categoryId): bool
    {
        return in_array($categoryId, $this->selectedCategoryIds, true);
    }

    /**
     * Select all NEW categories (skip existing)
     */
    public function selectAll(): void
    {
        $this->selectedCategoryIds = $this->extractNewCategoryIds($this->categoryTree);

        Log::info('CategoryPreviewModal: Selected all new categories', [
            'count' => count($this->selectedCategoryIds),
        ]);
    }

    /**
     * Deselect all categories
     */
    public function deselectAll(): void
    {
        $this->selectedCategoryIds = [];

        Log::info('CategoryPreviewModal: Deselected all categories');
    }

    /**
     * Toggle skip categories option
     */
    public function toggleSkipCategories(): void
    {
        $this->skipCategories = !$this->skipCategories;

        Log::info('CategoryPreviewModal: Skip categories toggled', [
            'skip_categories' => $this->skipCategories,
        ]);
    }

    /**
     * Approve category import
     *
     * Validates selection, marks preview as approved, dispatches BulkCreateCategories job
     * OR skips categories and imports products directly if skipCategories is true
     */
    public function approve(): void
    {
        Log::info('🔥 CategoryPreviewModal::approve() CALLED', [
            'component_id' => $this->getId(),
            'preview_id' => $this->previewId,
            'skip_categories' => $this->skipCategories,
            'selected_count' => count($this->selectedCategoryIds),
        ]);

        // Skip categories option - import products without categories
        if ($this->skipCategories) {
            $this->approveSkipCategories();
            return;
        }

        // Empty preview - all categories exist, proceed directly to import
        if (empty($this->categoryTree)) {
            Log::info('CategoryPreviewModal: Empty tree - dispatching BulkImportProducts', [
                'preview_id' => $this->previewId,
                'totalCount' => $this->totalCount,
            ]);
            $this->approveSkipCategories();  // Use same logic (dispatch BulkImportProducts)
            return;
        }

        // Normal flow - validate category selection
        if (empty($this->selectedCategoryIds)) {
            $this->dispatch('warning', message: 'Wybierz przynajmniej jedną kategorię lub zaznacz "Importuj bez kategorii"');
            return;
        }

        $this->isApproving = true;

        try {
            $preview = CategoryPreview::find($this->previewId);

            if (!$preview) {
                throw new \Exception('Preview nie został znaleziony');
            }

            // Save user selection
            $preview->setUserSelection($this->selectedCategoryIds);

            // Mark as approved
            $preview->markApproved();

            Log::info('CategoryPreviewModal: Preview approved', [
                'preview_id' => $this->previewId,
                'shop_id' => $preview->shop_id,
                'selected_count' => count($this->selectedCategoryIds),
                'selected_ids' => $this->selectedCategoryIds,
            ]);

            // Get import context (originalImportOptions) to pass to BulkCreateCategories
            $importContext = $preview->import_context_json ?? [];

            // Dispatch BulkCreateCategories job WITH originalImportOptions
            BulkCreateCategories::dispatch(
                $this->previewId,
                $this->selectedCategoryIds,
                $importContext  // 🔧 FIX: Pass import context so BulkImportProducts can be dispatched!
            );

            Log::info('CategoryPreviewModal: BulkCreateCategories job dispatched', [
                'preview_id' => $this->previewId,
                'category_count' => count($this->selectedCategoryIds),
                'import_context' => $importContext,
            ]);

            $this->dispatch('success', message: sprintf(
                'Tworzenie %d kategorii rozpoczęte. Import produktów nastąpi automatycznie po ukończeniu.',
                count($this->selectedCategoryIds)
            ));

            $this->close();

        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Approval failed', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas zatwierdzania: ' . $e->getMessage());
        } finally {
            $this->isApproving = false;
        }
    }

    /**
     * Approve with skip categories option
     *
     * Mark preview as approved without creating categories,
     * proceed directly to BulkImportProducts
     */
    private function approveSkipCategories(): void
    {
        $this->isApproving = true;

        try {
            $preview = CategoryPreview::find($this->previewId);

            if (!$preview) {
                throw new \Exception('Preview nie został znaleziony');
            }

            // Mark as approved with skip flag
            $preview->markApproved();
            $preview->update(['user_selection_json' => ['skip_categories' => true]]);

            Log::warning('CategoryPreviewModal: Skip categories approved', [
                'preview_id' => $this->previewId,
                'shop_id' => $preview->shop_id,
            ]);

            // Get import context (originalImportOptions)
            $importContext = $preview->import_context_json ?? [];
            $shop = $preview->shop;
            $jobId = $preview->job_id;

            // Extract mode and options from import context
            $mode = $importContext['mode'] ?? 'individual';
            $options = array_merge(
                $importContext['options'] ?? [],
                [
                    'skip_category_analysis' => true,  // Skip AnalyzeMissingCategories
                ]
            );

            // Dispatch BulkImportProducts directly (skip BulkCreateCategories)
            \App\Jobs\PrestaShop\BulkImportProducts::dispatch(
                $shop,
                $mode,
                $options,
                $jobId
            );

            Log::info('CategoryPreviewModal: BulkImportProducts job dispatched (skip categories)', [
                'preview_id' => $this->previewId,
                'shop_id' => $preview->shop_id,
                'shop_name' => $shop->name,
                'job_id' => $jobId,
                'mode' => $mode,
                'options' => $options,
            ]);

            $this->dispatch('warning', message: 'Import produktów rozpoczęty BEZ kategorii. Produkty zostaną zaimportowane bez przypisania kategorii.');

            $this->close();

        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Skip categories approval failed', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas zatwierdzania: ' . $e->getMessage());
        } finally {
            $this->isApproving = false;
        }
    }

    /**
     * Reject category import
     *
     * Marks preview as rejected, no categories will be created
     */
    public function reject(): void
    {
        try {
            $preview = CategoryPreview::find($this->previewId);

            if (!$preview) {
                throw new \Exception('Preview nie został znaleziony');
            }

            $preview->markRejected();

            // Update JobProgress to 'failed' (user rejected import)
            $jobProgress = \App\Models\JobProgress::where('job_id', $preview->job_id)->first();
            if ($jobProgress) {
                $jobProgress->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);

                Log::info('CategoryPreviewModal: JobProgress marked as failed (user rejected)', [
                    'job_progress_id' => $jobProgress->id,
                    'job_id' => $preview->job_id,
                ]);
            }

            Log::info('CategoryPreviewModal: Preview rejected', [
                'preview_id' => $this->previewId,
                'shop_id' => $preview->shop_id,
            ]);

            $this->dispatch('info', message: 'Import anulowany. Kategorie nie zostaną utworzone.');

            $this->close();

        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Rejection failed', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas anulowania: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check which categories already exist in PPM
     *
     * Mark each category with 'exists_in_ppm' flag based on ShopMapping
     *
     * @param array $categoryTree Hierarchical category tree
     * @return array Tree with exists_in_ppm flags added
     */
    private function checkExistingCategories(array $categoryTree): array
    {
        if (!$this->shopId) {
            return $categoryTree;
        }

        // Get existing PrestaShop category IDs from mappings
        $existingPrestashopIds = \App\Models\ShopMapping::where('shop_id', $this->shopId)
            ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->pluck('prestashop_id')
            ->toArray();

        Log::info('CategoryPreviewModal: Checking existing categories', [
            'shop_id' => $this->shopId,
            'existing_count' => count($existingPrestashopIds),
            'existing_ids' => $existingPrestashopIds,
        ]);

        // Recursively mark existing categories in tree
        return $this->markExistingInTree($categoryTree, $existingPrestashopIds);
    }

    /**
     * Recursively mark categories as existing or new
     *
     * @param array $tree Category tree
     * @param array $existingIds Existing PrestaShop IDs
     * @return array Tree with exists_in_ppm flags
     */
    private function markExistingInTree(array $tree, array $existingIds): array
    {
        foreach ($tree as &$category) {
            $prestashopId = $category['prestashop_id'] ?? 0;
            $category['exists_in_ppm'] = in_array($prestashopId, $existingIds, true);

            // Recursively process children
            if (!empty($category['children'])) {
                $category['children'] = $this->markExistingInTree($category['children'], $existingIds);
            }
        }

        return $tree;
    }

    /**
     * Extract only NEW category IDs (skip existing)
     *
     * @param array $tree Category tree
     * @return array Flat array of new PrestaShop category IDs
     */
    private function extractNewCategoryIds(array $tree): array
    {
        $ids = [];

        foreach ($tree as $category) {
            $existsInPpm = $category['exists_in_ppm'] ?? false;

            // Add only NEW categories
            if (!$existsInPpm && isset($category['prestashop_id'])) {
                $ids[] = (int) $category['prestashop_id'];
            }

            // Recursively extract children IDs
            if (!empty($category['children'])) {
                $childIds = $this->extractNewCategoryIds($category['children']);
                $ids = array_merge($ids, $childIds);
            }
        }

        return $ids;
    }

    /**
     * Extract all category IDs from hierarchical tree (recursive)
     *
     * @param array $tree Category tree
     * @return array Flat array of all PrestaShop category IDs
     */
    private function extractAllCategoryIds(array $tree): array
    {
        $ids = [];

        foreach ($tree as $category) {
            // Add current category ID
            if (isset($category['prestashop_id'])) {
                $ids[] = (int) $category['prestashop_id'];
            }

            // Recursively extract children IDs
            if (!empty($category['children'])) {
                $childIds = $this->extractAllCategoryIds($category['children']);
                $ids = array_merge($ids, $childIds);
            }
        }

        return $ids;
    }

    /**
     * Load category mapping info from import context
     *
     * Extract source PrestaShop category and target PPM category mapping
     *
     * @param CategoryPreview $preview
     * @return void
     */
    private function loadCategoryMappingInfo(CategoryPreview $preview): void
    {
        // Get import context (originalImportOptions)
        $importContext = $preview->import_context_json;

        if (empty($importContext)) {
            return; // No context available
        }

        // Extract source PrestaShop category ID
        $sourceCategoryId = $importContext['options']['category_id'] ?? null;

        if (!$sourceCategoryId) {
            return; // No source category (individual/bulk import mode)
        }

        Log::debug('CategoryPreviewModal: Loading category mapping', [
            'source_category_id' => $sourceCategoryId,
            'shop_id' => $this->shopId,
        ]);

        try {
            // Fetch source PrestaShop category name
            $this->sourceCategoryName = $this->getPrestaShopCategoryName($preview->shop, $sourceCategoryId);

            // Check if mapping exists in PPM
            $mapping = \App\Models\ShopMapping::where('shop_id', $this->shopId)
                ->where('prestashop_id', $sourceCategoryId)
                ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
                ->where('is_active', true)
                ->first();

            if ($mapping) {
                // Get PPM category name
                $ppmCategory = \App\Models\Category::find($mapping->ppm_id);
                $this->targetCategoryName = $ppmCategory?->name;

                Log::info('CategoryPreviewModal: Category mapping found', [
                    'source_name' => $this->sourceCategoryName,
                    'target_name' => $this->targetCategoryName,
                    'ppm_category_id' => $ppmCategory?->id,
                ]);
            } else {
                Log::info('CategoryPreviewModal: No category mapping found', [
                    'source_category_id' => $sourceCategoryId,
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('CategoryPreviewModal: Failed to load category mapping', [
                'source_category_id' => $sourceCategoryId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get PrestaShop category name from API
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param int $categoryId
     * @return string|null
     */
    private function getPrestaShopCategoryName(\App\Models\PrestaShopShop $shop, int $categoryId): ?string
    {
        try {
            $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
            $client = $clientFactory->create($shop);

            $response = $client->getCategory($categoryId);
            $categoryData = $response['category'] ?? $response;

            // Extract multilang name (first language)
            $nameData = $categoryData['name'] ?? [];

            if (is_string($nameData)) {
                return $nameData;
            }

            if (is_array($nameData) && !empty($nameData)) {
                $firstValue = reset($nameData);
                return $firstValue['value'] ?? (is_string($firstValue) ? $firstValue : null);
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('CategoryPreviewModal: Failed to fetch PrestaShop category name', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return "Kategoria #{$categoryId}"; // Fallback
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.category-preview-modal');
    }
}
