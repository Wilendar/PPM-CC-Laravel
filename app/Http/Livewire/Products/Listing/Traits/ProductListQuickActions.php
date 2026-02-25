<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Product;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

/**
 * ProductListQuickActions Trait
 *
 * Manages single-product quick actions from the listing:
 * - Toggle product/variant status (activate/deactivate)
 * - Delete variant
 * - Preview modal (show/close)
 * - Sync product to shops
 * - Publish to all shops
 * - Delete product (with confirmation modal)
 * - Duplicate product
 * - Event listeners (shops-updated, progress-completed)
 * - Polling methods (category previews, sync job statuses)
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListQuickActions
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Preview Modal
    public bool $showPreviewModal = false;
    public ?Product $selectedProduct = null;

    // Delete Modal
    public bool $showDeleteModal = false;
    public ?int $productToDelete = null;

    /*
    |--------------------------------------------------------------------------
    | TOGGLE STATUS
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $product->is_active = !$product->is_active;
        $product->save();

        $status = $product->is_active ? 'aktywowany' : 'deaktywowany';
        $this->dispatch('success', message: "Produkt został {$status}");
    }

    public function toggleVariantStatus(int $variantId): void
    {
        $variant = \App\Models\ProductVariant::find($variantId);

        if (!$variant) {
            $this->dispatch('error', message: 'Wariant nie zostal znaleziony');
            return;
        }

        $variant->is_active = !$variant->is_active;
        $variant->save();

        $status = $variant->is_active ? 'aktywowany' : 'deaktywowany';
        $this->dispatch('success', message: "Wariant zostal {$status}");
    }

    public function deleteVariant(int $variantId): void
    {
        $variant = \App\Models\ProductVariant::find($variantId);

        if (!$variant) {
            $this->dispatch('error', message: 'Wariant nie zostal znaleziony');
            return;
        }

        $variantSku = $variant->sku;
        $variant->delete();

        $this->dispatch('success', message: "Wariant {$variantSku} zostal usuniety");
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW MODAL
    |--------------------------------------------------------------------------
    */

    public function showProductPreview(int $productId): void
    {
        $this->selectedProduct = Product::with([
            'productType',
            'shopData.shop:id,name,label_color,label_icon',
        ])->find($productId);

        if (!$this->selectedProduct) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $this->showPreviewModal = true;
    }

    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->selectedProduct = null;
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC & PUBLISH
    |--------------------------------------------------------------------------
    */

    public function syncProduct(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        try {
            $updatedCount = $product->markAllShopsForSync();

            if ($this->showPreviewModal && $this->selectedProduct?->id === $productId) {
                $this->closePreviewModal();
            }

            if ($updatedCount > 0) {
                $this->dispatch('success', message: "Synchronizacja produktu {$product->sku} została zaplanowana dla {$updatedCount} sklepów");
            } else {
                $this->dispatch('info', message: "Produkt {$product->sku} nie ma skonfigurowanych sklepów do synchronizacji");
            }
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Błąd podczas planowania synchronizacji: ' . $e->getMessage());
        }
    }

    public function publishToShops(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        try {
            $activeShops = \App\Models\PrestaShopShop::active()->get();

            if ($activeShops->isEmpty()) {
                $this->dispatch('warning', message: 'Brak aktywnych sklepów do publikacji');
                return;
            }

            $publishedCount = 0;
            foreach ($activeShops as $shop) {
                $shopData = $product->publishToShop($shop->id);
                if ($shopData) {
                    $publishedCount++;
                }
            }

            $this->dispatch('success', message: "Produkt {$product->sku} został opublikowany na {$publishedCount} sklepach");
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Błąd podczas publikacji: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE ACTIONS
    |--------------------------------------------------------------------------
    */

    public function confirmDelete(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $this->productToDelete = $productId;
        $this->showDeleteModal = true;
    }

    public function deleteProduct(): void
    {
        if (!$this->productToDelete) {
            $this->dispatch('error', message: 'Brak produktu do usunięcia');
            return;
        }

        $product = Product::find($this->productToDelete);
        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            $this->cancelDelete();
            return;
        }

        try {
            $sku = $product->sku;
            $product->forceDelete();

            Log::info('Quick Action delete completed', [
                'product_id' => $this->productToDelete,
                'sku' => $sku,
            ]);

            $this->dispatch('success', message: "Produkt {$sku} został trwale usunięty");
            $this->cancelDelete();
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Quick Action delete failed', [
                'product_id' => $this->productToDelete,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania produktu: ' . $e->getMessage());
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->productToDelete = null;
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE
    |--------------------------------------------------------------------------
    */

    public function duplicateProduct(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'Produkt nie został znaleziony');
            return;
        }

        $newProduct = $product->replicate();
        $newProduct->sku = $this->generateDuplicateSku($product->sku);
        $newProduct->name = $product->name . ' (kopia)';
        $newProduct->is_active = false;
        $newProduct->save();

        $this->dispatch('success', message: 'Produkt został zduplikowany');
        $this->dispatch('product-duplicated', productId: $newProduct->id);
    }

    private function generateDuplicateSku(string $originalSku): string
    {
        $baseSku = $originalSku . '-COPY';
        $counter = 1;
        $newSku = $baseSku;

        while (Product::where('sku', $newSku)->exists()) {
            $newSku = $baseSku . '-' . $counter;
            $counter++;
        }

        return $newSku;
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    #[On('shops-updated')]
    public function refreshAfterShopUpdate($productId = null): void
    {
        unset($this->products);
        $this->resetPage();
        $this->perPage = $this->perPage;

        Log::info('ProductList refreshed after shop update', [
            'product_id' => $productId,
            'current_page' => $this->getPage(),
        ]);

        $this->js('$wire.$refresh()');
    }

    #[On('progress-completed')]
    public function refreshAfterImport(): void
    {
        unset($this->products);
        $this->resetPage();
        $this->perPage = $this->perPage;

        Log::info('ProductList refreshed after import completion');

        $this->js('$wire.$refresh()');
    }

    /*
    |--------------------------------------------------------------------------
    | POLLING METHODS
    |--------------------------------------------------------------------------
    */

    public function checkForPendingCategoryPreviews(): void
    {
        if (empty($this->activeJobProgress)) {
            return;
        }

        $activeJobIds = collect($this->activeJobProgress)->pluck('job_id')->filter()->toArray();

        if (empty($activeJobIds)) {
            return;
        }

        $pendingPreviews = \App\Models\CategoryPreview::whereIn('job_id', $activeJobIds)
            ->where('status', \App\Models\CategoryPreview::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->get();

        foreach ($pendingPreviews as $preview) {
            if (in_array($preview->id, $this->shownPreviewIds, true)) {
                continue;
            }

            Log::info('ProductList: Pending CategoryPreview detected via polling', [
                'preview_id' => $preview->id,
                'job_id' => $preview->job_id,
                'shop_id' => $preview->shop_id,
            ]);

            $this->isAnalyzingCategories = false;
            $this->analyzingShopName = null;
            $this->shownPreviewIds[] = $preview->id;

            break;
        }
    }

    public function checkSyncJobStatuses(): void
    {
        $paginator = $this->products;
        if ($paginator->isEmpty()) {
            return;
        }

        $productIds = $paginator->getCollection()->pluck('id')->toArray();

        $activeJobs = \App\Models\SyncJob::whereIn('source_id', $productIds)
            ->where('source_type', \App\Models\SyncJob::TYPE_PPM)
            ->whereIn('status', [\App\Models\SyncJob::STATUS_PENDING, \App\Models\SyncJob::STATUS_RUNNING])
            ->count();

        if ($this->previousActiveSyncJobCount !== null && $activeJobs !== $this->previousActiveSyncJobCount) {
            unset($this->productStatuses);

            Log::info('[SYNC STATUS POLL] Sync job count changed, refreshing statuses', [
                'previous' => $this->previousActiveSyncJobCount,
                'current' => $activeJobs,
            ]);
        }

        $this->previousActiveSyncJobCount = $activeJobs;
    }

    #[On('category-preview-ready')]
    public function handleCategoryPreviewReady(array $data): void
    {
        $previewId = $data['preview_id'] ?? null;

        if (!$previewId) {
            Log::warning('ProductList: category-preview-ready event without preview_id', [
                'event_data' => $data,
            ]);
            return;
        }

        Log::info('ProductList: CategoryPreviewReady event received', [
            'preview_id' => $previewId,
            'job_id' => $data['job_id'] ?? null,
            'shop_id' => $data['shop_id'] ?? null,
        ]);

        $this->dispatch('show-category-preview', previewId: $previewId);
        $this->dispatch('info', message: 'Analiza kategorii ukończona. Sprawdź podgląd przed importem.');
    }
}
