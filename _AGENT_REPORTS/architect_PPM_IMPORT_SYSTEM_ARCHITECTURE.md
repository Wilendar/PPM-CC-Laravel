# RAPORT ARCHITEKTURY: System IMPORT DO PPM

**Agent:** architect
**Data:** 2025-12-08
**Zadanie:** Zaprojektowanie całkowicie nowego systemu importu produktów DO aplikacji PPM

---

## 📋 STRESZCZENIE WYKONAWCZE

System **IMPORT DO PPM** to dedykowany moduł służący do masowego wprowadzania produktów przez różne działy organizacji. Produkty "niekompletne" przechowywane są w dedykowanym panelu i NIE pojawiają się w głównej liście produktów dopóki nie zostaną uzupełnione wszystkie wymagane dane.

### Kluczowe Cechy Systemu:
- **Panel produktów oczekujących** - dedykowana lista z wizualizacją statusu
- **Walidacja przed publikacją** - wymuszenie kompletu danych
- **Akcje masowe** - przypisywanie kategorii, prefixów, typów
- **3 sposoby importu** - wklejanie SKU, CSV/Excel, ERP (przyszłość)
- **Workflow publikacji** - PPM → ProductList → sklepy PrestaShop
- **Historia importu** - audit trail wszystkich operacji

---

## 🎯 ZAŁOŻENIA PROJEKTOWE

### 1. SKU-FIRST Architecture (KRYTYCZNE!)
**Zgodnie z `_DOCS/SKU_ARCHITECTURE_GUIDE.md`:**
- SKU jako PRIMARY lookup dla produktów
- SKU jako klucz conflict detection
- External IDs (PrestaShop) jako SECONDARY

### 2. Separacja "Niekompletnych" od ProductList
**Produkt w stanie DRAFT NIE pojawia się w /admin/products dopóki:**
- ✅ SKU wypełnione
- ✅ Nazwa wypełniona
- ✅ Kategoria przypisana (L3-L7)
- ✅ Typ produktu wybrany
- ✅ Min 1 zdjęcie (opcjonalne dla części)
- ✅ Sklepy wybrane

### 3. Integracja z Istniejącymi Systemami
**Wykorzystujemy już zaimplementowane:**
- `ProductForm.php` - dla edycji wariantów/zdjęć
- `CompatibilityManagement.php` - dla dopasowań pojazdów
- `CategoryTree.php` - dla wyboru kategorii
- `BulkImportProducts.php` - jako wzór dla queue jobs
- `ProductShopData` - dla per-shop tracking

---

## 🏗️ ARCHITEKTURA KOMPONENTÓW

### DIAGRAM KOMPONENTÓW

```
┌─────────────────────────────────────────────────────────────┐
│                    IMPORT PANEL (UI)                        │
│  /admin/import/products - Lista produktów oczekujących     │
└────────────┬────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────┐
│           LIVEWIRE COMPONENTS                               │
│  - PendingProductsList.php (główna lista)                  │
│  - PendingProductForm.php (edycja inline/modal)            │
│  - ImportWizard.php (kreator importu SKU/CSV)              │
│  - PublishProducts.php (publikacja do ProductList)         │
└────────────┬────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────┐
│              SERVICES LAYER                                 │
│  - PendingProductService.php (CRUD logic)                  │
│  - ImportProcessor.php (parse SKU/CSV)                     │
│  - PublishService.php (DRAFT → PUBLISHED workflow)         │
│  - ValidationService.php (business rules)                  │
└────────────┬────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────┐
│                 DATA MODELS                                 │
│  - PendingProduct.php (draft products)                     │
│  - ImportSession.php (session tracking)                    │
│  - PublishHistory.php (audit trail)                        │
└────────────┬────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────┐
│            INTEGRATION LAYER                                │
│  → Product.php (po publikacji)                             │
│  → ProductShopData.php (per-shop tracking)                 │
│  → BulkSyncProducts.php (eksport do PrestaShop)            │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 MODELE DANYCH

### 1. `PendingProduct` Model

**Tabela:** `pending_products`

**Pola:**
```php
id                      // PK
sku                     // UNIQUE - może być NULL dla partial imports
name                    // NULLABLE - uzupełniane w trakcie
slug                    // NULLABLE - auto-generated
product_type_id         // FK → product_types, NULLABLE
manufacturer            // NULLABLE
supplier_code           // NULLABLE
ean                     // NULLABLE

// Kategorie (JSON - lista L3-L7 IDs)
category_ids            // JSON [3,7,12]

// Zdjęcia (JSON - lista temporary upload paths)
temp_media_paths        // JSON ['tmp/uuid1.jpg', 'tmp/uuid2.jpg']

