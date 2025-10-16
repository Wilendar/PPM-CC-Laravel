<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * CategoryConflictModal Component
 *
 * Modal dla rozwiązywania konfliktów kategorii między default a per-shop
 * Wyświetla różnice i pozwala użytkownikowi wybrać które kategorie zachować
 *
 * @package App\Http\Livewire\Products
 * @version 1.0
 * @since 2025-10-13 - Per-Shop Categories Conflict Resolution
 */
class CategoryConflictModal extends Component
{
    public bool $isOpen = false;
    public ?int $productId = null;
    public ?int $shopId = null;

    // Conflict data
    public array $defaultCategories = [];
    public array $shopCategories = [];
    public ?string $detectedAt = null;
    public ?string $shopName = null;
    public ?string $productName = null;

    protected $listeners = [
        'showCategoryConflict' => 'show',
    ];

    /**
     * Show modal with conflict data
     */
    public function show(int $productId, int $shopId): void
    {
        $this->productId = $productId;
        $this->shopId = $shopId;

        $this->loadConflictData();
        $this->isOpen = true;

        Log::info('Category conflict modal opened', [
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);
    }

    /**
     * Load conflict data from ProductShopData
     */
    private function loadConflictData(): void
    {
        $product = Product::find($this->productId);
        $shop = PrestaShopShop::find($this->shopId);
        $shopData = ProductShopData::where('product_id', $this->productId)
            ->where('shop_id', $this->shopId)
            ->first();

        if (!$product || !$shop || !$shopData || !$shopData->conflict_data) {
            $this->close();
            return;
        }

        $conflictData = $shopData->conflict_data;
        $this->detectedAt = $conflictData['detected_at'] ?? now()->toISOString();
        $this->shopName = $shop->name;
        $this->productName = $product->name;

        // Load default categories with names
        $defaultCategoryIds = $conflictData['default_categories'] ?? [];
        $this->defaultCategories = Category::whereIn('id', $defaultCategoryIds)
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'full_path' => $cat->getFullPath(),
            ])
            ->toArray();

        // Load shop categories with names
        $shopCategoryIds = $conflictData['shop_categories'] ?? [];
        $this->shopCategories = Category::whereIn('id', $shopCategoryIds)
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'full_path' => $cat->getFullPath(),
            ])
            ->toArray();

        Log::info('Conflict data loaded', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'default_count' => count($this->defaultCategories),
            'shop_count' => count($this->shopCategories),
        ]);
    }

    /**
     * Use categories from shop import (keep per-shop override)
     */
    public function useShopCategories(): void
    {
        $this->resolveConflict('use_shop');
    }

    /**
     * Use default categories (remove per-shop override)
     */
    public function useDefaultCategories(): void
    {
        $this->resolveConflict('use_default');
    }

    /**
     * Resolve conflict by user choice
     *
     * UPDATED 2025-10-13: Per-shop categories ALWAYS saved during import
     * Modal only asks: "Update DEFAULT categories (shop_id=NULL) to match shop?"
     *
     * - use_shop: Update DEFAULT categories (shop_id=NULL) = shop categories
     * - use_default: Keep DEFAULT categories (shop_id=NULL) unchanged
     *
     * Per-shop categories (shop_id=X) are already saved in DB!
     */
    private function resolveConflict(string $action): void
    {
        try {
            DB::transaction(function () use ($action) {
                // Get conflict data
                $shopData = ProductShopData::where('product_id', $this->productId)
                    ->where('shop_id', $this->shopId)
                    ->first();

                if (!$shopData || !$shopData->conflict_data) {
                    Log::warning('No conflict data found - cannot resolve', [
                        'product_id' => $this->productId,
                        'shop_id' => $this->shopId,
                    ]);
                    return;
                }

                $conflictData = $shopData->conflict_data;
                $shopCategoryIds = $conflictData['shop_categories'] ?? [];

                if ($action === 'use_shop') {
                    // UPDATE DEFAULT categories (shop_id=NULL) to match shop
                    if (empty($shopCategoryIds)) {
                        Log::error('No shop category IDs in conflict_data', [
                            'product_id' => $this->productId,
                            'shop_id' => $this->shopId,
                        ]);
                        return;
                    }

                    // Get per-shop categories pivot data (already saved with shop_id=X)
                    $shopCategories = DB::table('product_categories')
                        ->where('product_id', $this->productId)
                        ->where('shop_id', $this->shopId)
                        ->get();

                    // Reset is_primary for default categories
                    DB::table('product_categories')
                        ->where('product_id', $this->productId)
                        ->whereNull('shop_id')
                        ->update(['is_primary' => false]);

                    // Remove old default categories
                    DB::table('product_categories')
                        ->where('product_id', $this->productId)
                        ->whereNull('shop_id')
                        ->delete();

                    // Insert new default categories (copy from per-shop)
                    foreach ($shopCategories as $shopCat) {
                        DB::table('product_categories')->insert([
                            'product_id' => $this->productId,
                            'category_id' => $shopCat->category_id,
                            'shop_id' => null, // DEFAULT categories
                            'is_primary' => $shopCat->is_primary,
                            'sort_order' => $shopCat->sort_order,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Log::info('DEFAULT categories updated to match shop (user chose use_shop)', [
                        'product_id' => $this->productId,
                        'shop_id' => $this->shopId,
                        'category_count' => count($shopCategories),
                        'action' => $action,
                    ]);

                } elseif ($action === 'use_default') {
                    // KEEP DEFAULT categories (shop_id=NULL) unchanged
                    // Per-shop categories (shop_id=X) already exist - no action needed

                    Log::info('DEFAULT categories kept unchanged (user chose use_default)', [
                        'product_id' => $this->productId,
                        'shop_id' => $this->shopId,
                        'action' => $action,
                        'note' => 'Per-shop categories (shop_id=X) remain in DB, default (shop_id=NULL) unchanged',
                    ]);
                }

                // Clear conflict_data and requires_resolution flag
                $shopData->update([
                    'conflict_data' => null,
                    'conflict_detected_at' => null,
                    'requires_resolution' => false,
                ]);

                Log::info('Category conflict resolved', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                    'action' => $action,
                    'resolution' => $action === 'use_default' ? 'Default categories kept' : 'Default categories updated to match shop',
                ]);
            });

            $this->dispatch('conflictResolved', [
                'productId' => $this->productId,
                'shopId' => $this->shopId,
                'action' => $action,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $action === 'use_default'
                    ? 'Konflikt rozwiązany - używane domyślne kategorie'
                    : 'Konflikt rozwiązany - używane kategorie ze sklepu',
            ]);

            $this->close();
        } catch (\Exception $e) {
            Log::error('Failed to resolve category conflict', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas rozwiązywania konfliktu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['productId', 'shopId', 'defaultCategories', 'shopCategories', 'detectedAt', 'shopName', 'productName']);
    }

    public function render()
    {
        return view('livewire.products.category-conflict-modal');
    }
}
