# RAPORT ANALIZY I PLANU: ETAP_07 FAZA 2 - DWUKIERUNKOWA SYNCHRONIZACJA PRESTASHOP

**Data:** 2025-10-03
**Agent:** architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie:** Gap Analysis ETAP_07 FAZA 1 vs User Requirements + Plan FAZA 2

---

## 🎯 EXECUTIVE SUMMARY

### Wymagania Użytkownika (User Requirements)

User otworzył plan ETAP_07 i wyraził obawy że brakuje kluczowych funkcji synchronizacji dwukierunkowej:

**1. POBIERANIE Z PRESTASHOP → PPM (MISSING):**
- ❌ Pobieranie pojedynczego/wybranego produktu z PrestaShop do PPM
- ❌ Pobieranie wszystkich produktów z wybranej kategorii PrestaShop
- ❌ Pobieranie wszystkich produktów z PrestaShop
- ❌ Automatyczne utworzenie struktury kategorii pobranego produktu dla danego sklepu w PPM

**2. WYSYŁANIE Z PPM → PRESTASHOP (PARTIAL):**
- ✅ Wysłanie produktu utworzonego w PPM na PrestaShop (FAZA 1 COMPLETED)
- ⚠️ Ze wszystkimi parametrami PPM oraz kategoriami (PARTIAL - kategorie wybierane statycznie)
- ❌ Kategorie wybierane z zakładki/label sklepu w oknie edycji/dodawania produktu (MISSING)
- ❌ Kategorie i ich struktura dynamicznie pobierane z PrestaShop w real-time (MISSING)

### Status Obecny (FAZA 1 COMPLETED - 100%)

**✅ ZREALIZOWANE W FAZA 1:**
- ✅ Database models & migrations (3 tabele: shop_mappings, product_sync_status, sync_logs)
- ✅ API Clients (BasePrestaShopClient, PrestaShop8Client, PrestaShop9Client, Factory)
- ✅ Sync Strategies (ProductSyncStrategy, CategorySyncStrategy, ISyncStrategy interface)
- ✅ Transformers & Mappers (ProductTransformer, CategoryTransformer, PriceGroupMapper, WarehouseMapper)
- ✅ Queue Jobs (SyncProductToPrestaShop, BulkSyncProducts, SyncCategoryToPrestaShop)
- ✅ Service Orchestration (PrestaShopSyncService - 16 metod, 558 linii)
- ✅ Livewire UI Extensions (ShopManager integration - testConnection, syncShop, syncStatistics)
- ✅ Blade Views & Testing (SyncController - 17 active jobs, full UI operational)

**Deployed Files:** 28 production files (~4800+ linii kodu)
**Status:** Production ready - wszystkie komponenty deployed i verified na ppm.mpptrade.pl

### Gap Analysis - Kluczowe Luki

| Funkcjonalność | FAZA 1 Status | FAZA 2 Required | Priority |
|----------------|---------------|-----------------|----------|
| **Import produktów (PS→PPM)** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🔴 CRITICAL |
| **Import kategorii (PS→PPM)** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🔴 CRITICAL |
| **Dynamiczne ładowanie kategorii PS** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🔴 CRITICAL |
| **ProductForm shop tabs - kategorie PS** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🔴 CRITICAL |
| **Bulk import z kategorii PS** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🟡 HIGH |
| **Real-time category picker** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🟡 HIGH |
| **PrestaShop → PPM transformers** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🔴 CRITICAL |
| **Import progress tracking UI** | ❌ NOT IMPLEMENTED | ✅ REQUIRED | 🟢 MEDIUM |

---

## 📊 SZCZEGÓŁOWA GAP ANALYSIS

### ✅ IMPLEMENTED (FAZA 1 COMPLETED)

**1. Database Layer (100% Complete)**
- ✅ `shop_mappings` table - mapowania PPM ↔ PrestaShop (kategorie, grupy cenowe, magazyny)
- ✅ `product_sync_status` table - status synchronizacji per produkt per sklep
- ✅ `sync_logs` table - audit trail, request/response logging
- ✅ Indexes, foreign keys, constraints properly configured

**2. API Clients (100% Complete)**
- ✅ `BasePrestaShopClient` - abstract base z retry logic, error handling, logging
- ✅ `PrestaShop8Client` - v8 specific implementation
- ✅ `PrestaShop9Client` - v9 specific implementation z bulk operations
- ✅ `PrestaShopClientFactory` - factory pattern dla version selection
- ✅ `PrestaShopAPIException` - custom exception handling
- ✅ Basic Auth, timeout config, comprehensive logging
- ✅ Methods: `getProducts()`, `getProduct()`, `createProduct()`, `updateProduct()`, `deleteProduct()`

**3. Sync Strategies (100% Complete)**
- ✅ `ISyncStrategy` interface - contract dla sync strategies
- ✅ `ProductSyncStrategy` - PPM → PrestaShop product sync logic
- ✅ `CategorySyncStrategy` - category hierarchy sync
- ✅ Checksum calculation (MD5 hash) dla change detection
- ✅ Error handling, retry logic, database transactions

**4. Transformers & Mappers (100% Complete)**
- ✅ `ProductTransformer` - PPM Product → PrestaShop format transformation
- ✅ `CategoryTransformer` - category data transformation
- ✅ `CategoryMapper` - mapowanie kategorii PPM ↔ PrestaShop
- ✅ `PriceGroupMapper` - mapowanie grup cenowych
- ✅ `WarehouseMapper` - mapowanie magazynów
- ✅ Multi-language support (PL + EN)
- ✅ Price group mapping, stock mapping, category associations

**5. Queue System (100% Complete)**
- ✅ `SyncProductToPrestaShop` - async product sync job
- ✅ `BulkSyncProducts` - bulk sync (chunks of 10 products)
- ✅ `SyncCategoryToPrestaShop` - category sync job
- ✅ Priority queues (high/default based on is_featured)
- ✅ Retry mechanism (3 attempts, exponential backoff)
- ✅ Failed jobs handling, error logging

**6. Service Orchestration (100% Complete)**
- ✅ `PrestaShopSyncService` - orchestration layer (16 methods, 558 linii)
- ✅ Methods implemented:
  - Connection testing: `testConnection()`
  - Product sync: `syncProduct()`, `syncProductToAllShops()`, `queueProductSync()`, `queueBulkProductSync()`
  - Category sync: `syncCategory()`, `syncCategoryHierarchy()`
  - Status monitoring: `getSyncStatus()`, `getSyncStatistics()`, `getRecentSyncLogs()`, `getPendingSyncs()`
  - Utilities: `retryFailedSyncs()`, `resetSyncStatus()`, `needsSync()`

**7. UI Components (100% Complete)**
- ✅ `ShopManager` - zarządzanie sklepami PrestaShop (ETAP_04 integration)
- ✅ `SyncController` - monitoring synchronizacji, job management
- ✅ Connection testing UI, sync statistics dashboard
- ✅ Real-time job monitoring (17 active jobs displayed)
- ✅ Event handlers: syncQueued, connectionSuccess, connectionError

**FAZA 1 Achievement:** Kompletna jednokierunkowa synchronizacja PPM → PrestaShop z monitoring UI.

---

### ❌ MISSING - Critical for User Requirements

#### 1. **Product Import (PrestaShop → PPM) - NOT IMPLEMENTED**

**Status:** Marked as "FAZA 2" in current plan (lines 34-40)
**Required:** Single product import, bulk import, category-based import
**Impact:** **KRYTYCZNY** - User nie może pobrać produktów z PrestaShop do PPM

**Brakujące Komponenty:**
- ❌ API methods: `fetchProductFromPrestaShop(int $prestashopProductId, PrestaShopShop $shop)`
- ❌ API methods: `fetchProductsFromCategory(int $categoryId, PrestaShopShop $shop)`
- ❌ API methods: `fetchAllProducts(PrestaShopShop $shop, array $filters)`
- ❌ Reverse transformers: PrestaShop data → PPM Product model
- ❌ Mapping: PrestaShop categories → PPM categories (auto-create if missing)
- ❌ Mapping: PrestaShop attributes → PPM product fields
- ❌ Mapping: PrestaShop price groups → PPM price groups
- ❌ Mapping: PrestaShop stock/warehouses → PPM warehouses
- ❌ Queue job: `ImportProductsFromPrestaShop`
- ❌ Progress tracking dla long-running imports
- ❌ UI: "Import produkty" button w ShopManager
- ❌ UI: Modal z wyborem kategorii PrestaShop + filtry

**User Impact:**
- ❌ Nie można zsynchronizować istniejących produktów z PrestaShop → PPM
- ❌ Brak możliwości aktualizacji PPM danymi z PrestaShop
- ❌ Synchronizacja tylko jednokierunkowa (PPM → PS)

---

#### 2. **Category Import (PrestaShop → PPM) - NOT IMPLEMENTED**

**Status:** Not implemented
**Required:** Fetch category tree, create PPM categories, map per shop
**Impact:** **KRYTYCZNY** - Brak synchronizacji struktury kategorii PrestaShop

**Brakujące Komponenty:**
- ❌ API method: `fetchCategoryTree(PrestaShopShop $shop)`
- ❌ Recursive category import (parent → children, 5 levels deep)
- ❌ Auto-create PPM categories if not exist
- ❌ Create ShopMapping records (category mapping per shop)
- ❌ Handle category translations (PL + EN)
- ❌ Reverse transformer: `PrestaShopCategoryTransformer->transformToPPM()`

**User Impact:**
- ❌ User musi ręcznie tworzyć strukturę kategorii w PPM zgodną z PrestaShop
- ❌ Brak automatycznego mapowania kategorii przy imporcie produktów
- ❌ Risk of category mismatch między systemami

---

#### 3. **Dynamic Category Loading (Real-time) - NOT IMPLEMENTED**

**Status:** Not implemented
**Required:** Real-time fetch category tree from PrestaShop API w ProductForm
**Impact:** **KRYTYCZNY** - User Requirements: "Kategorie i ich struktura dynamicznie pobierane z PrestaShop w real-time"

**Brakujące Komponenty:**
- ❌ Livewire component: `PrestaShopCategoryPicker`
- ❌ AJAX endpoint: `/api/prestashop/categories/{shopId}`
- ❌ Cache categories per shop (15 min TTL)
- ❌ Render category tree in ProductForm shop tab
- ❌ "Odśwież kategorie" button (reload from API)

**User Impact:**
- ❌ User nie może wybrać kategorii PrestaShop bezpośrednio w ProductForm
- ❌ Brak real-time synchronizacji kategorii
- ❌ User Requirements: "Kategorie wybierane z zakładki/label sklepu w oknie edycji/dodawania produktu" - **NOT SATISFIED**

---

#### 4. **ProductForm Shop Tab Enhancement - NOT IMPLEMENTED**

**Status:** Not implemented
**Required:** Sekcja "Kategorie PrestaShop" per shop tab z dynamic picker
**Impact:** **KRYTYCZNY** - Główny User Requirement nie spełniony

**Brakujące Komponenty:**
- ❌ ProductForm: sekcja "Kategorie PrestaShop" per shop tab
- ❌ Dynamic category picker (fetch from shop API)
- ❌ Multi-select categories per shop
- ❌ Display mapped categories (PPM ↔ PrestaShop)
- ❌ Update ProductShopData.prestashop_categories (JSON field)

**Związane pliki (do aktualizacji):**
- `resources/views/livewire/products/product-form.blade.php` - shop tabs update
- `resources/views/livewire/products/components/prestashop-category-picker.blade.php` - nowy komponent
- `app/Http/Livewire/Products/PrestaShopCategoryPicker.php` - nowy Livewire component

