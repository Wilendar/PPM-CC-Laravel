# ETAP_07 FAZA 3: IMPORT REDESIGN - ProductList Integration

**Data:** 2025-10-03
**Priority:** 🔴 KRYTYCZNE - User Requirement Correction
**Status:** 🛠️ IN PROGRESS

---

## PROBLEM Z OBECNĄ IMPLEMENTACJĄ

**❌ BŁĄD:** Import UI został zaimplementowany w **ProductForm** (edycja produktu)

**User Feedback:**
> "Importuj z PrestaShop nie powinno być w edycji/dodawaniu produktu, tylko na Liście produktów"

**✅ POPRAWNE ROZWIĄZANIE:** Import UI w **ProductList** (lista wszystkich produktów)

---

## WYMAGANIA UŻYTKOWNIKA

### Lokalizacja: ProductList (lista produktów)

### Opcje Importu:

**1. Import wszystkie produkty**
- Import wszystkich produktów z wybranego sklepu PrestaShop
- Background job (bulk import)
- Progress tracking

**2. Import wszystkie z kategorii**
- Wybór kategorii PrestaShop
- Import wszystkich produktów z danej kategorii
- Możliwość wyboru hierarchii (z podkategoriami lub tylko główna)

**3. Import indywidualny (wybrane produkty)**
- Modal z listą produktów z PrestaShop
- Checkbox selection
- Import tylko zaznaczonych produktów

### Wspólne dla wszystkich opcji:
- **Dropdown wyboru sklepu PrestaShop** (obowiązkowy)
- Status import progress
- Conflict resolution (jeśli produkt już istnieje)
- Preview przed finalnym importem

---

## ARCHITEKTURA ROZWIĄZANIA

### Komponenty do utworzenia/modyfikacji:

```
ProductList.php (modify)
├── Properties
│   ├── $showImportModal (bool)
│   ├── $importShopId (int|null)
│   ├── $importMode (string: 'all', 'category', 'individual')
│   ├── $importCategoryId (int|null)
│   ├── $selectedProductsToImport (array)
│   └── $prestashopProducts (array)
│
├── Methods - Shop Selection
│   └── setImportShop(int $shopId)
│
├── Methods - Import All
│   ├── importAllProducts()
│   └── dispatchBulkImportJob()
│
├── Methods - Import by Category
│   ├── loadPrestaShopCategories()
│   ├── selectImportCategory(int $categoryId)
│   └── importFromCategory()
│
├── Methods - Import Individual
│   ├── loadPrestaShopProducts()
│   ├── toggleProductSelection(int $productId)
│   └── importSelectedProducts()
│
└── Methods - UI Control
    ├── openImportModal(string $mode)
    ├── closeImportModal()
    └── resetImportState()
```

```
product-list.blade.php (modify)
├── Header Actions
│   └── "📥 Importuj z PrestaShop" button
│
└── Import Modal
    ├── Shop Selector Dropdown
    ├── Mode Tabs (All / Category / Individual)
    ├── Mode: All
    │   └── Confirmation + Start Import
    ├── Mode: Category
    │   ├── Category Tree Selector
    │   └── Include Subcategories Checkbox
    └── Mode: Individual
        ├── Search Products
        ├── Product List with Checkboxes
        └── Import Selected Button
```

---

## IMPLEMENTACJA - SEKCJA 1: ProductList.php Backend

### 1.1 Properties

```php
// Import Modal State
public bool $showImportModal = false;
public ?int $importShopId = null;
public string $importMode = 'all'; // all, category, individual
public ?int $importCategoryId = null;
public array $selectedProductsToImport = [];
public array $prestashopProducts = [];
public array $prestashopCategories = [];
public string $importSearch = '';
public bool $importIncludeSubcategories = true;
```

### 1.2 Shop Selection

```php
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\PrestaShopImportService;

/**
 * Set shop for import
 */
public function setImportShop(int $shopId): void
{
    $this->importShopId = $shopId;

    // Auto-load data based on mode
    if ($this->importMode === 'individual') {
        $this->loadPrestaShopProducts();
    } elseif ($this->importMode === 'category') {
        $this->loadPrestaShopCategories();
    }
}
```

### 1.3 Import All Products