// Sklepy (JSON - lista shop IDs)
shop_ids                // JSON [1,3,5]

// Dane fizyczne
weight, height, width, length, tax_rate  // NULLABLE

// Opisy
short_description       // NULLABLE
long_description        // NULLABLE
meta_title, meta_description  // NULLABLE

// Status importu
completion_status       // JSON {sku: true, name: true, category: false, ...}
completion_percentage   // INT (0-100) - calculated
is_ready_for_publish    // BOOLEAN - wszystkie required fields wypełnione

// Tracking
import_session_id       // FK → import_sessions
imported_by             // FK → users
imported_at             // TIMESTAMP
published_at            // NULLABLE - po publikacji do ProductList
published_as_product_id // NULLABLE - FK → products (po publikacji)

// Audit
created_at, updated_at, deleted_at  // Soft deletes
```

**Relations:**
```php
belongsTo(ImportSession, 'import_session_id')
belongsTo(User, 'imported_by')
belongsTo(Product, 'published_as_product_id') // NULL before publish
belongsTo(ProductType, 'product_type_id')
```

**Scopes:**
```php
scopeIncomplete($query)      // completion_percentage < 100
scopeReadyForPublish($query) // is_ready_for_publish = true
scopeBySession($sessionId)
scopeByUser($userId)
```

**Methods:**
```php
calculateCompletion(): int           // Zwraca 0-100%
canPublish(): bool                   // Sprawdza required fields
publishToProductList(): Product      // Konwertuje do Product model
markAsPublished(Product $product)    // Update tracking
```

---

### 2. `ImportSession` Model

**Tabela:** `import_sessions`

**Pola:**
```php
id                      // PK
uuid                    // UNIQUE - dla public URL tracking
session_name            // Nazwa sesji importu
import_method           // ENUM: paste_sku, paste_sku_name, csv, excel, erp
import_source_file      // NULLABLE - ścieżka do uploaded file
parsed_data             // JSON - surowe dane po parse

// Statystyki
total_rows              // INT - ile rekordów w imporcie
products_created        // INT - ile PendingProducts utworzonych
products_published      // INT - ile opublikowanych do ProductList
products_failed         // INT - ile błędów

// Status
status                  // ENUM: parsing, ready, publishing, completed, failed
error_log               // JSON - lista błędów

// Tracking
imported_by             // FK → users
started_at              // TIMESTAMP
completed_at            // NULLABLE
created_at, updated_at
```

**Relations:**
```php
hasMany(PendingProduct, 'import_session_id')
belongsTo(User, 'imported_by')
```

**Methods:**
```php
markAsParsing()
markAsReady(int $productsCount)
markAsCompleted()
addError(string $sku, string $message)
getStats(): array  // {total, created, published, failed, completion_%}
```

---

### 3. `PublishHistory` Model

**Tabela:** `publish_history`

**Pola:**
```php
id                      // PK
pending_product_id      // FK → pending_products
product_id              // FK → products (utworzony)
published_by            // FK → users
published_at            // TIMESTAMP

// Co zostało opublikowane
published_shops         // JSON [1,3,5] - IDs sklepów
published_categories    // JSON [3,7,12] - IDs kategorii
published_media_count   // INT - ile zdjęć

// Sync to PrestaShop
sync_jobs_dispatched    // JSON - lista job UUIDs
sync_status             // ENUM: pending, in_progress, completed, failed

created_at
```

**Relations:**
```php
belongsTo(PendingProduct)
belongsTo(Product)
belongsTo(User, 'published_by')
```

---

## 🔧 SERVICES LAYER

### 1. `PendingProductService`

**Odpowiedzialność:** CRUD dla PendingProduct

**Metody:**
```php
create(array $data): PendingProduct
update(PendingProduct $product, array $data): bool
delete(PendingProduct $product): bool
bulkUpdate(array $productIds, array $data): int  // Zwraca count
calculateCompletion(PendingProduct $product): array  // {percentage, missing_fields}
```

---

### 2. `ImportProcessor`

**Odpowiedzialność:** Parsing różnych formatów importu

**Metody:**
```php
// Paste SKU (jedna kolumna)
parseSingleColumn(string $text, string $separator = "\n"): array
// Output: [['sku' => 'SKU-001'], ['sku' => 'SKU-002']]

// Paste SKU+Name (dwie kolumny)
parseTwoColumns(string $text, string $separator = "\n", string $columnDelimiter = null): array
// Auto-detect delimiter: ; , \t
// Output: [['sku' => 'SKU-001', 'name' => 'Product 1'], ...]

// CSV/Excel
parseCsvFile(string $filePath, array $columnMapping): array
// columnMapping: ['A' => 'sku', 'B' => 'name', 'C' => 'manufacturer']
// Output: [['sku' => ..., 'name' => ..., 'manufacturer' => ...], ...]

