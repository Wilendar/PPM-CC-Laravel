# RAPORT ARCHITEKTONICZNY: System Dopasowań Części Zamiennych - Przeprojektowanie

**Agent:** architect
**Data:** 2025-12-04
**Zadanie:** Zaprojektowanie architektury nowego Systemu Dopasowań Części Zamiennych
**Status:** ✅ COMPLETED - Architecture Design Phase

---

## 📋 EXECUTIVE SUMMARY

**CEL:** Przeprojektowanie systemu dopasowań części zamiennych z uwzględnieniem masowej edycji, inteligentnych sugestii i per-shop filtering.

**OBECNY STAN:**
- ✅ Podstawowa struktura bazy danych istnieje (4 tabele: vehicle_models, vehicle_compatibility, compatibility_attributes, compatibility_sources)
- ✅ Modele Eloquent zaimplementowane z relationships
- ✅ CompatibilityManager service z sub-services (Vehicle, Bulk, Cache)
- ⚠️ UI placeholder (mock data) - panel /admin/compatibility nie działa
- ❌ Brak ProductForm TAB dla indywidualnej edycji
- ❌ Brak masowej edycji (Excel-like workflow)
- ❌ Brak per-shop filtering
- ❌ Brak inteligentnych sugestii

**PROJEKTOWANE ROZWIĄZANIE:**
1. **Rozszerzenie schematu bazy** - dodanie shop_id, metadanych, cache
2. **Nowy CompatibilityPanel** - masowa edycja w stylu Excel
3. **ProductForm TAB** - indywidualna edycja dopasowań
4. **SmartSuggestionEngine** - inteligentne sugerowanie pojazdów
5. **ShopFilteringService** - per-shop brand restrictions
6. **UX Optimization** - kafelki, szybkie zaznaczanie, autocomplete

---

## 1. ANALIZA OBECNEGO STANU

### 1.1 Istniejąca Struktura Bazy Danych

#### ✅ Tabela: `vehicle_models`
```sql
CREATE TABLE vehicle_models (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(255) UNIQUE NOT NULL,              -- SKU-first: VEH-HONDA-CBR600RR-2013
    brand VARCHAR(100) NOT NULL,                    -- "Honda", "Yamaha", "YCF"
    model VARCHAR(100) NOT NULL,                    -- "CBR 600", "YZF-R1"
    variant VARCHAR(100) NULLABLE,                  -- "RR", "Sport", "ABS"
    year_from YEAR NULLABLE,
    year_to YEAR NULLABLE,
    engine_code VARCHAR(50) NULLABLE,
    engine_capacity INT NULLABLE,                   -- cc
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_vehicle_sku (sku),
    INDEX idx_vehicle_brand_model (brand, model),
    INDEX idx_vehicle_years (year_from, year_to),
    INDEX idx_vehicle_active (is_active)
);
```

**COMPLIANCE CHECK:** ✅ PASS
- SKU-first architecture (zgodnie z `_DOCS/SKU_ARCHITECTURE_GUIDE.md`)
- Proper indexes dla performance
- Year range support dla filtrowania

#### ✅ Tabela: `vehicle_compatibility` (PIVOT TABLE)
```sql
CREATE TABLE vehicle_compatibility (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,            -- FK to products
    part_sku VARCHAR(255) NOT NULL,                 -- SKU backup (SKU-first)
    vehicle_model_id BIGINT UNSIGNED NOT NULL,      -- FK to vehicle_models
    vehicle_sku VARCHAR(255) NOT NULL,              -- SKU backup (SKU-first)
    compatibility_attribute_id BIGINT UNSIGNED NULLABLE, -- Original/Replacement/etc.
    compatibility_source_id BIGINT UNSIGNED NOT NULL,    -- Manual/Import/TecDoc
    notes TEXT NULLABLE,
    verified BOOLEAN DEFAULT 0,
    verified_at TIMESTAMP NULLABLE,
    verified_by BIGINT UNSIGNED NULLABLE,           -- FK to users
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    FOREIGN KEY (compatibility_attribute_id) REFERENCES compatibility_attributes(id) ON DELETE SET NULL,
    FOREIGN KEY (compatibility_source_id) REFERENCES compatibility_sources(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_compat_product_vehicle (product_id, vehicle_model_id),
    INDEX idx_compat_part_sku (part_sku),
    INDEX idx_compat_vehicle_sku (vehicle_sku),
    INDEX idx_compat_attr (compatibility_attribute_id),
    INDEX idx_compat_verified (verified),

    UNIQUE KEY uniq_compat_product_vehicle (product_id, vehicle_model_id)
);
```

**COMPLIANCE CHECK:** ✅ PASS (z zaplanowanymi rozszerzeniami)
- SKU columns jako backup dla SKU-first lookup
- Proper cascade rules
- Unique constraint zapobiega duplikatom

**⚠️ ISSUES:**
1. ❌ Brak `shop_id` column - nie ma per-shop support
2. ❌ Brak `is_suggested` flag - nie ma smart suggestions tracking
3. ❌ Brak `metadata` JSON - nie ma dodatkowych informacji (confidence score, auto-applied, etc.)

#### ✅ Tabela: `compatibility_attributes`
```sql
CREATE TABLE compatibility_attributes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,                     -- "Oryginał", "Zamiennik", "Model"
    code VARCHAR(50) UNIQUE NOT NULL,               -- "original", "replacement", "model"
    color VARCHAR(7) NULLABLE,                      -- "#4ade80" (HEX for badges)
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    is_auto_generated BOOLEAN DEFAULT 0,            -- Added 2025-10-24
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_compat_attr_code (code),
    INDEX idx_compat_attr_active (is_active)
);
```

**COMPLIANCE CHECK:** ✅ PASS
- 3 główne typy zgodnie z requirements (Original, Replacement, Model)
- Badge color support dla UI
- `is_auto_generated` flag dla auto-created "Model" attributes

#### ✅ Tabela: `compatibility_sources`
```sql
CREATE TABLE compatibility_sources (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,                     -- "Manual", "Import", "TecDoc"
    code VARCHAR(50) UNIQUE NOT NULL,               -- "manual", "import", "tecdoc"
    trust_level ENUM('high', 'medium', 'low') DEFAULT 'medium',
    description TEXT NULLABLE,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_compat_source_code (code),
    INDEX idx_compat_source_active (is_active)
);
```

**COMPLIANCE CHECK:** ✅ PASS
- Trust level tracking dla weryfikacji
- Extensible (można dodawać nowe źródła)

### 1.2 Istniejące Modele Eloquent

#### ✅ VehicleModel.php
- ✅ SKU-first patterns (scopeBySku, findBySku)
- ✅ Proper relationships (hasMany VehicleCompatibility)
- ✅ Helper methods (getFullName, getYearRange, isActiveForYear)
- ✅ Year range filtering scope

#### ✅ VehicleCompatibility.php
- ✅ SKU backup columns (part_sku, vehicle_sku)
- ✅ Proper relationships (belongsTo Product, VehicleModel, CompatibilityAttribute, etc.)
- ✅ Verification methods (verify, isVerified)
- ✅ Scopes (verified, byPartSku, byVehicleSku, byProduct, byVehicle)
- ✅ Helper methods (getDisplayAttribute, getTrustLevel, getTypeBadge)

#### ✅ CompatibilityAttribute.php
- ✅ Code constants (CODE_ORIGINAL, CODE_REPLACEMENT, CODE_PERFORMANCE)
- ✅ Helper methods (getBadgeHtml, isOriginal, isReplacement)
- ✅ Proper scopes (active, byCode, ordered)

### 1.3 Istniejące Serwisy

#### ✅ CompatibilityManager.php
**Lokalizacja:** `app/Services/CompatibilityManager.php`

**Funkcjonalność:**
- ✅ SKU-first lookup patterns
- ✅ CRUD operations (add, update, remove compatibility)
- ✅ Verification system (verify, bulk verify)
- ✅ Cache layer (15-minute TTL)
- ✅ Delegation do sub-services (Vehicle, Bulk, Cache)

**Sub-Services:**
- ✅ `CompatibilityVehicleService` - zarządzanie vehicle models
- ✅ `CompatibilityBulkService` - bulk operations (copy from product, etc.)
- ✅ `CompatibilityCacheService` - cache management

**COMPLIANCE CHECK:** ✅ PASS
- ~280 linii (zgodnie z CLAUDE.md limit ~300)
- Laravel 12.x DI patterns
- Type hints PHP 8.3
- DB transactions dla multi-record operations

### 1.4 Istniejące Livewire Components

#### ⚠️ CompatibilityManagement.php (PLACEHOLDER)
**Lokalizacja:** `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php`

**Status:** ❌ MOCK DATA ONLY
- ⚠️ Używa placeholder data
- ⚠️ Brak prawdziwych queries do bazy
- ⚠️ Brak bulk operations backend

**Funkcjonalność (teoretyczna):**
- Search by part SKU/name
- Filter by shop, brand, status
- Expand/collapse part rows
- Multi-select with checkboxes
- Bulk edit modal trigger

