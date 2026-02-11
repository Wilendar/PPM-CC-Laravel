<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\PrestaShop\PullSingleProductFromPrestaShop;
use App\Services\PrestaShop\ShopVariantService;
use Illuminate\Support\Facades\Log;

/**
 * ProductFormShopTabs Trait
 *
 * FAZA 9.4: Shop Tab na karcie produktu
 *
 * Trait zarządzający zakładkami sklepów w formularzu produktu,
 * umożliwiający synchronizację, pobieranie danych i odłączanie
 * produktów od poszczególnych sklepów PrestaShop.
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since FAZA 9.4 - Shop Tab Implementation
 */
trait ProductFormShopTabs
{
    /**
     * Active shop tab identifier
     */
    public string $activeShopTab = 'all';

    /**
     * Currently selected shop ID
     */
    public ?int $selectedShopId = null;

    /**
     * Variants pulled from PrestaShop API for current shop
     * Format: ['variants' => Collection, 'synced' => bool, 'error' => ?string]
     */
    public array $prestaShopVariants = [];

    /**
     * Flag indicating if variants are being pulled from PrestaShop
     */
    public bool $pullingShopVariants = false;

    /**
     * Select shop tab and set active shop
     *
     * ETAP_05c FIX: Pulls variants LIVE from PrestaShop API when entering shop tab
     *
     * @param int $shopId
     * @return void
     */
    public function selectShopTab(int $shopId): void
    {
        $this->selectedShopId = $shopId;
        $this->activeShopTab = "shop_{$shopId}";

        // FIX PROBLEM 1: Set activeShopId for getAllVariantsForDisplay() to use PrestaShop data
        // Without this, activeShopId stays null and variants tab shows local data instead of API data
        $this->activeShopId = $shopId;

        // ETAP_05c: Pull variants from PrestaShop API when entering shop tab
        if ($this->product && $this->isEditMode) {
            $this->pullVariantsFromPrestaShop($shopId);

            // FIX: Switch variant context to enable shop-specific overrides
            $this->switchVariantContextToShop($shopId);
        }

        Log::info('Shop tab selected', [
            'product_id' => $this->product->id ?? null,
            'shop_id' => $shopId,
            'active_shop_id' => $this->activeShopId,
            'variants_pulled' => !empty($this->prestaShopVariants),
        ]);
    }

    /**
     * Pull variants from PrestaShop API for specific shop
     *
     * ETAP_05c: Called when entering shop tab to get LIVE data from PrestaShop
     *
     * @param int $shopId
     * @return void
     */
    protected function pullVariantsFromPrestaShop(int $shopId): void
    {
        if (!$this->product) {
            return;
        }

        $this->pullingShopVariants = true;

        try {
            $service = app(ShopVariantService::class);
            $result = $service->pullShopVariants($this->product, $shopId);

            $this->prestaShopVariants = $result;

            // FIX 2026-02-11: Store PS product images for variant image picker
            if (property_exists($this, 'psProductImages')) {
                $this->psProductImages = $result['product_images'] ?? [];
            }

            Log::info('[ProductFormShopTabs] Pulled variants from PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'variants_count' => $result['variants']->count(),
                'synced' => $result['synced'],
                'error' => $result['error'] ?? null,
                'product_images_count' => count($result['product_images'] ?? []),
            ]);

        } catch (\Exception $e) {
            Log::error('[ProductFormShopTabs] Failed to pull variants from PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->prestaShopVariants = [
                'variants' => collect(),
                'synced' => false,
                'error' => $e->getMessage(),
            ];
        }

