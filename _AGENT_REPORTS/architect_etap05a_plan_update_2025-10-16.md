# RAPORT PRACY AGENTA: architect (ETAP_05a Plan Update)

**Data**: 2025-10-16
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: Analiza compliance violations i aktualizacja Plan_Projektu/ETAP_05a_Produkty.md

---

## EXECUTIVE SUMMARY

### Kontekst

Po analizie ETAP_05a przez 3 agentów specjalistycznych (documentation-reader, laravel-expert, architect-execution-plan) zidentyfikowano **krytyczne compliance violations** wymagające aktualizacji planu PRZED rozpoczęciem implementacji.

### Compliance Score Analysis

**OBECNY STAN:**
- **Overall Compliance Score:** 78/100 (Dobry, ale wymaga poprawek)
- **CRITICAL Violations:** 7 naruszeń zasady max 300 linii
- **HIGH Violations:** Brak Context7 integration (100% coverage wymagane)
- **MEDIUM Violations:** Częściowe naruszenia SKU-first pattern

**TARGET STAN (po aktualizacji):**
- **Overall Compliance Score:** 95+/100
- **CRITICAL Violations:** 0
- **Context7 Integration:** 100% coverage (6 mandatory checkpoints)
- **SKU-first Compliance:** 100%

### Kluczowe Źródła

1. `_AGENT_REPORTS/documentation_reader_etap05a_compliance_2025-10-16.md` - Szczegółowa analiza compliance (78/100)
2. `_AGENT_REPORTS/laravel_expert_etap05a_migrations_spec_2025-10-16.md` - 15 migrations specyfikacja
3. `_AGENT_REPORTS/architect_etap05a_implementation_plan_2025-10-16.md` - Execution plan (7 faz, 77-97h)

---

## WYMAGANE ZMIANY W ETAP_05a_Produkty.md

### **ZMIANA 1: DODAĆ SEKCJĘ 0 (CRITICAL - PRE-IMPLEMENTATION REFACTORING)**

**Lokalizacja:** Po linii 103 (przed SEKCJA 1: DATABASE SCHEMA DESIGN)

**Treść do dodania:**

```markdown
---

## ⚠️ SEKCJA 0: PRE-IMPLEMENTATION REFACTORING (CRITICAL - PRZED FAZĄ 1)

**Status:** ❌ NIEROZPOCZĘTY
**Priorytet:** 🔴 CRITICAL (MUST BE COMPLETED BEFORE FAZA 1)
**Szacowany czas:** 12-16 godzin
**Agent:** laravel-expert + coding-style-agent (review)

**⚠️ MANDATORY:** Ten etap MUSI być ukończony przed rozpoczęciem FAZA 1 (Database Schema). Implementacja bez refactoringu spowoduje technical debt i naruszenie zasad CLAUDE.md.

### **0.1 CRITICAL PROBLEM: Product.php Size Violation**

**Obecny stan:**
```
app/Models/Product.php: 2181 linii
CLAUDE.md limit: 300 linii (max 500 w wyjątkowych przypadkach)
Naruszenie: 7x przekroczenie limitu!
```

**Przyczyna:** 8 różnych responsibilities w jednym pliku (pricing, stock, categories, variants, features, compatibility, multi-store, sync)

**Rozwiązanie:** Ekstrakcja do Traits

```
PRZED Refactoringu:
app/Models/Product.php (2181 linii) ← 🚫 CRITICAL VIOLATION

PO Refactoringu:
app/Models/Product.php (250 linii) ← ✅ TYLKO core model + relationships

app/Models/Concerns/Product/
├── HasPricing.php (150 linii)           → price methods
├── HasStock.php (140 linii)             → stock methods
├── HasCategories.php (120 linii)        → category methods
├── HasVariants.php (130 linii)          → variant methods (NOWE dla ETAP_05a)
├── HasFeatures.php (110 linii)          → feature methods (NOWE dla ETAP_05a)
├── HasCompatibility.php (140 linii)     → compatibility methods (NOWE dla ETAP_05a)
├── HasMultiStore.php (160 linii)        → multi-store methods (ISTNIEJĄCE)
└── HasSyncStatus.php (120 linii)        → sync methods (ISTNIEJĄCE)
```

**Implementacja:**

#### **0.1.1 Extract Pricing Methods → HasPricing Trait (2h)**

```php
// app/Models/Concerns/Product/HasPricing.php (~150 linii)
trait HasPricing
{
    // Relacje
    public function prices(): HasMany { ... }
    public function priceGroups(): BelongsToMany { ... }