**ISSUES:**
- ❌ Brak integracji z CompatibilityManager service
- ❌ Brak per-shop filtering logic
- ❌ Brak smart suggestions
- ❌ Brak Excel-like workflow

#### ❌ ProductForm TAB - NIE ISTNIEJE
**Expected Location:** `app/Http/Livewire/Products/Management/Traits/ProductFormCompatibility.php`

**Requirements:** TAB w ProductForm dla indywidualnej edycji dopasowań pojedynczego produktu

---

## 2. PROJEKTOWANY SCHEMAT BAZY DANYCH

### 2.1 Rozszerzenia Istniejących Tabel

#### 🔧 Migration: `add_shop_support_to_vehicle_compatibility.php`

**CEL:** Dodanie per-shop filtering support

```php
Schema::table('vehicle_compatibility', function (Blueprint $table) {
    // Per-shop support (NULLABLE = dane domyślne)
    $table->foreignId('shop_id')
          ->nullable()
          ->after('vehicle_sku')
          ->constrained('prestashop_shops')
          ->cascadeOnDelete();

    // Smart suggestions tracking
    $table->boolean('is_suggested')->default(false)->after('verified_by');
    $table->decimal('confidence_score', 3, 2)->nullable()->after('is_suggested'); // 0.00-1.00

    // Metadata (JSON)
    $table->json('metadata')->nullable()->after('confidence_score');
    /* Metadata structure:
    {
        "auto_applied": false,
        "suggestion_reason": "brand_match|name_match|manual",
        "user_edited": false,
        "last_edited_by": 8,
        "last_edited_at": "2025-12-04T10:30:00Z"
    }
    */

    // Indexes for performance
    $table->index('shop_id', 'idx_compat_shop');
    $table->index(['product_id', 'shop_id'], 'idx_compat_product_shop');
    $table->index('is_suggested', 'idx_compat_suggested');

    // DROP old unique constraint, ADD new one with shop_id
    $table->dropUnique('uniq_compat_product_vehicle');
    $table->unique(['product_id', 'vehicle_model_id', 'shop_id'], 'uniq_compat_product_vehicle_shop');
});
```

**ARCHITEKTURA DECISION:**
- `shop_id = NULL` → Dane domyślne (globalne dla wszystkich sklepów)
- `shop_id = X` → Per-shop override (różne dopasowania na różnych sklepach)
- Unique constraint zapobiega duplikatom per (product, vehicle, shop)

**BUSINESS LOGIC:**
```
Query default: WHERE shop_id IS NULL
Query per-shop: WHERE shop_id = X
Fallback: Per-shop → default if no shop-specific exist
```

#### 🔧 Migration: `add_brand_restrictions_to_prestashop_shops.php`

**CEL:** Konfiguracja dozwolonych marek pojazdów per sklep

```php
Schema::table('prestashop_shops', function (Blueprint $table) {
    // Allowed vehicle brands per shop (JSON array)
    $table->json('allowed_vehicle_brands')->nullable()->after('custom_field_mappings');
    /* Example:
    ["YCF", "Honda", "Yamaha"]
    NULL = wszystkie marki dozwolone (brak restrykcji)
    [] = brak dopasowań (shop nie obsługuje compatibility)
    */

    // Compatibility settings (JSON)
    $table->json('compatibility_settings')->nullable()->after('allowed_vehicle_brands');
    /* Example:
    {
        "enable_smart_suggestions": true,
        "auto_apply_suggestions": false,
        "min_confidence_score": 0.75,
        "show_unverified": true
    }
    */
});
```

**ARCHITEKTURA DECISION:**
- Flexibility: Admin może zdefiniować per-shop brand restrictions w panelu
- ProductForm automatycznie filtruje niedozwolone marki w shop context
- Domyślnie: brak restrykcji (wszystkie marki widoczne)

### 2.2 Nowe Tabele

#### 🆕 Tabela: `compatibility_suggestions` (CACHE TABLE)

**CEL:** Cache dla inteligentnych sugestii (performance optimization)

```sql
CREATE TABLE compatibility_suggestions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    part_sku VARCHAR(255) NOT NULL,
    vehicle_model_id BIGINT UNSIGNED NOT NULL,
    vehicle_sku VARCHAR(255) NOT NULL,
    shop_id BIGINT UNSIGNED NULLABLE,               -- NULL = global suggestions

    -- Suggestion metadata
    suggestion_reason ENUM('brand_match', 'name_match', 'description_match', 'category_match', 'manual') NOT NULL,
    confidence_score DECIMAL(3, 2) NOT NULL,        -- 0.00-1.00
    is_applied BOOLEAN DEFAULT 0,                   -- Czy użytkownik zaaplikował sugestię
    applied_at TIMESTAMP NULLABLE,
    applied_by BIGINT UNSIGNED NULLABLE,            -- FK to users

    -- Cache control
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,                  -- TTL: 24h

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_suggestion_product (product_id),
    INDEX idx_suggestion_part_sku (part_sku),
    INDEX idx_suggestion_shop (shop_id),
    INDEX idx_suggestion_score (confidence_score),
    INDEX idx_suggestion_applied (is_applied),
    INDEX idx_suggestion_expires (expires_at),

    UNIQUE KEY uniq_suggestion_product_vehicle_shop (product_id, vehicle_model_id, shop_id)
);
```

**ARCHITEKTURA DECISION:**
- Separate table dla cache (nie zaśmiecamy głównej tabeli compatibility)
- TTL 24h: suggestions regenerują się codziennie
- Tracking aplikowanych sugestii (audit trail)

#### 🆕 Tabela: `compatibility_bulk_operations` (AUDIT LOG)

**CEL:** Tracking bulk operations dla audytu

```sql
CREATE TABLE compatibility_bulk_operations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    operation_type ENUM('add', 'remove', 'verify', 'copy', 'apply_suggestions') NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,               -- Kto wykonał operację
    shop_id BIGINT UNSIGNED NULLABLE,               -- Context sklepu (NULL = global)

    -- Operation details (JSON)
    operation_data JSON NOT NULL,
    /* Example:
    {
        "product_ids": [123, 456, 789],
        "vehicle_model_ids": [10, 11],
        "compatibility_attribute_id": 1,
        "notes": "Bulk import from Excel"
    }
    */

    -- Results
    affected_rows INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULLABLE,

    started_at TIMESTAMP,
    completed_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE SET NULL,

    INDEX idx_bulk_op_user (user_id),
    INDEX idx_bulk_op_shop (shop_id),
    INDEX idx_bulk_op_status (status),
    INDEX idx_bulk_op_started (started_at)
);
```

**ARCHITEKTURA DECISION:**
- Audit trail dla compliance (kto, kiedy, co zmienił w bulk)
- JSON data flexibility (różne typy operacji, różne parametry)
- Status tracking dla long-running operations

---

## 3. ERD - Entity Relationship Diagram

```
┌─────────────────┐         ┌──────────────────────┐         ┌─────────────────┐
│    products     │         │ vehicle_compatibility │         │ vehicle_models  │
│─────────────────│         │──────────────────────│         │─────────────────│
│ • id (PK)       │1       N│ • id (PK)            │N       1│ • id (PK)       │
│ • sku (UNIQUE)  ├─────────┤ • product_id (FK)    ├─────────┤ • sku (UNIQUE)  │
│ • name          │         │ • part_sku           │         │ • brand         │
│ • product_type  │         │ • vehicle_model_id   │         │ • model         │
│ • manufacturer  │         │ • vehicle_sku        │         │ • variant       │
│ ...             │         │ • shop_id (FK) ⚡NEW │         │ • year_from     │
└─────────────────┘         │ • compatibility_attr │         │ • year_to       │
                            │ • compatibility_src  │         │ • engine_cc     │
                            │ • is_suggested ⚡NEW  │         │ • is_active     │
                            │ • confidence_score   │         └─────────────────┘
                            │ • metadata (JSON)    │
                            │ • verified           │
                            │ • verified_at        │
                            │ • verified_by (FK)   │
                            │ • notes              │
                            └──────────────────────┘
                                    │      │
                      ┌─────────────┘      └─────────────┐
                      │                                   │
                      │1                                  │N
         ┌────────────┴────────────┐         ┌───────────┴──────────┐
         │ compatibility_attributes│         │ prestashop_shops     │
         │─────────────────────────│         │──────────────────────│
         │ • id (PK)               │         │ • id (PK)            │
         │ • code (original, etc.) │         │ • name               │
         │ • name                  │         │ • allowed_brands ⚡NEW│
         │ • color (badge)         │         │ • compatibility_cfg  │
         │ • is_active             │         │ ...                  │
         └─────────────────────────┘         └──────────────────────┘


┌───────────────────────────┐         ┌───────────────────────────┐
│ compatibility_suggestions │         │ compatibility_bulk_ops    │
│───────────────────────────│         │───────────────────────────│
│ • id (PK)                 │         │ • id (PK)                 │
│ • product_id (FK)         │         │ • operation_type          │
│ • vehicle_model_id (FK)   │         │ • user_id (FK)            │
│ • shop_id (FK) NULLABLE   │         │ • shop_id (FK) NULLABLE   │
│ • suggestion_reason       │         │ • operation_data (JSON)   │
│ • confidence_score        │         │ • affected_rows           │
│ • is_applied              │         │ • status                  │
│ • applied_at              │         │ • started_at              │
│ • expires_at (TTL 24h)    │         │ • completed_at            │
└───────────────────────────┘         └───────────────────────────┘
          (CACHE TABLE)                       (AUDIT LOG)
```

