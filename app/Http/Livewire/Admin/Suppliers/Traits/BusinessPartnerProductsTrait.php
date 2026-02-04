<?php

namespace App\Http\Livewire\Admin\Suppliers\Traits;

use App\Models\BusinessPartner;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

/**
 * BusinessPartnerProductsTrait - Zarzadzanie produktami przypisanymi do partnera
 *
 * Obsluguje:
 * - Lista produktow partnera z paginacja
 * - Przypisywanie/odpisywanie produktow (supplier_id, manufacturer_id, importer_id)
 * - Aktualizacja kodu dostawcy (supplier_code)
 * - Zakladka BRAK: produkty bez przypisania
 * - Dropdown listy partnerow do szybkiego przypisania
 *
 * Wymaga: $activeTab, $selectedEntityId z BusinessPartnerPanel
 *
 * @property string $activeTab
 * @property int|null $selectedEntityId
 */
trait BusinessPartnerProductsTrait
{
    // Wyszukiwanie produktow
    public string $productSearch = '';
    public int $productPerPage = 15;

    // Filtr dla zakladki BRAK
    public string $brakFilter = 'any'; // 'any', 'supplier', 'manufacturer', 'importer'

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Produkty przypisane do wybranego partnera (lub bez przypisania dla BRAK)
     */
    #[Computed]
    public function entityProducts(): LengthAwarePaginator
    {
        if ($this->activeTab === 'brak') {
            return $this->brakProducts;
        }

        if (! $this->selectedEntityId) {
            return Product::query()->where('id', 0)->paginate($this->productPerPage, ['*'], 'products');
        }

        $fkField = $this->getFkFieldForType($this->activeTab);
        if (! $fkField) {
            return Product::query()->where('id', 0)->paginate($this->productPerPage, ['*'], 'products');
        }

        $query = Product::where($fkField, $this->selectedEntityId);

        // Wyszukiwanie
        if ($this->productSearch !== '') {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%' . $this->productSearch . '%')
                    ->orWhere('sku', 'LIKE', '%' . $this->productSearch . '%')
                    ->orWhere('supplier_code', 'LIKE', '%' . $this->productSearch . '%');
            });
        }

        return $query
            ->with([
                'supplierRelation:id,name,type',
                'manufacturerRelation:id,name,type',
                'importerRelation:id,name,type',
                'erpData.erpConnection:id,erp_type,instance_name,label_color,label_icon',
                'shopData.shop:id,name,label_color,label_icon',
            ])
            ->orderBy('name')
            ->paginate($this->productPerPage, ['*'], 'products');
    }

    /**
     * Wszyscy dostawcy dla dropdownu
     */
    #[Computed]
    public function allSuppliers(): Collection
    {
        return BusinessPartner::where('type', 'supplier')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Wszyscy producenci dla dropdownu
     */
    #[Computed]
    public function allManufacturers(): Collection
    {
        return BusinessPartner::where('type', 'manufacturer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Wszyscy importerzy dla dropdownu
     */
    #[Computed]
    public function allImporters(): Collection
    {
        return BusinessPartner::where('type', 'importer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function brakStats(): array
    {
        return [
            'total' => Product::where(function ($q) {
                $q->whereNull('supplier_id')
                    ->orWhereNull('manufacturer_id')
                    ->orWhereNull('importer_id');
            })->count(),
            'no_supplier' => Product::whereNull('supplier_id')->count(),
            'no_manufacturer' => Product::whereNull('manufacturer_id')->count(),
            'no_importer' => Product::whereNull('importer_id')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT ASSIGNMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Zaladuj produkty partnera (reset paginacji)
     */
    public function loadEntityProducts(): void
    {
        $this->resetPage('products');
    }

    /**
     * Zmien przypisanie produktu do partnera
     *
     * @param int $productId ID produktu
     * @param string $field Pole FK: supplier_id|manufacturer_id|importer_id
     * @param int|null $value ID partnera lub null (odpiecie)
     */
    public function updateProductAssignment(int $productId, string $field, ?int $value): void
    {
        $allowedFields = ['supplier_id', 'manufacturer_id', 'importer_id'];
        if (! in_array($field, $allowedFields, true)) {
            $this->dispatch('flash-message', type: 'error', message: 'Niedozwolone pole: ' . $field);
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            $this->dispatch('flash-message', type: 'error', message: 'Produkt nie znaleziony');
            return;
        }

        // Walidacja: wartosc musi byc istniejacym partnerem lub null
        if ($value !== null) {
            $partner = BusinessPartner::find($value);
            if (! $partner) {
                $this->dispatch('flash-message', type: 'error', message: 'Partner nie znaleziony');
                return;
            }
        }

        try {
            $product->update([$field => $value]);

            $label = $this->getFieldLabel($field);
            $partnerName = $value ? BusinessPartner::find($value)?->name : 'brak';

            $this->dispatch('flash-message', type: 'success', message: "{$label} produktu '{$product->sku}' zmieniony na: {$partnerName}");

            Log::info('[BUSINESS_PARTNER] Product assignment updated', [
                'product_id' => $productId,
                'product_sku' => $product->sku,
                'field' => $field,
                'value' => $value,
            ]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Assignment update failed', [
                'product_id' => $productId,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad aktualizacji: ' . $e->getMessage());
        }
    }

    /**
     * Zaktualizuj kod dostawcy produktu
     */
    public function updateProductSupplierCode(int $productId, string $value): void
    {
        $product = Product::find($productId);
        if (! $product) {
            $this->dispatch('flash-message', type: 'error', message: 'Produkt nie znaleziony');
            return;
        }

        try {
            $product->update(['supplier_code' => $value ?: null]);

            $this->dispatch('flash-message', type: 'success', message: "Kod dostawcy produktu '{$product->sku}' zaktualizowany");

            Log::info('[BUSINESS_PARTNER] Supplier code updated', [
                'product_id' => $productId,
                'product_sku' => $product->sku,
                'supplier_code' => $value,
            ]);
        } catch (\Exception $e) {
            Log::error('[BUSINESS_PARTNER] Supplier code update failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad aktualizacji kodu: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Produkty bez przypisanego partnera (zakladka BRAK)
     */
    #[Computed]
    public function brakProducts(): LengthAwarePaginator
    {
        $query = Product::query();

        // Filtr: ktory typ partnera brakuje
        switch ($this->brakFilter) {
            case 'supplier':
                $query->whereNull('supplier_id');
                break;
            case 'manufacturer':
                $query->whereNull('manufacturer_id');
                break;
            case 'importer':
                $query->whereNull('importer_id');
                break;
            case 'any':
            default:
                $query->where(function ($q) {
                    $q->whereNull('supplier_id')
                        ->orWhereNull('manufacturer_id')
                        ->orWhereNull('importer_id');
                });
                break;
        }

        // Wyszukiwanie
        if ($this->productSearch !== '') {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%' . $this->productSearch . '%')
                    ->orWhere('sku', 'LIKE', '%' . $this->productSearch . '%')
                    ->orWhere('supplier_code', 'LIKE', '%' . $this->productSearch . '%');
            });
        }

        return $query
            ->with([
                'supplierRelation:id,name,type',
                'manufacturerRelation:id,name,type',
                'importerRelation:id,name,type',
                'erpData.erpConnection:id,erp_type,instance_name,label_color,label_icon',
                'shopData.shop:id,name,label_color,label_icon',
            ])
            ->orderBy('name')
            ->paginate($this->productPerPage, ['*'], 'products');
    }

    /**
     * Mapowanie typ partnera -> pole FK produktu
     */
    private function getFkFieldForType(string $type): ?string
    {
        return match ($type) {
            'supplier' => 'supplier_id',
            'manufacturer' => 'manufacturer_id',
            'importer' => 'importer_id',
            default => null,
        };
    }

    /**
     * Mapowanie pola FK -> etykieta
     */
    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'supplier_id' => 'Dostawca',
            'manufacturer_id' => 'Producent',
            'importer_id' => 'Importer',
            default => $field,
        };
    }

    /**
     * Mapowanie typ -> FK field (public, uzyty w blade)
     */
    public function getTypeToFkMapping(): array
    {
        return [
            'supplier' => 'supplier_id',
            'manufacturer' => 'manufacturer_id',
            'importer' => 'importer_id',
        ];
    }
}
