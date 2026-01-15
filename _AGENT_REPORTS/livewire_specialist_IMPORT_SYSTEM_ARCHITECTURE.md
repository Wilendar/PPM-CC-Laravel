# RAPORT: Architektura Komponentów Livewire - System Import PPM

**Agent:** livewire-specialist
**Data:** 2025-12-08
**Zadanie:** Projekt architektury komponentów Livewire dla systemu Import PPM z panelem produktów niekompletnych

---

## EXECUTIVE SUMMARY

Zaprojektowano 9 komponentów Livewire 3.x dla nowego systemu importu produktów do PPM, zgodnie z zasadami:
- **SKU-first architecture** (zgodnie z `_DOCS/SKU_ARCHITECTURE_GUIDE.md`)
- **UI/UX Standards PPM** (minimum 20px padding, high contrast, no hover transforms)
- **Enterprise patterns** (bulk operations, real-time updates, event-driven)
- **Performance optimization** (lazy loading, pagination, debouncing)

---

## 📋 KOMPONENTY LIVEWIRE - LISTA I ODPOWIEDZIALNOŚĆ

### 1. **ProductImportPanel** (główny komponent)
**Lokalizacja:** `app/Http/Livewire/Products/Import/ProductImportPanel.php`
**Odpowiedzialność:**
- Lista produktów w statusie "pending" (niekompletne)
- Paginacja (25 produktów per page)
- Sortowanie (SKU, nazwa, data dodania, priorytet)
- Filtrowanie (status, sklep docelowy, typ produktu)
- Bulk actions (checkbox selection + toolbar)
- Real-time updates przy edycji (wire:poll.10s conditional)

**Properties:**
```php
public array $pendingProducts = [];
public array $selectedIds = [];
public ?int $editingProductId = null;
public string $search = '';
public array $filters = [
    'status' => 'all',           // all, incomplete, ready, blocked
    'shop_id' => null,
    'product_type_id' => null,
];
public string $sortField = 'created_at';
public string $sortDirection = 'desc';
public int $perPage = 25;
```

**Methods:**
```php
public function mount(): void
public function render()
public function updatedSearch(): void              // Reset pagination
public function sortBy(string $field): void
public function toggleProductSelection(int $productId): void
public function toggleSelectAll(): void
public function editProduct(int $productId): void  // Navigate to edit mode
public function deleteProduct(int $productId): void
public function bulkSetCategory(array $categoryPath): void
public function bulkSetType(string $productTypeId): void
public function bulkSetShops(array $shopIds): void
public function bulkPublish(): void                // Dispatch jobs
public function refreshList(): void                // Manual refresh
```

**Events (listens):**
- `product-updated` → refreshList()
- `products-published` → refreshList()
- `import-completed` → refreshList()

**Events (dispatches):**
- `open-sku-paste-modal`
- `open-category-picker-modal`
- `open-bulk-publish-modal`

---

### 2. **SkuPasteInput** (modal komponent)
**Lokalizacja:** `app/Http/Livewire/Products/Import/SkuPasteInput.php`
**Odpowiedzialność:**
- Textarea do wklejania SKU lub SKU+Nazwa
- Parsowanie różnych separatorów (newline, semicolon, comma, tab)
- Preview parsowanych danych (table view)
- Walidacja (czy SKU już istnieje w bazie)
- Bulk create produktów w statusie "pending"

**Properties:**
```php
public string $pastedText = '';
public array $parsedData = [];
public array $validationResults = [];
public bool $isModalOpen = false;
public string $separator = 'auto';  // auto, newline, semicolon, comma, tab
```

**Methods:**
```php
public function mount(): void
public function openModal(): void
public function closeModal(): void
public function parseInput(): void                 // Parse on blur/button click
public function updatedPastedText(): void          // Auto-parse (debounced)
public function validateSkus(): void               // Check DB for duplicates
public function addToList(): void                  // Create pending products
protected function detectSeparator(string $text): string
protected function parseLine(string $line): array  // Returns ['sku' => '', 'name' => '']
```

**Parsowanie przykład:**
```
Input (newline separated):
JK25154D
ABC123 | Nazwa produktu
XYZ789; Inny produkt

Parsed:
[
    ['sku' => 'JK25154D', 'name' => '', 'exists' => true, 'status' => 'duplicate'],
    ['sku' => 'ABC123', 'name' => 'Nazwa produktu', 'exists' => false, 'status' => 'valid'],
    ['sku' => 'XYZ789', 'name' => 'Inny produkt', 'exists' => false, 'status' => 'valid'],
]
```

**Events (listens):**
- `open-sku-paste-modal` → openModal()

**Events (dispatches):**
- `products-added` → ProductImportPanel refreshes

---

### 3. **HierarchicalCategoryPicker** (KRYTYCZNY komponent)
**Lokalizacja:** `app/Http/Livewire/Products/Import/HierarchicalCategoryPicker.php`
**Odpowiedzialność:**
- Kaskadowe dropdowny L3→L4→L5→L6→L7
- Searchbar w każdym dropdown (live search)
- Możliwość zakończenia na dowolnym poziomie (przycisk ❌)
- Inteligentne sugestie na podstawie nazwy produktu (ML/fuzzy matching)
- Multi-select dla bulk operations

**Properties:**
```php
public ?int $productId = null;
public ?string $productName = null;
public array $selectedPath = [];              // [3 => 123, 4 => 456, 5 => 789]
public array $availableCategories = [];       // Per level
public array $suggestions = [];               // ML-based suggestions
public bool $isModalOpen = false;
public string $searchL3 = '';
public string $searchL4 = '';
public string $searchL5 = '';
public string $searchL6 = '';
public string $searchL7 = '';
public bool $isBulkMode = false;              // Single vs bulk selection
```