    // Methods
    public function getPriceForGroup(int $priceGroupId): ?float { ... }
    public function getEffectivePrice(int $shopId): float { ... }
    public function updatePrices(array $prices): void { ... }
    // ... pozostałe price methods
}
```

#### **0.1.2 Extract Stock Methods → HasStock Trait (2h)**

```php
// app/Models/Concerns/Product/HasStock.php (~140 linii)
trait HasStock
{
    public function stocks(): HasMany { ... }
    public function getStockForWarehouse(int $warehouseId): int { ... }
    public function updateStock(int $warehouseId, int $quantity): void { ... }
    // ... pozostałe stock methods
}
```

#### **0.1.3 Extract Category Methods → HasCategories Trait (1.5h)**

```php
// app/Models/Concerns/Product/HasCategories.php (~120 linii)
trait HasCategories
{
    public function categories(): BelongsToMany { ... }
    public function categoriesForShop(int $shopId): BelongsToMany { ... }
    public function assignCategories(array $categoryIds, int $shopId): void { ... }
    // ... pozostałe category methods
}
```

#### **0.1.4 Extract Variant Methods → HasVariants Trait (1.5h)**

```php
// app/Models/Concerns/Product/HasVariants.php (~130 linii) - NOWE
trait HasVariants
{
    public function variants(): HasMany { ... }
    public function activeVariants(): HasMany { ... }
    public function variantsBySKUPattern(string $pattern): Collection { ... }
    // ... metody z ETAP_05a
}
```

#### **0.1.5 Extract Feature Methods → HasFeatures Trait (1.5h)**

```php
// app/Models/Concerns/Product/HasFeatures.php (~110 linii) - NOWE
trait HasFeatures
{
    public function features(): HasMany { ... }
    public function featuresForShop(?int $shopId = null): HasMany { ... }
    public function getFeatureValue(Feature $feature, ?int $shopId = null) { ... }
    // ... metody z ETAP_05a
}
```

#### **0.1.6 Extract Compatibility Methods → HasCompatibility Trait (2h)**

```php
// app/Models/Concerns/Product/HasCompatibility.php (~140 linii) - NOWE
trait HasCompatibility
{
    public function compatibleVehicles(int $shopId): HasMany { ... }
    public function compatibleParts(int $shopId): HasMany { ... }
    public function compatibilityCache(int $shopId): HasOne { ... }
    // ... metody z ETAP_05a
}
```

#### **0.1.7 Refactor Existing Multi-Store Methods → HasMultiStore Trait (2h)**

```php
// app/Models/Concerns/Product/HasMultiStore.php (~160 linii)
trait HasMultiStore
{
    public function shopData(): HasMany { ... }
    public function getShopData(int $shopId): ?ProductShopData { ... }
    public function updateShopData(int $shopId, array $data): void { ... }
    // ... pozostałe multi-store methods
}
```

#### **0.1.8 Refactor Existing Sync Methods → HasSyncStatus Trait (1.5h)**

```php
// app/Models/Concerns/Product/HasSyncStatus.php (~120 linii)
trait HasSyncStatus
{
    public function getSyncStatus(int $shopId): string { ... }
    public function markAsSynced(int $shopId): void { ... }
    public function markAsConflicted(int $shopId, array $conflicts): void { ... }
    // ... pozostałe sync methods
}
```

#### **0.1.9 Updated Product.php (Core Only) (1h)**

```php
// app/Models/Product.php (~250 linii) - TYLKO CORE
<?php

namespace App\Models;

use App\Models\Concerns\Product\{
    HasPricing,
    HasStock,
    HasCategories,
    HasVariants,
    HasFeatures,
    HasCompatibility,
    HasMultiStore,
    HasSyncStatus
};

class Product extends Model
{
    use HasFactory,
        HasPricing,
        HasStock,
        HasCategories,
        HasVariants,
        HasFeatures,
        HasCompatibility,
        HasMultiStore,
        HasSyncStatus;

    // TYLKO: $fillable, $casts, core relationships (productType), scopes, accessors
}
```

#### **0.1.10 Verification & Tests (2h)**

```bash
# 1. Verify no regressions
php artisan test