**LEGENDA:**
- ⚡NEW - Nowe kolumny/tabele w przeprojektowaniu
- PK - Primary Key
- FK - Foreign Key
- UNIQUE - Unique constraint
- N:M - Many-to-many relationship via pivot table

---

## 4. ARCHITEKTURA KOMPONENTÓW

### 4.1 Service Layer

#### 🔧 Rozszerzenie: `CompatibilityManager.php`

**Nowe metody:**

```php
/**
 * Get compatibility with shop context (per-shop filtering)
 */
public function getCompatibilityForShop(int $productId, ?int $shopId = null): Collection

/**
 * Apply smart suggestions for product
 */
public function applySuggestions(
    int $productId,
    array $suggestionIds,
    User $user,
    ?int $shopId = null
): int

/**
 * Remove compatibility with shop context
 */
public function removeCompatibilityForShop(
    int $productId,
    int $vehicleModelId,
    ?int $shopId = null
): bool

/**
 * Bulk add compatibility (Excel-like workflow)
 */
public function bulkAddCompatibility(
    array $productIds,
    array $vehicleModelIds,
    int $compatibilityAttributeId,
    ?int $shopId = null,
    User $user
): int
```

#### 🆕 Nowy Service: `SmartSuggestionEngine.php`

**Lokalizacja:** `app/Services/Compatibility/SmartSuggestionEngine.php`

**Odpowiedzialność:** Generowanie inteligentnych sugestii dopasowań

```php
class SmartSuggestionEngine
{
    /**
     * Generate suggestions for product based on rules:
     * 1. Marka części = marka pojazdu (YCF → YCF)
     * 2. Nazwa/opis części zawiera nazwę pojazdu
     * 3. Kategoria produktu → kategorie pojazdów
     */
    public function generateSuggestions(
        Product $product,
        ?int $shopId = null
    ): Collection

    /**
     * Calculate confidence score (0.00-1.00)
     * - 1.00: Exact brand + name match
     * - 0.75: Brand match + partial name
     * - 0.50: Name match only
     * - 0.25: Category match
     */
    private function calculateConfidenceScore(
        Product $product,
        VehicleModel $vehicle,
        string $matchReason
    ): float

    /**
     * Filter vehicles by shop brand restrictions
     */
    private function filterByShopBrands(
        Collection $vehicles,
        ?int $shopId
    ): Collection

    /**
     * Cache suggestions (TTL: 24h)
     */
    public function cacheSuggestions(
        int $productId,
        Collection $suggestions,
        ?int $shopId = null
    ): void

    /**
     * Get cached suggestions
     */
    public function getCachedSuggestions(
        int $productId,
        ?int $shopId = null
    ): ?Collection
}
```

**ALGORITHMIC LOGIC:**

```
Step 1: Brand Match (highest priority)
  IF product.manufacturer == vehicle.brand THEN score += 0.50

Step 2: Name/Description Match
  IF product.name CONTAINS vehicle.brand OR vehicle.model THEN score += 0.30
  IF product.long_description CONTAINS vehicle.model THEN score += 0.10

Step 3: Category Match (contextual)
  IF product.category.name CONTAINS "Pojazdy" AND product.product_type == 'vehicle' THEN score += 0.10

Step 4: Shop Brand Filtering
  IF shop_id NOT NULL THEN
    allowed_brands = shop.allowed_vehicle_brands
    IF allowed_brands NOT NULL AND vehicle.brand NOT IN allowed_brands THEN
      SKIP vehicle (filtered out)

Result: Suggestions sorted by confidence_score DESC
```

#### 🆕 Nowy Service: `ShopFilteringService.php`

**Lokalizacja:** `app/Services/Compatibility/ShopFilteringService.php`

**Odpowiedzialność:** Per-shop brand restrictions i filtering

```php
class ShopFilteringService
{
    /**
     * Get allowed vehicle brands for shop
     *
     * @return array|null  NULL = wszystkie dozwolone, [] = żadne, ['YCF'] = tylko YCF
     */
    public function getAllowedBrands(?int $shopId): ?array

    /**
     * Check if brand is allowed for shop
     */
    public function isBrandAllowed(string $brand, ?int $shopId): bool

    /**
     * Filter vehicle models by shop restrictions
     */
    public function filterVehiclesByShop(
        Collection $vehicles,
        ?int $shopId
    ): Collection

    /**
     * Get shop compatibility settings
     */
    public function getShopSettings(?int $shopId): array

    /**
     * Update shop allowed brands (Admin action)
     */
    public function updateAllowedBrands(
        int $shopId,
        array $brands,
        User $admin
    ): bool
}
```

#### 🔧 Rozszerzenie: `CompatibilityBulkService.php`

**Nowe metody:**

```php
/**
 * Bulk add compatibility (Excel-like workflow)
 *
 * @param array $productIds Product IDs to add compatibility
 * @param array $vehicleModelIds Vehicle IDs to match
 * @param int $compatibilityAttributeId Original/Replacement/etc.
 * @param int|null $shopId Shop context (NULL = global)
 * @param User $user Who performs the operation
 * @return int Number of added records
 */
public function bulkAdd(
    array $productIds,
    array $vehicleModelIds,
    int $compatibilityAttributeId,
    ?int $shopId,
    User $user
): int

/**
 * Bulk remove compatibility
 */
public function bulkRemove(
    array $productIds,
    array $vehicleModelIds,
    ?int $shopId
): int

/**
 * Bulk verify compatibility
 */
public function bulkVerify(
    array $compatibilityIds,
    User $user
): int

/**
 * Apply smart suggestions in bulk
 */
public function bulkApplySuggestions(
    array $suggestionIds,
    User $user
): int
```

### 4.2 Livewire Components Layer

#### 🔧 Przeprojektowanie: `CompatibilityManagement.php`

**Lokalizacja:** `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php`

**STATUS:** Replace MOCK DATA with real implementation

**Nowe properties:**
```php
public ?int $shopContext = null;              // Per-shop filtering context
public bool $showSuggestions = true;          // Toggle smart suggestions
public float $minConfidenceScore = 0.50;      // Minimum confidence dla suggestions
public string $viewMode = 'parts';            // 'parts' | 'vehicles'
public array $bulkSelectedParts = [];
public array $bulkSelectedVehicles = [];
public bool $showBulkModal = false;
public string $bulkOperation = 'add';         // 'add' | 'remove' | 'verify'
```

**Nowe metody:**
```php
/**
 * Switch shop context (trigger per-shop filtering)
 */
public function switchShopContext(?int $shopId): void

/**
 * Load parts with compatibility data (real query, NOT mock)
 */
public function loadParts(): LengthAwarePaginator

/**
 * Load vehicles with compatibility data (reverse view)
 */
public function loadVehicles(): LengthAwarePaginator

/**
 * Toggle suggestion visibility
 */
public function toggleSuggestion(int $suggestionId): void

/**
 * Apply suggestion (add to compatibility)
 */
public function applySuggestion(int $suggestionId): void

/**
 * Bulk apply all suggestions for part
 */
public function bulkApplySuggestions(int $productId): void

/**
 * Open bulk operation modal
 */
public function openBulkModal(string $operation): void

/**
 * Execute bulk operation
 */
public function executeBulkOperation(): void
```

**Query Optimization:**
```php
// Eager loading relationships (N+1 problem prevention)
public function loadParts(): LengthAwarePaginator
{
    return Product::with([
        'vehicleCompatibility.vehicleModel',
        'vehicleCompatibility.compatibilityAttribute',
        'vehicleCompatibility.compatibilitySource'
    ])
    ->where('product_type', 'spare_part')
    ->when($this->shopContext, function($q) {
        $q->whereHas('vehicleCompatibility', function($q2) {
            $q2->where('shop_id', $this->shopContext)
               ->orWhereNull('shop_id'); // Fallback to global
        });
    })
    ->when($this->searchPart, function($q) {
        $q->where(function($q2) {
            $q2->where('sku', 'like', '%' . $this->searchPart . '%')
               ->orWhere('name', 'like', '%' . $this->searchPart . '%');
        });
    })
    ->orderBy($this->sortField, $this->sortDirection)
    ->paginate(50);
}
```

#### 🆕 Nowy Component: `ProductFormCompatibilityTab.php`

**Lokalizacja:** `app/Http/Livewire/Products/Management/Tabs/CompatibilityTab.php`

