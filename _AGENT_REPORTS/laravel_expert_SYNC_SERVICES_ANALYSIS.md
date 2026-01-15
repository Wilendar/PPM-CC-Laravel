# RAPORT ANALIZY: System Synchronizacji PrestaShop w PPM-CC-Laravel

**Data**: 2025-12-09
**Agent**: laravel-expert
**Zadanie**: Analiza istniejących Jobs i serwisów synchronizacji produktów z PrestaShop

---

## 1. ARCHITEKTURA SYSTEMU SYNCHRONIZACJI

### 1.1 Komponenty Główne

```
┌─────────────────────────────────────────────────────────────────┐
│                    ORCHESTRATION LAYER                          │
│  PrestaShopSyncService - Główny punkt wejścia dla Livewire     │
└────────────────┬────────────────────────────────────────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
    v            v            v
┌────────┐  ┌────────┐  ┌──────────┐
│ JOBS   │  │STRATEGY│  │TRANSFORMERS│
└────────┘  └────────┘  └──────────┘
```

**Główne komponenty:**
1. **PrestaShopSyncService** - Orchestration layer (fasada dla Livewire)
2. **Queue Jobs** - Background processing (SyncProductToPrestaShop, BulkSyncProducts, PullSingleProductFromPrestaShop)
3. **ProductSyncStrategy** - Strategia synchronizacji (implementuje logikę sync)
4. **ProductTransformer** - Transformacja PPM → PrestaShop XML
5. **PrestaShop8Client / PrestaShop9Client** - Klienty API (multi-version support)

### 1.2 Wzorce Projektowe

| Wzorzec | Zastosowanie | Lokalizacja |
|---------|--------------|-------------|
| **Strategy Pattern** | Sync strategies (ISyncStrategy) | `ProductSyncStrategy`, `CategorySyncStrategy` |
| **Factory Pattern** | API Client creation | `PrestaShopClientFactory::create()` |
| **Facade Pattern** | Orchestration layer | `PrestaShopSyncService` |
| **Repository Pattern** | Data access | Implicit via Eloquent |
| **Queue Pattern** | Async processing | Laravel Queue Jobs |

---

## 2. ANALIZA JOBS SYNCHRONIZACJI

### 2.1 SyncProductToPrestaShop Job

**Lokalizacja:** `app/Jobs/PrestaShop/SyncProductToPrestaShop.php`

**Odpowiedzialności:**
- ✅ Synchronizacja pojedynczego produktu PPM → PrestaShop
- ✅ Unique jobs (prevents duplicate syncs via `uniqueId()`)
- ✅ Exponential backoff retry (30s, 1min, 5min)
- ✅ Integration z ProductSyncStrategy
- ✅ SyncJob tracking (start/complete/fail)
- ✅ User attribution (auth()->id() captured w web context, NULL = SYSTEM w queue)

**Kluczowe cechy:**
```php
// Unique lock (prevents concurrent syncs)
public function uniqueId(): string {
    return "product_{$this->product->id}_shop_{$this->shop->id}";
}

// Backoff strategy
public function backoff(): array {
    return [30, 60, 300]; // 30s, 1min, 5min
}

// Constructor captures user + pending media changes
public function __construct(
    Product $product,
    PrestaShopShop $shop,
    ?int $userId = null,
    array $pendingMediaChanges = []
)
```

**Workflow:**
```
1. Create SyncJob record (status=pending)
2. Mark as "syncing" in ProductShopData
3. Call ProductSyncStrategy::syncToPrestaShop()
4. Update SyncJob with performance metrics
5. Mark ProductShopData as "synced" + update checksum
6. Log success/error to SyncLog
```

**KRYTYCZNA CECHA - Pending Media Changes:**
```php
// ETAP_07d (2025-12-02): Session is NOT available in queue context!
// Solution: Pass pendingMediaChanges as job parameter
$this->pendingMediaChanges = $pendingMediaChanges;

// In handle():
$result = $strategy->syncToPrestaShop(
    $this->product,
    $client,
    $this->shop,
    $this->pendingMediaChanges  // ← CRITICAL!
);
```

---

### 2.2 BulkSyncProducts Job

**Lokalizacja:** `app/Jobs/PrestaShop/BulkSyncProducts.php`

**Odpowiedzialności:**
- ✅ Dispatch individual SyncProductToPrestaShop jobs
- ✅ Priority handling (high/normal/low queues)
- ✅ Batch tracking z progress callbacks
- ✅ JobProgressService integration
- ✅ Memory efficient (chunks products)

**Kluczowe cechy:**
```php
// Priority grouping
private function groupProductsByPriority(): array {
    $grouped = ['high' => [], 'normal' => [], 'low' => []];
    foreach ($this->products as $product) {
        $priority = $this->getProductPriority($product);
        if ($priority <= 3) $grouped['high'][] = $product;
        elseif ($priority >= 7) $grouped['low'][] = $product;
        else $grouped['normal'][] = $product;
    }
    return $grouped;
}

// Batch with callbacks
$batch = Bus::batch($jobs)
    ->name($this->batchName)
    ->allowFailures() // Don't cancel entire batch
    ->then(fn() => $progressService->markCompleted($progressId))
    ->catch(fn($e) => $progressService->addError($progressId, $e))
    ->finally(fn() => $progressService->updateProgress($progressId))
    ->dispatch();
```

**Sync Modes (ETAP_07c FAZA 4):**
- `full_sync` - All data
- `prices_only` - Only prices (specific_prices)
- `stock_only` - Only stock (stock_availables)
- `descriptions_only` - Only descriptions
- `categories_only` - Only category associations

