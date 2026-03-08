<?php

namespace App\Http\Livewire\Admin\Compatibility;

use App\Http\Livewire\Admin\Compatibility\Traits\ManagesCompatibilityFilters;
use App\Http\Livewire\Admin\Compatibility\Traits\ManagesFilterPresets;
use App\Models\CompatibilityAttribute;
use App\Models\PendingProduct;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * PendingCompatibilityTab - Manages vehicle compatibility for pending (unpublished) products
 *
 * Tab 2 of CompatibilityManagement panel.
 * Uses PendingProduct.compatibility_data JSON for bidirectional sync with Import modal.
 */
class PendingCompatibilityTab extends Component
{
    use AuthorizesRequests, WithPagination;
    use ManagesCompatibilityFilters, ManagesFilterPresets;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    public ?int $expandedPendingId = null;
    public array $selectedOriginal = [];
    public array $selectedZamiennik = [];
    public string $selectionMode = 'original';
    public array $collapsedBrands = [];

    /** Override preset context for pending tab */
    protected string $presetContext = 'pending_compatibility';

    protected $queryString = [
        'searchPart' => ['except' => '', 'as' => 'ps'],
        'filterBrand' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterManufacturer' => ['except' => ''],
        'filterCompatCountRange' => ['except' => ''],
        'sortField' => ['except' => 'sku'],
        'sortDirection' => ['except' => 'asc'],
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->authorize('compatibility.read');
        $this->authorize('import.read');
        $this->loadDefaultPreset();
    }

