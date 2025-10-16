<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CategoryPreview;
use App\Models\Category;
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

    /**
     * Detected category conflicts for re-imported products
     *
     * Format: [
     *   {
     *     product_id: 123,
     *     sku: "ABC123",
     *     name: "Product Name",
     *     prestashop_categories: [1, 2, 3],
     *     ppm_default_categories: [10, 20],
     *     shop_categories: [15, 25]
     *   },
     *   ...
     * ]
     *
     * @var array
     */
    public array $detectedConflicts = [];

    /**
     * Show conflicts section in UI
     *
     * @var bool
     */
    public bool $showConflicts = false;

    /**
     * Show create category form (ETAP 4: Manual Category Creator)
     *
     * @var bool
     */
    public bool $showCreateForm = false;

    /**
     * New category form data (ETAP 4: Manual Category Creator)
     *
     * @var array
     */
    public array $newCategoryForm = [
        'name' => '',
        'parent_id' => null,
        'description' => '',
        'is_active' => true,
    ];

    /**
     * Validation errors for create form
     *
     * @var array
     */
    protected $rules = [
        'newCategoryForm.name' => 'required|string|max:300',
        'newCategoryForm.parent_id' => 'nullable|exists:categories,id',
        'newCategoryForm.description' => 'nullable|string|max:500',
        'newCategoryForm.is_active' => 'boolean',
    ];

    /**
     * Show conflict resolution modal (ETAP 3: Conflict Resolution UI)
     *
     * @var bool
     */
    public bool $showConflictResolutionModal = false;

    /**
     * Selected product for conflict resolution (ETAP 3)
     *
     * @var array|null
     */
    public ?array $selectedConflictProduct = null;

    /**
     * Selected resolution option (ETAP 3)
     * Options: 'overwrite', 'keep', 'manual', 'cancel'
     *
     * @var string|null
     */
    public ?string $selectedResolution = null;

    /**
     * Manually selected categories for conflict resolution (ETAP 3 - Option 3)
     *
     * @var array
     */
    public array $manuallySelectedCategories = [];

    /**
     * Modal instance ID for unique wire:key (prevents Livewire component ID conflicts)
     *
     * @var string
     */
    public string $modalInstanceId = '';

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

            // Detect category conflicts for re-imported products (2025-10-13)
            $this->detectedConflicts = $this->detectCategoryConflicts($preview);

            // Open modal
            $this->isOpen = true;

            Log::info('CategoryPreviewModal: Opened successfully', [
                'preview_id' => $previewId,
                'shop_name' => $this->shopName,
                'total_categories' => $this->totalCount,
                'selected_count' => count($this->selectedCategoryIds),
                'conflicts_detected' => count($this->detectedConflicts),
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
            'detectedConflicts',
            'showConflicts',
            'showCreateForm',
            'newCategoryForm',
            'showConflictResolutionModal',
            'selectedConflictProduct',
            'selectedResolution',
            'manuallySelectedCategories',
        ]);

        $this->resetValidation();

        Log::info('CategoryPreviewModal: Closed');
    }

    /**
     * Toggle conflicts section visibility
     */
    public function toggleConflicts(): void
    {
        $this->showConflicts = !$this->showConflicts;

        Log::debug('CategoryPreviewModal: Conflicts section toggled', [
            'show_conflicts' => $this->showConflicts,
            'conflicts_count' => count($this->detectedConflicts),
        ]);
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
     * Show create category form (ETAP 4: Manual Category Creator)
     *
     * ENHANCED 2025-10-15: Also listens to 'create-category-requested' event from CategoryPicker
     */
    #[On('create-category-requested')]
    public function showCreateCategoryForm(): void
    {
        $this->showCreateForm = true;
        $this->reset('newCategoryForm');

        Log::info('CategoryPreviewModal: Create form opened', [
            'preview_id' => $this->previewId,
        ]);
    }

    /**
     * Hide create category form
     */
    public function hideCreateCategoryForm(): void
    {
        $this->showCreateForm = false;
        $this->reset('newCategoryForm');
        $this->resetValidation();

        Log::info('CategoryPreviewModal: Create form closed', [
            'preview_id' => $this->previewId,
        ]);
    }

    /**
     * Create new category (ETAP 4: Manual Category Creator)
     *
     * Creates category in PPM and auto-creates shop mapping
     * Auto-selects newly created category in preview
     */
    public function createQuickCategory(): void
    {
        Log::info('🔥 CategoryPreviewModal: createQuickCategory() CALLED', [
            'livewire_id' => $this->getId(),
            'preview_id' => $this->previewId,
            'form_data' => $this->newCategoryForm,
        ]);

        try {
            // Validate form
            $validated = $this->validate([
                'newCategoryForm.name' => 'required|string|max:300',
                'newCategoryForm.parent_id' => 'nullable|exists:categories,id',
                'newCategoryForm.description' => 'nullable|string|max:500',
                'newCategoryForm.is_active' => 'boolean',
            ]);

            Log::info('CategoryPreviewModal: Form validation passed', [
                'validated' => $validated,
            ]);

            DB::transaction(function () use ($validated) {
                // Generate unique slug
                $slug = Str::slug($this->newCategoryForm['name']);
                $counter = 1;
                while (Category::where('slug', $slug)->exists()) {
                    $slug = Str::slug($this->newCategoryForm['name']) . '-' . $counter;
                    $counter++;
                }

                // Create category in PPM
                $category = Category::create([
                    'name' => $this->newCategoryForm['name'],
                    'slug' => $slug,
                    'description' => $this->newCategoryForm['description'] ?? '',
                    'parent_id' => $this->newCategoryForm['parent_id'],
                    'is_active' => $this->newCategoryForm['is_active'],
                    'sort_order' => 0,
                ]);

                // Auto-create shop mapping if shop context exists
                if ($this->shopId) {
                    // Use ShopMapping helper method (handles null prestashop_id gracefully)
                    \App\Models\ShopMapping::updateOrCreate(
                        [
                            'shop_id' => $this->shopId,
                            'mapping_type' => \App\Models\ShopMapping::TYPE_CATEGORY,
                            'ppm_value' => (string) $category->id, // PPM category ID as string
                        ],
                        [
                            'prestashop_id' => 0, // Manual category, no PrestaShop ID yet (0 = not synced)
                            'is_active' => true,
                        ]
                    );

                    Log::info('CategoryPreviewModal: Shop mapping created for new category', [
                        'category_id' => $category->id,
                        'shop_id' => $this->shopId,
                        'ppm_value' => (string) $category->id,
                    ]);
                }

                // Auto-select newly created category
                if (!in_array($category->id, $this->selectedCategoryIds, true)) {
                    $this->selectedCategoryIds[] = $category->id;
                }

                Log::info('CategoryPreviewModal: Quick category created', [
                    'preview_id' => $this->previewId,
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                ]);
            });

            $this->dispatch('success', message: 'Kategoria została utworzona pomyślnie');
            $this->hideCreateCategoryForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically displayed
            Log::warning('CategoryPreviewModal: Category creation validation failed', [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Category creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas tworzenia kategorii: ' . $e->getMessage());
        }
    }

    /**
     * Get available parent categories for dropdown
     *
     * @return array
     */
    public function getParentCategoryOptionsProperty(): array
    {
        return Category::active()
            ->orderBy('name', 'asc')
            ->get()
            ->mapWithKeys(function ($category) {
                $prefix = str_repeat('— ', $category->level ?? 0);
                return [$category->id => $prefix . $category->name];
            })
            ->toArray();
    }

    /**
     * Open conflict resolution modal for product (ETAP 3: Conflict Resolution UI)
     *
     * @param int $productId PPM product ID
     */
    public function openConflictResolution(int $productId): void
    {
        // Find conflict data for this product
        $conflict = collect($this->detectedConflicts)->firstWhere('product_id', $productId);

        if (!$conflict) {
            Log::warning('CategoryPreviewModal: Conflict not found for product', [
                'product_id' => $productId,
            ]);
            $this->dispatch('error', message: 'Konflikt nie został znaleziony');
            return;
        }

        $this->selectedConflictProduct = $conflict;
        $this->selectedResolution = null;
        $this->manuallySelectedCategories = [];
        $this->modalInstanceId = uniqid('modal_', true); // 🔧 FIX: Unique ID per modal open
        $this->showConflictResolutionModal = true;

        Log::info('🔓 CategoryPreviewModal: CONFLICT MODAL OPENED', [
            'livewire_id' => $this->getId(),
            'product_id' => $productId,
            'sku' => $conflict['sku'],
            'modal_instance_id' => $this->modalInstanceId,
            'conflict_types' => [
                'default' => $conflict['has_default_conflict'],
                'shop' => $conflict['has_shop_conflict'],
            ],
            'showConflictResolutionModal' => $this->showConflictResolutionModal,
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Close conflict resolution modal
     */
    public function closeConflictResolution(): void
    {
        Log::info('🔒 CategoryPreviewModal: CONFLICT MODAL CLOSING', [
            'livewire_id' => $this->getId(),
            'was_manual' => ($this->selectedResolution === 'manual'),
            'had_selected_categories' => count($this->manuallySelectedCategories),
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);

        $this->reset([
            'showConflictResolutionModal',
            'selectedConflictProduct',
            'selectedResolution',
            'manuallySelectedCategories',
        ]);

        Log::info('CategoryPreviewModal: Closed conflict resolution');
    }

    /**
     * Select resolution option (ETAP 3: Conflict Resolution UI)
     *
     * @param string $option 'overwrite', 'keep', 'manual', 'cancel'
     */
    public function selectResolutionOption(string $option): void
    {
        $beforeState = $this->selectedResolution;
        $this->selectedResolution = $option;

        Log::info('🎯 CategoryPreviewModal: RESOLUTION OPTION SELECTED', [
            'livewire_id' => $this->getId(),
            'option' => $option,
            'before_state' => $beforeState,
            'after_state' => $this->selectedResolution,
            'product_id' => $this->selectedConflictProduct['product_id'] ?? null,
            'product_sku' => $this->selectedConflictProduct['sku'] ?? null,
            'modal_instance_id' => $this->modalInstanceId,
            'showConflictResolutionModal' => $this->showConflictResolutionModal,
            'will_show_category_picker' => ($option === 'manual'),
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
        ]);

        // If manual selected, CategoryPicker will be shown (nested Livewire component!)
        if ($option === 'manual') {
            Log::warning('⚠️ CategoryPreviewModal: MANUAL SELECTED - CategoryPicker will mount!', [
                'livewire_id' => $this->getId(),
                'modal_instance_id' => $this->modalInstanceId,
                'manuallySelectedCategories' => $this->manuallySelectedCategories,
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
            ]);
        }
    }

    /**
     * Confirm and apply conflict resolution (ETAP 3: Conflict Resolution UI)
     */
    public function confirmConflictResolution(): void
    {
        Log::info('🔥 CategoryPreviewModal: confirmConflictResolution() CALLED', [
            'livewire_id' => $this->getId(),
            'has_selected_product' => !empty($this->selectedConflictProduct),
            'selected_resolution' => $this->selectedResolution,
            'product_id' => $this->selectedConflictProduct['product_id'] ?? null,
            'manually_selected_categories' => $this->manuallySelectedCategories,
        ]);

        if (!$this->selectedConflictProduct || !$this->selectedResolution) {
            Log::warning('CategoryPreviewModal: Missing required data for resolution', [
                'has_product' => !empty($this->selectedConflictProduct),
                'has_resolution' => !empty($this->selectedResolution),
            ]);
            $this->dispatch('warning', message: 'Wybierz sposób rozwiązania konfliktu');
            return;
        }

        $productId = $this->selectedConflictProduct['product_id'];

        Log::info('CategoryPreviewModal: Attempting resolution', [
            'product_id' => $productId,
            'sku' => $this->selectedConflictProduct['sku'],
            'resolution_method' => $this->selectedResolution,
        ]);

        try {
            switch ($this->selectedResolution) {
                case 'overwrite':
                    Log::debug('Calling resolveConflictOverwrite()');
                    $this->resolveConflictOverwrite($productId);
                    break;
                case 'keep':
                    Log::debug('Calling resolveConflictKeep()');
                    $this->resolveConflictKeep($productId);
                    break;
                case 'manual':
                    Log::debug('Calling resolveConflictManual()');
                    $this->resolveConflictManual($productId);
                    break;
                case 'cancel':
                    Log::debug('Calling resolveConflictCancel()');
                    $this->resolveConflictCancel($productId);
                    break;
                default:
                    throw new \Exception('Nieznana opcja rozwiązania: ' . $this->selectedResolution);
            }

            Log::info('✅ CategoryPreviewModal: Resolution completed successfully', [
                'product_id' => $productId,
                'method' => $this->selectedResolution,
            ]);

            // Remove resolved conflict from list
            $this->detectedConflicts = array_values(
                array_filter($this->detectedConflicts, fn($c) => $c['product_id'] !== $productId)
            );

            $this->dispatch('success', message: 'Konflikt został rozwiązany');
            $this->closeConflictResolution();

        } catch (\Exception $e) {
            Log::error('❌ CategoryPreviewModal: Conflict resolution FAILED', [
                'product_id' => $productId,
                'resolution' => $this->selectedResolution,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas rozwiązywania konfliktu: ' . $e->getMessage());
        }
    }

    /**
     * Resolve conflict: Option 1 - Overwrite default categories
     *
     * Update DEFAULT categories (shop_id=NULL) to match import categories
     * Remove per-shop override if exists
     *
     * @param int $productId
     */
    private function resolveConflictOverwrite(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $conflict = $this->selectedConflictProduct;
            $newCategories = $conflict['import_will_assign_categories'];

            // FIX 2025-10-15: Extract IDs from structured data [{id, name, level}] → [id, id, id]
            // Since 2025-10-14 hierarchical enhancement, $newCategories contains objects not plain IDs
            $categoryIds = array_map(function($cat) {
                return is_array($cat) ? ($cat['id'] ?? $cat) : $cat;
            }, $newCategories);

            // Delete ALL existing product_categories entries (default + shop)
            DB::table('product_categories')
                ->where('product_id', $productId)
                ->delete();

            // Insert new DEFAULT categories (shop_id=NULL)
            foreach ($categoryIds as $categoryId) {
                DB::table('product_categories')->insert([
                    'product_id' => $productId,
                    'category_id' => (int) $categoryId, // Ensure INT type
                    'shop_id' => null, // Default categories
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('CategoryPreviewModal: Conflict resolved - Overwrite', [
                'product_id' => $productId,
                'sku' => $conflict['sku'],
                'old_default' => $conflict['ppm_default_categories'],
                'new_default' => $categoryIds, // Log extracted IDs
            ]);
        });
    }

    /**
     * Resolve conflict: Option 2 - Keep default categories
     *
     * Keep DEFAULT categories unchanged
     * Create/update per-shop categories (shop_id=X) with import categories
     *
     * @param int $productId
     */
    private function resolveConflictKeep(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $conflict = $this->selectedConflictProduct;
            $newCategories = $conflict['import_will_assign_categories'];

            // FIX 2025-10-15: Extract IDs from structured data [{id, name, level}] → [id, id, id]
            // Since 2025-10-14 hierarchical enhancement, $newCategories contains objects not plain IDs
            $categoryIds = array_map(function($cat) {
                return is_array($cat) ? ($cat['id'] ?? $cat) : $cat;
            }, $newCategories);

            // Delete existing per-shop categories for THIS shop
            DB::table('product_categories')
                ->where('product_id', $productId)
                ->where('shop_id', $this->shopId)
                ->delete();

            // Insert per-shop categories
            foreach ($categoryIds as $categoryId) {
                DB::table('product_categories')->insert([
                    'product_id' => $productId,
                    'category_id' => (int) $categoryId, // Ensure INT type
                    'shop_id' => $this->shopId, // Per-shop override
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('CategoryPreviewModal: Conflict resolved - Keep', [
                'product_id' => $productId,
                'sku' => $conflict['sku'],
                'default_unchanged' => $conflict['ppm_default_categories'],
                'new_shop_override' => $categoryIds, // Log extracted IDs
                'shop_id' => $this->shopId,
            ]);
        });
    }

    /**
     * Resolve conflict: Option 3 - Manual category selection
     *
     * User manually selected categories via Category Picker
     * Update DEFAULT categories (shop_id=NULL) with manual selection
     *
     * @param int $productId
     */
    private function resolveConflictManual(int $productId): void
    {
        if (empty($this->manuallySelectedCategories)) {
            throw new \Exception('Wybierz przynajmniej jedną kategorię');
        }

        DB::transaction(function () use ($productId) {
            $conflict = $this->selectedConflictProduct;

            // Delete ALL existing product_categories entries
            DB::table('product_categories')
                ->where('product_id', $productId)
                ->delete();

            // Insert manually selected categories as DEFAULT
            foreach ($this->manuallySelectedCategories as $categoryId) {
                DB::table('product_categories')->insert([
                    'product_id' => $productId,
                    'category_id' => $categoryId,
                    'shop_id' => null, // Default categories
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('CategoryPreviewModal: Conflict resolved - Manual', [
                'product_id' => $productId,
                'sku' => $conflict['sku'],
                'old_default' => $conflict['ppm_default_categories'],
                'manual_selection' => $this->manuallySelectedCategories,
            ]);
        });
    }

    /**
     * Resolve conflict: Option 4 - Cancel (no changes)
     *
     * Skip this product during import
     * No category changes
     *
     * @param int $productId
     */
    private function resolveConflictCancel(int $productId): void
    {
        $conflict = $this->selectedConflictProduct;

        Log::info('CategoryPreviewModal: Conflict resolved - Cancel', [
            'product_id' => $productId,
            'sku' => $conflict['sku'],
            'message' => 'Product will be skipped during import',
        ]);

        // No database changes - just log and remove from conflicts list
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
     * Get PrestaShop category data from API (name + level)
     *
     * ENHANCED 2025-10-15: Returns hierarchical structure with name + level
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param int $categoryId
     * @return array|null Returns ['name' => string, 'level' => int] or null
     */
    private function getPrestaShopCategoryData(\App\Models\PrestaShopShop $shop, int $categoryId): ?array
    {
        try {
            $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
            $client = $clientFactory->create($shop);

            $response = $client->getCategory($categoryId);
            $categoryData = $response['category'] ?? $response;

            // Extract multilang name (first language)
            $nameData = $categoryData['name'] ?? [];
            $name = null;

            if (is_string($nameData)) {
                $name = $nameData;
            } elseif (is_array($nameData) && !empty($nameData)) {
                $firstValue = reset($nameData);
                $name = $firstValue['value'] ?? (is_string($firstValue) ? $firstValue : null);
            }

            if (!$name) {
                return null;
            }

            // Extract level_depth (PrestaShop hierarchy level)
            $levelDepth = (int) ($categoryData['level_depth'] ?? 0);

            return [
                'name' => $name,
                'level' => $levelDepth,
            ];

        } catch (\Exception $e) {
            Log::warning('CategoryPreviewModal: Failed to fetch PrestaShop category data', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return [
                'name' => "Kategoria #{$categoryId}",
                'level' => 0,
            ];
        }
    }

    /**
     * Get PrestaShop category name from API (legacy wrapper)
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param int $categoryId
     * @return string|null
     */
    private function getPrestaShopCategoryName(\App\Models\PrestaShopShop $shop, int $categoryId): ?string
    {
        $data = $this->getPrestaShopCategoryData($shop, $categoryId);
        return $data['name'] ?? null;
    }

    /**
     * Detect category conflicts for re-imported products
     *
     * Compare PrestaShop categories vs existing PPM categories for products
     * that will be re-imported (already exist in PPM by SKU or prestashop_product_id)
     *
     * @param CategoryPreview $preview
     * @return array List of conflicts
     */
    private function detectCategoryConflicts(CategoryPreview $preview): array
    {
        $conflicts = [];
        $importContext = $preview->import_context_json;

        Log::info('🔍 CategoryPreviewModal: detectCategoryConflicts() CALLED', [
            'preview_id' => $preview->id,
            'has_import_context' => !empty($importContext),
            'import_context' => $importContext,
        ]);

        if (empty($importContext)) {
            Log::warning('CategoryPreviewModal: No import context - skipping conflict detection');
            return $conflicts; // No context = no conflicts to detect
        }

        try {
            // Get products to import based on mode
            $mode = $importContext['mode'] ?? 'individual';
            $options = $importContext['options'] ?? [];

            Log::info('CategoryPreviewModal: Fetching products to check', [
                'mode' => $mode,
                'options' => $options,
            ]);

            $productIds = $this->getProductIdsToImport($preview->shop, $mode, $options);

            if (empty($productIds)) {
                Log::warning('CategoryPreviewModal: No products to check for conflicts', [
                    'mode' => $mode,
                    'options_keys' => array_keys($options),
                ]);
                return $conflicts;
            }

            Log::info('CategoryPreviewModal: Checking conflicts for products', [
                'shop_id' => $preview->shop_id,
                'mode' => $mode,
                'product_count' => count($productIds),
            ]);

            // Fetch PrestaShop category data for these products
            $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
            $client = $clientFactory->create($preview->shop);

            foreach ($productIds as $prestashopProductId) {
                try {
                    // Get product from PrestaShop
                    $prestashopData = $client->getProduct($prestashopProductId);

                    // 🔧 FIX 2025-10-13: Unwrap nested 'product' key (same as import)
                    // PrestaShop API returns: {product: {id: 123, associations: {...}}}
                    if (isset($prestashopData['product']) && is_array($prestashopData['product'])) {
                        $psProduct = $prestashopData['product'];
                    } else {
                        $psProduct = $prestashopData;
                    }

                    // 🔧 FIX 2025-10-13 ETAP 1: Use same category mapping logic as import
                    // Extract and MAP categories (PrestaShop → PPM IDs)
                    // This matches the logic in PrestaShopImportService::syncProductCategories()
                    $ppmCategoryIds = $this->extractAndMapCategories($psProduct, $preview->shop);

                    Log::info('📦 CategoryPreviewModal: Product categories after mapping', [
                        'prestashop_product_id' => $prestashopProductId,
                        'raw_ps_categories' => $this->extractPrestaShopCategoryIds($psProduct),
                        'mapped_ppm_categories' => $ppmCategoryIds,
                    ]);

                    // 🔧 FIX 2025-10-13 ETAP 1.5: UNIVERSAL RE-IMPORT detection
                    // Product może istnieć w PPM jako:
                    // 1. Ręcznie dodany (bez ProductShopData, ma DEFAULT categories)
                    // 2. Z innego sklepu (ProductShopData z innym shop_id)
                    // 3. Ten sam sklep (ProductShopData z tym samym shop_id)
                    //
                    // KLUCZOWE: Szukaj PO SKU (reference), nie po prestashop_product_id!
                    // SKU jest uniwersalnym identyfikatorem produktu w PPM

                    $product = null;
                    $ppmProductId = null;
                    $existingShopId = null;
                    $foundBy = null;

                    // METODA 1 (PRIMARY): Search by SKU from PrestaShop reference
                    // To pokrywa WSZYSTKIE scenariusze: ręczne, cross-shop, same-shop
                    $sku = $psProduct['reference'] ?? null;

                    if ($sku) {
                        $product = \App\Models\Product::where('sku', $sku)->first();

                        if ($product) {
                            $ppmProductId = $product->id;
                            $foundBy = 'SKU';

                            // Check if product has ProductShopData for ANY shop
                            $existingShopData = \App\Models\ProductShopData::where('product_id', $ppmProductId)->first();
                            $existingShopId = $existingShopData->shop_id ?? null;

                            Log::info('✅ CategoryPreviewModal: Product found by SKU', [
                                'prestashop_product_id' => $prestashopProductId,
                                'sku' => $sku,
                                'ppm_product_id' => $ppmProductId,
                                'existing_shop_id' => $existingShopId,
                                'importing_to_shop_id' => $preview->shop_id,
                                'scenario' => $existingShopId
                                    ? ($existingShopId === $preview->shop_id ? 'SAME_SHOP_REIMPORT' : 'CROSS_SHOP_IMPORT')
                                    : 'MANUAL_PRODUCT_IMPORT',
                            ]);
                        }
                    }

                    // METODA 2 (FALLBACK): Search by prestashop_product_id
                    // Tylko jeśli produkt nie ma SKU (bardzo rzadkie)
                    if (!$product) {
                        $anyProductShopData = \App\Models\ProductShopData::where('prestashop_product_id', $prestashopProductId)
                            ->first();

                        if ($anyProductShopData) {
                            $ppmProductId = $anyProductShopData->product_id;
                            $product = \App\Models\Product::find($ppmProductId);
                            $existingShopId = $anyProductShopData->shop_id;
                            $foundBy = 'ProductShopData';

                            Log::info('✅ CategoryPreviewModal: Product found by ProductShopData (no SKU)', [
                                'prestashop_product_id' => $prestashopProductId,
                                'ppm_product_id' => $ppmProductId,
                                'existing_shop_id' => $existingShopId,
                                'importing_to_shop_id' => $preview->shop_id,
                            ]);
                        }
                    }

                    // If product NOT found by either method → TRUE first import
                    if (!$product || !$ppmProductId) {
                        Log::debug('CategoryPreviewModal: Product NOT FOUND - first import', [
                            'prestashop_product_id' => $prestashopProductId,
                            'sku' => $sku ?? 'N/A',
                            'checked_methods' => ['SKU', 'ProductShopData'],
                        ]);
                        continue; // TRUE first import, no conflict
                    }

                    // Skip conflict check if no mapped categories (import będzie auto-create categories)
                    if (empty($ppmCategoryIds)) {
                        Log::debug('CategoryPreviewModal: No categories mapped - will check default categories for conflicts', [
                            'prestashop_product_id' => $prestashopProductId,
                            'ppm_product_id' => $ppmProductId,
                            'existing_shop_id' => $existingShopId,
                        ]);
                        // KONTYNUUJ sprawdzanie konfliktów nawet bez mappingu - może istnieć konflikt w default categories
                    }

                    // At this point: $product and $ppmProductId are guaranteed to exist (validated above)
                    // Get DEFAULT categories (shop_id=NULL)
                    $defaultCategories = \DB::table('product_categories')
                        ->where('product_id', $ppmProductId)
                        ->whereNull('shop_id')
                        ->pluck('category_id')
                        ->toArray();

                    // Get SHOP-SPECIFIC categories (shop_id=X)
                    $shopCategories = \DB::table('product_categories')
                        ->where('product_id', $ppmProductId)
                        ->where('shop_id', $preview->shop_id)
                        ->pluck('category_id')
                        ->toArray();

                    // NOTE: $ppmCategoryIds already contains mapped PPM category IDs from extractAndMapCategories()
                    // No need for additional conversion - this is the same data as import would use!

                    Log::info('🔍 CategoryPreviewModal: Comparing categories (using mapped PPM IDs)', [
                        'product_id' => $ppmProductId,
                        'sku' => $product->sku,
                        'mapped_ppm_categories' => $ppmCategoryIds,
                        'ppm_default_categories' => $defaultCategories,
                        'ppm_shop_categories' => $shopCategories,
                    ]);

                    // Check if categories differ (now using PPM IDs directly)
                    // Sort arrays to ensure order doesn't matter
                    sort($ppmCategoryIds);
                    sort($defaultCategories);
                    sort($shopCategories);

                    // 🔧 FIX 2025-10-13: Correct conflict detection logic
                    // Conflict istnieje gdy tablice SĄ RÓŻNE (niezależnie która większa)
                    // array_diff() TYLKO pokazuje elementy z PIERWSZEJ tablicy których NIE MA w drugiej
                    // ALE konflikt to różnica w KTÓRYMKOLWIEK kierunku!

                    $hasDefaultConflict = ($ppmCategoryIds !== $defaultCategories);
                    $hasShopConflict = ($ppmCategoryIds !== $shopCategories);

                    // Special case: unmapped categories (will be auto-imported with unknown PPM IDs)
                    $rawPsCategories = $this->extractPrestaShopCategoryIds($psProduct);
                    $hasUnmappedCategories = (empty($ppmCategoryIds) && !empty($rawPsCategories));

                    $hasConflict = $hasDefaultConflict || $hasShopConflict || $hasUnmappedCategories;

                    Log::info('🔍 CategoryPreviewModal: Category comparison results', [
                        'product_id' => $ppmProductId,
                        'mapped_ppm_categories' => $ppmCategoryIds,
                        'default_categories' => $defaultCategories,
                        'shop_categories' => $shopCategories,
                        'raw_ps_categories' => $rawPsCategories,
                        'has_default_conflict' => $hasDefaultConflict,
                        'has_shop_conflict' => $hasShopConflict,
                        'has_unmapped_categories' => $hasUnmappedCategories,
                        'has_conflict' => $hasConflict,
                    ]);

                    if ($hasConflict) {
                        // CONFLICT DETECTED!
                        // 🔧 FIX 2025-10-14: Map category IDs to NAMES for UI display
                        // If mapped categories are empty but raw PrestaShop categories exist,
                        // fetch actual category names from PrestaShop API instead of showing IDs
                        $importWillAssign = !empty($ppmCategoryIds)
                            ? $this->mapCategoryIdsToNames($ppmCategoryIds)
                            : $this->mapPrestaShopCategoryIdsToNames($rawPsCategories, $preview->shop);

                        $conflicts[] = [
                            'product_id' => $ppmProductId,
                            'prestashop_product_id' => $prestashopProductId,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'import_will_assign_categories' => $importWillAssign, // Mapped names OR raw PS IDs
                            'ppm_default_categories' => $this->mapCategoryIdsToNames($defaultCategories), // Mapped names
                            'shop_categories' => $this->mapCategoryIdsToNames($shopCategories), // Mapped names
                            'raw_ps_categories' => $rawPsCategories, // Raw PrestaShop categories (not mapped)
                            'has_default_conflict' => $hasDefaultConflict,
                            'has_shop_conflict' => $hasShopConflict,
                            'has_unmapped_categories' => $hasUnmappedCategories,
                        ];

                        Log::warning('🚨 CategoryPreviewModal: CONFLICT DETECTED!', [
                            'product_id' => $ppmProductId,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'import_will_assign' => $ppmCategoryIds,
                            'current_default' => $defaultCategories,
                            'current_shop' => $shopCategories,
                            'raw_ps_categories' => $rawPsCategories,
                            'has_default_conflict' => $hasDefaultConflict,
                            'has_shop_conflict' => $hasShopConflict,
                            'has_unmapped_categories' => $hasUnmappedCategories,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::warning('CategoryPreviewModal: Failed to check product conflict', [
                        'prestashop_product_id' => $prestashopProductId,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            Log::info('CategoryPreviewModal: Conflict detection complete', [
                'conflicts_found' => count($conflicts),
            ]);

        } catch (\Exception $e) {
            Log::error('CategoryPreviewModal: Conflict detection failed', [
                'preview_id' => $preview->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $conflicts;
    }

    /**
     * Get product IDs to import based on mode
     *
     * @param \App\Models\PrestaShopShop $shop
     * @param string $mode
     * @param array $options
     * @return array PrestaShop product IDs
     */
    private function getProductIdsToImport(\App\Models\PrestaShopShop $shop, string $mode, array $options): array
    {
        try {
            $clientFactory = app(\App\Services\PrestaShop\PrestaShopClientFactory::class);
            $client = $clientFactory->create($shop);

            if ($mode === 'individual' && !empty($options['product_ids'])) {
                return $options['product_ids']; // Individual product IDs
            }

            if ($mode === 'category' && !empty($options['category_id'])) {
                // Fetch products from category
                $response = $client->getProductsByCategory($options['category_id'], $options['include_subcategories'] ?? false);
                return array_column($response, 'id');
            }

            if ($mode === 'all') {
                // Fetch all products (limit to first 100 for performance)
                $response = $client->getProducts(['limit' => 100]);
                return array_column($response, 'id');
            }

        } catch (\Exception $e) {
            Log::warning('CategoryPreviewModal: Failed to fetch products for conflict check', [
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Extract and map PrestaShop categories to PPM categories
     *
     * ETAP 1: ProductTransformer Integration (2025-10-13)
     *
     * Replicates the same logic as PrestaShopImportService::syncProductCategories()
     * to ensure conflict detection sees the SAME categories as actual import.
     *
     * Workflow:
     * 1. Extract associations.categories from PrestaShop product
     * 2. Map each PrestaShop category ID to PPM category ID via ShopMapping
     * 3. Skip unmapped categories (would be auto-imported during actual import)
     * 4. Return array of mapped PPM category IDs
     *
     * @param array $psProduct PrestaShop product data
     * @param \App\Models\PrestaShopShop $shop Shop instance
     * @return array PPM category IDs (as returned by import logic)
     */
    private function extractAndMapCategories(array $psProduct, \App\Models\PrestaShopShop $shop): array
    {
        // Extract PrestaShop category IDs from associations
        // Structure: associations.categories = [['id' => 2], ['id' => 51], ...]
        $prestashopCategories = data_get($psProduct, 'associations.categories', []);

        if (empty($prestashopCategories)) {
            Log::debug('CategoryPreviewModal: Product has no categories in PrestaShop API', [
                'prestashop_product_id' => data_get($psProduct, 'id'),
            ]);
            return [];
        }

        // Extract category IDs (handle both nested and flat structures)
        if (isset($prestashopCategories['category'])) {
            $prestashopCategories = $prestashopCategories['category'];
        }

        // Get default category (id_category_default)
        $defaultCategoryId = (int) data_get($psProduct, 'id_category_default', 0);

        // Map PrestaShop category IDs to PPM category IDs
        $ppmCategoryIds = [];

        foreach ((array) $prestashopCategories as $index => $categoryData) {
            $prestashopCategoryId = is_array($categoryData)
                ? (int) ($categoryData['id'] ?? 0)
                : (int) $categoryData;

            if ($prestashopCategoryId <= 0) {
                continue; // Invalid category ID
            }

            // Skip PrestaShop root categories (id 1, 2)
            if ($prestashopCategoryId <= 2) {
                Log::debug('CategoryPreviewModal: Skipping PrestaShop root category', [
                    'prestashop_category_id' => $prestashopCategoryId,
                ]);
                continue;
            }

            // Map PrestaShop category to PPM category via ShopMapping
            $mapping = \App\Models\ShopMapping::where('shop_id', $shop->id)
                ->where('prestashop_id', $prestashopCategoryId)
                ->where('mapping_type', \App\Models\ShopMapping::TYPE_CATEGORY)
                ->where('is_active', true)
                ->first();

            if ($mapping && $mapping->ppm_id) {
                $ppmCategoryIds[] = (int) $mapping->ppm_id;

                Log::debug('CategoryPreviewModal: Category mapped', [
                    'prestashop_id' => $prestashopCategoryId,
                    'ppm_id' => $mapping->ppm_id,
                    'is_primary' => ($prestashopCategoryId === $defaultCategoryId),
                ]);
            } else {
                // Category not mapped - would be auto-imported during actual import
                // For conflict detection, we skip it (conservative approach)
                Log::debug('CategoryPreviewModal: Category not mapped (would be auto-imported)', [
                    'prestashop_category_id' => $prestashopCategoryId,
                    'shop_id' => $shop->id,
                ]);
            }
        }

        Log::info('CategoryPreviewModal: Categories extracted and mapped', [
            'prestashop_product_id' => data_get($psProduct, 'id'),
            'shop_id' => $shop->id,
            'prestashop_category_count' => count($prestashopCategories),
            'mapped_ppm_category_count' => count($ppmCategoryIds),
            'ppm_category_ids' => $ppmCategoryIds,
        ]);

        return array_unique($ppmCategoryIds);
    }

    /**
     * Extract category IDs from PrestaShop product data
     *
     * @param array $psProduct PrestaShop product data
     * @return array PrestaShop category IDs
     */
    private function extractPrestaShopCategoryIds(array $psProduct): array
    {
        $associations = $psProduct['associations'] ?? [];
        $categories = $associations['categories'] ?? [];

        if (empty($categories)) {
            return [];
        }

        // Handle both single category and multiple categories
        if (isset($categories['category'])) {
            $categories = $categories['category'];
        }

        // Normalize to array of IDs
        $categoryIds = [];
        foreach ((array)$categories as $cat) {
            if (isset($cat['id'])) {
                $categoryIds[] = (int)$cat['id'];
            } elseif (is_numeric($cat)) {
                $categoryIds[] = (int)$cat;
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Map category IDs to category names for UI display
     *
     * ENHANCED 2025-10-15: Returns hierarchical structure with name + level for UI rendering
     *
     * Converts array of PPM category IDs to array of category data objects
     * Each object contains: id, name, level (for hierarchical indentation)
     * Returns empty array if input is empty or no categories found
     *
     * @param array $categoryIds Array of PPM category IDs
     * @return array Array of category data: [{id, name, level}, ...]
     */
    private function mapCategoryIdsToNames(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        // Fetch categories from DB with level
        $categories = Category::whereIn('id', $categoryIds)
            ->orderBy('level', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Map to hierarchical structure
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'level' => $category->level ?? 0,
            ];
        })->toArray();
    }

    /**
     * Map PrestaShop category IDs to category data via API
     *
     * ENHANCED 2025-10-15: Returns hierarchical structure with name + level
     *
     * Fetches category data (name + level_depth) from PrestaShop API for unmapped categories
     * Includes basic caching to avoid duplicate API calls
     * Returns array of structured data objects matching PPM format
     *
     * @param array $prestashopCategoryIds Array of PrestaShop category IDs
     * @param \App\Models\PrestaShopShop $shop Shop instance for API client
     * @return array Array of category data: [{id, name, level}, ...]
     */
    private function mapPrestaShopCategoryIdsToNames(array $prestashopCategoryIds, \App\Models\PrestaShopShop $shop): array
    {
        if (empty($prestashopCategoryIds)) {
            return [];
        }

        $categoryData = [];
        static $cache = []; // Simple static cache to avoid duplicate API calls

        foreach ($prestashopCategoryIds as $categoryId) {
            // Check cache first
            $cacheKey = "{$shop->id}_{$categoryId}";

            if (isset($cache[$cacheKey])) {
                $categoryData[] = $cache[$cacheKey];
                Log::debug('CategoryPreviewModal: Category data from cache', [
                    'category_id' => $categoryId,
                    'data' => $cache[$cacheKey],
                ]);
                continue;
            }

            // Fetch from API
            try {
                $data = $this->getPrestaShopCategoryData($shop, $categoryId);

                if ($data && isset($data['name'])) {
                    // Build structured data object
                    $categoryObj = [
                        'id' => $categoryId, // PrestaShop ID (for reference)
                        'name' => $data['name'],
                        'level' => $data['level'] ?? 0,
                    ];

                    $categoryData[] = $categoryObj;
                    $cache[$cacheKey] = $categoryObj; // Cache structured data

                    Log::debug('CategoryPreviewModal: Fetched PrestaShop category data', [
                        'category_id' => $categoryId,
                        'data' => $categoryObj,
                    ]);
                } else {
                    // API returned null - use fallback
                    $fallback = [
                        'id' => $categoryId,
                        'name' => "PrestaShop ID: {$categoryId} (będzie zaimportowana)",
                        'level' => 0,
                    ];
                    $categoryData[] = $fallback;
                    $cache[$cacheKey] = $fallback;

                    Log::warning('CategoryPreviewModal: PrestaShop category data not found', [
                        'category_id' => $categoryId,
                        'fallback' => $fallback,
                    ]);
                }
            } catch (\Exception $e) {
                // API error - use fallback with ID
                $fallback = [
                    'id' => $categoryId,
                    'name' => "PrestaShop ID: {$categoryId} (błąd pobierania nazwy)",
                    'level' => 0,
                ];
                $categoryData[] = $fallback;
                $cache[$cacheKey] = $fallback;

                Log::error('CategoryPreviewModal: Failed to fetch PrestaShop category data', [
                    'category_id' => $categoryId,
                    'error' => $e->getMessage(),
                    'fallback' => $fallback,
                ]);
            }
        }

        Log::info('CategoryPreviewModal: Mapped PrestaShop category IDs to data', [
            'shop_id' => $shop->id,
            'category_count' => count($prestashopCategoryIds),
            'data' => $categoryData,
        ]);

        return $categoryData;
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
