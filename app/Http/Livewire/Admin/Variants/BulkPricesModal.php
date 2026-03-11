<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\PriceGroup;
use App\Models\ProductVariant;
use App\Models\VariantPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bulk Prices Modal Component
 *
 * Umożliwia masową aktualizację cen wariantów dla wybranych grup cenowych
 * z preview przed zastosowaniem zmian
 *
 * @property array $selectedVariantIds IDs wariantów do aktualizacji
 * @property array $selectedPriceGroups IDs wybranych grup cenowych
 * @property string $changeType Typ zmiany: set|increase|decrease|percentage
 * @property float $amount Wartość zmiany
 * @property bool $showModal Widoczność modala
 * @property bool $showPreview Widoczność preview
 */
class BulkPricesModal extends Component
{
    public array $selectedVariantIds = [];
    public array $selectedPriceGroups = [];
    public string $changeType = 'set';
    public float $amount = 0.0;
    public bool $showModal = false;
    public bool $showPreview = false;

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        $rules = [
            'selectedPriceGroups' => 'required|array|min:1',
            'selectedPriceGroups.*' => 'exists:price_groups,id',
            'changeType' => 'required|in:set,increase,decrease,percentage',
        ];

        // Walidacja kwoty w zależności od typu zmiany
        if ($this->changeType === 'set') {
            $rules['amount'] = 'required|numeric|min:0';
        } else {
            $rules['amount'] = 'required|numeric|min:0.01';
        }

        return $rules;
    }

    /**
     * Custom validation messages
     */
    protected function messages(): array
    {
        return [
            'selectedPriceGroups.required' => 'Wybierz przynajmniej jedną grupę cenową',
            'selectedPriceGroups.min' => 'Wybierz przynajmniej jedną grupę cenową',
            'amount.required' => 'Podaj wartość zmiany',
            'amount.min' => $this->changeType === 'set'
                ? 'Cena nie może być ujemna'
                : 'Wartość zmiany musi być większa od 0',
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
    #[On('open-bulk-prices-modal')]
    public function openModal($variantIds): void
    {
        $this->selectedVariantIds = (array) $variantIds;
        $this->selectedPriceGroups = [];
        $this->changeType = 'set';
        $this->amount = 0.0;
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
     * Get all price groups
     */
    #[Computed]
    public function priceGroups(): Collection
    {
        return PriceGroup::active()->ordered()->get();
    }

    /**
     * Get selected variants
     */
    #[Computed]
    public function variants(): Collection
    {
        return ProductVariant::whereIn('id', $this->selectedVariantIds)
            ->with(['product', 'prices.priceGroup'])
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

        $data = [];
        $totalUpdates = 0;

        foreach ($this->variants as $variant) {
            foreach ($this->selectedPriceGroups as $priceGroupId) {
                $priceGroup = $this->priceGroups->firstWhere('id', $priceGroupId);
                if (!$priceGroup) {
                    continue;
                }

                // Pobierz aktualną cenę
                $variantPrice = $variant->prices->firstWhere('price_group_id', $priceGroupId);
                $currentPrice = $variantPrice ? (float) $variantPrice->price : 0.0;

                // Oblicz nową cenę
                $newPrice = $this->calculateNewPrice($currentPrice);

                // Oblicz różnicę
                $difference = $newPrice - $currentPrice;

                $data[] = [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'variant_name' => $variant->name,
                    'price_group_id' => $priceGroupId,
                    'price_group_name' => $priceGroup->name,
                    'current_price' => $currentPrice,
                    'new_price' => $newPrice,
                    'difference' => $difference,
                    'difference_color' => $difference > 0 ? 'green' : ($difference < 0 ? 'red' : 'gray'),
                ];

                $totalUpdates++;
            }
        }

        return [
            'items' => $data,
            'total_updates' => $totalUpdates,
            'total_variants' => count($this->selectedVariantIds),
            'total_groups' => count($this->selectedPriceGroups),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate new price based on change type
     */
    private function calculateNewPrice(float $currentPrice): float
    {
        return match($this->changeType) {
            'set' => $this->amount,
            'increase' => $currentPrice + $this->amount,
            'decrease' => max(0, $currentPrice - $this->amount),
            'percentage' => $currentPrice * (1 + ($this->amount / 100)),
            default => $currentPrice,
        };
    }

    /**
     * Toggle select all price groups
     */
    public function toggleSelectAllGroups(): void
    {
        if (count($this->selectedPriceGroups) === $this->priceGroups->count()) {
            $this->selectedPriceGroups = [];
        } else {
            $this->selectedPriceGroups = $this->priceGroups->pluck('id')->toArray();
        }
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
     * Apply bulk price changes with transaction safety
     */
    public function apply(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $updatedCount = 0;

                foreach ($this->selectedVariantIds as $variantId) {
                    foreach ($this->selectedPriceGroups as $priceGroupId) {
                        // Pobierz lub utwórz rekord ceny
                        $variantPrice = VariantPrice::firstOrNew([
                            'variant_id' => $variantId,
                            'price_group_id' => $priceGroupId,
                        ]);

                        $currentPrice = $variantPrice->price ?? 0.0;
                        $newPrice = $this->calculateNewPrice($currentPrice);

                        // Zapisz nową cenę
                        $variantPrice->price = $newPrice;
                        $variantPrice->save();

                        $updatedCount++;
                    }
                }

                session()->flash('message', sprintf(
                    'Zaktualizowano %d cen w %d wariantach',
                    $updatedCount,
                    count($this->selectedVariantIds)
                ));
            });

            // Dispatch event to refresh variants list
            $this->dispatch('refresh-variants');

            $this->close();

        } catch (\Exception $e) {
            $this->addError('apply', 'Błąd podczas aktualizacji cen: ' . $e->getMessage());
        }
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->showModal = false;
        $this->showPreview = false;
        $this->reset(['selectedVariantIds', 'selectedPriceGroups', 'changeType', 'amount']);
        $this->resetValidation();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.variants.bulk-prices-modal');
    }
}
