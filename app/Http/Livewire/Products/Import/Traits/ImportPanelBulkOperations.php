<?php

namespace App\Http\Livewire\Products\Import\Traits;

use App\Models\PendingProduct;
use App\Services\Import\ProductPublicationService;
use Illuminate\Support\Facades\Log;

/**
 * ImportPanelBulkOperations - Trait dla operacji masowych
 *
 * ETAP_06: Bulk operations dla ProductImportPanel
 */
trait ImportPanelBulkOperations
{
    /**
     * Toggle product selection
     */
    public function toggleSelection(int $productId): void
    {
        if (in_array($productId, $this->selectedIds)) {
            $this->selectedIds = array_filter(
                $this->selectedIds,
                fn($id) => $id !== $productId
            );
        } else {
            $this->selectedIds[] = $productId;
        }

        // Update selectAll flag
        $this->updateSelectAllFlag();
    }

    /**
     * Select all products on current page
     */
    public function selectAllOnPage(): void
    {
        $pageIds = $this->pendingProducts->pluck('id')->toArray();
        $this->selectedIds = array_unique(array_merge($this->selectedIds, $pageIds));
        $this->selectAll = true;
    }

    /**
     * Select all products (across all pages)
     */
    public function selectAllProducts(): void
    {
        $this->selectedIds = PendingProduct::unpublished()
            ->when($this->filterStatus, fn($q) => $this->applyStatusFilter($q))
            ->when($this->filterProductType, fn($q) => $q->where('product_type_id', $this->filterProductType))
            ->when($this->filterSessionId, fn($q) => $q->where('import_session_id', $this->filterSessionId))
            ->when($this->filterSearch, fn($q) => $this->applySearchFilter($q))
            ->pluck('id')
            ->toArray();

        $this->selectAll = true;
    }

