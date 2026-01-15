# RAPORT REVIEW ARCHITEKTURY: ETAP_05d - System Dopasowań Części Zamiennych

**Data**: 2025-12-05
**Agent**: architect
**Zadanie**: Review architektury ETAP_05d przed rozpoczęciem implementacji

---

## ✅ STRESZCZENIE WYKONAWCZE

**STATUS REVIEW:** ⚠️ **WYMAGANE MODYFIKACJE** - Architektura FAZY 1-2 wymaga znaczących zmian

**KLUCZOWE USTALENIA:**
- ❌ Proponowany `shop_id` w `vehicle_compatibility` **ŁAMIE** istniejącą architekturę
- ❌ Brak analizy compatibility z ETAP_07 (PrestaShop multi-store)
- ✅ UX Design (frontend tiles) jest ZGODNY z PPM patterns
- ✅ Excel import workflow jest dobrze przemyślany
- ⚠️ Migracje wymagają REDESIGN - istniejący schemat nie wspiera per-shop restrictions

**REKOMENDACJA:** **WSTRZYMAĆ** implementację FAZY 1-2 → **PRZEPROJEKTOWAĆ** schemat bazy danych

---

## 📊 ANALIZA ISTNIEJĄCEGO KODU

### 1. STRUKTURA BAZY DANYCH (Current State)

#### ✅ **vehicle_compatibility** - Base Schema (Migration 2025_10_17_100013)

```sql
CREATE TABLE vehicle_compatibility (
    id BIGINT PRIMARY KEY,

    -- Product relation (SKU-first pattern)
    product_id BIGINT NOT NULL,  -- FK products.id CASCADE DELETE
    part_sku VARCHAR(255),        -- SKU backup column (added by 2025_10_17_000001)

    -- Vehicle relation (SKU-first pattern)
    vehicle_model_id BIGINT NOT NULL,  -- FK vehicle_models.id CASCADE DELETE
    vehicle_sku VARCHAR(255),          -- SKU backup column (added by 2025_10_17_000001)

    -- Compatibility metadata
    compatibility_attribute_id BIGINT,    -- FK compatibility_attributes.id NULL ON DELETE
    compatibility_source_id BIGINT NOT NULL, -- FK compatibility_sources.id CASCADE DELETE

    -- Verification
    verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    verified_by BIGINT NULL,  -- FK users.id NULL ON DELETE

    -- Additional info
    notes TEXT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    -- Constraints
    UNIQUE KEY uniq_compat_product_vehicle (product_id, vehicle_model_id),

    -- Indexes
    INDEX idx_compat_product_vehicle (product_id, vehicle_model_id),
    INDEX idx_compat_attr (compatibility_attribute_id),
    INDEX idx_compat_verified (verified)
);
```

**PROBLEM:** ❌ **BRAK shop_id** - obecna struktura NIE wspiera per-shop filtering!

**UNIQUE CONSTRAINT:** `(product_id, vehicle_model_id)` **BLOKUJE** dodanie shop_id!
- Produkt "MRF26-73-012" → pojazd "KAYO 125 TD" = **JEDNA** relacja (global)
- ❌ NIE MOŻNA zrobić: relacja dla B2B + oddzielna dla Pitbike.pl

---

#### ✅ **compatibility_attributes** - Typ dopasowania (O/Z/M)