parseExcelFile(string $filePath, array $columnMapping): array
// Similar to CSV

// Walidacja
validateImportData(array $rows): array  // {valid: [], invalid: []}
detectDuplicates(array $skus): array    // [sku => exists_in_products|exists_in_pending]
```

---

### 3. `PublishService`

**Odpowiedzialność:** Publikacja DRAFT → PUBLISHED

**Workflow:**
```php
publish(PendingProduct $pendingProduct, array $options): Product
/**
 * Options:
 * - shop_ids: [1,3,5]
 * - dispatch_sync_jobs: true|false
 * - move_temp_media: true|false
 */

// Kroki:
// 1. Validate pendingProduct.canPublish()
// 2. DB::transaction {
//    a. Create Product from pendingProduct
//    b. Create ProductPrice records (default for all groups)
//    c. Sync categories (product_categories pivot)
//    d. Move temp media → storage/app/public/products/{sku}/
//    e. Create ProductShopData dla każdego shop_id
//    f. Mark pendingProduct as published
//    g. Create PublishHistory record
// }
// 3. Dispatch sync jobs (if options.dispatch_sync_jobs)
// 4. Return Product

bulkPublish(array $pendingProductIds, array $options): array
// Zwraca: {published: [Product], failed: [{id, reason}]}
```

---

### 4. `ValidationService`

**Odpowiedzialność:** Business rules validation

**Metody:**
```php
validateForPublish(PendingProduct $product): array
// Zwraca: {valid: true|false, errors: []}

// Rules:
// - SKU: required, unique (vs products + pending_products)
// - Name: required, min 3 chars
// - Category: min 1 category (L3-L7), max 10
// - ProductType: required
// - Shops: min 1 shop selected
// - Media: min 1 image (optional dla części)