**Odpowiedzialność:** TAB w ProductForm dla indywidualnej edycji dopasowań

```php
class CompatibilityTab extends Component
{
    public Product $product;
    public ?int $shopContext = null;
    public bool $showSuggestions = true;

    // Compatibility lists
    public Collection $originalVehicles;
    public Collection $replacementVehicles;
    public Collection $suggestions;

    // Search & filters
    public string $searchVehicle = '';
    public string $filterBrand = '';

    /**
     * Mount component
     */
    public function mount(Product $product): void

    /**
     * Load compatibility for product
     */
    public function loadCompatibility(): void

    /**
     * Load smart suggestions
     */
    public function loadSuggestions(): void

    /**
     * Add vehicle to Original group
     */
    public function addOriginal(int $vehicleModelId): void

    /**
     * Add vehicle to Replacement group
     */
    public function addReplacement(int $vehicleModelId): void

    /**
     * Remove vehicle compatibility
     */
    public function removeVehicle(int $vehicleModelId, string $type): void

    /**
     * Apply suggestion
     */
    public function applySuggestion(int $suggestionId, string $type): void

    /**
     * Render component
     */
    public function render(): View
}
```

#### 🆕 Nowy Component: `VehicleQuickPicker.php`

**Lokalizacja:** `app/Http/Livewire/Components/VehicleQuickPicker.php`

**Odpowiedzialność:** Autocomplete picker dla szybkiego dodawania pojazdów

```php
class VehicleQuickPicker extends Component
{
    public string $search = '';
    public Collection $results;
    public ?int $shopContext = null;
    public bool $showOnlyActive = true;
    public ?string $filterBrand = null;

    /**
     * Real-time search (wire:model.live)
     */
    public function updatedSearch(): void

    /**
     * Select vehicle (emit event to parent)
     */
    public function selectVehicle(int $vehicleModelId): void

    /**
     * Render component
     */
    public function render(): View
}
```

---

## 5. UX WORKFLOW DESIGN

### 5.1 Panel Masowej Edycji (/admin/compatibility)

#### LAYOUT STRUCTURE

```
┌────────────────────────────────────────────────────────────────┐
│ COMPATIBILITY PANEL - Masowa Edycja Dopasowań                  │
├────────────────────────────────────────────────────────────────┤
│ [🔍 Search Part: SKU/Name______] [Shop: All▼] [Brand: All▼]   │
│ [View: Parts▼] [☑ Show Suggestions]  [Min Confidence: 0.50___]│
├────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ 📦 PART-001 | Brake Pad Front YCF Pilot       [▶ Expand] │ │
│ │                                                              │ │
│ │ ──────────────────────────────────────────────────────────  │ │
│ │ 📦 PART-002 | Air Filter YCF 50cc/110cc       [▼ Collapse]│ │
│ │                                                              │ │
│ │   ORYGINAŁ (2):                                             │ │
│ │   ☑ YCF Pilot 50 (2015-2023) ✅ Verified     [❌ Remove]   │ │
│ │   ☑ YCF Pilot 110 (2015-2023) ⏳ Pending     [❌ Remove]   │ │
│ │                                                              │ │
│ │   ZAMIENNIK (1):                                            │ │
│ │   ☑ Honda CRF 50 (2020-2023) ✅ Verified     [❌ Remove]   │ │
│ │                                                              │ │
│ │   💡 SUGESTIE (3): [Confidence ≥ 0.50]                     │ │
│ │   ⭐ 0.95 YCF F125 (2018-2023) Brand+Name    [✅ Apply]    │ │
│ │   ⭐ 0.75 YCF Pilot 125 (2016-2023) Brand    [✅ Apply]    │ │
│ │   ⭐ 0.65 Pitbike 125cc (2019-2023) Category [✅ Apply]    │ │
│ │                                                              │ │
│ │   [+ Add Vehicle_______________] [🔄 Refresh Suggestions]  │ │
│ ├────────────────────────────────────────────────────────────┤ │
│ └────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────┤
│ [1] [2] [3] ... [25] (showing 1-50 of 1,245 parts)            │
└────────────────────────────────────────────────────────────────┘

BULK ACTIONS (when items selected):
┌────────────────────────────────────────────────────────────────┐
│ ☑ 5 parts selected                                             │
│ [➕ Bulk Add Vehicles] [➖ Bulk Remove] [✅ Bulk Verify]       │
└────────────────────────────────────────────────────────────────┘
```

#### INTERACTION FLOW

**Scenariusz 1: Dodanie pojedynczego dopasowania**

1. User wyszukuje część: `PART-002`
2. Rozwija wiersz: Click `[▶ Expand]`
3. Widzi sugestie: `💡 SUGESTIE (3)`
4. Klika `[✅ Apply]` na sugestii
5. Backend: `SmartSuggestionEngine->applySuggestion()`
6. UI update: Sugestia przesuwa się do sekcji ORYGINAŁ/ZAMIENNIK
7. Toast notification: "✅ Dodano dopasowanie"

**Scenariusz 2: Bulk Add Vehicles (Excel-like workflow)**

1. User zaznacza checkboxy: `☑ PART-001`, `☑ PART-002`, `☑ PART-003`
2. Klika: `[➕ Bulk Add Vehicles]`
3. Modal otwiera się:
   ```
   ┌──────────────────────────────────────────┐
   │ BULK ADD VEHICLES                        │
   ├──────────────────────────────────────────┤
   │ Selected Parts: 3                        │
   │                                          │
   │ Select Vehicles (multi-select):         │
   │ [🔍 Search: brand, model________]       │
   │                                          │
   │ ☑ YCF Pilot 50 (2015-2023)              │
   │ ☑ YCF Pilot 110 (2015-2023)             │
   │ ☐ YCF F125 (2018-2023)                  │
   │ ☐ Honda CRF 50 (2020-2023)              │
   │                                          │
   │ Compatibility Type:                      │
   │ ○ Oryginał  ● Zamiennik  ○ Performance  │
   │                                          │
   │ Shop Context: [B2B Test DEV ▼]          │
   │                                          │
   │ [💾 Add (2 vehicles × 3 parts = 6)]     │
   │ [❌ Cancel]                              │
   └──────────────────────────────────────────┘
   ```
4. User wybiera vehicles (2), type (Zamiennik), shop context
5. Klika `[💾 Add]`
6. Backend: `CompatibilityBulkService->bulkAdd()`
7. DB transaction: 6 inserts do `vehicle_compatibility`
8. Audit log: Insert do `compatibility_bulk_operations`
9. UI update: Rozwija wiersze, pokazuje nowe dopasowania
10. Toast notification: "✅ Dodano 6 dopasowań"

**Scenariusz 3: Per-Shop Filtering**

1. User przełącza shop context: `[Shop: All ▼]` → `B2B Test DEV`
2. Backend: `ShopFilteringService->filterVehiclesByShop()`
3. Query: `WHERE shop_id = 2 OR shop_id IS NULL` (fallback)
4. Brand filtering: Only YCF vehicles (shop allowed_brands = ["YCF"])
5. UI update: Lista pojazdów filtrowana, sugestie przefiltrowane
6. Info banner: "ℹ️ Showing compatibility for B2B Test DEV (YCF only)"

### 5.2 ProductForm TAB (Indywidualna Edycja)

#### LAYOUT STRUCTURE

```
ProductForm → Tabs:
[📝 Podstawowe] [📁 Kategorie] [💰 Ceny] [📦 Stany] ... [🚗 Dopasowania] ← NEW TAB

┌────────────────────────────────────────────────────────────────┐
│ TAB: DOPASOWANIA                                                │
├────────────────────────────────────────────────────────────────┤
│ Product: PART-002 | Air Filter YCF 50cc/110cc                  │
│                                                                 │
│ Shop Context: [🏪 Dane domyślne ▼]                             │
│               [B2B Test DEV] [Pitbike Store]                    │
├────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ ORYGINAŁ (Original Fit)                      [+ Add_____]  ││
│ │ ─────────────────────────────────────────────────────────  ││
│ │ ✅ YCF Pilot 50 (2015-2023) ✅ Verified      [❌ Remove]  ││
│ │ ✅ YCF Pilot 110 (2015-2023) ⏳ Pending      [❌ Remove]  ││
│ │                                                             ││
│ │ Drag & Drop: Przeciągnij pojazd do Zamiennik ───────────► ││
│ └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ ZAMIENNIK (Replacement)                      [+ Add_____]  ││
│ │ ─────────────────────────────────────────────────────────  ││
│ │ ✅ Honda CRF 50 (2020-2023) ✅ Verified      [❌ Remove]  ││
│ │ ✅ Pitbike 125cc (2019-2023) ✅ Verified     [❌ Remove]  ││
│ └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ 💡 INTELIGENTNE SUGESTIE (3)        [🔄 Refresh]          ││
│ │ ─────────────────────────────────────────────────────────  ││
│ │ ⭐ 0.95 YCF F125 (2018-2023)                              ││
│ │     Reason: Brand match + Name "YCF" in description       ││
│ │     [✅ Add as Original] [✅ Add as Replacement]          ││
│ │                                                             ││
│ │ ⭐ 0.75 YCF Pilot 125 (2016-2023)                         ││
│ │     Reason: Brand match                                    ││
│ │     [✅ Add as Original] [✅ Add as Replacement]          ││
│ │                                                             ││
│ │ ⭐ 0.65 Pitbike 125cc (2019-2023)                         ││
│ │     Reason: Category match (Części > Silniki)             ││
│ │     [✅ Add as Replacement]                               ││
│ └─────────────────────────────────────────────────────────────┘│
├────────────────────────────────────────────────────────────────┤
│ [💾 Zapisz]  [🔄 Sync to PrestaShop]  [📋 Copy from Product]  │
└────────────────────────────────────────────────────────────────┘
```

