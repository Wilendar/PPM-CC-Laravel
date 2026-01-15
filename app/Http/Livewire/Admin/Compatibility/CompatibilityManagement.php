<?php

namespace App\Http\Livewire\Admin\Compatibility;

use App\Http\Livewire\Admin\Compatibility\Traits\ManagesVehicleSelection;
use App\Models\CompatibilityAttribute;
use App\Models\CompatibilitySuggestion;
use App\Models\JobProgress;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\VehicleCompatibility;
use App\Services\Compatibility\ShopFilteringService;
use App\Services\Compatibility\SmartSuggestionEngine;
use App\Services\JobProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CompatibilityManagement Component
 *
 * ETAP_05d FAZA 3.1: Tile-based UI for mass compatibility editing
 *
 * Features:
 * - Tile-based vehicle selection (click = toggle)
 * - Per-shop filtering
 * - Smart suggestions display
 * - Bulk operations
 * - Collapsible brand sections
 */
class CompatibilityManagement extends Component
{
    use WithPagination, ManagesVehicleSelection;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** Current product for compatibility editing */
    public ?int $editingProductId = null;

    /** Per-shop context (null = default data) */
    public ?int $shopContext = null;

    /** Search filter for parts */
    public string $searchPart = '';

    /** Brand filter for vehicles */
    public string $filterBrand = '';

    /** Vehicle search within tiles */
    public string $vehicleSearch = '';

    /** Sort field */
    public string $sortField = 'sku';

    /** Sort direction */
    public string $sortDirection = 'asc';

    /** Expanded part IDs (accordion) */
    public array $expandedPartIds = [];

    /** Selected part IDs for bulk operations */
    public array $selectedPartIds = [];

    /** Collapsed brand sections */
    public array $collapsedBrands = [];

    /** Show/hide suggestions panel */
    public bool $showSuggestions = true;

    /** Minimum confidence for suggestions */
    public float $minConfidenceScore = 0.50;

    /** Show bulk edit modal */
    public bool $showBulkEditModal = false;

    /** View mode: 'list' or 'tiles' */
    public string $viewMode = 'list';

    /** Filter: show only parts without any matches */
    public bool $filterNoMatches = false;

    /** Sync job tracking - per-shop job IDs */
    public array $syncJobIds = [];

    /** Sync job statuses - per-shop */
    public array $syncJobStatuses = [];

    /** Timestamp when sync completed (for showing badge briefly) */
    public ?int $syncCompletedAt = null;