**Methods:**
```php
public function mount(?int $productId = null): void
public function openModal(): void
public function closeModal(): void
public function selectCategory(int $level, int $categoryId): void
public function clearLevel(int $level): void          // Wyczyść od poziomu X
public function resetSelection(): void
public function confirmSelection(): void              // Dispatch event
public function loadSuggestions(): void               // ML-based on product name
public function applyBulkSelection(array $productIds): void
protected function loadCategoriesForLevel(int $level): void
```

**Category Path Structure:**
```php
[
    3 => ['id' => 123, 'name' => 'Motorowery', 'slug' => 'motorowery'],
    4 => ['id' => 456, 'name' => 'Części silnika', 'slug' => 'czesci-silnika'],
    5 => ['id' => 789, 'name' => 'Tłoki', 'slug' => 'tloki'],
    // User może zakończyć tutaj (brak L6/L7)
]
```

**Events (listens):**
- `open-category-picker-modal` → openModal(productId)
- `open-bulk-category-picker` → openModal(null, bulk mode)

**Events (dispatches):**
- `categories-selected` → { productId, categoryPath }
- `bulk-categories-selected` → { productIds, categoryPath }

---

### 4. **ProductTypeSelector** (simple dropdown)
**Lokalizacja:** `app/Http/Livewire/Products/Import/ProductTypeSelector.php`
**Odpowiedzialność:**
- Dropdown z konfigurowalnymi typami produktów
- Typy: Część zamienna (1), Pojazd (2), Akcesoria (3), Odzież (4), Inne (5)
- Zmiana typu wpływa na wymagane pola (reactive validation)
- Bulk mode support

**Properties:**
```php
public ?int $productId = null;
public ?int $selectedTypeId = 1;              // Default: Część zamienna
public array $productTypes = [];
public bool $isBulkMode = false;
```

**Methods:**
```php
public function mount(?int $productId = null, ?int $typeId = 1): void
public function updatedSelectedTypeId(int $typeId): void
public function applyToProduct(): void                // Single product
public function applyBulkType(array $productIds): void
```

**Type Rules:**
```php
ProductType::CZESC_ZAMIENNA => [
    'requires' => ['vehicle_features', 'vehicle_compatibility'],
    'optional' => ['variants'],
],
ProductType::POJAZD => [
    'requires' => ['vehicle_features'],
    'optional' => ['variants', 'vehicle_compatibility'],
],
ProductType::AKCESORIA => [
    'requires' => [],
    'optional' => ['variants', 'vehicle_compatibility'],
],
```

**Events (dispatches):**
- `product-type-changed` → { productId, typeId }
- `bulk-type-changed` → { productIds, typeId }

---

### 5. **ImageUploadModal** (complex file handling)
**Lokalizacja:** `app/Http/Livewire/Products/Import/ImageUploadModal.php`
**Odpowiedzialność:**
- Multi-file drag&drop (Livewire temporary uploads)
- Preview uploaded images (thumbnail grid)
- Wybór zdjęcia głównego (radio buttons)
- Jeśli wariantowy: przypisanie zdjęć do wariantów (drag&drop to variant)
- Kopiowanie z innego produktu (search by SKU/nazwa)

**Properties:**
```php
public ?int $productId = null;
public array $uploadedFiles = [];             // Livewire\TemporaryUploadedFile[]
public array $existingImages = [];            // From Media model
public ?int $primaryImageId = null;
public array $variantAssignments = [];        // [variantId => [imageIds]]
public bool $isModalOpen = false;
public string $copyFromSearch = '';
public ?int $copyFromProductId = null;
```

**Methods:**
```php
public function mount(int $productId): void
public function openModal(): void
public function closeModal(): void
public function updatedUploadedFiles(): void          // Process uploads
public function setPrimaryImage(int $imageId): void
public function assignToVariant(int $imageId, int $variantId): void
public function removeImage(int $imageId): void
public function searchProductToCopy(): void
public function copyImagesFrom(int $sourceProductId): void
public function save(): void                          // Persist to Media model
```

**Upload Flow:**
```
User uploads 5 files (drag&drop)
  ↓
updatedUploadedFiles() processes temporary files
  ↓
Preview grid shows thumbnails (wire:key="img-{$index}")
  ↓
User clicks "Ustaw jako główne" → setPrimaryImage()
  ↓
If product has variants → show variant assignment UI
  ↓
User drags image to variant → assignToVariant()
  ↓
User clicks "Zapisz" → save() → Media records created
  ↓
Dispatch 'images-uploaded' event → ProductImportPanel refreshes
```

**Events (listens):**
- `open-image-upload-modal` → openModal(productId)

**Events (dispatches):**
- `images-uploaded` → { productId, imageCount }

---

### 6. **VariantCreationModal** (simplified ProductFormVariants)
**Lokalizacja:** `app/Http/Livewire/Products/Import/VariantCreationModal.php`
**Odpowiedzialność:**
- Uproszczona wersja trait ProductFormVariants
- Atrybuty + wartości (Kolor, Rozmiar, Materiał)
- Generowanie kombinacji wariantów
- BEZ zdjęć (zdjęcia w ImageUploadModal)

**Properties:**
```php
public ?int $productId = null;
public array $attributes = [];                // [{'attribute_id': 1, 'values': [1,2,3]}]
public array $generatedVariants = [];
public bool $isModalOpen = false;
```

**Methods:**
```php
public function mount(int $productId): void
public function openModal(): void
public function closeModal(): void
public function addAttribute(int $attributeId): void
public function removeAttribute(int $attributeId): void
public function toggleAttributeValue(int $attributeId, int $valueId): void
public function generateVariants(): void              // Cartesian product
public function saveVariants(): void                  // Create ProductVariant records
```

