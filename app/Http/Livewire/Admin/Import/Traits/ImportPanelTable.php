<?php

namespace App\Http\Livewire\Admin\Import\Traits;

use App\Models\PendingProduct;
use Illuminate\Support\Facades\Log;

/**
 * ImportPanelTable Trait
 *
 * ETAP_06 FAZA 2: Inline editing i table operations dla ProductImportPanel
 *
 * Responsibilities:
 * - Inline SKU editing
 * - Inline name editing
 * - Quick delete product
 * - Quick publish product (single)
 * - Completion helpers
 * - UI formatters
 *
 * @package App\Http\Livewire\Admin\Import\Traits
 */
trait ImportPanelTable
{
    /*
    |--------------------------------------------------------------------------
    | INLINE EDITING
    |--------------------------------------------------------------------------
    */

    /**
     * Update SKU inline
     */
    public function updateSKU(int $productId, string $sku): void
    {
        try {
            $product = PendingProduct::find($productId);

            if (!$product) {
                session()->flash('error', 'Produkt nie znaleziony');
                return;
            }

            // Validate SKU uniqueness
            if (PendingProduct::where('sku', $sku)
                ->where('id', '!=', $productId)
                ->exists()) {
                session()->flash('error', "SKU '{$sku}' juz istnieje");
                return;
            }

            $product->update(['sku' => $sku]);

            session()->flash('message', 'SKU zaktualizowany');
            session()->flash('message_type', 'success');
        } catch (\Exception $e) {
            Log::error('Update SKU failed', [
                'product_id' => $productId,
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas aktualizacji SKU');
        }
    }

    /**
     * Update name inline
     */
    public function updateName(int $productId, string $name): void
    {
        try {
            $product = PendingProduct::find($productId);

            if (!$product) {
                session()->flash('error', 'Produkt nie znaleziony');
                return;
            }

            $product->update(['name' => $name]);

            session()->flash('message', 'Nazwa zaktualizowana');
            session()->flash('message_type', 'success');
        } catch (\Exception $e) {
            Log::error('Update name failed', [
                'product_id' => $productId,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas aktualizacji nazwy');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | QUICK ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Quick delete single product
     */
    public function deleteProduct(int $productId): void
    {
        try {
            $product = PendingProduct::find($productId);

            if (!$product) {
                session()->flash('error', 'Produkt nie znaleziony');
                return;
            }

            $sku = $product->sku;
            $product->delete();

            session()->flash('message', "Produkt {$sku} usuniety");
            session()->flash('message_type', 'success');

            // Remove from selection if was selected
            $this->selectedIds = array_filter(
                $this->selectedIds,
                fn($id) => $id !== $productId
            );
        } catch (\Exception $e) {
            Log::error('Delete product failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas usuwania produktu');
        }
    }

    /**
     * Quick publish single product
     *
     * PLACEHOLDER dla FAZY 3: Full publication logic
     */
    public function publishProduct(int $productId): void
    {
        try {
            $product = PendingProduct::find($productId);

            if (!$product) {
                session()->flash('error', 'Produkt nie znaleziony');
                return;
            }

            // Check if ready
            if (!$product->canPublish()) {
                $missing = implode(', ', $product->getMissingRequiredFields());
                session()->flash('error', "Produkt niekompletny: {$missing}");
                return;
            }

            // PLACEHOLDER: Full publication logic (FAZA 3)
            // This will include media sync, variant creation, etc.

            session()->flash('warning', 'Funkcja publikacji bedzie dostepna w FAZY 3');
        } catch (\Exception $e) {
            Log::error('Publish product failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Blad podczas publikacji produktu');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get completion badge color
     */
    public function getCompletionColor(int $percentage): string
    {
        if ($percentage >= 100) return 'green';
        if ($percentage >= 80) return 'blue';
        if ($percentage >= 60) return 'yellow';
        if ($percentage >= 40) return 'orange';
        return 'red';
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(PendingProduct $product): string
    {
        if ($product->isPublished()) {
            return 'green';
        }

        if ($product->is_ready_for_publish) {
            return 'blue';
        }

        return 'gray';
    }

    /**
     * Format shop names for display
     */
    public function formatShops(array $shopIds): string
    {
        if (empty($shopIds)) {
            return '-';
        }

        if (count($shopIds) <= 2) {
            return implode(', ', $shopIds);
        }

        return count($shopIds) . ' sklepow';
    }
}
