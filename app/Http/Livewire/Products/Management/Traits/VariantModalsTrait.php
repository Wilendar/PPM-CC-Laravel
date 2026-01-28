<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VariantModalsTrait - Modal Windows for Variant Prices & Stock
 *
 * Handles: Opening/closing modals, loading/saving variant data
 *
 * DEPENDENCIES:
 * - VariantPriceTrait (updateVariantPrice method)
 * - VariantStockTrait (updateVariantStock method)
 * - Product model ($this->product)
 * - priceGroups property
 * - warehouses property
 * - tax_rate property
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since ETAP_14 - Variant Modals
 */
trait VariantModalsTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES - PRICES MODAL
    |--------------------------------------------------------------------------
    */

    /** @var bool Show variant prices modal */
    public bool $showVariantPricesModal = false;

    /** @var int|null Selected variant ID for prices modal */
    public ?int $selectedVariantIdForPrices = null;

    /** @var array Variant data for prices modal header */
    public array $selectedVariantForPricesData = [];

    /** @var array Prices data for modal [groupId => ['net' => X, 'gross' => Y]] */
    public array $variantModalPrices = [];

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES - STOCK MODAL
    |--------------------------------------------------------------------------
    */

    /** @var bool Show variant stock modal */
    public bool $showVariantStockModal = false;

    /** @var int|null Selected variant ID for stock modal */
    public ?int $selectedVariantIdForStock = null;

    /** @var array Variant data for stock modal header */
    public array $selectedVariantForStockData = [];

    /** @var array Stock data for modal [warehouseId => ['quantity', 'reserved', 'minimum', 'location']] */
    public array $variantModalStock = [];

    /*
    |--------------------------------------------------------------------------
    | PRICES MODAL METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Open variant prices modal
     */
    public function openVariantPricesModal(int $variantId): void
    {
        try {
            $variant = ProductVariant::with(['prices.priceGroup'])
                ->where('product_id', $this->product->id)
                ->findOrFail($variantId);

            $this->selectedVariantIdForPrices = $variantId;
            $this->selectedVariantForPricesData = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name ?? $variant->sku,
            ];

            $this->loadVariantPricesForModal($variant);
            $this->showVariantPricesModal = true;

            Log::debug('Opened variant prices modal', [
                'variant_id' => $variantId,
                'sku' => $variant->sku,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to open variant prices modal', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Nie udalo sie otworzyc modala cen: ' . $e->getMessage());
        }
    }

    /**
     * Close variant prices modal
     */
    public function closeVariantPricesModal(): void
    {
        $this->showVariantPricesModal = false;
        $this->selectedVariantIdForPrices = null;
        $this->selectedVariantForPricesData = [];
        $this->variantModalPrices = [];
    }

    /**
     * Save variant prices from modal
     */
    public function saveVariantModalPrices(): void
    {
        if (!$this->selectedVariantIdForPrices) {
            session()->flash('error', 'Brak wybranego wariantu.');
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($this->variantModalPrices as $groupId => $priceData) {
                $netPrice = (float) ($priceData['net'] ?? 0);

                if ($netPrice > 0) {
                    $this->updateVariantPrice(
                        $this->selectedVariantIdForPrices,
                        (int) $groupId,
                        ['price' => $netPrice]
                    );
                }
            }

            DB::commit();

            // Reload variants to refresh list
            $this->product->load('variants.prices');

            $this->closeVariantPricesModal();

            Log::info('Variant prices saved from modal', [
                'variant_id' => $this->selectedVariantIdForPrices,
                'prices_count' => count($this->variantModalPrices),
            ]);

            session()->flash('message', 'Ceny wariantu zostaly zapisane.');
            $this->dispatch('variant-prices-saved');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save variant prices from modal', [
                'variant_id' => $this->selectedVariantIdForPrices,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas zapisu cen: ' . $e->getMessage());
        }
    }

    /**
     * Load variant prices into modal data structure
     */
    protected function loadVariantPricesForModal(ProductVariant $variant): void
    {
        $this->variantModalPrices = [];
        $taxRate = $this->tax_rate ?? 23;

        foreach ($this->priceGroups as $groupId => $group) {
            $price = $variant->prices->firstWhere('price_group_id', $groupId);
            $netPrice = (float) ($price?->price ?? 0);
            $grossPrice = $netPrice > 0 ? $netPrice * (1 + $taxRate / 100) : 0;

            $this->variantModalPrices[$groupId] = [
                'net' => number_format($netPrice, 2, '.', ''),
                'gross' => number_format($grossPrice, 2, '.', ''),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK MODAL METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Open variant stock modal
     */
    public function openVariantStockModal(int $variantId): void
    {
        try {
            $variant = ProductVariant::with(['stock.warehouse'])
                ->where('product_id', $this->product->id)
                ->findOrFail($variantId);

            $this->selectedVariantIdForStock = $variantId;
            $this->selectedVariantForStockData = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name ?? $variant->sku,
            ];

            $this->loadVariantStockForModal($variant);
            $this->showVariantStockModal = true;

            Log::debug('Opened variant stock modal', [
                'variant_id' => $variantId,
                'sku' => $variant->sku,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to open variant stock modal', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Nie udalo sie otworzyc modala stanow: ' . $e->getMessage());
        }
    }

    /**
     * Close variant stock modal
     */
    public function closeVariantStockModal(): void
    {
        $this->showVariantStockModal = false;
        $this->selectedVariantIdForStock = null;
        $this->selectedVariantForStockData = [];
        $this->variantModalStock = [];
    }

    /**
     * Save variant stock from modal
     */
    public function saveVariantModalStock(): void
    {
        if (!$this->selectedVariantIdForStock) {
            session()->flash('error', 'Brak wybranego wariantu.');
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($this->variantModalStock as $warehouseId => $stockData) {
                $this->updateVariantStock(
                    $this->selectedVariantIdForStock,
                    (int) $warehouseId,
                    [
                        'quantity' => (int) ($stockData['quantity'] ?? 0),
                        'reserved' => (int) ($stockData['reserved'] ?? 0),
                    ]
                );

                // Update location if provided
                $variant = ProductVariant::find($this->selectedVariantIdForStock);
                if ($variant) {
                    $stock = $variant->stock()->where('warehouse_id', $warehouseId)->first();
                    if ($stock && isset($stockData['location'])) {
                        $stock->update(['location' => $stockData['location']]);
                    }
                }
            }

            DB::commit();

            // Reload variants to refresh list
            $this->product->load('variants.stock');

            $this->closeVariantStockModal();

            Log::info('Variant stock saved from modal', [
                'variant_id' => $this->selectedVariantIdForStock,
                'warehouses_count' => count($this->variantModalStock),
            ]);

            session()->flash('message', 'Stany magazynowe wariantu zostaly zapisane.');
            $this->dispatch('variant-stock-saved');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save variant stock from modal', [
                'variant_id' => $this->selectedVariantIdForStock,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas zapisu stanow: ' . $e->getMessage());
        }
    }

    /**
     * Load variant stock into modal data structure
     */
    protected function loadVariantStockForModal(ProductVariant $variant): void
    {
        $this->variantModalStock = [];
        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        foreach ($warehouses as $warehouse) {
            $stock = $variant->stock->firstWhere('warehouse_id', $warehouse->id);

            $this->variantModalStock[$warehouse->id] = [
                'warehouse_name' => $warehouse->name,
                'warehouse_code' => $warehouse->code,
                'quantity' => (int) ($stock?->quantity ?? 0),
                'reserved' => (int) ($stock?->reserved_quantity ?? 0),
                'minimum' => (int) ($stock?->minimum_stock ?? 0),
                'location' => $stock?->location ?? '',
            ];
        }
    }

    /**
     * Update location for variant in modal (called by Alpine via wire:model)
     */
    public function updateVariantModalStockLocation(int $warehouseId, string $location): void
    {
        if (isset($this->variantModalStock[$warehouseId])) {
            $this->variantModalStock[$warehouseId]['location'] = $location;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC LOCK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if variant modal should be locked (active sync job running)
     *
     * Mirrors the parent product lock behavior:
     * - PrestaShop sync job active
     * - ERP sync job active
     *
     * @return bool True if modal inputs should be disabled
     */
    public function isVariantModalLocked(): bool
    {
        // Check PrestaShop sync job (uses hasActiveSyncJob from ProductForm)
        if (method_exists($this, 'hasActiveSyncJob') && $this->hasActiveSyncJob()) {
            return true;
        }

        // Check ERP sync job (uses hasActiveErpSyncJob from ProductFormERPTabs)
        if (method_exists($this, 'hasActiveErpSyncJob') && $this->hasActiveErpSyncJob()) {
            return true;
        }

        return false;
    }

    /**
     * Get lock reason for variant modal (for display in UI)
     *
     * @return string|null Lock reason or null if not locked
     */
    public function getVariantModalLockReason(): ?string
    {
        if (method_exists($this, 'hasActiveErpSyncJob') && $this->hasActiveErpSyncJob()) {
            return 'Synchronizacja ERP w trakcie';
        }

        if (method_exists($this, 'hasActiveSyncJob') && $this->hasActiveSyncJob()) {
            return 'Synchronizacja PrestaShop w trakcie';
        }

        return null;
    }
}