**Variant Generation:**
```php
// User selects:
Kolor: [Czerwony, Niebieski]
Rozmiar: [S, M, L]

// Generated variants (Cartesian product):
[
    ['Kolor' => 'Czerwony', 'Rozmiar' => 'S'],
    ['Kolor' => 'Czerwony', 'Rozmiar' => 'M'],
    ['Kolor' => 'Czerwony', 'Rozmiar' => 'L'],
    ['Kolor' => 'Niebieski', 'Rozmiar' => 'S'],
    ['Kolor' => 'Niebieski', 'Rozmiar' => 'M'],
    ['Kolor' => 'Niebieski', 'Rozmiar' => 'L'],
]
// Total: 6 wariantów
```

**Events (listens):**
- `open-variant-creation-modal` → openModal(productId)

**Events (dispatches):**
- `variants-created` → { productId, variantCount }

---

### 7. **VehicleFeaturesModal** (cechy pojazdu)
**Lokalizacja:** `app/Http/Livewire/Products/Import/VehicleFeaturesModal.php`
**Odpowiedzialność:**
- Wczytaj szablon z /admin/features/vehicles
- Wczytaj z innego pojazdu (search by SKU/nazwa)
- Edycja indywidualna (feature groups + values)

**Properties:**
```php
public ?int $productId = null;
public array $features = [];                  // FeatureType + FeatureValue
public ?int $templateId = null;
public string $copyFromSearch = '';
public ?int $copyFromProductId = null;
public bool $isModalOpen = false;
```

**Methods:**
```php
public function mount(int $productId): void
public function openModal(): void
public function closeModal(): void
public function loadTemplate(int $templateId): void
public function searchProductToCopy(): void
public function copyFeaturesFrom(int $sourceProductId): void
public function updateFeature(int $featureTypeId, mixed $value): void
public function save(): void                          // Persist to ProductFeature
```

**Feature Structure:**
```php
[
    'Silnik' => [
        'Typ silnika' => '4-suw',
        'Pojemność' => '125cc',
        'Moc' => '15KM',
    ],
    'Wymiary' => [
        'Długość' => '2000mm',
        'Szerokość' => '800mm',
        'Wysokość' => '1100mm',
    ],
]
```

**Events (listens):**
- `open-vehicle-features-modal` → openModal(productId)

**Events (dispatches):**
- `vehicle-features-updated` → { productId, featureCount }

---

### 8. **ShopSelector** (mini kafelki)
**Lokalizacja:** `app/Http/Livewire/Products/Import/ShopSelector.php`
**Odpowiedzialność:**
- Mini kafelki sklepów PrestaShop (grid 3 columns)
- Toggle selection (checkbox + visual feedback)
- Bulk mode support (select shops for multiple products)

**Properties:**
```php
public ?int $productId = null;
public array $selectedShopIds = [];
public array $availableShops = [];
public bool $isBulkMode = false;
```

**Methods:**
```php
public function mount(?int $productId = null): void
public function toggleShop(int $shopId): void
public function selectAll(): void
public function deselectAll(): void
public function applyToProduct(): void                // Single product
public function applyBulkShops(array $productIds): void
```

**Shop Tile:**
```html
<div class="shop-tile {{ $selected ? 'selected' : '' }}"
     wire:click="toggleShop({{ $shop->id }})">
    <div class="shop-icon">🛒</div>
    <div class="shop-name">{{ $shop->name }}</div>
    <div class="shop-status {{ $shop->connection_status }}"></div>
</div>
```

**Events (dispatches):**
- `shops-selected` → { productId, shopIds }
- `bulk-shops-selected` → { productIds, shopIds }

---

### 9. **PublishButton** (akcja publikacji)
**Lokalizacja:** `app/Http/Livewire/Products/Import/PublishButton.php`
**Odpowiedzialność:**
- Aktywny tylko gdy produkt kompletny (validation)
- Wire:click uruchamia workflow publikacji
- Loading state podczas przetwarzania (wire:loading)
- Error handling (flash messages)

**Properties:**
```php
public ?int $productId = null;
public bool $isProcessing = false;
public array $validationErrors = [];
```

**Methods:**
```php
public function mount(int $productId): void
public function publish(): void                       // Validate + dispatch job
public function isProductComplete(): bool             // Validation check
public function getMissingFields(): array
protected function dispatchPublishJob(): void
```

**Validation Rules:**
```php
REQUIRED_FIELDS = [
    'sku',
    'name',
    'product_type_id',
    'categories' => 'min:1',
    'shops' => 'min:1',
];

CONDITIONAL_REQUIREMENTS = [
    ProductType::CZESC_ZAMIENNA => ['vehicle_features', 'vehicle_compatibility'],
    ProductType::POJAZD => ['vehicle_features'],
];
```

**Publish Flow:**
```
User clicks "Publikuj"
  ↓
isProductComplete() → validation
  ↓
If invalid → show missing fields modal
  ↓
If valid → dispatchPublishJob()
  ↓
Job: BulkSyncProducts (syncMode: 'create_new')
  ↓
JobProgressBar shows progress
  ↓
On completion → dispatch 'products-published' event
  ↓
ProductImportPanel refreshes (product removed from pending list)
```

**Events (dispatches):**
- `product-published` → { productId }
- `products-published` → { productIds[] } (bulk)

---

