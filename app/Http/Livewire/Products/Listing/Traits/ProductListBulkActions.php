<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductListBulkActions Trait
 *
 * Manages bulk operations on selected products:
 * - Selection (toggle, select all, select all pages)
 * - Bulk activate/deactivate
 * - Bulk delete (with confirmation modal)
 * - Bulk export CSV
 * - Bulk send to shops
 *
 * Category operations are in ProductListBulkCategories trait.
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListBulkActions
{
    /*
    |--------------------------------------------------------------------------
    | BULK SELECTION PROPERTIES
    |--------------------------------------------------------------------------
    */

    public array $selectedProducts = [];
    public array $selectedVariants = [];
    public bool $selectAll = false;
    public bool $selectingAllPages = false;
    public bool $showBulkActions = false;

    // Quick Send to Shops
    public bool $showQuickSendModal = false;
    public array $selectedShopsForBulk = [];

    // Bulk Delete
    public bool $showBulkDeleteModal = false;

    /*
    |--------------------------------------------------------------------------
    | SELECTION METHODS
    |--------------------------------------------------------------------------
    */

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedProducts = $this->products->pluck('id')->toArray();
        } else {
            $this->resetSelection();
        }

        $this->selectingAllPages = false;
        $this->updateBulkActionsVisibility();
    }

    public function updatedSelectedProducts(): void
    {
        $this->selectAll = count($this->selectedProducts) === $this->products->count();
        $this->updateBulkActionsVisibility();
    }

    public function toggleSelection(int $productId): void
    {
        if (in_array($productId, $this->selectedProducts)) {
            $this->selectedProducts = array_diff($this->selectedProducts, [$productId]);
        } else {
            $this->selectedProducts[] = $productId;
        }

        $this->updatedSelectedProducts();
    }

    public function selectAllPages(): void
    {
        $this->selectedProducts = $this->buildProductQuery()
            ->pluck('id')
            ->toArray();

        $this->selectAll = true;
        $this->selectingAllPages = true;
        $this->updateBulkActionsVisibility();

        $this->dispatch('success', message: sprintf(
            'Zaznaczono wszystkie %d produktów pasujących do filtrów',
            count($this->selectedProducts)
        ));
    }

    public function deselectAllPages(): void
    {
        $this->selectedProducts = $this->products->pluck('id')->toArray();
        $this->selectAll = true;
        $this->selectingAllPages = false;
        $this->updateBulkActionsVisibility();
    }

    public function resetSelection(): void
    {
        $this->reset(['selectedProducts', 'selectAll', 'selectingAllPages']);
        $this->updateBulkActionsVisibility();
    }

    private function updateBulkActionsVisibility(): void
    {
        $this->showBulkActions = count($this->selectedProducts) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTIVATE/DEACTIVATE
    |--------------------------------------------------------------------------
    */

    public function bulkActivate(): void
    {
        $this->authorizeAction('update');

        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $count = Product::whereIn('id', $this->selectedProducts)
            ->update(['is_active' => true]);

        $this->dispatch('success', message: "Aktywowano {$count} " . ($count == 1 ? 'produkt' : 'produkty'));
        $this->resetSelection();
    }

    public function bulkDeactivate(): void
    {
        $this->authorizeAction('update');

        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $count = Product::whereIn('id', $this->selectedProducts)
            ->update(['is_active' => false]);

        $this->dispatch('success', message: "Dezaktywowano {$count} " . ($count == 1 ? 'produkt' : 'produkty'));
        $this->resetSelection();
    }

    /*
    |--------------------------------------------------------------------------
    | BULK DELETE
    |--------------------------------------------------------------------------
    */

    public function openBulkDeleteModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function confirmBulkDelete(): void
    {
        $this->authorizeAction('delete');

        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            $this->closeBulkDeleteModal();
            return;
        }

        try {
            $count = Product::whereIn('id', $this->selectedProducts)->count();
            Product::whereIn('id', $this->selectedProducts)->forceDelete();

            Log::info('Bulk delete completed', [
                'count' => $count,
                'product_ids' => $this->selectedProducts,
            ]);

            $this->dispatch('success', message: "Trwale usunięto {$count} " . ($count == 1 ? 'produkt' : ($count < 5 ? 'produkty' : 'produktów')));
            $this->resetSelection();
            $this->closeBulkDeleteModal();

        } catch (\Exception $e) {
            Log::error('Bulk delete failed', [
                'error' => $e->getMessage(),
                'products' => $this->selectedProducts,
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania produktów: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK EXPORT CSV
    |--------------------------------------------------------------------------
    */

    public function bulkExportCsv(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        try {
            $products = Product::whereIn('id', $this->selectedProducts)
                ->with(['categories', 'priceGroups'])
                ->orderBy('sku')
                ->get();

            $csv = "SKU;Nazwa;Kategoria główna;Status;Stan magazynowy;Cena detaliczna;Cena dealer;Utworzono;Aktualizacja\n";

            foreach ($products as $product) {
                $primaryCategory = $product->categories
                    ->where('pivot.is_primary', true)
                    ->where('pivot.shop_id', null)
                    ->first();

                $retailPrice = $product->priceGroups->where('code', 'detaliczna')->first();
                $dealerPrice = $product->priceGroups->where('code', 'dealer_standard')->first();

                $csv .= sprintf(
                    "%s;%s;%s;%s;%d;%s;%s;%s;%s\n",
                    $this->escapeCsv($product->sku),
                    $this->escapeCsv($product->name),
                    $this->escapeCsv($primaryCategory?->name ?? '-'),
                    $product->is_active ? 'Aktywny' : 'Nieaktywny',
                    $product->stock_quantity ?? 0,
                    $retailPrice ? number_format($retailPrice->pivot->price, 2, ',', '') : '-',
                    $dealerPrice ? number_format($dealerPrice->pivot->price, 2, ',', '') : '-',
                    $product->created_at->format('Y-m-d H:i'),
                    $product->updated_at->format('Y-m-d H:i')
                );
            }

            $filename = 'products_export_' . date('Y-m-d_His') . '.csv';

            $this->dispatch('download-csv', [
                'filename' => $filename,
                'content' => $csv
            ]);

            Log::info('ProductList: Bulk export CSV completed', [
                'count' => $products->count(),
                'filename' => $filename,
            ]);

            $this->dispatch('success', message: "Wyeksportowano {$products->count()} produktów do CSV");

        } catch (\Exception $e) {
            Log::error('ProductList: Bulk export CSV failed', [
                'error' => $e->getMessage(),
                'selected_products' => $this->selectedProducts
            ]);

            $this->dispatch('error', message: 'Błąd podczas eksportu CSV: ' . $e->getMessage());
        }
    }

    private function escapeCsv(string $value): string
    {
        $value = str_replace('"', '""', $value);

        if (strpos($value, ';') !== false || strpos($value, ',') !== false ||
            strpos($value, "\n") !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | QUICK SEND TO SHOPS
    |--------------------------------------------------------------------------
    */

    public function openQuickSendModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden produkt.');
            return;
        }

        $this->selectedShopsForBulk = [];
        $this->showQuickSendModal = true;
    }

    public function closeQuickSendModal(): void
    {
        $this->showQuickSendModal = false;
        $this->selectedShopsForBulk = [];
    }

    public function bulkSendToShops(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden produkt.');
            return;
        }

        if (empty($this->selectedShopsForBulk)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jeden sklep.');
            return;
        }

        try {
            DB::transaction(function () {
                $addedCount = 0;

                foreach ($this->selectedProducts as $productId) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    foreach ($this->selectedShopsForBulk as $shopId) {
                        $exists = ProductShopData::where('product_id', $productId)
                            ->where('shop_id', $shopId)
                            ->exists();

                        if (!$exists) {
                            ProductShopData::create([
                                'product_id' => $productId,
                                'shop_id' => $shopId,
                                'name' => $product->name,
                                'slug' => $product->slug,
                                'short_description' => $product->short_description,
                                'long_description' => $product->long_description,
                                'meta_title' => $product->meta_title,
                                'meta_description' => $product->meta_description,
                                'category_mappings' => [],
                                'attribute_mappings' => [],
                                'image_settings' => [],
                                'sync_status' => 'pending',
                                'is_published' => false,
                            ]);
                            $addedCount++;
                        }
                    }
                }

                $productsCount = count($this->selectedProducts);
                $shopsCount = count($this->selectedShopsForBulk);

                $this->dispatch('success', message: "Wysłano {$productsCount} produktów do {$shopsCount} sklepów. Dodano {$addedCount} nowych powiązań.");
            });

            $this->resetSelection();
            $this->closeQuickSendModal();

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Wystąpił błąd podczas wysyłania produktów do sklepów.');
        }
    }
}