checkSkuConflict(string $sku): ?string  // NULL|'exists_in_products'|'exists_in_pending'
```

---

## 🎨 UI/UX ARCHITECTURE

### 1. Panel Importu - `/admin/import/products`

**Layout:**
```
┌────────────────────────────────────────────────────────────┐
│  [+ Nowy Import]  [Akcje Masowe ▼]  [Filtruj ▼]  [🔍]     │
├────────────────────────────────────────────────────────────┤
│  ┌─┬──────┬──────────┬──────┬──────────┬──────┬──────┐   │
│  │✅│Zdjęc.│   SKU    │Nazwa │Typ Prod. │Kateg.│%Gotow│   │
│  ├─┼──────┼──────────┼──────┼──────────┼──────┼──────┤   │
│  │☐│ 📷   │ SKU-001  │Pro..│ Część    │ ✅(3)│ 85%  │   │
│  │☐│ ❌   │ SKU-002  │Pro..│ ❌       │ ❌   │ 40%  │   │
│  │☐│ 📷   │ (brak)   │Pro..│ Pojazd   │ ✅(2)│ 60%  │   │
│  └─┴──────┴──────────┴──────┴──────────┴──────┴──────┘   │
│                                                            │
│  Zaznaczono: 0    Gotowych do publikacji: 1/3            │
└────────────────────────────────────────────────────────────┘
```

**Kolumny:**
- ✅ - checkbox dla akcji masowych
- Zdjęcie - thumbnail (📷 = ma zdjęcia, ❌ = brak)
- SKU - edytowalne inline
- Nazwa - edytowalne inline
- Typ Produktu - dropdown inline
- Kategorie - status: ✅(3) = 3 kategorie, ❌ = brak
- Master/Wariant - ikona (🔧 = master, [V] = ma warianty)
- Cechy/Dopasowania - status: ✅ = uzupełnione, ❌ = brak
- Sklep - kafelki z logo sklepów
- % Gotowe - progress bar 0-100%

**Row Actions:**
- 👁️ Podgląd
- ✏️ Edytuj (otwiera modal z pełnym formularzem)
- 🗑️ Usuń
- 📤 Publikuj (jeśli is_ready_for_publish)

---

### 2. Modal "Nowy Import"

**3 tryby importu:**

#### A) Wklej SKU (lista)
```
┌──────────────────────────────────────────┐
│  Wklej listę SKU (jeden na linię):      │
│  ┌────────────────────────────────────┐ │
│  │ SKU-001                             │ │
│  │ SKU-002                             │ │
│  │ SKU-003                             │ │
│  └────────────────────────────────────┘ │
│                                          │
│  Separator: [Auto-detect ▼]             │
│  [Anuluj]  [Import (3 SKU) →]           │
└──────────────────────────────────────────┘
```

#### B) Wklej SKU + Nazwa (tabela)
```
┌──────────────────────────────────────────┐
│  Wklej tabelę (SKU | Nazwa):             │
│  ┌────────────────────────────────────┐ │
│  │ SKU-001;Product Name 1              │ │
│  │ SKU-002,Product Name 2              │ │
│  │ SKU-003	Product Name 3            │ │
│  └────────────────────────────────────┘ │
│                                          │
│  Separator kolumn: [Auto-detect ▼]      │
│  [Anuluj]  [Import (3 produkty) →]      │
└──────────────────────────────────────────┘
```

#### C) Import z CSV/Excel
```
┌──────────────────────────────────────────┐
│  Wybierz plik CSV/Excel:                 │
│  [Wybierz plik...]  products.xlsx        │
│                                          │
│  Mapowanie kolumn:                       │
│  Kolumna A → [SKU ▼]                     │
│  Kolumna B → [Nazwa ▼]                   │
│  Kolumna C → [Producent ▼]               │
│  [+ Dodaj kolumnę]                       │
│                                          │
│  [Anuluj]  [Podgląd (10 wierszy) →]     │
└──────────────────────────────────────────┘
```

---

### 3. Modal Edycji Produktu

**Tabs:**
- **Podstawowe** - SKU, Nazwa, Typ, Producent, Kod dostawcy, EAN
- **Kategorie** - CategoryTree picker (hierarchiczny)
- **Warianty** - Button "Edytuj warianty" → otwiera ProductForm (zakładka Warianty)
- **Cechy/Dopasowania** - Button "Zarządzaj" → otwiera CompatibilityManagement
- **Zdjęcia** - Drag&drop upload, wybór głównego, przypisanie do wariantów
- **Sklepy** - Kafelki z checkbox (B2B, Pitbike, Cameraman, etc.)
- **Fizyczne** - Wymiary, waga, VAT

**Footer:**
```
[Anuluj]  [Zapisz]  [Zapisz i Publikuj]
```

---

### 4. Akcje Masowe Dropdown

**Opcje:**
```
✅ Zaznacz wszystkie
❌ Odznacz wszystkie
─────────────────────
📂 Przypisz kategorie (wybrane)
🏷️ Dodaj prefix/suffix do nazwy/SKU
📦 Ustaw typ produktu
🏪 Wybierz sklepy
🚗 Utwórz dopasowania masowe
─────────────────────
📤 Publikuj zaznaczone
🗑️ Usuń zaznaczone
```

---

### 5. Workflow Publikacji

**Kliknięcie "Publikuj" (single lub bulk):**

1. **Walidacja**
   - Sprawdź `is_ready_for_publish`
   - Jeśli FALSE → modal z błędami:
     ```
     ┌────────────────────────────────────┐
     │ Produkt nie jest gotowy:           │
     │  ❌ Brak kategorii                 │
     │  ❌ Brak typu produktu             │
     │  ✅ SKU OK                         │
     │  ✅ Nazwa OK                       │
     │  ✅ Sklepy wybrane                 │
     │                                    │
     │  [OK - Uzupełnię dane]             │
     └────────────────────────────────────┘
     ```

2. **Confirmation Modal**
   ```
   ┌────────────────────────────────────┐
   │ Publikacja produktu:               │
   │  SKU: SKU-001                      │
   │  Nazwa: Product Name               │
   │  Sklepy: B2B, Pitbike (2)          │
   │  Kategorie: 3                      │
   │                                    │
   │  Po publikacji:                    │
   │  ✅ Produkt pojawi się w ProductList
   │  ✅ Utworzone JOBy eksportu do PS  │
   │  ✅ Przeniesione do Historii       │
   │                                    │
   │  [Anuluj]  [Publikuj →]            │
   └────────────────────────────────────┘
   ```

3. **Progress (dla bulk)**
   ```
   Publikacja produktów...
   [████████░░] 8/10 (80%)
   Aktualnie: SKU-008
   ```

4. **Success Toast**
   ```
   ✅ Opublikowano 8 produktów
   📤 Utworzono 16 JOB-ów eksportu (8×2 sklepy)
   ```

---

## 🔄 FLOW DANYCH

### SCENARIUSZ 1: Import SKU (lista)

```
1. User wkleja listę SKU do modal
   Input: "SKU-001\nSKU-002\nSKU-003"

2. ImportProcessor.parseSingleColumn()
   → [['sku' => 'SKU-001'], ['sku' => 'SKU-002'], ['sku' => 'SKU-003']]

3. ValidationService.detectDuplicates()
   → SKU-002 already exists in products → skip
   → SKU-001, SKU-003 → OK

4. Create ImportSession
   session_name: "Import SKU (2025-12-08 14:23)"
   import_method: paste_sku
   total_rows: 3
   products_created: 2 (SKU-002 pominięty)