## 📊 EVENTS FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────┐
│                         ProductImportPanel                          │
│                         (main component)                            │
├─────────────────────────────────────────────────────────────────────┤
│ Displays pending products list                                      │
│ Pagination, sorting, filtering, bulk actions                       │
└───────────┬─────────────────────────────────────────────────────────┘
            │
            ├─→ open-sku-paste-modal ────────→ SkuPasteInput
            │                                      │
            │                                      ├─→ products-added
            │                                      └─→ refreshList()
            │
            ├─→ open-category-picker-modal ──→ HierarchicalCategoryPicker
            │                                      │
            │                                      ├─→ categories-selected
            │                                      ├─→ bulk-categories-selected
            │                                      └─→ product-updated
            │
            ├─→ product-type-changed ───────→ ProductTypeSelector
            │                                      │
            │                                      ├─→ product-type-changed
            │                                      └─→ product-updated
            │
            ├─→ open-image-upload-modal ────→ ImageUploadModal
            │                                      │
            │                                      ├─→ images-uploaded
            │                                      └─→ product-updated
            │
            ├─→ open-variant-creation-modal ─→ VariantCreationModal
            │                                      │
            │                                      ├─→ variants-created
            │                                      └─→ product-updated
            │
            ├─→ open-vehicle-features-modal ─→ VehicleFeaturesModal
            │                                      │
            │                                      ├─→ vehicle-features-updated
            │                                      └─→ product-updated
            │
            ├─→ shops-selected ─────────────→ ShopSelector
            │                                      │
            │                                      ├─→ shops-selected
            │                                      ├─→ bulk-shops-selected
            │                                      └─→ product-updated
            │
            └─→ publish ────────────────────→ PublishButton
                                                   │
                                                   ├─→ BulkSyncProducts (Job)
                                                   ├─→ product-published
                                                   └─→ products-published
                                                       │
                                                       └─→ refreshList()
```

---

## 💻 KLUCZOWE METODY - PRZYKŁADY KODU

### ProductImportPanel - Bulk Operations

```php
/**
 * Bulk set category for selected products
 *
 * @param array $categoryPath [3 => 123, 4 => 456, 5 => 789]
 */
public function bulkSetCategory(array $categoryPath): void
{
    if (empty($this->selectedIds)) {
        $this->addError('bulk_action', 'Wybierz produkty do edycji');
        return;
    }

    try {
        DB::transaction(function () use ($categoryPath) {
            $categoryManager = new ProductCategoryManager();

            foreach ($this->selectedIds as $productId) {
                $product = Product::find($productId);

                if ($product) {
                    $categoryManager->updateCategories($product, $categoryPath);
                }
            }
        });

        session()->flash('success',
            'Kategoria ustawiona dla ' . count($this->selectedIds) . ' produktów'
        );

        $this->dispatch('product-updated');
        $this->selectedIds = [];
        $this->refreshList();

    } catch (\Exception $e) {
        Log::error('Bulk category update failed', [
            'product_ids' => $this->selectedIds,
            'category_path' => $categoryPath,
            'error' => $e->getMessage(),
        ]);

        $this->addError('bulk_action', 'Błąd podczas aktualizacji kategorii');
    }
}

/**
 * Bulk publish selected products
 *
 * Validates each product and dispatches BulkSyncProducts job
 */
public function bulkPublish(): void
{
    if (empty($this->selectedIds)) {
        $this->addError('bulk_action', 'Wybierz produkty do publikacji');
        return;
    }

    // Validate products
    $validProducts = [];
    $invalidProducts = [];

    foreach ($this->selectedIds as $productId) {
        if ($this->isProductComplete($productId)) {
            $validProducts[] = $productId;
        } else {
            $invalidProducts[] = $productId;
        }
    }

    if (empty($validProducts)) {
        $this->addError('bulk_action', 'Brak kompletnych produktów do publikacji');
        return;
    }

    try {
        // Get products with shops
        $products = Product::whereIn('id', $validProducts)
            ->with('shopData')
            ->get();

        // Group by shop
        $productsByShop = [];
        foreach ($products as $product) {
            foreach ($product->shopData as $shopData) {
                $productsByShop[$shopData->shop_id][] = $product->id;
            }
        }

        // Dispatch jobs per shop
        foreach ($productsByShop as $shopId => $productIds) {
            BulkSyncProducts::dispatch(
                shopId: $shopId,
                productIds: $productIds,
                syncMode: 'create_new'
            );
        }

        session()->flash('success',
            'Rozpoczęto publikację ' . count($validProducts) . ' produktów do ' .
            count($productsByShop) . ' sklepów'
        );

        if (!empty($invalidProducts)) {
            session()->flash('warning',
                count($invalidProducts) . ' produktów pominięto (niekompletne dane)'
            );
        }

        $this->dispatch('products-published', productIds: $validProducts);
        $this->selectedIds = [];
        $this->refreshList();

    } catch (\Exception $e) {
        Log::error('Bulk publish failed', [
            'product_ids' => $validProducts,
            'error' => $e->getMessage(),
        ]);

        $this->addError('bulk_action', 'Błąd podczas publikacji produktów');
    }
}

/**
 * Check if product is complete and ready to publish
 *
 * @param int $productId
 * @return bool
 */
public function isProductComplete(int $productId): bool
{
    $product = Product::with(['shopData', 'features', 'vehicleCompatibility'])
        ->find($productId);

    if (!$product) {
        return false;
    }

    // Required fields
    if (empty($product->sku) || empty($product->name) || empty($product->product_type_id)) {
        return false;
    }

    // At least one category
    if (empty($product->category3_id)) {
        return false;
    }

    // At least one shop
    if ($product->shopData->isEmpty()) {
        return false;
    }

    // Type-specific requirements
    if ($product->product_type_id == ProductType::CZESC_ZAMIENNA) {
        // Części zamienne wymagają cech i dopasowań
        if ($product->features->isEmpty() || $product->vehicleCompatibility->isEmpty()) {
            return false;
        }
    } elseif ($product->product_type_id == ProductType::POJAZD) {
        // Pojazdy wymagają cech
        if ($product->features->isEmpty()) {
            return false;
        }
    }

    return true;
}

/**
 * Get missing fields for product
 *
 * @param int $productId
 * @return array ['field' => 'label']
 */