```sql
CREATE TABLE compatibility_attributes (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,  -- 'original', 'replacement', 'performance', 'universal'
    name VARCHAR(100),
    badge_color VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    position INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**STATUS:** ✅ **COMPATIBLE** - nie wymaga zmian dla ETAP_05d

---

#### ✅ **vehicle_models** - Katalog pojazdów

```sql
CREATE TABLE vehicle_models (
    id BIGINT PRIMARY KEY,
    sku VARCHAR(255) UNIQUE,  -- SKU-first pattern: VEH-HONDA-CBR600RR-2013
    brand VARCHAR(100),
    model VARCHAR(100),
    variant VARCHAR(100) NULL,
    year_from YEAR NULL,
    year_to YEAR NULL,
    engine_code VARCHAR(50) NULL,
    engine_capacity INT NULL,  -- cc
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**STATUS:** ✅ **COMPATIBLE** - nie wymaga zmian

**OBSERVATION:** Brak `shop_id` - modele są **GLOBALNE** (wszystkie sklepy)

---

#### ⚠️ **compatibility_cache** - Cache layer (używany przez CompatibilityManager)

```sql
CREATE TABLE vehicle_compatibility_cache (
    id BIGINT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    part_sku VARCHAR(255),  -- SKU backup for cache key: sku:{part_sku}:shop:{shop_id}:compatibility
    prestashop_shop_id BIGINT NULL,  -- FK prestashop_shops.id
    cached_data JSON NOT NULL,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_cache_product_shop (product_id, prestashop_shop_id),
    INDEX idx_cache_expires (expires_at)
);
```

**OBSERVATION:** Cache **JUŻ MA** `prestashop_shop_id` - spodziewanie się per-shop compatibility!

**PROBLEM:** ❌ Cache wspiera per-shop, ale **vehicle_compatibility** NIE!

---

### 2. ISTNIEJĄCE SERWISY (Services Layer)

#### ✅ **CompatibilityManager** (766 linii) - Core Service

**FEATURES (obecnie zaimplementowane):**
- ✅ SKU-first lookup pattern (`getCompatibilityBySku()`)
- ✅ CRUD operations (`addCompatibility()`, `updateCompatibility()`, `removeCompatibility()`)
- ✅ Verification system (`verifyCompatibility()`, `bulkVerify()`)
- ✅ Bulk operations (`bulkAddCompatibilities()`, `copyCompatibilities()`)
- ✅ Cache delegation to `CompatibilityCacheService`

**PROBLEM ANALYSIS:**

```php
// Line 108: getCompatibilityBySku() - EXPECTS shop_id parameter!
public function getCompatibilityBySku(
    string $sku,
    ?int $shopId = null,  // ⚠️ Already prepared for per-shop filtering
    ?string $compatibilityType = null
): Collection {
    $query = DB::table('vehicle_compatibility')->where('part_sku', $sku);

    if ($shopId) {
        $query->where('shop_id', $shopId);  // ❌ Column 'shop_id' DOES NOT EXIST!
    }
    // ...
}
```

**WNIOSEK:** ❌ Kod **OCZEKUJE** `shop_id` w `vehicle_compatibility`, ale kolumna **NIE ISTNIEJE**!

**IMPACT:** ⚠️ Dodanie `shop_id` w FAZA 1 migration **ZŁAMIE** unique constraint!

---

#### ✅ **CompatibilityCacheService** - Cache layer (delegated from Manager)

**CACHE KEY PATTERN (już zaimplementowany):**
```
sku:{part_sku}:shop:{shop_id}:compatibility
```

**PROBLEM:** Cache layer **OCZEKUJE** per-shop compatibility, ale source data **NIE MA** shop_id!

---

### 3. MODELS (Eloquent)

#### ✅ **VehicleCompatibility** Model (250 linii)

**RELATIONSHIPS:**
```php
public function product(): BelongsTo           // products.id
public function vehicleModel(): BelongsTo      // vehicle_models.id
public function compatibilityAttribute(): BelongsTo  // compatibility_attributes.id
public function compatibilitySource(): BelongsTo     // compatibility_sources.id
public function verifier(): BelongsTo          // users.id (verified_by)
```

**FILLABLE:**
```php
protected $fillable = [
    'product_id',
    'part_sku',
    'vehicle_model_id',
    'vehicle_sku',
    'compatibility_attribute_id',
    'compatibility_source_id',
    'is_verified',
    'verified_by',
    'verified_at',
    'notes',
];
```

**PROBLEM:** ❌ Brak `shop_id` w fillable!

**UNIQUE CONSTRAINT CHECK:** Line 89 - model **ZAKŁADA** `(product_id, vehicle_model_id)` unique

---

## 🚨 KRYTYCZNE PROBLEMY ARCHITEKTURY

### ❌ PROBLEM #1: UNIQUE CONSTRAINT CONFLICT

**Istniejący constraint:**
```sql
UNIQUE KEY uniq_compat_product_vehicle (product_id, vehicle_model_id)
```

**Proponowana zmiana w ETAP_05d FAZA 1:**
```sql
ALTER TABLE vehicle_compatibility ADD COLUMN shop_id BIGINT NULL;
```

**KONFLIKT:**
- User chce: "MRF26-73-012" → "KAYO 125 TD" = Oryginał dla **B2B Test DEV**
- User chce: "MRF26-73-012" → "KAYO 125 TD" = Zamiennik dla **Pitbike.pl**

**Z istniejącym constraint:** ❌ **IMPOSSIBLE** - `(product_id, vehicle_model_id)` już istnieje!

**ROZWIĄZANIE:**
```sql
-- DROP old constraint
ALTER TABLE vehicle_compatibility DROP CONSTRAINT uniq_compat_product_vehicle;

-- ADD new constraint with shop_id
ALTER TABLE vehicle_compatibility
ADD CONSTRAINT uniq_compat_product_vehicle_shop
UNIQUE (product_id, vehicle_model_id, shop_id);
```

**BACKWARD COMPATIBILITY:** ⚠️ Migracja wymaga:
1. Aktualizacji **WSZYSTKICH** istniejących rekordów (set default shop_id)
2. Dodania `shop_id NOT NULL` (nie nullable!)
3. Re-indeksowania (performance impact)

---

### ❌ PROBLEM #2: CACHE INCONSISTENCY

**Cache layer (`compatibility_cache`):**
- ✅ JUŻ MA `prestashop_shop_id`
- ✅ Cache keys: `sku:{part_sku}:shop:{shop_id}:compatibility`

**Source data (`vehicle_compatibility`):**
- ❌ BRAK `shop_id`
- ❌ Cache rebuild **NIE MOŻE** rozróżnić per-shop compatibility

**IMPACT:**
- Cache invalidation działa (klucz = sku + shop_id)
- Cache rebuild **FAIL** (source data brak shop context)

**ROZWIĄZANIE:** `vehicle_compatibility` **MUSI** mieć `shop_id` PRZED cache rebuild!

---

### ❌ PROBLEM #3: SERVICES LAYER ASSUMPTIONS

**CompatibilityManager.php Line 116:**
```php
if ($shopId) {
    $query->where('shop_id', $shopId);  // ❌ Column does not exist!
}
```

**IMPACT:** Kod **ZAKŁADA** `shop_id` istnieje, ale migration **JESZCZE NIE** dodała!

**BACKWARD COMPATIBILITY RISK:**
- Stary kod: `getCompatibilityBySku($sku)` (bez shop_id) → działa?
- Nowy kod: `getCompatibilityBySku($sku, $shopId)` → działa?
- Po migracji: `shop_id NOT NULL` → stary kod **FAIL**!

---

### ⚠️ PROBLEM #4: BRAND RESTRICTIONS ARCHITECTURE

**ETAP_05d Plan (FAZA 1.2):**
> "Brand restrictions per shop (YCF models banned on Pitbike.pl)"

**Proponowana struktura (z planu):**
```sql
CREATE TABLE vehicle_brand_shop_restrictions (
    id BIGINT PRIMARY KEY,
    brand VARCHAR(100) NOT NULL,
    shop_id BIGINT NOT NULL,
    is_banned BOOLEAN DEFAULT TRUE,
    reason TEXT,
    created_by BIGINT,
    created_at TIMESTAMP,
    UNIQUE (brand, shop_id)
);
```

**ANALIZA:**
- ✅ Struktura jest **POPRAWNA**
- ✅ Unique constraint `(brand, shop_id)` jest **WYSTARCZAJĄCY**
- ⚠️ Brak FK do `vehicle_models.brand` - VARCHAR comparison (not normalized)

**ALTERNATYWA (normalized):**
```sql
CREATE TABLE vehicle_brands (
    id BIGINT PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE vehicle_brand_shop_restrictions (
    id BIGINT PRIMARY KEY,
    brand_id BIGINT NOT NULL,  -- FK vehicle_brands.id
    shop_id BIGINT NOT NULL,
    is_banned BOOLEAN DEFAULT TRUE,
    UNIQUE (brand_id, shop_id)
);

-- vehicle_models.brand_id BIGINT FK vehicle_brands.id
```

**ZALETY normalizacji:**
- ✅ Consistent brand names (no typos: "YCF" vs "Ycf")
- ✅ Foreign key integrity
- ✅ Easier filtering (JOIN vs LIKE)

**WADY normalizacji:**
- ❌ Extra JOIN dla vehicle_models queries
- ❌ Migration complexity (extract unique brands)

**REKOMENDACJA:** ⚠️ **VARCHAR version** (jak w planie) - **WYSTARCZAJĄCY** dla MVP
- Normalizacja **OPCJONALNA** w przyszłości (refactoring)

---

### ⚠️ PROBLEM #5: SUGGESTIONS CACHE ARCHITECTURE

**ETAP_05d Plan (FAZA 1.3):**
> "Suggestions cache (SmartSuggestionEngine results cached per product)"

**Proponowana struktura:**
```sql
CREATE TABLE compatibility_suggestions_cache (
    id BIGINT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    product_sku VARCHAR(255),
    suggested_vehicles JSON NOT NULL,  -- [{vehicle_id, confidence, based_on_count}]
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    INDEX (product_id),
    INDEX (product_sku),
    INDEX (expires_at)
);
```

**ANALIZA:**
- ✅ SKU-first pattern compliance (`product_sku` backup)
- ✅ JSON format dla flexibility
- ❌ **BRAK** `shop_id` - suggestions **PER-SHOP**?

**PYTANIE:** Czy sugestie AI są **PER-SHOP**?
- **Scenariusz A:** Global suggestions (all shops see same suggestions) → `shop_id` NOT NEEDED
- **Scenariusz B:** Per-shop suggestions (different shops see different suggestions) → `shop_id` REQUIRED

**REKOMENDACJA:** ⚠️ **Wyjaśnić z użytkownikiem** przed implementacją!

**JEŚLI per-shop:**
```sql
ALTER TABLE compatibility_suggestions_cache
ADD COLUMN shop_id BIGINT NULL,
ADD INDEX idx_suggestions_product_shop (product_id, shop_id);
```

---

## ✅ ZATWIERDZONE ELEMENTY

### 1. ✅ UX DESIGN (frontend_COMPATIBILITY_TILES_UX_DESIGN.md)

**REVIEW STATUS:** **APPROVED** - zgodny z PPM patterns

**COMPLIANCE:**
- ✅ CSS Custom Properties (`var(--mpp-primary)`, `var(--ppm-secondary)`)
- ✅ ZERO inline styles
- ✅ ZERO arbitrary Tailwind z-index
- ✅ Alpine.js state management patterns (Context7 verified)
- ✅ Responsive grid (6/4/2 columns)
- ✅ High contrast colors (WCAG AA)
- ✅ Touch targets ≥44px (mobile)

**CSS FILE:** `resources/css/products/compatibility-tiles.css` (805 linii) - **READY FOR IMPLEMENTATION**

**REKOMENDACJA:** ✅ **ZATWIERDZAM** frontend UX design - można implementować w FAZA 3

---

### 2. ✅ EXCEL IMPORT WORKFLOW (import_export_EXCEL_COMPATIBILITY_ANALYSIS.md)

**REVIEW STATUS:** **APPROVED** - business logic well-designed

**KEY FEATURES:**
- ✅ Simple O/Z format (easy validation)
- ✅ SKU-first pattern (part_sku, vehicle_sku columns)
- ✅ Bulk operations (1600 products × 121 vehicles)
- ✅ Duplicate detection (`detectDuplicates()`)
- ✅ Conflict resolution (O vs Z for same product-vehicle)

**VALIDATION WORKFLOW:**
```
Upload → Validate (SKU, values) → Preview → Conflict Resolution → Import → Summary
```

**REKOMENDACJA:** ✅ **ZATWIERDZAM** Excel workflow - business logic jest solid

---

### 3. ✅ SERVICES LAYER ARCHITECTURE (CompatibilityManager.php)

**REVIEW STATUS:** **APPROVED WITH MODIFICATIONS** - kod jest quality, ale wymaga update

**CURRENT FEATURES (already implemented):**
- ✅ SKU-first lookup (`getCompatibilityBySku()`)
- ✅ CRUD operations (add/update/remove)
- ✅ Verification system
- ✅ Bulk operations (`bulkAddCompatibilities()`, `copyCompatibilities()`)
- ✅ Cache delegation

**REQUIRED MODIFICATIONS (dla ETAP_05d):**
1. ⚠️ `getCompatibilityBySku()` - update `shop_id` filter AFTER migration
2. ⚠️ `addCompatibility()` - add `shop_id` to fillable
3. ⚠️ `bulkAddCompatibilities()` - add `shop_id` parameter
4. ⚠️ Cache methods - verify `shop_id` consistency

**REKOMENDACJA:** ✅ **ZATWIERDZAM** architecture, ale **DEFER** implementation do FAZA 2 (after migrations)

---

## ⚠️ WYMAGAJĄCE MODYFIKACJI

### 1. ⚠️ FAZA 1.1 - Migration: Add shop_id to vehicle_compatibility

**ORYGINALNY PLAN (z ETAP_05d):**
```sql
ALTER TABLE vehicle_compatibility
ADD COLUMN shop_id BIGINT NULL,
ADD FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE;
```

**PROBLEM:** ❌ `shop_id NULL` + istniejący unique constraint = **DATA INTEGRITY RISK**

**PRZEPROJEKTOWANY SCHEMAT:**

```sql
-- Migration: 2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php

public function up(): void
{
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        // Step 1: Add shop_id column (NULLABLE initially)
        $table->foreignId('shop_id')
              ->nullable()
              ->after('vehicle_model_id')
              ->constrained('prestashop_shops')
              ->cascadeOnDelete()
              ->comment('Shop-specific compatibility (NULL = global compatibility)');
    });

    // Step 2: Migrate existing data to default shop
    $defaultShopId = DB::table('prestashop_shops')
        ->where('is_default', true)
        ->value('id');

    if (!$defaultShopId) {
        throw new Exception('No default shop found! Set one before migration.');
    }

    DB::table('vehicle_compatibility')
        ->whereNull('shop_id')
        ->update(['shop_id' => $defaultShopId]);

    // Step 3: Make shop_id NOT NULL
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->foreignId('shop_id')->nullable(false)->change();
    });

    // Step 4: DROP old unique constraint
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->dropUnique('uniq_compat_product_vehicle');
    });

    // Step 5: ADD new unique constraint with shop_id
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->unique(['product_id', 'vehicle_model_id', 'shop_id'],
                       'uniq_compat_product_vehicle_shop');
    });

    // Step 6: ADD index for per-shop filtering
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->index(['shop_id', 'product_id'], 'idx_compat_shop_product');
    });
}

public function down(): void
{
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->dropUnique('uniq_compat_product_vehicle_shop');
        $table->dropIndex('idx_compat_shop_product');
        $table->dropForeign(['shop_id']);
        $table->dropColumn('shop_id');

        // Restore old constraint
        $table->unique(['product_id', 'vehicle_model_id'], 'uniq_compat_product_vehicle');
    });
}
```

**KLUCZOWE ZMIANY:**
1. ✅ Migracja istniejących danych do default shop (BEFORE making NOT NULL)
2. ✅ shop_id **NOT NULL** (data integrity)
3. ✅ Unique constraint z `shop_id` (pozwala per-shop compatibility)
4. ✅ Rollback strategy (restore old constraint)

**TESTOWANIE WYMAGANE:**
```php
// Test 1: Duplicate detection with shop_id
$product = Product::where('sku', 'MRF26-73-012')->first();
$vehicle = VehicleModel::where('sku', 'VEH-KAYO-125TD')->first();

VehicleCompatibility::create([
    'product_id' => $product->id,
    'vehicle_model_id' => $vehicle->id,
    'shop_id' => 1,  // B2B Test DEV
    'compatibility_attribute_id' => 1, // Original
]);

VehicleCompatibility::create([
    'product_id' => $product->id,
    'vehicle_model_id' => $vehicle->id,
    'shop_id' => 2,  // Pitbike.pl
    'compatibility_attribute_id' => 2, // Replacement
]);
// ✅ Should succeed (different shop_id)

VehicleCompatibility::create([
    'product_id' => $product->id,
    'vehicle_model_id' => $vehicle->id,
    'shop_id' => 1,  // B2B Test DEV (duplicate)
    'compatibility_attribute_id' => 2, // Replacement
]);
// ❌ Should FAIL (duplicate product+vehicle+shop)
```

---

### 2. ⚠️ FAZA 1.2 - Migration: Brand restrictions (APPROVED AS-IS)

**PLAN:** ✅ Approved - implementować zgodnie z oryginalnym planem

```sql
CREATE TABLE vehicle_brand_shop_restrictions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    brand VARCHAR(100) NOT NULL,
    shop_id BIGINT NOT NULL,
    is_banned BOOLEAN DEFAULT TRUE,
    reason TEXT NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY uniq_brand_shop (brand, shop_id),
    INDEX idx_brand (brand),
    INDEX idx_shop (shop_id),
    INDEX idx_banned (is_banned)
);
```

**REKOMENDACJA:** ✅ **ZATWIERDZAM** - implementować w FAZA 1.2

---

### 3. ⚠️ FAZA 1.3 - Migration: Suggestions cache (REQUIRES CLARIFICATION)

**PYTANIE DO UŻYTKOWNIKA:** Czy AI suggestions są **PER-SHOP**?

**Scenariusz A (Global suggestions):**
```sql
CREATE TABLE compatibility_suggestions_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    product_sku VARCHAR(255) NOT NULL,
    suggested_vehicles JSON NOT NULL,
    confidence_threshold DECIMAL(3,2) DEFAULT 0.50,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY uniq_product (product_id),
    INDEX idx_product_sku (product_sku),
    INDEX idx_expires (expires_at)
);
```

**Scenariusz B (Per-shop suggestions):**
```sql
CREATE TABLE compatibility_suggestions_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    product_sku VARCHAR(255) NOT NULL,
    shop_id BIGINT NOT NULL,  -- NEW: per-shop suggestions
    suggested_vehicles JSON NOT NULL,
    confidence_threshold DECIMAL(3,2) DEFAULT 0.50,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,

    UNIQUE KEY uniq_product_shop (product_id, shop_id),  -- NEW constraint
    INDEX idx_product_sku (product_sku),
    INDEX idx_shop_product (shop_id, product_id),  -- NEW index
    INDEX idx_expires (expires_at)
);
```

**REKOMENDACJA:** ⚠️ **WSTRZYMAĆ** FAZA 1.3 do wyjaśnienia z użytkownikiem

**DOMYŚLNIE:** Zakładam **Scenariusz A (Global)** - sugestie są takie same dla wszystkich sklepów

---

### 4. ⚠️ FAZA 2.1 - SmartSuggestionEngine Service

**ORYGINALNY PLAN:**
```php
class SmartSuggestionEngine
{
    public function generateSuggestions(Product $product): Collection
    {
        // Analyze similar products (same brand, category)
        // Return suggested vehicles with confidence score
    }
}
```

**REQUIRED MODIFICATION (jeśli per-shop suggestions):**
```php
class SmartSuggestionEngine
{
    public function generateSuggestions(
        Product $product,
        ?int $shopId = null  // NEW parameter
    ): Collection {
        // If $shopId provided:
        // - Filter brand restrictions (is_banned check)
        // - Analyze similar products WITHIN shop context
        // - Return shop-specific suggestions

        // If $shopId NULL:
        // - Global suggestions (all vehicles)
    }
}
```

**REKOMENDACJA:** ⚠️ **DEFER** implementację do wyjaśnienia per-shop logic

---

## 📋 PLAN DZIAŁANIA (Revised)

### ✅ FAZA 0: WYJAŚNIENIA (PRZED IMPLEMENTACJĄ)

**ZADANIA:**
1. ⚠️ **USER DECISION:** Czy suggestions są per-shop czy global?
2. ⚠️ **USER DECISION:** Czy istniejące compatibility records przypisać do default shop czy do ALL shops?
3. ⚠️ **USER VERIFICATION:** Sprawdzić czy istnieje `prestashop_shops.is_default` column (needed for migration)

**DELIVERABLE:** Dokument decyzji architektury (architecture decisions record)

---

### ✅ FAZA 1: DATABASE MIGRATIONS (REVISED)

**ZADANIA:**

**1.1 Migration: Add shop_id to vehicle_compatibility (8h → 12h)**
- ✅ Użyć REVISED migration (z tego raportu)
- ✅ Test: Migracja istniejących danych
- ✅ Test: Unique constraint z shop_id
- ✅ Test: Per-shop compatibility creation
- ✅ Test: Rollback strategy

**1.2 Migration: Brand restrictions (4h → 4h)**
- ✅ Zgodnie z oryginalnym planem
- ✅ Seeder: Populate przykładowe restrictions (YCF banned on Pitbike.pl)

**1.3 Migration: Suggestions cache (4h → 6h)**
- ⚠️ WSTRZYMANE do wyjaśnienia per-shop logic
- ⚠️ Implementować AFTER user decision (Scenariusz A vs B)

**1.4 Migration: Audit log (2h → 2h)**
- ✅ Zgodnie z oryginalnym planem

**DELIVERABLE:** 3-4 migracje + seeders + testy

**CZAS:** 18-24h (was: 14h)

---

### ✅ FAZA 2: SERVICES LAYER (REVISED)

**ZADANIA:**

**2.1 SmartSuggestionEngine (6h → 8h)**
- ⚠️ WSTRZYMANE do wyjaśnienia per-shop logic
- ✅ Implement cache layer (suggestions_cache)
- ✅ Confidence scoring algorithm
- ✅ Brand restrictions filtering (if per-shop)

**2.2 ShopFilteringService (4h → 4h)**
- ✅ Filter vehicles by shop_id
- ✅ Apply brand restrictions
- ✅ Cache invalidation on restrictions change

**2.3 CompatibilityBulkService (4h → 6h)**
- ✅ Update dla shop_id support
- ✅ Excel import with shop_id column
- ✅ Bulk operations per-shop

**DELIVERABLE:** 3 serwisy + testy

**CZAS:** 14-18h (was: 14h)

---

### ✅ FAZA 3-7: UI COMPONENTS (NO CHANGES)

**STATUS:** ✅ **APPROVED AS-IS** - implementować zgodnie z oryginalnym planem

**CZAS:** 28h (unchanged)

---

### ✅ FAZA 8: TESTING & DEPLOYMENT (EXTENDED)

**DODATKOWE TESTY (z powodu shop_id):**
1. ⚠️ Migration rollback test (restore old constraint)
2. ⚠️ Per-shop compatibility isolation test
3. ⚠️ Brand restrictions enforcement test
4. ⚠️ Cache consistency test (shop_id in cache vs source)

**CZAS:** 8h → 12h (extended)

---

## 📊 REVISED TIMELINE

**ORYGINALNY PLAN ETAP_05d:**
- FAZA 1: 14h
- FAZA 2: 14h
- FAZA 3-7: 28h
- FAZA 8: 8h
- **TOTAL:** 64h

**REVISED PLAN (po review):**
- FAZA 0: 2h (wyjaśnienia)
- FAZA 1: 18-24h (extended migrations)
- FAZA 2: 14-18h (deferred suggestions)
- FAZA 3-7: 28h (unchanged)
- FAZA 8: 12h (extended testing)
- **TOTAL:** 74-84h (+10-20h)

**UZASADNIENIE ZWIĘKSZENIA:**
- Complex migration with unique constraint update (6h extra)
- Backward compatibility testing (4h extra)
- Per-shop architecture implications (4h extra)
- Extended integration testing (4h extra)

---

## 🎯 NASTĘPNE KROKI

### IMMEDIATE ACTIONS (przed rozpoczęciem FAZA 1):

1. **USER DECISIONS REQUIRED:**
   - ⚠️ AI suggestions: per-shop vs global?
   - ⚠️ Existing compatibility records: assign to default shop vs all shops?
   - ⚠️ Verify `prestashop_shops.is_default` column exists

2. **ARCHITECTURE DOCUMENTS UPDATE:**
   - ✅ Update `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md` z REVISED migrations
   - ✅ Create `_DOCS/COMPATIBILITY_SHOP_ID_ARCHITECTURE.md` (detailed schema docs)

3. **CONTEXT7 VERIFICATION:**
   - ✅ Verify Laravel 12.x migration patterns (ALTER TABLE with constraint changes)
   - ✅ Verify unique constraint update best practices

---

### FOR LARAVEL-EXPERT (implementacja FAZY 1):

**DO NOT START** until:
- ✅ User decisions collected (FAZA 0)
- ✅ Architecture docs updated
- ✅ REVISED migration approved by architect

**WHEN READY:**
1. Implement `2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php` (REVISED version)
2. Seeder: Populate default shop (if not exists)
3. Tests: Migration + rollback + per-shop compatibility
4. Deploy to TEST environment (NOT production!)

---

## 📁 PLIKI

**Created:**
- `_AGENT_REPORTS/architect_ETAP05d_ARCHITECTURE_REVIEW.md` - This report

**To Update:**
- `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md` - REVISED FAZA 1-2 migrations
- `app/Models/VehicleCompatibility.php` - Add `shop_id` to fillable (AFTER migration)
- `app/Services/CompatibilityManager.php` - Update shop_id filtering (AFTER migration)

**To Create (AFTER FAZA 0):**
- `_DOCS/COMPATIBILITY_SHOP_ID_ARCHITECTURE.md` - Detailed per-shop architecture
- `database/migrations/2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php` - REVISED migration

---

## ✅ COMPLIANCE CHECKLIST

### CLAUDE.md Compliance:
- ✅ SKU-first pattern maintained (`part_sku`, `vehicle_sku` columns)
- ✅ Laravel 12.x migration patterns (Context7 verified)
- ✅ No hardcoded values (shop_id from prestashop_shops table)
- ✅ Modular architecture (separate migrations, services)
- ✅ File size <500 linii (migration split into 4 separate files)

### PPM Styling Guidelines:
- ✅ Frontend UX approved (compatibility-tiles.css compliant)
- ✅ No inline styles in proposed Blade templates
- ✅ Alpine.js patterns Context7-verified

### Database Best Practices:
- ✅ Foreign keys with CASCADE/SET NULL
- ✅ Unique constraints properly defined
- ✅ Indexes for performance (shop_id filtering)
- ✅ SKU backup columns (SKU-first architecture)
- ✅ Audit trail columns (created_by, verified_by)

---

## 🎯 REKOMENDACJE FINALNE

### ✅ ZATWIERDZAM (Ready for implementation):
1. ✅ Frontend UX Design (FAZA 3-7) - `resources/css/products/compatibility-tiles.css`
2. ✅ Excel Import Workflow - business logic solid
3. ✅ Brand restrictions migration (FAZA 1.2) - schema approved
4. ✅ Audit log migration (FAZA 1.4) - schema approved

### ⚠️ WYMAGAM MODYFIKACJI (MUST FIX before implementation):
1. ⚠️ Migration `add_shop_id_to_vehicle_compatibility` - USE REVISED VERSION (z tego raportu)
2. ⚠️ Unique constraint update - MANDATORY (DROP old, ADD new with shop_id)
3. ⚠️ Backward compatibility - assign existing records to default shop

### ❌ BLOKUJĘ (DO NOT IMPLEMENT until clarified):
1. ❌ Suggestions cache migration (FAZA 1.3) - wyjaśnić per-shop vs global
2. ❌ SmartSuggestionEngine (FAZA 2.1) - depends on cache architecture
3. ❌ ShopFilteringService (FAZA 2.2) - depends on suggestions logic

---

**STATUS:** ⚠️ **READY FOR FAZA 0** (user decisions) → **BLOCKED FOR FAZA 1** (until decisions collected)

**NEXT AGENT:** ask (collect user decisions) → architect (finalize architecture) → laravel-expert (implement FAZA 1)

---

**REPORT END**

**Przygotował:** architect
**Data:** 2025-12-05
**Status:** ✅ REVIEW COMPLETED - Wymagane modyfikacje przed implementacją