```php
use App\Jobs\PrestaShop\BulkImportProducts;

/**
 * Import all products from selected shop
 */
public function importAllProducts(): void
{
    if (!$this->importShopId) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Wybierz sklep PrestaShop',
        ]);
        return;
    }

    $shop = PrestaShopShop::find($this->importShopId);

    // Dispatch bulk import job
    BulkImportProducts::dispatch($shop, [
        'mode' => 'all',
        'user_id' => auth()->id(),
    ]);

    Log::info('Bulk import all products dispatched', [
        'shop_id' => $this->importShopId,
        'shop_name' => $shop->shop_name,
    ]);

    $this->dispatch('notify', [
        'type' => 'success',
        'message' => "Import wszystkich produktów z {$shop->shop_name} rozpoczęty w tle",
    ]);

    $this->closeImportModal();
}
```

### 1.4 Import by Category

```php
/**
 * Load PrestaShop categories
 */
public function loadPrestaShopCategories(): void
{
    if (!$this->importShopId) {
        return;
    }

    try {
        $shop = PrestaShopShop::find($this->importShopId);
        $importService = app(PrestaShopImportService::class);

        // Get category tree from PrestaShop
        $this->prestashopCategories = $importService->getCategoryTreeFromPrestaShop($shop);

        Log::info('Loaded PrestaShop categories', [
            'shop_id' => $this->importShopId,
            'count' => count($this->prestashopCategories),
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to load PrestaShop categories', [
            'shop_id' => $this->importShopId,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Nie udało się pobrać kategorii: ' . $e->getMessage(),
        ]);
    }
}

/**
 * Select category for import
 */
public function selectImportCategory(int $categoryId): void
{
    $this->importCategoryId = $categoryId;
}

/**
 * Import products from selected category
 */
public function importFromCategory(): void
{
    if (!$this->importShopId || !$this->importCategoryId) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Wybierz sklep i kategorię',
        ]);
        return;
    }

    $shop = PrestaShopShop::find($this->importShopId);

    // Dispatch bulk import job for category
    BulkImportProducts::dispatch($shop, [
        'mode' => 'category',
        'category_id' => $this->importCategoryId,
        'include_subcategories' => $this->importIncludeSubcategories,
        'user_id' => auth()->id(),
    ]);

    Log::info('Bulk import from category dispatched', [
        'shop_id' => $this->importShopId,
        'category_id' => $this->importCategoryId,
        'include_subcategories' => $this->importIncludeSubcategories,
    ]);

    $this->dispatch('notify', [
        'type' => 'success',
        'message' => "Import produktów z kategorii rozpoczęty w tle",
    ]);

    $this->closeImportModal();
}
```

### 1.5 Import Individual (Selected Products)

```php
/**
 * Load products from PrestaShop for individual selection
 */
public function loadPrestaShopProducts(): void
{
    if (!$this->importShopId) {
        return;
    }

    try {
        $shop = PrestaShopShop::find($this->importShopId);
        $client = app(PrestaShopClientFactory::class)->create($shop);

        $filters = [
            'display' => 'full',
            'limit' => 100,
        ];

        if (!empty($this->importSearch)) {
            $filters['filter[name]'] = "%{$this->importSearch}%";
        }

        $response = $client->getProducts($filters);

        // Extract products
        if (isset($response['products']) && is_array($response['products'])) {
            $this->prestashopProducts = $response['products'];
        } elseif (isset($response[0])) {
            $this->prestashopProducts = $response;
        } else {
            $this->prestashopProducts = [];
        }

        Log::info('Loaded PrestaShop products for individual import', [
            'shop_id' => $this->importShopId,
            'count' => count($this->prestashopProducts),
            'search' => $this->importSearch,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to load PrestaShop products', [
            'shop_id' => $this->importShopId,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Nie udało się pobrać produktów: ' . $e->getMessage(),
        ]);

        $this->prestashopProducts = [];
    }
}

/**
 * Toggle product selection for import
 */
public function toggleProductSelection(int $productId): void
{
    if (in_array($productId, $this->selectedProductsToImport, true)) {
        $this->selectedProductsToImport = array_values(
            array_filter($this->selectedProductsToImport, fn($id) => $id !== $productId)
        );
    } else {
        $this->selectedProductsToImport[] = $productId;
    }
}

/**
 * Import selected products
 */
public function importSelectedProducts(): void
{
    if (!$this->importShopId || empty($this->selectedProductsToImport)) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Wybierz produkty do importu',
        ]);
        return;
    }

    $shop = PrestaShopShop::find($this->importShopId);

    // Dispatch bulk import job for selected products
    BulkImportProducts::dispatch($shop, [
        'mode' => 'individual',
        'product_ids' => $this->selectedProductsToImport,
        'user_id' => auth()->id(),
    ]);

    Log::info('Individual products import dispatched', [
        'shop_id' => $this->importShopId,
        'product_count' => count($this->selectedProductsToImport),
        'product_ids' => $this->selectedProductsToImport,
    ]);

    $this->dispatch('notify', [
        'type' => 'success',
        'message' => "Import " . count($this->selectedProductsToImport) . " produktów rozpoczęty w tle",
    ]);

    $this->closeImportModal();
}
```