# 2. Verify Product model loads all traits
php artisan tinker
> $product = Product::first();
> $product->getPriceForGroup(1); // HasPricing
> $product->getStockForWarehouse(1); // HasStock
> $product->variants; // HasVariants
> $product->features(); // HasFeatures

# 3. Verify all methods accessible
# 4. Run coding-style-agent review
```

### **0.2 SERVICE LAYER SPLIT STRATEGY (PLANNING)**

**Cel:** Zapewnić że ŻADNA klasa Service nie przekroczy 300 linii podczas implementacji

**Wymagane dla SEKCJA 2 (Backend Service Layer):**

#### **0.2.1 VariantManager Split Pattern (~500 linii planned)**

**Problem:** Przewidywane 500+ linii w jednym pliku

**Rozwiązanie:**

```
VariantManager.php (180 linii) - Orchestrator
├── VariantCreationService.php (150 linii) - Create/Update logic
├── VariantInheritanceService.php (140 linii) - Inheritance rules
└── VariantSKUGenerator.php (100 linii) - SKU generation + validation
```

**Implementation Note:** Plan od początku z split architecture!

#### **0.2.2 CompatibilityManager Split Pattern (~600 linii planned)**

**Problem:** Najbardziej złożony serwis, 600+ linii

**Rozwiązanie:**

```
CompatibilityManager.php (200 linii) - Main API
├── CompatibilityCacheService.php (180 linii) - Cache operations
├── CompatibilityValidationService.php (120 linii) - Business rules
└── CompatibilityBulkService.php (150 linii) - Bulk operations
```

#### **0.2.3 FeatureManager Split Pattern (~400 linii planned)**

**Problem:** 400+ linii przewidywane

**Rozwiązanie:**

```
FeatureManager.php (150 linii) - Core operations
├── FeatureSetService.php (120 linii) - Feature sets logic
└── FeatureValueService.php (130 linii) - Values CRUD + validation
```

### **0.3 UI COMPONENT SPLIT STRATEGY (PLANNING)**

**Wymagane dla SEKCJA 4 (UI/UX Components):**

#### **0.3.1 VariantsTab Split Pattern (~500 linii planned)**

**Problem:** Livewire component z business logic = 500+ linii

**Rozwiązanie:**

```
VariantsTab.php (220 linii) - Livewire component
├── VariantCombinationService.php (180 linii) - Business logic (kombinacje)
└── Traits/ (ManagesGeneration, ManagesInheritance, ManagesValidation)
```

**ZASADA:** Livewire component TYLKO wire:model, dispatch, mount/render. Business logic → Services!

#### **0.3.2 CompatibilityTab Split Pattern (~600 linii planned)**

**Problem:** Najbardziej złożony tab

**Rozwiązanie:**

```
CompatibilityTab.php (240 linii) - Livewire component
├── CompatibilitySearchService.php (180 linii) - Search logic
└── Traits/ (ManagesFiltering, ManagesSelection, ManagesBulk)
```

### **0.4 SUCCESS CRITERIA - PRZED ROZPOCZĘCIEM FAZA 1**

**KRYTYCZNE:**
- [ ] Product.php ≤ 300 linii (currently 2181)
- [ ] All Traits ≤ 150 linii each
- [ ] All tests GREEN (no regressions)
- [ ] Services architecture documented (split patterns)
- [ ] UI components architecture documented (split patterns)
- [ ] laravel-expert raport utworzony
- [ ] coding-style-agent approval

**⚠️ BLOKADA:** FAZA 1 (Database Migrations) NIE MOŻE rozpocząć się przed ukończeniem Sekcji 0!

**Timeline Impact:**
- Sequential (1 developer): 77-97h → 89-113h (+ 12-16h overhead)
- Parallelized (3 developers): 55-65h → 67-81h (+ 12-16h sequential pre-work)

---
```

---

### **ZMIANA 2: DODAĆ CONTEXT7 CHECKPOINTS W SEKCJACH**

**MANDATORY:** Context7 integration PRZED każdą sekcją implementacji (zgodnie z AGENT_USAGE_GUIDE.md + CONTEXT7_INTEGRATION_GUIDE.md)

#### **SEKCJA 1 - DATABASE SCHEMA (po linii 105)**

**Dodaj PRZED "1.1 PRODUCT VARIANTS EXTENSIONS":**