#### INTERACTION FLOW

**Scenariusz 1: Dodanie pojazdu do Oryginał**

1. User klika `[+ Add]` w sekcji ORYGINAŁ
2. Autocomplete picker otwiera się (VehicleQuickPicker component)
3. User wpisuje: "YCF F"
4. Real-time search: `wire:model.live="search"`
5. Wyniki filtrowane przez ShopFilteringService (per-shop brands)
6. User wybiera: "YCF F125 (2018-2023)"
7. Backend: `CompatibilityManager->addCompatibility()`
8. UI update: Pojazd pojawia się w ORYGINAŁ
9. Toast: "✅ Dodano dopasowanie"

**Scenariusz 2: Przeciągnięcie pojazdu (Drag & Drop)**

1. User chwyta kafelek: "YCF Pilot 50" z ORYGINAŁ
2. Alpine.js: `x-sortable` directive
3. User przeciąga do sekcji ZAMIENNIK
4. Drop event: `wire:drop="moveToReplacement(1)"`
5. Backend: Update `compatibility_attribute_id` z Original → Replacement
6. UI update: Kafelek przesuwa się do ZAMIENNIK
7. Toast: "✅ Przeniesiono do Zamiennik"

**Scenariusz 3: Aplikowanie sugestii**

1. User widzi sugestię: ⭐ 0.95 YCF F125
2. Klika: `[✅ Add as Original]`
3. Backend: `SmartSuggestionEngine->applySuggestion()`
4. DB: Insert do `vehicle_compatibility` + Update `compatibility_suggestions.is_applied = 1`
5. UI update: Sugestia znika, pojazd pojawia się w ORYGINAŁ
6. Toast: "✅ Zastosowano sugestię (confidence: 0.95)"

### 5.3 Kafelki do Szybkiego Zaznaczania (Tile-based UI)

**DESIGN PATTERN:**Clickable tiles zamiast checkboxów (szybsze zaznaczanie)

```
┌─────────────────────────────────────────────────────────────┐
│ VEHICLES (click to toggle selection)                        │
├─────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐         │
│ │ ✅ SELECTED  │ │              │ │              │         │
│ │ YCF Pilot 50 │ │ YCF Pilot 110│ │ YCF F125     │         │
│ │ (2015-2023)  │ │ (2015-2023)  │ │ (2018-2023)  │         │
│ │ 50cc         │ │ 110cc        │ │ 125cc        │         │
│ └──────────────┘ └──────────────┘ └──────────────┘         │
│ [Selected: 1/3]  [✅ Select All] [❌ Deselect All]         │
└─────────────────────────────────────────────────────────────┘
```

**CSS (TailwindCSS + Alpine.js):**
```html
<div
    class="vehicle-tile"
    :class="{'selected': selectedVehicles.includes(vehicleId)}"
    @click="toggleSelection(vehicleId)"
    x-data
>
    <div class="tile-header">
        <span class="brand">{{ brand }}</span>
        <span class="model">{{ model }}</span>
    </div>
    <div class="tile-body">
        <span class="year-range">({{ yearFrom }}-{{ yearTo }})</span>
        <span class="engine">{{ engineCc }}cc</span>
    </div>
</div>

<style>
.vehicle-tile {
    @apply border-2 border-gray-300 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-500;
}
.vehicle-tile.selected {
    @apply border-blue-600 bg-blue-50;
}
</style>
```

---

## 6. PER-SHOP FILTERING LOGIC

### 6.1 Business Rules

**Rule 1:** Dane domyślne (shop_id = NULL)
- Globalne dopasowania dla wszystkich sklepów
- Brak restrykcji marek pojazdów
- Fallback jeśli brak per-shop data

**Rule 2:** Per-shop override (shop_id = X)
- Specyficzne dopasowania dla sklepu X
- Filtrowane przez `allowed_vehicle_brands` sklepu
- Nadpisuje dane domyślne (jeśli istnieją)

**Rule 3:** Brand Restrictions
- Admin definiuje `allowed_vehicle_brands` per sklep w panelu
- NULL = wszystkie marki dozwolone (brak restrykcji)
- [] = brak dopasowań (shop nie obsługuje compatibility)
- ["YCF", "Honda"] = tylko YCF i Honda

**Rule 4:** Query Fallback
```sql
-- Get compatibility for product + shop
SELECT * FROM vehicle_compatibility
WHERE product_id = ?
AND (shop_id = ? OR shop_id IS NULL)
ORDER BY shop_id DESC NULLS LAST;
-- Result: Per-shop records first, then global fallback
```

### 6.2 Admin Configuration Panel

**Lokalizacja:** `/admin/shops/{shop}/compatibility-settings`

```
┌────────────────────────────────────────────────────────────────┐
│ SHOP: B2B Test DEV - Compatibility Settings                    │
├────────────────────────────────────────────────────────────────┤
│ Allowed Vehicle Brands:                                         │
│ [Multi-select with search]                                      │
│                                                                 │
│ ☑ YCF                                                           │
│ ☐ Honda                                                         │
│ ☐ Yamaha                                                        │
│ ☐ Kawasaki                                                      │
│ ☐ Pitbike                                                       │
│                                                                 │
│ ⚠️ NULL = All brands allowed (no restrictions)                 │
│ ⚠️ [] Empty = No compatibility support for this shop            │
├────────────────────────────────────────────────────────────────┤
│ Smart Suggestions Settings:                                     │
│ ☑ Enable smart suggestions                                     │
│ ☐ Auto-apply suggestions (confidence ≥ [0.90_])               │
│ ☑ Show unverified compatibility                                │
│ Minimum confidence score: [0.50___] (0.00-1.00)                │
├────────────────────────────────────────────────────────────────┤
│ [💾 Save Settings]  [🔄 Reset to Defaults]                     │
└────────────────────────────────────────────────────────────────┘
```

### 6.3 Implementation Code

#### ShopFilteringService.php

```php
public function filterVehiclesByShop(
    Collection $vehicles,
    ?int $shopId
): Collection {
    if ($shopId === null) {
        return $vehicles; // No filtering for global context
    }

    $shop = PrestaShopShop::find($shopId);
    if (!$shop) {
        return $vehicles;
    }

    $allowedBrands = $shop->allowed_vehicle_brands; // JSON array

    // NULL = wszystkie dozwolone (brak restrykcji)
    if ($allowedBrands === null) {
        return $vehicles;
    }

    // [] = żadne (shop nie obsługuje compatibility)
    if (empty($allowedBrands)) {
        return collect([]);
    }

    // Filter by allowed brands
    return $vehicles->filter(function($vehicle) use ($allowedBrands) {
        return in_array($vehicle->brand, $allowedBrands);
    });
}
```

#### ProductFormCompatibilityTab.php

```php
public function loadCompatibility(): void
{
    // Query with shop context
    $this->originalVehicles = VehicleCompatibility::byProduct($this->product->id)
        ->where('shop_id', $this->shopContext)
        ->orWhereNull('shop_id') // Fallback to global
        ->whereHas('compatibilityAttribute', function($q) {
            $q->where('code', CompatibilityAttribute::CODE_ORIGINAL);
        })
        ->with('vehicleModel')
        ->get();

    // Filter by shop brand restrictions
    $this->originalVehicles = $this->filterByShopBrands($this->originalVehicles);
}

private function filterByShopBrands(Collection $compatibilities): Collection
{
    if ($this->shopContext === null) {
        return $compatibilities; // No filtering for global
    }

    $filteringService = app(ShopFilteringService::class);

    return $compatibilities->filter(function($compatibility) use ($filteringService) {
        $vehicle = $compatibility->vehicleModel;
        return $filteringService->isBrandAllowed($vehicle->brand, $this->shopContext);
    });
}
```

---

## 7. PLAN IMPLEMENTACJI (FAZY)

### 📊 TIMELINE OVERVIEW

**Total Estimated Time:** 80-100 godzin (10-12 dni roboczych)