public function getMissingFields(int $productId): array
{
    $product = Product::with(['shopData', 'features', 'vehicleCompatibility'])
        ->find($productId);

    if (!$product) {
        return ['product' => 'Produkt nie znaleziony'];
    }

    $missing = [];

    if (empty($product->sku)) {
        $missing['sku'] = 'SKU';
    }
    if (empty($product->name)) {
        $missing['name'] = 'Nazwa';
    }
    if (empty($product->product_type_id)) {
        $missing['product_type_id'] = 'Typ produktu';
    }
    if (empty($product->category3_id)) {
        $missing['categories'] = 'Kategoria';
    }
    if ($product->shopData->isEmpty()) {
        $missing['shops'] = 'Sklepy docelowe';
    }

    // Type-specific
    if ($product->product_type_id == ProductType::CZESC_ZAMIENNA) {
        if ($product->features->isEmpty()) {
            $missing['features'] = 'Cechy pojazdu';
        }
        if ($product->vehicleCompatibility->isEmpty()) {
            $missing['compatibility'] = 'Dopasowania pojazdów';
        }
    } elseif ($product->product_type_id == ProductType::POJAZD) {
        if ($product->features->isEmpty()) {
            $missing['features'] = 'Cechy pojazdu';
        }
    }

    return $missing;
}
```

---

### HierarchicalCategoryPicker - Cascading Dropdowns

```php
/**
 * Select category at specific level
 *
 * @param int $level (3-7)
 * @param int $categoryId
 */
public function selectCategory(int $level, int $categoryId): void
{
    // Clear all levels below selected
    for ($i = $level + 1; $i <= 7; $i++) {
        unset($this->selectedPath[$i]);
        $this->{'searchL' . $i} = '';
    }

    // Load category details
    $category = Category::find($categoryId);

    if (!$category) {
        $this->addError('category', 'Kategoria nie znaleziona');
        return;
    }

    // Store in path
    $this->selectedPath[$level] = [
        'id' => $category->id,
        'name' => $category->name,
        'slug' => $category->slug,
    ];

    // Load next level categories
    if ($level < 7) {
        $this->loadCategoriesForLevel($level + 1);
    }

    Log::debug('Category selected', [
        'level' => $level,
        'category_id' => $categoryId,
        'path' => $this->selectedPath,
    ]);
}

/**
 * Load categories for specific level
 *
 * @param int $level (4-7)
 */
protected function loadCategoriesForLevel(int $level): void
{
    if ($level < 4 || $level > 7) {
        return;
    }

    $parentLevel = $level - 1;
    $parentId = $this->selectedPath[$parentLevel]['id'] ?? null;

    if (!$parentId) {
        $this->availableCategories[$level] = [];
        return;
    }

    // Load children
    $query = Category::where('level', $level)
        ->where('parent_id', $parentId)
        ->orderBy('name');

    // Apply search filter
    $searchKey = 'searchL' . $level;
    if (!empty($this->{$searchKey})) {
        $query->where('name', 'LIKE', '%' . $this->{$searchKey} . '%');
    }

    $this->availableCategories[$level] = $query->get()->toArray();
}

/**
 * Load ML-based suggestions for product
 *
 * Uses fuzzy matching on product name + historical data
 */
public function loadSuggestions(): void
{
    if (empty($this->productName)) {
        $this->suggestions = [];
        return;
    }

    try {
        // Simple fuzzy matching (can be enhanced with ML model later)
        $keywords = explode(' ', strtolower($this->productName));

        $suggestions = Category::where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                }
            })
            ->where('level', '>=', 3)
            ->limit(5)
            ->get();

        $this->suggestions = $suggestions->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->getFullPath(), // L3 > L4 > L5
                'level' => $category->level,
                'confidence' => rand(60, 95) / 100, // Placeholder (use ML score later)
            ];
        })->toArray();

    } catch (\Exception $e) {
        Log::error('Failed to load category suggestions', [
            'product_name' => $this->productName,
            'error' => $e->getMessage(),
        ]);

        $this->suggestions = [];
    }
}

/**
 * Confirm category selection
 *
 * Validates path and dispatches event
 */
public function confirmSelection(): void
{
    if (empty($this->selectedPath)) {
        $this->addError('category', 'Wybierz kategorię');
        return;
    }

    // Validate path (must start at L3)
    if (!isset($this->selectedPath[3])) {
        $this->addError('category', 'Kategoria musi zaczynać się od poziomu 3');
        return;
    }

    // Dispatch event based on mode
    if ($this->isBulkMode) {
        $this->dispatch('bulk-categories-selected', [
            'productIds' => $this->productIds,
            'categoryPath' => $this->selectedPath,
        ]);
    } else {
        $this->dispatch('categories-selected', [
            'productId' => $this->productId,
            'categoryPath' => $this->selectedPath,
        ]);
    }

    $this->closeModal();
}
```

---

### ImageUploadModal - File Uploads + Variant Assignment

```php
/**
 * Process uploaded files
 *
 * Livewire automatically handles temporary file uploads
 */
public function updatedUploadedFiles(): void
{
    // Validate file types and sizes
    $this->validate([
        'uploadedFiles.*' => 'image|max:10240', // 10MB max
    ]);

    Log::info('Images uploaded', [
        'product_id' => $this->productId,
        'file_count' => count($this->uploadedFiles),
    ]);
}

/**
 * Assign image to variant
 *
 * @param int $imageId
 * @param int $variantId
 */
public function assignToVariant(int $imageId, int $variantId): void
{
    if (!isset($this->variantAssignments[$variantId])) {
        $this->variantAssignments[$variantId] = [];
    }

    if (!in_array($imageId, $this->variantAssignments[$variantId])) {
        $this->variantAssignments[$variantId][] = $imageId;
    }

    Log::debug('Image assigned to variant', [
        'image_id' => $imageId,
        'variant_id' => $variantId,
    ]);
}

/**
 * Copy images from another product
 *
 * @param int $sourceProductId
 */
