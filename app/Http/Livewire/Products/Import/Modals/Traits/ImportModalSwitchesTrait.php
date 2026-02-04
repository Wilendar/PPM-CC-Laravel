<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\Import\Modals\Traits;

use App\Models\PendingProduct;
use Illuminate\Support\Facades\Log;

/**
 * ImportModalSwitchesTrait - Shared switches for CSV and Column import modes
 *
 * Provides boolean switches that apply to ALL imported products:
 * - shop_internet: Auto-assign to online shop
 * - split_payment: Mark as split payment eligible
 * - is_variant_master: Mark as variant master product
 *
 * @package App\Http\Livewire\Products\Import\Modals\Traits
 */
trait ImportModalSwitchesTrait
{
    /**
     * Auto-assign products to online shop
     */
    public bool $switchShopInternet = false;

    /**
     * Mark products as split payment eligible
     */
    public bool $switchSplitPayment = false;

    /**
     * Mark products as variant master
     */
    public bool $switchVariantProduct = false;

    /**
     * Toggle shop internet switch
     */
    public function toggleShopInternet(): void
    {
        $this->switchShopInternet = !$this->switchShopInternet;

        Log::debug('ImportModalSwitchesTrait: toggleShopInternet', [
            'value' => $this->switchShopInternet,
        ]);
    }

    /**
     * Toggle split payment switch
     */
    public function toggleSplitPayment(): void
    {
        $this->switchSplitPayment = !$this->switchSplitPayment;

        Log::debug('ImportModalSwitchesTrait: toggleSplitPayment', [
            'value' => $this->switchSplitPayment,
        ]);
    }

    /**
     * Toggle variant product switch
     *
     * When enabled, dispatches event to show variant configuration button.
     */
    public function toggleVariantProduct(): void
    {
        $this->switchVariantProduct = !$this->switchVariantProduct;

        Log::debug('ImportModalSwitchesTrait: toggleVariantProduct', [
            'value' => $this->switchVariantProduct,
        ]);
    }

    /**
     * Open variant modal from import context
     *
     * Dispatches event to open the existing VariantModal on top of this modal.
     */
    public function openVariantModalFromImport(): void
    {
        $this->dispatch('openVariantModal');

        Log::debug('ImportModalSwitchesTrait: openVariantModalFromImport dispatched');
    }

    /**
     * Apply switch values to a PendingProduct
     *
     * Called during import for each created product.
     */
    protected function applySwitchesToProduct(PendingProduct $product): void
    {
        $product->shop_internet = $this->switchShopInternet;
        $product->split_payment = $this->switchSplitPayment;
        $product->is_variant_master = $this->switchVariantProduct;
    }

    /**
     * Reset all switches to defaults
     */
    protected function resetSwitches(): void
    {
        $this->switchShopInternet = false;
        $this->switchSplitPayment = false;
        $this->switchVariantProduct = false;
    }
}