```
FAZA 1: Database & Models        [████████░░] 8h   (1 dzień)
FAZA 2: Services Layer            [████████░░] 12h  (1.5 dnia)
FAZA 3: CompatibilityPanel UI     [██████████] 20h  (2.5 dnia)
FAZA 4: ProductForm TAB           [████████░░] 16h  (2 dni)
FAZA 5: Smart Suggestions         [████████░░] 12h  (1.5 dnia)
FAZA 6: Per-Shop Filtering        [████████░░] 10h  (1.25 dnia)
FAZA 7: Testing & Polish          [████████░░] 12h  (1.5 dnia)
──────────────────────────────────────────────────────
TOTAL:                            90h (11.25 dni)
```

### FAZA 1: Database & Models (8h)

**Priority:** 🔴 CRITICAL (Foundation)

**Zadania:**

1.1 ✅ Migration: `add_shop_support_to_vehicle_compatibility.php` (2h)
   - Add `shop_id` column (NULLABLE, FK to prestashop_shops)
   - Add `is_suggested`, `confidence_score`, `metadata` columns
   - Add indexes (shop_id, product_shop, suggested)
   - Update unique constraint (product, vehicle, shop)
   - Test migration on production (backup first!)

1.2 ✅ Migration: `add_brand_restrictions_to_prestashop_shops.php` (1h)
   - Add `allowed_vehicle_brands` JSON column
   - Add `compatibility_settings` JSON column
   - Test migration on production

1.3 ✅ Migration: `create_compatibility_suggestions_table.php` (2h)
   - Create new table per schema (see section 2.2)
   - Indexes for performance
   - Foreign keys with cascade rules
   - Test migration on production

1.4 ✅ Migration: `create_compatibility_bulk_operations_table.php` (1h)
   - Create audit log table per schema
   - Indexes for audit queries
   - Foreign keys
   - Test migration on production

1.5 ✅ Update Models (2h)
   - VehicleCompatibility.php: Add `shop_id`, `is_suggested`, `confidence_score`, `metadata` to fillable
   - VehicleCompatibility.php: Add casts (metadata → array)
   - VehicleCompatibility.php: Add scopes (byShop, suggested, highConfidence)
   - PrestaShopShop.php: Add `allowed_vehicle_brands`, `compatibility_settings` to fillable/casts
   - Create CompatibilitySuggestion.php model
   - Create CompatibilityBulkOperation.php model

**Deliverables:**
- ✅ 4 migracje wdrożone na production
- ✅ 4 modele Eloquent zaktualizowane/utworzone
- ✅ Schema documentation update (`_DOCS/Struktura_Bazy_Danych.md`)

### FAZA 2: Services Layer (12h)

**Priority:** 🔴 CRITICAL (Business Logic)

**Zadania:**

2.1 ✅ Create: `SmartSuggestionEngine.php` (5h)
   - Implement `generateSuggestions()` with algorithm (brand/name/category matching)
   - Implement `calculateConfidenceScore()` (0.00-1.00 scale)
   - Implement `filterByShopBrands()` (per-shop filtering)
   - Implement `cacheSuggestions()` / `getCachedSuggestions()` (TTL 24h)
   - Unit tests (PHPUnit)

2.2 ✅ Create: `ShopFilteringService.php` (3h)
   - Implement `getAllowedBrands()` (return array|null)
   - Implement `isBrandAllowed()` (boolean check)
   - Implement `filterVehiclesByShop()` (collection filtering)
   - Implement `getShopSettings()` / `updateAllowedBrands()` (admin actions)
   - Unit tests

2.3 ✅ Extend: `CompatibilityManager.php` (2h)
   - Add `getCompatibilityForShop()` method
   - Add `applySuggestions()` method
   - Add `removeCompatibilityForShop()` method
   - Add `bulkAddCompatibility()` method
   - Integration tests

2.4 ✅ Extend: `CompatibilityBulkService.php` (2h)
   - Add `bulkAdd()` method (Excel-like workflow)
   - Add `bulkRemove()` method
   - Add `bulkVerify()` method
   - Add `bulkApplySuggestions()` method
   - Audit logging (insert to compatibility_bulk_operations)
   - Integration tests

**Deliverables:**
- ✅ 2 nowe serwisy (SmartSuggestionEngine, ShopFilteringService)
- ✅ 2 rozszerzone serwisy (CompatibilityManager, CompatibilityBulkService)
- ✅ Unit tests coverage ≥ 80%

### FAZA 3: CompatibilityPanel UI (20h)

**Priority:** 🟡 HIGH (Core Feature)

**Zadania:**

3.1 ✅ Refactor: `CompatibilityManagement.php` component (8h)
   - Replace MOCK DATA with real queries (loadParts, loadVehicles)
   - Implement shop context switching (`switchShopContext()`)
   - Implement expand/collapse logic (`toggleExpand()`)
   - Implement search & filters (part SKU/name, brand, shop)
   - Implement sorting (SKU, name, compatibility count)
   - Eager loading relationships (prevent N+1)
   - Pagination (50 per page)

3.2 ✅ Blade View: `compatibility-management.blade.php` (6h)
   - Layout structure per UX design (section 5.1)
   - Parts list with expand/collapse
   - Compatibility groups (Oryginał, Zamiennik)
   - Smart suggestions section (badges, confidence scores)
   - Bulk actions toolbar (selected items counter)
   - Empty states (no parts, no compatibility)

3.3 ✅ Alpine.js: Interactive UI (4h)
   - Expand/collapse animations
   - Checkbox multi-select (x-data tracking)
   - Bulk modal trigger
   - Real-time filters (wire:model.live)
   - Toast notifications (success/error)

3.4 ✅ CSS: Styling (2h)
   - Enterprise-consistent styling (`resources/css/admin/compatibility.css`)
   - Responsive layout (mobile/tablet/desktop)
   - Badge colors (Original: green, Replacement: blue, Suggested: amber)
   - Hover effects (tiles, buttons)

**Deliverables:**
- ✅ Working CompatibilityPanel at `/admin/compatibility`
- ✅ Real data queries (no mock)
- ✅ Responsive UI
- ✅ Manual testing checklist passed

### FAZA 4: ProductForm TAB (16h)

**Priority:** 🟡 HIGH (Core Feature)

**Zadania:**

4.1 ✅ Create: `ProductFormCompatibilityTab.php` Livewire component (6h)
   - Mount with product ID
   - Load compatibility (`loadCompatibility()`)
   - Load suggestions (`loadSuggestions()`)
   - Add vehicle to Original/Replacement (`addOriginal()`, `addReplacement()`)
   - Remove vehicle (`removeVehicle()`)
   - Apply suggestion (`applySuggestion()`)
   - Shop context switching

4.2 ✅ Create: `VehicleQuickPicker.php` autocomplete component (4h)
   - Real-time search (wire:model.live)
   - Results filtering (shop brands, active only)
   - Select vehicle (emit event to parent)
   - Keyboard navigation (up/down/enter)

4.3 ✅ Blade View: `tabs/compatibility-tab.blade.php` (4h)
   - Layout per UX design (section 5.2)
   - Shop context tabs (Dane domyślne, per-shop)
   - Original/Replacement sections
   - Suggestions section with apply buttons
   - Vehicle quick picker integration

4.4 ✅ Integrate: Add TAB to ProductForm navigation (2h)
   - Update `product-form.blade.php` tab navigation
   - Update `ProductForm.php` component (tab state)
   - Routing: preserve tab state in URL query string
   - Save action: persist compatibility changes

**Deliverables:**
- ✅ Working "Dopasowania" TAB in ProductForm
- ✅ Autocomplete vehicle picker
- ✅ Smart suggestions integration
- ✅ Per-shop context switching

### FAZA 5: Smart Suggestions (12h)

**Priority:** 🟢 MEDIUM (Enhancement)

**Zadania:**

5.1 ✅ Algorithm Implementation (5h)
   - Brand matching logic (product.manufacturer == vehicle.brand)
   - Name/description matching (fuzzy search, contains)
   - Category contextual matching
   - Confidence score calculation (weighted algorithm)
   - Test with real data (100+ products)

5.2 ✅ Cache Layer (3h)
   - Store suggestions in `compatibility_suggestions` table
   - TTL 24h (automatic expiry)
   - Invalidation on product update
   - Batch generation (queue job for 1000+ products)

5.3 ✅ UI Integration (4h)
   - Suggestions section in CompatibilityPanel
   - Suggestions section in ProductForm TAB
   - Confidence badges (⭐ 0.95 = green, ⭐ 0.50 = amber)
   - Apply buttons (add as Original/Replacement)
   - Refresh suggestions button

**Deliverables:**
- ✅ Working smart suggestions algorithm
- ✅ Cache layer with TTL
- ✅ UI integration in 2 places
- ✅ Performance: <200ms per product

### FAZA 6: Per-Shop Filtering (10h)

**Priority:** 🟢 MEDIUM (Business Requirement)

**Zadania:**

6.1 ✅ Admin Configuration Panel (4h)
   - Route: `/admin/shops/{shop}/compatibility-settings`
   - Livewire component: `ShopCompatibilitySettings.php`
   - Blade view: Multi-select dla allowed_vehicle_brands
   - Save action: Update `prestashop_shops` table
   - Validation: At least 1 brand OR NULL (all allowed)