public function copyImagesFrom(int $sourceProductId): void
{
    try {
        $sourceProduct = Product::with('media')->find($sourceProductId);

        if (!$sourceProduct) {
            $this->addError('copy', 'Produkt źródłowy nie znaleziony');
            return;
        }

        // Copy media records
        foreach ($sourceProduct->media as $media) {
            $newMedia = $media->replicate();
            $newMedia->product_id = $this->productId;
            $newMedia->save();

            // Copy physical file
            Storage::disk('public')->copy(
                $media->file_path,
                'products/' . $this->productId . '/' . basename($media->file_path)
            );

            $this->existingImages[] = $newMedia->toArray();
        }

        session()->flash('success',
            'Skopiowano ' . count($sourceProduct->media) . ' zdjęć'
        );

        Log::info('Images copied from product', [
            'source_product_id' => $sourceProductId,
            'target_product_id' => $this->productId,
            'image_count' => count($sourceProduct->media),
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to copy images', [
            'source_product_id' => $sourceProductId,
            'target_product_id' => $this->productId,
            'error' => $e->getMessage(),
        ]);

        $this->addError('copy', 'Błąd podczas kopiowania zdjęć');
    }
}

/**
 * Save uploaded images to database
 */
public function save(): void
{
    try {
        DB::transaction(function () {
            $product = Product::findOrFail($this->productId);

            // Save uploaded files
            foreach ($this->uploadedFiles as $file) {
                $path = $file->store('products/' . $this->productId, 'public');

                $media = Media::create([
                    'product_id' => $this->productId,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'is_primary' => false,
                ]);

                $this->existingImages[] = $media->toArray();
            }

            // Set primary image
            if ($this->primaryImageId) {
                Media::where('product_id', $this->productId)
                    ->update(['is_primary' => false]);

                Media::where('id', $this->primaryImageId)
                    ->update(['is_primary' => true]);
            }

            // Save variant assignments
            foreach ($this->variantAssignments as $variantId => $imageIds) {
                $variant = ProductVariant::find($variantId);

                if ($variant) {
                    $variant->images()->sync($imageIds);
                }
            }
        });

        session()->flash('success', 'Zdjęcia zapisane pomyślnie');

        $this->dispatch('images-uploaded', [
            'productId' => $this->productId,
            'imageCount' => count($this->existingImages),
        ]);

        $this->closeModal();

    } catch (\Exception $e) {
        Log::error('Failed to save images', [
            'product_id' => $this->productId,
            'error' => $e->getMessage(),
        ]);

        $this->addError('save', 'Błąd podczas zapisywania zdjęć');
    }
}
```

---

## 🔗 INTEGRACJA Z ISTNIEJĄCYMI KOMPONENTAMI

### 1. Reużycie ProductForm trait'ów

```php
// VariantCreationModal reuses ProductFormVariants logic
use App\Http\Livewire\Products\Management\Traits\ProductFormVariants;

class VariantCreationModal extends Component
{
    use ProductFormVariants;

    // Override methods to simplify for import workflow
    public function generateVariants(): void
    {
        // Delegate to trait method
        $this->generateVariantCombinations();
    }
}
```

### 2. Reużycie CompatibilityManagement

```php
// ProductImportPanel może otworzyć istniejący modal
public function openCompatibilityModal(int $productId): void
{
    $this->dispatch('open-compatibility-modal', productId: $productId);
}

// CompatibilityManagement pozostaje bez zmian
// Import system korzysta z istniejącego komponentu
```

### 3. Reużycie CategoryPreviewModal

```php
// Import system integruje się z istniejącym systemem kategorii
// HierarchicalCategoryPicker używa tego samego CategoryMapper

use App\Services\PrestaShop\CategoryMapper;

protected function loadCategoriesForLevel(int $level): void
{
    $mapper = new CategoryMapper();
    // Use existing category mapping logic
}
```

---

## ⚡ PERFORMANCE CONSIDERATIONS

### 1. Lazy Loading

```php
// ProductImportPanel - lazy load product details
public function getProductsProperty()
{
    return Product::query()
        ->where('status', 'pending')
        ->when($this->search, fn($q) =>
            $q->where('sku', 'LIKE', "%{$this->search}%")
              ->orWhere('name', 'LIKE', "%{$this->search}%")
        )
        ->when($this->filters['shop_id'], fn($q) =>
            $q->whereHas('shopData', fn($sq) =>
                $sq->where('shop_id', $this->filters['shop_id'])
            )
        )
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate($this->perPage);
}
```

### 2. Pagination

```php
// Use Livewire WithPagination trait
use Livewire\WithPagination;

class ProductImportPanel extends Component
{
    use WithPagination;

    public int $perPage = 25;

    // Reset pagination on search
    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}
```

### 3. Debouncing

```blade
<!-- Search input with debouncing -->
<input type="text"
       wire:model.debounce.500ms="search"
       placeholder="Szukaj SKU lub nazwy..."
       class="form-input">

<!-- Category search with debouncing -->
<input type="text"
       wire:model.debounce.300ms="searchL4"
       placeholder="Szukaj kategorii..."
       class="form-input">
```

### 4. Conditional Polling

```php
// Only poll when there are active jobs
public function getHasActiveJobsProperty(): bool
{
    return Product::where('status', 'processing')->exists();
}
```

```blade
<!-- Conditional polling -->
<div @if($hasActiveJobs) wire:poll.10s="refreshList" @endif>
    <!-- Product list -->
</div>
```

### 5. Query Optimization

```php
// Eager load relationships to prevent N+1
public function getProductsProperty()
{
    return Product::with([
            'productType:id,name',
            'shopData.shop:id,name',
            'media' => fn($q) => $q->where('is_primary', true),
        ])
        ->where('status', 'pending')
        ->paginate($this->perPage);
}
```

### 6. Batch Operations

```php
// Bulk operations use batch processing
public function bulkSetCategory(array $categoryPath): void
{
    DB::transaction(function () use ($categoryPath) {
        // Process in chunks to prevent memory issues
        Product::whereIn('id', $this->selectedIds)
            ->chunk(100, function ($products) use ($categoryPath) {
                foreach ($products as $product) {
                    $this->updateProductCategory($product, $categoryPath);
                }
            });
    });
}
```

---

## 📋 VALIDATION PER PRODUCT

### Product Completeness Rules

```php
/**
 * Validation rules per product type
 */
