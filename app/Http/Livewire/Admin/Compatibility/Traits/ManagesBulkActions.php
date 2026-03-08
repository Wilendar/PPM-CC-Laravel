<?php

namespace App\Http\Livewire\Admin\Compatibility\Traits;

use App\Models\CompatibilityAttribute;
use App\Models\Product;
use App\Models\VehicleCompatibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ManagesBulkActions Trait
 *
 * Bulk selection and bulk actions for parts in the Compatibility Management panel.
 *
 * Handles:
 * - Part selection (individual + select all on page)
 * - Bulk vehicle assignment (original/replacement)
 * - Copy compatibilities from source product
 * - Export selected parts to CSV
 *
 * Assumes the using class has:
 * - $shopContext (public ?int)
 * - getDefaultShopId(): int
 * - $parts computed property (LengthAwarePaginator)
 */
trait ManagesBulkActions
{
    /** Selected part IDs for bulk operations */
    public array $selectedPartIds = [];

    /** Whether "select all on page" is active */
    public bool $selectAllOnPage = false;

    /**
     * Toggle individual part selection
     */
    public function togglePartSelection(int $partId): void
    {
        if (in_array($partId, $this->selectedPartIds)) {
            $this->selectedPartIds = array_values(array_diff($this->selectedPartIds, [$partId]));
        } else {
            $this->selectedPartIds[] = $partId;
        }
    }

    /**
     * Toggle select all parts on current page
     */
    public function toggleSelectAll(): void
    {
        $this->selectAllOnPage = !$this->selectAllOnPage;

        $pageIds = $this->parts->pluck('id')->toArray();

        if ($this->selectAllOnPage) {
            // Add all current page IDs (avoid duplicates)
            foreach ($pageIds as $id) {
                if (!in_array($id, $this->selectedPartIds)) {
                    $this->selectedPartIds[] = $id;
                }
            }
        } else {
            // Remove all current page IDs from selection
            $this->selectedPartIds = array_values(
                array_diff($this->selectedPartIds, $pageIds)
            );
        }
    }

    /**
     * Open bulk edit modal (dispatch event to frontend)
     */
    public function openBulkEdit(): void
    {
        if (count($this->selectedPartIds) === 0) {
            $this->dispatch('flash-message', message: 'Zaznacz przynajmniej 1 czesc', type: 'warning');
            return;
        }

        $this->dispatch('open-bulk-modal', [
            'direction' => 'part_to_vehicle',
            'selectedIds' => $this->selectedPartIds,
        ]);
    }