6.2 ✅ Filtering Logic Integration (4h)
   - CompatibilityPanel: Shop context dropdown
   - ProductForm TAB: Shop context tabs
   - ShopFilteringService integration
   - Query optimization (compound indexes)
   - Test with restricted shop (only YCF)

6.3 ✅ UI Indicators (2h)
   - Info banner: "Showing compatibility for B2B Test DEV (YCF only)"
   - Disabled tiles (vehicle not allowed for shop)
   - Filter summary: "3 brands allowed, 25 vehicles available"

**Deliverables:**
- ✅ Admin configuration panel
- ✅ Per-shop filtering working in 2 places
- ✅ UI indicators for filtered state

### FAZA 7: Testing & Polish (12h)

**Priority:** 🔴 CRITICAL (Quality Assurance)

**Zadania:**

7.1 ✅ Unit Tests (4h)
   - SmartSuggestionEngine tests (algorithm, confidence score)
   - ShopFilteringService tests (brand restrictions)
   - CompatibilityManager tests (CRUD operations)
   - CompatibilityBulkService tests (bulk operations)
   - Coverage ≥ 80%

7.2 ✅ Integration Tests (3h)
   - ProductForm TAB workflow (add/remove vehicles)
   - CompatibilityPanel workflow (bulk operations)
   - Per-shop filtering workflow
   - Smart suggestions workflow (apply/refresh)

7.3 ✅ Manual Testing (3h)
   - Chrome DevTools MCP verification (MANDATORY)
   - Test wszystkich scenariuszy z section 5
   - Test per-shop filtering (3 shops, different brands)
   - Test performance (1000+ products, 500+ vehicles)
   - Test mobile/tablet/desktop responsive

7.4 ✅ Bug Fixes & Polish (2h)
   - Fix edge cases
   - UI/UX polish (spacing, colors, animations)
   - Error handling improvements
   - Loading states (wire:loading)

**Deliverables:**
- ✅ Test suite passed (unit + integration)
- ✅ Manual testing checklist completed
- ✅ Chrome DevTools verification passed
- ✅ Zero critical bugs
- ✅ Performance benchmarks met

---

## 8. ESTYMACJE CZASOWE

### 8.1 Breakdown per Agent

| Agent Type | FAZA | Estimated Hours | Days (8h/day) |
|------------|------|----------------|---------------|
| **laravel-expert** | FAZA 1, 2 | 20h | 2.5 |
| **livewire-specialist** | FAZA 3, 4 | 36h | 4.5 |
| **frontend-specialist** | FAZA 3, 4 | 12h | 1.5 |
| **architect** (this) | FAZA 5, 6 | 12h | 1.5 |
| **debugger** | FAZA 7 | 10h | 1.25 |
| **deployment-specialist** | Deploy all | 2h | 0.25 |
| ────────────────────────────────────────────────────── |
| **TOTAL** | 1-7 | **92h** | **11.5 days** |

### 8.2 Dependency Chain

```
FAZA 1 (Database)
    ↓
FAZA 2 (Services) ← BLOCKING: FAZA 3, 4, 5, 6
    ↓
┌───┴────┐
│        │
FAZA 3   FAZA 4  ← PARALLEL (can run together)
(Panel)  (TAB)
│        │
└───┬────┘
    ↓
FAZA 5 (Suggestions) ← Integration with FAZA 3, 4
    ↓
FAZA 6 (Per-Shop) ← Integration with FAZA 3, 4, 5
    ↓
FAZA 7 (Testing) ← ALL previous FAZA must be COMPLETED
```

### 8.3 Critical Path

**Critical Path:** FAZA 1 → FAZA 2 → FAZA 3 → FAZA 5 → FAZA 6 → FAZA 7

**Parallelization Opportunity:**
- FAZA 3 (CompatibilityPanel) + FAZA 4 (ProductForm TAB) can run in parallel after FAZA 2
- Reduce total time: 11.5 days → 9.5 days (savings: 2 days)

**Risk Mitigation:**
- ⚠️ FAZA 2 (Services Layer) is BLOCKING - priority 🔴 CRITICAL
- ⚠️ FAZA 7 (Testing) cannot be rushed - allocate full 12h
- ⚠️ Per-shop filtering logic is COMPLEX - może wymagać dodatkowych 2-4h debugging

---

## 9. COMPLIANCE CHECK

### 9.1 PPM Architecture Compliance

#### ✅ Menu & Routing
- **Route:** `/admin/compatibility` (existing, needs real implementation)
- **Menu:** PRODUKTY → Dopasowania części (as per `ARCHITEKTURA_PPM/07_PRODUKTY.md`)
- **Permissions:** Admin/Menadżer (full access), Redaktor (read-only), User (read-only search)

#### ✅ Database Schema
- **Tables:** Following PPM naming conventions (snake_case)
- **Foreign Keys:** Proper cascade rules (ON DELETE CASCADE/SET NULL)
- **Indexes:** Performance-optimized (compound indexes for frequent queries)
- **Constraints:** Unique constraints prevent duplicates

#### ✅ File Structure
- **Models:** `app/Models/` (VehicleCompatibility, CompatibilitySuggestion, etc.)
- **Services:** `app/Services/Compatibility/` (SmartSuggestionEngine, ShopFilteringService)
- **Livewire:** `app/Http/Livewire/Admin/Compatibility/` (CompatibilityManagement)
- **Migrations:** `database/migrations/` (timestamped, descriptive names)
- **Blade:** `resources/views/livewire/admin/compatibility/`
- **CSS:** `resources/css/admin/compatibility.css`