```markdown
#### **1.0 CONTEXT7 VERIFICATION (MANDATORY - PRZED migracjami)**

**⚠️ CRITICAL:** Sprawdź aktualne Laravel 12.x patterns PRZED pisaniem migrations

**Context7 Checkpoints:**

```php
// 1. Resolve Laravel library
mcp__context7__resolve-library-id('laravel')
// Expected: /websites/laravel_12_x (4927 snippets, trust: 7.5)

// 2. Get migrations documentation
mcp__context7__get-library-docs(
    '/websites/laravel_12_x',
    'database migrations foreign keys indexes composite unique constraints'
)
```

**Verify:**
- [ ] Foreign key syntax (`foreignId()->constrained()`)
- [ ] Composite indexes patterns (`$table->index(['col1', 'col2'], 'idx_name')`)
- [ ] Migration rollback safety (dropForeign, dropIndex w reverse order)
- [ ] Schema verification methods (`Schema::hasTable()`, `Schema::hasColumn()`)
- [ ] ENUM handling (use string + validation, not ENUM column type)
- [ ] JSON casting syntax (`$casts = ['data' => 'array']`)

**Reference:** `_AGENT_REPORTS/laravel_expert_etap05a_migrations_spec_2025-10-16.md`

---
```

#### **SEKCJA 2 - BACKEND SERVICE LAYER (po linii 631)**

**Dodaj PRZED "2.1 ARCHITECTURE OVERVIEW":**

```markdown
#### **2.0 CONTEXT7 VERIFICATION (MANDATORY - PRZED services)**

**⚠️ CRITICAL:** Sprawdź aktualne service layer patterns PRZED pisaniem service classes

**Context7 Checkpoints:**

```php
// 1. Service layer patterns
mcp__context7__get-library-docs(
    '/websites/laravel_12_x',
    'service layer patterns dependency injection repository pattern'
)
```

**Verify:**
- [ ] Service patterns (constructor injection, interfaces)
- [ ] Repository patterns (data access abstraction)
- [ ] Business logic separation (nie w controllers/Livewire)
- [ ] Transaction handling (`DB::transaction()`)
- [ ] Error handling (custom exceptions)

**SPLIT STRATEGY (zgodnie z SEKCJA 0.2):**
- MAX 300 linii per service file
- Jeśli >250 linii → plan split do sub-services
- Business logic → Services, NOT Livewire components

---
```

#### **SEKCJA 4 - UI/UX COMPONENTS (po linii 1231)**

**Dodaj PRZED "4.1 PRODUCTFORM TABS ARCHITECTURE":**

```markdown
#### **4.0 CONTEXT7 VERIFICATION (MANDATORY - PRZED Livewire/Alpine)**

**⚠️ CRITICAL:** Sprawdź aktualne Livewire 3.x + Alpine.js patterns PRZED pisaniem components

**Context7 Checkpoints:**

```php
// 1. Livewire 3.x lifecycle
mcp__context7__resolve-library-id('livewire')
// Expected: /livewire/livewire (867 snippets, trust: 7.4)

mcp__context7__get-library-docs(
    '/livewire/livewire',
    'component lifecycle mount hydrate render events dispatching wire:key'
)

// 2. Alpine.js patterns
mcp__context7__resolve-library-id('alpinejs')
// Expected: /alpinejs/alpine (364 snippets, trust: 6.6)

mcp__context7__get-library-docs(
    '/alpinejs/alpine',
    'reactive data x-data x-model x-on x-show x-if'
)
```

**Verify:**
- [ ] Livewire 3.x lifecycle (mount → hydrate → render)
- [ ] Event dispatching (`dispatch()` NOT `emit()`!)
- [ ] `wire:key` requirements (OBOWIĄZKOWE w @foreach!)
- [ ] Alpine.js reactivity patterns
- [ ] `$wire` interaction patterns (Livewire ↔ Alpine)

**CRITICAL from _ISSUES_FIXES:**
- [ ] `wire:snapshot` pattern (Blade wrapper dla routes)
- [ ] `wire:poll` POZA `@if` (conditional rendering issue)
- [ ] `wire:key="unique-{{ $context }}-{{ $item->id }}"` (cross-contamination prevention)
- [ ] x-teleport z `$wire.method()` (nie `wire:click`)

**SPLIT STRATEGY (zgodnie z SEKCJA 0.3):**
- MAX 300 linii per Livewire component
- Jeśli >250 linii → split do sub-components + Services
- Business logic → Services, Livewire TYLKO UI interaction

---
```

---