    public function render(): View
    {
        return view('livewire.admin.compatibility.pending-compatibility-tab', [
            'pendingParts' => $this->pendingParts,
            'vehiclesGrouped' => $this->getVehiclesGroupedByBrand(),
            'brands' => $this->brands,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    public function getPendingPartsProperty()
    {
        $query = PendingProduct::unpublished()
            ->whereHas('productType', fn($q) => $q->where('slug', 'czesc-zamienna'))
            ->with('productType');

        // Search filter (uses searchPart from trait)
        if (!empty($this->searchPart)) {
            $search = '%' . $this->searchPart . '%';
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        // Manufacturer filter
        if (!empty($this->filterManufacturer)) {
            $query->where('manufacturer', $this->filterManufacturer);
        }

        // Sort
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(30);
    }

    public function getBrandsProperty(): Collection
    {
        return Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer');
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE DATA
    |--------------------------------------------------------------------------
    */

    public function getVehiclesGroupedByBrand(): Collection
    {
        $query = Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->orderBy('manufacturer')
            ->orderBy('name');

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

        return $vehicles->groupBy('manufacturer');
    }

    /*
    |--------------------------------------------------------------------------
    | EXPAND/COLLAPSE
    |--------------------------------------------------------------------------
    */

    public function expandProduct(int $id): void
    {
        $this->expandedPendingId = $id;
        $this->selectionMode = 'original';
        $this->selectedOriginal = [];
        $this->selectedZamiennik = [];

        $pendingProduct = PendingProduct::find($id);
        if (!$pendingProduct) return;

        $data = $pendingProduct->compatibility_data ?? [];
        if (empty($data['compatibilities'])) return;

        foreach ($data['compatibilities'] as $compat) {
            $vehicleId = $compat['vehicle_id'] ?? null;
            $attrName = strtolower($compat['compatibility_attribute_name'] ?? '');
            $attrName = str_replace(
                ['ą','ć','ę','ł','ń','ó','ś','ź','ż'],
                ['a','c','e','l','n','o','s','z','z'],
                $attrName
            );

            if (!$vehicleId) continue;

            if (str_contains($attrName, 'oryginal') || str_contains($attrName, 'original')) {
                $this->selectedOriginal[] = $vehicleId;
            } elseif (str_contains($attrName, 'zamiennik') || str_contains($attrName, 'replacement')) {
                $this->selectedZamiennik[] = $vehicleId;
            }
        }

        $this->selectedOriginal = array_values(array_unique($this->selectedOriginal));
        $this->selectedZamiennik = array_values(array_unique($this->selectedZamiennik));
    }

    public function collapseProduct(): void
    {
        $this->expandedPendingId = null;
        $this->selectedOriginal = [];
        $this->selectedZamiennik = [];
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE SELECTION
    |--------------------------------------------------------------------------
    */

    public function toggleVehicle(int $vehicleId): void
    {
        if ($this->selectionMode === 'original') {
            if (in_array($vehicleId, $this->selectedOriginal)) {
                $this->selectedOriginal = array_values(array_diff($this->selectedOriginal, [$vehicleId]));
            } else {
                $this->selectedOriginal[] = $vehicleId;
            }
        } else {
            if (in_array($vehicleId, $this->selectedZamiennik)) {
                $this->selectedZamiennik = array_values(array_diff($this->selectedZamiennik, [$vehicleId]));
            } else {
                $this->selectedZamiennik[] = $vehicleId;
            }
        }
    }

    public function setSelectionMode(string $mode): void
    {
        if (in_array($mode, ['original', 'zamiennik'])) {
            $this->selectionMode = $mode;
        }
    }

    public function selectAllInBrand(string $brand): void
    {
        $vehicles = $this->getVehiclesGroupedByBrand()->get($brand, collect());
        foreach ($vehicles as $vehicle) {
            if ($this->selectionMode === 'original') {
                if (!in_array($vehicle->id, $this->selectedOriginal)) {
                    $this->selectedOriginal[] = $vehicle->id;
                }
            } else {
                if (!in_array($vehicle->id, $this->selectedZamiennik)) {
                    $this->selectedZamiennik[] = $vehicle->id;
                }
            }
        }
    }

    public function deselectAllInBrand(string $brand): void
    {
        $vehicles = $this->getVehiclesGroupedByBrand()->get($brand, collect());
        $vehicleIds = $vehicles->pluck('id')->toArray();

        if ($this->selectionMode === 'original') {
            $this->selectedOriginal = array_values(array_diff($this->selectedOriginal, $vehicleIds));
        } else {
            $this->selectedZamiennik = array_values(array_diff($this->selectedZamiennik, $vehicleIds));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VEHICLE STATE HELPERS
    |--------------------------------------------------------------------------
    */

    public function isOriginal(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->selectedOriginal);
    }

    public function isZamiennik(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->selectedZamiennik);
    }

    public function isBoth(int $vehicleId): bool
    {
        return $this->isOriginal($vehicleId) && $this->isZamiennik($vehicleId);
    }

    public function getVehicleStateClass(int $vehicleId): string
    {
        if ($this->isBoth($vehicleId)) return 'vehicle-tile--selected-both';
        if ($this->isOriginal($vehicleId)) return 'vehicle-tile--selected-original';
        if ($this->isZamiennik($vehicleId)) return 'vehicle-tile--selected-zamiennik';
        return '';
    }

    public function getOriginalCount(): int
    {
        return count($this->selectedOriginal);
    }

    public function getZamiennikCount(): int
    {
        return count($this->selectedZamiennik);
    }

    /*
    |--------------------------------------------------------------------------
    | BRAND COLLAPSE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    public function saveCompatibilities(): void
    {
        if (!$this->expandedPendingId) return;

        $pendingProduct = PendingProduct::find($this->expandedPendingId);
        if (!$pendingProduct) {
            $this->dispatch('flash-message', message: 'Nie znaleziono produktu', type: 'error');
            return;
        }

        try {
            $originalAttr = CompatibilityAttribute::where('code', 'original')->first();
            $replacementAttr = CompatibilityAttribute::where('code', 'replacement')->first();

            $compatibilities = [];

            foreach ($this->selectedOriginal as $vehicleId) {
                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $originalAttr?->id,
                    'compatibility_attribute_name' => $originalAttr?->name ?? 'Oryginal',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                ];
            }

            foreach ($this->selectedZamiennik as $vehicleId) {
                $vehicle = Product::find($vehicleId);
                if (!$vehicle) continue;

                $compatibilities[] = [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_sku' => $vehicle->sku,
                    'vehicle_name' => $vehicle->name,
                    'compatibility_attribute_id' => $replacementAttr?->id,
                    'compatibility_attribute_name' => $replacementAttr?->name ?? 'Zamiennik',
                    'compatibility_source_id' => 1,
                    'compatibility_source_name' => 'Manual',
                ];
            }

            $pendingProduct->update([
                'compatibility_data' => [
                    'compatibilities' => $compatibilities,
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

            Log::info('[PendingCompatibilityTab] Saved compatibilities', [
                'pending_product_id' => $this->expandedPendingId,
                'original_count' => count($this->selectedOriginal),
                'zamiennik_count' => count($this->selectedZamiennik),
            ]);

            $this->dispatch('flash-message',
                message: 'Zapisano ' . count($compatibilities) . ' dopasowan',
                type: 'success'
            );

            $this->collapseProduct();

        } catch (\Exception $e) {
            Log::error('[PendingCompatibilityTab] Save failed', [
                'pending_product_id' => $this->expandedPendingId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message',
                message: 'Blad zapisu: ' . $e->getMessage(),
                type: 'error'
            );
        }
    }

    /**
     * Get compatibility counts for a pending product from JSON
     */
    public function getCompatCounts(PendingProduct $product): array
    {
        $data = $product->compatibility_data ?? [];
        $compatibilities = $data['compatibilities'] ?? [];

        $original = 0;
        $zamiennik = 0;

        foreach ($compatibilities as $compat) {
            $attrName = strtolower($compat['compatibility_attribute_name'] ?? '');
            $attrName = str_replace(
                ['ą','ć','ę','ł','ń','ó','ś','ź','ż'],
                ['a','c','e','l','n','o','s','z','z'],
                $attrName
            );

            if (str_contains($attrName, 'oryginal') || str_contains($attrName, 'original')) {
                $original++;
            } elseif (str_contains($attrName, 'zamiennik') || str_contains($attrName, 'replacement')) {
                $zamiennik++;
            }
        }

        return ['original' => $original, 'zamiennik' => $zamiennik];
    }
}
