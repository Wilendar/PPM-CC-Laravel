# RAPORT PRACY AGENTA: architect

**Data**: 2025-12-03 14:30
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: Zaprojektować architekturę FAZA 4 - Synchronizacja Cech z PrestaShop (ETAP_07e)

---

## ✅ WYKONANE PRACE

### 1. Analiza istniejącego kodu
- ✅ Przeanalizowano `PrestaShop8Client.php` - metody API (products, categories, images, attributes)
- ✅ Przeanalizowano `ProductSyncStrategy.php` - wzorce synchronizacji produktów
- ✅ Przeanalizowano modele PPM: `FeatureType`, `PrestashopFeatureMapping`
- ✅ Przeanalizowano PrestaShop Features API (Context7 docs)

### 2. Zidentyfikowano kluczowe wzorce
- ✅ **CQRS Pattern** - ProductSyncStrategy używa rozdzielenia Command/Query
- ✅ **GET-MODIFY-PUT Pattern** - PrestaShop wymaga pełnych danych przy UPDATE
- ✅ **XML Format** - Wszystkie POST/PUT/PATCH w PrestaShop wymagają XML (nie JSON!)
- ✅ **Multilang Support** - Nazwy cech muszą mieć strukturę `<language id="1">`
- ✅ **Checksum-based sync** - Detekcja zmian przez porównanie SHA256
- ✅ **Transaction Pattern** - Atomowe operacje z rollback

### 3. Zaprojektowano architekturę synchronizacji
- ✅ Utworzono diagram przepływu danych (3 główne scenariusze)
- ✅ Zdefiniowano 8 kluczowych klas/serwisów
- ✅ Określono mapowanie pól PPM ↔ PrestaShop
- ✅ Zaplanowano kolejność implementacji (4 sub-fazy)
- ✅ Zidentyfikowano 7 głównych ryzyk + mitygacje

---

## 📊 DIAGRAM PRZEPŁYWU DANYCH

```
┌─────────────────────────────────────────────────────────────────┐
│ SCENARIUSZ 1: Sync Feature Types (PPM → PrestaShop)            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [PPM FeatureType]                                              │
│         ↓                                                       │
│  [FeatureSyncService::syncFeatureTypes()]                       │
│         ↓                                                       │
│  Check PrestashopFeatureMapping                                 │
│         ↓                                                       │
│  ┌─────────────────┬─────────────────────┐                     │
│  │ Mapping exists? │ Mapping not exists? │                     │
│  └────────┬────────┴──────────┬──────────┘                     │
│           │                   │                                 │
│           v                   v                                 │
│    UPDATE feature      CREATE feature in PS                     │
│    in PrestaShop       ↓                                        │
│           │            Create PrestashopFeatureMapping          │
│           └────────────┴─────────────┐                          │
│                                      ↓                          │
│                         Update mapping.last_synced_at           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ SCENARIUSZ 2: Sync Product Features (PPM Product → PrestaShop) │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [PPM Product.features JSON]                                    │
│         ↓                                                       │
│  [FeatureSyncService::syncProductFeatures($product, $shop)]     │
│         ↓                                                       │
│  Loop przez product features:                                   │
│    1. Get FeatureType                                           │
│    2. Get PrestashopFeatureMapping (ps_feature_id)              │
│    3. Get/Create FeatureValue → ps_feature_value_id            │
│         ↓                                                       │
│  Build associations.product_features XML:                       │
│    <product_feature>                                            │
│      <id>ps_feature_id</id>                                     │
│      <id_feature_value>ps_feature_value_id</id_feature_value>  │
│    </product_feature>                                           │
│         ↓                                                       │
│  [PrestaShop8Client::updateProduct()]                           │
│         → PATCH with associations.product_features              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ SCENARIUSZ 3: Import Features (PrestaShop → PPM)               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [PrestaShop /api/product_features]                             │
│         ↓                                                       │
│  [FeatureImportService::importFromPrestaShop($shop)]            │
│         ↓                                                       │
│  GET /api/product_features?display=full                         │
│         ↓                                                       │
│  Loop przez PS features:                                        │
│    1. Check if PrestashopFeatureMapping exists                  │
│       ├─ YES → Update FeatureType (if name changed)            │
│       └─ NO  → Create FeatureType + mapping                    │
│         ↓                                                       │
│  GET /api/product_feature_values?filter[id_feature]=X          │
│         ↓                                                       │
│  Import feature values → FeatureValue model                     │
│         ↓                                                       │
│  Return import stats (created, updated, skipped)                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🏗️ ARCHITEKTURA - Klasy i Serwisy

### 1. **PrestaShopFeatureSyncService** (CORE SERVICE)

**Lokalizacja:** `app/Services/PrestaShop/PrestaShopFeatureSyncService.php`

**Odpowiedzialność:** Koordynacja synchronizacji cech między PPM a PrestaShop

**Metody:**

```php
class PrestaShopFeatureSyncService
{
    /**
     * Sync feature types from PPM to PrestaShop
     * Creates/updates ps_feature entries based on FeatureType model
     *
     * @param PrestaShopShop $shop Target shop
     * @param array|null $featureTypeIds Optional filter (null = all active)
     * @return array Stats: ['created' => int, 'updated' => int, 'errors' => array]
     */
    public function syncFeatureTypes(PrestaShopShop $shop, ?array $featureTypeIds = null): array;

