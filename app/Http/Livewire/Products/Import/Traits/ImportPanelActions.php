<?php

namespace App\Http\Livewire\Products\Import\Traits;

use App\Models\PendingProduct;
use App\Services\Import\ProductPublicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ImportPanelActions - Trait dla akcji na pojedynczych produktach
 *
 * ETAP_06: Single item actions dla ProductImportPanel
 */
trait ImportPanelActions
{
    /**
     * Currently editing product ID
     */
    public ?int $editingProductId = null;

    /**
     * Inline edit field being edited
     */
    public ?string $editingField = null;

    /**
     * Temporary value for inline editing
     */
    public ?string $editValue = null;

    /**
     * Open SKU paste modal
     */
    public function openSKUPasteModal(): void
    {
        $this->openModal('sku-paste');
    }

    /**
     * Open CSV import modal
     */
    public function openCSVImportModal(): void
    {
        $this->openModal('csv-import');
    }

    /**
     * Start inline editing a field
     */
    public function startEditing(int $productId, string $field): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $this->editingProductId = $productId;
        $this->editingField = $field;
        $this->editValue = $product->{$field} ?? '';
    }

    /**
     * Save inline edit
     */
    public function saveInlineEdit(): void
    {
        if (!$this->editingProductId || !$this->editingField) {
            return;
        }

        $product = PendingProduct::find($this->editingProductId);
        if (!$product) {
            $this->cancelEditing();
            return;
        }

        try {
            $product->{$this->editingField} = $this->editValue;
            $product->save();

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zapisano zmiany',
            ]);
        } catch (\Exception $e) {
            Log::error('Import panel inline edit failed', [
                'product_id' => $this->editingProductId,
                'field' => $this->editingField,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad podczas zapisu: ' . $e->getMessage(),
            ]);
        }

        $this->cancelEditing();
    }

    /**
     * Cancel inline editing
     */
    public function cancelEditing(): void
    {
        $this->editingProductId = null;
        $this->editingField = null;
        $this->editValue = null;
    }

    /**
     * Update product type for single product
     */
    public function updateProductType(int $productId, ?int $typeId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $product->product_type_id = $typeId;
        $product->save();

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Zaktualizowano typ produktu',
        ]);
    }

    /**
     * Update manufacturer for single product via dropdown
     */
    public function updateManufacturer(int $productId, ?int $manufacturerId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $product->manufacturer_id = $manufacturerId ?: null;
        $product->save();

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => 'Zaktualizowano marke produktu',
        ]);
    }

    /**
     * Delete single pending product
     */
    public function deletePendingProduct(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        // Check if already published
        if ($product->published_at) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie mozna usunac opublikowanego produktu',
            ]);
            return;
        }

        $sku = $product->sku;
        $product->delete();

        // Remove from selection if selected
        $this->selectedIds = array_filter(
            $this->selectedIds,
            fn($id) => $id !== $productId
        );

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Usunieto produkt: {$sku}",
        ]);
    }

    /**
     * Publish single pending product
     */
    public function publishSingle(int $productId): void
    {
        $pendingProduct = PendingProduct::find($productId);
        if (!$pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Produkt nie istnieje',
            ]);
            return;
        }

        // Use publication service
        $service = app(ProductPublicationService::class);
        $result = $service->publishSingle($pendingProduct);

        if ($result['success']) {
            $product = $result['product'];

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => "Opublikowano produkt: {$pendingProduct->sku} (ID: {$product->id})",
            ]);

            // Redirect to product edit page
            $this->redirect(route('admin.products.edit', $product->id), navigate: true);
        } else {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad publikacji: ' . implode(', ', $result['errors']),
            ]);
        }
    }

    /**
     * Open product for full editing (redirect to edit page)
     */
    public function editProduct(int $productId): void
    {
        // TODO: Implement dedicated PendingProduct edit page or modal
        $this->dispatch('flash-message', [
            'type' => 'info',
            'message' => 'Szczegolowa edycja zostanie zaimplementowana w FAZIE 5',
        ]);
    }

    /**
     * Duplicate pending product
     */
    public function duplicateProduct(int $productId): void
    {
        $product = PendingProduct::find($productId);
        if (!$product) {
            return;
        }

        $newProduct = $product->replicate();
        $newProduct->sku = $product->sku . '-COPY';
        $newProduct->published_at = null;
        $newProduct->published_as_product_id = null;
        $newProduct->imported_at = now();
        $newProduct->imported_by = Auth::id();
        $newProduct->save();

        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Skopiowano produkt jako: {$newProduct->sku}",
        ]);
    }
}