**JobProgressService Integration:**
```php
// Create progress tracking
$progressId = $progressService->createJobProgress(
    $this->job->getJobId(),
    $this->shop,
    $jobType,
    $this->products->count()
);

// Update metadata
$progressService->updateMetadata($progressId, [
    'sample_skus' => $sampleSkus,
    'sync_mode' => $this->syncMode,
]);
```

---

### 2.3 PullSingleProductFromPrestaShop Job (InstaPull)

**Lokalizacja:** `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php`

**Odpowiedzialności:**
- ✅ Fetch single product from PrestaShop → PPM
- ✅ ConflictResolver integration
- ✅ PrestaShopPriceImporter integration
- ✅ PrestaShopStockImporter integration
- ✅ Graceful 404 handling (unlink deleted products)

**Workflow:**
```
1. Get ProductShopData (prestashop_product_id)
2. Fetch product from PrestaShop API
3. Apply ConflictResolver (should_update? reasons?)
4. Update ProductShopData IF allowed
5. Import prices (specific_prices)
6. Import stock (stock_availables)
7. Handle 404: Clear prestashop_product_id (allow re-sync)
```

**ConflictResolver Strategies:**
```php
$resolution = $conflictResolver->resolve($shopData, $psData);

// Result:
// - should_update: bool
// - reason: 'ppm_wins', 'ps_wins', 'manual_review'
// - conflicts: array (field-level conflicts)
```

**Graceful 404 Handling:**
```php
if ($e->isNotFound()) {
    $shopData->update([
        'prestashop_product_id' => null,
        'sync_status' => 'not_synced',
        'last_sync_error' => 'Product deleted from PrestaShop (404)',
    ]);
    return; // Don't throw - job completed successfully
}
```

---

## 3. ANALIZA ProductSyncStrategy

**Lokalizacja:** `app/Services/PrestaShop/Sync/ProductSyncStrategy.php`

### 3.1 Główna Metoda: syncToPrestaShop()

**Parametry:**
```php
public function syncToPrestaShop(
    Model $model,                    // Product model
    BasePrestaShopClient $client,   // API client (8 or 9)
    PrestaShopShop $shop,            // Target shop
    array $pendingMediaChanges = [] // ETAP_07d (2025-12-02)
): array
```

**Workflow (12 kroków):**
```
1. validateBeforeSync() - SKU, name, is_active checks
2. getOrCreateSyncStatus() - ProductShopData record
3. needsSync() - Checksum-based change detection
4. Mark as "syncing" in ProductShopData
5. ProductTransformer::transformForPrestaShop()
6. CREATE or UPDATE decision (prestashop_product_id exists?)
7. detectChangedFields() for UPDATE (track what changed)
8. PrestaShop API call (createProduct/updateProduct)
9. calculateChecksum() + update ProductShopData
10. CategoryAssociationService::ensureProductCategories() (FIX 2025-11-27)
11. PrestaShopPriceExporter::exportPricesForProduct() (FIX 2025-11-14)
12. syncMediaIfEnabled() (ETAP_07d) + syncFeaturesIfEnabled() (ETAP_07e)
```

### 3.2 Checksum-Based Change Detection

**Problem:** Skąd wiedzieć czy produkt się zmienił?
**Rozwiązanie:** SHA-256 checksum obliczony z kluczowych pól

```php
public function calculateChecksum(Product $model, PrestaShopShop $shop): string {
    $data = [
        'sku' => $model->sku,
        'name' => $model->name,
        'short_description' => $model->short_description,
        'long_description' => $model->long_description,
        'weight' => $model->weight,
        'ean' => $model->ean,
        'is_active' => $model->is_active,

        // Shop-specific data
        'shop_name' => $shopData->name,
        'shop_short_description' => $shopData->short_description,

        // CRITICAL FIX (2025-11-18): Use PrestaShop IDs from mappings
        'categories' => $shopData->category_mappings['mappings'] ?? [],

        // CRITICAL FIX (2025-11-12): Include prices + stock
        'price_net' => $defaultPrice->price_net,
        'price_gross' => $defaultPrice->price_gross,
        'stock_quantity' => $warehouseMapper->calculateStockForShop($model, $shop),
    ];

    ksort($data);
    return hash('sha256', json_encode($data));
}
```