    /**
     * Sync product features to PrestaShop product
     * Updates associations.product_features in PrestaShop
     *
     * @param Product $product PPM Product with features
     * @param PrestaShopShop $shop Target shop
     * @param int $psProductId PrestaShop product ID
     * @return array Stats: ['synced' => int, 'errors' => array]
     */
    public function syncProductFeatures(Product $product, PrestaShopShop $shop, int $psProductId): array;

    /**
     * Import features from PrestaShop to PPM
     * Creates FeatureType + PrestashopFeatureMapping for unmapped PS features
     *
     * @param PrestaShopShop $shop Source shop
     * @param bool $overwriteExisting Update existing PPM features?
     * @return array Stats: ['imported' => int, 'updated' => int, 'skipped' => int]
     */
    public function importFeaturesFromPrestaShop(PrestaShopShop $shop, bool $overwriteExisting = false): array;

    /**
     * Resolve conflicts when same feature name exists in PPM and PS
     * Strategies: auto_merge, manual_mapping, create_new
     *
     * @param array $conflicts Array of conflict descriptors
     * @param string $strategy Conflict resolution strategy
     * @return array Resolution results
     */
    public function resolveConflicts(array $conflicts, string $strategy = 'auto_merge'): array;
}
```

---

### 2. **FeatureTransformer** (TRANSFORMATION LAYER)

**Lokalizacja:** `app/Services/PrestaShop/Transformers/FeatureTransformer.php`

**Odpowiedzialność:** Konwersja danych między formatami PPM ↔ PrestaShop

**Metody:**

```php
class FeatureTransformer
{
    /**
     * Transform FeatureType to PrestaShop product_feature XML structure
     *
     * @param FeatureType $featureType PPM feature type
     * @param int $langId PrestaShop language ID (default: 1)
     * @return array PrestaShop format: ['name' => [...], 'position' => int]
     */
    public function transformFeatureTypeToPS(FeatureType $featureType, int $langId = 1): array;

    /**
     * Transform PrestaShop product_feature to FeatureType data
     *
     * @param array $psFeature PrestaShop feature data
     * @return array FeatureType fillable data
     */
    public function transformPSToFeatureType(array $psFeature): array;

    /**
     * Build product_features associations XML for product
     *
     * @param Product $product PPM Product
     * @param PrestaShopShop $shop Target shop
     * @return array Associations array: [['id' => ps_feature_id, 'id_feature_value' => ps_value_id], ...]
     */
    public function buildProductFeaturesAssociations(Product $product, PrestaShopShop $shop): array;