**User Impact:**
- ❌ User Requirements główne wymaganie nie spełnione
- ❌ Brak możliwości wyboru kategorii PrestaShop w UI produktu
- ❌ Manual category mapping required

---

#### 5. **Import Products UI - NOT IMPLEMENTED**

**Status:** Not implemented
**Required:** UI dla bulk import produktów z PrestaShop
**Impact:** 🟡 HIGH - User Requirements: "Pobieranie wszystkich produktów z wybranej kategorii PrestaShop"

**Brakujące Komponenty:**
- ❌ ShopManager: "Import produkty" button per shop
- ❌ Modal: wybór kategorii PrestaShop + filters
- ❌ Import progress bar (Livewire polling)
- ❌ Success summary: X produktów zaimportowanych, Y błędów
- ❌ Error handling UI (partial imports)

**User Impact:**
- ❌ Brak UI dla bulk import z PrestaShop
- ❌ User musi ręcznie importować każdy produkt osobno (gdy funkcja zostanie zaimplementowana)

---

#### 6. **Reverse Transformers (PrestaShop → PPM) - NOT IMPLEMENTED**

**Status:** Not implemented
**Required:** Transformacja danych PrestaShop → PPM format
**Impact:** **KRYTYCZNY** - Niezbędne dla import funkcjonalności

**Brakujące Komponenty:**
- ❌ `PrestaShopProductTransformer->transformToPPM(array $psData, PrestaShopShop $shop): Product`
- ❌ `PrestaShopCategoryTransformer->transformToPPM(array $psData): Category`
- ❌ Price mapping (PrestaShop price rules → PPM price groups)
- ❌ Stock mapping (PrestaShop warehouses → PPM warehouses)
- ❌ Attribute mapping (PrestaShop features → PPM product fields)
- ❌ Multi-language handling (PL/EN language detection)

**User Impact:**
- ❌ Import funkcjonalność nie może działać bez transformers
- ❌ Risk of data loss/corruption przy ręcznej transformacji

---

#### 7. **Product Model Extensions - NOT IMPLEMENTED**

**Status:** Not implemented (partial models exist)
**Required:** Relations i methods dla PrestaShop import
**Impact:** 🟡 HIGH - Infrastruktura dla FAZA 2

**Brakujące Komponenty:**
- ❌ `Product->prestashopCategories()` - many-to-many relation per shop
- ❌ `Product->importFromPrestaShop(int $psProductId, PrestaShopShop $shop): bool`
- ❌ `Product::scopeImportedFrom($shopId)` - Eloquent scope
- ❌ `Category->prestashopMappings()` - ShopMapping relation per shop
- ❌ `Category->syncWithPrestaShop(PrestaShopShop $shop): bool`
- ❌ `Category::importTreeFromPrestaShop(PrestaShopShop $shop): array`

**User Impact:**
- ❌ Brak ORM-level support dla import operations
- ❌ Manual query handling required

---

### 📋 GAP SUMMARY

**TOTAL MISSING COMPONENTS:** 45+ plików/metod/funkcjonalności

**CRITICAL (Must Have for FAZA 2):**
- 🔴 Import produktów (PS→PPM) - 12 komponentów
- 🔴 Import kategorii (PS→PPM) - 6 komponentów
- 🔴 Dynamic category picker - 5 komponentów
- 🔴 ProductForm shop tabs extension - 3 komponenty
- 🔴 Reverse transformers - 6 komponentów

**HIGH (Important for User Experience):**
- 🟡 Bulk import UI - 4 komponenty
- 🟡 Model extensions - 6 metod
- 🟡 Import progress tracking - 3 komponenty

**MEDIUM (Nice to Have):**
- 🟢 Advanced filters dla import
- 🟢 Conflict resolution UI (dla bidirectional sync)
- 🟢 Real-time monitoring dashboard

---

## 🎯 PLAN FAZA 2: DWUKIERUNKOWA SYNCHRONIZACJA (PrestaShop → PPM)

### OVERVIEW

**Cel:** Kompletna dwukierunkowa komunikacja z PrestaShop (import + export)
**Zakres:** Import produktów, import kategorii, dynamic category picker, ProductForm extensions
**Estimated Effort:** 40-50 godzin (8-10 dni roboczych)
**Priority:** 🔴 CRITICAL - User Requirements nie spełnione bez FAZA 2

---

## 🔄 2.1 IMPORT PRODUKTÓW Z PRESTASHOP → PPM

**Status:** ⏳ PLANNED
**Priority:** 🔴 CRITICAL
**Estimated:** 15-18 godzin

### ❌ 2.1.1 Single Product Import

**Cel:** Pobranie pojedynczego produktu z PrestaShop do PPM z pełnym mapowaniem

#### ❌ 2.1.1.1 API method: fetchProductFromPrestaShop()
**File:** `app/Services/PrestaShop/BasePrestaShopClient.php` (extend)
**Lines:** ~80 linii

```php
public function fetchProductFromPrestaShop(int $prestashopProductId): array
{
    $endpoint = "/products/{$prestashopProductId}?display=full";
    $response = $this->makeRequest('GET', $endpoint);

    // Return PrestaShop product data (full schema)
    return $response['product'];
}
```

**Dependencies:**
- ✅ BasePrestaShopClient (exists)
- ✅ makeRequest() method (exists)
- ❌ Response schema validation (to implement)

---

#### ❌ 2.1.1.2 Transform PrestaShop product data → PPM Product model
**File:** `app/Services/PrestaShop/ProductTransformer.php` (extend)
**Lines:** ~150 linii (new method)

```php
public function transformToPPM(array $psData, PrestaShopShop $shop): Product
{
    // Reverse transformation: PrestaShop → PPM
    // Handle: name, description, price, stock, categories, attributes
    // Create/update Product model
    // Handle multi-language fields (PL/EN detection)

    return $product;
}
```

**Business Logic:**
- Map PrestaShop fields → PPM Product schema
- Language detection (id=1 → PL, id=2 → EN)
- Price extraction (PrestaShop specific_prices → PPM price_groups)
- Stock extraction (PrestaShop stock_availables → PPM stock table)
- Category mapping (use CategoryMapper->mapFromPrestaShop())
- Attribute mapping (PrestaShop features → PPM product fields)

**Edge Cases:**
- Missing fields in PrestaShop data (default values)
- Multi-language content (fallback to default language)
- Missing categories (auto-create or skip?)
- Price groups not mapped (warning log)

---

#### ❌ 2.1.1.3 Map PrestaShop categories → PPM categories (auto-create if missing)
**File:** `app/Services/PrestaShop/CategoryMapper.php` (extend)
**Lines:** ~60 linii (new method)

```php
public function ensureCategoryExists(int $prestashopCategoryId, PrestaShopShop $shop): ?Category
{
    // Check if mapping exists
    $mapping = $this->mapFromPrestaShop($prestashopCategoryId, $shop);

    if ($mapping) {
        return Category::find($mapping);
    }

    // Fetch category from PrestaShop API
    $psCategory = $shop->client->fetchCategoryFromPrestaShop($prestashopCategoryId);

    // Create PPM category
    $category = Category::create([
        'name' => $psCategory['name'][1]['value'], // PL
        'name_en' => $psCategory['name'][2]['value'] ?? null, // EN
        'parent_id' => $this->ensureParentCategory($psCategory['id_parent'], $shop),
        // ... other fields
    ]);

    // Create mapping
    $this->createMapping($category->id, $prestashopCategoryId, $shop);

    return $category;
}
```

**Recursive Logic:**
- Fetch parent categories recursively (up to 5 levels)
- Create PPM category hierarchy matching PrestaShop
- Create ShopMapping for each category (per shop)
- Handle category translations (PL + EN)

---

#### ❌ 2.1.1.4 Map PrestaShop attributes → PPM product fields
**File:** `app/Services/PrestaShop/AttributeMapper.php` (NEW)
**Lines:** ~100 linii

```php
<?php
namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use App\Models\ShopMapping;

class AttributeMapper
{
    public function mapAttributesToPPM(array $psFeatures, PrestaShopShop $shop): array
    {
        $mappedAttributes = [];

        foreach ($psFeatures as $feature) {
            // Map PrestaShop feature → PPM product field
            // Use ShopMapping (type: 'attribute')
            // Return associative array: field_name => value
        }

        return $mappedAttributes;
    }

    public function createAttributeMapping(string $psFeatureName, string $ppmFieldName, PrestaShopShop $shop): void
    {
        ShopMapping::updateOrCreate([
            'shop_id' => $shop->id,
            'mapping_type' => 'attribute',
            'ppm_value' => $ppmFieldName,
        ], [
            'prestashop_value' => $psFeatureName,
            'is_active' => true,
        ]);
    }
}
```

**Mapping Strategy:**
- PrestaShop features (color, size, weight, etc.) → PPM custom fields
- Admin-configurable mappings (ShopMapping table)
- Default mappings dla common attributes (weight, dimensions)
- Unmapped attributes → log warning, skip

---

#### ❌ 2.1.1.5 Handle price groups mapping
**File:** `app/Services/PrestaShop/PriceGroupMapper.php` (extend existing)
**Lines:** ~80 linii (reverse mapping method)

```php
public function mapFromPrestaShop(array $psPrices, PrestaShopShop $shop): array
{
    // PrestaShop specific_prices → PPM price_groups
    // Map customer groups → PPM price groups (detaliczna, dealer, etc.)
    // Return array: ['price_group_name' => price_value, ...]

    $ppmPrices = [];

    foreach ($psPrices as $psPrice) {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'price_group')
            ->where('prestashop_id', $psPrice['id_group'])
            ->first();

        if ($mapping) {
            $ppmPrices[$mapping->ppm_value] = $psPrice['price'];
        }
    }

    return $ppmPrices;
}
```

**Business Logic:**
- PrestaShop customer groups (1=Guest, 2=Customer, 3=B2B, etc.)
- PPM price groups (Detaliczna, Dealer Standard/Premium, Warsztat, etc.)
- Admin-configurable mapping via ShopMapping
- Default price fallback (PrestaShop base price → PPM 'Detaliczna')

---

#### ❌ 2.1.1.6 Handle stock/warehouse mapping
**File:** `app/Services/PrestaShop/WarehouseMapper.php` (extend existing)
**Lines:** ~80 linii (reverse mapping method)

```php
public function mapFromPrestaShop(array $psStockAvailables, PrestaShopShop $shop): array
{
    // PrestaShop stock_availables → PPM warehouse stocks
    // Map PrestaShop warehouses → PPM warehouses
    // Return array: ['warehouse_code' => quantity, ...]

    $ppmStock = [];

    foreach ($psStockAvailables as $psStock) {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'warehouse')
            ->where('prestashop_id', $psStock['id_warehouse'])
            ->first();

        if ($mapping) {
            $ppmStock[$mapping->ppm_value] = $psStock['quantity'];
        } else {
            // Default warehouse (MPPTRADE)
            $ppmStock['MPPTRADE'] = ($ppmStock['MPPTRADE'] ?? 0) + $psStock['quantity'];
        }
    }

    return $ppmStock;
}
```

**Warehouse Mapping:**
- PrestaShop warehouses → PPM warehouses (MPPTRADE, Pitbike.pl, Cameraman, etc.)
- Admin-configurable via ShopMapping
- Default: wszystkie stany → MPPTRADE (jeśli brak mapowania)

---