### **ZMIANA 3: DODAĆ SKU-FIRST ENHANCEMENTS**

#### **SEKCJA 1.3.1 - Vehicle Compatibility Table (DODAJ SKU backup columns)**

**Lokalizacja:** W migration `create_vehicle_compatibility_table.php` (około linii 490-540)

**Dodaj kolumny SKU backup:**

```sql
-- ✅ SKU-FIRST ENHANCEMENTS (ADDED 2025-10-16 based on documentation-reader compliance report)
part_sku VARCHAR(255) NULL COMMENT 'SKU backup dla lookup (gdy product_id zmieni się)',
vehicle_sku VARCHAR(255) NULL COMMENT 'SKU vehicula (jeśli applicable)',

-- Indexes dla SKU lookups
INDEX idx_part_sku (part_sku),
INDEX idx_vehicle_sku (vehicle_sku),
INDEX idx_sku_lookup (part_sku, vehicle_sku)
```

**Uzasadnienie:** SKU_ARCHITECTURE_GUIDE.md - SKU jako UNIVERSAL IDENTIFIER, ID może się zmienić (re-import, merge), SKU ZAWSZE ten sam

#### **SEKCJA 1.3.2 - Vehicle Compatibility Cache Table (SKU-based cache keys)**

**Lokalizacja:** W migration `create_vehicle_compatibility_cache_table.php` (około linii 540-590)

**Aktualizuj cache_key strategy:**

```sql
-- ✅ SKU-FIRST CACHE KEYS (ADDED 2025-10-16)
cache_key VARCHAR(500) NOT NULL COMMENT 'SKU-based: "sku:{part_sku}:shop:{shop_id}"',

-- Index dla SKU-based lookups
INDEX idx_cache_key (cache_key)
```

**Cache Strategy Pattern:**

```php
// ✅ CORRECT - PRIMARY: Cache key z SKU
$cacheKey = "sku:{$product->sku}:shop:{$shopId}:compatibility";

// ❌ WRONG - Cache key z ID (nie przetrwa re-import)
$cacheKey = "product:{$product->id}:shop:{$shopId}:compatibility";
```

#### **SEKCJA 2.3 - CompatibilityManager (SKU-first lookup pattern)**

**Lokalizacja:** W CompatibilityManager specyfikacji (około linii 780-850)

**Dodaj nową sekcję 2.3.1:**

```markdown
#### **2.3.1 SKU-FIRST LOOKUP PATTERN (MANDATORY)**

**⚠️ CRITICAL:** Wszystkie compatibility queries MUSZĄ używać SKU jako PRIMARY lookup, ID jako FALLBACK

**Pattern Implementation:**

```php
// app/Services/Products/CompatibilityManager.php

/**
 * Get compatibility data for product (SKU-FIRST approach)
 *
 * @see _DOCS/SKU_ARCHITECTURE_GUIDE.md
 */
public function getCompatibility(string $sku, int $shopId): Collection
{
    // 1. Try cache with SKU key (PRIMARY)
    $cacheKey = "sku:{$sku}:shop:{$shopId}:compatibility";
    if ($cached = Cache::get($cacheKey)) {
        Log::debug('Compatibility cache HIT', ['sku' => $sku, 'shop_id' => $shopId]);
        return $cached;
    }

    // 2. Query by SKU (PRIMARY)
    $product = Product::where('sku', $sku)->first();

    if ($product) {
        // 3a. Query compatibility by product_id (performance)
        $compatibility = VehicleCompatibility::where('part_product_id', $product->id)
            ->where('shop_id', $shopId)
            ->with('vehicleProduct')
            ->get();
    } else {
        // 3b. FALLBACK: Try by SKU backup column (edge case: product deleted, compatibility remains)
        $compatibility = VehicleCompatibility::where('part_sku', $sku)
            ->where('shop_id', $shopId)
            ->get();

        Log::warning('Product not found, used SKU backup', ['sku' => $sku]);
    }

    // 4. Cache with SKU key (TTL: 3600s = 1h)
    Cache::put($cacheKey, $compatibility, 3600);

    return $compatibility;
}

/**
 * Refresh compatibility cache (SKU-based invalidation)
 */