    /**
     * Transform product feature value (text/number/bool) to PrestaShop value
     *
     * @param mixed $value PPM feature value
     * @param FeatureType $featureType Feature type for context
     * @return string PrestaShop-compatible value string
     */
    public function transformValueToPS($value, FeatureType $featureType): string;
}
```

---

### 3. **FeatureValueMapper** (VALUE MANAGEMENT)

**Lokalizacja:** `app/Services/PrestaShop/Mappers/FeatureValueMapper.php`

**Odpowiedzialność:** Zarządzanie wartościami cech (ps_feature_value)

**Metody:**

```php
class FeatureValueMapper
{
    /**
     * Get or create PrestaShop feature_value for given value
     * Searches existing values by value string, creates if not found
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param string $value Value string
     * @param PrestaShopShop $shop Target shop
     * @return int PrestaShop feature_value ID
     */
    public function getOrCreateFeatureValue(int $psFeatureId, string $value, PrestaShopShop $shop): int;

    /**
     * Sync all values for a FeatureType to PrestaShop
     * Used for SELECT type features with predefined values
     *
     * @param FeatureType $featureType Feature type with values
     * @param int $psFeatureId PrestaShop feature ID
     * @param PrestaShopShop $shop Target shop
     * @return array Stats: ['created' => int, 'existing' => int]
     */
    public function syncFeatureValues(FeatureType $featureType, int $psFeatureId, PrestaShopShop $shop): array;

    /**
     * Import feature values from PrestaShop to PPM FeatureValue model
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param FeatureType $featureType Target PPM feature type
     * @param PrestaShopShop $shop Source shop
     * @return array Stats: ['imported' => int, 'skipped' => int]
     */
    public function importFeatureValuesFromPS(int $psFeatureId, FeatureType $featureType, PrestaShopShop $shop): array;
}
```

---

### 4. **PrestaShop8Client Extensions** (API METHODS)

**Lokalizacja:** `app/Services/PrestaShop/PrestaShop8Client.php` (extend existing)

**Nowe metody:**

```php
// ===================================
// PRODUCT FEATURES API METHODS
// ===================================

/**
 * Get all product features
 */
public function getProductFeatures(array $filters = []): array;

/**
 * Get single product feature by ID
 */
public function getProductFeature(int $featureId): array;

/**
 * Create new product feature
 * XML format required!
 */
public function createProductFeature(array $featureData): array;

/**
 * Update existing product feature
 * XML format required!
 */
public function updateProductFeature(int $featureId, array $featureData): array;

/**
 * Delete product feature
 */
public function deleteProductFeature(int $featureId): bool;

// ===================================
// PRODUCT FEATURE VALUES API METHODS
// ===================================

/**
 * Get all feature values (optionally filtered by feature ID)
 */
public function getProductFeatureValues(array $filters = []): array;

/**
 * Get single feature value by ID
 */
public function getProductFeatureValue(int $valueId): array;

/**
 * Create new feature value
 * XML format required!
 */
public function createProductFeatureValue(array $valueData): array;

/**
 * Update existing feature value
 * XML format required!
 */
public function updateProductFeatureValue(int $valueId, array $valueData): array;

/**
 * Delete feature value
 */
public function deleteProductFeatureValue(int $valueId): bool;
```

---

### 5. **FeatureMappingManager** (MAPPING UI BACKEND)

**Lokalizacja:** `app/Services/PrestaShop/FeatureMappingManager.php`

**Odpowiedzialność:** Business logic dla UI mapowania cech

**Metody:**

```php
class FeatureMappingManager
{
    /**
     * Get all mappings for shop with match suggestions
     *
     * @param PrestaShopShop $shop
     * @return array ['mapped' => [...], 'unmapped_ppm' => [...], 'unmapped_ps' => [...], 'suggestions' => [...]]
     */
    public function getMappingsWithSuggestions(PrestaShopShop $shop): array;

    /**
     * Auto-match features by name similarity
     * Uses Levenshtein distance for fuzzy matching
     *
     * @param PrestaShopShop $shop
     * @param float $threshold Similarity threshold (0.0-1.0, default: 0.8)
     * @return array Stats: ['matched' => int, 'suggestions' => array]
     */
    public function autoMatchByName(PrestaShopShop $shop, float $threshold = 0.8): array;

    /**
     * Create manual mapping
     *
     * @param int $featureTypeId PPM FeatureType ID
     * @param int $psFeatureId PrestaShop feature ID
     * @param PrestaShopShop $shop
     * @return PrestashopFeatureMapping
     */
    public function createMapping(int $featureTypeId, int $psFeatureId, PrestaShopShop $shop): PrestashopFeatureMapping;

