# ARCHITECT REPORT: Bulk Category Operations dla Produktów

**Data:** 2025-10-15
**Agent:** architect
**Zadanie:** Zaplanuj szczegółową implementację Bulk Category Operations dla Produktów (ETAP_05 punkt 2.2.2.2)

---

## 📋 EXECUTIVE SUMMARY

Szczegółowy plan implementacji masowych operacji kategorii dla produktów w module PPM-CC-Laravel. Plan obejmuje 4 główne funkcjonalności: przypisywanie kategorii, usuwanie kategorii, przenoszenie między kategoriami oraz scalanie kategorii. Implementacja wykorzystuje istniejące komponenty (ProductList, ProductCategoryManager) zgodnie z zasadami enterprise Laravel + Livewire 3.x.

**Główne założenia:**
- Reuse istniejącej infrastruktury bulk operations z ProductList
- Rozszerzenie ProductCategoryManager o metody bulk
- Queue-based processing dla >50 produktów
- Multi-store support (operacje na default categories)
- Consistent UI patterns z bulkSendToShops()

---

## 🎯 KONTEKST I WYMAGANIA

### Istniejąca Infrastruktura

**✅ Już zaimplementowane:**
1. **ProductList.php** (2300 linii):
   - `selectedProducts[]` - array zaznaczonych produktów
   - `selectAll`, `selectingAllPages` - bulk selection infrastructure
   - `bulkActivate()`, `bulkDeactivate()` - przykłady bulk operations
   - `bulkSendToShops()` - modal pattern z progress tracking

2. **ProductCategoryManager.php** (492 linii):
   - `toggleCategory($categoryId)` - toggle pojedynczej kategorii
   - `setPrimaryCategory($categoryId)` - ustawianie primary
   - `syncCategories()` - zapis do bazy danych
   - `toggleDefaultCategory()`, `toggleShopCategory()` - multi-store logic

3. **CategoryTree.php**:
   - `bulkActivate()`, `bulkDelete()`, `bulkMove()` - bulk operations dla KATEGORII
   - Pattern dla queue jobs i progress tracking

**❌ Brakuje:**
- Bulk operations dla przypisywania/usuwania kategorii OD PRODUKTÓW
- Modals dla bulk category assignment
- Queue jobs dla bulk category operations na produktach
- UI components dla category merge functionality

### Multi-Store Considerations

**KRYTYCZNE:** Bulk operations muszą działać na **default categories** (shop_id=NULL w pivot table).

**Architektura per-shop categories:**
- `product_categories` pivot table zawiera kolumnę `shop_id`
- `shop_id=NULL` → Default categories (używane jeśli sklep nie ma override)
- `shop_id=X` → Per-shop override (różne kategorie per sklep)

**Decyzja architektury:**
Bulk operations operują TYLKO na default categories. Shop-specific categories zarządzane są indywidualnie w ProductForm.

---

## 📐 SZCZEGÓŁOWY IMPLEMENTATION PLAN

## ZADANIE 1: Bulk Assign Categories (2.2.2.2.1)

**Opis:** Przypisz jedną lub więcej kategorii do zaznaczonych produktów.

### 1.1 UI Component - Modal z Category Picker

**File:** `resources/views/livewire/products/listing/product-list.blade.php`

**Lokalizacja w pliku:** Po `showBulkDeleteModal` (linia ~1850)

**Nowy kod:**
```blade
{{-- Bulk Assign Categories Modal --}}
<div x-show="$wire.showBulkAssignCategoriesModal"
     x-cloak
     class="modal-root"
     style="display: none;">
    <div class="modal-overlay" @click="$wire.closeBulkAssignCategoriesModal()"></div>

    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Przypisz kategorie do produktów</h3>
            <button @click="$wire.closeBulkAssignCategoriesModal()" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <p class="mb-4 text-gray-600">
                Wybierz kategorie do przypisania dla <strong>{{ count($selectedProducts) }}</strong> zaznaczonych produktów.
            </p>

            {{-- Category Picker Tree --}}
            <div class="category-picker-container">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Wybierz kategorie (możesz wybrać wiele)
                </label>

                {{-- Reuse category picker z ProductForm --}}
                @foreach($categories as $category)
                    <div class="category-tree-item" wire:key="bulk-assign-cat-{{ $category->id }}">
                        <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="bulkAssignSelectedCategories"
                                   value="{{ $category->id }}"
                                   class="rounded border-gray-300">
                            <span>{{ $category->name }}</span>
                        </label>

                        {{-- Subcategories (recursive) --}}
                        @if($category->children->isNotEmpty())
                            <div class="ml-6">
                                @foreach($category->children as $child)
                                    {{-- Recursive subcategory rendering --}}
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Validation warning --}}
            @if(count($bulkAssignSelectedCategories) + $maxCategoriesPerProduct > 10)
                <div class="alert alert-warning mt-4">
                    ⚠️ Niektóre produkty mogą przekroczyć limit 10 kategorii po przypisaniu.
                </div>
            @endif

            {{-- Primary category option --}}
            <div class="mt-4">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" wire:model.live="bulkAssignSetAsPrimary" class="rounded">
                    <span class="text-sm text-gray-700">Ustaw pierwszą wybraną kategorię jako główną (primary)</span>
                </label>
            </div>
        </div>

        <div class="modal-footer">
            <button @click="$wire.closeBulkAssignCategoriesModal()" class="btn btn-secondary">
                Anuluj
            </button>
            <button wire:click="confirmBulkAssignCategories"
                    wire:loading.attr="disabled"
                    :disabled="!$wire.bulkAssignSelectedCategories.length"
                    class="btn btn-primary">
                <span wire:loading.remove>Przypisz kategorie</span>
                <span wire:loading>Przypisywanie...</span>
            </button>
        </div>
    </div>
</div>
```

