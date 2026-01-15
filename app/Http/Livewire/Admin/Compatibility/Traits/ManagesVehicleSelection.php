<?php

namespace App\Http\Livewire\Admin\Compatibility\Traits;

use App\Models\VehicleCompatibility;
use App\Models\VehicleModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ManagesVehicleSelection Trait
 *
 * ETAP_05d FAZA 3.2 - Vehicle selection logic for tile-based UI
 *
 * Handles:
 * - Original/Zamiennik vehicle selection
 * - Tile toggle logic (click = toggle selection)
 * - Bulk selection (select all in brand)
 * - State management for selections
 */
trait ManagesVehicleSelection
{
    /**
     * Selected vehicle IDs for Oryginal type
     */
    public array $selectedOriginal = [];

    /**
     * Selected vehicle IDs for Zamiennik type
     */
    public array $selectedZamiennik = [];

    /**
     * Currently active selection mode
     * 'original' | 'zamiennik'
     */
    public string $selectionMode = 'original';

    /**
     * Vehicle IDs that are pending save
     */
    public array $pendingChanges = [];

    /**
     * Product IDs that have unsaved changes
     */
    public array $productsWithUnsavedChanges = [];

    /**
     * Initialize selection arrays from existing compatibility data
     */
    protected function initializeSelections(int $productId, ?int $shopId = null): void
    {
        $query = VehicleCompatibility::byProduct($productId);

        if ($shopId !== null) {
            $query->byShop($shopId);
        }

        $compatibilities = $query->with('compatibilityAttribute')->get();

        $this->selectedOriginal = $compatibilities
            ->filter(fn($c) => $c->compatibilityAttribute?->code === 'original')
            ->pluck('vehicle_model_id')
            ->unique()
            ->values()
            ->toArray();

        $this->selectedZamiennik = $compatibilities
            ->filter(fn($c) => $c->compatibilityAttribute?->code === 'replacement')
            ->pluck('vehicle_model_id')
            ->unique()
            ->values()
            ->toArray();

        $this->pendingChanges = [];
    }

    /**
     * Toggle vehicle selection based on current mode
     */
    public function toggleVehicle(int $vehicleId): void
    {
        if ($this->selectionMode === 'original') {
            $this->toggleOriginal($vehicleId);
        } else {
            $this->toggleZamiennik($vehicleId);
        }

        // Track change for pending save
        $this->trackChange($vehicleId, $this->selectionMode);
    }

    /**
     * Toggle vehicle as Original
     */
    public function toggleOriginal(int $vehicleId): void
    {
        if (in_array($vehicleId, $this->selectedOriginal)) {
            $this->selectedOriginal = array_values(
                array_diff($this->selectedOriginal, [$vehicleId])
            );
        } else {
            $this->selectedOriginal[] = $vehicleId;
        }
    }

    /**
     * Toggle vehicle as Zamiennik
     */
    public function toggleZamiennik(int $vehicleId): void
    {
        if (in_array($vehicleId, $this->selectedZamiennik)) {
            $this->selectedZamiennik = array_values(
                array_diff($this->selectedZamiennik, [$vehicleId])
            );
        } else {
            $this->selectedZamiennik[] = $vehicleId;
        }
    }

    /**
     * Add vehicle as Original (explicit)
     */
    public function addAsOriginal(int $vehicleId): void
    {
        if (!in_array($vehicleId, $this->selectedOriginal)) {
            $this->selectedOriginal[] = $vehicleId;
            $this->trackChange($vehicleId, 'original');
        }
    }

    /**
     * Add vehicle as Zamiennik (explicit)
     */
    public function addAsZamiennik(int $vehicleId): void
    {
        if (!in_array($vehicleId, $this->selectedZamiennik)) {
            $this->selectedZamiennik[] = $vehicleId;
            $this->trackChange($vehicleId, 'zamiennik');
        }
    }

    /**
     * Remove vehicle from both types
     */
    public function removeVehicle(int $vehicleId): void
    {
        $this->selectedOriginal = array_values(
            array_diff($this->selectedOriginal, [$vehicleId])
        );
        $this->selectedZamiennik = array_values(
            array_diff($this->selectedZamiennik, [$vehicleId])
        );
        $this->trackChange($vehicleId, 'remove');
    }