    /**
     * Create missing features in PrestaShop
     * For unmapped PPM features, creates them in PS and auto-maps
     *
     * @param array $featureTypeIds PPM FeatureType IDs to create
     * @param PrestaShopShop $shop
     * @return array Stats: ['created' => int, 'errors' => array]
     */
    public function createMissingInPrestaShop(array $featureTypeIds, PrestaShopShop $shop): array;
}
```

---

### 6. **SyncFeaturesJob** (BACKGROUND JOB)

**Lokalizacja:** `app/Jobs/Features/SyncFeaturesJob.php`

**Odpowiedzialność:** Batch synchronizacja cech produktów

```php
class SyncFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $productIds,
        public int $shopId,
        public ?int $jobProgressId = null
    ) {}

    public function handle(
        PrestaShopFeatureSyncService $syncService,
        JobProgressService $progressService
    ): void {
        // Batch process products with progress tracking
        // Similar pattern to BulkSyncProducts
    }
}
```

---

### 7. **ImportFeaturesFromPSJob** (IMPORT JOB)

**Lokalizacja:** `app/Jobs/Features/ImportFeaturesFromPSJob.php`

**Odpowiedzialność:** Import cech z PrestaShop do PPM

```php
class ImportFeaturesFromPSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shopId,
        public ?int $jobProgressId = null
    ) {}

    public function handle(
        PrestaShopFeatureSyncService $syncService,
        JobProgressService $progressService
    ): void {
        // Import all features from shop
        // Track progress with JobProgressService
    }
}
```

---

### 8. **ProductSyncStrategy Extensions** (INTEGRATE WITH PRODUCT SYNC)

**Lokalizacja:** `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` (extend existing)

**Modyfikacje:**

```php
// In syncToPrestaShop() method, add AFTER price export:

