<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\ProductVariant;
use App\Models\VariantStock;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bulk Stock Modal Component
 *
 * Umożliwia masową aktualizację stanów magazynowych wariantów
 * dla wybranego magazynu z preview przed zastosowaniem zmian
 *
 * @property array $selectedVariantIds IDs wariantów do aktualizacji
 * @property int|null $warehouseId ID wybranego magazynu
 * @property string $changeType Typ zmiany: set|adjust|percentage
 * @property int $amount Wartość zmiany
 * @property bool $showModal Widoczność modala
 * @property bool $showPreview Widoczność preview
 */
class BulkStockModal extends Component
{
    public array $selectedVariantIds = [];
    public ?int $warehouseId = 1; // Default MPPTRADE
    public string $changeType = 'set';
    public int $amount = 0;
    public bool $showModal = false;
    public bool $showPreview = false;

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        $rules = [
            'warehouseId' => 'required|exists:warehouses,id',
            'changeType' => 'required|in:set,adjust,percentage',
        ];

        // Walidacja kwoty w zależności od typu zmiany
        if ($this->changeType === 'set') {
            $rules['amount'] = 'required|integer|min:0';
        } elseif ($this->changeType === 'adjust') {
            $rules['amount'] = 'required|integer';
        } else { // percentage
            $rules['amount'] = 'required|integer|min:-100';
        }

        return $rules;
    }

    /**
     * Custom validation messages
     */
    protected function messages(): array
    {
        return [
            'warehouseId.required' => 'Wybierz magazyn',
            'warehouseId.exists' => 'Wybrany magazyn nie istnieje',
            'amount.required' => 'Podaj wartość zmiany',
            'amount.min' => $this->changeType === 'percentage'
                ? 'Wartość procentowa nie może być mniejsza niż -100%'
                : 'Stan nie może być ujemny',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE HOOKS
    |--------------------------------------------------------------------------
    */

    /**
     * Component mount (no parameters - DI safe)
     */
    public function mount(): void
    {
        // Initialize component without parameters
        // Modal opens via event dispatch
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Open modal with selected variant IDs
     */
    #[On('open-bulk-stock-modal')]
    public function openModal($variantIds): void
    {
        $this->selectedVariantIds = (array) $variantIds;
        $this->warehouseId = Warehouse::getDefault()?->id ?? 1;
        $this->changeType = 'set';
        $this->amount = 0;
        $this->showModal = true;
        $this->showPreview = false;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get all warehouses
     */
    #[Computed]
    public function warehouses(): Collection
    {
        return Warehouse::active()->ordered()->get();
    }

    /**
     * Get selected variants
     */
    #[Computed]
    public function variants(): Collection
    {
        return ProductVariant::whereIn('id', $this->selectedVariantIds)
            ->with(['product', 'stock'])
            ->get();
    }

    /**
     * Calculate preview data
     */
    #[Computed]
    public function previewData(): array
    {
        if (!$this->showPreview) {
            return [];
        }

        $warehouse = $this->warehouses->firstWhere('id', $this->warehouseId);
        if (!$warehouse) {
            return [];
        }

        $data = [];

        foreach ($this->variants as $variant) {
            // Pobierz aktualny stan
            $variantStock = $variant->stock->firstWhere('warehouse_id', $this->warehouseId);
            $currentStock = $variantStock ? $variantStock->quantity : 0;

            // Oblicz nowy stan
            $newStock = $this->calculateNewStock($currentStock);

            // Oblicz różnicę
            $difference = $newStock - $currentStock;

            $data[] = [
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku,
                'variant_name' => $variant->name,
                'current_stock' => $currentStock,
                'new_stock' => $newStock,
                'difference' => $difference,
                'difference_color' => $difference > 0 ? 'green' : ($difference < 0 ? 'red' : 'gray'),
            ];
        }

        return [
            'items' => $data,
            'warehouse_name' => $warehouse->name,
            'total_variants' => count($this->selectedVariantIds),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate new stock based on change type
     */
    private function calculateNewStock(int $currentStock): int
    {
        $newStock = match($this->changeType) {
            'set' => $this->amount,
            'adjust' => $currentStock + $this->amount,
            'percentage' => (int) round($currentStock * (1 + ($this->amount / 100))),
            default => $currentStock,
        };

        // Stock nie może być ujemny
        return max(0, $newStock);
    }

    /**
     * Show preview before applying changes
     */
    public function calculatePreview(): void
    {
        $this->validate();
        $this->showPreview = true;
    }

    /**
     * Apply bulk stock changes with transaction safety
     */
    public function apply(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $updatedCount = 0;

                foreach ($this->selectedVariantIds as $variantId) {
                    // Pobierz lub utwórz rekord stanu
                    $variantStock = VariantStock::firstOrNew([
                        'variant_id' => $variantId,
                        'warehouse_id' => $this->warehouseId,
                    ]);

                    $currentStock = $variantStock->quantity ?? 0;
                    $newStock = $this->calculateNewStock($currentStock);

                    // Zapisz nowy stan
                    $variantStock->quantity = $newStock;
                    $variantStock->save();

                    $updatedCount++;
                }

                $warehouse = $this->warehouses->firstWhere('id', $this->warehouseId);

                session()->flash('message', sprintf(
                    'Zaktualizowano stan %d wariantów w magazynie %s',
                    $updatedCount,
                    $warehouse->name ?? ''
                ));
            });

            // Dispatch event to refresh variants list
            $this->dispatch('refresh-variants');

            $this->close();

        } catch (\Exception $e) {
            $this->addError('apply', 'Błąd podczas aktualizacji stanów: ' . $e->getMessage());
        }
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->showModal = false;
        $this->showPreview = false;
        $this->reset(['selectedVariantIds', 'warehouseId', 'changeType', 'amount']);
        $this->resetValidation();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.variants.bulk-stock-modal');
    }
}