### 1.2 Component Properties - ProductList.php

**File:** `app/Http/Livewire/Products/Listing/ProductList.php`

**Lokalizacja:** Po `showBulkDeleteModal` property (linia ~100)

**Nowe properties:**
```php
// Bulk Assign Categories Modal
public bool $showBulkAssignCategoriesModal = false;
public array $bulkAssignSelectedCategories = [];
public bool $bulkAssignSetAsPrimary = false;
```

### 1.3 Component Methods - ProductList.php

**Lokalizacja:** Po `confirmBulkDelete()` method (linia ~1887)

**Nowe metody:**
```php
/**
 * Open bulk assign categories modal
 */
public function openBulkAssignCategoriesModal(): void
{
    if (empty($this->selectedProducts)) {
        $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
        return;
    }

    $this->bulkAssignSelectedCategories = [];
    $this->bulkAssignSetAsPrimary = false;
    $this->showBulkAssignCategoriesModal = true;
}

/**
 * Close bulk assign categories modal
 */
public function closeBulkAssignCategoriesModal(): void
{
    $this->showBulkAssignCategoriesModal = false;
    $this->bulkAssignSelectedCategories = [];
    $this->bulkAssignSetAsPrimary = false;
}

/**
 * Confirm and execute bulk category assignment
 *
 * BUSINESS RULES:
 * - Max 10 categories per product
 * - Validates category existence
 * - Queue-based for >50 products
 * - Operates on DEFAULT categories (shop_id=NULL)
 */
public function confirmBulkAssignCategories(): void
{
    if (empty($this->selectedProducts)) {
        $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
        $this->closeBulkAssignCategoriesModal();
        return;
    }

    if (empty($this->bulkAssignSelectedCategories)) {
        $this->dispatch('error', message: 'Nie wybrano żadnej kategorii');
        return;
    }

    try {
        $productsCount = count($this->selectedProducts);
        $categoriesCount = count($this->bulkAssignSelectedCategories);

        // Validate categories exist
        $validCategories = Category::whereIn('id', $this->bulkAssignSelectedCategories)
            ->pluck('id')
            ->toArray();

        if (count($validCategories) !== $categoriesCount) {
            $this->dispatch('error', message: 'Niektóre wybrane kategorie nie istnieją');
            return;
        }

        // DECISION: Queue-based processing for >50 products
        if ($productsCount > 50) {
            // Dispatch queue job
            $jobId = (string) \Illuminate\Support\Str::uuid();

            \App\Jobs\Products\BulkAssignCategories::dispatch(
                $this->selectedProducts,
                $validCategories,
                $this->bulkAssignSetAsPrimary,
                $jobId
            );

            $this->dispatch('success', message: "Przypisywanie {$categoriesCount} kategorii do {$productsCount} produktów zostało zaplanowane. Sprawdź pasek postępu.");
        } else {
            // Synchronous processing for <=50 products
            DB::transaction(function () use ($validCategories, $productsCount, $categoriesCount) {
                $primaryCategoryId = $this->bulkAssignSetAsPrimary ? $validCategories[0] : null;

                foreach ($this->selectedProducts as $productId) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    // Get current default categories
                    $currentCategories = $product->categories()
                        ->wherePivotNull('shop_id')
                        ->pluck('categories.id')
                        ->toArray();

                    // Merge with new categories (avoid duplicates)
                    $mergedCategories = array_unique(array_merge($currentCategories, $validCategories));

                    // VALIDATION: Max 10 categories per product
                    if (count($mergedCategories) > 10) {
                        Log::warning('Product exceeds max categories limit', [
                            'product_id' => $productId,
                            'current_count' => count($currentCategories),
                            'new_count' => count($validCategories),
                            'total_count' => count($mergedCategories),
                        ]);

                        // Take first 10 categories
                        $mergedCategories = array_slice($mergedCategories, 0, 10);
                    }

                    // Prepare sync data with shop_id=NULL (default categories)
                    $syncData = [];
                    foreach ($mergedCategories as $index => $categoryId) {
                        $syncData[$categoryId] = [
                            'shop_id' => null, // Default categories
                            'is_primary' => ($primaryCategoryId && $categoryId === $primaryCategoryId),
                            'sort_order' => $index,
                        ];
                    }

                    // Sync categories (will merge with existing)
                    // CRITICAL: Only sync default categories (shop_id=NULL)
                    $product->categories()->syncWithoutDetaching($syncData);
                }
            });

            $this->dispatch('success', message: "Przypisano {$categoriesCount} kategorii do {$productsCount} produktów");
        }

        $this->resetSelection();
        $this->closeBulkAssignCategoriesModal();

    } catch (\Exception $e) {
        Log::error('Bulk assign categories failed', [
            'error' => $e->getMessage(),
            'products' => $this->selectedProducts,
            'categories' => $this->bulkAssignSelectedCategories,
        ]);

        $this->dispatch('error', message: 'Błąd podczas przypisywania kategorii: ' . $e->getMessage());
    }
}
```