// NEW: Export product features
Log::debug('[SYNC DEBUG] Starting feature export');
try {
    $featureExportResults = $this->featureSyncService->syncProductFeatures($model, $shop, $externalId);
    Log::info('[SYNC DEBUG] Feature export completed', [
        'synced' => $featureExportResults['synced'],
        'errors' => count($featureExportResults['errors']),
    ]);
} catch (\Exception $e) {
    // Log error but don't fail entire sync
    Log::error('[SYNC DEBUG] Feature export failed (non-fatal)', [
        'product_id' => $model->id,
        'shop_id' => $shop->id,
        'error' => $e->getMessage(),
    ]);
}
```

---

## 🗂️ MAPOWANIE PÓL: PPM ↔ PrestaShop

### FeatureType ↔ ps_feature

| PPM Field           | PrestaShop Field        | Kierunek | Notatki                                    |
|---------------------|-------------------------|----------|--------------------------------------------|
| `code`              | -                       | PPM only | Unikalny identyfikator w PPM               |
| `name`              | `name.language[id=1]`   | Both     | Multilang w PS                             |
| `value_type`        | -                       | PPM only | text/number/bool/select                    |
| `unit`              | -                       | PPM only | Przechowywane w PPM, może być w nazwie PS  |
| `feature_group_id`  | -                       | PPM only | Grupowanie tylko w PPM                     |
| `position`          | `position`              | Both     | Kolejność wyświetlania                     |
| `is_active`         | -                       | PPM only | PS nie ma aktywności dla features          |
| -                   | `id`                    | PS only  | Auto-generated w PrestaShop                |

### PrestashopFeatureMapping (JUNCTION TABLE)

| Field                      | Typ      | Opis                                           |
|----------------------------|----------|------------------------------------------------|
| `feature_type_id`          | FK       | PPM FeatureType                                |
| `shop_id`                  | FK       | PrestaShopShop (per-shop mapping)              |
| `prestashop_feature_id`    | int      | ps_feature.id                                  |
| `prestashop_feature_name`  | string   | Reference name from PS                         |
| `sync_direction`           | enum     | both/ppm_to_ps/ps_to_ppm                       |
| `auto_create_values`       | bool     | Auto-create ps_feature_value?                  |
| `is_active`                | bool     | Enable/disable mapping                         |
| `last_synced_at`           | datetime | Last successful sync timestamp                 |
| `sync_count`               | int      | Number of syncs performed                      |
| `last_sync_error`          | string   | Last error message (null = no error)           |

### Product Features ↔ associations.product_features

| PPM Field                       | PrestaShop Field                              | Notatki                          |
|---------------------------------|-----------------------------------------------|----------------------------------|
| `Product.features` (JSON)       | `associations.product_features`               | JSON w PPM, associations w PS    |
| `features[].feature_type_id`    | `product_feature.id`                          | Via PrestashopFeatureMapping     |
| `features[].value`              | `product_feature.id_feature_value`            | Get/create ps_feature_value      |
| -                               | `product_feature.custom` (bool)               | Always 0 for synced features     |

---

## 📋 KOLEJNOŚĆ IMPLEMENTACJI

### **FAZA 4.1: Feature Sync Service (Core) - 3-4 sesje**

**Priorytet:** KRYTYCZNY (fundament całej synchronizacji)

**Zadania:**

1. **PrestaShop8Client Extensions**
   - Dodać 10 metod API dla product_features i product_feature_values
   - Implementacja XML conversion (wzór z istniejących metod)
   - Unit testy dla każdej metody
   - **Plik:** `app/Services/PrestaShop/PrestaShop8Client.php`

2. **FeatureTransformer**
   - Implementacja 4 metod transformacji
   - Obsługa multilang (structure: `['language' => [['id' => 1, 'value' => '...']]]`)
   - Testy konwersji PPM ↔ PS
   - **Plik:** `app/Services/PrestaShop/Transformers/FeatureTransformer.php`

3. **FeatureValueMapper**
   - getOrCreateFeatureValue() z cache'owaniem
   - syncFeatureValues() dla SELECT types
   - **Plik:** `app/Services/PrestaShop/Mappers/FeatureValueMapper.php`

4. **PrestaShopFeatureSyncService (Part 1)**
   - syncFeatureTypes() - podstawowa sync
   - syncProductFeatures() - integracja z product sync
   - Error handling i retry logic (wzór z ProductSyncStrategy)
   - **Plik:** `app/Services/PrestaShop/PrestaShopFeatureSyncService.php`

**Kryteria ukończenia:**
- ✅ Wszystkie metody API działają (manual test z Tinker)
- ✅ Transformer tworzy poprawny XML dla PrestaShop
- ✅ FeatureValueMapper tworzy/pobiera wartości
- ✅ syncProductFeatures() synchronizuje cechy testowego produktu

---

### **FAZA 4.2: Mapping UI & Auto-Match - 2-3 sesje**

**Priorytet:** WYSOKI (UX dla administracji)

**Zadania:**

1. **FeatureMappingManager**
   - getMappingsWithSuggestions() - zbiera dane dla UI
   - autoMatchByName() - algorytm dopasowania (Levenshtein distance)
   - createMapping(), createMissingInPrestaShop()
   - **Plik:** `app/Services/PrestaShop/FeatureMappingManager.php`

2. **Livewire Component: FeatureMappingPanel**
   - Lista mapped/unmapped features
   - Auto-match button z progress bar
   - Manual mapping (drag & drop lub select)
   - Create missing in PS button
   - **Plik:** `app/Http/Livewire/Admin/PrestaShop/FeatureMappingPanel.php`
   - **View:** `resources/views/livewire/admin/prestashop/feature-mapping-panel.blade.php`

3. **Route & Navigation**
   - `/admin/prestashop/features/mapping/{shop}`
   - Link w shop configuration wizard
   - **Plik:** `routes/web.php`

**Kryteria ukończenia:**
- ✅ Panel mapowania widoczny w admin
- ✅ Auto-match znajduje dopasowania (>80% similarity)
- ✅ Manual mapping zapisuje się poprawnie
- ✅ Create missing tworzy features w PrestaShop

---

### **FAZA 4.3: Background Jobs & Batch Operations - 2 sesje**

**Priorytet:** ŚREDNI (optymalizacja i skalowanie)

**Zadania:**

1. **SyncFeaturesJob**
   - Batch processing (100 produktów na raz)
   - JobProgress integration (wzór z BulkSyncProducts)
   - Error tracking per-product
   - **Plik:** `app/Jobs/Features/SyncFeaturesJob.php`

2. **ImportFeaturesFromPSJob**
   - Import wszystkich features z PS do PPM
   - Progress tracking
   - Conflict detection i auto-resolution
   - **Plik:** `app/Jobs/Features/ImportFeaturesFromPSJob.php`

3. **Job Configuration**
   - Dodać feature_sync do config/job_types.php
   - Queue configuration (channel: prestashop)
   - **Plik:** `config/job_types.php`

4. **Artisan Commands (optional)**
   - `php artisan prestashop:sync-features {shop}`
   - `php artisan prestashop:import-features {shop}`
   - **Pliki:** `app/Console/Commands/PrestaShop/`

**Kryteria ukończenia:**
- ✅ SyncFeaturesJob synchronizuje batch produktów
- ✅ ImportFeaturesFromPSJob importuje features z PS
- ✅ Progress tracking działa (JobProgressBar w UI)
- ✅ Error handling i retry logic działa

---

### **FAZA 4.4: Product Sync Integration & Testing - 1-2 sesje**

**Priorytet:** KRYTYCZNY (finalizacja integracji)

**Zadania:**

1. **ProductSyncStrategy Extensions**
   - Dodać syncProductFeatures() do workflow sync produktu
   - Kolejność: product → categories → features → prices → images
   - Non-blocking error handling (jak w media sync)
   - **Plik:** `app/Services/PrestaShop/Sync/ProductSyncStrategy.php`

2. **ProductForm Integration**
   - Auto-trigger feature sync przy zapisie produktu (opcjonalne, checkbox?)
   - Badge "Features synced" w shop tab
   - **Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`