protected array $completenessRules = [
    ProductType::CZESC_ZAMIENNA => [
        'required' => ['sku', 'name', 'category3_id', 'shops', 'features', 'compatibility'],
        'optional' => ['variants', 'images'],
    ],
    ProductType::POJAZD => [
        'required' => ['sku', 'name', 'category3_id', 'shops', 'features'],
        'optional' => ['variants', 'images', 'compatibility'],
    ],
    ProductType::AKCESORIA => [
        'required' => ['sku', 'name', 'category3_id', 'shops'],
        'optional' => ['variants', 'images', 'features', 'compatibility'],
    ],
    ProductType::ODZIEZ => [
        'required' => ['sku', 'name', 'category3_id', 'shops', 'variants'],
        'optional' => ['images', 'features'],
    ],
];

/**
 * Get validation status for product
 *
 * @param int $productId
 * @return array ['complete' => bool, 'missing' => array, 'percentage' => int]
 */
public function getValidationStatus(int $productId): array
{
    $product = Product::with(['shopData', 'features', 'vehicleCompatibility', 'variants', 'media'])
        ->find($productId);

    if (!$product) {
        return ['complete' => false, 'missing' => ['product' => 'Not found'], 'percentage' => 0];
    }

    $rules = $this->completenessRules[$product->product_type_id] ?? $this->completenessRules[ProductType::AKCESORIA];
    $required = $rules['required'];
    $missing = [];
    $total = count($required);
    $completed = 0;

    foreach ($required as $field) {
        switch ($field) {
            case 'sku':
            case 'name':
                if (empty($product->{$field})) {
                    $missing[] = $field;
                } else {
                    $completed++;
                }
                break;

            case 'category3_id':
                if (empty($product->category3_id)) {
                    $missing[] = 'categories';
                } else {
                    $completed++;
                }
                break;

            case 'shops':
                if ($product->shopData->isEmpty()) {
                    $missing[] = 'shops';
                } else {
                    $completed++;
                }
                break;

            case 'features':
                if ($product->features->isEmpty()) {
                    $missing[] = 'features';
                } else {
                    $completed++;
                }
                break;

            case 'compatibility':
                if ($product->vehicleCompatibility->isEmpty()) {
                    $missing[] = 'compatibility';
                } else {
                    $completed++;
                }
                break;

            case 'variants':
                if ($product->variants->isEmpty()) {
                    $missing[] = 'variants';
                } else {
                    $completed++;
                }
                break;

            case 'images':
                if ($product->media->isEmpty()) {
                    $missing[] = 'images';
                } else {
                    $completed++;
                }
                break;
        }
    }

    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

    return [
        'complete' => empty($missing),
        'missing' => $missing,
        'percentage' => $percentage,
        'completed' => $completed,
        'total' => $total,
    ];
}
```

---

## 🎨 UI/UX COMPLIANCE

### PPM Standards Checklist

**✅ SPACING (8px Grid):**
- Card padding: **24px** (minimum 20px)
- Form groups: **margin-bottom 20px**
- Grid gaps: **20px** (product list grid)
- Page padding: **32px 24px**

**✅ COLORS (High Contrast):**
- Primary actions (Publikuj): **#f97316** (Orange-500)
- Secondary actions (Edytuj): **#3b82f6** (Blue-500)
- Success (Ukończone): **#10b981** (Emerald-500)
- Danger (Usuń): **#ef4444** (Red-500)

**✅ BUTTON HIERARCHY:**
- Primary: Orange background, white text, font-weight 600
- Secondary: Transparent background, blue border
- Danger: Red background, white text

**🚫 FORBIDDEN:**
- ❌ NO hover transforms (cards/panels)
- ❌ NO inline styles
- ❌ NO Tailwind arbitrary z-index (class="z-[9999]")

### CSS Classes (Existing PPM Styles)

```css
/* Use existing PPM classes from resources/css/admin/components.css */

.enterprise-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.btn-enterprise-primary {
    background: #f97316;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
}

.btn-enterprise-secondary {
    background: transparent;
    color: #3b82f6;
    padding: 10px 20px;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    font-weight: 600;
}

.form-group-ppm {
    margin-bottom: 20px;
}

.grid-product-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
```

---

## 📊 DATABASE SCHEMA CONSIDERATIONS

### Nowa tabela: pending_products

```sql
CREATE TABLE pending_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NULL,
    product_type_id INT NULL,
    status ENUM('incomplete', 'ready', 'processing', 'blocked') DEFAULT 'incomplete',
    priority INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Completeness tracking
    has_categories BOOLEAN DEFAULT FALSE,
    has_shops BOOLEAN DEFAULT FALSE,
    has_features BOOLEAN DEFAULT FALSE,
    has_compatibility BOOLEAN DEFAULT FALSE,
    has_variants BOOLEAN DEFAULT FALSE,
    has_images BOOLEAN DEFAULT FALSE,

    -- Metadata
    metadata JSON NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_type_id) REFERENCES product_types(id) ON DELETE SET NULL,

    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
);
```

**ALTERNATYWA:** Reużycie istniejącej tabeli `products` z nowym statusem

```sql
ALTER TABLE products
    ADD COLUMN import_status ENUM('draft', 'pending', 'ready', 'published') DEFAULT 'published';

ALTER TABLE products
    ADD COLUMN import_priority INT DEFAULT 0;

ALTER TABLE products
    ADD INDEX idx_import_status (import_status);
```

---

## 🔄 WORKFLOW PUBLIKACJI

```
┌─────────────────────────────────────────────────────────────────────┐
│ WORKFLOW: Import → Uzupełnienie → Publikacja                       │
└─────────────────────────────────────────────────────────────────────┘