5. Create PendingProduct records
   - id=1: sku='SKU-001', completion_percentage=20% (tylko SKU)
   - id=2: sku='SKU-003', completion_percentage=20%

6. Flash message:
   "✅ Zaimportowano 2 produkty (1 pominięty - duplikat)"

7. Redirect → /admin/import/products
   Lista wyświetla 2 nowe produkty z completion 20%
```

---

### SCENARIUSZ 2: Uzupełnienie danych i publikacja

```
1. User klika "Edytuj" na PendingProduct id=1
   → Modal z tabs

2. User wypełnia:
   - Nazwa: "Product Name 1"
   - Typ produktu: "Część zamienna" (ID=2)
   - Kategorie: [3, 7, 12] (3 kategorie L3-L7)
   - Sklepy: [1, 3] (B2B, Pitbike)
   - Zdjęcia: upload 2 plików → ['tmp/uuid1.jpg', 'tmp/uuid2.jpg']

3. User klika "Zapisz"
   → PendingProductService.update()
   → calculateCompletion() → 85% (brak tylko cech/dopasowań - opcjonalne)
   → is_ready_for_publish = TRUE (required fields OK)

4. User klika "Publikuj"
   → Confirmation modal
   → User potwierdza

5. PublishService.publish(pendingProduct, {shop_ids: [1,3], dispatch_sync_jobs: true})
   → DB::transaction {
      a. Product.create([
           sku: 'SKU-001',
           name: 'Product Name 1',
           product_type_id: 2,
           ...
         ]) → product_id=5001

      b. product_categories.insert([
           {product_id: 5001, category_id: 3},
           {product_id: 5001, category_id: 7, is_primary: true},
           {product_id: 5001, category_id: 12},
         ])

      c. Move media:
           tmp/uuid1.jpg → storage/app/public/products/SKU-001/1.jpg
           tmp/uuid2.jpg → storage/app/public/products/SKU-001/2.jpg
         Create Media records

      d. ProductShopData.create([
           {product_id: 5001, shop_id: 1, ...},
           {product_id: 5001, shop_id: 3, ...},
         ])

      e. PendingProduct->update([
           published_at: now(),
           published_as_product_id: 5001,
         ])

      f. PublishHistory.create([
           pending_product_id: 1,
           product_id: 5001,
           published_by: auth()->id(),
           published_shops: [1, 3],
           ...
         ])
   }

6. Dispatch BulkSyncProducts.dispatch(product_id=5001, shop_ids=[1,3])
   → 2 jobs created

7. Flash message:
   "✅ Opublikowano SKU-001 → Produkt #5001
    📤 Utworzono 2 JOB-y eksportu"

8. PendingProduct disappears from /admin/import/products
   → Pojawia się w /admin/products
```

---

### SCENARIUSZ 3: Import CSV z mapowaniem

```
1. User wybiera plik products.csv:
   A        | B              | C           | D
   SKU      | Name           | Manufacturer| EAN
   SKU-101  | Product 101    | Toyota      | 1234567890123
   SKU-102  | Product 102    | Honda       | 9876543210987

2. User mapuje kolumny:
   A → SKU
   B → Name
   C → Manufacturer
   D → EAN

3. User klika "Podgląd"
   → ImportProcessor.parseCsvFile()
   → Modal z preview 10 wierszy

4. User klika "Import"
   → Create ImportSession
   → parseCsvFile() full parse
   → Create PendingProduct records (2)
     - id=10: sku='SKU-101', name='Product 101', manufacturer='Toyota', ean='1234567890123'
       completion_percentage=50% (ma SKU, name, manufacturer, EAN - brak kategorii, typu, sklepów)
     - id=11: similar

5. Flash: "✅ Zaimportowano 2 produkty z CSV"

6. User przechodzi do bulk actions:
   - Zaznacza wszystkie (2)
   - "Ustaw typ produktu" → Część zamienna
   - "Wybierz sklepy" → B2B, Pitbike
   - "Przypisz kategorie" → [3, 7]

7. PendingProductService.bulkUpdate([10, 11], {
     product_type_id: 2,
     shop_ids: [1, 3],
     category_ids: [3, 7],
   })
   → calculateCompletion() → 85% (brak tylko zdjęć - opcjonalne)
   → is_ready_for_publish = TRUE

8. User klika "Publikuj zaznaczone (2)"
   → PublishService.bulkPublish([10, 11], ...)
   → 2 Product records created
   → 4 sync jobs dispatched (2 produkty × 2 sklepy)