public function refreshCompatibilityCache(string $sku, int $shopId): void
{
    $cacheKey = "sku:{$sku}:shop:{$shopId}:compatibility";
    Cache::forget($cacheKey);

    // Rebuild cache
    $this->getCompatibility($sku, $shopId);
}
```

**❌ ANTI-PATTERN (DO NOT USE):**

```php
// ❌ WRONG - ID-first approach
public function getCompatibility(int $productId, int $shopId): Collection
{
    $cacheKey = "product:{$productId}:shop:{$shopId}"; // ← ID breaks on re-import!

    return VehicleCompatibility::where('part_product_id', $productId) // ← ID lookup primary
        ->where('shop_id', $shopId)
        ->get();
}
```

**Why SKU-FIRST?**
- ✅ **Persistence:** SKU nie zmienia się po re-import
- ✅ **Consistency:** Ten sam SKU = ten sam produkt fizyczny
- ✅ **Cache Stability:** Cache keys przetrwają re-import
- ❌ **ID Problem:** ID może się zmienić (re-import, merge, migrate)

**Reference:** `_DOCS/SKU_ARCHITECTURE_GUIDE.md` (Database Schema section)

---
```

---

### **ZMIANA 4: AKTUALIZOWAĆ TIMELINE ESTIMATES**

**Lokalizacja:** Executive Summary (początek pliku, około linii 3-5)

**PRZED:**
```markdown
**Szacowany czas:** 77-97 godzin (2.5-3 tygodnie full-time)
```

**PO:**
```markdown
**Szacowany czas:** 97-126 godzin (12-16 dni = 2.5-3 tygodnie full-time)
  - PRE-IMPLEMENTATION REFACTORING (SEKCJA 0): +12-16h (MANDATORY sequential)
  - FAZA 1-7 (implementacja): 85-110h (original estimate)
```

#### **Dodaj szczegółowy breakdown po Executive Summary:**

**Lokalizacja:** Po linii 54 (po "User experience: Intuicyjne UI z masową edycją")

```markdown

### ⏱️ Timeline (UPDATED 2025-10-16)

**Sequential (1 developer):**
- **Total:** 97-126h (12-16 dni roboczych = 2.5-3 tygodnie)
- **Breakdown:**
  - SEKCJA 0 (Pre-Implementation): 12-16h (MUST complete first)
  - FAZA 1 (Database): 12-15h
  - FAZA 2 (Models): 8-10h
  - FAZA 3 (Services): 20-25h
  - FAZA 4 (UI): 15-20h
  - FAZA 5 (PrestaShop): 12-15h
  - FAZA 6 (CSV): 8-10h
  - FAZA 7 (Performance): 10-15h

**Parallelized (3 developers):**
- **Total:** 67-81h (8-10 dni roboczych = 2 tygodnie)
- **Breakdown:**
  - SEKCJA 0 (Pre-Implementation): 12-16h (sequential - MUST complete first)
  - FAZA 1-3 (sequential): 40-50h (critical path)
  - FAZA 4-6 (parallel): 35-45h
  - FAZA 7 (final): 10-15h

**CRITICAL PATH:** SEKCJA 0 → FAZA 1 → FAZA 2 → FAZA 3 (52-66h sequential)

**Reference:** `_AGENT_REPORTS/architect_etap05a_implementation_plan_2025-10-16.md` (TIMELINE ESTIMATE section)
```

---

### **ZMIANA 5: AKTUALIZOWAĆ COMPLIANCE STATEMENT**

**Lokalizacja:** Dodać nową sekcję po Executive Summary, przed "ANALIZA OBECNEGO STANU"

**Dodaj po linii 54:**