    /**
     * Deselect all products
     */
    public function deselectAll(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * Update selectAll flag based on current selection
     */
    protected function updateSelectAllFlag(): void
    {
        $pageIds = $this->pendingProducts->pluck('id')->toArray();
        $this->selectAll = !empty($pageIds) && empty(array_diff($pageIds, $this->selectedIds));
    }

    /**
     * Check if product is selected
     */
    public function isSelected(int $productId): bool
    {
        return in_array($productId, $this->selectedIds);
    }

    /**
     * Bulk delete selected products
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        try {
            $count = PendingProduct::whereIn('id', $this->selectedIds)
                ->whereNull('published_at') // Don't delete published
                ->delete();

            $this->selectedIds = [];
            $this->selectAll = false;

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Usunieto {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas usuwania: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk set product type for selected products
     */
    public function bulkSetType(int $typeId): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $count = PendingProduct::whereIn('id', $this->selectedIds)
                ->update(['product_type_id' => $typeId]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zaktualizowano typ dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk set type failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas aktualizacji typu',
            ]);
        }
    }

    /**
     * Bulk set shops for selected products
     */
    public function bulkSetShops(array $shopIds): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $count = PendingProduct::whereIn('id', $this->selectedIds)
                ->update(['shop_ids' => json_encode($shopIds)]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zaktualizowano sklepy dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk set shops failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas aktualizacji sklepow',
            ]);
        }
    }

    /**
     * Bulk set category for selected products
     */
    public function bulkSetCategory(array $categoryIds): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $count = PendingProduct::whereIn('id', $this->selectedIds)
                ->update(['category_ids' => json_encode($categoryIds)]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Zaktualizowano kategorie dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk set category failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas aktualizacji kategorii',
            ]);
        }
    }

    /**
     * Bulk publish selected products
     *
     * FAZA 6: Implementacja masowej publikacji
     */
    public function bulkPublish(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        // Get ready products only
        $readyProducts = PendingProduct::whereIn('id', $this->selectedIds)
            ->readyForPublish()
            ->get();

        if ($readyProducts->isEmpty()) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Zaden z wybranych produktow nie jest gotowy do publikacji',
            ]);
            return;
        }

        $readyIds = $readyProducts->pluck('id')->toArray();
        $notReadyCount = count($this->selectedIds) - count($readyIds);

        try {
            // Use publication service for batch processing
            $service = app(ProductPublicationService::class);
            $results = $service->publishBatch($readyIds);

            // Clear selection after publish
            $this->selectedIds = [];
            $this->selectAll = false;

            // Build result message
            $message = "Opublikowano {$results['success']} z {$results['total']} produktow";
            if ($results['failed'] > 0) {
                $message .= " ({$results['failed']} bledow)";
            }
            if ($notReadyCount > 0) {
                $message .= ". Pominieto {$notReadyCount} niegotowych produktow.";
            }

            $this->dispatch('flash-message', [
                'type' => $results['failed'] > 0 ? 'warning' : 'success',
                'message' => $message,
            ]);

            Log::info('Bulk publish completed', [
                'total' => $results['total'],
                'success' => $results['success'],
                'failed' => $results['failed'],
                'skipped_not_ready' => $notReadyCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk publish failed', [
                'selected_ids' => $this->selectedIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas publikacji: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get count of selected products
     */
    public function getSelectedCount(): int
    {
        return count($this->selectedIds);
    }

    /**
     * Get count of ready-to-publish in selection
     */
    public function getSelectedReadyCount(): int
    {
        if (empty($this->selectedIds)) {
            return 0;
        }

        return PendingProduct::whereIn('id', $this->selectedIds)
            ->readyForPublish()
            ->count();
    }

    /**
     * REDESIGN: Bulk set category for specific level (L3/L4/L5)
     * Uses the same logic as setCategoryForLevel but for multiple products
     */
    public function bulkSetCategoryLevel(int $level, int $categoryId): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        try {
            $count = 0;
            foreach ($this->selectedIds as $productId) {
                $this->setCategoryForLevel($productId, $level, $categoryId);
                $count++;
            }

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Ustawiono kategorie L{$level} dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk set category level failed', [
                'level' => $level,
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas ustawiania kategorii',
            ]);
        }
    }

    /**
     * REDESIGN: Bulk add single shop to selected products
     * Adds to existing shops (doesn't replace)
     */
    public function bulkAddShop(int $shopId): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        try {
            $count = 0;
            $products = PendingProduct::whereIn('id', $this->selectedIds)->get();

            foreach ($products as $product) {
                $currentShops = $product->shop_ids ?? [];
                if (!in_array($shopId, $currentShops)) {
                    $currentShops[] = $shopId;
                    $product->setShops($currentShops);
                    $count++;
                }
            }

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Dodano sklep do {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk add shop failed', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas dodawania sklepu',
            ]);
        }
    }

    /**
     * REDESIGN: Bulk clear all shops from selected products
     */
    public function bulkClearShops(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        try {
            $count = PendingProduct::whereIn('id', $this->selectedIds)
                ->update(['shop_ids' => json_encode([])]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Wyczyszczono sklepy dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk clear shops failed', ['error' => $e->getMessage()]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas czyszczenia sklepow',
            ]);
        }
    }

    /**
     * Open bulk compatibility modal for selected products
     */
    public function bulkEditCompatibility(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        // Dispatch event to open CompatibilityModal in bulk mode
        $this->dispatch('openBulkCompatibilityModal', productIds: $this->selectedIds);
    }

    /**
     * FIX 2025-12-10: Bulk clear category for specific level
     */
    public function bulkClearCategoryLevel(int $level): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Nie wybrano zadnych produktow',
            ]);
            return;
        }

        try {
            $count = 0;
            foreach ($this->selectedIds as $productId) {
                $this->setCategoryForLevel($productId, $level, null);
                $count++;
            }

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Wyczyszczono kategorie L{$level} dla {$count} produktow",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk clear category level failed', [
                'level' => $level,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas czyszczenia kategorii',
            ]);
        }
    }

    /**
     * FIX 2025-12-10: Get Wszystko category ID for bulk category creation
     */
    public function getWszystkoId(): ?int
    {
        $wszystko = \App\Models\Category::where('level', 1)->where('is_active', true)->first();
        return $wszystko?->id;
    }

    /**
     * FIX 2025-12-10: Create new category for bulk dropdown
     */
    public function createBulkCategory(int $level, int $parentId, string $name): ?array
    {
        try {
            $parent = \App\Models\Category::find($parentId);
            if (!$parent) {
                return null;
            }

            // Calculate level in DB (L3 = level 2 in DB)
            $dbLevel = $level - 1;

            $category = \App\Models\Category::create([
                'name' => trim($name),
                'parent_id' => $parentId,
                'level' => $dbLevel,
                'is_active' => true,
                'sort_order' => 999,
            ]);

            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        } catch (\Exception $e) {
            Log::error('Bulk create category failed', [
                'level' => $level,
                'parent_id' => $parentId,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
