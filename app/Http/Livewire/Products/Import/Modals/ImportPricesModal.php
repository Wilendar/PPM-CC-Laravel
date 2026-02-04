<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\PriceGroup;
use Illuminate\Support\Facades\Log;

/**
 * ImportPricesModal - FAZA 9.4
 *
 * Modal for editing prices per price group on a PendingProduct.
 * Modeled after variant-prices-modal pattern with lock/unlock mechanism.
 *
 * Features:
 * - Table of price groups: Detaliczna, Dealer Standard/Premium, etc.
 * - Columns: Group name | Net price (PLN) | Gross price (PLN)
 * - Lock/unlock mechanism (default: locked)
 * - Auto-calculation: net * (1 + VAT%) = gross (and reverse)
 * - Save to price_data JSON in PendingProduct
 * - Sync: base_price = "Detaliczna" group price
 *
 * @package App\Http\Livewire\Products\Import\Modals
 */
class ImportPricesModal extends Component
{
    /**
     * Whether the modal is visible
     */
    public bool $showPricesModal = false;

    /**
     * Current PendingProduct ID being edited
     */
    public ?int $editingProductId = null;

    /**
     * Product SKU for display
     */
    public string $editingProductSku = '';

    /**
     * Whether prices are unlocked for editing
     */
    public bool $pricesUnlocked = false;

    /**
     * Price values per group: [groupId => ['net' => float, 'gross' => float]]
     */
    public array $modalPrices = [];

    /**
     * Tax rate for calculations
     */
    public float $taxRate = 23.00;

    /**
     * Open the prices modal for a given PendingProduct
     */
    #[On('openImportPricesModal')]
    public function openPricesModal(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $this->editingProductId = $productId;
        $this->editingProductSku = $product->sku ?? 'N/A';
        $this->taxRate = (float) ($product->tax_rate ?? 23.00);
        $this->pricesUnlocked = false;

        // Load existing price_data or initialize empty
        $this->loadPriceData($product);

        $this->showPricesModal = true;

        Log::debug('ImportPricesModal: opened', [
            'product_id' => $productId,
            'sku' => $this->editingProductSku,
        ]);
    }

    /**
     * Close the modal
     */
    public function closePricesModal(): void
    {
        $this->showPricesModal = false;
        $this->editingProductId = null;
        $this->editingProductSku = '';
        $this->modalPrices = [];
        $this->pricesUnlocked = false;
    }

    /**
     * Toggle price lock/unlock
     */
    public function togglePricesLock(): void
    {
        $this->pricesUnlocked = !$this->pricesUnlocked;
    }

    /**
     * Save prices to PendingProduct.price_data
     *
     * Also syncs base_price from the default price group (Detaliczna).
     */
    public function savePrices(): void
    {
        if (!$this->editingProductId || !$this->pricesUnlocked) {
            return;
        }

        $product = PendingProduct::find($this->editingProductId);
        if (!$product) {
            $this->closePricesModal();
            return;
        }

        try {
            // Build price_data structure
            $priceData = ['groups' => []];
            foreach ($this->modalPrices as $groupId => $prices) {
                $net = !empty($prices['net']) ? (float) $prices['net'] : null;
                $gross = !empty($prices['gross']) ? (float) $prices['gross'] : null;

                if ($net !== null || $gross !== null) {
                    $priceData['groups'][$groupId] = [
                        'net' => $net,
                        'gross' => $gross,
                    ];
                }
            }

            $product->price_data = $priceData;

            // Sync base_price from default price group
            $defaultGroup = PriceGroup::where('is_default', true)->first();
            if ($defaultGroup && isset($priceData['groups'][$defaultGroup->id])) {
                $defaultNet = $priceData['groups'][$defaultGroup->id]['net'] ?? null;
                if ($defaultNet !== null) {
                    $product->base_price = $defaultNet;
                }
            }

            $product->save();

            Log::info('ImportPricesModal: prices saved', [
                'product_id' => $this->editingProductId,
                'groups_count' => count($priceData['groups']),
                'base_price' => $product->base_price,
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Ceny zapisane dla: ' . $this->editingProductSku,
            ]);

            $this->closePricesModal();
            $this->dispatch('refreshPendingProducts');

        } catch (\Exception $e) {
            Log::error('ImportPricesModal: save failed', [
                'product_id' => $this->editingProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu cen: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get price groups for the table
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPriceGroupsProperty()
    {
        return PriceGroup::active()->ordered()->get();
    }

    /**
     * Load existing price data from PendingProduct
     */
    protected function loadPriceData(PendingProduct $product): void
    {
        $this->modalPrices = [];
        $priceData = $product->price_data ?? [];
        $groups = $priceData['groups'] ?? [];

        // Initialize all groups with existing or empty values
        $allGroups = PriceGroup::active()->ordered()->get();
        foreach ($allGroups as $group) {
            $existing = $groups[$group->id] ?? null;
            $this->modalPrices[$group->id] = [
                'net' => $existing['net'] ?? '',
                'gross' => $existing['gross'] ?? '',
            ];
        }

        // If base_price is set but no price_data, set default group
        if ($product->base_price && empty($groups)) {
            $defaultGroup = $allGroups->where('is_default', true)->first();
            if ($defaultGroup) {
                $netPrice = $product->base_price;
                $grossPrice = round($netPrice * (1 + $this->taxRate / 100), 2);
                $this->modalPrices[$defaultGroup->id] = [
                    'net' => number_format($netPrice, 2, '.', ''),
                    'gross' => number_format($grossPrice, 2, '.', ''),
                ];
            }
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.import-prices-modal');
    }
}