#### ❌ 2.1.1.7 Create ProductSyncStatus record (direction: ps_to_ppm)
**File:** `app/Services/PrestaShop/PrestaShopSyncService.php` (extend)
**Lines:** ~40 linii (new method)

```php
public function importProduct(int $prestashopProductId, PrestaShopShop $shop): Product
{
    $client = PrestaShopClientFactory::create($shop);

    // 1. Fetch product from PrestaShop
    $psData = $client->fetchProductFromPrestaShop($prestashopProductId);

    // 2. Transform to PPM format
    $transformer = app(ProductTransformer::class);
    $product = $transformer->transformToPPM($psData, $shop);

    // 3. Create ProductSyncStatus
    ProductSyncStatus::updateOrCreate([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
    ], [
        'prestashop_product_id' => $prestashopProductId,
        'sync_status' => 'synced',
        'sync_direction' => 'ps_to_ppm',
        'last_sync_at' => now(),
        'last_success_sync_at' => now(),
    ]);

    // 4. Log import
    SyncLog::create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'operation' => 'import_product',
        'direction' => 'ps_to_ppm',
        'status' => 'success',
        'message' => "Produkt #{$prestashopProductId} zaimportowany z PrestaShop",
    ]);

    return $product;
}
```

**Deployment:**
```
└──📁 PLIK: app/Services/PrestaShop/PrestaShopSyncService.php (update)
```

---

### ❌ 2.1.2 Bulk Product Import

**Cel:** Masowy import produktów z PrestaShop (kategoria/wszystkie)
**Priority:** 🟡 HIGH
**Estimated:** 8-10 godzin

#### ❌ 2.1.2.1 API method: fetchProductsFromCategory()
**File:** `app/Services/PrestaShop/BasePrestaShopClient.php` (extend)

```php
public function fetchProductsFromCategory(int $categoryId, array $filters = []): array
{
    $endpoint = "/products?filter[id_category_default]={$categoryId}&display=full";

    if (isset($filters['limit'])) {
        $endpoint .= "&limit={$filters['limit']}";
    }

    $response = $this->makeRequest('GET', $endpoint);
    return $response['products'];
}
```

---

#### ❌ 2.1.2.2 API method: fetchAllProducts()
**File:** `app/Services/PrestaShop/BasePrestaShopClient.php` (extend)

```php
public function fetchAllProducts(array $filters = []): array
{
    $allProducts = [];
    $page = 1;
    $limit = $filters['limit'] ?? 50;

    do {
        $endpoint = "/products?display=full&limit={$limit}&page={$page}";
        $response = $this->makeRequest('GET', $endpoint);

        $products = $response['products'];
        $allProducts = array_merge($allProducts, $products);

        $page++;
    } while (count($products) === $limit);

    return $allProducts;
}
```

**Pagination Strategy:**
- Fetch w chunks (default 50 products per page)
- Continue until no more products returned
- Progress tracking (page number)

---

#### ❌ 2.1.2.3 Queue job: ImportProductsFromPrestaShop
**File:** `app/Jobs/PrestaShop/ImportProductsFromPrestaShop.php` (NEW)
**Lines:** ~180 linii

```php
<?php
namespace App\Jobs\PrestaShop;

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopSyncService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProductsFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected PrestaShopShop $shop;
    protected ?int $categoryId;
    protected array $filters;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        PrestaShopShop $shop,
        ?int $categoryId = null,
        array $filters = []
    ) {
        $this->shop = $shop;
        $this->categoryId = $categoryId;
        $this->filters = $filters;

        $this->onQueue('prestashop_import');
    }

    public function handle(PrestaShopSyncService $syncService): void
    {
        $client = PrestaShopClientFactory::create($this->shop);

        // Fetch products
        if ($this->categoryId) {
            $products = $client->fetchProductsFromCategory($this->categoryId, $this->filters);
            $message = "Import z kategorii #{$this->categoryId}";
        } else {
            $products = $client->fetchAllProducts($this->filters);
            $message = "Import wszystkich produktów";
        }

        Log::info($message, [
            'shop_id' => $this->shop->id,
            'products_count' => count($products),
        ]);

        // Process in chunks
        $chunks = array_chunk($products, 10);
        $imported = 0;
        $errors = 0;

        foreach ($chunks as $chunk) {
            foreach ($chunk as $psProduct) {
                try {
                    $syncService->importProduct($psProduct['id'], $this->shop);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Product import failed', [
                        'prestashop_product_id' => $psProduct['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info("Import zakończony", [
            'shop_id' => $this->shop->id,
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk import failed', [
            'shop_id' => $this->shop->id,
            'category_id' => $this->categoryId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Deployment:**
```
└──📁 PLIK: app/Jobs/PrestaShop/ImportProductsFromPrestaShop.php
```

---

#### ❌ 2.1.2.4 Batch processing (chunks of 50 products)
**Implementation:** Built into ImportProductsFromPrestaShop job (see above)

**Chunk Strategy:**
- Fetch products w paginacji (50/page)
- Process w mniejszych chunks (10 products at a time)
- Prevent memory overflow dla large imports (1000+ products)

---

#### ❌ 2.1.2.5 Progress tracking dla long-running imports
**File:** `app/Models/ImportJob.php` (NEW)
**Lines:** ~80 linii

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $fillable = [
        'shop_id',
        'category_id',
        'total_products',
        'imported_products',
        'failed_products',
        'status', // pending, processing, completed, error
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function progress(): float
    {
        if ($this->total_products === 0) return 0;
        return round(($this->imported_products / $this->total_products) * 100, 2);
    }
}
```

**Migration:**
```php
Schema::create('import_jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_id')->constrained('prestashop_shops')->onDelete('cascade');
    $table->unsignedBigInteger('category_id')->nullable();
    $table->unsignedInteger('total_products')->default(0);
    $table->unsignedInteger('imported_products')->default(0);
    $table->unsignedInteger('failed_products')->default(0);
    $table->enum('status', ['pending', 'processing', 'completed', 'error'])->default('pending');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['shop_id', 'status']);
});
```

**Deployment:**
```
└──📁 PLIK: database/migrations/2025_10_04_000001_create_import_jobs_table.php
└──📁 PLIK: app/Models/ImportJob.php
```

---

#### ❌ 2.1.2.6 Error handling i partial imports (continue on error)
**Implementation:** Built into ImportProductsFromPrestaShop job