9. Flash: "✅ Opublikowano 2 produkty, 📤 4 JOB-y eksportu"
```

---

## 🔌 INTEGRACJE

### 1. Z ProductForm.php (Warianty)

**Button "Edytuj warianty" w modal PendingProductForm:**
```php
// Livewire dispatch event
$this->dispatch('open-product-form', [
    'mode' => 'variants',
    'pending_product_id' => $this->pendingProduct->id,
]);

// ProductForm component:
protected $listeners = ['open-product-form' => 'handleOpenFromPending'];

public function handleOpenFromPending($data)
{
    $pendingProduct = PendingProduct::find($data['pending_product_id']);

    // Load pending data → form state
    $this->loadFromPendingProduct($pendingProduct);
    $this->activeTab = 'variants';
    $this->showModal = true;
}
```

**Po zapisaniu wariantów:**
```php
// ProductForm saves to PendingProduct (not Product yet!)
$pendingProduct->update([
    'has_variants' => true,
    'variant_data' => $this->variantData, // JSON
]);
```

---

### 2. Z CompatibilityManagement.php (Dopasowania)

**Button "Zarządzaj dopasowaniami":**
```php
// Redirect to CompatibilityManagement with context
return redirect()->route('admin.compatibility', [
    'context' => 'pending_product',
    'pending_product_id' => $this->pendingProduct->id,
]);

// CompatibilityManagement component:
public function mount($context = null, $pending_product_id = null)
{
    if ($context === 'pending_product') {
        $this->pendingProduct = PendingProduct::find($pending_product_id);
        $this->loadCompatibilitiesFromPending();
    }
}
```

**Po zapisaniu:**
```php
$pendingProduct->update([
    'compatibility_data' => $this->compatibilityData, // JSON
]);
```

---

### 3. Z CategoryTree.php (Wybór kategorii)

**W modal PendingProductForm - CategoryTree picker:**
```blade
<div wire:ignore>
    <livewire:products.categories.category-tree
        :mode="'picker'"
        :selected-categories="$categoryIds"
        :max-selection="10"
    />
</div>

<script>
Livewire.on('categories-selected', (categoryIds) => {
    @this.set('categoryIds', categoryIds);
});
</script>
```

---

### 4. Z BulkSyncProducts.php (Eksport do PrestaShop)

**Po publikacji:**
```php
// PublishService.php
foreach ($shopIds as $shopId) {
    BulkSyncProducts::dispatch(
        Product::find($product->id),
        [$shopId],
        'export', // mode
        ['source' => 'pending_product_publish']
    );
}

// Tracking w PublishHistory
$publishHistory->update([
    'sync_jobs_dispatched' => $jobUuids,
    'sync_status' => 'pending',
]);
```

**Callback po zakończeniu sync:**
```php
// BulkSyncProducts job (na końcu handle()):
if ($source === 'pending_product_publish') {
    PublishHistory::where('product_id', $productId)
                  ->update(['sync_status' => 'completed']);
}
```

---

## 🗂️ ROUTING

```php
// routes/web.php