### 1.4 Queue Job - BulkAssignCategories

**File:** `app/Jobs/Products/BulkAssignCategories.php` (NOWY PLIK)

**Kod:**
```php
<?php

namespace App\Jobs\Products;

use App\Models\Product;
use App\Models\Category;
use App\Models\JobProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BulkAssignCategories Job
 *
 * Assigns categories to multiple products in background
 * Uses JobProgress tracking for UI progress bar
 *
 * BUSINESS RULES:
 * - Max 10 categories per product
 * - Operates on DEFAULT categories (shop_id=NULL)
 * - Merges with existing categories (no overwrite)
 * - Optional primary category setting
 *
 * @package App\Jobs\Products
 * @version 1.0
 * @since ETAP_05 - Bulk Category Operations
 */
class BulkAssignCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;

    protected array $productIds;
    protected array $categoryIds;
    protected bool $setAsPrimary;
    protected string $jobId;

    /**
     * Create a new job instance
     */
    public function __construct(
        array $productIds,
        array $categoryIds,
        bool $setAsPrimary = false,
        string $jobId = null
    ) {
        $this->productIds = $productIds;
        $this->categoryIds = $categoryIds;
        $this->setAsPrimary = $setAsPrimary;
        $this->jobId = $jobId ?? (string) \Illuminate\Support\Str::uuid();
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $progressService = app(\App\Services\JobProgressService::class);

        // Initialize progress tracking
        $progress = $progressService->startJob(
            $this->jobId,
            'bulk_assign_categories',
            count($this->productIds)
        );

        $processedCount = 0;
        $errorCount = 0;
        $primaryCategoryId = $this->setAsPrimary ? $this->categoryIds[0] : null;

        try {
            // Process products in batches
            foreach (array_chunk($this->productIds, 20) as $batchProductIds) {
                DB::transaction(function () use ($batchProductIds, $primaryCategoryId, &$processedCount, &$errorCount, $progressService, $progress) {
                    foreach ($batchProductIds as $productId) {
                        try {
                            $product = Product::find($productId);
                            if (!$product) {
                                $errorCount++;
                                continue;
                            }

                            // Get current default categories
                            $currentCategories = $product->categories()
                                ->wherePivotNull('shop_id')
                                ->pluck('categories.id')
                                ->toArray();

                            // Merge with new categories
                            $mergedCategories = array_unique(array_merge($currentCategories, $this->categoryIds));

                            // VALIDATION: Max 10 categories
                            if (count($mergedCategories) > 10) {
                                $mergedCategories = array_slice($mergedCategories, 0, 10);
                            }

                            // Prepare sync data
                            $syncData = [];
                            foreach ($mergedCategories as $index => $categoryId) {
                                $syncData[$categoryId] = [
                                    'shop_id' => null, // Default categories
                                    'is_primary' => ($primaryCategoryId && $categoryId === $primaryCategoryId),
                                    'sort_order' => $index,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }

                            // Sync without detaching existing
                            $product->categories()->syncWithoutDetaching($syncData);

                            $processedCount++;

                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::error('Failed to assign categories to product', [
                                'product_id' => $productId,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        // Update progress
                        $progressService->updateProgress(
                            $progress->id,
                            $processedCount,
                            $errorCount
                        );
                    }
                });
            }

            // Mark as completed
            $progressService->completeJob($progress->id, $errorCount);

            Log::info('Bulk assign categories completed', [
                'job_id' => $this->jobId,
                'products_count' => count($this->productIds),
                'categories_count' => count($this->categoryIds),
                'processed' => $processedCount,
                'errors' => $errorCount,
            ]);

        } catch (\Exception $e) {
            $progressService->failJob($progress->id, $e->getMessage());

            Log::error('Bulk assign categories job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## ZADANIE 2: Bulk Remove Categories (2.2.2.2.2)

**Opis:** Usuń jedną lub więcej kategorii od zaznaczonych produktów.

### 2.1 UI Component - Modal

**File:** `resources/views/livewire/products/listing/product-list.blade.php`

**Kod:** (analogiczny do Bulk Assign, z różnicami)

```blade
{{-- Bulk Remove Categories Modal --}}
<div x-show="$wire.showBulkRemoveCategoriesModal" x-cloak class="modal-root">
    {{-- ... similar structure ... --}}

    <div class="modal-body">
        <p class="mb-4 text-gray-600">
            Wybierz kategorie do usunięcia z <strong>{{ count($selectedProducts) }}</strong> produktów.
        </p>

        {{-- Category selection (only show categories currently assigned to selected products) --}}
        @if(!empty($commonCategoriesInSelectedProducts))
            <div class="category-list">
                @foreach($commonCategoriesInSelectedProducts as $category)
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input type="checkbox"
                               wire:model.live="bulkRemoveSelectedCategories"
                               value="{{ $category->id }}">
                        <span class="ml-2">{{ $category->name }}</span>
                        <span class="ml-auto text-sm text-gray-500">({{ $category->products_count }} produktów)</span>
                    </label>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">Zaznaczone produkty nie mają wspólnych kategorii.</p>
        @endif

        {{-- WARNING: Cannot remove primary category --}}
        <div class="alert alert-warning mt-4">
            ⚠️ Nie można usunąć kategorii głównej (primary) bez zastąpienia jej inną.
        </div>
    </div>

    {{-- ... footer buttons ... --}}
