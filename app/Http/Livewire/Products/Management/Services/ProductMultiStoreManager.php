<?php

namespace App\Http\Livewire\Products\Management\Services;

use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ProductMultiStoreManager Service
 *
 * Handles multi-store functionality for ProductForm
 * Manages shop-specific data and synchronization
 * Separated from main component per CLAUDE.md guidelines
 *
 * @package App\Http\Livewire\Products\Management\Services
 */
class ProductMultiStoreManager
{
    protected $component;

    public function __construct($component)
    {
        $this->component = $component;
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP DATA MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Load existing shop-specific data for edit mode
     * CRITICAL: Only stores custom values, does NOT overwrite default data
     */
    public function loadShopData(): void
    {
        if (!$this->component->product || !$this->component->product->exists) {
            return;
        }

        // Get all shop data for this product
        $shopDataRecords = ProductShopData::where('product_id', $this->component->product->id)
            ->get();

        foreach ($shopDataRecords as $shopData) {
            $this->component->exportedShops[] = $shopData->shop_id;

            // Store only custom shop data (not null/empty values)
            $this->component->shopData[$shopData->shop_id] = [
                'name' => $shopData->name ?? '',
                'slug' => $shopData->slug ?? '',
                'short_description' => $shopData->short_description ?? '',
                'long_description' => $shopData->long_description ?? '',
                'meta_title' => $shopData->meta_title ?? '',
                'meta_description' => $shopData->meta_description ?? '',
                'sync_status' => $shopData->sync_status ?? 'pending',
                'is_published' => $shopData->is_published ?? false,
                'last_sync_at' => $shopData->last_sync_at,
            ];

            // Load shop-specific categories if they exist
            if (!empty($shopData->category_mappings)) {
                $this->component->shopCategories[$shopData->shop_id] = [
                    'selected' => $shopData->category_mappings['selected'] ?? [],
                    'primary' => $shopData->category_mappings['primary'] ?? null,
                ];
            }

            // Load shop-specific attributes if they exist
            if (!empty($shopData->attribute_mappings)) {
                $this->component->shopAttributes[$shopData->shop_id] = $shopData->attribute_mappings;
            }
        }

        Log::info('Shop data loaded for product', [
            'product_id' => $this->component->product->id,
            'exported_shops' => count($this->component->exportedShops),
        ]);
    }

    /**
     * Add product to selected shops
     */
    public function addToShops(): void
    {
        if (empty($this->component->selectedShopsToAdd)) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->component->selectedShopsToAdd as $shopId) {
                if (!in_array($shopId, $this->component->exportedShops)) {
                    $this->component->exportedShops[] = $shopId;

                    // Initialize empty shop data
                    $this->component->shopData[$shopId] = [
                        'name' => '',
                        'slug' => '',
                        'short_description' => '',
                        'long_description' => '',
                        'meta_title' => '',
                        'meta_description' => '',
                        'sync_status' => 'pending',
                        'is_published' => false,
                        'last_sync_at' => null,
                    ];

                    // Initialize shop categories and attributes
                    $this->component->shopCategories[$shopId] = [
                        'selected' => [],
                        'primary' => null,
                    ];
                    $this->component->shopAttributes[$shopId] = [];
                }
            }
        });

        // Reset selection and close modal
        $this->component->selectedShopsToAdd = [];
        $this->component->showShopSelector = false;

        $addedCount = count($this->component->selectedShopsToAdd);
        $this->component->dispatch('success', message: "Produkt został dodany do {$addedCount} sklepów");
    }

    /**
     * Remove product from specific shop
     */
    public function removeFromShop(int $shopId): void
    {
        // Remove from exported shops
        $this->component->exportedShops = array_filter(
            $this->component->exportedShops,
            fn($id) => $id !== $shopId
        );

        // Remove shop data
        unset($this->component->shopData[$shopId]);
        unset($this->component->shopCategories[$shopId]);
        unset($this->component->shopAttributes[$shopId]);

        // If currently viewing this shop, switch to default
        if ($this->component->activeShopId === $shopId) {
            $this->switchToShop(null);
        }

        $this->component->dispatch('success', message: 'Produkt został usunięty ze sklepu');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP SWITCHING
    |--------------------------------------------------------------------------
    */

    /**
     * Switch between shops or default data
     */
    public function switchToShop(?int $shopId = null): void
    {
        // Save current data before switching
        if ($this->component->activeShopId === null) {
            $this->saveCurrentDefaultData();
        } else {
            $this->saveCurrentShopData();
        }

        // Switch to new view
        $this->component->activeShopId = $shopId;

        // Load appropriate data
        if ($shopId === null) {
            $this->loadDefaultDataToForm();
        } else {
            $this->loadShopDataToForm($shopId);
        }

        $this->component->dispatch('shop-switched', shopId: $shopId);
    }

    /**
     * Load default data to form
     */
    private function loadDefaultDataToForm(): void
    {
        $this->component->name = $this->component->defaultData['name'] ?? '';
        $this->component->slug = $this->component->defaultData['slug'] ?? '';
        $this->component->short_description = $this->component->defaultData['short_description'] ?? '';
        $this->component->long_description = $this->component->defaultData['long_description'] ?? '';
        $this->component->meta_title = $this->component->defaultData['meta_title'] ?? '';
        $this->component->meta_description = $this->component->defaultData['meta_description'] ?? '';
    }

    /**
     * Load shop-specific data to form
     */
    private function loadShopDataToForm(int $shopId): void
    {
        // Load shop data with fallback to default values
        $this->component->name = $this->getShopValue($shopId, 'name');
        $this->component->slug = $this->getShopValue($shopId, 'slug');
        $this->component->short_description = $this->getShopValue($shopId, 'short_description');
        $this->component->long_description = $this->getShopValue($shopId, 'long_description');
        $this->component->meta_title = $this->getShopValue($shopId, 'meta_title');
        $this->component->meta_description = $this->getShopValue($shopId, 'meta_description');
    }

    /**
     * Get shop-specific value with fallback to default
     */
    private function getShopValue(int $shopId, string $field): string
    {
        // First check shop-specific data
        if (isset($this->component->shopData[$shopId][$field]) &&
            !empty($this->component->shopData[$shopId][$field])) {
            return $this->component->shopData[$shopId][$field];
        }

        // Fallback to default data
        return $this->component->defaultData[$field] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | DATA PERSISTENCE
    |--------------------------------------------------------------------------
    */

    /**
     * Save current shop-specific data
     */
    private function saveCurrentShopData(): void
    {
        if ($this->component->activeShopId === null) {
            return;
        }

        $shopId = $this->component->activeShopId;

        $this->component->shopData[$shopId] = [
            'name' => $this->component->name,
            'slug' => $this->component->slug,
            'short_description' => $this->component->short_description,
            'long_description' => $this->component->long_description,
            'meta_title' => $this->component->meta_title,
            'meta_description' => $this->component->meta_description,
            'sync_status' => $this->component->shopData[$shopId]['sync_status'] ?? 'pending',
            'is_published' => $this->component->shopData[$shopId]['is_published'] ?? false,
            'last_sync_at' => $this->component->shopData[$shopId]['last_sync_at'] ?? null,
        ];

        Log::info('Shop data saved for switching', [
            'shop_id' => $shopId,
            'product_id' => $this->component->product?->id,
        ]);
    }

    /**
     * Save current default data
     */
    private function saveCurrentDefaultData(): void
    {
        $this->component->defaultData = [
            'name' => $this->component->name,
            'slug' => $this->component->slug,
            'short_description' => $this->component->short_description,
            'long_description' => $this->component->long_description,
            'meta_title' => $this->component->meta_title,
            'meta_description' => $this->component->meta_description,
        ];

        Log::info('Default data saved', [
            'product_id' => $this->component->product?->id,
            'default_data' => $this->component->defaultData,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Open shop selector modal
     */
    public function openShopSelector(): void
    {
        $this->component->selectedShopsToAdd = [];
        $this->component->showShopSelector = true;
    }

    /**
     * Close shop selector modal
     */
    public function closeShopSelector(): void
    {
        $this->component->showShopSelector = false;
        $this->component->selectedShopsToAdd = [];
    }
}