        $this->pullingShopVariants = false;
    }

    /**
     * Reset shop to default (Dane Domyslne)
     *
     * @return void
     */
    public function selectDefaultTab(): void
    {
        $this->selectedShopId = null;
        $this->activeShopTab = 'all';
        $this->prestaShopVariants = []; // Clear PS variants when going back to default

        // FIX 2026-02-11: Clear PS product images when going back to default
        if (property_exists($this, 'psProductImages')) {
            $this->psProductImages = [];
        }

        // FIX PROBLEM 1: Reset activeShopId to null for getAllVariantsForDisplay() to use local data
        $this->activeShopId = null;

        // Restore variant context to default
        if (method_exists($this, 'restoreVariantContextToDefault')) {
            $this->restoreVariantContextToDefault();
        }

        Log::info('Default tab selected (Dane Domyslne)', [
            'product_id' => $this->product->id ?? null,
            'active_shop_id' => $this->activeShopId,
        ]);
    }

    /**
     * Sync product to specific shop
     *
     * Dispatches single-shop sync job to queue
     *
     * @param int $shopId
     * @return void
     */
    public function syncShop(int $shopId): void
    {
        try {
            // Find shop data
            $shopData = $this->product->shopData->where('shop_id', $shopId)->first();

            if (!$shopData) {
                $this->addError('shop_sync', 'Produkt nie jest połączony z tym sklepem');
                Log::warning('Shop sync failed - product not linked', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            // Verify shop exists and is active
            if (!$shopData->shop || !$shopData->shop->is_active) {
                $this->addError('shop_sync', 'Sklep jest nieaktywny lub nie istnieje');
                Log::warning('Shop sync failed - shop inactive', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            // Dispatch sync job - CORRECT argument order: (Product, PrestaShopShop, userId)
            SyncProductToPrestaShop::dispatch(
                $this->product,      // Product instance
                $shopData->shop,     // PrestaShopShop instance
                auth()->id()         // User ID who triggered sync
            );

            session()->flash('message', 'Zadanie synchronizacji zostało uruchomione dla tego sklepu');

            Log::info('Shop sync job dispatched', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'shop_name' => $shopData->shop->name,
            ]);

            // Refresh product data to show pending status
            $this->product->refresh();

        } catch (\Exception $e) {
            $this->addError('shop_sync', 'Błąd podczas uruchamiania synchronizacji: ' . $e->getMessage());

            Log::error('Shop sync job dispatch failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pull latest data from PrestaShop for specific shop
     *
     * Dispatches pull job to fetch current data from PrestaShop
     *
     * @param int $shopId
     * @return void
     */
    public function pullShopData(int $shopId): void
    {
        try {
            // Find shop data
            $shopData = $this->product->shopData->where('shop_id', $shopId)->first();

            if (!$shopData) {
                $this->addError('shop_pull', 'Produkt nie jest połączony z tym sklepem');
                Log::warning('Shop pull failed - product not linked', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            // Verify PrestaShop product exists
            if (!$shopData->prestashop_product_id) {
                $this->addError('shop_pull', 'Produkt nie ma ID w PrestaShop - wykonaj najpierw synchronizację');
                Log::warning('Shop pull failed - no PrestaShop ID', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            // Verify shop exists and is active
            if (!$shopData->shop || !$shopData->shop->is_active) {
                $this->addError('shop_pull', 'Sklep jest nieaktywny lub nie istnieje');
                Log::warning('Shop pull failed - shop inactive', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return;
            }

            // Dispatch single-product pull job
            PullSingleProductFromPrestaShop::dispatch(
                $this->product,      // Product instance
                $shopData->shop      // PrestaShopShop instance
            );

            session()->flash('message', 'Pobieranie danych z PrestaShop zostało uruchomione - dane zostaną zaktualizowane za chwilę');

            Log::info('Shop pull job dispatched', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'shop_name' => $shopData->shop->name,
                'prestashop_product_id' => $shopData->prestashop_product_id,
            ]);

        } catch (\Exception $e) {
            $this->addError('shop_pull', 'Błąd podczas pobierania danych: ' . $e->getMessage());

            Log::error('Shop pull job dispatch failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unlink product from specific shop
     *
     * Removes ProductShopData record and disconnects product from shop
     *
     * @param int $shopId
     * @return void
     */
    public function unlinkShop(int $shopId): void
    {
        try {
            // Find shop data
            $shopData = $this->product->shopData->where('shop_id', $shopId)->first();

            if (!$shopData) {
                $this->addError('shop_unlink', 'Produkt nie jest połączony z tym sklepem');
                return;
            }

            $shopName = $shopData->shop->name ?? 'Unknown Shop';

            // Delete shop data record
            $shopData->delete();

            session()->flash('message', "Produkt został odłączony od sklepu: {$shopName}");
            $this->dispatch('shopUnlinked');

            Log::info('Product unlinked from shop', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'shop_name' => $shopName,
            ]);

            // Refresh product to update UI
            $this->product->refresh();

            // If no shops left, reset active shop tab
            if ($this->product->shopData->isEmpty()) {
                $this->activeShopTab = 'all';
                $this->selectedShopId = null;
            }

        } catch (\Exception $e) {
            $this->addError('shop_unlink', 'Błąd podczas odłączania sklepu: ' . $e->getMessage());

            Log::error('Shop unlink failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get formatted time since last sync
     *
     * @param \App\Models\ProductShopData $shopData
     * @return string
     */
    protected function getTimeSinceSync($shopData): string
    {
        if (!$shopData->last_sync_at) {
            return 'Nigdy';
        }

        return $shopData->last_sync_at->diffForHumans();
    }

    /**
     * Get formatted time since last pull
     *
     * @param \App\Models\ProductShopData $shopData
     * @return string
     */
    protected function getTimeSincePull($shopData): string
    {
        if (!$shopData->last_pulled_at) {
            return 'Nigdy';
        }

        return $shopData->last_pulled_at->diffForHumans();
    }
}