</div>
```

### 2.2 Component Properties & Methods

**Properties:**
```php
public bool $showBulkRemoveCategoriesModal = false;
public array $bulkRemoveSelectedCategories = [];
public array $commonCategoriesInSelectedProducts = [];
```

**Methods:**
```php
public function openBulkRemoveCategoriesModal(): void
{
    if (empty($this->selectedProducts)) {
        $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
        return;
    }

    // Find common categories across selected products
    $this->loadCommonCategories();

    $this->bulkRemoveSelectedCategories = [];
    $this->showBulkRemoveCategoriesModal = true;
}

/**
 * Load categories that are common to selected products
 */
private function loadCommonCategories(): void
{
    // Get all categories from first product
    $firstProduct = Product::find($this->selectedProducts[0]);
    if (!$firstProduct) {
        $this->commonCategoriesInSelectedProducts = [];
        return;
    }

    $categoriesWithCounts = Category::whereHas('products', function($q) {
            $q->whereIn('products.id', $this->selectedProducts)
              ->wherePivotNull('shop_id'); // Only default categories
        })
        ->withCount(['products' => function($q) {
            $q->whereIn('products.id', $this->selectedProducts)
              ->wherePivotNull('shop_id');
        }])
        ->get();

    $this->commonCategoriesInSelectedProducts = $categoriesWithCounts->toArray();
}

public function confirmBulkRemoveCategories(): void
{
    if (empty($this->selectedProducts) || empty($this->bulkRemoveSelectedCategories)) {
        $this->dispatch('error', message: 'Wybierz produkty i kategorie do usunięcia');
        return;
    }

    try {
        $productsCount = count($this->selectedProducts);

        // Check if removing primary categories
        $removingPrimaryCategories = $this->checkIfRemovingPrimaryCategories();

        if ($removingPrimaryCategories && !$this->bulkRemoveConfirmPrimary) {
            $this->dispatch('warning', message: 'Usuwasz kategorie główne. Potwierdź operację.');
            $this->bulkRemoveConfirmPrimary = true;
            return;
        }

        if ($productsCount > 50) {
            // Queue job
            $jobId = (string) \Illuminate\Support\Str::uuid();

            \App\Jobs\Products\BulkRemoveCategories::dispatch(
                $this->selectedProducts,
                $this->bulkRemoveSelectedCategories,
                $jobId
            );

            $this->dispatch('success', message: 'Usuwanie kategorii zostało zaplanowane');
        } else {
            // Synchronous
            DB::transaction(function () {
                foreach ($this->selectedProducts as $productId) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    // Detach selected categories (only default, shop_id=NULL)
                    $product->categories()
                        ->wherePivotNull('shop_id')
                        ->whereIn('category_id', $this->bulkRemoveSelectedCategories)
                        ->detach($this->bulkRemoveSelectedCategories);

                    // BUSINESS RULE: If primary category was removed, set new primary
                    $remainingCategories = $product->categories()
                        ->wherePivotNull('shop_id')
                        ->get();

                    if ($remainingCategories->isNotEmpty()) {
                        $hasPrimary = $remainingCategories->where('pivot.is_primary', true)->isNotEmpty();

                        if (!$hasPrimary) {
                            // Set first remaining category as primary
                            $firstCategory = $remainingCategories->first();
                            DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->where('category_id', $firstCategory->id)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => true]);
                        }
                    }
                }
            });

            $this->dispatch('success', message: "Usunięto kategorie z {$productsCount} produktów");
        }

        $this->resetSelection();
        $this->closeBulkRemoveCategoriesModal();

    } catch (\Exception $e) {
        Log::error('Bulk remove categories failed', ['error' => $e->getMessage()]);
        $this->dispatch('error', message: 'Błąd: ' . $e->getMessage());
    }
}

/**
 * Check if any selected categories are primary for selected products
 */