3. **End-to-End Testing**
   - Test scenariusz: Dodaj cechy do produktu → Zapisz → Sync do PS → Weryfikacja w PS admin
   - Test scenariusz: Import features z PS → Auto-match → Manual adjust → Sync product
   - Performance testing (100 produktów z 20 cechami każdy)

4. **Documentation Update**
   - Aktualizacja ETAP_07e_Features_System.md (FAZA 4 ✅ COMPLETED)
   - Utworzenie `_DOCS/FEATURES_SYNC_GUIDE.md` (user guide)
   - Aktualizacja Plan_Projektu/ETAP_07e.md

**Kryteria ukończenia:**
- ✅ Features synchronizują się razem z produktem
- ✅ PrestaShop admin pokazuje zsynchronizowane cechy
- ✅ Batch sync 100 produktów kończy się sukcesem (<5% błędów)
- ✅ Documentation aktualna

---

## ⚠️ RYZYKA I MITYGACJE

### **RYZYKO 1: PrestaShop Multilang Complexity**

**Opis:** PrestaShop wymaga multilang dla nazw cech, PPM ma tylko jedną wartość

**Impact:** WYSOKI - Mogą powstać błędy 400 Bad Request z PS API

**Mitygacja:**
- Używać default language ID (1) dla wszystkich synchronizacji
- Transformer zawsze generuje structure: `['language' => [['id' => 1, 'value' => $name]]]`
- Test z rzeczywistym PS API przed implementacją masową
- Dodać obsługę wielu języków w przyszłości (FAZA 6?)

**Odpowiedzialny:** laravel-expert podczas implementacji FeatureTransformer

---

### **RYZYKO 2: Feature Value Duplication**

**Opis:** PrestaShop może mieć duplikaty wartości cech (np. "100W" vs "100 W")

**Impact:** ŚREDNI - Powstają niepotrzebne wartości w ps_feature_value

**Mitygacja:**
- FeatureValueMapper normalizuje wartości przed search (trim, lowercase, remove extra spaces)
- Search case-insensitive w PrestaShop
- Dodać cleanup command: `php artisan prestashop:cleanup-feature-values`
- Unit validation regex w FeatureType.validation_rules

**Odpowiedzialny:** prestashop-api-expert podczas implementacji FeatureValueMapper

---

### **RYZYKO 3: Sync Performance (Large Datasets)**

**Opis:** 1000 produktów x 20 cech = 20k wartości do sync

**Impact:** WYSOKI - Timeout, rate limiting, długie czasy sync