1. IMPORT SKU
   User wkleja SKU w SkuPasteInput
   ↓
   Parsowanie + walidacja (czy SKU istnieje)
   ↓
   CREATE Product (status: 'pending', import_status: 'draft')

2. UZUPEŁNIENIE DANYCH (ProductImportPanel)
   User wybiera produkt z listy
   ↓
   Otwiera modały:
   - HierarchicalCategoryPicker → categories
   - ProductTypeSelector → type
   - ImageUploadModal → images
   - VariantCreationModal → variants (optional)
   - VehicleFeaturesModal → features (if required)
   - ShopSelector → shops
   ↓
   Każda akcja → dispatch 'product-updated' → refreshList()
   ↓
   Status: 'incomplete' → 'ready' (gdy wszystkie wymagane pola)

3. WALIDACJA
   PublishButton sprawdza isProductComplete()
   ↓
   If incomplete → show missing fields modal
   ↓
   If ready → enable "Publikuj" button

4. PUBLIKACJA
   User klika "Publikuj"
   ↓
   Dispatch BulkSyncProducts(shopId, productIds, syncMode: 'create_new')
   ↓
   Job tworzy JobProgress record
   ↓
   JobProgressBar pokazuje progress
   ↓
   On completion:
   - Product status: 'pending' → 'published'
   - Dispatch 'products-published' event
   - ProductImportPanel refreshes (product znika z listy)

5. MONITORING
   ActiveOperationsBar (ETAP_07c component)
   ↓
   Pokazuje wszystkie aktywne JOB-y
   ↓
   User może nawigować podczas publikacji
```

---

## 📁 STRUKTURA PLIKÓW

```
app/Http/Livewire/Products/Import/
├── ProductImportPanel.php              (główny komponent)
├── SkuPasteInput.php                   (modal - paste SKU)
├── HierarchicalCategoryPicker.php      (modal - categories)
├── ProductTypeSelector.php             (dropdown - type)
├── ImageUploadModal.php                (modal - images)
├── VariantCreationModal.php            (modal - variants)
├── VehicleFeaturesModal.php            (modal - features)
├── ShopSelector.php                    (mini kafelki)
├── PublishButton.php                   (akcja publikacji)
└── Services/
    ├── ImportProductValidator.php      (validation logic)
    └── ImportProductPublisher.php      (publish workflow)

resources/views/livewire/products/import/
├── product-import-panel.blade.php
├── sku-paste-input.blade.php
├── hierarchical-category-picker.blade.php
├── product-type-selector.blade.php
├── image-upload-modal.blade.php
├── variant-creation-modal.blade.php
├── vehicle-features-modal.blade.php
├── shop-selector.blade.php
├── publish-button.blade.php
└── partials/
    ├── product-card.blade.php          (single product in grid)
    ├── bulk-actions-toolbar.blade.php  (checkbox toolbar)
    ├── validation-status-badge.blade.php
    └── missing-fields-modal.blade.php

resources/css/products/import/
└── import-panel.css                    (dedicated CSS)

routes/web.php
+ Route::get('/products/import', ProductImportPanel::class)
    ->name('products.import')
    ->middleware(['auth', 'permission:products.create']);
```

---

## 🎯 IMPLEMENTATION PRIORITIES

### FAZA 1: Core Components (40h)
1. ProductImportPanel (15h)
2. SkuPasteInput (5h)
3. HierarchicalCategoryPicker (12h) - CRITICAL
4. PublishButton (8h)

### FAZA 2: Media & Variants (20h)
5. ImageUploadModal (10h)
6. VariantCreationModal (10h)

### FAZA 3: Features & Shops (15h)
7. VehicleFeaturesModal (8h)
8. ShopSelector (4h)
9. ProductTypeSelector (3h)

### FAZA 4: Integration & Testing (10h)
- Event system integration
- Bulk operations testing
- UI/UX polish
- Performance optimization

**TOTAL EFFORT:** ~85h (10-12 working days)

---

## ✅ WYKONANE PRACE

1. ✅ Analiza wymagań systemu Import PPM
2. ✅ Weryfikacja zgodności z SKU Architecture Guide
3. ✅ Weryfikacja zgodności z UI/UX Standards PPM
4. ✅ Przegląd istniejących komponentów (ProductForm, JobProgressBar, ImportManager)
5. ✅ Zaprojektowanie 9 komponentów Livewire 3.x
6. ✅ Szczegółowe properties i methods dla każdego komponentu
7. ✅ Events flow diagram
8. ✅ Przykłady kodu dla kluczowych metod
9. ✅ Integracja z istniejącymi komponentami
10. ✅ Performance considerations (lazy loading, pagination, debouncing)
11. ✅ Validation per product (completeness rules)
12. ✅ Workflow publikacji (5-step process)
13. ✅ Database schema considerations
14. ✅ Struktura plików i priorities

---

## 📋 NASTĘPNE KROKI

1. **User Review:** Przedstawienie architektury użytkownikowi do zatwierdzenia
2. **FAZA 1 Implementation:** ProductImportPanel + SkuPasteInput + HierarchicalCategoryPicker
3. **Database Migration:** Utworzenie `pending_products` table lub dodanie kolumn do `products`
4. **Routes & Middleware:** Dodanie route `/products/import` z permissions
5. **CSS Styling:** Utworzenie `import-panel.css` zgodnie z PPM standards
6. **Testing:** Manual testing + Chrome DevTools verification

---

## 📁 PLIKI

- `_AGENT_REPORTS/livewire_specialist_IMPORT_SYSTEM_ARCHITECTURE.md` - Ten raport

---

**Status:** ✅ UKOŃCZONY - Architektura gotowa do review i implementacji
**Next Agent:** architect (review architektury) → laravel-expert (implementacja FAZA 1)