    /**
     * Switch selection mode
     */
    public function setSelectionMode(string $mode): void
    {
        if (in_array($mode, ['original', 'zamiennik'])) {
            $this->selectionMode = $mode;
        }
    }

    /**
     * Select all vehicles in a brand as current mode
     *
     * FIXED: Pobiera pojazdy wewnętrznie przez query (Livewire nie może serializować Collection w wire:click)
     */
    public function selectAllInBrand(string $brand): void
    {
        $vehicleIds = VehicleModel::where('brand', $brand)
            ->pluck('id')
            ->toArray();

        foreach ($vehicleIds as $vehicleId) {
            if ($this->selectionMode === 'original') {
                $this->addAsOriginal($vehicleId);
            } else {
                $this->addAsZamiennik($vehicleId);
            }
        }
    }

    /**
     * Deselect all vehicles in a brand
     *
     * FIXED: Pobiera pojazdy wewnętrznie przez query (Livewire nie może serializować Collection w wire:click)
     */
    public function deselectAllInBrand(string $brand): void
    {
        $vehicleIds = VehicleModel::where('brand', $brand)
            ->pluck('id')
            ->toArray();

        foreach ($vehicleIds as $vehicleId) {
            $this->removeVehicle($vehicleId);
        }
    }

    /**
     * Track a change for pending save
     */
    protected function trackChange(int $vehicleId, string $type): void
    {
        $this->pendingChanges[$vehicleId] = [
            'vehicle_id' => $vehicleId,
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
        ];

        // Mark current product as having unsaved changes
        if ($this->editingProductId && !in_array($this->editingProductId, $this->productsWithUnsavedChanges)) {
            $this->productsWithUnsavedChanges[] = $this->editingProductId;
        }
    }

    /**
     * Check if vehicle is selected as Original
     */
    public function isOriginal(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->selectedOriginal);
    }

    /**
     * Check if vehicle is selected as Zamiennik
     */
    public function isZamiennik(int $vehicleId): bool
    {
        return in_array($vehicleId, $this->selectedZamiennik);
    }

    /**
     * Check if vehicle is selected as both
     */
    public function isBoth(int $vehicleId): bool
    {
        return $this->isOriginal($vehicleId) && $this->isZamiennik($vehicleId);
    }

    /**
     * Get vehicle selection state class for CSS
     */
    public function getVehicleStateClass(int $vehicleId): string
    {
        if ($this->isBoth($vehicleId)) {
            return 'vehicle-tile--selected-both';
        }
        if ($this->isOriginal($vehicleId)) {
            return 'vehicle-tile--selected-original';
        }
        if ($this->isZamiennik($vehicleId)) {
            return 'vehicle-tile--selected-zamiennik';
        }
        return '';
    }

    /**
     * Get count of selected Original vehicles
     */
    public function getOriginalCount(): int
    {
        return count($this->selectedOriginal);
    }

    /**
     * Get count of selected Zamiennik vehicles
     */
    public function getZamiennikCount(): int
    {
        return count($this->selectedZamiennik);
    }

    /**
     * Get total unique model count (Original + Zamiennik)
     */
    public function getTotalModelCount(): int
    {
        return count(array_unique(
            array_merge($this->selectedOriginal, $this->selectedZamiennik)
        ));
    }

    /**
     * Check if there are pending changes
     */
    public function hasPendingChanges(): bool
    {
        return !empty($this->pendingChanges);
    }

    /**
     * Get pending changes count
     */
    public function getPendingChangesCount(): int
    {
        return count($this->pendingChanges);
    }

    /**
     * Check if a specific product has unsaved changes
     */
    public function productHasUnsavedChanges(int $productId): bool
    {
        return in_array($productId, $this->productsWithUnsavedChanges);
    }

    /**
     * Clear all selections
     */
    public function clearAllSelections(): void
    {
        $this->selectedOriginal = [];
        $this->selectedZamiennik = [];
        $this->pendingChanges = [];
    }
}
