<?php

namespace App\Http\Livewire\Admin\Import\Traits;

use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\PublishHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ImportPanelBulkActions Trait
 *
 * ETAP_06 FAZA 2: Bulk operations dla ProductImportPanel
 *
 * Responsibilities:
 * - Selection management (selectAll/deselectAll)
 * - Bulk category assignment
 * - Bulk product type assignment
 * - Bulk shop assignment
 * - Bulk publish
 * - Bulk delete
 *
 * @package App\Http\Livewire\Admin\Import\Traits
 */
trait ImportPanelBulkActions
{
    /*
    |--------------------------------------------------------------------------
    | SELECTION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Select all pending products on current page
     */
    public function selectAllOnPage(): void
    {
        $ids = $this->pendingProducts->pluck('id')->toArray();
        $this->selectedIds = array_unique(array_merge($this->selectedIds, $ids));
        $this->selectAll = true;
    }

    /**
     * Deselect all products
     */
    public function deselectAll(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->showBulkActions = false;
    }

    /*
    |--------------------------------------------------------------------------
    | BULK CATEGORY ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Assign categories to selected products
     *
     * @param array $categoryIds Array of category IDs to assign
     */
    public function bulkSetCategory(array $categoryIds): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Nie wybrano produktow');
            return;
        }

        try {
            $updated = 0;

            foreach ($this->selectedIds as $productId) {
                $product = PendingProduct::find($productId);
                if ($product) {
                    $product->setCategories($categoryIds);
                    $updated++;
                }
            }

            session()->flash('message', "Zaktualizowano kategorie dla {$updated} produktow");
            session()->flash('message_type', 'success');

            // Deselect after action
            $this->deselectAll();
        } catch (\Exception $e) {
            Log::error('Bulk category assignment failed', [
                'selected_ids' => $this->selectedIds,
                'category_ids' => $categoryIds,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas przypisywania kategorii');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK PRODUCT TYPE ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Assign product type to selected products
     *
     * @param int $productTypeId Product type ID
     */
    public function bulkSetType(int $productTypeId): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Nie wybrano produktow');
            return;
        }

        try {
            $updated = PendingProduct::whereIn('id', $this->selectedIds)
                ->update(['product_type_id' => $productTypeId]);

            session()->flash('message', "Zaktualizowano typ dla {$updated} produktow");
            session()->flash('message_type', 'success');

            // Deselect after action
            $this->deselectAll();
        } catch (\Exception $e) {
            Log::error('Bulk type assignment failed', [
                'selected_ids' => $this->selectedIds,
                'product_type_id' => $productTypeId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas przypisywania typu');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK SHOP ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Assign shops to selected products
     *
     * @param array $shopIds Array of PrestaShop shop IDs
     */
    public function bulkSetShops(array $shopIds): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Nie wybrano produktow');
            return;
        }

        try {
            $updated = 0;

            foreach ($this->selectedIds as $productId) {
                $product = PendingProduct::find($productId);
                if ($product) {
                    $product->setShops($shopIds);
                    $updated++;
                }
            }

            session()->flash('message', "Zaktualizowano sklepy dla {$updated} produktow");
            session()->flash('message_type', 'success');

            // Deselect after action
            $this->deselectAll();
        } catch (\Exception $e) {
            Log::error('Bulk shops assignment failed', [
                'selected_ids' => $this->selectedIds,
                'shop_ids' => $shopIds,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas przypisywania sklepow');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK PUBLISH
    |--------------------------------------------------------------------------
    */

    /**
     * Publish selected pending products to main products table
     *
     * PLACEHOLDER dla FAZY 3: Full publication logic z media sync
     */
    public function publishSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Nie wybrano produktow');
            return;
        }

        try {
            $batchId = PublishHistory::generateBatchId();
            $published = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($this->selectedIds as $productId) {
                $pendingProduct = PendingProduct::find($productId);

                if (!$pendingProduct) {
                    $failed++;
                    continue;
                }

                // Check if ready for publish
                if (!$pendingProduct->canPublish()) {
                    $errors[] = "SKU {$pendingProduct->sku}: " . implode(', ', $pendingProduct->getMissingRequiredFields());
                    $failed++;
                    continue;
                }

                // PLACEHOLDER: Full publication logic (FAZA 3)
                // This will be expanded with media sync, variant creation, etc.
                // For now: Basic validation only

                $published++;
            }

            DB::commit();

            if ($published > 0) {
                session()->flash('message', "Opublikowano {$published} produktow");
                session()->flash('message_type', 'success');
            }

            if ($failed > 0) {
                session()->flash('warning', "Nie udalo sie opublikowac {$failed} produktow");
                if (!empty($errors)) {
                    session()->flash('errors', array_slice($errors, 0, 5)); // First 5 errors
                }
            }

            // Deselect after action
            $this->deselectAll();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk publish failed', [
                'selected_ids' => $this->selectedIds,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas publikacji produktow');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK DELETE
    |--------------------------------------------------------------------------
    */

    /**
     * Soft delete selected pending products
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Nie wybrano produktow');
            return;
        }

        try {
            $deleted = PendingProduct::whereIn('id', $this->selectedIds)->delete();

            session()->flash('message', "Usunieto {$deleted} produktow");
            session()->flash('message_type', 'success');

            // Deselect after action
            $this->deselectAll();
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', [
                'selected_ids' => $this->selectedIds,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas usuwania produktow');
        }
    }
}