    /**
     * Bulk assign a vehicle to all selected parts
     *
     * @param int $vehicleId Vehicle product ID (type='pojazd')
     * @param string $type 'original' or 'replacement'
     */
    public function bulkAssignVehicle(int $vehicleId, string $type = 'original'): void
    {
        if (empty($this->selectedPartIds)) {
            $this->dispatch('flash-message', message: 'Brak zaznaczonych czesci', type: 'warning');
            return;
        }

        $attributeId = CompatibilityAttribute::where('code', $type)->value('id');
        if (!$attributeId) {
            $this->dispatch('flash-message', message: 'Nieprawidlowy typ dopasowania: ' . $type, type: 'error');
            return;
        }

        $shopId = $this->shopContext ?? $this->getDefaultShopId();
        $created = 0;

        try {
            DB::beginTransaction();

            foreach ($this->selectedPartIds as $partId) {
                $exists = VehicleCompatibility::where('product_id', $partId)
                    ->where('vehicle_model_id', $vehicleId)
                    ->where('shop_id', $shopId)
                    ->where('compatibility_attribute_id', $attributeId)
                    ->exists();

                if (!$exists) {
                    VehicleCompatibility::create([
                        'product_id' => $partId,
                        'vehicle_model_id' => $vehicleId,
                        'shop_id' => $shopId,
                        'compatibility_attribute_id' => $attributeId,
                        'compatibility_source_id' => 1,
                        'verified' => false,
                        'is_suggested' => false,
                    ]);
                    $created++;
                }
            }

            DB::commit();

            $typeLabel = $type === 'original' ? 'oryginalnych' : 'zamiennikow';
            $this->dispatch('flash-message',
                message: "Przypisano pojazd do {$created} czesci ({$typeLabel})",
                type: 'success'
            );
            $this->clearSelection();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ManagesBulkActions: bulkAssignVehicle failed', [
                'vehicle_id' => $vehicleId,
                'type' => $type,
                'selected_count' => count($this->selectedPartIds),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('flash-message', message: 'Blad: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Copy all compatibilities from a source product to all selected parts
     *
     * @param int $sourceProductId Product ID to copy compatibilities from
     */
    public function copyCompatibilitiesFrom(int $sourceProductId): void
    {
        if (empty($this->selectedPartIds)) {
            $this->dispatch('flash-message', message: 'Brak zaznaczonych czesci', type: 'warning');
            return;
        }

        $sourceCompats = VehicleCompatibility::where('product_id', $sourceProductId)->get();

        if ($sourceCompats->isEmpty()) {
            $this->dispatch('flash-message', message: 'Produkt zrodlowy nie ma dopasowan', type: 'warning');
            return;
        }

        $shopId = $this->shopContext ?? $this->getDefaultShopId();
        $totalCreated = 0;

        try {
            DB::beginTransaction();

            foreach ($this->selectedPartIds as $partId) {
                if ($partId === $sourceProductId) {
                    continue;
                }

                foreach ($sourceCompats as $compat) {
                    $exists = VehicleCompatibility::where('product_id', $partId)
                        ->where('vehicle_model_id', $compat->vehicle_model_id)
                        ->where('shop_id', $shopId)
                        ->where('compatibility_attribute_id', $compat->compatibility_attribute_id)
                        ->exists();

                    if (!$exists) {
                        VehicleCompatibility::create([
                            'product_id' => $partId,
                            'vehicle_model_id' => $compat->vehicle_model_id,
                            'shop_id' => $shopId,
                            'compatibility_attribute_id' => $compat->compatibility_attribute_id,
                            'compatibility_source_id' => $compat->compatibility_source_id,
                            'verified' => false,
                            'is_suggested' => false,
                        ]);
                        $totalCreated++;
                    }
                }
            }

            DB::commit();

            $targetCount = count($this->selectedPartIds);
            $this->dispatch('flash-message',
                message: "Skopiowano {$totalCreated} dopasowan do {$targetCount} czesci",
                type: 'success'
            );
            $this->clearSelection();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ManagesBulkActions: copyCompatibilitiesFrom failed', [
                'source_product_id' => $sourceProductId,
                'selected_count' => count($this->selectedPartIds),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('flash-message', message: 'Blad kopiowania: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Export selected parts with compatibility data as CSV (browser download via event)
     */
    public function exportSelected(): void
    {
        if (empty($this->selectedPartIds)) {
            $this->dispatch('flash-message', message: 'Brak zaznaczonych czesci', type: 'warning');
            return;
        }

        $products = Product::whereIn('id', $this->selectedPartIds)
            ->with(['vehicleCompatibility.vehicleProduct', 'vehicleCompatibility.compatibilityAttribute'])
            ->get();

        $rows = [];
        foreach ($products as $product) {
            if ($product->vehicleCompatibility->isEmpty()) {
                $rows[] = [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'vehicle' => '',
                    'type' => '',
                ];
                continue;
            }

            foreach ($product->vehicleCompatibility as $compat) {
                $vehicleName = $compat->vehicleProduct
                    ? trim(($compat->vehicleProduct->manufacturer ?? '') . ' ' . ($compat->vehicleProduct->name ?? ''))
                    : 'N/A';

                $typeLabel = match ($compat->compatibilityAttribute?->code) {
                    'original' => 'Oryginal',
                    'replacement' => 'Zamiennik',
                    default => $compat->compatibilityAttribute?->name ?? 'Standard',
                };

                $rows[] = [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'vehicle' => $vehicleName,
                    'type' => $typeLabel,
                ];
            }
        }

        // Build CSV string on backend (JS listener expects 'content' key)
        $csvLines = [];
        $csvLines[] = implode(';', ['SKU', 'Nazwa', 'Pojazd', 'Typ']);
        foreach ($rows as $row) {
            $csvLines[] = implode(';', [
                '"' . str_replace('"', '""', $row['sku']) . '"',
                '"' . str_replace('"', '""', $row['name']) . '"',
                '"' . str_replace('"', '""', $row['vehicle']) . '"',
                '"' . str_replace('"', '""', $row['type']) . '"',
            ]);
        }
        $csvContent = implode("\n", $csvLines);

        $this->dispatch('download-csv', [
            'filename' => 'compatibility_export_' . now()->format('Y-m-d_His') . '.csv',
            'content' => $csvContent,
        ]);
    }

    /**
     * Get count of selected parts
     */
    public function getSelectedCount(): int
    {
        return count($this->selectedPartIds);
    }

    /**
     * Clear all part selections
     */
    public function clearSelection(): void
    {
        $this->selectedPartIds = [];
        $this->selectAllOnPage = false;
    }

    /**
     * Check if a specific part is selected
     */
    public function isPartSelected(int $partId): bool
    {
        return in_array($partId, $this->selectedPartIds);
    }
}