### 1.6 UI Control Methods

```php
/**
 * Open import modal with specific mode
 */
public function openImportModal(string $mode = 'all'): void
{
    $this->importMode = $mode;
    $this->showImportModal = true;
    $this->resetImportState();
}

/**
 * Close import modal
 */
public function closeImportModal(): void
{
    $this->showImportModal = false;
    $this->resetImportState();
}

/**
 * Reset import state
 */
public function resetImportState(): void
{
    $this->importShopId = null;
    $this->importCategoryId = null;
    $this->selectedProductsToImport = [];
    $this->prestashopProducts = [];
    $this->prestashopCategories = [];
    $this->importSearch = '';
    $this->importIncludeSubcategories = true;
}

/**
 * Watch import search changes
 */
public function updatedImportSearch(): void
{
    if ($this->importMode === 'individual') {
        $this->loadPrestaShopProducts();
    }
}

/**
 * Watch import mode changes
 */
public function updatedImportMode(): void
{
    $this->resetImportState();

    if ($this->importShopId) {
        $this->setImportShop($this->importShopId);
    }
}
```

---

## IMPLEMENTACJA - SEKCJA 2: UI Components (product-list.blade.php)

### 2.1 Import Button w Header Actions

**Lokalizacja:** Obok przycisku "Dodaj produkt" w headerze

```blade
{{-- Import from PrestaShop Button --}}
<button
    wire:click="openImportModal('all')"
    class="btn-secondary inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300"
    title="Importuj produkty z PrestaShop"
>
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
    </svg>
    Importuj z PrestaShop
</button>
```

### 2.2 Import Modal (pełna implementacja)

**Dodać na końcu pliku product-list.blade.php:**