**Mitygacja:**
- Batch processing (100 produktów per job)
- Rate limiting w PrestaShop8Client (500ms delay między requests)
- Cache ps_feature_value IDs w Redis (TTL: 1h)
- Background jobs z queue workers (nie synchronicznie)
- Incremental sync (tylko zmienione cechy)

**Odpowiedzialny:** deployment-specialist + laravel-expert

---

### **RYZYKO 4: Mapping Conflicts (Name Collisions)**

**Opis:** PPM feature "Moc" może być zmapowane do różnych PS features w różnych sklepach

**Impact:** ŚREDNI - Błędne dane w niektórych sklepach

**Mitygacja:**
- PrestashopFeatureMapping ma shop_id (per-shop mapping)
- UI pokazuje ostrzeżenie przy konfliktach nazw
- resolveConflicts() metoda z strategiami:
  - `auto_merge` - Merge do pierwszego znalezionego
  - `manual_mapping` - Wymaga user action
  - `create_new` - Tworzy nowy feature w PS
- Validation przed sync: sprawdź czy mappingi są spójne

**Odpowiedzialny:** architect + frontend-specialist (UI alerts)

---

### **RYZYKO 5: XML Encoding Issues**

**Opis:** Wartości cech mogą zawierać znaki specjalne (<, >, &, polskie znaki)

**Impact:** WYSOKI - PrestaShop zwraca 400 Bad Request

**Mitygacja:**
- Używać `<![CDATA[...]]>` dla wszystkich wartości tekstowych
- XML escape funkcja w BasePrestaShopClient::arrayToXml()
- UTF-8 encoding validation przed wysłaniem
- Test suite z problematycznymi znakami (ąćęłńóśźż, <>&)

**Odpowiedzialny:** prestashop-api-expert

---

### **RYZYKO 6: PrestaShop API Rate Limiting**

**Opis:** Hostido shared hosting może zablokować nadmierny traffic API

**Impact:** ŚREDNI - Sync failed z 503 Service Unavailable

**Mitygacja:**
- Już zaimplementowane w BasePrestaShopClient (500ms delay)
- Zwiększyć delay do 1000ms dla feature sync (więcej requests)
- Exponential backoff w retry logic (już w ProductSyncStrategy)
- Monitor logs dla 503 errors → auto-adjust delay
- Batch size adjustment (100 → 50 jeśli 503 występuje)

**Odpowiedzialny:** deployment-specialist

---

### **RYZYKO 7: Incomplete Feature Sync (Transaction Boundaries)**

**Opis:** Product sync zakończył się sukcesem, ale feature sync failed

**Impact:** ŚREDNI - Produkt w PS bez cech lub z niepełnymi cechami

**Mitygacja:**
- Non-blocking feature sync (jak media sync) - product sync nie failuje
- Separate log entries dla feature sync errors
- UI badge w ProductForm: "Features: ⚠️ Sync error" z retry button
- Cron job: daily retry failed feature syncs
- SyncLog table tracks feature sync separately

**Odpowiedzialny:** laravel-expert + debugger

---

## 📋 NASTĘPNE KROKI

### Dla **laravel-expert** (FAZA 4.1 - Rozpocznij)
1. Przeczytaj ten raport architektury
2. Rozpocznij od PrestaShop8Client extensions (10 metod API)
3. Implementuj FeatureTransformer (wzór z ProductTransformer)
4. Test każdej metody z Tinker przed przejściem dalej
5. **Plik do utworzenia:** `app/Services/PrestaShop/Transformers/FeatureTransformer.php`

### Dla **prestashop-api-expert** (FAZA 4.1 - Wsparcie)
1. Review PrestaShop Features API docs (Context7)
2. Pomoc w debugging XML format issues
3. Implementacja FeatureValueMapper
4. Test integration z rzeczywistym PrestaShop 8.x
5. **Plik do utworzenia:** `app/Services/PrestaShop/Mappers/FeatureValueMapper.php`