private function checkIfRemovingPrimaryCategories(): bool
{
    return DB::table('product_categories')
        ->whereIn('product_id', $this->selectedProducts)
        ->whereIn('category_id', $this->bulkRemoveSelectedCategories)
        ->whereNull('shop_id')
        ->where('is_primary', true)
        ->exists();
}
```

### 2.3 Queue Job - BulkRemoveCategories

**File:** `app/Jobs/Products/BulkRemoveCategories.php` (NOWY PLIK)

**Struktura:** Analogiczna do BulkAssignCategories, z logika detach() zamiast syncWithoutDetaching()

---

## ZADANIE 3: Bulk Move Products Between Categories (2.2.2.2.3)

**Opis:** Zamień kategorie produktów - usuń stare, dodaj nowe (move operation).

### 3.1 UI Component - Modal z From/To Selection

**File:** `resources/views/livewire/products/listing/product-list.blade.php`

```blade
{{-- Bulk Move Categories Modal --}}
<div x-show="$wire.showBulkMoveCategoriesModal" x-cloak class="modal-root">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Przenieś produkty między kategoriami</h3>
        </div>

        <div class="modal-body">
            <p class="mb-4">
                Przenieś <strong>{{ count($selectedProducts) }}</strong> produktów z jednej kategorii do drugiej.
            </p>

            {{-- FROM Category Selection --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Z kategorii (usuń):</label>
                <select wire:model.live="bulkMoveFromCategory" class="form-select">
                    <option value="">-- Wybierz kategorię źródłową --</option>
                    @foreach($commonCategoriesInSelectedProducts as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }} ({{ $category->products_count }} produktów)
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- TO Category Selection --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Do kategorii (dodaj):</label>
                <select wire:model.live="bulkMoveToCategory" class="form-select">
                    <option value="">-- Wybierz kategorię docelową --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Operation Mode --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Tryb operacji:</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" wire:model.live="bulkMoveMode" value="replace" checked>
                        <span class="ml-2">Zamień kategorię (usuń FROM, dodaj TO)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" wire:model.live="bulkMoveMode" value="add_keep">
                        <span class="ml-2">Dodaj TO i zostaw FROM</span>
                    </label>
                </div>
            </div>

            {{-- Preview --}}
            @if($bulkMoveFromCategory && $bulkMoveToCategory)
                <div class="preview-box bg-blue-50 p-4 rounded">
                    <strong>Podgląd operacji:</strong>
                    <ul class="mt-2 text-sm">
                        <li>✓ {{ count($selectedProducts) }} produktów zostanie zaktualizowanych</li>
                        @if($bulkMoveMode === 'replace')
                            <li>✓ Kategoria "{{ $fromCategoryName }}" zostanie usunięta</li>
                        @endif
                        <li>✓ Kategoria "{{ $toCategoryName }}" zostanie dodana</li>
                    </ul>
                </div>
            @endif
        </div>

        <div class="modal-footer">
            <button @click="$wire.closeBulkMoveCategoriesModal()" class="btn btn-secondary">Anuluj</button>
            <button wire:click="confirmBulkMoveCategories"
                    :disabled="!$wire.bulkMoveFromCategory || !$wire.bulkMoveToCategory"
                    class="btn btn-primary">
                Przenieś produkty
            </button>
        </div>
    </div>
</div>
```

### 3.2 Component Logic

**Properties:**
```php
public bool $showBulkMoveCategoriesModal = false;
public ?int $bulkMoveFromCategory = null;
public ?int $bulkMoveToCategory = null;
public string $bulkMoveMode = 'replace'; // replace, add_keep
```

**Methods:**
```php
public function confirmBulkMoveCategories(): void
{
    if (!$this->bulkMoveFromCategory || !$this->bulkMoveToCategory) {
        $this->dispatch('error', message: 'Wybierz kategorie źródłową i docelową');
        return;
    }

    if ($this->bulkMoveFromCategory === $this->bulkMoveToCategory) {
        $this->dispatch('error', message: 'Kategorie źródłowa i docelowa muszą być różne');
        return;
    }

    try {
        $productsCount = count($this->selectedProducts);

        if ($productsCount > 50) {
            // Queue job
            \App\Jobs\Products\BulkMoveCategories::dispatch(
                $this->selectedProducts,
                $this->bulkMoveFromCategory,
                $this->bulkMoveToCategory,
                $this->bulkMoveMode,
                (string) \Illuminate\Support\Str::uuid()
            );

            $this->dispatch('success', message: 'Przenoszenie produktów zaplanowane');
        } else {
            // Synchronous
            DB::transaction(function () {
                foreach ($this->selectedProducts as $productId) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    // Check if product has FROM category
                    $hasFromCategory = $product->categories()
                        ->wherePivotNull('shop_id')
                        ->where('category_id', $this->bulkMoveFromCategory)
                        ->exists();

                    if (!$hasFromCategory) {
                        // Skip products that don't have FROM category
                        continue;
                    }

                    if ($this->bulkMoveMode === 'replace') {
                        // Remove FROM category
                        $product->categories()
                            ->wherePivotNull('shop_id')
                            ->detach($this->bulkMoveFromCategory);
                    }

                    // Add TO category (if not already present)
                    $hasToCategory = $product->categories()
                        ->wherePivotNull('shop_id')
                        ->where('category_id', $this->bulkMoveToCategory)
                        ->exists();

                    if (!$hasToCategory) {
                        // Get current max sort_order
                        $maxSortOrder = DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->whereNull('shop_id')
                            ->max('sort_order') ?? -1;

                        $product->categories()->attach($this->bulkMoveToCategory, [
                            'shop_id' => null,
                            'is_primary' => false,
                            'sort_order' => $maxSortOrder + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

            $mode = $this->bulkMoveMode === 'replace' ? 'Przeniesiono' : 'Dodano kategorię dla';
            $this->dispatch('success', message: "{$mode} {$productsCount} produktów");
        }

        $this->resetSelection();
        $this->closeBulkMoveCategoriesModal();

    } catch (\Exception $e) {
        Log::error('Bulk move categories failed', ['error' => $e->getMessage()]);
        $this->dispatch('error', message: 'Błąd: ' . $e->getMessage());
    }
}
```

---

## ZADANIE 4: Category Merge Functionality (2.2.2.2.4)

**Opis:** Scal dwie kategorie - wszystkie produkty z kategorii A przeniesione do B, kategoria A usunięta.

### 4.1 Dedicated Component - CategoryMerge

**DECYZJA ARCHITEKTURY:** Category Merge to zbyt złożona operacja dla ProductList. Powinna być w CategoryTree component lub dedykowanym CategoryMerge component.

**Rekomendacja:** Zaimplementować jako część CategoryTree.php (już ma bulk operations).

**File:** `app/Http/Livewire/Products/Categories/CategoryTree.php`

**Nowe metody:**
```php
/**
 * Open category merge modal
 */
public function openCategoryMergeModal(int $sourceCategoryId): void
{
    $this->sourceCategoryForMerge = $sourceCategoryId;
    $this->targetCategoryForMerge = null;
    $this->showCategoryMergeModal = true;
}

/**
 * Execute category merge
 *
 * BUSINESS RULES:
 * - All products from SOURCE moved to TARGET
 * - SOURCE category deleted after merge
 * - Cannot merge into own subcategory (prevents loops)
 * - Shop-specific categories also merged
 */
public function confirmCategoryMerge(): void
{
    if (!$this->sourceCategoryForMerge || !$this->targetCategoryForMerge) {
        $this->dispatch('error', message: 'Wybierz kategorie źródłową i docelową');
        return;
    }

    if ($this->sourceCategoryForMerge === $this->targetCategoryForMerge) {
        $this->dispatch('error', message: 'Nie można scalić kategorii ze sobą samą');
        return;
    }

    try {
        $sourceCategory = Category::find($this->sourceCategoryForMerge);
        $targetCategory = Category::find($this->targetCategoryForMerge);

        if (!$sourceCategory || !$targetCategory) {
            $this->dispatch('error', message: 'Kategoria nie została znaleziona');
            return;
        }

        // VALIDATION: Cannot merge into own subcategory
        if ($targetCategory->isDescendantOf($sourceCategory)) {
            $this->dispatch('error', message: 'Nie można scalić kategorii do jej podkategorii');
            return;
        }

        DB::transaction(function () use ($sourceCategory, $targetCategory) {
            // 1. Move all products from SOURCE to TARGET (default categories)
            $productsToMove = $sourceCategory->products()
                ->wherePivotNull('shop_id')
                ->get();

            foreach ($productsToMove as $product) {
                // Detach from source
                $product->categories()
                    ->wherePivotNull('shop_id')
                    ->detach($sourceCategory->id);

                // Attach to target (if not already there)
                $hasTargetCategory = $product->categories()
                    ->wherePivotNull('shop_id')
                    ->where('category_id', $targetCategory->id)
                    ->exists();

                if (!$hasTargetCategory) {
                    $maxSortOrder = DB::table('product_categories')
                        ->where('product_id', $product->id)
                        ->whereNull('shop_id')
                        ->max('sort_order') ?? -1;

                    $product->categories()->attach($targetCategory->id, [
                        'shop_id' => null,
                        'is_primary' => false,
                        'sort_order' => $maxSortOrder + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 2. Move shop-specific category mappings
            DB::table('product_categories')
                ->where('category_id', $sourceCategory->id)
                ->whereNotNull('shop_id')
                ->update(['category_id' => $targetCategory->id]);

            // 3. Delete SOURCE category
            $sourceCategory->delete();

            Log::info('Category merge completed', [
                'source_id' => $sourceCategory->id,
                'source_name' => $sourceCategory->name,
                'target_id' => $targetCategory->id,
                'target_name' => $targetCategory->name,
                'products_moved' => $productsToMove->count(),
            ]);
        });

        $this->dispatch('success', message: "Kategoria \"{$sourceCategory->name}\" została scalona z \"{$targetCategory->name}\"");
        $this->closeCategoryMergeModal();

        // Refresh category tree
        $this->loadCategories();

    } catch (\Exception $e) {
        Log::error('Category merge failed', ['error' => $e->getMessage()]);
        $this->dispatch('error', message: 'Błąd scalania kategorii: ' . $e->getMessage());
    }
}
```

---

## 📊 FILE MODIFICATIONS SUMMARY

### Files to Modify:

1. **app/Http/Livewire/Products/Listing/ProductList.php**
   - Dodaj 12 nowych properties
   - Dodaj 10 nowych methods
   - Rozszerzenie o ~400 linii kodu
   - **TOTAL AFTER:** ~2700 linii

2. **resources/views/livewire/products/listing/product-list.blade.php**
   - Dodaj 3 nowe modals (Assign, Remove, Move)
   - Rozszerzenie o ~300 linii HTML/Blade
   - **TOTAL AFTER:** ~2400 linii

3. **app/Http/Livewire/Products/Categories/CategoryTree.php**
   - Dodaj Category Merge functionality
   - Dodaj 5 nowych methods
   - Rozszerzenie o ~150 linii
   - **TOTAL AFTER:** ~900 linii

### Files to Create:

4. **app/Jobs/Products/BulkAssignCategories.php** (NOWY)
   - ~180 linii
   - Queue job dla bulk assign

5. **app/Jobs/Products/BulkRemoveCategories.php** (NOWY)
   - ~180 linii
   - Queue job dla bulk remove

6. **app/Jobs/Products/BulkMoveCategories.php** (NOWY)
   - ~200 linii
   - Queue job dla bulk move

---

## 🧪 TESTING STRATEGY

### Unit Tests

**File:** `tests/Unit/Jobs/BulkCategoryOperationsTest.php` (NOWY)

**Coverage:**
- BulkAssignCategories job logic
- BulkRemoveCategories job logic
- BulkMoveCategories job logic
- Validation rules (max 10 categories)
- Primary category handling
- Multi-store isolation (shop_id=NULL)

### Feature Tests

**File:** `tests/Feature/ProductListBulkCategoriesTest.php` (NOWY)

**Coverage:**
- Bulk assign categories workflow
- Bulk remove categories workflow
- Bulk move categories workflow
- Category merge functionality
- Edge cases (empty selection, invalid categories)
- Permission checks (Admin/Manager only)

### Manual Testing Checklist

**Bulk Assign:**
- [ ] Select 5 products → Assign 2 categories → Verify assigned
- [ ] Select 50+ products → Queue job dispatched → Progress bar visible
- [ ] Try to assign >10 categories → Validation warning shown
- [ ] Assign with "Set as primary" → First category is primary

**Bulk Remove:**
- [ ] Select products with common categories → Remove 1 category → Verify removed
- [ ] Try to remove primary category → Warning shown + replacement logic
- [ ] Remove all categories → At least one remains or handled gracefully

**Bulk Move:**
- [ ] Move products from Cat A to Cat B → Verify moved
- [ ] Move with "add_keep" mode → Both categories present
- [ ] Move products without FROM category → Skipped gracefully

**Category Merge:**
- [ ] Merge Cat A into Cat B → All products moved → Cat A deleted
- [ ] Try to merge into subcategory → Validation prevents
- [ ] Merge with shop-specific categories → All mappings updated

---

## ⏱️ IMPLEMENTATION TIMELINE

**Szacowany czas:** 12-16 godzin

**Breakdown:**
- **Zadanie 1 (Bulk Assign):** 4-5h
  - UI modal: 1h
  - Component logic: 1.5h
  - Queue job: 1.5h
  - Testing: 1h

- **Zadanie 2 (Bulk Remove):** 3-4h
  - UI modal: 0.5h
  - Component logic: 1.5h
  - Queue job: 1h
  - Testing: 1h

- **Zadanie 3 (Bulk Move):** 3-4h
  - UI modal: 1h
  - Component logic: 1.5h
  - Queue job: 1h
  - Testing: 0.5h

- **Zadanie 4 (Category Merge):** 2-3h
  - UI modal: 0.5h
  - Component logic: 1h
  - Testing: 0.5h

---

## 🚨 POTENCJALNE PROBLEMY I ROZWIĄZANIA

### Problem 1: Performance przy >1000 produktów

**Objawy:** Timeout podczas synchronous bulk operations

**Rozwiązanie:**
- Obniżyć threshold queue processing z 50 do 20 produktów
- Zwiększyć batch size w queue job (20 → 50)
- Dodać chunking w query (whereIn → whereIntegerInRaw)

### Problem 2: Race condition przy primary category

**Objawy:** Produkt bez primary category po bulk remove

**Rozwiązanie:**
- Database transaction wrapper
- Re-check primary category after detach
- Auto-assign first remaining category as primary

### Problem 3: Category merge z circular references

**Objawy:** Merge kategorii do swojej podkategorii powoduje błędy

**Rozwiązanie:**
- Validation: `$targetCategory->isDescendantOf($sourceCategory)`
- Prevent merge if target is child/grandchild of source
- UI warning przed confirmation

### Problem 4: Shop-specific categories mieszają się z default

**Objawy:** Bulk operations modyfikują per-shop categories

**Rozwiązanie:**
- **KRYTYCZNE:** Zawsze używać `wherePivotNull('shop_id')` w queries
- Dokumentacja w kodzie: "ONLY default categories"
- Unit tests dla multi-store isolation

---

## 📋 ETAP_05 PLAN UPDATE

**Po implementacji zaktualizuj:**

**File:** `Plan_Projektu/ETAP_05_Produkty.md`

**Sekcja:** 2.2.2.2 Bulk Category Operations (linia ~475)

**Zmiana:**
```markdown
- ✅ **2.2.2.2 Bulk Category Operations - 100% UKOŃCZONA**
  - ✅ 2.2.2.2.1 Bulk assign categories to products
    └──📁 PLIK: app/Http/Livewire/Products/Listing/ProductList.php (metody: openBulkAssignCategoriesModal, confirmBulkAssignCategories)
    └──📁 PLIK: app/Jobs/Products/BulkAssignCategories.php (queue job dla >50 produktów)
    └──📁 PLIK: resources/views/livewire/products/listing/product-list.blade.php (modal UI)

  - ✅ 2.2.2.2.2 Bulk remove categories from products
    └──📁 PLIK: app/Http/Livewire/Products/Listing/ProductList.php (metody: openBulkRemoveCategoriesModal, confirmBulkRemoveCategories)
    └──📁 PLIK: app/Jobs/Products/BulkRemoveCategories.php (queue job)
    └──📁 PLIK: resources/views/livewire/products/listing/product-list.blade.php (modal UI)

  - ✅ 2.2.2.2.3 Bulk move products between categories
    └──📁 PLIK: app/Http/Livewire/Products/Listing/ProductList.php (metody: openBulkMoveCategoriesModal, confirmBulkMoveCategories)
    └──📁 PLIK: app/Jobs/Products/BulkMoveCategories.php (queue job)
    └──📁 PLIK: resources/views/livewire/products/listing/product-list.blade.php (modal UI z FROM/TO selection)

  - ✅ 2.2.2.2.4 Category merge functionality
    └──📁 PLIK: app/Http/Livewire/Products/Categories/CategoryTree.php (metody: openCategoryMergeModal, confirmCategoryMerge)
    └──📁 PLIK: resources/views/livewire/products/categories/category-tree.blade.php (modal UI)
    └──📁 ENHANCEMENT: Circular reference prevention (isDescendantOf validation)
```

---

## ✅ CRITERIA AKCEPTACJI

Feature uznajemy za ukończone gdy:

1. **Bulk Assign Categories:**
   - ✅ Modal z category picker (multi-select) działa
   - ✅ Przypisywanie do <=50 produktów synchroniczne
   - ✅ Queue job dla >50 produktów z progress tracking
   - ✅ Validation: max 10 categories per product
   - ✅ Primary category setting optional
   - ✅ Multi-store: operacje tylko na default categories (shop_id=NULL)

2. **Bulk Remove Categories:**
   - ✅ Lista wspólnych kategorii dla zaznaczonych produktów
   - ✅ Warning przy usuwaniu primary category
   - ✅ Auto-reassignment primary category po remove
   - ✅ Queue job dla >50 produktów

3. **Bulk Move Categories:**
   - ✅ FROM/TO category selection
   - ✅ Dwa tryby: "replace" i "add_keep"
   - ✅ Preview operacji przed wykonaniem
   - ✅ Queue job dla >50 produktów

4. **Category Merge:**
   - ✅ Merge wszystkich produktów z A do B
   - ✅ Deletion kategorii A po merge
   - ✅ Validation: prevent circular references
   - ✅ Shop-specific categories również merged

5. **Quality & Performance:**
   - ✅ Unit tests coverage >85%
   - ✅ Feature tests dla wszystkich workflows
   - ✅ Performance: <3s dla 50 produktów (synchronous)
   - ✅ Queue jobs działają poprawnie z JobProgressService
   - ✅ Zgodność z Laravel 12.x + Livewire 3.x patterns

---

## 📖 DOCUMENTATION REFERENCES

**Context7 Patterns Used:**
- Laravel Queue Jobs: `/websites/laravel_12_x` (Bus::batch, ShouldQueue)
- Livewire Modals: `/livewire/livewire` (wire:model.live, wire:click patterns)
- Laravel Transactions: DB::transaction() dla data integrity
- Job Progress Tracking: JobProgressService integration

**PPM-CC-Laravel Specific:**
- Multi-Store Architecture: `_DOCS/SKU_ARCHITECTURE_GUIDE.md`
- Category System: `Plan_Projektu/ETAP_05_Produkty.md` sekcja 2.1
- Bulk Operations Pattern: `app/Http/Livewire/Products/Listing/ProductList.php` (bulkSendToShops reference)

---

## 🎯 NEXT STEPS

**Po zatwierdzeniu planu przez użytkownika:**

1. **FAZA IMPLEMENTACJI:**
   - Start: Bulk Assign Categories (najprostsze)
   - Następnie: Bulk Remove Categories
   - Następnie: Bulk Move Categories
   - Na końcu: Category Merge (najbardziej złożone)

2. **CODE REVIEW CHECKPOINTS:**
   - Po Bulk Assign: Review queue job pattern
   - Po Bulk Remove: Review primary category logic
   - Po Bulk Move: Review FROM/TO logic
   - Po Category Merge: Full feature review

3. **TESTING PHASE:**
   - Unit tests dla wszystkich job classes
   - Feature tests dla UI workflows
   - Manual testing z checklist
   - Performance testing (100, 500, 1000 produktów)

4. **DEPLOYMENT:**
   - Deploy na produkcję
   - Monitoring queue jobs
   - User acceptance testing
   - Documentation update

---

## 📊 METRYKI SUKCESU

- **Implementacja:** 12-16 godzin
- **Testing:** 4-6 godzin
- **Documentation:** 2 godziny
- **TOTAL:** 18-24 godziny

**Performance Targets:**
- Bulk operations (synchronous): <3s dla 50 produktów
- Queue job throughput: >100 produktów/min
- UI responsiveness: <200ms modal open/close
- Database queries: <10 queries per product (N+1 prevention)

---

**STATUS:** ✅ PLAN GOTOWY DO IMPLEMENTACJI

**Autor:** architect
**Reviewed by:** Context7 (Laravel 12.x + Livewire 3.x patterns)
**Date:** 2025-10-15
