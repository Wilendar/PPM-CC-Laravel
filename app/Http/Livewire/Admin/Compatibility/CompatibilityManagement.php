<?php

namespace App\Http\Livewire\Admin\Compatibility;

use App\Http\Livewire\Admin\Compatibility\Traits\ManagesBulkActions;
use App\Http\Livewire\Admin\Compatibility\Traits\ManagesCompatibilityFilters;
use App\Http\Livewire\Admin\Compatibility\Traits\ManagesFilterPresets;
use App\Http\Livewire\Admin\Compatibility\Traits\ManagesSyncJobs;
use App\Http\Livewire\Admin\Compatibility\Traits\ManagesVehicleSelection;
use App\Models\CompatibilityAttribute;
use App\Models\CompatibilitySuggestion;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\VehicleCompatibility;
use App\Services\Compatibility\ShopFilteringService;
use App\Services\Compatibility\SmartSuggestionEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CompatibilityManagement Component
 *
 * Tile-based UI for mass compatibility editing.
 * Uses traits for filters, presets, bulk actions, sync jobs, and vehicle selection.
 */
class CompatibilityManagement extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use ManagesVehicleSelection;
    use ManagesCompatibilityFilters;
    use ManagesFilterPresets;
    use ManagesBulkActions;
    use ManagesSyncJobs;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** Filter preset context identifier */
    protected string $presetContext = 'compatibility_management';

    /** Current product for compatibility editing */
    public ?int $editingProductId = null;

    /** Per-shop context (null = default data) */
    public ?int $shopContext = null;

    /** Expanded part IDs (accordion) */
    public array $expandedPartIds = [];

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

    /** Map of vehicle_id => score for AI suggested vehicles */
    public array $suggestedVehicleScores = [];

    /** Active tab: 'published' or 'pending' */
    public string $activeTab = 'published';

    protected $queryString = [
        'searchPart' => ['except' => ''],
        'shopContext' => ['except' => null],
        'filterBrand' => ['except' => ''],
        'filterNoMatches' => ['except' => false],
        'filterCategory' => ['except' => ''],
        'filterShopAssignment' => ['except' => ''],
        'filterManufacturer' => ['except' => ''],
        'filterCompatCountRange' => ['except' => ''],
        'sortField' => ['except' => 'sku'],
        'sortDirection' => ['except' => 'asc'],
        'activeTab' => ['except' => 'published'],
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
        $this->authorize('compatibility.read');
        $this->expandedPartIds = [];
        $this->collapsedBrands = [];
        $this->loadDefaultPreset();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['published', 'pending'])) {
            $this->activeTab = $tab;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.compatibility.compatibility-management', [
            'pendingCount' => $this->pendingCount,
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

    public function getPartsProperty(): LengthAwarePaginator
    {
        $query = Product::query()
            ->byType('czesc-zamienna')
            ->with([
                'media' => fn($q) => $q->limit(1),
                'shopData' => fn($q) => $q->with('shop'),
            ]);

        // Apply all filters from trait
        $this->applyPartFilters($query);

        $parts = $query->paginate(50);

        // Add compatibility counts
        $productIds = $parts->pluck('id')->toArray();
        $compatCounts = $this->getCompatibilityCountsForProducts($productIds);

        $parts->getCollection()->transform(function ($part) use ($compatCounts) {
            $counts = $compatCounts[$part->id] ?? ['original' => 0, 'replacement' => 0];
            $part->original_count = $counts['original'];
            $part->replacement_count = $counts['replacement'];

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

    public function getShopsProperty(): Collection
    {
        return PrestaShopShop::orderBy('name')->get();
    }

    public function getBrandsProperty(): Collection
    {
        return $this->shopFilteringService->getAllBrands();
    }

    public function getPendingCountProperty(): int
    {
        return \App\Models\PendingProduct::unpublished()
            ->whereHas('productType', fn($q) => $q->where('slug', 'czesc-zamienna'))
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE DATA METHODS
    |--------------------------------------------------------------------------
    */

    public function getVehiclesForShop(): Collection
    {
        $query = Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->orderBy('manufacturer')
            ->orderBy('name');

        if ($this->shopContext !== null) {
            $shop = PrestaShopShop::find($this->shopContext);
            if ($shop && $shop->allowed_vehicle_brands !== null) {
                if (empty($shop->allowed_vehicle_brands)) {
                    return collect();
                }
                $query->whereIn('manufacturer', $shop->allowed_vehicle_brands);
            }
        }

        $vehicles = $query->get();

        if (!empty($this->filterBrand)) {
            $vehicles = $vehicles->filter(fn($v) => $v->manufacturer === $this->filterBrand);
        }

        if (!empty($this->vehicleSearch)) {
            $search = strtolower($this->vehicleSearch);
            $vehicles = $vehicles->filter(function ($v) use ($search) {
                return str_contains(strtolower($v->manufacturer ?? ''), $search)
                    || str_contains(strtolower($v->name ?? ''), $search);
            });
        }

        return $vehicles;
    }

    public function getVehiclesGroupedByBrand(): Collection
    {
        return $this->getVehiclesForShop()->groupBy('manufacturer');
    }

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

    public function applySuggestion(int $suggestionId, string $type = 'original'): void
    {
        $suggestion = CompatibilitySuggestion::find($suggestionId);
        if (!$suggestion) {
            $this->dispatch('flash-message', message: 'Sugestia nie znaleziona', type: 'error');
            return;
        }

        try {
            $suggestion->apply(auth()->user());
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

    public function editPart(int $productId): void
    {
        $this->editingProductId = $productId;
        $this->initializeSelections($productId, $this->shopContext);
        $this->loadSuggestionsForProduct($productId);

        if (!in_array($productId, $this->expandedPartIds)) {
            $this->expandedPartIds[] = $productId;
        }
    }

    public function loadSuggestionsForProduct(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->suggestedVehicleScores = [];
            return;
        }

        try {
            $suggestions = $this->suggestionEngine->generateForProductCentral($product);
            $this->suggestedVehicleScores = [];
            foreach ($suggestions as $suggestion) {
                $this->suggestedVehicleScores[$suggestion['vehicle_id']] = $suggestion['score'];
            }
        } catch (\Exception $e) {
            Log::error('CompatibilityManagement: loadSuggestionsForProduct failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            $this->suggestedVehicleScores = [];
        }
    }

    public function dismissAiSuggestion(int $vehicleId): void
    {
        if (!$this->editingProductId) return;

        \App\Models\SmartSuggestionDismissal::updateOrCreate(
            ['product_id' => $this->editingProductId, 'vehicle_product_id' => $vehicleId],
            ['dismissed_by' => auth()->id(), 'dismissed_at' => now(), 'restored_at' => null, 'restored_by' => null]
        );

        unset($this->suggestedVehicleScores[$vehicleId]);
    }

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

            $originalAttrId = CompatibilityAttribute::where('code', 'original')->value('id');
            $replacementAttrId = CompatibilityAttribute::where('code', 'replacement')->value('id');

            $deleteQuery = VehicleCompatibility::where('product_id', $this->editingProductId);
            if ($this->shopContext !== null) {
                $deleteQuery->where('shop_id', $this->shopContext);
            }
            $deleteQuery->delete();

            $insertedOriginal = 0;
            foreach ($this->selectedOriginal as $vehicleId) {
                VehicleCompatibility::create([
                    'product_id' => $this->editingProductId,
                    'vehicle_model_id' => $vehicleId,
                    'shop_id' => $this->shopContext ?? $this->getDefaultShopId(),
                    'compatibility_attribute_id' => $originalAttrId,
                    'compatibility_source_id' => 1,
                    'verified' => false,
                    'is_suggested' => false,
                ]);
                $insertedOriginal++;
            }

            $insertedZamiennik = 0;
            foreach ($this->selectedZamiennik as $vehicleId) {
                VehicleCompatibility::create([
                    'product_id' => $this->editingProductId,
                    'vehicle_model_id' => $vehicleId,
                    'shop_id' => $this->shopContext ?? $this->getDefaultShopId(),
                    'compatibility_attribute_id' => $replacementAttrId,
                    'compatibility_source_id' => 1,
                    'verified' => false,
                    'is_suggested' => false,
                ]);
                $insertedZamiennik++;
            }

            DB::commit();

            $this->pendingChanges = [];
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

    public function cancelEdit(): void
    {
        if ($this->editingProductId) {
            $this->productsWithUnsavedChanges = array_values(
                array_diff($this->productsWithUnsavedChanges, [$this->editingProductId])
            );
        }
        $this->editingProductId = null;
        $this->clearAllSelections();
    }

    /**
     * Override trait's resetFilters to also reset shopContext
     */
    public function resetFilters(): void
    {
        $this->shopContext = null;
        // Call the trait's reset via explicit property reset
        $this->searchPart = '';
        $this->filterBrand = '';
        $this->vehicleSearch = '';
        $this->filterNoMatches = false;
        $this->filterCategory = '';
        $this->filterShopAssignment = '';
        $this->filterManufacturer = '';
        $this->filterCompatCountRange = '';
        $this->sortField = 'sku';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP CONTEXT
    |--------------------------------------------------------------------------
    */

    public function switchShopContext(?int $shopId): void
    {
        $this->shopContext = $shopId;
        if ($this->editingProductId !== null) {
            $this->initializeSelections($this->editingProductId, $shopId);
        }
        $this->dispatch('flash-message',
            message: $shopId ? 'Kontekst sklepu zmieniony' : 'Widok danych domyslnych',
            type: 'info'
        );
    }

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

    public function getStatusBadgeClass(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) return 'status-badge-full';
        if ($originalCount > 0 || $replacementCount > 0) return 'status-badge-partial';
        return 'status-badge-none';
    }

    public function getStatusBadgeLabel(int $originalCount, int $replacementCount): string
    {
        if ($originalCount > 0 && $replacementCount > 0) return 'Pelny';
        if ($originalCount > 0 || $replacementCount > 0) return 'Czesciowy';
        return 'Brak';
    }

    public function canAccessPendingTab(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('compatibility.read') && $user->can('import.read');
    }
}