#### ✅ Design System
- **Colors:** MPP TRADE palette (Primary: #3b82f6, Success: #4ade80, Warning: #f59e0b)
- **Typography:** Inter font, 16px base
- **Components:** `.enterprise-card`, `.tabs-enterprise`, `.btn-enterprise-*`
- **Spacing:** 8px base scale
- **Badges:** Color-coded (Original: green, Replacement: blue, Suggested: amber)

#### ✅ SKU-First Architecture
- **Primary:** SKU columns (`part_sku`, `vehicle_sku`) for lookup
- **Secondary:** ID columns for relationships
- **Cache Keys:** Based on SKU (persist across re-imports)
- **References:** Compliant with `_DOCS/SKU_ARCHITECTURE_GUIDE.md`

### 9.2 Laravel 12.x Compliance

#### ✅ Query Optimization (Context7 verified)
- **Eager Loading:** Prevent N+1 (with relationships in query)
- **Indexes:** Compound indexes for frequent filters (product_id + shop_id)
- **Pagination:** Cursor pagination for large datasets (better performance than offset)
- **Full-Text Search:** whereLike / whereFullText for product/vehicle search
- **Lateral Joins:** NOT needed (relationships sufficient)

#### ✅ Service Layer Patterns
- **Dependency Injection:** Constructor injection for sub-services
- **Single Responsibility:** Each service has clear, focused purpose (~200-300 lines)
- **Type Hints:** PHP 8.3 type declarations (strict types)
- **DB Transactions:** For multi-record operations (atomicity)
- **Cache Layer:** 15-minute TTL for compatibility data

#### ✅ Livewire 3.x Patterns
- **wire:model.live:** Real-time search/filters
- **wire:loading:** Loading states for UX
- **wire:poll:** NOT used (avoid unless needed)
- **x-teleport:** For modals (avoid wire:id conflicts)
- **Events:** dispatch() for parent-child communication (NOT emit())

---

## 10. RISKS & MITIGATION

### 10.1 Technical Risks

#### 🔴 RISK 1: N+1 Query Problem
**Probability:** HIGH
**Impact:** HIGH (performance degradation with 1000+ products)

**Scenario:** Loading CompatibilityPanel with 50 parts, each with 5 vehicles = 250 queries

**Mitigation:**
```php
// ❌ BAD: N+1 problem
$parts = Product::paginate(50);
foreach ($parts as $part) {
    $part->vehicleCompatibility; // 50 queries
    foreach ($part->vehicleCompatibility as $compat) {
        $compat->vehicleModel; // 250 queries
    }
}
// TOTAL: 1 + 50 + 250 = 301 queries ❌

// ✅ GOOD: Eager loading
$parts = Product::with([
    'vehicleCompatibility.vehicleModel',
    'vehicleCompatibility.compatibilityAttribute'
])->paginate(50);
// TOTAL: 3 queries ✅
```

**Testing:** Use Laravel Debugbar to monitor query count (target: <10 queries per page)

#### 🟡 RISK 2: Cache Invalidation Complexity
**Probability:** MEDIUM
**Impact:** MEDIUM (stale suggestions shown to users)

**Scenario:** Product updated (name, manufacturer), but cached suggestions not refreshed

**Mitigation:**
- Event Listener: `ProductUpdated` event → invalidate cache
- TTL: 24h automatic expiry (suggestions regenerate daily)
- Manual refresh button in UI

```php
// ProductUpdated Event Listener
public function handle(ProductUpdated $event): void
{
    $product = $event->product;

    // Invalidate cached suggestions
    Cache::forget("suggestions:product:{$product->id}");
    Cache::forget("suggestions:part_sku:{$product->sku}");

    // Re-generate suggestions (async queue job)
    dispatch(new GenerateSuggestionsJob($product->id));
}
```

#### 🟡 RISK 3: Bulk Operation Performance
**Probability:** MEDIUM
**Impact:** HIGH (timeout with 1000+ products × 100+ vehicles)

**Scenario:** User selects 500 parts, wants to add 50 vehicles = 25,000 inserts

**Mitigation:**
- **Chunking:** Process in batches of 100 (Laravel chunk())
- **Queue Jobs:** Offload to background (Laravel Horizon)
- **Progress Bar:** Real-time updates via Livewire polling
- **Timeout:** Set `max_execution_time = 300` for bulk operations

```php
// ✅ GOOD: Chunked bulk insert
DB::transaction(function() use ($productIds, $vehicleIds) {
    collect($productIds)->chunk(100)->each(function($chunk) use ($vehicleIds) {
        $inserts = [];
        foreach ($chunk as $productId) {
            foreach ($vehicleIds as $vehicleId) {
                $inserts[] = [
                    'product_id' => $productId,
                    'vehicle_model_id' => $vehicleId,
                    // ...
                ];
            }
        }
        VehicleCompatibility::insert($inserts); // Batch insert
    });
});
```

#### 🟢 RISK 4: Shop Brand Filtering Logic Error
**Probability:** LOW
**Impact:** MEDIUM (wrong vehicles shown for shop)

**Scenario:** Shop has `allowed_vehicle_brands = []` (empty array), but UI shows all vehicles

**Mitigation:**
- Unit tests for ShopFilteringService (all edge cases)
- Strict type checking (`empty()` vs `null` check)
- UI indicator: "No brands allowed for this shop"

```php
// ✅ GOOD: Explicit null vs empty check
public function filterVehiclesByShop(Collection $vehicles, ?int $shopId): Collection
{
    $allowedBrands = $shop->allowed_vehicle_brands;

    if ($allowedBrands === null) {
        return $vehicles; // NULL = all allowed
    }

    if (empty($allowedBrands)) {
        return collect([]); // [] = none allowed
    }

    return $vehicles->filter(fn($v) => in_array($v->brand, $allowedBrands));
}
```

### 10.2 Business Risks

#### 🔴 RISK 5: User Adoption (Excel Workflow Resistance)
**Probability:** MEDIUM
**Impact:** HIGH (users prefer Excel, don't use new system)

**Scenario:** Users familiar with Excel "przeciągnij O/Z" workflow, new UI too different

**Mitigation:**
- **Training:** Tutorial video (5 min) demonstrating Excel-like workflow
- **Onboarding:** First-time user tooltip tour (step-by-step)
- **Feedback Loop:** Collect user feedback in first 2 weeks, iterate UI

#### 🟡 RISK 6: Data Migration (Existing Compatibility Records)
**Probability:** LOW
**Impact:** MEDIUM (existing data corrupted during migration)

**Scenario:** Migration adds `shop_id` column, but existing records need shop assignment

**Mitigation:**
- **Backup:** Full database backup BEFORE migration
- **Dry Run:** Test migration on staging environment
- **Rollback Plan:** Prepared rollback migration (drop columns)
- **Data Seeding:** Existing records get `shop_id = NULL` (global default)

---

## 11. SUCCESS METRICS

### 11.1 Performance Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Page Load Time** | <2s | CompatibilityPanel load |
| **Query Count** | <10 | Per page load (N+1 prevention) |
| **Bulk Operation** | <30s | 100 products × 10 vehicles |
| **Suggestion Generation** | <200ms | Per product |
| **Cache Hit Rate** | >80% | Suggestions cache |

### 11.2 User Experience Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| **User Adoption** | 80% of admins | Using panel within 1 month |
| **Task Completion Time** | <2min | Add 10 vehicles to 5 parts |
| **Error Rate** | <5% | Bulk operations fail rate |
| **User Satisfaction** | >4/5 | Post-launch survey |

### 11.3 Business Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Compatibility Coverage** | 80% of parts | Have ≥1 vehicle compatibility |
| **Verified Compatibility** | 60% | Compatibility records verified by users |
| **Per-Shop Customization** | 50% | Shops with brand restrictions configured |
| **Smart Suggestions Applied** | 40% | Suggestions accepted by users |

---

## 12. PODSUMOWANIE

### 12.1 Kluczowe Decyzje Architektoniczne

1. **Per-Shop Support via `shop_id` Column**
   - NULLABLE = dane domyślne (globalne)
   - Non-null = per-shop override
   - Query fallback: per-shop → global

2. **Smart Suggestions = Separate Cache Table**
   - Nie zaśmiecamy głównej tabeli compatibility
   - TTL 24h (automatic regeneration)
   - Tracking aplikowanych sugestii (audit trail)

3. **Excel-like Workflow via Bulk Operations**
   - Multi-select with tiles (clickable, not checkboxes)
   - Modal bulk add/remove (user-friendly)
   - Audit log dla compliance

4. **Brand Restrictions = JSON Array in Shops Table**
   - Flexibility: Admin definiuje per-shop brands
   - NULL = wszystkie dozwolone
   - [] = brak compatibility support
   - ["YCF"] = tylko YCF

5. **Service Layer Separation**
   - CompatibilityManager (orchestrator)
   - SmartSuggestionEngine (algorithm)
   - ShopFilteringService (per-shop logic)
   - CompatibilityBulkService (bulk operations)
   - Każdy <300 linii (CLAUDE.md compliant)

### 12.2 Najważniejsze Ryzyka

1. 🔴 **N+1 Query Problem** → Mitigation: Eager loading + Laravel Debugbar monitoring
2. 🟡 **Bulk Operation Performance** → Mitigation: Chunking + Queue Jobs + Progress Bar
3. 🟡 **Cache Invalidation** → Mitigation: Event Listeners + TTL 24h + Manual refresh
4. 🔴 **User Adoption** → Mitigation: Training + Onboarding + Feedback Loop

### 12.3 Next Steps

**Immediate Actions (przed implementacją):**
1. ✅ Review tego raportu przez Product Owner (user = Kamil Wiliński)
2. ✅ Approval architektury (schema + UX workflow)
3. ✅ Assign agents do FAZA 1-7 (laravel-expert, livewire-specialist, etc.)
4. ✅ Setup project tracking (Plan_Projektu/ETAP_11_Dopasowania.md)

**Implementation Kickoff:**
1. ⏳ laravel-expert: FAZA 1 (Database & Models) - START 2025-12-04
2. ⏳ laravel-expert: FAZA 2 (Services Layer) - After FAZA 1
3. ⏳ livewire-specialist + frontend-specialist: FAZA 3+4 (UI) - PARALLEL after FAZA 2
4. ⏳ architect: FAZA 5+6 (Suggestions + Per-Shop) - After FAZA 3+4
5. ⏳ debugger: FAZA 7 (Testing) - After ALL previous FAZA

**Timeline:**
- **Start:** 2025-12-04
- **Estimated Completion:** 2025-12-18 (11.5 days with parallelization)
- **Buffer:** +2 days for testing & bug fixes
- **Production Deploy:** 2025-12-20 (przed świętami)

---

## 📚 REFERENCES

### Documentation
- [`_DOCS/SKU_ARCHITECTURE_GUIDE.md`](../_DOCS/SKU_ARCHITECTURE_GUIDE.md) - SKU-first patterns
- [`_DOCS/ARCHITEKTURA_PPM/07_PRODUKTY.md`](../_DOCS/ARCHITEKTURA_PPM/07_PRODUKTY.md) - ProductForm TAB design
- [`_DOCS/Struktura_Bazy_Danych.md`](../_DOCS/Struktura_Bazy_Danych.md) - Database schema reference
- [`_DOCS/CSS_STYLING_GUIDE.md`](../_DOCS/CSS_STYLING_GUIDE.md) - CSS anti-patterns & best practices
- [`CLAUDE.md`](../CLAUDE.md) - Project architecture & deployment guide

### Code References
- **Existing Models:** `app/Models/VehicleCompatibility.php`, `VehicleModel.php`, `CompatibilityAttribute.php`
- **Existing Services:** `app/Services/CompatibilityManager.php`, `CompatibilityBulkService.php`
- **Existing Components:** `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php` (placeholder)

### External
- Laravel 12.x Docs: Query Optimization, Pagination, Eager Loading
- Livewire 3.x Docs: Real-time validation, Alpine.js integration
- Context7: Laravel enterprise patterns verification

---

**Report Status:** ✅ COMPLETED
**Next Action:** User review & approval
**Estimated Implementation Start:** 2025-12-04
**Responsible Agent:** architect (Kamil Wiliński approval required)

---

**END OF REPORT**