```markdown

---

## ⚠️ COMPLIANCE STATUS (Updated 2025-10-16)

### Pre-Update Compliance Score

**Overall:** 78/100 (Dobry, ale wymaga krytycznych poprawek)

**Breakdown:**
- Database Schema Design: 66% (46/70)
- Backend Service Layer: 53% (37/70)
- Model Extensions: 60% (36/60)
- UI/UX Components: 37% (22/60)
- CSV Import/Export: 73% (44/60)
- PrestaShop Integration: 75% (45/60)
- Performance Optimization: 78% (47/60)

### Critical Violations Identified

**HIGH PRIORITY (MUST FIX przed implementacją):**

1. **❌ WIELKOSC PLIKOW - Naruszenie CLAUDE.md (max 300 linii)**
   - Product.php: 2181 linii → Rozdziel na 8 Traits (SEKCJA 0.1)
   - VariantManager: ~500 linii planned → Rozdziel na 4 services (SEKCJA 0.2.1)
   - CompatibilityManager: ~600 linii planned → Rozdziel na 4 services (SEKCJA 0.2.2)
   - FeatureManager: ~400 linii planned → Rozdziel na 3 services (SEKCJA 0.2.3)
   - VariantsTab: ~500 linii planned → Rozdziel na component + service (SEKCJA 0.3.1)
   - CompatibilityTab: ~600 linii planned → Rozdziel na component + service (SEKCJA 0.3.2)

2. **❌ BRAK Context7 INTEGRATION - Naruszenie AGENT_USAGE_GUIDE.md**
   - SEKCJA 1: Laravel 12.x migrations patterns (ADDED 1.0)
   - SEKCJA 2: Laravel service layer patterns (ADDED 2.0)
   - SEKCJA 4: Livewire 3.x + Alpine.js patterns (ADDED 4.0)

3. **⚠️ SKU-first Pattern - Częściowe naruszenia**
   - vehicle_compatibility: brak SKU backup columns (FIXED 1.3.1)
   - compatibility_cache: brak SKU-based cache keys (FIXED 1.3.2)
   - CompatibilityManager: mixed ID/SKU queries (FIXED 2.3.1)

**MEDIUM PRIORITY (Zalecane poprawki):**
- Frontend Verification requirement (Reference: _DOCS/FRONTEND_VERIFICATION_GUIDE.md)
- Exception Handling Strategy (Custom exceptions dla domain errors)
- Performance Monitoring (Cache TTL, slow query logging)

### Post-Update Target Compliance Score

**Target:** 95+/100

**Actions Taken:**
- ✅ SEKCJA 0 added (Pre-Implementation Refactoring)
- ✅ Context7 checkpoints added (SEKCJA 1.0, 2.0, 4.0)
- ✅ SKU-first enhancements added (SEKCJA 1.3.1, 1.3.2, 2.3.1)
- ✅ Timeline updated (+ 12-16h overhead)
- ✅ Service/UI split strategies documented (SEKCJA 0.2, 0.3)

**Remaining Actions:**
- [ ] User approval of updated plan
- [ ] laravel-expert: Execute SEKCJA 0 refactoring
- [ ] coding-style-agent: Review SEKCJA 0 completion
- [ ] Proceed to FAZA 1 (Database Migrations)

**Reference:** `_AGENT_REPORTS/documentation_reader_etap05a_compliance_2025-10-16.md` (COMPLIANCE MATRIX section)

---
```

---

## SZCZEGÓŁOWE INSTRUKCJE IMPLEMENTACJI

### Dla laravel-expert (SEKCJA 0 Executor)

**Workflow:**

1. **PRZED rozpoczęciem:**
   - [ ] Przeczytaj `_DOCS/SKU_ARCHITECTURE_GUIDE.md`
   - [ ] Przeczytaj `CLAUDE.md` (zasady max 300 linii)
   - [ ] Context7: `/websites/laravel_12_x` → "traits best practices"

2. **Podczas refactoringu:**
   - [ ] Extract pricing methods → HasPricing.php (~150 linii)
   - [ ] Extract stock methods → HasStock.php (~140 linii)
   - [ ] Extract category methods → HasCategories.php (~120 linii)
   - [ ] Extract variant methods → HasVariants.php (~130 linii) - **NOWE**
   - [ ] Extract feature methods → HasFeatures.php (~110 linii) - **NOWE**
   - [ ] Extract compatibility methods → HasCompatibility.php (~140 linii) - **NOWE**
   - [ ] Refactor multi-store methods → HasMultiStore.php (~160 linii)
   - [ ] Refactor sync methods → HasSyncStatus.php (~120 linii)
   - [ ] Update Product.php → use all traits (~250 linii)
   - [ ] Verify all tests GREEN

3. **Po ukończeniu:**
   - [ ] coding-style-agent review
   - [ ] Raport w `_AGENT_REPORTS/laravel_expert_sekcja0_refactoring_2025-10-XX.md`
   - [ ] architect approval

### Dla coding-style-agent (Reviewer)

**Review Checklist:**

- [ ] Product.php ≤ 300 linii?
- [ ] All Traits ≤ 150 linii?
- [ ] No code duplication?
- [ ] All methods accessible (trait imports correct)?
- [ ] Tests GREEN?
- [ ] Naming conventions followed?
- [ ] No hardcoded values?
- [ ] PHPDoc comments complete?

---

## RISK ASSESSMENT