```blade
{{-- Import from PrestaShop Modal --}}
@if($showImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="import-modal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black opacity-50 transition-opacity" wire:click="closeImportModal"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-5xl w-full p-6 shadow-2xl z-10">
                {{-- Header --}}
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Import produktów z PrestaShop
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Wybierz sklep i metodę importu produktów
                        </p>
                    </div>
                    <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        ✕
                    </button>
                </div>

                {{-- Shop Selector (MANDATORY) --}}
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <label class="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        1. Wybierz sklep PrestaShop <span class="text-red-500">*</span>
                    </label>
                    <select
                        wire:model.live="importShopId"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                        required
                    >
                        <option value="">-- Wybierz sklep --</option>
                        @foreach($availableShops as $shop)
                            <option value="{{ $shop->id }}">
                                {{ $shop->shop_name }} ({{ $shop->shop_url }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($importShopId)
                    {{-- Mode Tabs --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            2. Wybierz metodę importu
                        </label>
                        <div class="flex space-x-2 border-b border-gray-300 dark:border-gray-600">
                            <button
                                wire:click="$set('importMode', 'all')"
                                class="px-4 py-2 text-sm font-medium {{ $importMode === 'all' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                            >
                                📦 Wszystkie produkty
                            </button>
                            <button
                                wire:click="$set('importMode', 'category')"
                                class="px-4 py-2 text-sm font-medium {{ $importMode === 'category' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                            >
                                🗂️ Z kategorii
                            </button>
                            <button
                                wire:click="$set('importMode', 'individual')"
                                class="px-4 py-2 text-sm font-medium {{ $importMode === 'individual' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
                            >
                                ✅ Wybrane produkty
                            </button>
                        </div>
                    </div>

                    {{-- Mode: Import All --}}
                    @if($importMode === 'all')
                        <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto text-blue-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Import wszystkich produktów
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                    Zostaną zaimportowane wszystkie produkty z wybranego sklepu PrestaShop.
                                    Operacja zostanie wykonana w tle.
                                </p>
                                <button
                                    wire:click="importAllProducts"
                                    class="btn-primary inline-flex items-center px-6 py-3"
                                >
                                    🚀 Rozpocznij import wszystkich
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Mode: Import by Category --}}
                    @if($importMode === 'category')
                        <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            {{-- Loading categories --}}
                            <div wire:loading wire:target="loadPrestaShopCategories" class="text-center py-8">
                                <p class="text-gray-600 dark:text-gray-400">Ładowanie kategorii...</p>
                            </div>

                            {{-- Category selector --}}
                            <div wire:loading.remove wire:target="loadPrestaShopCategories">
                                @if(!empty($prestashopCategories))
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            Wybierz kategorię
                                        </label>
                                        <select
                                            wire:model.live="importCategoryId"
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                                        >
                                            <option value="">-- Wybierz kategorię --</option>
                                            @foreach($prestashopCategories as $category)
                                                <option value="{{ $category['id'] }}">
                                                    {{ str_repeat('--', $category['level'] ?? 0) }} {{ $category['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model.live="importIncludeSubcategories"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                Uwzględnij podkategorie
                                            </span>
                                        </label>
                                    </div>

                                    @if($importCategoryId)
                                        <button
                                            wire:click="importFromCategory"
                                            class="btn-primary w-full"
                                        >
                                            🚀 Importuj z kategorii
                                        </button>
                                    @endif
                                @else
                                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                                        Brak dostępnych kategorii
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Mode: Import Individual --}}
                    @if($importMode === 'individual')
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            {{-- Search --}}
                            <div class="mb-4">
                                <input
                                    wire:model.live.debounce.500ms="importSearch"
                                    type="text"
                                    placeholder="Szukaj produktów..."
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                                >
                            </div>

                            {{-- Loading --}}
                            <div wire:loading wire:target="loadPrestaShopProducts,importSearch" class="text-center py-8">
                                <p class="text-gray-600 dark:text-gray-400">Ładowanie produktów...</p>
                            </div>

                            {{-- Product list --}}
                            <div wire:loading.remove wire:target="loadPrestaShopProducts,importSearch">
                                @if(!empty($prestashopProducts))
                                    <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0">
                                                <tr>
                                                    <th class="px-4 py-3 text-left">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleSelectAll"
                                                            class="rounded"
                                                        >
                                                    </th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium">ID</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium">SKU</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium">Nazwa</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($prestashopProducts as $product)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                                        <td class="px-4 py-3">
                                                            <input
                                                                type="checkbox"
                                                                wire:click="toggleProductSelection({{ $product['id'] }})"
                                                                @checked(in_array($product['id'], $selectedProductsToImport))
                                                                class="rounded"
                                                            >
                                                        </td>
                                                        <td class="px-4 py-3 text-sm">#{{ $product['id'] }}</td>
                                                        <td class="px-4 py-3 text-sm font-mono">{{ $product['reference'] ?? 'N/A' }}</td>
                                                        <td class="px-4 py-3 text-sm">{{ $product['name'] ?? 'N/A' }}</td>
                                                        <td class="px-4 py-3 text-sm">
                                                            @if($this->productExistsInPPM($product['reference'] ?? null))
                                                                <span class="text-yellow-600">⚠️ Istnieje</span>
                                                            @else
                                                                <span class="text-green-600">✅ Nowy</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if(!empty($selectedProductsToImport))
                                        <div class="mt-4">
                                            <button
                                                wire:click="importSelectedProducts"
                                                class="btn-primary w-full"
                                            >
                                                🚀 Importuj zaznaczone ({{ count($selectedProductsToImport) }})
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-center text-gray-500 py-8">Brak produktów</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Footer --}}
                <div class="mt-6 flex justify-end">
                    <button wire:click="closeImportModal" class="btn-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

---

## DEPLOYMENT CHECKLIST

### Backend:
- [ ] Dodać properties do ProductList.php
- [ ] Dodać metody import logic do ProductList.php
- [ ] Dodać importy (PrestaShopClientFactory, PrestaShopImportService, BulkImportProducts)
- [ ] **USUNĄĆ** import logic z ProductForm.php (properties + metody)

### Frontend:
- [ ] Dodać Import button w header product-list.blade.php
- [ ] Dodać Import Modal w product-list.blade.php
- [ ] **USUNĄĆ** Import Modal z product-form.blade.php

### Jobs:
- [ ] Zweryfikować BulkImportProducts Job (czy obsługuje modes: all/category/individual)
- [ ] Dodać support dla category import w Job

### Testing:
- [ ] Test: Import button widoczny w ProductList
- [ ] Test: Modal opens z shop selector
- [ ] Test: Mode tabs działają (all/category/individual)
- [ ] Test: Import all dispatches Job
- [ ] Test: Import category loads categories + dispatches Job
- [ ] Test: Import individual shows products + selection works

---

**NASTĘPNY KROK:** Delegować implementację do livewire-specialist + frontend-specialist