### Dla **frontend-specialist** (FAZA 4.2 - Po ukończeniu 4.1)
1. Projektowanie UI dla Feature Mapping Panel
2. Implementacja Livewire component
3. Drag & drop lub select dla manual mapping
4. Progress bar dla auto-match i batch operations
5. **Plik do utworzenia:** `app/Http/Livewire/Admin/PrestaShop/FeatureMappingPanel.php`

### Dla **deployment-specialist** (FAZA 4.4 - Finalizacja)
1. Deploy FAZY 4.1-4.3 na produkcję (staged rollout)
2. Performance monitoring (queue workers, API rate limits)
3. Backup przed masową synchronizacją
4. Weryfikacja w PrestaShop admin panel
5. Rollback plan w przypadku problemów

### Dla **architect** (Ciągła odpowiedzialność)
1. Monitoring postępu FAZY 4
2. Aktualizacja Plan_Projektu/ETAP_07e.md po każdej sub-fazie
3. Code review kluczowych serwisów (FeatureSyncService, Transformer)
4. Coordination między agentami (conflicts resolution)
5. Documentation updates

---

## 📁 PLIKI DO UTWORZENIA/MODYFIKACJI

### Nowe pliki (8):

1. **`app/Services/PrestaShop/PrestaShopFeatureSyncService.php`** - Core sync service
2. **`app/Services/PrestaShop/Transformers/FeatureTransformer.php`** - Data transformation
3. **`app/Services/PrestaShop/Mappers/FeatureValueMapper.php`** - Value management
4. **`app/Services/PrestaShop/FeatureMappingManager.php`** - Mapping logic
5. **`app/Jobs/Features/SyncFeaturesJob.php`** - Batch sync job
6. **`app/Jobs/Features/ImportFeaturesFromPSJob.php`** - Import job
7. **`app/Http/Livewire/Admin/PrestaShop/FeatureMappingPanel.php`** - Mapping UI
8. **`resources/views/livewire/admin/prestashop/feature-mapping-panel.blade.php`** - View

### Modyfikacje istniejących plików (3):

1. **`app/Services/PrestaShop/PrestaShop8Client.php`** - Dodać 10 metod API
2. **`app/Services/PrestaShop/Sync/ProductSyncStrategy.php`** - Integracja feature sync
3. **`config/job_types.php`** - Feature sync job configuration

### Dokumentacja (2):

1. **`Plan_Projektu/ETAP_07e_Features_System.md`** - Aktualizacja statusu FAZY 4
2. **`_DOCS/FEATURES_SYNC_GUIDE.md`** - User guide (nowy plik)

---

## 🎯 METRYKI SUKCESU (FAZA 4)

### Funkcjonalne:
- ✅ Feature types synchronizują się PPM → PS (CREATE + UPDATE)
- ✅ Product features synchronizują się z produktem (associations)
- ✅ Import features z PS → PPM działa (auto-match >70%)
- ✅ Mapping UI pozwala na manual mapping i auto-match
- ✅ Batch sync 100 produktów kończy się <5 min z <5% błędów

### Techniczne:
- ✅ Wszystkie metody API mają unit testy (coverage >80%)
- ✅ FeatureTransformer poprawnie konwertuje PPM ↔ PS (multilang support)
- ✅ FeatureValueMapper nie tworzy duplikatów wartości
- ✅ ProductSyncStrategy integruje feature sync non-blocking
- ✅ Jobs trackują progress z JobProgressService

### Performance:
- ✅ Single feature sync: <2s (network dependent)
- ✅ Batch 100 produktów z 20 cechami: <5 min
- ✅ Import all features z PS: <30s (dla 100 features)
- ✅ Auto-match algorithm: <10s (dla 70 PPM + 80 PS features)
- ✅ Memory usage: <512MB per job worker

### UX:
- ✅ Mapping UI intuicyjna (0 błędów w user testing)
- ✅ Progress bar pokazuje real-time postęp
- ✅ Error messages zrozumiałe (nie technical jargon)
- ✅ Retry button działa dla failed syncs
- ✅ 0 błędów konsoli (Chrome DevTools verification)

---

**Raport utworzony:** 2025-12-03 14:30
**Następna aktualizacja:** Po ukończeniu FAZY 4.1 (laravel-expert)
**Status:** ✅ ARCHITEKTURA ZAPROJEKTOWANA - Gotowa do implementacji