**KRYTYCZNE ISSUE (#12, #15):**
- ❌ **BUG**: Początkowo checksum NIE zawierał prices/stock
- ❌ **SYMPTOM**: "zmiana stanów magazynowych nie pojawia się w CHANGED FIELDS"
- ✅ **FIX**: Dodano prices + stock do checksum (2025-11-12)

### 3.3 Changed Fields Tracking (UPDATE only)

**Cel:** Pokazać użytkownikowi co dokładnie się zmieniło

```php
// Extract trackable fields
$syncedData = $this->extractTrackableFields($productData, $model, $shop);

// Compare with previous sync
$changedFields = $this->detectChangedFields(
    $previousSync->result_summary['synced_data'],
    $syncedData
);

// Result format:
// [
//   'price (brutto)' => ['old' => 100.00, 'new' => 120.00],
//   'quantity' => ['old' => 50, 'new' => 75],
//   'categories' => ['old' => [9, 15], 'new' => [9, 15, 800]],
// ]
```

**Trackable fields:**
- `sku`, `ean`, `active`
- `name`, `short_description`, `long_description`
- `price (netto)`, `price (brutto)` ← FIX #13 (user wanted BRUTTO)
- `weight`, `width`, `height`, `depth`
- `quantity` ← FIX #15 (calculate from PPM, not PS response!)
- `categories` (PrestaShop IDs)
- `manufacturer_name`, `tax_rules_group`

---

### 3.4 Media Sync Integration (ETAP_07d)

**Problem:** Jak synchronizować zdjęcia razem z produktem?
**Rozwiązanie:** `syncMediaIfEnabled()` wywoływane synchronicznie w ProductSyncStrategy

```php
protected function syncMediaIfEnabled(
    Product $product,
    PrestaShopShop $shop,
    int $externalId,
    array $pendingMediaChanges = [] // Passed from job!
): void {
    // Check SystemSetting
    if (!SystemSetting::get('media.auto_sync_on_product_sync', false)) {
        return;
    }

    // Filter changes for THIS shop
    $shopChanges = [];
    foreach ($pendingMediaChanges as $key => $action) {
        [$mediaId, $shopId] = explode(':', $key);
        if ((int) $shopId === $shop->id) {
            $shopChanges[(int) $mediaId] = $action;
        }
    }

    // REPLACE ALL STRATEGY (ETAP_07d)
    // - Delete ALL images from PrestaShop
    // - Upload ONLY selected images
    // - Set correct cover based on is_primary
    $result = $syncService->replaceAllImages($product, $shop, $selectedMedia);
}
```

**REPLACE ALL Strategy:**
1. Calculate FINAL desired state (which media SHOULD be synced)
2. Delete ALL existing images from PrestaShop
3. Upload ONLY selected images
4. Set cover image based on `is_primary`

**Pending Media Changes Format:**
```php
// Format: 'mediaId:shopId' => 'sync'|'unsync'
[
    '123:5' => 'sync',    // Media #123 for shop #5
    '456:5' => 'unsync',  // Media #456 for shop #5
    '789:8' => 'sync',    // Media #789 for shop #8
]
```

---

### 3.5 Features Sync Integration (ETAP_07e)

**Problem:** Jak synchronizować cechy produktu (features)?
**Rozwiązanie:** `syncFeaturesIfEnabled()` wywoływane synchronicznie

```php
protected function syncFeaturesIfEnabled(
    Product $product,
    PrestaShopShop $shop,
    int $externalId,
    BasePrestaShopClient $client
): void {
    // Check SystemSetting
    if (!SystemSetting::get('features.auto_sync_on_product_sync', true)) {
        return;
    }

    // Create PrestaShopFeatureSyncService with EXISTING client
    // CRITICAL FIX: Don't use app() DI - creates new client with empty shop!
    $transformer = new FeatureTransformer();
    $featureSyncService = new PrestaShopFeatureSyncService($client, $transformer);

    // Sync product features
    $result = $featureSyncService->syncProductFeatures($product, $shop, $externalId);
}
```

**PrestaShopFeatureSyncService Workflow:**
```
1. Load product features (productFeatures() relationship)
2. Build associations using FeatureTransformer
3. Get current product data from PS (GET-MODIFY-PUT pattern)
4. Update product with new features associations
```

**GET-MODIFY-PUT Pattern (CRITICAL):**
```php
// ETAP_07e FAZA 4.6 - CRITICAL FIX
// PrestaShop PUT replaces ENTIRE product
// We MUST preserve ALL existing fields!

// Start with ALL existing product data
$updateData = $existingProductData;

// Only override associations
$updateData['associations']['product_features'] = $associations;

// Remove read-only fields
unset($updateData['manufacturer_name'], $updateData['quantity'], ...);

// Update product
$this->client->updateProduct($psProductId, $updateData);
```

---

## 4. PENDING CHANGES HANDLING

### 4.1 Koncept "Pending Changes"

**Problem:** Użytkownik edytuje dane w ProductForm (Shop Tab), ale dane MUSZĄ pochodzić z PrestaShop!

**Rozwiązanie:** Pending Changes Pattern

```
┌─────────────────────────────────────────────────────────────┐
│  SHOP TAB w ProductForm                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  DANE DOMYŚLNE (PPM)    │  SKLEP: B2B Test DEV      │   │
│  ├─────────────────────────┼───────────────────────────┤   │
│  │  Name: "Example"        │  Name: "Example B2B"      │   │
│  │                         │  ↑ PENDING CHANGES         │   │
│  │                         │  Badge: "ZMIENIONY"        │   │
│  └─────────────────────────┴───────────────────────────┘   │
│                                                             │
│  [ZAPISZ] → Dispatches SyncProductToPrestaShop Job         │
└─────────────────────────────────────────────────────────────┘
```

**Workflow:**
1. User edits shop-specific data (name, description, categories)
2. ProductForm marks fields as "pending" (badge "ZMIENIONY" / "PENDING")
3. User clicks "ZAPISZ"
4. ProductForm saves changes to ProductShopData
5. ProductForm dispatches SyncProductToPrestaShop Job
6. Job syncs changes to PrestaShop
7. Badge changes to "ZSYNCHRONIZOWANY"

### 4.2 Pending Changes w Kodzie

**Livewire Component (ProductForm):**
```php
// File: app/Http/Livewire/Products/Management/ProductForm.php

protected function hasPendingChanges(int $shopId): bool {
    $shopData = $this->product->shopData()
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData) return false;

    return $shopData->sync_status === 'pending';
}
```

**ProductShopData Model:**
```php
// Sync status tracking
const STATUS_PENDING = 'pending';
const STATUS_SYNCING = 'syncing';
const STATUS_SYNCED = 'synced';
const STATUS_ERROR = 'error';
const STATUS_CONFLICT = 'conflict';

// Checksum tracking
protected $fillable = [
    'checksum',              // SHA-256 of synced data
    'last_push_at',          // ETAP_13: PPM → PrestaShop timestamp
    'last_pulled_at',        // ETAP_13: PrestaShop → PPM timestamp
];
```

---

## 5. FEATURES SYNC (ETAP_07e)

### 5.1 PrestaShopFeatureSyncService

**Lokalizacja:** `app/Services/PrestaShop/PrestaShopFeatureSyncService.php`

**Odpowiedzialności:**
- ✅ Sync feature types (FeatureType → ps_feature)
- ✅ Sync product features (ProductFeature → product associations)
- ✅ Import features from PrestaShop → PPM
- ✅ Conflict resolution (name collisions)

**Key Methods:**

| Metoda | Cel | Kierunek |
|--------|-----|----------|
| `syncFeatureTypes()` | Sync feature definitions | PPM → PS |
| `syncProductFeatures()` | Sync product features | PPM → PS |
| `importFeaturesFromPrestaShop()` | Import feature definitions | PS → PPM |
| `resolveConflicts()` | Handle name collisions | - |

**Mapping Table:**
```php
// PrestashopFeatureMapping (pivot table)
- feature_type_id (PPM FeatureType.id)
- shop_id (PrestaShopShop.id)
- prestashop_feature_id (ps_feature.id_feature)
- sync_direction (SYNC_PPM_TO_PS | SYNC_PS_TO_PPM | SYNC_BOTH)
- auto_create_values (bool)
- is_active (bool)
- last_synced_at
```

### 5.2 Feature Value Mapping

**FeatureValueMapper:**
```php
// Maps PPM feature values to PrestaShop feature_value IDs
// Handles multilang values (pl_PL, en_GB)
// Creates new values if missing
// Caches mappings for performance
```

**Transformation Flow:**
```
PPM ProductFeature
    ↓ (FeatureTransformer::buildProductFeaturesAssociations)
PrestaShop associations.product_features
    ↓ (Format: flat indexed array)
[
    ['id' => 1, 'id_feature_value' => 5],
    ['id' => 2, 'id_feature_value' => 10],
]
    ↓ (BasePrestaShopClient::buildXmlFromArray)
XML:
<product_features>
  <product_feature><id>1</id><id_feature_value>5</id_feature_value></product_feature>
  <product_feature><id>2</id><id_feature_value>10</id_feature_value></product_feature>
</product_features>
```

---

## 6. COMPATIBILITY SYSTEM (Vehicle Matching)

### 6.1 VehicleCompatibility Model

**Lokalizacja:** `app/Models/VehicleCompatibility.php`

**Struktura:**
```php
class VehicleCompatibility {
    protected $fillable = [
        'product_id',               // Part (czesc zamienna)
        'vehicle_model_id',         // Vehicle product (FK → products.id)
        'shop_id',                  // ETAP_05d: Per-shop compatibility
        'compatibility_attribute_id', // Oryginał/Zamiennik/Model
        'compatibility_source_id',  // Źródło: TecDoc/Manual/AI
        'verified',                 // Czy zweryfikowane przez człowieka
        'is_suggested',             // Added via SmartSuggestionEngine
        'confidence_score',         // AI confidence 0.00-1.00
        'metadata',                 // Additional JSON data
    ];
}
```

**Relationships:**
```php
public function product(): BelongsTo           // Part
public function vehicleProduct(): BelongsTo   // Vehicle (pojazd)
public function shop(): BelongsTo             // Per-shop
public function compatibilityAttribute()      // Oryginał/Zamiennik/Model
public function compatibilitySource()         // TecDoc/Manual/AI
public function verifier(): BelongsTo         // User who verified
```

**CRITICAL CHANGE (2025-12-08):**
- ❌ **BEFORE**: `vehicle_model_id` → `vehicle_models` table
- ✅ **AFTER**: `vehicle_model_id` → `products` table (type='pojazd')

---

### 6.2 SmartSuggestionEngine

**Lokalizacja:** `app/Services/Compatibility/SmartSuggestionEngine.php`

**Cel:** AI-powered suggestion system dla dopasowań

**Scoring Algorithm:**
```php
// Weights for each match type
const WEIGHT_BRAND_MATCH = 0.50;       // product.manufacturer == vehicle.brand
const WEIGHT_NAME_MATCH = 0.30;        // product.name CONTAINS vehicle.model
const WEIGHT_DESCRIPTION_MATCH = 0.10; // product.description CONTAINS vehicle
const WEIGHT_CATEGORY_MATCH = 0.10;    // matching category patterns

// Confidence threshold
const MIN_CONFIDENCE_THRESHOLD = 0.30;  // Below this = discard
const AUTO_APPLY_THRESHOLD = 0.90;      // Above this = auto-apply

// Max suggestions per product per shop
const MAX_SUGGESTIONS_PER_PRODUCT = 50;
```

**Workflow:**
```
1. Get existing compatibilities (to avoid duplicates)
2. Get eligible vehicles (filtered by shop's allowed brands)
3. For each vehicle:
   a. calculateScore(product, vehicle)
   b. Skip if below MIN_CONFIDENCE_THRESHOLD
   c. Create CompatibilitySuggestion record
4. Sort by confidence (highest first)
5. Auto-apply suggestions >= 0.90 (if enabled)
```

**Usage:**
```php
$engine = app(SmartSuggestionEngine::class);

// Generate suggestions for product
$suggestions = $engine->generateForProduct($product, $shop);

// Auto-apply high-confidence suggestions
$applied = $engine->applyHighConfidenceSuggestions($shop, $user);
```

---

### 6.3 ShopFilteringService

**Lokalizacja:** `app/Services/Compatibility/ShopFilteringService.php`

**Cel:** Per-shop vehicle filtering (different brands per shop)

**Business Rules:**
```php
// PrestaShopShop.allowed_vehicle_brands = null → all brands allowed
// PrestaShopShop.allowed_vehicle_brands = [] → no brands (compatibility disabled)
// PrestaShopShop.allowed_vehicle_brands = ["YCF", "KAYO"] → only these brands
```

**Key Methods:**
```php
// Get vehicles filtered by shop's brand restrictions
public function getFilteredVehicles(PrestaShopShop $shop): Collection;

// Get compatibility records for product in shop
public function getProductCompatibilities(Product $product, PrestaShopShop $shop): Collection;

// Get products compatible with vehicle in shop
public function getCompatibleProducts(Product $vehicleProduct, PrestaShopShop $shop): Collection;

// Copy compatibilities from one shop to another
public function copyCompatibilities(
    PrestaShopShop $sourceShop,
    PrestaShopShop $targetShop,
    bool $overwrite = false
): int;
```

**Caching:**
```php
// Cache TTL: 1 hour
const CACHE_TTL = 3600;

// Cached keys:
// - "shop_{$shopId}_filtered_vehicles"
// - "shop_{$shopId}_vehicle_count"
// - "vehicle_brands_all"
```

---

## 7. INTEGRACJA SYNC DOPASOWAŃ Z ISTNIEJĄCYMI JOBS

### 7.1 Obecna Sytuacja

**Status Quo:**
- ✅ VehicleCompatibility model istnieje (per-shop)
- ✅ SmartSuggestionEngine generuje sugestie
- ✅ ShopFilteringService filtruje pojazdy per-shop
- ❌ **BRAK** synchronizacji dopasowań do PrestaShop

**Co synchronizujemy obecnie:**
1. Product data (name, description, price, stock)
2. Categories (associations.categories)
3. Media (images)
4. Features (associations.product_features)

**Co NIE synchronizujemy:**
- ❌ Vehicle Compatibility (dopasowania Oryginał/Zamiennik/Model)

---

### 7.2 Propozycja Integracji

#### 7.2.1 PrestaShop Features dla Dopasowań

**ROZWIĄZANIE:** Wykorzystać istniejący system features!

```
PPM VehicleCompatibility
    ↓
PrestaShop product_features
    ↓
Feature Groups:
    - "Oryginał" (id=1)
    - "Zamiennik" (id=2)
    - "Model" (id=3)

Feature Values:
    - "YCF F125 (2019-2023)" (id=100)
    - "KAYO T2 250 (2020-2024)" (id=101)
    - ...
```

**Mapping Strategy:**

| PPM Concept | PrestaShop Equivalent | Implementacja |
|-------------|----------------------|---------------|
| CompatibilityAttribute | FeatureType (e.g. "Oryginał") | PrestashopFeatureMapping |
| Vehicle Model | Feature Value (e.g. "YCF F125") | FeatureValueMapper |
| Product Compatibility | associations.product_features | ProductSyncStrategy |

**Przykład:**
```php
// PPM:
VehicleCompatibility {
    product_id: 123,
    vehicle_model_id: 456,  // Vehicle: "YCF F125 (2019-2023)"
    compatibility_attribute_id: 1,  // "Oryginał"
    shop_id: 5,
}

// PrestaShop:
{
    "product": {
        "id": 789,
        "associations": {
            "product_features": [
                {
                    "id": 1,               // Feature "Oryginał"
                    "id_feature_value": 100 // Value "YCF F125 (2019-2023)"
                }
            ]
        }
    }
}
```

---

#### 7.2.2 Nowy Serwis: VehicleCompatibilitySyncService

**Lokalizacja (propozycja):** `app/Services/PrestaShop/VehicleCompatibilitySyncService.php`

**Odpowiedzialności:**
```php
class VehicleCompatibilitySyncService {
    /**
     * Sync vehicle compatibilities to PrestaShop product_features
     *
     * @param Product $product Product to sync
     * @param PrestaShopShop $shop Target shop
     * @param int $psProductId PrestaShop product ID
     * @return array Stats: ['synced' => int, 'skipped' => int, 'errors' => array]
     */
    public function syncCompatibilitiesToFeatures(
        Product $product,
        PrestaShopShop $shop,
        int $psProductId
    ): array;

    /**
     * Build feature associations from vehicle compatibilities
     *
     * @param Collection $compatibilities VehicleCompatibility records
     * @param PrestaShopShop $shop Shop for feature mapping
     * @return array PrestaShop associations format
     */
    protected function buildCompatibilityAssociations(
        Collection $compatibilities,
        PrestaShopShop $shop
    ): array;

    /**
     * Ensure compatibility feature types exist in PrestaShop
     *
     * @param PrestaShopShop $shop Shop to sync
     * @return array Map of attribute_id => ps_feature_id
     */
    public function ensureCompatibilityFeatures(PrestaShopShop $shop): array;
}
```

---

#### 7.2.3 Modyfikacja ProductSyncStrategy

**Lokalizacja:** `app/Services/PrestaShop/Sync/ProductSyncStrategy.php`

**Zmiana:** Dodaj wywołanie sync compatibility w metodzie `syncToPrestaShop()`

```php
// AFTER syncFeaturesIfEnabled() (line 1089)

/**
 * Sync vehicle compatibilities to PrestaShop if enabled (ETAP_05d)
 *
 * Synchronizes vehicle compatibility records (Oryginał/Zamiennik/Model) to PrestaShop:
 * - Maps PPM CompatibilityAttributes to PrestaShop features
 * - Creates/updates feature values for vehicle models
 * - Updates product associations with compatibility features
 *
 * Called synchronously during product sync for consistency.
 * Non-blocking: errors are logged but don't fail the product sync.
 *
 * @param Product $product PPM Product
 * @param PrestaShopShop $shop Target shop
 * @param int $externalId PrestaShop product ID
 * @param BasePrestaShopClient $client PrestaShop API client
 */
protected function syncCompatibilitiesIfEnabled(
    Product $product,
    PrestaShopShop $shop,
    int $externalId,
    BasePrestaShopClient $client
): void {
    try {
        // Check if compatibility sync is enabled in SystemSettings
        $compatSyncEnabled = \App\Models\SystemSetting::get(
            'compatibility.auto_sync_on_product_sync',
            true
        );

        if (!$compatSyncEnabled) {
            Log::debug('[COMPATIBILITY SYNC] Auto-sync disabled, skipping', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return;
        }

        // Load product compatibilities for this shop
        $compatibilities = \App\Models\VehicleCompatibility::byProduct($product->id)
            ->byShop($shop->id)
            ->with(['vehicleProduct', 'compatibilityAttribute'])
            ->get();

        if ($compatibilities->isEmpty()) {
            Log::debug('[COMPATIBILITY SYNC] No compatibilities to sync for product', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);
            return;
        }

        Log::info('[COMPATIBILITY SYNC] Starting compatibility sync for product', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'compatibilities_count' => $compatibilities->count(),
        ]);

        // Create VehicleCompatibilitySyncService
        $compatSyncService = new \App\Services\PrestaShop\VehicleCompatibilitySyncService(
            $client
        );

        // Sync compatibilities to PrestaShop features
        $result = $compatSyncService->syncCompatibilitiesToFeatures(
            $product,
            $shop,
            $externalId
        );

        Log::info('[COMPATIBILITY SYNC] Compatibility sync completed', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'success' => empty($result['errors']),
            'synced_count' => $result['synced'] ?? 0,
            'errors_count' => count($result['errors'] ?? []),
        ]);

    } catch (\Exception $e) {
        // Log error but don't fail product sync (non-blocking)
        Log::error('[COMPATIBILITY SYNC] Failed during compatibility sync (non-fatal)', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
```

**Zmiana w syncToPrestaShop():**
```php
// Line 1089 (after syncFeaturesIfEnabled)
$this->syncFeaturesIfEnabled($model, $shop, $externalId, $client);

// NEW: Sync vehicle compatibilities
$this->syncCompatibilitiesIfEnabled($model, $shop, $externalId, $client);
```

---

#### 7.2.4 SystemSetting Integration

**Dodaj nowe ustawienie:**
```php
// database/seeders/SystemSettingsSeeder.php

[
    'key' => 'compatibility.auto_sync_on_product_sync',
    'value' => true,
    'type' => 'boolean',
    'group' => 'prestashop',
    'label' => 'Auto-sync vehicle compatibilities with products',
    'description' => 'Automatically sync vehicle compatibility records (Oryginał/Zamiennik/Model) to PrestaShop features when product is synced',
    'is_sensitive' => false,
    'validation_rules' => 'boolean',
]
```

---

#### 7.2.5 Database Migrations

**Feature Mappings dla Compatibility Attributes:**
```php
// Migration: 2025_12_09_000001_create_compatibility_feature_mappings.php

Schema::create('compatibility_feature_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('compatibility_attribute_id')
        ->constrained('compatibility_attributes')
        ->onDelete('cascade');
    $table->foreignId('shop_id')
        ->constrained('prestashop_shops')
        ->onDelete('cascade');
    $table->integer('prestashop_feature_id')->nullable();
    $table->string('prestashop_feature_name')->nullable();
    $table->enum('sync_direction', ['ppm_to_ps', 'ps_to_ppm', 'both'])
        ->default('both');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();

    $table->unique(['compatibility_attribute_id', 'shop_id']);
    $table->index('prestashop_feature_id');
});
```

**Feature Value Mappings dla Vehicle Models:**
```php
// Migration: 2025_12_09_000002_create_vehicle_feature_value_mappings.php

Schema::create('vehicle_feature_value_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_model_id')  // FK → products.id (type=pojazd)
        ->constrained('products')
        ->onDelete('cascade');
    $table->foreignId('shop_id')
        ->constrained('prestashop_shops')
        ->onDelete('cascade');
    $table->integer('prestashop_feature_id');
    $table->integer('prestashop_feature_value_id')->nullable();
    $table->string('vehicle_label')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();

    $table->unique(['vehicle_model_id', 'shop_id', 'prestashop_feature_id'],
        'vehicle_shop_feature_unique');
    $table->index('prestashop_feature_value_id');
});
```

---

#### 7.2.6 Workflow Example

**Scenariusz:** Produkt "Hamulec przedni" pasuje do "YCF F125 (2019-2023)" jako Oryginał

**PPM Data:**
```php
Product {
    id: 123,
    sku: "HAM-PRZOD-001",
    name: "Hamulec przedni",
}

VehicleCompatibility {
    product_id: 123,
    vehicle_model_id: 456,  // Vehicle: "YCF F125 (2019-2023)"
    shop_id: 5,             // B2B Test DEV
    compatibility_attribute_id: 1,  // "Oryginał"
}
```

**Sync Flow:**
```
1. ProductForm: User saves product
2. dispatchSyncJobsForAllShops() dispatches SyncProductToPrestaShop
3. SyncProductToPrestaShop::handle()
4. ProductSyncStrategy::syncToPrestaShop()
5. syncFeaturesIfEnabled() - syncs regular features
6. syncCompatibilitiesIfEnabled() - NEW!
   a. Load VehicleCompatibility records for shop
   b. Ensure CompatibilityAttribute "Oryginał" mapped to PS feature (e.g. id=50)
   c. Ensure Vehicle "YCF F125" mapped to PS feature_value (e.g. id=200)
   d. Build associations:
      {
          "id": 50,                 // Feature "Oryginał"
          "id_feature_value": 200   // Value "YCF F125 (2019-2023)"
      }
   e. GET-MODIFY-PUT pattern (append to existing product_features)
   f. Update product in PrestaShop
```

**PrestaShop Result:**
```xml
<product>
    <id>789</id>
    <associations>
        <product_features>
            <!-- Regular features -->
            <product_feature>
                <id>1</id>
                <id_feature_value>10</id_feature_value>
            </product_feature>

            <!-- Vehicle compatibility (NEW!) -->
            <product_feature>
                <id>50</id>  <!-- "Oryginał" -->
                <id_feature_value>200</id_feature_value>  <!-- "YCF F125 (2019-2023)" -->
            </product_feature>
        </product_features>
    </associations>
</product>
```

---

## 8. ZALECENIA I BEST PRACTICES

### 8.1 Dla Nowych Features

**Przy dodawaniu nowych pól do synchronizacji:**
1. ✅ Dodaj pole do `calculateChecksum()` (change detection)
2. ✅ Dodaj pole do `extractTrackableFields()` (changed fields tracking)
3. ✅ Dodaj transformację w ProductTransformer
4. ✅ Dodaj walidację w `validateBeforeSync()`
5. ✅ Przetestuj z różnymi sync modes (full_sync, prices_only, etc.)

**Przykład:**
```php
// 1. calculateChecksum()
$data['new_field'] = $model->new_field;

// 2. extractTrackableFields()
$fields['new_field'] = $product['new_field'] ?? null;

// 3. ProductTransformer
$product['new_field'] = $model->new_field;

// 4. validateBeforeSync()
if (empty($model->new_field)) {
    $errors[] = 'New field is required';
}
```

---

### 8.2 Dla Queue Jobs

**Best Practices:**
1. ✅ **Unique Jobs** - używaj `uniqueId()` dla prevent duplicate jobs
2. ✅ **Exponential Backoff** - implementuj `backoff()` dla retry strategy
3. ✅ **User Attribution** - capture `auth()->id()` w web context (NULL = SYSTEM w queue)
4. ✅ **Performance Metrics** - track duration, memory, API calls
5. ✅ **Graceful Failures** - handle 404, rate limits, server errors
6. ✅ **Progress Tracking** - integracja z JobProgressService dla UI feedback

**Anti-Patterns:**
- ❌ Hardcoded timeouts (use SystemSettings)
- ❌ Missing retry logic (jobs should be retryable)
- ❌ No error logging (use SyncLog + Log::error)
- ❌ Batch jobs without progress tracking (user nie widzi co się dzieje)

---

### 8.3 Dla Pending Changes

**Rules:**
1. ✅ **SHOP TAB = PrestaShop Source of Truth** - dane MUSZĄ pochodzić z PS
2. ✅ **Pending Badge** - oznacz zmienione pola jako "ZMIENIONY"
3. ✅ **Sync on Save** - dispatch SyncProductToPrestaShop po zapisie
4. ✅ **Session dla Media** - pass pendingMediaChanges do Job (session NOT available w queue!)

**Example:**
```php
// ProductForm trait
protected function hasPendingMediaChanges(int $shopId): bool {
    $sessionKey = "product.{$this->product->id}.pending_media_changes";
    $pendingChanges = session($sessionKey, []);

    foreach ($pendingChanges as $key => $action) {
        [$mediaId, $changeShopId] = explode(':', $key);
        if ((int) $changeShopId === $shopId) {
            return true;
        }
    }

    return false;
}
```

---

### 8.4 Dla Features Sync

**Best Practices:**
1. ✅ **GET-MODIFY-PUT Pattern** - ZAWSZE pobierz pełny produkt przed UPDATE
2. ✅ **Preserve All Fields** - NIE nadpisuj pól których nie chcesz zmieniać
3. ✅ **Remove Read-Only** - usuń pola read-only (manufacturer_name, quantity, etc.)
4. ✅ **Flat Indexed Array** - product_features = flat array, NOT nested!

**Anti-Patterns:**
- ❌ Minimal PUT data (causes fields to be wiped!)
- ❌ Double nesting `['product_features' => ['product_feature' => [...]]]`
- ❌ Using app() DI dla clients w serwisach (creates empty shop!)

---

## 9. PODSUMOWANIE I KLUCZOWE WNIOSKI

### 9.1 Mocne Strony Obecnego Systemu

✅ **Enterprise-Grade Architecture:**
- Strategy Pattern dla sync logic
- Factory Pattern dla multi-version API clients
- Queue-based async processing
- Comprehensive error handling

✅ **Checksum-Based Change Detection:**
- Efficient (sync only when changed)
- Trackable (show what changed)
- Debuggable (store synced_data for comparison)

✅ **Multi-Store Support:**
- Per-shop data inheritance
- Shop-specific categories/descriptions
- Filtered vehicle brands per shop

✅ **Non-Blocking Sync:**
- Media sync doesn't fail product sync
- Features sync doesn't fail product sync
- Compatibility sync won't fail product sync (proposed)

---

### 9.2 Propozycja Integracji Compatibility Sync

**RECOMMENDED APPROACH:** Wykorzystaj istniejący system features!

**Zalety:**
- ✅ Minimalna ilość nowego kodu (reuse PrestaShopFeatureSyncService)
- ✅ Spójny z obecną architekturą (product_features jako universal mechanism)
- ✅ Non-blocking (errors logged, nie failuje product sync)
- ✅ Per-shop support (SmartSuggestionEngine + ShopFilteringService już działają)

**Implementacja (estimate):**
1. **VehicleCompatibilitySyncService** - ~300 linii (similar to PrestaShopFeatureSyncService)
2. **Migrations** - 2 tabele (compatibility_feature_mappings, vehicle_feature_value_mappings)
3. **ProductSyncStrategy mod** - ~50 linii (syncCompatibilitiesIfEnabled method)
4. **SystemSetting** - 1 rekord (compatibility.auto_sync_on_product_sync)
5. **Tests** - ~200 linii (unit + integration tests)

**Total estimate:** ~550-600 linii nowego kodu

---

### 9.3 Kluczowe Pliki Do Modyfikacji

| Plik | Zmiana | Powód |
|------|--------|-------|
| `ProductSyncStrategy.php` | +syncCompatibilitiesIfEnabled() | Wywołanie sync compatibility |
| `VehicleCompatibilitySyncService.php` | NEW file | Core sync logic |
| `SystemSettingsSeeder.php` | +1 setting | compatibility.auto_sync_on_product_sync |
| `2025_12_09_*_mappings.php` | 2 migrations | Feature mappings tables |

---

## 10. NASTĘPNE KROKI

### 10.1 Priorytet 1 (CRITICAL)

1. **Review z użytkownikiem:**
   - Czy approach "features dla compatibilities" jest OK?
   - Czy chcemy sync Oryginał/Zamiennik/Model osobno czy razem?
   - Czy są jakieś dodatkowe wymagania dla compatibility sync?

2. **Prototyp VehicleCompatibilitySyncService:**
   - Implementuj podstawową wersję
   - Przetestuj na małym zestawie danych
   - Verify PrestaShop response format

### 10.2 Priorytet 2 (HIGH)

1. **Integration Tests:**
   - Test full workflow: ProductForm → Job → Sync → PrestaShop
   - Test compatibility changes detection
   - Test per-shop filtering

2. **Documentation:**
   - Update CLAUDE.md z compatibility sync workflow
   - Create _DOCS/COMPATIBILITY_SYNC_GUIDE.md
   - Update Plan_Projektu dla ETAP_05d completion

### 10.3 Priorytet 3 (MEDIUM)

1. **UI Enhancements:**
   - Show compatibility sync status w ProductForm Shop Tab
   - Add "ZMIENIONY" badge dla compatibility changes
   - Progress bar dla bulk compatibility sync

2. **Performance Optimization:**
   - Cache compatibility feature mappings
   - Batch vehicle feature value creation
   - Monitor sync job duration

---

## ZAŁĄCZNIKI

### A. Kluczowe Modele

**ProductShopData:**
```php
// Consolidated model (ETAP_13)
// Replaces deprecated ProductSyncStatus
protected $fillable = [
    'product_id',
    'shop_id',
    'prestashop_product_id',
    'sync_status',              // pending/syncing/synced/error/conflict
    'checksum',                 // SHA-256
    'last_push_at',             // PPM → PrestaShop
    'last_pulled_at',           // PrestaShop → PPM
    'priority',                 // 1-10
    'retry_count',
    'error_message',
];
```

**VehicleCompatibility:**
```php
protected $fillable = [
    'product_id',               // Part
    'vehicle_model_id',         // Vehicle (FK → products.id)
    'shop_id',                  // Per-shop
    'compatibility_attribute_id', // Oryginał/Zamiennik/Model
    'is_suggested',             // SmartSuggestionEngine
    'confidence_score',         // 0.00-1.00
];
```

---

### B. API Endpoints Reference

**PrestaShop Web Services:**
```
GET    /api/products/{id}                  - Get product
PUT    /api/products/{id}                  - Update product (FULL data required!)
POST   /api/products                       - Create product
DELETE /api/products/{id}                  - Delete product

GET    /api/product_features               - List features
POST   /api/product_features               - Create feature
PUT    /api/product_features/{id}          - Update feature

GET    /api/product_feature_values         - List feature values
POST   /api/product_feature_values         - Create feature value
```

---

### C. Queue Configuration

**Queue Names:**
- `default` - Default queue (CRON compatibility)
- `prestashop_sync` - PrestaShop sync jobs
- `prestashop_high` - High priority products
- `prestashop_low` - Low priority products

**Worker Commands:**
```bash
# Run all queues (priority order)
php artisan queue:work --queue=prestashop_high,prestashop_sync,default

# Run specific queue
php artisan queue:work --queue=prestashop_sync
```

---

## KONIEC RAPORTU

**Przygotował:** laravel-expert
**Data:** 2025-12-09
**Status:** ✅ COMPLETE

**Podsumowanie:**
Przeprowadzono dogłębną analizę systemu synchronizacji PrestaShop w PPM-CC-Laravel. Zidentyfikowano wszystkie kluczowe komponenty, workflow, i wzorce architektoniczne. Zaproponowano integracę sync compatibility z istniejącymi Jobs wykorzystując system features. Implementacja oszacowana na ~550-600 linii nowego kodu z wykorzystaniem istniejącej infrastruktury.