Route::prefix('admin')->middleware(['auth', 'role:admin|manager|editor'])->group(function () {

    // Import Panel
    Route::get('/import/products', [PendingProductsController::class, 'index'])
        ->name('admin.import.products');

    // Import Wizard (modal steps)
    Route::post('/import/products/parse', [ImportController::class, 'parse'])
        ->name('admin.import.parse');

    Route::post('/import/products/confirm', [ImportController::class, 'confirm'])
        ->name('admin.import.confirm');

    // Publish
    Route::post('/import/products/{pendingProduct}/publish', [PublishController::class, 'single'])
        ->name('admin.import.publish.single');

    Route::post('/import/products/publish-bulk', [PublishController::class, 'bulk'])
        ->name('admin.import.publish.bulk');

    // History
    Route::get('/import/history', [PublishHistoryController::class, 'index'])
        ->name('admin.import.history');
});
```

---

## 📦 FAZY IMPLEMENTACJI

### FAZA 1: FUNDAMENT (8h)
**Cel:** Database schema + podstawowe modele

**Zadania:**
- ✅ Migration: `pending_products` table
- ✅ Migration: `import_sessions` table
- ✅ Migration: `publish_history` table
- ✅ Model: `PendingProduct.php` (z relations, scopes, methods)
- ✅ Model: `ImportSession.php`
- ✅ Model: `PublishHistory.php`
- ✅ Seeder: przykładowe pending products dla testów

**Deliverables:**
- `database/migrations/2025_12_08_*_create_pending_products_table.php`
- `app/Models/PendingProduct.php`
- `app/Models/ImportSession.php`
- `app/Models/PublishHistory.php`

---

### FAZA 2: IMPORT ENGINE (12h)
**Cel:** Parsing SKU/CSV + ImportSession management

**Zadania:**
- ✅ Service: `ImportProcessor.php` (parseSingleColumn, parseTwoColumns, parseCsvFile, parseExcelFile)
- ✅ Service: `ValidationService.php` (validateForPublish, checkSkuConflict)
- ✅ Service: `PendingProductService.php` (CRUD, bulkUpdate, calculateCompletion)
- ✅ Controller: `ImportController.php` (parse, confirm)
- ✅ Tests: ImportProcessor unit tests (20+ scenarios)

**Deliverables:**
- `app/Services/Import/ImportProcessor.php`
- `app/Services/Import/ValidationService.php`
- `app/Services/Import/PendingProductService.php`
- `tests/Unit/Services/ImportProcessorTest.php`

---

### FAZA 3: UI PANEL (16h)
**Cel:** Panel importu + lista produktów oczekujących

**Zadania:**
- ✅ Component: `PendingProductsList.php` (główna lista)
- ✅ View: `pending-products-list.blade.php` (tabela z inline editing)
- ✅ Component: `ImportWizard.php` (modal importu 3 tryby)
- ✅ View: `import-wizard.blade.php` (steps: wybór trybu → parse → preview → confirm)
- ✅ Controller: `PendingProductsController.php` (index)
- ✅ CSS: `resources/css/admin/import-panel.css` (styling zgodny z PPM Playbook)
- ✅ Route: `/admin/import/products`

**Deliverables:**
- `app/Http/Livewire/Admin/Import/PendingProductsList.php`
- `resources/views/livewire/admin/import/pending-products-list.blade.php`
- `app/Http/Livewire/Admin/Import/ImportWizard.php`

---

### FAZA 4: EDYCJA & AKCJE MASOWE (14h)
**Cel:** Modal edycji + bulk actions

**Zadania:**
- ✅ Component: `PendingProductForm.php` (modal z tabs)
- ✅ View: `pending-product-form.blade.php` (6 tabs: Podstawowe, Kategorie, Warianty, Cechy, Zdjęcia, Sklepy)
- ✅ Integration: CategoryTree picker
- ✅ Integration: Temporary media upload (Livewire File Upload)
- ✅ Bulk actions: Przypisz kategorie (modal)
- ✅ Bulk actions: Dodaj prefix/suffix (modal)
- ✅ Bulk actions: Ustaw typ produktu (dropdown)
- ✅ Bulk actions: Wybierz sklepy (kafelki)

**Deliverables:**
- `app/Http/Livewire/Admin/Import/PendingProductForm.php`
- `app/Http/Livewire/Admin/Import/BulkActions.php` (trait lub component)

---

### FAZA 5: PUBLIKACJA (12h)
**Cel:** PublishService + workflow DRAFT → PUBLISHED

**Zadania:**
- ✅ Service: `PublishService.php` (publish, bulkPublish)
- ✅ Controller: `PublishController.php` (single, bulk)
- ✅ Job: `PublishProductJob.php` (queue job dla bulk operations)
- ✅ Integration: Move temp media → storage/products/{sku}/
- ✅ Integration: Create ProductShopData records
- ✅ Integration: Dispatch BulkSyncProducts jobs
- ✅ Integration: Update PublishHistory tracking
- ✅ Tests: PublishService integration tests

**Deliverables:**
- `app/Services/Import/PublishService.php`
- `app/Http/Controllers/Admin/PublishController.php`
- `app/Jobs/Import/PublishProductJob.php`

---

### FAZA 6: HISTORIA & MONITORING (8h)
**Cel:** Panel historii + sync status tracking

**Zadania:**
- ✅ Component: `PublishHistoryList.php` (lista opublikowanych)
- ✅ View: `publish-history-list.blade.php` (tabela z filtrowaniem)
- ✅ Controller: `PublishHistoryController.php` (index)
- ✅ Webhook: Callback z BulkSyncProducts (update sync_status)
- ✅ Route: `/admin/import/history`

**Deliverables:**
- `app/Http/Livewire/Admin/Import/PublishHistoryList.php`
- `resources/views/livewire/admin/import/publish-history-list.blade.php`

---

### FAZA 7: TESTING & DEPLOYMENT (10h)
**Cel:** Testy E2E + deployment na produkcję

**Zadania:**
- ✅ Tests: Feature tests (import workflow end-to-end)
- ✅ Tests: Browser tests (Dusk - UI interactions)
- ✅ Documentation: User guide (screenshoty + instrukcje)
- ✅ Deployment: Migrations + seeders na produkcję
- ✅ Deployment: Assets build + upload
- ✅ User training: Demo video (10 min)

**Deliverables:**
- `tests/Feature/ImportWorkflowTest.php`
- `tests/Browser/ImportPanelTest.php`
- `_DOCS/USER_GUIDE_IMPORT_SYSTEM.md`

---

## ⚠️ RYZYKA I MITYGACJE

### RYZYKO 1: Konflikt SKU między PendingProduct a Product

**Problem:** User importuje SKU który już istnieje w products

**Mitygacja:**
- ValidationService.checkSkuConflict() PRZED utworzeniem PendingProduct
- Flash warning: "SKU-XXX już istnieje w produktach (ID: 1234). Pominięto."
- Opcja "Nadpisz istniejący" (dla Admin role)

---

### RYZYKO 2: Temporary media cleanup

**Problem:** User uploaduje zdjęcia ale nie publikuje → temp files accumulate

**Mitygacja:**
- Scheduled job: `CleanupTempMediaJob.php` (daily)
- Delete temp files starsze niż 7 dni + bez powiązanego PendingProduct
- Soft delete PendingProduct → cascade delete temp media

---

### RYZYKO 3: Bulk publish timeout

**Problem:** User publikuje 500 produktów → timeout

**Mitygacja:**
- Limit bulk publish: max 100 produktów
- Queue job: `PublishProductJob.php` dla każdego produktu
- Progress bar w UI (Livewire polling)
- Email notification po zakończeniu

---

### RYZYKO 4: Kategorie per-shop conflict

**Problem:** Pending produkt ma kategorie [3,7], ale sklep B wymaga innej kategorii

**Mitygacja:**
- W PendingProduct: `shop_categories` JSON: `{shop_id: [category_ids]}`
- Default categories stosowane jeśli brak shop-specific
- UI: Per-shop category override w modal (opcjonalne)

---

### RYZYKO 5: Warianty w DRAFT mode

**Problem:** User tworzy warianty dla PendingProduct, ale ProductVariant wymaga product_id (FK)

**Mitygacja:**
- Store variants jako JSON w `pending_products.variant_data`
- Po publikacji: Convert JSON → ProductVariant records
- Format JSON:
  ```json
  {
    "variants": [
      {"sku": "SKU-001-RED", "name": "Red", "attributes": {"color": "red"}},
      {"sku": "SKU-001-BLUE", "name": "Blue", "attributes": {"color": "blue"}}
    ]
  }
  ```

---

## 📊 METRYKI SUKCESU

**Performance:**
- Import 100 SKU: <3s (parsing)
- Create 100 PendingProducts: <10s (DB bulk insert)
- Publish 1 produkt: <2s (transaction)
- Bulk publish 50: <30s (queue)

**UX:**
- Completion % visible w <100ms (cached)
- Inline editing: <500ms (Livewire)
- Modal load: <300ms

**Business:**
- 80% produktów publikowanych w <5 min od importu
- <5% konfliktów SKU (dzięki validation)
- 100% audit trail (PublishHistory)

---

## 🎓 WNIOSKI

### ✅ Zalety Architektury:
1. **Separacja DRAFT vs PUBLISHED** - ProductList pozostaje clean
2. **SKU-FIRST pattern** - zgodność z całym systemem PPM
3. **Modular design** - łatwo dodać nowe import sources (ERP)
4. **Queue-based publish** - skalowalność dla bulk operations
5. **Audit trail** - pełna historia importów i publikacji

### ⚠️ Kluczowe Decyzje Architektoniczne:
1. **PendingProduct jako oddzielny model** (nie Product z flagą draft) - łatwiejsze zarządzanie lifecycle
2. **JSON dla variant_data i compatibility_data** - elastyczność bez dodatkowych tabel pivot
3. **ImportSession tracking** - grupowanie importów dla analytics
4. **PublishHistory jako CQRS pattern** - odczyt historii niezależny od write operations

### 🚀 Przyszłe Rozszerzenia:
1. **ERP Integration** - import z Baselinker/Subiekt GT/Dynamics (ETAP_08)
2. **Templates** - save import mappings jako templates
3. **Scheduled imports** - cron job dla automatic imports
4. **AI-powered matching** - auto-assign categories based na name/description
5. **Collaborative editing** - multiple users editing same PendingProduct (lock mechanism)

---

## 📁 PLIKI DO UTWORZENIA

**Summary:**
- **3 migrations** (pending_products, import_sessions, publish_history)
- **3 models** (PendingProduct, ImportSession, PublishHistory)
- **4 services** (ImportProcessor, ValidationService, PendingProductService, PublishService)
- **4 Livewire components** (PendingProductsList, ImportWizard, PendingProductForm, PublishHistoryList)
- **3 controllers** (ImportController, PendingProductsController, PublishController)
- **1 job** (PublishProductJob)
- **1 CSS** (import-panel.css)
- **~12 views** (blade templates)

**Szacowany czas implementacji:** 80 godzin (10 dni roboczych)

---

**KONIEC RAPORTU**