### SEKCJA 0 Refactoring Risks

**Risk 1: Circular Dependencies (Product ↔ ProductVariant)**
- **Probability:** MEDIUM
- **Impact:** HIGH (całość przestaje działać)
- **Mitigation:** Use lazy loading w relationships, test każdy trait osobno

**Risk 2: Test Regressions**
- **Probability:** MEDIUM
- **Impact:** MEDIUM (development delays)
- **Mitigation:** Run tests po każdym extracted trait, fix immediately

**Risk 3: Missing Methods after Refactor**
- **Probability:** LOW
- **Impact:** HIGH (code breaks)
- **Mitigation:** Create comprehensive method inventory przed refactorem, verify all accessible po refactorze

### Timeline Impact

**Best Case:** 12h refactoring, smooth implementation → 97h total
**Realistic:** 14h refactoring, minor issues → 107h total
**Worst Case:** 16h refactoring, regressions + fixes → 120h total

**Recommended:** Plan for 14-16h SEKCJA 0 overhead (realistic scenario)

---

## NEXT STEPS

### Immediate Actions (Post-Approval)

1. **User Decision:**
   - [ ] Approve updated plan with SEKCJA 0?
   - [ ] Allocate 12-16h pre-implementation time?
   - [ ] Single developer (97-126h) vs Team (67-81h)?

2. **laravel-expert Briefing:**
   - [ ] Share this report
   - [ ] Share `_DOCS/SKU_ARCHITECTURE_GUIDE.md`
   - [ ] Context7 access verification

3. **Start SEKCJA 0:**
   - [ ] laravel-expert: Begin Product.php refactoring
   - [ ] Daily progress updates w Plan_Projektu/ETAP_05a_Produkty.md
   - [ ] coding-style-agent review po każdym trait

### Post-SEKCJA 0 (Ready for FAZA 1)

4. **Verify SEKCJA 0 Completion:**
   - [ ] All success criteria met
   - [ ] coding-style-agent approval
   - [ ] Tests GREEN

5. **Proceed to FAZA 1:**
   - [ ] laravel-expert: Database migrations (15 files)
   - [ ] Reference: `_AGENT_REPORTS/laravel_expert_etap05a_migrations_spec_2025-10-16.md`

---

## FILES MODIFIED

### Created:
- `_AGENT_REPORTS/architect_etap05a_plan_update_2025-10-16.md` (this file)

### To Be Modified (by User):
- `Plan_Projektu/ETAP_05a_Produkty.md` (add SEKCJA 0, Context7 checkpoints, SKU enhancements, timeline update, compliance statement)

### Referenced:
- `_AGENT_REPORTS/documentation_reader_etap05a_compliance_2025-10-16.md`
- `_AGENT_REPORTS/laravel_expert_etap05a_migrations_spec_2025-10-16.md`
- `_AGENT_REPORTS/architect_etap05a_implementation_plan_2025-10-16.md`
- `_DOCS/SKU_ARCHITECTURE_GUIDE.md`
- `_DOCS/AGENT_USAGE_GUIDE.md`
- `_DOCS/CONTEXT7_INTEGRATION_GUIDE.md`
- `CLAUDE.md`

---

## CONCLUSION

### Summary

Plan ETAP_05a jest **kompleksowy i dobrze przemyślany** (78/100 compliance), ale wymaga **krytycznych poprawek** przed rozpoczęciem implementacji:

1. **SEKCJA 0 (Pre-Implementation Refactoring)** - MANDATORY 12-16h
2. **Context7 integration** - 3 checkpoints (SEKCJA 1.0, 2.0, 4.0)
3. **SKU-first enhancements** - 3 locations (1.3.1, 1.3.2, 2.3.1)
4. **Timeline update** - +12-16h overhead
5. **Compliance statement** - dokumentacja zmian

### Recommendation

**✅ APPROVE PLAN WITH UPDATES**

- Timeline: 97-126h total (12-16h pre-work + 85-110h implementation)
- Compliance Target: 95+/100
- Risk: LOW (with SEKCJA 0 completion)
- Business Value: HIGH (80% time reduction, 95% accuracy)

**Next Step:** User approval → laravel-expert begins SEKCJA 0

---

**Data:** 2025-10-16
**Agent:** architect
**Status:** ✅ REPORT COMPLETE - READY FOR USER APPROVAL
**Następny krok:** User decision + laravel-expert SEKCJA 0 execution