    protected $queryString = [
        'searchPart' => ['except' => ''],
        'shopContext' => ['except' => null],
        'filterBrand' => ['except' => ''],
        'filterNoMatches' => ['except' => false],
        'sortField' => ['except' => 'sku'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected ShopFilteringService $shopFilteringService;
    protected SmartSuggestionEngine $suggestionEngine;

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function boot(
        ShopFilteringService $shopFilteringService,
        SmartSuggestionEngine $suggestionEngine
    ): void {
        $this->shopFilteringService = $shopFilteringService;
        $this->suggestionEngine = $suggestionEngine;
    }

    public function mount(): void
    {
        $this->expandedPartIds = [];
        $this->selectedPartIds = [];
        $this->collapsedBrands = [];
    }

    public function render(): View
    {
        return view('livewire.admin.compatibility.compatibility-management', [
            'parts' => $this->parts,
            'shops' => $this->shops,
            'brands' => $this->brands,
            'vehicles' => $this->getVehiclesForShop(),
            'vehiclesGrouped' => $this->getVehiclesGroupedByBrand(),
            'suggestions' => $this->getSuggestions(),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get parts with compatibility data (paginated)
     */
    public function getPartsProperty(): LengthAwarePaginator
    {
        $query = Product::query()
            ->byType('czesc-zamienna')  // ProductType slug for spare parts
            ->with([
                'media' => fn($q) => $q->limit(1),
                'shopData' => fn($q) => $q->with('shop'),
            ]);

        // Search filter
        if (!empty($this->searchPart)) {
            $search = '%' . $this->searchPart . '%';
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        // Filter: only parts WITHOUT any matches
        if ($this->filterNoMatches) {
            $shopFilter = $this->shopContext;
            $query->whereDoesntHave('vehicleCompatibility', function ($q) use ($shopFilter) {
                if ($shopFilter !== null) {
                    $q->where('shop_id', $shopFilter);
                }
            });
        }

        // Sort
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate
        $parts = $query->paginate(50);

        // Add compatibility counts
        $productIds = $parts->pluck('id')->toArray();
        $compatCounts = $this->getCompatibilityCountsForProducts($productIds);

        $parts->getCollection()->transform(function ($part) use ($compatCounts) {
            $counts = $compatCounts[$part->id] ?? ['original' => 0, 'replacement' => 0];
            $part->original_count = $counts['original'];
            $part->replacement_count = $counts['replacement'];

            // Calculate sync status for this product
            $publishedShops = $part->shopData->where('is_published', true);
            if ($publishedShops->isEmpty()) {
                $part->sync_status = 'not_published';
                $part->sync_shops_count = 0;
            } else {
                $part->sync_status = 'synced';
                $part->sync_shops_count = $publishedShops->count();
                $part->sync_shop_names = $publishedShops->pluck('shop.name')->filter()->implode(', ');
            }

            return $part;
        });

        return $parts;
    }

    /**
     * Get available shops
     */
    public function getShopsProperty(): Collection
    {
        return PrestaShopShop::orderBy('name')->get();
    }

    /**
     * Get unique vehicle brands
     */
    public function getBrandsProperty(): Collection
    {
        return $this->shopFilteringService->getAllBrands();
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE DATA METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get vehicles filtered by shop context
     *
     * 2025-12-08: Uses Product::byType('pojazd') - FK now points to products table
     * Products with type 'pojazd' have: manufacturer (brand), name (model)
     */
    public function getVehiclesForShop(): Collection
    {
        // Query products with type='pojazd' (vehicle products)
        $query = Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->orderBy('manufacturer')
            ->orderBy('name');

        // Apply shop context filtering if set
        if ($this->shopContext !== null) {
            $shop = PrestaShopShop::find($this->shopContext);
            if ($shop && $shop->allowed_vehicle_brands !== null) {
                if (empty($shop->allowed_vehicle_brands)) {
                    // Empty array = no brands allowed
                    return collect();
                }
                // Filter by allowed brands (manufacturer column for products)
                $query->whereIn('manufacturer', $shop->allowed_vehicle_brands);
            }
        }

        $vehicles = $query->get();

        // Apply brand filter (manufacturer for products)
        if (!empty($this->filterBrand)) {
            $vehicles = $vehicles->filter(fn($v) => $v->manufacturer === $this->filterBrand);
        }

        // Apply vehicle search
        if (!empty($this->vehicleSearch)) {
            $search = strtolower($this->vehicleSearch);
            $vehicles = $vehicles->filter(function ($v) use ($search) {
                return str_contains(strtolower($v->manufacturer ?? ''), $search)
                    || str_contains(strtolower($v->name ?? ''), $search);
            });
        }

        return $vehicles;
    }

    /**
     * Get vehicles grouped by brand (manufacturer for products)
     */
    public function getVehiclesGroupedByBrand(): Collection
    {
        return $this->getVehiclesForShop()->groupBy('manufacturer');
    }

    /**
     * Get compatibility counts for multiple products
     */
    protected function getCompatibilityCountsForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $query = VehicleCompatibility::query()
            ->selectRaw('product_id, compatibility_attribute_id, COUNT(*) as count')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id', 'compatibility_attribute_id');

        if ($this->shopContext !== null) {
            $query->where('shop_id', $this->shopContext);
        }

        $results = $query->get();

        // Get attribute codes
        $attributes = CompatibilityAttribute::pluck('code', 'id')->toArray();

        $counts = [];
        foreach ($results as $row) {
            $productId = $row->product_id;
            $attrCode = $attributes[$row->compatibility_attribute_id] ?? 'unknown';

            if (!isset($counts[$productId])) {
                $counts[$productId] = ['original' => 0, 'replacement' => 0];
            }

            if ($attrCode === 'original') {
                $counts[$productId]['original'] = $row->count;
            } elseif ($attrCode === 'replacement') {
                $counts[$productId]['replacement'] = $row->count;
            }
        }

        return $counts;
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get smart suggestions for current product
     */
    public function getSuggestions(): Collection
    {
        if ($this->editingProductId === null || !$this->showSuggestions) {
            return collect();
        }

        $query = CompatibilitySuggestion::query()
            ->where('product_id', $this->editingProductId)
            ->where('confidence_score', '>=', $this->minConfidenceScore)
            ->where('is_applied', false)
            ->where('is_dismissed', false)
            ->orderByDesc('confidence_score')
            ->with('vehicleModel');

        if ($this->shopContext !== null) {
            $query->where('shop_id', $this->shopContext);
        }

        return $query->limit(20)->get();
    }

    /**
     * Apply a suggestion
     */
    public function applySuggestion(int $suggestionId, string $type = 'original'): void
    {
        $suggestion = CompatibilitySuggestion::find($suggestionId);

        if (!$suggestion) {
            $this->dispatch('flash-message', message: 'Sugestia nie znaleziona', type: 'error');
            return;
        }

        try {
            $suggestion->apply(auth()->user());

            // Update local selection
            if ($type === 'original') {
                $this->addAsOriginal($suggestion->vehicle_model_id);
            } else {
                $this->addAsZamiennik($suggestion->vehicle_model_id);
            }

            $this->dispatch('flash-message', message: 'Sugestia zastosowana', type: 'success');
        } catch (\Exception $e) {
            Log::error('CompatibilityManagement: applySuggestion failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', message: 'Blad: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Dismiss a suggestion
     */
    public function dismissSuggestion(int $suggestionId): void
    {
        $suggestion = CompatibilitySuggestion::find($suggestionId);

        if ($suggestion) {
            $suggestion->dismiss(auth()->user());
            $this->dispatch('flash-message', message: 'Sugestia odrzucona', type: 'info');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PART EDITING
    |--------------------------------------------------------------------------
    */

    /**
     * Start editing a part's compatibility
     */
    public function editPart(int $productId): void
    {
        $this->editingProductId = $productId;
        $this->initializeSelections($productId, $this->shopContext);

        // Expand the part row
        if (!in_array($productId, $this->expandedPartIds)) {
            $this->expandedPartIds[] = $productId;
        }
    }

    /**
     * Save compatibility changes for current product
     */
    public function saveCompatibility(): void
    {
        if ($this->editingProductId === null) {
            $this->dispatch('flash-message', message: 'Wybierz produkt przed zapisem', type: 'warning');
            return;
        }

        $product = Product::find($this->editingProductId);
        if (!$product) {
            $this->dispatch('flash-message', message: 'Produkt nie znaleziony', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            // Get attribute IDs
            $originalAttrId = CompatibilityAttribute::where('code', 'original')->value('id');
            $replacementAttrId = CompatibilityAttribute::where('code', 'replacement')->value('id');
            $defaultSourceId = 1; // Default source

            // Delete existing for this product/shop
            $deleteQuery = VehicleCompatibility::where('product_id', $this->editingProductId);
            if ($this->shopContext !== null) {
                $deleteQuery->where('shop_id', $this->shopContext);
            }
            $deleteQuery->delete();

            // Insert Original
            $insertedOriginal = 0;
            foreach ($this->selectedOriginal as $vehicleId) {
                VehicleCompatibility::create([
                    'product_id' => $this->editingProductId,
                    'vehicle_model_id' => $vehicleId,
                    'shop_id' => $this->shopContext ?? $this->getDefaultShopId(),
                    'compatibility_attribute_id' => $originalAttrId,
                    'compatibility_source_id' => $defaultSourceId,
                    'verified' => false,
                    'is_suggested' => false,
                ]);
                $insertedOriginal++;
            }

            // Insert Zamiennik
            // NOTE: Same vehicle CAN have both Original AND Zamiennik - create separate records
            $insertedZamiennik = 0;
            foreach ($this->selectedZamiennik as $vehicleId) {
                VehicleCompatibility::create([
                    'product_id' => $this->editingProductId,
                    'vehicle_model_id' => $vehicleId,
                    'shop_id' => $this->shopContext ?? $this->getDefaultShopId(),
                    'compatibility_attribute_id' => $replacementAttrId,
                    'compatibility_source_id' => $defaultSourceId,
                    'verified' => false,
                    'is_suggested' => false,
                ]);
                $insertedZamiennik++;
            }

            DB::commit();

            $this->pendingChanges = [];
            // Remove saved product from unsaved changes list
            $this->productsWithUnsavedChanges = array_values(
                array_diff($this->productsWithUnsavedChanges, [$this->editingProductId])
            );
            $this->dispatch('flash-message', message: "Zapisano: $insertedOriginal oryginalnych, $insertedZamiennik zamiennikow", type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CompatibilityManagement: saveCompatibility failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', message: 'Blad zapisu: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Save compatibility and sync to PrestaShop
     */
    public function saveAndSync(): void
    {
        // First save the changes
        $this->saveCompatibility();

        // Reset job tracking arrays
        $this->syncJobIds = [];
        $this->syncJobStatuses = [];

        // Then trigger sync to PrestaShop (if product has shop associations)
        if ($this->editingProductId) {
            $product = Product::find($this->editingProductId);
            if ($product) {
                // Get all shops where product is published
                $shopData = $product->shopData()->with('shop')->get();
                $syncedShops = 0;
                $jobProgressService = app(JobProgressService::class);

                foreach ($shopData as $data) {
                    if ($data->shop && $data->is_published) {
                        // Generate unique job ID
                        $jobId = Str::uuid()->toString();

                        // Create PENDING job progress BEFORE dispatch
                        // NOTE: Using 'sync' type (not 'compat_sync') to match DB ENUM
                        $progressId = $jobProgressService->createPendingJobProgress(
                            $jobId,
                            $data->shop,
                            'sync',
                            1 // Single product sync
                        );

                        // Store job info for UI tracking
                        $this->syncJobIds[$data->shop->id] = $progressId;
                        $this->syncJobStatuses[$data->shop->id] = 'pending';

                        // Dispatch with pre-generated job ID for tracking
                        // Constructor: Product, Shop, userId, pendingMediaChanges, preGeneratedJobId
                        \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch(
                            $product,
                            $data->shop,
                            auth()->id(), // userId
                            [], // pendingMediaChanges
                            $jobId // preGeneratedJobId
                        );
                        $syncedShops++;

                        Log::info('CompatibilityManagement: Dispatched sync job', [
                            'product_id' => $product->id,
                            'shop_id' => $data->shop->id,
                            'job_id' => $jobId,
                            'progress_id' => $progressId,
                        ]);
                    }
                }

                if ($syncedShops > 0) {
                    $this->dispatch('flash-message', message: "Dopasowania zapisane - synchronizacja z {$syncedShops} sklepami w toku...", type: 'info');
                } else {
                    $this->dispatch('flash-message', message: 'Dopasowania zapisane (produkt nie jest opublikowany w zadnym sklepie)', type: 'info');
                }
            }
        }
    }

    /**
     * Refresh sync job statuses (called by wire:poll)
     */
    public function refreshSyncStatus(): void
    {
        if (empty($this->syncJobIds)) {
            return;
        }

        $allCompleted = true;

        foreach ($this->syncJobIds as $shopId => $progressId) {
            $progress = JobProgress::find($progressId);

            if ($progress) {
                $this->syncJobStatuses[$shopId] = $progress->status;

                if (!in_array($progress->status, ['completed', 'failed'])) {
                    $allCompleted = false;
                }
            }
        }

        // If all jobs completed, mark completion time (don't clear immediately)
        if ($allCompleted && !empty($this->syncJobIds) && $this->syncCompletedAt === null) {
            $this->syncCompletedAt = time();

            // Show success message
            $completedCount = collect($this->syncJobStatuses)->filter(fn($s) => $s === 'completed')->count();
            $failedCount = collect($this->syncJobStatuses)->filter(fn($s) => $s === 'failed')->count();

            if ($failedCount > 0) {
                $this->dispatch('flash-message', message: "Synchronizacja zakonczona: {$completedCount} OK, {$failedCount} bledow", type: 'warning');
            } else {
                $this->dispatch('flash-message', message: "Synchronizacja zakonczona pomyslnie ({$completedCount} sklepow)", type: 'success');
            }
        }

        // Clear tracking 5 seconds after completion
        if ($this->syncCompletedAt !== null && (time() - $this->syncCompletedAt) >= 5) {
            $this->syncJobIds = [];
            $this->syncJobStatuses = [];
            $this->syncCompletedAt = null;
        }
    }

    /**
     * Check if any sync jobs are active or recently completed
     */
    public function hasSyncJobsActive(): bool
    {
        // Show badge if jobs are running OR completed within last 5 seconds
        return !empty($this->syncJobIds) || $this->syncCompletedAt !== null;
    }

    /**
     * Get overall sync status for display
     */
    public function getOverallSyncStatus(): string
    {
        if (empty($this->syncJobStatuses)) {
            return 'idle';
        }

        $statuses = array_values($this->syncJobStatuses);

        if (in_array('running', $statuses)) {
            return 'running';
        }
        if (in_array('pending', $statuses)) {
            return 'pending';
        }
        if (in_array('failed', $statuses)) {
            return 'failed';
        }

        return 'completed';
    }

    /**
     * Check if sync is currently in progress (pending or running)
     */
    public function isSyncInProgress(): bool
    {
        $status = $this->getOverallSyncStatus();
        return in_array($status, ['pending', 'running']);
    }

    /**
     * Cancel editing
     */
    public function cancelEdit(): void
    {
        // Remove current product from unsaved changes list (changes are being discarded)
        if ($this->editingProductId) {
            $this->productsWithUnsavedChanges = array_values(
                array_diff($this->productsWithUnsavedChanges, [$this->editingProductId])
            );
        }

        $this->editingProductId = null;
        $this->clearAllSelections();
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP CONTEXT
    |--------------------------------------------------------------------------
    */

    /**
     * Switch shop context
     */
    public function switchShopContext(?int $shopId): void
    {
        $this->shopContext = $shopId;

        // Reinitialize selections if editing
        if ($this->editingProductId !== null) {
            $this->initializeSelections($this->editingProductId, $shopId);
        }

        $this->dispatch('flash-message',
            message: $shopId ? 'Kontekst sklepu zmieniony' : 'Widok danych domyslnych',
            type: 'info'
        );
    }

    /**
     * Get default shop ID
     * Returns first active shop ID or fallback to 1
     */
    protected function getDefaultShopId(): int
    {
        return PrestaShopShop::active()->orderBy('id')->value('id') ?? 1;
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */

    public function toggleExpand(int $partId): void
    {
        if (in_array($partId, $this->expandedPartIds)) {
            $this->expandedPartIds = array_values(array_diff($this->expandedPartIds, [$partId]));
        } else {
            $this->expandedPartIds[] = $partId;
        }
    }

    public function isExpanded(int $partId): bool
    {
        return in_array($partId, $this->expandedPartIds);
    }

    public function toggleBrandCollapse(string $brand): void
    {
        if (in_array($brand, $this->collapsedBrands)) {
            $this->collapsedBrands = array_values(array_diff($this->collapsedBrands, [$brand]));
        } else {
            $this->collapsedBrands[] = $brand;
        }
    }

    public function isBrandCollapsed(string $brand): bool
    {
        return in_array($brand, $this->collapsedBrands);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->searchPart = '';
        $this->filterBrand = '';
        $this->vehicleSearch = '';
        $this->filterNoMatches = false;
        $this->shopContext = null;
        $this->resetPage();
    }

    public function updatedSearchPart(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBrand(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoMatches(): void
    {
        $this->resetPage();
    }

    public function togglePartSelection(int $partId): void
    {
        if (in_array($partId, $this->selectedPartIds)) {
            $this->selectedPartIds = array_values(array_diff($this->selectedPartIds, [$partId]));
        } else {
            $this->selectedPartIds[] = $partId;
        }
    }

    public function openBulkEdit(): void
    {
        if (count($this->selectedPartIds) === 0) {
            $this->dispatch('flash-message', message: 'Zaznacz przynajmniej 1 czesc', type: 'warning');
            return;
        }

        $this->dispatch('open-bulk-modal', [
            'direction' => 'part_to_vehicle',
            'selectedIds' => $this->selectedPartIds
        ]);
    }

    /**
     * Get statistics for display
     */
    public function getStatistics(): array
    {
        if ($this->shopContext !== null) {
            $shop = PrestaShopShop::find($this->shopContext);
            if ($shop) {
                return $this->shopFilteringService->getShopStatistics($shop);
            }
        }

        return [
            'total_compatibilities' => VehicleCompatibility::count(),
            'unique_products' => VehicleCompatibility::distinct('product_id')->count('product_id'),
            'unique_vehicles' => VehicleCompatibility::distinct('vehicle_model_id')->count('vehicle_model_id'),
            'available_vehicles' => Product::byType('pojazd')->count(),
        ];
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) {
            return 'status-badge-full';
        } elseif ($originalCount > 0 || $replacementCount > 0) {
            return 'status-badge-partial';
        }
        return 'status-badge-none';
    }

    /**
     * Get status badge label
     */
    public function getStatusBadgeLabel(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) {
            return 'Pelny';
        } elseif ($originalCount > 0 || $replacementCount > 0) {
            return 'Czesciowy';
        }
        return 'Brak';
    }
}