**Strategy:**
- Try-catch per product (single product failure doesn't stop entire import)
- Log każdy error (SyncLog + Laravel log)
- Update ImportJob.failed_products counter
- Continue processing remaining products
- Final summary: X imported, Y failed

---

### ❌ 2.1.3 Reverse Transformers (PrestaShop → PPM)

**Priority:** 🔴 CRITICAL (dependency dla 2.1.1, 2.1.2)
**Estimated:** 5-6 godzin

#### ❌ 2.1.3.1 PrestaShopProductTransformer->transformToPPM()
**Status:** Described in 2.1.1.2 (see above)

---

#### ❌ 2.1.3.2 PrestaShopCategoryTransformer->transformToPPM()
**File:** `app/Services/PrestaShop/CategoryTransformer.php` (extend)
**Lines:** ~100 linii

```php
public function transformToPPM(array $psCategoryData, PrestaShopShop $shop): Category
{
    // Extract category data from PrestaShop response
    $name = $psCategoryData['name'][1]['value']; // Language ID 1 = PL
    $nameEn = $psCategoryData['name'][2]['value'] ?? null; // Language ID 2 = EN
    $description = $psCategoryData['description'][1]['value'] ?? null;
    $descriptionEn = $psCategoryData['description'][2]['value'] ?? null;

    // Find parent category (recursive)
    $parentId = null;
    if ($psCategoryData['id_parent'] && $psCategoryData['id_parent'] != 1) {
        $parentMapping = app(CategoryMapper::class)->mapFromPrestaShop(
            $psCategoryData['id_parent'],
            $shop
        );

        if ($parentMapping) {
            $parentId = $parentMapping;
        } else {
            // Parent nie istnieje → fetch from PrestaShop recursively
            $client = PrestaShopClientFactory::create($shop);
            $psParent = $client->fetchCategoryFromPrestaShop($psCategoryData['id_parent']);
            $parent = $this->transformToPPM($psParent, $shop);
            $parentId = $parent->id;
        }
    }

    // Create/update PPM category
    $category = Category::updateOrCreate([
        'name' => $name,
        'parent_id' => $parentId,
    ], [
        'name_en' => $nameEn,
        'description' => $description,
        'description_en' => $descriptionEn,
        'is_active' => (bool)$psCategoryData['active'],
        // ... other fields
    ]);

    return $category;
}
```

**Deployment:**
```
└──📁 PLIK: app/Services/PrestaShop/CategoryTransformer.php (update)
```

---

#### ❌ 2.1.3.3 Price mapping (PrestaShop price rules → PPM price groups)
**Status:** Described in 2.1.1.5 (see PriceGroupMapper->mapFromPrestaShop())

---

#### ❌ 2.1.3.4 Stock mapping (PrestaShop warehouses → PPM warehouses)
**Status:** Described in 2.1.1.6 (see WarehouseMapper->mapFromPrestaShop())

---

## 🌳 2.2 IMPORT KATEGORII Z PRESTASHOP → PPM

**Status:** ⏳ PLANNED
**Priority:** 🔴 CRITICAL
**Estimated:** 8-10 godzin

### ❌ 2.2.1 Category Tree Sync

**Cel:** Pełna synchronizacja hierarchii kategorii PrestaShop → PPM

#### ❌ 2.2.1.1 API method: fetchCategoryTree()
**File:** `app/Services/PrestaShop/BasePrestaShopClient.php` (extend)

```php
public function fetchCategoryTree(PrestaShopShop $shop): array
{
    // Fetch all categories from PrestaShop
    $response = $this->makeRequest('GET', '/categories?display=full');
    $categories = $response['categories'];

    // Build hierarchical tree structure
    return $this->buildCategoryTree($categories);
}

protected function buildCategoryTree(array $categories): array
{
    $tree = [];
    $indexed = [];

    // Index categories by ID
    foreach ($categories as $category) {
        $indexed[$category['id']] = $category;
        $indexed[$category['id']]['children'] = [];
    }

    // Build parent-child relationships
    foreach ($indexed as $id => $category) {
        if ($category['id_parent'] == 1 || !isset($indexed[$category['id_parent']])) {
            // Root category
            $tree[] = &$indexed[$id];
        } else {
            // Child category
            $indexed[$category['id_parent']]['children'][] = &$indexed[$id];
        }
    }

    return $tree;
}
```

---

#### ❌ 2.2.1.2 Recursive category import (parent → children, 5 levels deep)
**File:** `app/Services/PrestaShop/PrestaShopSyncService.php` (extend)

```php
public function importCategoryTree(PrestaShopShop $shop): array
{
    $client = PrestaShopClientFactory::create($shop);
    $tree = $client->fetchCategoryTree($shop);

    $stats = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0,
    ];

    $this->importCategoryRecursive($tree, $shop, null, 0, $stats);

    return $stats;
}

protected function importCategoryRecursive(
    array $categories,
    PrestaShopShop $shop,
    ?int $parentId,
    int $level,
    array &$stats
): void {
    if ($level >= 5) {
        // Max 5 levels (PPM limit: Category, Category2, Category3, Category4, Category5)
        return;
    }

    foreach ($categories as $psCategory) {
        try {
            $transformer = app(CategoryTransformer::class);
            $category = $transformer->transformToPPM($psCategory, $shop);

            // Create ShopMapping
            app(CategoryMapper::class)->createMapping(
                $category->id,
                $psCategory['id'],
                $shop
            );

            $stats['imported']++;

            // Recursive import children
            if (!empty($psCategory['children'])) {
                $this->importCategoryRecursive(
                    $psCategory['children'],
                    $shop,
                    $category->id,
                    $level + 1,
                    $stats
                );
            }
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Category import failed', [
                'prestashop_category_id' => $psCategory['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Deployment:**
```
└──📁 PLIK: app/Services/PrestaShop/PrestaShopSyncService.php (update)
```

---

#### ❌ 2.2.1.3 Create PPM categories if not exist
**Status:** Built into CategoryTransformer->transformToPPM() (see 2.1.3.2)

---

#### ❌ 2.2.1.4 Create ShopMapping records (category mapping per shop)
**Status:** Built into CategoryMapper->createMapping() (existing method)

---

#### ❌ 2.2.1.5 Handle category translations (PL + EN)
**Status:** Built into CategoryTransformer->transformToPPM() (see 2.1.3.2)

**Language Mapping:**
- PrestaShop Language ID 1 → PL (name, description)
- PrestaShop Language ID 2 → EN (name_en, description_en)
- Fallback: jeśli EN brak → use PL

---

### ❌ 2.2.2 Dynamic Category Loading (Real-time)

**Cel:** Real-time ładowanie kategorii PrestaShop w ProductForm
**Priority:** 🔴 CRITICAL (User Requirement)
**Estimated:** 6-8 godzin

#### ❌ 2.2.2.1 Livewire component: PrestaShopCategoryPicker
**File:** `app/Http/Livewire/Products/PrestaShopCategoryPicker.php` (NEW)
**Lines:** ~150 linii

```php
<?php
namespace App\Http\Livewire\Products;

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class PrestaShopCategoryPicker extends Component
{
    public PrestaShopShop $shop;
    public array $selectedCategories = [];
    public array $categoryTree = [];
    public bool $loading = false;

    protected $listeners = ['refreshCategories' => 'loadCategories'];

    public function mount(PrestaShopShop $shop, array $selectedCategories = [])
    {
        $this->shop = $shop;
        $this->selectedCategories = $selectedCategories;
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $this->loading = true;

        $cacheKey = "prestashop_categories_{$this->shop->id}";

        $this->categoryTree = Cache::remember($cacheKey, 900, function () {
            // 15 min cache
            $client = PrestaShopClientFactory::create($this->shop);
            return $client->fetchCategoryTree($this->shop);
        });

        $this->loading = false;
    }

    public function refreshCategories(): void
    {
        // Force refresh (clear cache)
        Cache::forget("prestashop_categories_{$this->shop->id}");
        $this->loadCategories();

        $this->dispatch('categoryRefreshed', shop: $this->shop->id);
    }

    public function toggleCategory(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        } else {
            $this->selectedCategories[] = $categoryId;
        }

        $this->dispatch('categoriesUpdated', categories: $this->selectedCategories);
    }

    public function render()
    {
        return view('livewire.products.prestashop-category-picker');
    }
}
```

**Deployment:**
```
└──📁 PLIK: app/Http/Livewire/Products/PrestaShopCategoryPicker.php
```

---

#### ❌ 2.2.2.2 AJAX endpoint: /api/prestashop/categories/{shopId}
**File:** `routes/api.php` (extend)

```php
Route::prefix('prestashop')->group(function () {
    Route::get('/categories/{shopId}', [PrestaShopCategoryController::class, 'getCategories'])
        ->name('api.prestashop.categories');
});
```

**Controller:**
**File:** `app/Http/Controllers/API/PrestaShopCategoryController.php` (NEW)

```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PrestaShopCategoryController extends Controller
{
    public function getCategories(int $shopId): JsonResponse
    {
        $shop = PrestaShopShop::findOrFail($shopId);

        $cacheKey = "prestashop_categories_{$shopId}";

        $categories = Cache::remember($cacheKey, 900, function () use ($shop) {
            $client = PrestaShopClientFactory::create($shop);
            return $client->fetchCategoryTree($shop);
        });

        return response()->json([
            'success' => true,
            'shop_id' => $shopId,
            'categories' => $categories,
            'cached_at' => Cache::get($cacheKey . '_timestamp', now()),
        ]);
    }
}
```

**Deployment:**
```
└──📁 PLIK: routes/api.php (update)
└──📁 PLIK: app/Http/Controllers/API/PrestaShopCategoryController.php
```

---

#### ❌ 2.2.2.3 Cache categories per shop (15 min TTL)
**Status:** Built into PrestaShopCategoryPicker component (see 2.2.2.1)

**Cache Strategy:**
- Key: `prestashop_categories_{shop_id}`
- TTL: 15 minutes (900 seconds)
- Manual refresh: "Odśwież kategorie" button → Cache::forget()

---

#### ❌ 2.2.2.4 Render category tree in ProductForm shop tab
**File:** `resources/views/livewire/products/prestashop-category-picker.blade.php` (NEW)
**Lines:** ~120 linii

```blade
<div class="prestashop-category-picker">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Kategorie PrestaShop - {{ $shop->name }}</h3>

        <button
            wire:click="refreshCategories"
            wire:loading.attr="disabled"
            class="btn-enterprise-secondary"
        >
            <span wire:loading.remove>
                <i class="fas fa-sync-alt"></i> Odśwież kategorie
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin"></i> Ładowanie...
            </span>
        </button>
    </div>

    @if($loading)
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-ppm-gold"></i>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Pobieranie kategorii z PrestaShop...</p>
        </div>
    @else
        <div class="category-tree">
            @foreach($categoryTree as $category)
                @include('livewire.products.partials.category-node', [
                    'category' => $category,
                    'level' => 0,
                ])
            @endforeach
        </div>

        @if(empty($categoryTree))
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-folder-open text-4xl mb-4"></i>
                <p>Brak kategorii w sklepie PrestaShop</p>
            </div>
        @endif
    @endif

    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
        Wybrane kategorie: {{ count($selectedCategories) }}
    </div>
</div>
```

**Partial:** `resources/views/livewire/products/partials/category-node.blade.php`

```blade
<div class="category-node ml-{{ $level * 4 }} mb-2">
    <label class="flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded">
        <input
            type="checkbox"
            wire:click="toggleCategory({{ $category['id'] }})"
            @if(in_array($category['id'], $selectedCategories)) checked @endif
            class="form-checkbox h-5 w-5 text-ppm-gold"
        >
        <span class="ml-3 text-gray-800 dark:text-gray-200">
            {{ $category['name'][1]['value'] ?? $category['name'] }}
            <span class="text-xs text-gray-500">(ID: {{ $category['id'] }})</span>
        </span>
    </label>

    @if(!empty($category['children']))
        <div class="ml-4">
            @foreach($category['children'] as $child)
                @include('livewire.products.partials.category-node', [
                    'category' => $child,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
```

**Deployment:**
```
└──📁 PLIK: resources/views/livewire/products/prestashop-category-picker.blade.php
└──📁 PLIK: resources/views/livewire/products/partials/category-node.blade.php
```

---

## 🎨 2.3 UI EXTENSIONS - PRODUCT FORM SHOP TABS

**Status:** ⏳ PLANNED
**Priority:** 🔴 CRITICAL (User Requirement główne wymaganie)
**Estimated:** 10-12 godzin

### ❌ 2.3.1 ProductForm Shop Tab Enhancement

**Cel:** Dodanie sekcji "Kategorie PrestaShop" per shop tab w ProductForm

#### ❌ 2.3.1.1 Add "Kategorie PrestaShop" section per shop tab
**File:** `resources/views/livewire/products/product-form.blade.php` (update)
**Location:** W każdej zakładce sklepu (shop tab)
**Lines:** ~50 linii per shop tab

**Implementation:**
```blade
<!-- Existing ProductForm shop tabs -->
<div class="tabs-enterprise">
    @foreach($exportedShops as $shopId)
        <div x-show="activeShopTab === {{ $shopId }}" class="tab-content">

            <!-- EXISTING: Shop-specific product data -->
            <div class="enterprise-card mb-6">
                <h3>Dane produktu dla sklepu: {{ $shops->find($shopId)->name }}</h3>
                <!-- ... existing fields ... -->
            </div>

            <!-- NEW: PrestaShop Categories Section -->
            <div class="enterprise-card">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-folder-tree text-ppm-gold mr-2"></i>
                    Kategorie PrestaShop
                </h3>

                @livewire('products.prestashop-category-picker', [
                    'shop' => $shops->find($shopId),
                    'selectedCategories' => $shopData[$shopId]['prestashop_categories'] ?? [],
                ], key('category-picker-' . $shopId))

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-2"></i>
                    Wybrane kategorie zostaną użyte podczas synchronizacji produktu z PrestaShop
                </div>
            </div>

        </div>
    @endforeach
</div>
```

**Deployment:**
```
└──📁 PLIK: resources/views/livewire/products/product-form.blade.php (update)
```

---

#### ❌ 2.3.1.2 Dynamic category picker (fetch from shop API)
**Status:** Implemented in PrestaShopCategoryPicker component (see 2.2.2.1)

---

#### ❌ 2.3.1.3 Multi-select categories per shop
**Status:** Implemented in PrestaShopCategoryPicker component (see 2.2.2.1)

**Logic:**
- User clicks checkbox → toggleCategory(categoryId)
- $selectedCategories array updated
- Dispatch 'categoriesUpdated' event → ProductForm listens
- ProductForm updates $shopData[$shopId]['prestashop_categories']

---

#### ❌ 2.3.1.4 Display mapped categories (PPM ↔ PrestaShop)
**File:** `resources/views/livewire/products/product-form.blade.php` (extend)

**Add section pokazująca mapping:**
```blade
<div class="enterprise-card mt-4">
    <h4 class="text-md font-semibold mb-3">
        <i class="fas fa-link text-ppm-gold mr-2"></i>
        Zmapowane kategorie (PPM ↔ PrestaShop)
    </h4>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">PPM Kategoria:</p>
            <p class="text-sm">{{ $product->category->name ?? 'Brak' }}</p>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">PrestaShop Kategorie:</p>
            @if(!empty($shopData[$shopId]['prestashop_categories']))
                <ul class="text-sm list-disc list-inside">
                    @foreach($shopData[$shopId]['prestashop_categories'] as $psCategoryId)
                        <li>{{ $this->getPrestashopCategoryName($shopId, $psCategoryId) }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500">Brak wybranych kategorii</p>
            @endif
        </div>
    </div>
</div>
```

**ProductForm Component Method:**
```php
public function getPrestashopCategoryName(int $shopId, int $psCategoryId): string
{
    $cacheKey = "prestashop_categories_{$shopId}";
    $categories = Cache::get($cacheKey, []);

    // Find category name in tree (recursive search)
    return $this->findCategoryNameInTree($categories, $psCategoryId) ?? "Kategoria #{$psCategoryId}";
}

protected function findCategoryNameInTree(array $tree, int $categoryId): ?string
{
    foreach ($tree as $category) {
        if ($category['id'] == $categoryId) {
            return $category['name'][1]['value'] ?? $category['name'];
        }

        if (!empty($category['children'])) {
            $found = $this->findCategoryNameInTree($category['children'], $categoryId);
            if ($found) return $found;
        }
    }

    return null;
}
```

---

#### ❌ 2.3.1.5 "Odśwież kategorie" button (reload from API)
**Status:** Built into PrestaShopCategoryPicker component (see 2.2.2.1)

---

### ❌ 2.3.2 Import Products UI

**Cel:** UI dla bulk import produktów z PrestaShop w ShopManager

#### ❌ 2.3.2.1 ShopManager: "Import produkty" button per shop
**File:** `resources/views/livewire/admin/shops/shop-manager.blade.php` (update)

**Add button w shop list:**
```blade
<!-- Existing shop row -->
<tr>
    <td>{{ $shop->name }}</td>
    <td>{{ $shop->url }}</td>
    <td><!-- Status --></td>
    <td>
        <!-- Existing buttons: Edit, Test Connection, Sync -->

        <!-- NEW: Import Products button -->
        <button
            wire:click="openImportModal({{ $shop->id }})"
            class="btn-enterprise-secondary"
            title="Import produktów z PrestaShop"
        >
            <i class="fas fa-download"></i> Import produkty
        </button>
    </td>
</tr>
```

**ShopManager Component Method:**
```php
public bool $showImportModal = false;
public ?int $importShopId = null;

public function openImportModal(int $shopId): void
{
    $this->importShopId = $shopId;
    $this->showImportModal = true;
}
```

---

#### ❌ 2.3.2.2 Modal: wybór kategorii PrestaShop + filters
**File:** `resources/views/livewire/admin/shops/shop-manager.blade.php` (extend)

```blade
<!-- Import Products Modal -->
@if($showImportModal)
<div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ activeTab: 'single' }">
    <div class="flex items-center justify-center min-h-screen px-4">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-900 opacity-75" wire:click="$set('showImportModal', false)"></div>

        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    <i class="fas fa-download text-ppm-gold mr-2"></i>
                    Import produktów z PrestaShop
                </h2>

                <button wire:click="$set('showImportModal', false)" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Tabs -->
            <div class="tabs-enterprise mb-6">
                <button
                    @click="activeTab = 'single'"
                    :class="activeTab === 'single' ? 'active' : ''"
                    class="tab-button"
                >
                    Pojedynczy produkt
                </button>

                <button
                    @click="activeTab = 'category'"
                    :class="activeTab === 'category' ? 'active' : ''"
                    class="tab-button"
                >
                    Z kategorii
                </button>

                <button
                    @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'active' : ''"
                    class="tab-button"
                >
                    Wszystkie produkty
                </button>
            </div>

            <!-- Single Product Import -->
            <div x-show="activeTab === 'single'">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">PrestaShop Product ID</label>
                    <input
                        type="number"
                        wire:model="importProductId"
                        class="form-input w-full"
                        placeholder="np. 123"
                    >
                </div>

                <button wire:click="importSingleProduct" class="btn-enterprise-primary">
                    <i class="fas fa-download mr-2"></i> Importuj produkt
                </button>
            </div>

            <!-- Category Import -->
            <div x-show="activeTab === 'category'">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Wybierz kategorię PrestaShop</label>

                    @if($importShopId)
                        @livewire('products.prestashop-category-picker', [
                            'shop' => $shops->find($importShopId),
                            'selectedCategories' => [],
                            'singleSelect' => true,
                        ], key('import-category-picker'))
                    @endif
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="importIncludeSubcategories" class="form-checkbox">
                        <span class="ml-2">Uwzględnij podkategorie</span>
                    </label>
                </div>

                <button wire:click="importFromCategory" class="btn-enterprise-primary">
                    <i class="fas fa-download mr-2"></i> Importuj z kategorii
                </button>
            </div>

            <!-- All Products Import -->
            <div x-show="activeTab === 'all'">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
                        <div>
                            <p class="font-semibold text-yellow-800 dark:text-yellow-300">Uwaga!</p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                Import wszystkich produktów może potrwać długo (w zależności od ilości produktów w PrestaShop).
                                Operacja będzie wykonana w tle. Otrzymasz powiadomienie po zakończeniu.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Maksymalna liczba produktów (opcjonalnie)</label>
                    <input
                        type="number"
                        wire:model="importLimit"
                        class="form-input w-full"
                        placeholder="Brak limitu"
                    >
                </div>

                <button wire:click="importAllProducts" class="btn-enterprise-primary">
                    <i class="fas fa-download mr-2"></i> Rozpocznij import wszystkich produktów
                </button>
            </div>
        </div>
    </div>
</div>
@endif
```

---

#### ❌ 2.3.2.3 Import progress bar (Livewire polling)
**File:** `app/Http/Livewire/Admin/Shops/ShopManager.php` (extend)

```php
public ?int $activeImportJobId = null;

public function importSingleProduct(): void
{
    $shop = PrestaShopShop::find($this->importShopId);

    try {
        $syncService = app(PrestaShopSyncService::class);
        $product = $syncService->importProduct($this->importProductId, $shop);

        $this->dispatch('importSuccess', message: "Produkt #{$this->importProductId} zaimportowany pomyślnie");
        $this->showImportModal = false;
    } catch (\Exception $e) {
        $this->dispatch('importError', message: $e->getMessage());
    }
}

public function importFromCategory(): void
{
    $shop = PrestaShopShop::find($this->importShopId);
    $categoryId = $this->selectedCategories[0] ?? null;

    if (!$categoryId) {
        $this->dispatch('importError', message: 'Wybierz kategorię');
        return;
    }

    // Create ImportJob record
    $importJob = ImportJob::create([
        'shop_id' => $shop->id,
        'category_id' => $categoryId,
        'status' => 'pending',
    ]);

    // Dispatch queue job
    ImportProductsFromPrestaShop::dispatch($shop, $categoryId);

    $this->activeImportJobId = $importJob->id;
    $this->dispatch('importQueued', message: "Import rozpoczęty w tle");
}

public function importAllProducts(): void
{
    $shop = PrestaShopShop::find($this->importShopId);

    $importJob = ImportJob::create([
        'shop_id' => $shop->id,
        'category_id' => null,
        'status' => 'pending',
    ]);

    $filters = [];
    if ($this->importLimit) {
        $filters['limit'] = $this->importLimit;
    }

    ImportProductsFromPrestaShop::dispatch($shop, null, $filters);

    $this->activeImportJobId = $importJob->id;
    $this->dispatch('importQueued', message: "Import wszystkich produktów rozpoczęty");
}

public function getImportProgress()
{
    if (!$this->activeImportJobId) return null;

    $job = ImportJob::find($this->activeImportJobId);
    return $job ? $job->progress() : 0;
}
```

**Progress Bar UI:**
```blade
@if($activeImportJobId)
<div class="fixed bottom-4 right-4 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-4 w-96" wire:poll.1s="getImportProgress">
    <div class="flex justify-between items-center mb-2">
        <h4 class="font-semibold">Import w trakcie...</h4>
        <button wire:click="$set('activeImportJobId', null)" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 mb-2">
        <div
            class="bg-ppm-gold h-4 rounded-full transition-all duration-300"
            style="width: {{ $this->getImportProgress() }}%"
        ></div>
    </div>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Postęp: {{ $this->getImportProgress() }}%
    </p>
</div>
@endif
```

---

#### ❌ 2.3.2.4 Success summary: X produktów zaimportowanych, Y błędów
**Implementation:** Built into ImportProductsFromPrestaShop job (see 2.1.2.3)

**After job completion:**
```php
// In ImportProductsFromPrestaShop job handle()
Log::info("Import zakończony", [
    'shop_id' => $this->shop->id,
    'imported' => $imported,
    'errors' => $errors,
]);

// Update ImportJob status
ImportJob::where('id', $this->importJob->id)->update([
    'status' => 'completed',
    'imported_products' => $imported,
    'failed_products' => $errors,
    'completed_at' => now(),
]);

// Dispatch Livewire event
event(new ImportCompleted($this->importJob));
```

**UI Notification:**
```blade
<script>
Livewire.on('importCompleted', (data) => {
    Swal.fire({
        title: 'Import zakończony!',
        html: `
            <p>Produkty zaimportowane: <strong>${data.imported}</strong></p>
            <p>Błędy: <strong>${data.errors}</strong></p>
        `,
        icon: 'success',
    });
});
</script>
```

---

## 📦 2.4 MODELE I ROZSZERZENIA

**Status:** ⏳ PLANNED
**Priority:** 🟡 HIGH (infrastruktura dla FAZA 2)
**Estimated:** 4-6 godzin

### ❌ 2.4.1 Product Model Extensions

#### ❌ 2.4.1.1 Relation: prestashopCategories()
**File:** `app/Models/Product.php` (extend)

```php
/**
 * PrestaShop categories per shop (many-to-many through ProductShopData)
 */
public function prestashopCategories(int $shopId): array
{
    $shopData = ProductShopData::where('product_id', $this->id)
        ->where('shop_id', $shopId)
        ->first();

    return $shopData->prestashop_categories ?? [];
}
```

---

#### ❌ 2.4.1.2 Method: importFromPrestaShop()
**File:** `app/Models/Product.php` (extend)

```php
/**
 * Import product from PrestaShop (static factory method)
 */
public static function importFromPrestaShop(int $psProductId, PrestaShopShop $shop): self
{
    $syncService = app(PrestaShopSyncService::class);
    return $syncService->importProduct($psProductId, $shop);
}
```

---

#### ❌ 2.4.1.3 Scope: importedFrom()
**File:** `app/Models/Product.php` (extend)

```php
/**
 * Scope: produkty zaimportowane z danego sklepu PrestaShop
 */
public function scopeImportedFrom($query, int $shopId)
{
    return $query->whereHas('syncStatus', function ($q) use ($shopId) {
        $q->where('shop_id', $shopId)
          ->where('sync_direction', 'ps_to_ppm');
    });
}
```

---

### ❌ 2.4.2 Category Model Extensions

#### ❌ 2.4.2.1 Relation: prestashopMappings()
**File:** `app/Models/Category.php` (extend)

```php
/**
 * PrestaShop mappings per shop
 */
public function prestashopMappings()
{
    return $this->hasMany(ShopMapping::class, 'ppm_value', 'id')
        ->where('mapping_type', 'category');
}

/**
 * Get PrestaShop category ID dla danego sklepu
 */
public function getPrestashopId(int $shopId): ?int
{
    $mapping = $this->prestashopMappings()
        ->where('shop_id', $shopId)
        ->first();

    return $mapping?->prestashop_id;
}
```

---

#### ❌ 2.4.2.2 Method: syncWithPrestaShop()
**File:** `app/Models/Category.php` (extend)

```php
/**
 * Sync category to PrestaShop shop
 */
public function syncWithPrestaShop(PrestaShopShop $shop): bool
{
    $syncService = app(PrestaShopSyncService::class);
    return $syncService->syncCategory($this, $shop);
}
```

---

#### ❌ 2.4.2.3 Static: importTreeFromPrestaShop()
**File:** `app/Models/Category.php` (extend)

```php
/**
 * Import complete category tree from PrestaShop
 */
public static function importTreeFromPrestaShop(PrestaShopShop $shop): array
{
    $syncService = app(PrestaShopSyncService::class);
    return $syncService->importCategoryTree($shop);
}
```

---

## 📋 WORKFLOW SCENARIOS - IMPORT PRODUKTU Z PRESTASHOP

### **Scenariusz 1: Import pojedynczego produktu (User Manual)**

**Trigger:** User klika "Import produkty" → "Pojedynczy produkt" w ShopManager

**Steps:**
1. User otwiera modal import w ShopManager
2. User wybiera tab "Pojedynczy produkt"
3. User wpisuje PrestaShop Product ID (np. 123)
4. User klika "Importuj produkt"
5. **Backend:**
   - `ShopManager->importSingleProduct()` wywołane
   - `PrestaShopSyncService->importProduct(123, $shop)` wywołane
   - `PrestaShop8Client->fetchProductFromPrestaShop(123)` - API call
   - `ProductTransformer->transformToPPM($psData, $shop)` - transformacja danych
   - `CategoryMapper->ensureCategoryExists()` - auto-create kategorii jeśli nie istnieją
   - `PriceGroupMapper->mapFromPrestaShop()` - mapowanie cen
   - `WarehouseMapper->mapFromPrestaShop()` - mapowanie stanów
   - `Product::create()` - utworzenie produktu w PPM
   - `ProductSyncStatus::create()` - utworzenie statusu sync (direction: ps_to_ppm)
   - `SyncLog::create()` - log operacji
6. **Frontend:**
   - Success notification: "Produkt #123 zaimportowany pomyślnie"
   - Modal closes
   - Product list refreshes (jeśli jest otwarta)

**Edge Cases:**
- PrestaShop product nie istnieje (404) → Error notification
- API connection failed → Retry (3 attempts) → Error notification
- Category mapping brak → Auto-create category w PPM
- Duplicate product (SKU already exists) → Update existing product

**Expected Result:**
- ✅ Produkt utworzony w PPM z danymi z PrestaShop
- ✅ Kategorie auto-created (jeśli nie istniały)
- ✅ ProductSyncStatus record (direction: ps_to_ppm, status: synced)
- ✅ SyncLog entry

---

### **Scenariusz 2: Import wszystkich produktów z kategorii (Background Job)**

**Trigger:** User klika "Import produkty" → "Z kategorii" w ShopManager

**Steps:**
1. User otwiera modal import
2. User wybiera tab "Z kategorii"
3. **Dynamic category picker loads** (Livewire component):
   - `PrestaShopCategoryPicker->mount()` wywołane
   - Check cache: `prestashop_categories_{shop_id}`
   - If cache miss → `PrestaShop8Client->fetchCategoryTree()` - API call
   - Cache categories (TTL: 15 min)
   - Render category tree (hierarchical checkboxes)
4. User wybiera kategorię (np. "Części samochodowe", ID: 45)
5. User zaznacza "Uwzględnij podkategorie" (opcjonalnie)
6. User klika "Importuj z kategorii"
7. **Backend:**
   - `ShopManager->importFromCategory()` wywołane
   - `ImportJob::create()` - utworzenie job record (status: pending)
   - `ImportProductsFromPrestaShop::dispatch($shop, 45)` - queue job
   - Job ID returned → `$activeImportJobId = 123`
8. **Queue Processing (Background):**
   - `ImportProductsFromPrestaShop->handle()` wykonane
   - `PrestaShop8Client->fetchProductsFromCategory(45)` - API call (paginated)
   - Process w chunks (10 products at a time):
     - For each product:
       - `PrestaShopSyncService->importProduct($psProductId, $shop)`
       - Update `ImportJob` progress: `imported_products++`
   - Handle errors: `failed_products++`, continue processing
   - Update `ImportJob` status: completed
   - Dispatch event: `ImportCompleted`
9. **Frontend (Polling):**
   - Progress bar visible (bottom-right corner)
   - Livewire poll every 1s: `ShopManager->getImportProgress()`
   - Update progress bar: "Postęp: 45%"
   - On completion:
     - Sweet Alert: "Import zakończony! Produkty zaimportowane: 120, Błędy: 3"
     - Progress bar hidden

**Edge Cases:**
- Category nie istnieje w PrestaShop → Error notification
- Category empty (0 products) → Warning notification
- Partial import (niektóre produkty failed) → Continue, show summary
- Job timeout (>10 min) → Automatic retry (3 attempts)
- User closes browser → Job continues in background

**Expected Result:**
- ✅ 120 produktów zaimportowanych z kategorii 45
- ✅ 3 błędy (logged in SyncLog)
- ✅ ImportJob record (status: completed, imported: 120, failed: 3)
- ✅ User notification z summary

---

### **Scenariusz 3: Wybór kategorii PrestaShop w ProductForm (Real-time)**

**Trigger:** User edits product w ProductForm, otwiera zakładkę "Sklep X"

**Steps:**
1. User otwiera ProductForm (edit existing product lub create new)
2. User klika zakładkę "Sklep X" (np. "Pitbike.pl")
3. **Dynamic category picker loads automatically:**
   - `PrestaShopCategoryPicker` component rendered
   - Check cache: `prestashop_categories_{shop_id}`
   - If cache hit (< 15 min) → Use cached categories
   - If cache miss → `PrestaShop8Client->fetchCategoryTree()` - API call
   - Render hierarchical category tree
4. User widzi sekcję "Kategorie PrestaShop"
5. User rozwijfa drzewo kategorii, wybiera checkboxami:
   - [ ] Części samochodowe (ID: 45)
     - [x] Silnik (ID: 78) ← User zaznacza
     - [ ] Zawieszenie (ID: 79)
   - [ ] Akcesoria (ID: 50)
6. User clicks checkbox "Silnik" → `toggleCategory(78)` wywołane
7. **Component Logic:**
   - Add 78 to `$selectedCategories` array
   - Dispatch event: `categoriesUpdated` → ProductForm listens
   - ProductForm updates: `$shopData[$shopId]['prestashop_categories'] = [78]`
8. User widzi: "Wybrane kategorie: 1"
9. **Sekcja "Zmapowane kategorie" shows:**
   - PPM Kategoria: "Części" (existing PPM category)
   - PrestaShop Kategorie:
     - Silnik (ID: 78)
10. User clicks "Odśwież kategorie" button (opcjonalnie):
    - `PrestaShopCategoryPicker->refreshCategories()` wywołane
    - Cache cleared: `Cache::forget("prestashop_categories_{shop_id}")`
    - Fresh API call: `fetchCategoryTree()`
    - Category tree re-rendered
11. User saves product (clicks "Zapisz" button)
12. **Product Save Logic:**
    - `ProductForm->save()` executed
    - Update `ProductShopData`:
      ```php
      ProductShopData::updateOrCreate([
          'product_id' => $product->id,
          'shop_id' => $shopId,
      ], [
          'prestashop_categories' => json_encode([78]),
          // ... other shop-specific data
      ]);
      ```
    - If product already synced with this shop:
      - Trigger re-sync: `PrestaShopSyncService->queueProductSync($product, $shop)`
      - ProductSyncStatus updated: checksum changed, status: pending
13. **Sync Job Triggered (Background):**
    - `SyncProductToPrestaShop` job executed
    - Product sent to PrestaShop with category 78 assigned
    - ProductSyncStatus updated: status: synced

**Edge Cases:**
- PrestaShop API offline → Show cached categories (stale data warning)
- Cache expired + API offline → Error message, disable category picker
- User selects category that doesn't exist in PrestaShop anymore → Warning on save
- User selects 0 categories → Product sent to PrestaShop without category assignment (use default)

**Expected Result:**
- ✅ User selected PrestaShop category 78 in ProductForm
- ✅ ProductShopData.prestashop_categories = [78]
- ✅ Product saved
- ✅ Sync job triggered (if product already synced)
- ✅ PrestaShop product updated with category assignment

---

## ✅ DEPLOYMENT CHECKLIST - FAZA 2

### Prerequisites

- [ ] **FAZA 1 fully deployed and operational**
  - ✅ Database migrations (shop_mappings, product_sync_status, sync_logs)
  - ✅ API Clients (BasePrestaShopClient, Factory, v8/v9)
  - ✅ Transformers & Mappers (ProductTransformer, CategoryMapper, PriceGroupMapper, WarehouseMapper)
  - ✅ Queue Jobs (SyncProductToPrestaShop, BulkSyncProducts, SyncCategoryToPrestaShop)
  - ✅ Service Orchestration (PrestaShopSyncService - 16 methods)
  - ✅ UI Components (ShopManager, SyncController)

- [ ] **PrestaShop API access configured (v8 & v9)**
  - ✅ At least 1 PrestaShop shop configured in database
  - ✅ API keys valid and tested (testConnection passed)
  - ✅ API permissions: READ products, categories, stock

- [ ] **Category mappings table verified**
  - ✅ ShopMapping table exists
  - ✅ Indexes on (shop_id, mapping_type, ppm_value)
  - ✅ Foreign keys to prestashop_shops table

---

### Code Deployment

#### **2.1 Import Produktów (PrestaShop → PPM)**

- [ ] **API Methods (BasePrestaShopClient extend):**
  - [ ] `fetchProductFromPrestaShop(int $prestashopProductId): array`
  - [ ] `fetchProductsFromCategory(int $categoryId, array $filters = []): array`
  - [ ] `fetchAllProducts(array $filters = []): array`
  - [ ] `fetchCategoryFromPrestaShop(int $categoryId): array`

- [ ] **Reverse Transformers:**
  - [ ] `ProductTransformer->transformToPPM(array $psData, PrestaShopShop $shop): Product`
  - [ ] `CategoryTransformer->transformToPPM(array $psData, PrestaShopShop $shop): Category`
  - [ ] `PriceGroupMapper->mapFromPrestaShop(array $psPrices, PrestaShopShop $shop): array`
  - [ ] `WarehouseMapper->mapFromPrestaShop(array $psStock, PrestaShopShop $shop): array`

- [ ] **Attribute Mapper (NEW):**
  - [ ] File: `app/Services/PrestaShop/AttributeMapper.php`
  - [ ] Method: `mapAttributesToPPM(array $psFeatures, PrestaShopShop $shop): array`
  - [ ] Method: `createAttributeMapping(string $psFeatureName, string $ppmFieldName, PrestaShopShop $shop): void`

- [ ] **PrestaShopSyncService (extend):**
  - [ ] Method: `importProduct(int $prestashopProductId, PrestaShopShop $shop): Product`
  - [ ] Method: `importCategoryTree(PrestaShopShop $shop): array`

- [ ] **Queue Jobs:**
  - [ ] File: `app/Jobs/PrestaShop/ImportProductsFromPrestaShop.php`
  - [ ] Implements: ShouldQueue, Dispatchable, InteractsWithQueue, SerializesModels
  - [ ] Timeout: 600s (10 min)
  - [ ] Tries: 3
  - [ ] Queue: 'prestashop_import'

#### **2.2 Import Kategorii (PrestaShop → PPM)**

- [ ] **Category Tree Methods:**
  - [ ] `BasePrestaShopClient->fetchCategoryTree(PrestaShopShop $shop): array`
  - [ ] `BasePrestaShopClient->buildCategoryTree(array $categories): array` (protected)
  - [ ] `CategoryMapper->ensureCategoryExists(int $prestashopCategoryId, PrestaShopShop $shop): ?Category`
  - [ ] `CategoryMapper->ensureParentCategory(int $parentId, PrestaShopShop $shop): ?int` (protected)

- [ ] **Recursive Import:**
  - [ ] `PrestaShopSyncService->importCategoryRecursive()` (protected)
  - [ ] Max depth: 5 levels (PPM limit)
  - [ ] Auto-create categories if not exist
  - [ ] Auto-create ShopMapping records

#### **2.3 UI Extensions**

- [ ] **PrestaShopCategoryPicker Component:**
  - [ ] File: `app/Http/Livewire/Products/PrestaShopCategoryPicker.php`
  - [ ] View: `resources/views/livewire/products/prestashop-category-picker.blade.php`
  - [ ] Partial: `resources/views/livewire/products/partials/category-node.blade.php`
  - [ ] Properties: $shop, $selectedCategories, $categoryTree, $loading
  - [ ] Methods: loadCategories(), refreshCategories(), toggleCategory()
  - [ ] Listeners: 'refreshCategories'
  - [ ] Events: 'categoryRefreshed', 'categoriesUpdated'

- [ ] **ProductForm Extensions:**
  - [ ] View: `resources/views/livewire/products/product-form.blade.php` (update)
  - [ ] Add sekcja "Kategorie PrestaShop" per shop tab
  - [ ] Integrate PrestaShopCategoryPicker component
  - [ ] Add sekcja "Zmapowane kategorie" (PPM ↔ PrestaShop)
  - [ ] Component methods: `getPrestashopCategoryName()`, `findCategoryNameInTree()`

- [ ] **ShopManager Extensions:**
  - [ ] View: `resources/views/livewire/admin/shops/shop-manager.blade.php` (update)
  - [ ] Add "Import produkty" button per shop
  - [ ] Add import modal (3 tabs: single, category, all)
  - [ ] Add import progress bar (Livewire polling)
  - [ ] Component properties: $showImportModal, $importShopId, $importProductId, $activeImportJobId
  - [ ] Component methods: openImportModal(), importSingleProduct(), importFromCategory(), importAllProducts(), getImportProgress()

- [ ] **API Routes:**
  - [ ] File: `routes/api.php` (extend)
  - [ ] Route: `GET /api/prestashop/categories/{shopId}`
  - [ ] Controller: `app/Http/Controllers/API/PrestaShopCategoryController.php`
  - [ ] Method: `getCategories(int $shopId): JsonResponse`

#### **2.4 Models & Database**

- [ ] **ImportJob Model (NEW):**
  - [ ] Migration: `database/migrations/2025_10_04_000001_create_import_jobs_table.php`
  - [ ] Model: `app/Models/ImportJob.php`
  - [ ] Fillable: shop_id, category_id, total_products, imported_products, failed_products, status, started_at, completed_at, error_message
  - [ ] Relation: `shop()` → BelongsTo PrestaShopShop
  - [ ] Method: `progress(): float`

- [ ] **Product Model Extensions:**
  - [ ] File: `app/Models/Product.php` (extend)
  - [ ] Method: `prestashopCategories(int $shopId): array`
  - [ ] Static method: `importFromPrestaShop(int $psProductId, PrestaShopShop $shop): self`
  - [ ] Scope: `scopeImportedFrom($query, int $shopId)`

- [ ] **Category Model Extensions:**
  - [ ] File: `app/Models/Category.php` (extend)
  - [ ] Relation: `prestashopMappings()` → HasMany ShopMapping
  - [ ] Method: `getPrestashopId(int $shopId): ?int`
  - [ ] Method: `syncWithPrestaShop(PrestaShopShop $shop): bool`
  - [ ] Static method: `importTreeFromPrestaShop(PrestaShopShop $shop): array`

---

### Database

- [ ] **Run Migrations:**
  ```bash
  php artisan migrate --path=database/migrations/2025_10_04_000001_create_import_jobs_table.php
  ```

- [ ] **Verify Tables:**
  - [ ] `import_jobs` table exists
  - [ ] Columns: id, shop_id, category_id, total_products, imported_products, failed_products, status, started_at, completed_at, error_message, created_at, updated_at
  - [ ] Foreign key: shop_id → prestashop_shops.id (ON DELETE CASCADE)
  - [ ] Index: (shop_id, status)

- [ ] **Verify Existing Tables (from FAZA 1):**
  - [ ] `shop_mappings` - category mappings
  - [ ] `product_sync_status` - sync status records
  - [ ] `sync_logs` - audit trail

---

### Testing

#### **Unit Tests**

- [ ] **PrestaShopClient Tests:**
  - [ ] Test: `testCanFetchProductFromPrestaShop()`
  - [ ] Test: `testCanFetchProductsFromCategory()`
  - [ ] Test: `testCanFetchAllProducts()`
  - [ ] Test: `testCanFetchCategoryTree()`
  - [ ] Test: `testHandlesAPIErrorsGracefully()`

- [ ] **Transformer Tests:**
  - [ ] Test: `testTransformPrestaShopProductToPPM()`
  - [ ] Test: `testTransformPrestaShopCategoryToPPM()`
  - [ ] Test: `testMapPrestaShopPricesToPPM()`
  - [ ] Test: `testMapPrestaShopStockToPPM()`

- [ ] **Mapper Tests:**
  - [ ] Test: `testEnsureCategoryExists()` - creates if missing
  - [ ] Test: `testEnsureCategoryExists()` - uses existing if found
  - [ ] Test: `testMapAttributesToPPM()`

#### **Integration Tests**

- [ ] **Import Tests:**
  - [ ] Test: `testImportSingleProductFromPrestaShop()`
    - Verify: Product created in PPM
    - Verify: ProductSyncStatus created (direction: ps_to_ppm)
    - Verify: SyncLog entry
  - [ ] Test: `testImportProductsFromCategory()`
    - Verify: Multiple products imported
    - Verify: ImportJob created and updated
    - Verify: Progress tracking works
  - [ ] Test: `testImportCategoryTree()`
    - Verify: Categories created recursively (5 levels)
    - Verify: ShopMapping records created
    - Verify: Parent-child relationships preserved

- [ ] **UI Tests (Manual):**
  - [ ] Test: Open ProductForm → Shop tab → Category picker loads
  - [ ] Test: Select PrestaShop category → Save product → Verify ProductShopData updated
  - [ ] Test: Click "Odśwież kategorie" → Verify fresh data from API
  - [ ] Test: Open ShopManager → Click "Import produkty" → Modal opens
  - [ ] Test: Import single product → Verify success notification
  - [ ] Test: Import from category → Verify progress bar, final summary

#### **Edge Case Tests**

- [ ] **PrestaShop API Errors:**
  - [ ] Test: Product ID doesn't exist (404) → Error notification
  - [ ] Test: API connection timeout → Retry 3x → Error notification
  - [ ] Test: Invalid API key → Error notification

- [ ] **Data Edge Cases:**
  - [ ] Test: PrestaShop product missing required fields → Use defaults
  - [ ] Test: PrestaShop category doesn't exist → Auto-create
  - [ ] Test: Duplicate product (SKU exists) → Update existing
  - [ ] Test: Price group not mapped → Log warning, skip price

- [ ] **Performance:**
  - [ ] Test: Import 100+ products from category → Complete in <10 min
  - [ ] Test: Category tree with 5 levels depth → Correct hierarchy
  - [ ] Test: Cache categories → API call only on first load

---

### Performance

- [ ] **Cache Configuration:**
  - [ ] Cache key: `prestashop_categories_{shop_id}`
  - [ ] TTL: 15 minutes (900 seconds)
  - [ ] Manual refresh: "Odśwież kategorie" button clears cache

- [ ] **Queue Configuration:**
  - [ ] Queue: `prestashop_import`
  - [ ] Driver: Redis (fallback: database)
  - [ ] Priority: low (don't block PPM → PrestaShop sync)
  - [ ] Timeout: 600s (10 min per job)
  - [ ] Chunk size: 10 products per batch

- [ ] **Optimization:**
  - [ ] Bulk import uses pagination (50 products/page)
  - [ ] Process w chunks (10 products at a time)
  - [ ] Database transactions per product (isolate failures)
  - [ ] Minimal API calls (cache category tree)

---

### User Acceptance

- [ ] **User Can Import Single Product:**
  - [ ] Open ShopManager
  - [ ] Click "Import produkty" → "Pojedynczy produkt"
  - [ ] Enter PrestaShop Product ID
  - [ ] Click "Importuj produkt"
  - [ ] Verify: Success notification
  - [ ] Verify: Product appears in ProductList
  - [ ] Verify: ProductSyncStatus shows "synced" (direction: ps_to_ppm)

- [ ] **User Can Import All Products from Category:**
  - [ ] Open ShopManager
  - [ ] Click "Import produkty" → "Z kategorii"
  - [ ] Select PrestaShop category from tree
  - [ ] Click "Importuj z kategorii"
  - [ ] Verify: Progress bar appears
  - [ ] Verify: Progress updates in real-time (polling)
  - [ ] Verify: Summary notification (X imported, Y errors)
  - [ ] Verify: Products appear in ProductList

- [ ] **User Can Select PrestaShop Categories in ProductForm:**
  - [ ] Open ProductForm (edit product)
  - [ ] Click shop tab (np. "Pitbike.pl")
  - [ ] Verify: "Kategorie PrestaShop" section visible
  - [ ] Verify: Category tree loaded (hierarchical)
  - [ ] Select 1+ categories (checkboxes)
  - [ ] Verify: "Wybrane kategorie: X" updates
  - [ ] Click "Zapisz"
  - [ ] Verify: ProductShopData updated (prestashop_categories JSON)
  - [ ] Verify: If product synced → Sync job queued

- [ ] **Categories Refresh Dynamically:**
  - [ ] Open ProductForm → Shop tab
  - [ ] Click "Odśwież kategorie"
  - [ ] Verify: Loading indicator
  - [ ] Verify: Fresh data from PrestaShop API
  - [ ] Verify: Category tree re-rendered

- [ ] **Import Progress Visible:**
  - [ ] Start bulk import (100+ products)
  - [ ] Verify: Progress bar visible (bottom-right)
  - [ ] Verify: Progress updates every 1s (polling)
  - [ ] Verify: Percentage accurate (matches ImportJob.progress())
  - [ ] Wait for completion
  - [ ] Verify: Summary notification with stats

---

### Documentation

- [ ] **Update CLAUDE.md:**
  - [ ] Add FAZA 2 completion date
  - [ ] Update "ETAP_07 Status" → FAZA 2 COMPLETED
  - [ ] Document new components (PrestaShopCategoryPicker, ImportJob model)

- [ ] **Update ETAP_07 Plan:**
  - [ ] Mark wszystkie sekcje FAZA 2 jako ✅ COMPLETED
  - [ ] Add deployment dates per sekcja
  - [ ] Add file paths (└──📁 PLIK: ...)

- [ ] **Create User Guide:**
  - [ ] How to import single product
  - [ ] How to import products from category
  - [ ] How to select PrestaShop categories in ProductForm
  - [ ] How to refresh categories
  - [ ] Troubleshooting common issues

---

## 📈 ESTIMATED EFFORT - FAZA 2

### Time Breakdown (Hours)

| Sekcja | Tasks | Estimated Hours | Priority |
|--------|-------|----------------|----------|
| **2.1.1 Single Product Import** | API methods, transformers, mappers | 6-8h | 🔴 CRITICAL |
| **2.1.2 Bulk Product Import** | Queue jobs, progress tracking, ImportJob model | 8-10h | 🟡 HIGH |
| **2.1.3 Reverse Transformers** | ProductTransformer, CategoryTransformer, mappers | 5-6h | 🔴 CRITICAL |
| **2.2.1 Category Tree Sync** | Recursive import, auto-create categories | 4-5h | 🔴 CRITICAL |
| **2.2.2 Dynamic Category Loading** | PrestaShopCategoryPicker component, API endpoint | 6-8h | 🔴 CRITICAL |
| **2.3.1 ProductForm Extensions** | Shop tabs, category picker integration | 6-8h | 🔴 CRITICAL |
| **2.3.2 Import Products UI** | ShopManager modal, progress bar, polling | 4-6h | 🟡 HIGH |
| **2.4 Model Extensions** | Product, Category model methods, relations | 2-3h | 🟡 HIGH |
| **Testing & Debugging** | Unit tests, integration tests, edge cases | 8-10h | 🔴 CRITICAL |
| **Documentation** | User guide, code documentation, plan updates | 2-3h | 🟢 MEDIUM |

**TOTAL ESTIMATED:** 51-67 godzin (średnio 59 godzin)

**Recommended Timeline:** 10-12 dni roboczych (zakładając 5-6h/dzień)

---

### Propozycja Kolejności Implementacji (Priority-Based)

**FAZA 2A (CRITICAL - Week 1):**
1. ✅ 2.1.3 Reverse Transformers (5-6h) - DEPENDENCY dla wszystkiego
2. ✅ 2.1.1 Single Product Import (6-8h) - Core functionality
3. ✅ 2.2.1 Category Tree Sync (4-5h) - Needed dla import
4. ✅ 2.4 Model Extensions (2-3h) - Infrastructure

**FAZA 2B (CRITICAL - Week 2):**
5. ✅ 2.2.2 Dynamic Category Loading (6-8h) - User Requirement główne
6. ✅ 2.3.1 ProductForm Extensions (6-8h) - User Requirement główne
7. ✅ 2.1.2 Bulk Product Import (8-10h) - User Requirement

**FAZA 2C (HIGH - Week 2-3):**
8. ✅ 2.3.2 Import Products UI (4-6h) - UX enhancement
9. ✅ Testing & Debugging (8-10h) - Quality assurance
10. ✅ Documentation (2-3h) - Knowledge transfer

---

## 🎯 SUCCESS CRITERIA - FAZA 2

**FAZA 2 zostanie uznana za ukończoną gdy:**

### ✅ Functional Requirements

1. **Import Functionality:**
   - ✅ User może zaimportować pojedynczy produkt z PrestaShop do PPM (by PrestaShop Product ID)
   - ✅ User może zaimportować wszystkie produkty z wybranej kategorii PrestaShop
   - ✅ User może zaimportować wszystkie produkty z PrestaShop (z optional limit)
   - ✅ Kategorie PrestaShop auto-created w PPM jeśli nie istnieją (5 poziomów głębokości)
   - ✅ ProductSyncStatus utworzony z direction: ps_to_ppm

2. **Dynamic Category Picker:**
   - ✅ User może wybrać kategorie PrestaShop w ProductForm (per shop tab)
   - ✅ Kategorie ładowane dynamicznie z PrestaShop API (real-time)
   - ✅ Kategorie cache'owane (15 min TTL)
   - ✅ "Odśwież kategorie" button force-reload from API
   - ✅ Multi-select categories per shop

3. **ProductForm Integration:**
   - ✅ Sekcja "Kategorie PrestaShop" visible per shop tab
   - ✅ PrestaShopCategoryPicker component integrated
   - ✅ Selected categories saved to ProductShopData.prestashop_categories (JSON)
   - ✅ Mapped categories displayed (PPM ↔ PrestaShop)

4. **Import UI:**
   - ✅ "Import produkty" button w ShopManager per shop
   - ✅ Modal z 3 tabs: single, category, all
   - ✅ Progress bar dla long-running imports (Livewire polling)
   - ✅ Summary notification: X imported, Y errors

### ✅ Technical Requirements

5. **Code Quality:**
   - ✅ Wszystkie komponenty FAZA 2 deployed na produkcję
   - ✅ Zero errors w Laravel logs
   - ✅ Code follows Laravel 12.x best practices (Context7 verified)
   - ✅ PrestaShop API integration follows official docs (Context7 verified)
   - ✅ NO hardcoded values, NO mock data

6. **Testing:**
   - ✅ Unit tests pass (transformers, mappers, API clients)
   - ✅ Integration tests pass (import flows, category sync)
   - ✅ Edge cases handled (API errors, missing data, duplicates)
   - ✅ Manual UI testing completed (all scenarios)

7. **Performance:**
   - ✅ Bulk import 100+ products completes in <10 min
   - ✅ Category tree cached (15 min TTL)
   - ✅ API calls minimized (pagination, caching)
   - ✅ Queue system operational (prestashop_import queue)

8. **Documentation:**
   - ✅ ETAP_07 plan updated (wszystkie sekcje FAZA 2 marked ✅)
   - ✅ File paths dodane do planu (└──📁 PLIK: ...)
   - ✅ User guide created (import workflows)
   - ✅ Code documentation (PHPDoc comments)

### ✅ User Acceptance

9. **User Satisfaction:**
   - ✅ User confirmed: "Import produktów działa idealnie"
   - ✅ User confirmed: "Dynamic category picker działa jak należy"
   - ✅ User confirmed: "Wszystkie requirements spełnione"

---

## 🚀 DEPLOYMENT STRATEGY - FAZA 2

### Phase 1: Foundation (Days 1-3)

**Deploy:**
- Reverse transformers (ProductTransformer, CategoryTransformer, mappers)
- Single product import (API methods, PrestaShopSyncService extension)
- Category tree sync (recursive import, auto-create)
- Model extensions (Product, Category)

**Test:**
- Import single product z PrestaShop
- Verify: Product created w PPM
- Verify: Categories auto-created
- Verify: ProductSyncStatus (direction: ps_to_ppm)

**User Acceptance:** Import pojedynczego produktu działa poprawnie

---

### Phase 2: UI Integration (Days 4-7)

**Deploy:**
- PrestaShopCategoryPicker component
- API endpoint: /api/prestashop/categories/{shopId}
- ProductForm shop tabs extension
- Cache system (category tree, 15 min TTL)

**Test:**
- Open ProductForm → Shop tab → Category picker loads
- Select categories → Save product
- Verify: ProductShopData.prestashop_categories updated
- Click "Odśwież kategorie" → Verify fresh data

**User Acceptance:** Dynamic category picker w ProductForm działa idealnie

---

### Phase 3: Bulk Import (Days 8-10)

**Deploy:**
- ImportProductsFromPrestaShop queue job
- ImportJob model & migration
- ShopManager import modal (3 tabs)
- Progress tracking (Livewire polling)

**Test:**
- Import z kategorii (100+ products)
- Verify: Progress bar updates in real-time
- Verify: Summary notification accurate
- Verify: ImportJob record correct

**User Acceptance:** Bulk import działa poprawnie z progress tracking

---

### Phase 4: Testing & Documentation (Days 11-12)

**Deploy:**
- Unit tests
- Integration tests
- User guide documentation

**Test:**
- Run all tests (phpunit)
- Manual UI testing (all scenarios)
- Edge case testing (API errors, missing data)

**User Acceptance:** Wszystkie funkcje przetestowane i działające

---

## 📝 NOTES & RECOMMENDATIONS

### Architecture Decisions

1. **Cache Strategy:**
   - Category tree cached per shop (15 min TTL)
   - Rationale: Reduce PrestaShop API calls, improve UX
   - Trade-off: Stale data possible (manual refresh available)

2. **Queue Priority:**
   - Import jobs: low priority queue
   - Rationale: Don't block PPM → PrestaShop sync (export priority)
   - Exception: Single product import (synchronous, immediate feedback)

3. **Error Handling:**
   - Bulk import: continue on error (partial import)
   - Rationale: Some products may fail (missing data), don't fail entire import
   - User notified: summary with error count

4. **Category Auto-Create:**
   - Missing PrestaShop categories auto-created w PPM
   - Rationale: User expects seamless import (no manual category setup)
   - Risk: Category structure mismatch (PPM vs PrestaShop)
   - Mitigation: Log auto-created categories, admin review recommended

### Security Considerations

1. **API Credentials:**
   - PrestaShop API keys stored encrypted (database)
   - Never exposed in logs (masked in SyncLog)

2. **Input Validation:**
   - PrestaShop Product ID validated (integer, positive)
   - Category ID validated (exists in PrestaShop)
   - API responses sanitized (XSS prevention)

3. **Rate Limiting:**
   - Respect PrestaShop API limits (configurable per shop)
   - Implement exponential backoff (retry logic)
   - Monitor API usage (SyncLog entries)

### Performance Considerations

1. **Bulk Import Optimization:**
   - Pagination: 50 products per API call
   - Chunk processing: 10 products per batch
   - Database transactions: per product (isolate failures)
   - Prevent memory overflow (large imports)

2. **Category Tree Caching:**
   - Cache invalidation: manual ("Odśwież kategorie") + TTL (15 min)
   - Cache key per shop: `prestashop_categories_{shop_id}`
   - Alternative: Event-driven invalidation (on category sync)

3. **Database Indexes:**
   - ImportJob: (shop_id, status) - fast progress queries
   - ProductSyncStatus: (sync_direction, sync_status) - filter imports
   - ShopMapping: (shop_id, mapping_type, ppm_value) - fast lookups

### User Experience Enhancements

1. **Import Progress:**
   - Real-time updates (Livewire polling 1s)
   - Visual feedback (progress bar, percentage)
   - Estimated time remaining (optional enhancement)

2. **Error Messages:**
   - User-friendly messages (nie technical stack traces)
   - Actionable suggestions (np. "Check PrestaShop API key")
   - Link to troubleshooting guide

3. **Category Picker UX:**
   - Hierarchical display (indentation, parent-child clear)
   - Search/filter functionality (optional enhancement FAZA 3)
   - Collapse/expand categories (optional enhancement FAZA 3)

---

## 🔗 CROSS-REFERENCES

### Related ETAP Plans

- **ETAP_02 (Modele Bazy):** Product model, Category model, ProductShopData model
- **ETAP_04 (Panel Admin):** ShopManager component (ETAP_04 sekcja 2.1)
- **ETAP_05 (Produkty):** ProductForm component, CategoryTree
- **ETAP_08 (ERP Integracje):** Similar import patterns (BaseLinker, Subiekt GT)

### Related Documentation

- `_DOCS/ETAP_07_FAZA_1_Implementation_Plan.md` - FAZA 1 detailed plan
- `_DOCS/ETAP_07_Synchronization_Workflow.md` - Sync workflow (PPM → PrestaShop)
- `_DOCS/Struktura_Bazy_Danych.md` - Database schema (shop_mappings, product_sync_status, sync_logs)
- `_DOCS/Struktura_Plikow_Projektu.md` - File organization (Services/PrestaShop/, Jobs, Livewire)

### PrestaShop API References

- **Context7 Library:** `/prestashop/docs` (3289 snippets, Trust Score: 8.2)
- **API Endpoints Used:**
  - `GET /api/products/{id}?display=full` - fetch single product
  - `GET /api/products?filter[id_category_default]={categoryId}` - fetch products from category
  - `GET /api/products?display=full` - fetch all products (paginated)
  - `GET /api/categories?display=full` - fetch category tree
  - `GET /api/stock_availables?filter[id_product]={productId}` - fetch stock
  - `GET /api/specific_prices?filter[id_product]={productId}` - fetch prices

### Laravel Patterns Used (Context7)

- **Service Layer Pattern:** PrestaShopSyncService orchestration
- **Factory Pattern:** PrestaShopClientFactory (version selection)
- **Strategy Pattern:** ProductSyncStrategy, CategorySyncStrategy
- **Transformer Pattern:** ProductTransformer, CategoryTransformer
- **Repository Pattern:** ShopMapping (category/price/warehouse mappings)
- **Queue Jobs:** ImportProductsFromPrestaShop (ShouldQueue, Dispatchable)
- **Livewire Components:** PrestaShopCategoryPicker (reactive UI)

---

**KONIEC RAPORTU**

---

**AUTOR:** architect agent (Expert Planning Manager & Project Plan Keeper)
**DATA:** 2025-10-03
**STATUS:** ✅ COMPLETED - Gap Analysis & FAZA 2 Plan Ready for Review
**NASTĘPNY KROK:** Update Plan_Projektu/ETAP_07_Prestashop_API.md z sekcją FAZA 2
