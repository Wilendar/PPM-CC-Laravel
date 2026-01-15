# 🛠️ ETAP_05d: System Zarządzania Dopasowaniami Części Zamiennych - REDESIGN

**Status ETAPU:** 🛠️ **W TRAKCIE** - FAZA 0-4.5 ukończone, FAZA 5+ oczekuje (2025-12-19)
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 74-84 godzin (9-11 dni roboczych, zwiększone z powodu złożoności migracji)
**Zależności:** ETAP_05a (CompatibilityManager ✅), ETAP_05b (ProductForm ✅)
**Deployment:** https://ppm.mpptrade.pl/admin/compatibility

**Data utworzenia planu:** 2025-12-04
**Data architecture review:** 2025-12-05
**Raporty architektury:**
- [`_AGENT_REPORTS/architect_COMPATIBILITY_SYSTEM_REDESIGN.md`](../_AGENT_REPORTS/architect_COMPATIBILITY_SYSTEM_REDESIGN.md) - Architektura systemu
- [`_AGENT_REPORTS/import_export_EXCEL_COMPATIBILITY_ANALYSIS.md`](../_AGENT_REPORTS/import_export_EXCEL_COMPATIBILITY_ANALYSIS.md) - Analiza workflow Excel
- [`_AGENT_REPORTS/frontend_COMPATIBILITY_TILES_UX_DESIGN.md`](../_AGENT_REPORTS/frontend_COMPATIBILITY_TILES_UX_DESIGN.md) - Design UX kafelków
- ⚠️ [`_AGENT_REPORTS/architect_ETAP05d_ARCHITECTURE_REVIEW.md`](../_AGENT_REPORTS/architect_ETAP05d_ARCHITECTURE_REVIEW.md) - **ARCHITECTURE REVIEW** (2025-12-05)

---

## 📊 EXECUTIVE SUMMARY

### 🎯 Cel Etapu

**PRZEPROJEKTOWANIE** systemu dopasowań części zamiennych z:
- **Tile-Based UI** - kafelki pojazdów do szybkiego zaznaczania (klik na kafelek, NIE checkbox)
- **3 grupy dopasowań:** Oryginał (O), Zamiennik (Z), Model (auto-suma O+Z)
- **Smart Suggestions** - inteligentne podpowiedzi na podstawie marki, nazwy, opisu
- **Per-Shop Filtering** - KRYTYCZNE: filtrowanie marek pojazdów per sklep
- **Dwukierunkowy workflow:**
  - ProductForm TAB (część zamienne) → przypisz pojazdy
  - ProductForm TAB (pojazd) → wyświetl przypisane części z kategoriami i miniaturkami
- **Panel masowej edycji** `/admin/compatibility` - Excel-like bulk operations

### 🔑 Kluczowe Różnice vs. Stary Plan

| Aspekt | Stary Plan | Nowy Plan |
|--------|------------|-----------|
| **UI Selection** | Checkboxy | Tile-Based (klik na kafelek) |
| **Grupy** | Original/Replacement/Model | Oryginał (O) / Zamiennik (Z) / Model (auto) |
| **Sugestie** | Brak | SmartSuggestionEngine (confidence 0.00-1.00) |
| **Per-Shop** | Podstawowe | Pełne filtrowanie + allowed_vehicle_brands |
| **Widok Pojazdu** | Brak | Części pogrupowane wg kategorii + miniaturki |
| **Bulk Edit** | Prosty modal | Excel-like workflow (drag & select) |

### 📈 Business Value

- **Szybkość:** Tile-based UI = 3x szybsze zaznaczanie vs. checkboxy
- **Inteligencja:** Smart suggestions redukują pracę ręczną o ~60%
- **Flexibility:** Per-shop = różne dopasowania na różnych sklepach
- **Dwukierunkowy workflow:** Część→Pojazdy + Pojazd→Części
- **Audit Trail:** Pełne logowanie bulk operations

---

## ✅ ARCHITECTURE REVIEW NOTES (2025-12-05) - RESOLVED

**DECYZJE UŻYTKOWNIKA (2025-12-05):**
- ✅ AI Suggestions: **Per-Shop** (różne sugestie dla różnych sklepów)
- ✅ Istniejące dane: **DELETE ALL** - system od zera (fresh import z PrestaShop/CSV)
- ✅ Granularność: **PARENT level** (product, nie variant)
- ✅ ETAP_05a: Plan utworzony

**ZAIMPLEMENTOWANE (2025-12-05):**
- ✅ 4 migracje: shop_id, brand_restrictions, suggestions_cache, bulk_operations
- ✅ 2 zaktualizowane modele: VehicleCompatibility, PrestaShopShop
- ✅ 2 nowe modele: CompatibilitySuggestion, CompatibilityBulkOperation
- ✅ 2 nowe serwisy: SmartSuggestionEngine, ShopFilteringService
- ✅ Deployment: wszystkie migracje wykonane pomyślnie

**SZCZEGÓŁY:** Pełna analiza w `_AGENT_REPORTS/architect_ETAP05d_ARCHITECTURE_REVIEW.md`

---

## PLAN RAMOWY ETAPU (REVISED)

| FAZA | Nazwa | Czas | Status |
|------|-------|------|--------|
| **FAZA 0** | User Decisions & Architecture Finalization | 2h | ✅ **DONE** (2025-12-05) |
| **FAZA 1** | Database Migrations (REVISED) | 18-24h | ✅ **DONE** (2025-12-05) |
| **FAZA 2** | Services Layer (REVISED) | 14-18h | ✅ **DONE** (2025-12-05) |
| **FAZA 3** | CompatibilityPanel UI (masowa edycja) | 18h | 🛠️ IN PROGRESS |
| **FAZA 4** | ProductForm TAB - Część Zamienna | 12h | ✅ **DONE** (2025-12-08) |
| **FAZA 4.5** | **Synchronizacja Dopasowań z PrestaShop** | 12h | ✅ **DONE** (2025-12-19) |
| **FAZA 5** | ProductForm TAB - Pojazd (części view) | 10h | ❌ |
| **FAZA 6** | Smart Suggestions (DEFERRED) | 12h | ❌ |
| **FAZA 7** | Per-Shop Filtering | 10h | ❌ |
| **FAZA 8** | Testing & Deployment (EXTENDED) | 12h | ❌ |
| **TOTAL** | | **86-96h** | **FAZA 0-4.5: ✅ DONE** |

---

## 📋 FAZA 1: DATABASE MIGRATIONS (18-24h REVISED) - ✅ DONE

~~⚠️ **ARCHITECTURE REVIEW (2025-12-05):** Migration 1.1 wymaga **PRZEPROJEKTOWANIA** z powodu unique constraint conflict!~~ **RESOLVED (2025-12-05)**

**Cel:** Rozszerzenie schematu bazy danych o per-shop support, smart suggestions, audit log.

**KRYTYCZNY PROBLEM:** Istniejący unique constraint `(product_id, vehicle_model_id)` **BLOKUJE** dodanie `shop_id`!

### ✅ 1.1 Migration: Shop Support dla vehicle_compatibility (REVISED - 12h)

**❌ ORYGINALNY PLAN (NIE UŻYĆ!):**
```php
// WRONG: Dodanie shop_id bez zmiany unique constraint nie zadziała!
Schema::table('vehicle_compatibility', function (Blueprint $table) {
    $table->foreignId('shop_id')->nullable()->after('vehicle_sku');
    // ❌ PROBLEM: unique constraint nadal (product_id, vehicle_model_id)
    // = NIE MOŻNA mieć tego samego produktu+pojazdu dla różnych sklepów!
});
```

**✅ PRZEPROJEKTOWANA MIGRACJA (USE THIS!):**
```php
// 2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php

public function up(): void
{
    // Step 1: Add shop_id column (NULLABLE initially)
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->foreignId('shop_id')
              ->nullable()
              ->after('vehicle_model_id')
              ->constrained('prestashop_shops')
              ->cascadeOnDelete()
              ->comment('Shop-specific compatibility (NULL before migration)');
    });

    // Step 2: Migrate existing data to default shop
    $defaultShopId = DB::table('prestashop_shops')
        ->where('is_default', true)
        ->value('id');

    if (!$defaultShopId) {
        throw new \Exception('No default shop found! Set prestashop_shops.is_default=1 before migration.');
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

    // Step 6: ADD indexes for per-shop filtering
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->index(['shop_id', 'product_id'], 'idx_compat_shop_product');
        $table->index('shop_id', 'idx_compat_shop');
    });

    // Step 7: Smart suggestions tracking
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        $table->boolean('is_suggested')->default(false)->after('notes');
        $table->decimal('confidence_score', 3, 2)->nullable()->after('is_suggested');
        $table->json('metadata')->nullable()->after('confidence_score');
    });
}

public function down(): void
{
    Schema::table('vehicle_compatibility', function (Blueprint $table) {
        // Remove suggestions columns
        $table->dropColumn(['is_suggested', 'confidence_score', 'metadata']);

        // Drop per-shop indexes
        $table->dropIndex('idx_compat_shop_product');
        $table->dropIndex('idx_compat_shop');

        // Drop new unique constraint
        $table->dropUnique('uniq_compat_product_vehicle_shop');

        // Restore old constraint
        $table->unique(['product_id', 'vehicle_model_id'], 'uniq_compat_product_vehicle');

        // Drop shop_id
        $table->dropForeign(['shop_id']);
        $table->dropColumn('shop_id');
    });
}
```

**KLUCZOWE ZMIANY:**
- ✅ Migracja istniejących danych do default shop (BEFORE NOT NULL)
- ✅ shop_id **NOT NULL** (data integrity)
- ✅ Unique constraint z `shop_id` (pozwala per-shop compatibility)
- ✅ Rollback strategy (restore old constraint)
- ✅ Extended testing required (migration + rollback)

**TESTOWANIE (MANDATORY):**
```php
// Test 1: Per-shop compatibility creation
VehicleCompatibility::create([
    'product_id' => 123,
    'vehicle_model_id' => 456,
    'shop_id' => 1,  // B2B Test DEV
    'compatibility_attribute_id' => 1, // Original
]);

VehicleCompatibility::create([
    'product_id' => 123,
    'vehicle_model_id' => 456,
    'shop_id' => 2,  // Pitbike.pl
    'compatibility_attribute_id' => 2, // Replacement
]);
// ✅ Should succeed (different shop_id)

// Test 2: Duplicate detection
VehicleCompatibility::create([
    'product_id' => 123,
    'vehicle_model_id' => 456,
    'shop_id' => 1,  // B2B Test DEV (duplicate)
    'compatibility_attribute_id' => 2, // Different attribute
]);
// ❌ Should FAIL (duplicate product+vehicle+shop)

// Test 3: Rollback
php artisan migrate:rollback
// ✅ Should restore old constraint
```

**Deliverables:**
- ✅ 1.1.1 Utworzenie migracji
  └──📁 PLIK: database/migrations/2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php
- ✅ 1.1.2 Test lokalny
- ✅ 1.1.3 Deploy na produkcję

### ✅ 1.2 Migration: Brand Restrictions dla prestashop_shops

```php
// add_brand_restrictions_to_prestashop_shops.php
Schema::table('prestashop_shops', function (Blueprint $table) {
    // Dozwolone marki pojazdów (JSON array)
    $table->json('allowed_vehicle_brands')->nullable();
    // NULL = wszystkie marki, [] = brak, ["YCF", "Honda"] = tylko te

    // Compatibility settings (JSON)
    $table->json('compatibility_settings')->nullable();
    // { "enable_smart_suggestions": true, "auto_apply": false, "min_confidence": 0.75 }
});
```

**Deliverables:**
- ✅ 1.2.1 Utworzenie migracji
  └──📁 PLIK: database/migrations/2025_12_05_000002_add_brand_restrictions_to_prestashop_shops.php
- ✅ 1.2.2 Update PrestashopShop model ($casts, $fillable)
  └──📁 PLIK: app/Models/PrestaShopShop.php
- ✅ 1.2.3 Deploy na produkcję

### ✅ 1.3 Nowa Tabela: compatibility_suggestions (CACHE)

```sql
CREATE TABLE compatibility_suggestions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    part_sku VARCHAR(255) NOT NULL,
    vehicle_model_id BIGINT UNSIGNED NOT NULL,
    vehicle_sku VARCHAR(255) NOT NULL,
    shop_id BIGINT UNSIGNED NULLABLE,

    suggestion_reason ENUM('brand_match', 'name_match', 'description_match', 'category_match'),
    confidence_score DECIMAL(3, 2) NOT NULL,
    is_applied BOOLEAN DEFAULT 0,
    applied_at TIMESTAMP NULLABLE,
    applied_by BIGINT UNSIGNED NULLABLE,

    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL, -- TTL: 24h

    UNIQUE KEY uniq_suggestion (product_id, vehicle_model_id, shop_id)
);
```

**Deliverables:**
- ✅ 1.3.1 Utworzenie migracji
  └──📁 PLIK: database/migrations/2025_12_05_000003_create_compatibility_suggestions_table.php
- ✅ 1.3.2 Utworzenie modelu CompatibilitySuggestion
  └──📁 PLIK: app/Models/CompatibilitySuggestion.php
- ✅ 1.3.3 Deploy na produkcję

### ✅ 1.4 Nowa Tabela: compatibility_bulk_operations (AUDIT LOG)

```sql
CREATE TABLE compatibility_bulk_operations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    operation_type ENUM('add', 'remove', 'verify', 'copy', 'apply_suggestions'),
    user_id BIGINT UNSIGNED NOT NULL,
    shop_id BIGINT UNSIGNED NULLABLE,

    operation_data JSON NOT NULL,
    affected_rows INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed'),
    error_message TEXT NULLABLE,

    started_at TIMESTAMP,
    completed_at TIMESTAMP NULLABLE
);
```

**Deliverables:**
- ✅ 1.4.1 Utworzenie migracji
  └──📁 PLIK: database/migrations/2025_12_05_000004_create_compatibility_bulk_operations_table.php
- ✅ 1.4.2 Utworzenie modelu CompatibilityBulkOperation
  └──📁 PLIK: app/Models/CompatibilityBulkOperation.php
- ✅ 1.4.3 Deploy na produkcję

---

## 📋 FAZA 2: SERVICES LAYER (14h) - ✅ DONE

**Cel:** Implementacja warstwy serwisów dla smart suggestions, shop filtering, bulk operations.

### ✅ 2.1 SmartSuggestionEngine Service

**Lokalizacja:** `app/Services/Compatibility/SmartSuggestionEngine.php`

**Algorytm confidence score:**
```
Brand Match:  product.manufacturer == vehicle.brand  → +0.50
Name Match:   product.name CONTAINS vehicle.model    → +0.30
Description:  product.description CONTAINS vehicle   → +0.10
Category:     matching category patterns             → +0.10
```

**Metody:**
```php
public function generateSuggestions(Product $product, ?int $shopId = null): Collection
public function calculateConfidenceScore(Product $product, VehicleModel $vehicle): float
public function cacheSuggestions(int $productId, Collection $suggestions): void
public function getCachedSuggestions(int $productId, ?int $shopId = null): ?Collection
public function applySuggestion(int $suggestionId, string $type, User $user): bool
```

**Deliverables:**
- ✅ 2.1.1 Utworzenie SmartSuggestionEngine
  └──📁 PLIK: app/Services/Compatibility/SmartSuggestionEngine.php
- ✅ 2.1.2 Implementacja algorytmu confidence score (WEIGHT_BRAND_MATCH=0.50, WEIGHT_NAME_MATCH=0.30, etc.)
- ✅ 2.1.3 Implementacja cache layer (TTL 24h)
- ❌ 2.1.4 Unit tests

### ✅ 2.2 ShopFilteringService

**Lokalizacja:** `app/Services/Compatibility/ShopFilteringService.php`

**Metody:**
```php
public function getAllowedBrands(?int $shopId): ?array
public function isBrandAllowed(string $brand, ?int $shopId): bool
public function filterVehiclesByShop(Collection $vehicles, ?int $shopId): Collection
public function getShopSettings(?int $shopId): array
public function updateAllowedBrands(int $shopId, array $brands, User $admin): bool
```

**Logika:**
- `shop_id = NULL` → Dane domyślne (wszystkie marki)
- `allowed_vehicle_brands = NULL` → Brak restrykcji
- `allowed_vehicle_brands = []` → Brak dopasowań dla tego sklepu
- `allowed_vehicle_brands = ['YCF', 'Honda']` → Tylko te marki

**Deliverables:**
- ✅ 2.2.1 Utworzenie ShopFilteringService
  └──📁 PLIK: app/Services/Compatibility/ShopFilteringService.php
- ✅ 2.2.2 Implementacja brand filtering logic (getAllowedBrands, getFilteredVehicles, queryFilteredVehicles)
- ✅ 2.2.3 Integracja z CompatibilityManager
- ❌ 2.2.4 Unit tests

### ✅ 2.3 Rozszerzenie CompatibilityBulkService

**Nowe metody:**
```php
public function bulkAdd(array $productIds, array $vehicleModelIds, int $attributeId, ?int $shopId, User $user): int
public function bulkRemove(array $productIds, array $vehicleModelIds, ?int $shopId): int
public function bulkVerify(array $compatibilityIds, User $user): int
public function bulkApplySuggestions(array $suggestionIds, User $user): int
```

**Audit Trail:**
- Każda bulk operacja → insert do `compatibility_bulk_operations`
- JSON `operation_data` zawiera: product_ids, vehicle_ids, attribute_id, shop_id

**Deliverables:**
- ✅ 2.3.1 Rozszerzenie CompatibilityBulkService
  └──📁 PLIK: app/Services/CompatibilityBulkService.php
- ✅ 2.3.2 Implementacja audit logging
- ✅ 2.3.3 DB::transaction() dla atomicity
- ❌ 2.3.4 Unit tests

### ✅ 2.4 Rozszerzenie CompatibilityManager

**Nowe metody:**
```php
public function getCompatibilityForShop(int $productId, ?int $shopId = null): Collection
public function addCompatibilityForShop(int $productId, int $vehicleId, string $type, ?int $shopId): bool
public function removeCompatibilityForShop(int $productId, int $vehicleId, ?int $shopId): bool
public function getPartsForVehicle(int $vehicleId, ?int $shopId = null): Collection  // NEW!
```

**Deliverables:**
- ✅ 2.4.1 Rozszerzenie CompatibilityManager
  └──📁 PLIK: app/Services/CompatibilityManager.php
- ✅ 2.4.2 Metoda getPartsForVehicle() dla widoku pojazdu
- ✅ 2.4.3 Shop context support we wszystkich metodach
- ❌ 2.4.4 Unit tests

---

## 📋 FAZA 3: COMPATIBILITYPANEL UI - MASOWA EDYCJA (18h)

**Status:** 🛠️ IN PROGRESS (2025-12-05)

**Cel:** Panel `/admin/compatibility` z tile-based UI, bulk operations, smart suggestions.

### ✅ WYKONANE FIXES (2025-12-05)

**3.6.1 Brand Controls Fix:**
- ✅ Naprawiono kontrolki "Zaznacz wszystkie w marce" / "Odznacz wszystkie w marce"
- ✅ Problem: Livewire nie może serializować Collection w wire:click
- ✅ Rozwiązanie: Metody selectAllInBrand()/deselectAllInBrand() pobierają pojazdy wewnętrznie przez query
- └──📁 PLIK: `app/Http/Livewire/Admin/Compatibility/Traits/ManagesVehicleSelection.php`

**3.7.1 "Tylko bez dopasowan" Filter:**
- ✅ Dodano checkbox filtra dla części bez żadnych dopasowań
- ✅ Filtr używa whereDoesntHave('vehicleCompatibility')
- ✅ Zintegrowane z queryString (URL persistence)
- └──📁 PLIK: `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php`
- └──📁 PLIK: `resources/views/livewire/admin/compatibility/compatibility-management.blade.php`
- └──📁 PLIK: `resources/css/products/compatibility-tiles.css`

**Screenshots:**
- `_TOOLS/screenshots/compat_brand_controls_SUCCESS.jpg`
- `_TOOLS/screenshots/compat_no_matches_filter_SUCCESS.jpg`

### ❌ 3.1 CompatibilityManagement Component - Przeprojektowanie

**Lokalizacja:** `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php`

**Properties:**
```php
public ?int $shopContext = null;          // Per-shop context
public bool $showSuggestions = true;      // Toggle suggestions
public float $minConfidenceScore = 0.50;  // Min confidence dla sugestii
public string $viewMode = 'parts';        // 'parts' | 'vehicles'
public array $selectedPartIds = [];       // Multi-select parts
public array $selectedVehicleIds = [];    // Multi-select vehicles
public string $searchPart = '';           // Search filter
public string $filterBrand = '';          // Brand filter
public string $sortField = 'sku';         // Sort
public string $sortDirection = 'asc';
```

**Deliverables:**
- ✅ 3.1.1 Przeprojektowanie CompatibilityManagement.php
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php
- ✅ 3.1.2 Real queries (zamiast mock data) - Vehicle tiles teraz z Products type='pojazd'
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php (getVehiclesForShop)
- ✅ 3.1.3 Per-shop context switching
- ❌ 3.1.4 Integration z SmartSuggestionEngine

### ✅ 3.2 Blade View - Tile-Based Layout

**Layout Structure:**
```
┌────────────────────────────────────────────────────────────────┐
│ COMPATIBILITY PANEL - Dopasowania Części Zamiennych            │
├────────────────────────────────────────────────────────────────┤
│ [🔍 Search Part] [Shop: ▼] [Brand: ▼] [☑ Show Suggestions]    │
├────────────────────────────────────────────────────────────────┤
│ ▼ PART-001 | Hamulec kompletny YCF Pilot                      │
│                                                                 │
│   ORYGINAŁ (2 pojazdy):                                        │
│   ┌─────────┬─────────┬─────────┬─────────┐                   │
│   │ YCF     │ YCF     │         │         │                   │
│   │ Pilot 50│ Pilot110│ + Dodaj │         │                   │
│   │ [O] ✓   │ [O] ✓   │         │         │                   │
│   └─────────┴─────────┴─────────┴─────────┘                   │
│                                                                 │
│   ZAMIENNIK (1 pojazd):                                        │
│   ┌─────────┬─────────┬─────────┬─────────┐                   │
│   │ Honda   │         │         │         │                   │
│   │ CRF 50  │ + Dodaj │         │         │                   │
│   │ [Z]     │         │         │         │                   │
│   └─────────┴─────────┴─────────┴─────────┘                   │
│                                                                 │
│   💡 SUGESTIE (confidence ≥ 0.50):                            │
│   ┌─────────┬─────────┬─────────┬─────────┐                   │
│   │ YCF     │ Pitbike │ KAYO    │         │                   │
│   │ F125    │ 125cc   │ 125 TD  │         │                   │
│   │ ⭐ 0.95 │ ⭐ 0.75 │ ⭐ 0.65 │         │                   │
│   └─────────┴─────────┴─────────┴─────────┘                   │
├────────────────────────────────────────────────────────────────┤
│ [Pagination: 1-50 of 1,245 parts]                              │
└────────────────────────────────────────────────────────────────┘
```

**Deliverables:**
- ✅ 3.2.1 Utworzenie compatibility-management.blade.php
  └──📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php
- ✅ 3.2.2 Tile-based vehicle grid
- ✅ 3.2.3 Collapsible part rows
- ❌ 3.2.4 Suggestions display section

### ✅ 3.3 VehicleTile Component (Reusable)

**Alpine.js State Management:**
```javascript
x-data="{
    selectedOriginal: @entangle('originalVehicles'),
    selectedZamiennik: @entangle('zamiennikVehicles'),
    selectionMode: 'original', // 'original' | 'zamiennik'

    toggleVehicle(vehicleId) {
        if (this.selectionMode === 'original') {
            this.toggleArray(this.selectedOriginal, vehicleId);
        } else {
            this.toggleArray(this.selectedZamiennik, vehicleId);
        }
    },

    toggleArray(arr, id) {
        const idx = arr.indexOf(id);
        if (idx === -1) arr.push(id);
        else arr.splice(idx, 1);
    }
}"
```

**Deliverables:**
- ✅ 3.3.1 Utworzenie vehicle-tile.blade.php (partial) - zaimplementowane w compatibility-management.blade.php
- ✅ 3.3.2 Alpine.js toggle logic
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/Traits/ManagesVehicleSelection.php
- ✅ 3.3.3 Visual states (selected O, selected Z, both)
- ✅ 3.3.4 Hover effects, animations

### ✅ 3.4 CSS Styling - Tile System

**Lokalizacja:** `resources/css/products/compatibility-tiles.css`

**Klasy:**
```css
.vehicle-tile { }
.vehicle-tile--selected-original { border-color: var(--ppm-success); }
.vehicle-tile--selected-zamiennik { border-color: var(--mpp-primary); }
.vehicle-tile--selected-both { background: linear-gradient(...); }
.vehicle-tile__badge--original { background: rgba(5, 150, 105, 0.2); }
.vehicle-tile__badge--zamiennik { background: rgba(224, 172, 126, 0.2); }
.vehicle-tile__badge--model { background: rgba(37, 99, 235, 0.2); }
.brand-section { }
.brand-section--collapsed { }
.suggestion-tile { border-style: dashed; }
```

**Responsive Grid:**
- Desktop ≥1024px: 6 tiles per row
- Tablet 768-1023px: 4 tiles per row
- Mobile <768px: 2 tiles per row

**Deliverables:**
- ✅ 3.4.1 Utworzenie compatibility-tiles.css (1661 linii!)
  └──📁 PLIK: resources/css/products/compatibility-tiles.css
- ✅ 3.4.2 Badge styles (O/Z/Model)
- ✅ 3.4.3 Tile selection states
- ✅ 3.4.4 Responsive grid
- ✅ 3.4.5 Collapsible brand sections
- ✅ 3.4.6 npm run build + deploy

### ✅ 3.5 Bulk Actions Modal

**Funkcjonalność:**
- Bulk Add: Zaznacz wiele parts → dodaj do wielu vehicles
- Bulk Remove: Usuń wybrane dopasowania
- Bulk Apply Suggestions: Zastosuj wszystkie sugestie z min confidence

**Deliverables:**
- ✅ 3.5.1 BulkEditCompatibilityModal component
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/BulkEditCompatibilityModal.php
- ✅ 3.5.2 Direction selector (Part→Vehicle / Vehicle→Part)
- ✅ 3.5.3 Type selector (Oryginał / Zamiennik)
- ❌ 3.5.4 Preview table przed apply
- ❌ 3.5.5 Progress indicator dla bulk operations

### ✅ 3.6 Floating Action Bar (ROZSZERZONY) - CORE DONE (2025-12-08)

**Cel:** Pływający pasek akcji widoczny podczas przewijania listy pojazdów z przyciskami zmiany trybu i synchronizacji.

**ZAIMPLEMENTOWANO (2025-12-08):**
- ✅ Floating bar ZAWSZE widoczny podczas edycji (nie tylko przy pending changes)
- ✅ Przyciski Oryginal/Zamiennik sticky na dole viewport
- ✅ CSS position: sticky; bottom: 0;
- ✅ **Radio button icons** - aktywny = wypełnione kółko (fas), nieaktywny = puste (far)
  └──📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php

**Layout:**
```
┌──────────────────────────────────────────────────────────────────────────┐
│  [ 🟢 ORYGINAŁ ]  [ 🟡 ZAMIENNIK ]  │  Zaznaczono: 5 zmian  │  [ 💾 Zapisz ]  [ 📤 Zapisz i wyślij ]  │
│     ▲ active                        │                       │                                        │
└──────────────────────────────────────────────────────────────────────────┘
                           ↑ STICKY: position: sticky; bottom: 0; z-index: 50;
```

**Funkcjonalności:**

1. **Pływające przyciski trybu (Oryginał / Zamiennik):**
   - Sticky position na dole ekranu podczas scrollowania
   - Aktywny tryb = podświetlony przycisk
   - Klik zmienia tryb zaznaczania dla wszystkich tiles
   - Widoczne nawet przy długiej liście 100+ pojazdów

2. **Przycisk "Zapisz i wyślij na sklepy":**
   - Zapisuje zmiany w bazie (jak standardowy Save)
   - Uruchamia Job `SyncCompatibilityToShopsJob`
   - Job aktualizuje WSZYSTKIE zmodyfikowane produkty na WSZYSTKIE przypisane sklepy
   - Progress bar z licznikiem "Synchronizacja: 5/23 produktów"
   - Po zakończeniu: flash message "Zsynchronizowano 23 produkty na 3 sklepy"

**Deliverables:**
- ✅ 3.6.1 Floating action bar blade component (FIXED position - zawsze widoczny!)
  └──📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php
- ✅ 3.6.2 Mode switching buttons (Oryginał / Zamiennik) - prominent, always visible
- ❌ 3.6.3 Bulk selection buttons (zaznacz wszystkie O/Z)
- ✅ 3.6.4 Save button z loading state
- ❌ 3.6.5 **"Zapisz i wyślij na sklepy" button** z confirmation modal
- ❌ 3.6.6 **SyncCompatibilityToShopsJob** - Job synchronizacji dopasowań
- ❌ 3.6.7 **Progress tracking** - JobProgress dla sync status
- ✅ 3.6.8 **CSS FIXED positioning** - position: fixed; bottom: 20px; (floating bar)
  └──📁 PLIK: resources/css/products/compatibility-tiles.css

### ❌ 3.7 SyncCompatibilityToShopsJob (NOWY)

**Lokalizacja:** `app/Jobs/PrestaShop/SyncCompatibilityToShopsJob.php`

**Logika:**
```php
class SyncCompatibilityToShopsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $productIds,      // Zmodyfikowane produkty
        public ?int $shopId = null,    // null = wszystkie sklepy
        public int $userId
    ) {}

    public function handle(
        PrestaShopFeatureSyncService $featureSync,
        JobProgressService $progressService
    ): void
    {
        $products = Product::whereIn('id', $this->productIds)
            ->with(['vehicleCompatibility', 'shops'])
            ->get();

        $progress = $progressService->create(
            type: 'compatibility_sync',
            total: $products->count(),
            metadata: ['user_id' => $this->userId]
        );

        foreach ($products as $product) {
            // Sync compatibility features to PrestaShop
            $shops = $this->shopId
                ? [$product->shops->find($this->shopId)]
                : $product->shops;

            foreach ($shops as $shop) {
                $featureSync->syncCompatibilityFeatures($product, $shop);
            }

            $progressService->increment($progress->id);
        }

        $progressService->complete($progress->id);
    }
}
```

**Deliverables:**
- ❌ 3.7.1 Utworzenie SyncCompatibilityToShopsJob
- ❌ 3.7.2 Integracja z JobProgressService
- ❌ 3.7.3 Metoda dispatchSyncToShops() w CompatibilityManagement
- ❌ 3.7.4 Flash messages po zakończeniu sync
- ❌ 3.7.5 Error handling z retry logic

---

## 📋 FAZA 4: PRODUCTFORM TAB - CZĘŚĆ ZAMIENNA (12h) - ✅ DONE (2025-12-08)

**Cel:** TAB "Dopasowania" w ProductForm dla produktów typu spare_part.

**UWAGA:** Slug typu produktu to `czesc-zamienna` (nie `spare_part`!)

### ✅ 4.1 CompatibilityTab Trait

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/ProductFormCompatibility.php`

**Properties:**
```php
public Collection $originalVehicles;      // Vehicles marked as Oryginał
public Collection $zamiennikVehicles;     // Vehicles marked as Zamiennik
public Collection $suggestions;            // Smart suggestions
public string $vehicleSearch = '';         // Search filter
public string $vehicleBrandFilter = '';    // Brand filter
public ?int $compatibilityShopContext = null; // Per-shop context
```

**Methods:**
```php
public function loadCompatibilityData(): void
public function loadSuggestions(): void
public function addOriginal(int $vehicleId): void
public function addZamiennik(int $vehicleId): void
public function removeCompatibility(int $vehicleId, string $type): void
public function applySuggestion(int $suggestionId, string $type): void
public function bulkApplySuggestions(): void
```

**Deliverables:**
- ✅ 4.1.1 Utworzenie ProductFormCompatibility trait
  └──📁 PLIK: app/Http/Livewire/Products/Management/Traits/ProductFormCompatibility.php
- ✅ 4.1.2 Integracja z ProductForm component
  └──📁 PLIK: app/Http/Livewire/Products/Management/ProductForm.php (use ProductFormCompatibility)
- ✅ 4.1.3 Shop context handling (z selectedShop)
- ✅ 4.1.4 Livewire event handling

### ✅ 4.2 Blade View - Compatibility Tab

**Lokalizacja:** `resources/views/livewire/products/management/tabs/compatibility-tab.blade.php`

**Conditional Display:**
```blade
@if($product->product_type === 'spare_part')
    <div x-show="activeTab === 'compatibility'">
        @include('livewire.products.management.tabs.compatibility-tab')
    </div>
@endif
```

**Layout:**
```
┌────────────────────────────────────────────────────────────────┐
│ TAB: DOPASOWANIA                                                │
├────────────────────────────────────────────────────────────────┤
│ [🔍 Szukaj pojazdu] [Marka: ▼] [Tryb: Oryginał ▼]             │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ▼ YCF (12 pojazdów)                                            │
│ ┌─────────┬─────────┬─────────┬─────────┬─────────┬─────────┐ │
│ │ YCF     │ YCF     │ YCF     │ YCF     │ YCF     │ YCF     │ │
│ │ Pilot   │ Bigy    │ Factory │ SM 50   │ SP03    │ F150    │ │
│ │ [O] ✓   │         │ [Z]     │ [O][Z]  │         │         │ │
│ └─────────┴─────────┴─────────┴─────────┴─────────┴─────────┘ │
│                                                                 │
│ 💡 SUGESTIE:                                                   │
│ ┌─────────┬─────────┬─────────┐                               │
│ │ YCF F125│ KAYO 125│ Pitbike │ [✅ Zastosuj wszystkie]       │
│ │ ⭐ 0.95 │ ⭐ 0.80 │ ⭐ 0.65 │                               │
│ └─────────┴─────────┴─────────┘                               │
├────────────────────────────────────────────────────────────────┤
│ PODSUMOWANIE:                                                   │
│ Oryginał: 5 pojazdów | Zamiennik: 3 pojazdy | Model: 8 łącznie │
└────────────────────────────────────────────────────────────────┘
```

**Deliverables:**
- ✅ 4.2.1 Utworzenie compatibility-tab.blade.php
  └──📁 PLIK: resources/views/livewire/products/management/tabs/compatibility-tab.blade.php
- ✅ 4.2.2 Vehicle tiles grid (reuse z FAZA 3)
- ✅ 4.2.3 Brand sections collapsible
- ✅ 4.2.4 Suggestions section (placeholder - FAZA 6)
- ✅ 4.2.5 Summary bar (Oryginał/Zamiennik counts)

### ✅ 4.3 Tab Navigation Update

**Dodanie tab w tab-navigation.blade.php:**
```blade
@if($product->product_type === 'spare_part')
<button @click="activeTab = 'compatibility'"
        :class="{ 'tab-active': activeTab === 'compatibility' }">
    <x-icon name="link" class="w-4 h-4" />
    Dopasowania
    <span class="tab-badge">{{ $compatibilityCount }}</span>
</button>
@endif
```

**Deliverables:**
- ✅ 4.3.1 Update tab-navigation.blade.php
  └──📁 PLIK: resources/views/livewire/products/management/partials/tab-navigation.blade.php
- ✅ 4.3.2 Conditional display dla czesc-zamienna (slug)
- ✅ 4.3.3 Badge z liczbą dopasowań (getCompatibilityCounts)

### ✅ 4.4 Per-Shop Context Integration

**Logika:**
- Dane domyślne: `selectedShop = null` → pokaż wszystkie marki
- Shop context: `selectedShop = 2 (B2B)` → filtruj tylko dozwolone marki
- Auto-inherit: przy zmianie shop → przeładuj vehicles z filtrem

**Deliverables:**
- ✅ 4.4.1 Watch selectedShop changes
- ✅ 4.4.2 Auto-reload compatibility przy shop switch (loadCompatibilityData)
- ✅ 4.4.3 Visual indicator aktywnego shop context (shop badges)

**FIXES podczas implementacji (2025-12-08):**
- ✅ Poprawka slug: `spare_part` → `czesc-zamienna` w tab-navigation.blade.php i ProductFormCompatibility.php
- ✅ Poprawka undefined variable: dodano `isset($selectedShop)` checks w compatibility-tab.blade.php (linie 43, 274)

**Screenshot weryfikacji:**
- `_TOOLS/screenshots/etap05d_faza4_dopasowania_fixed.jpg`

---

## 📋 FAZA 4.5: SYNCHRONIZACJA DOPASOWAŃ Z PRESTASHOP (12h) - ✅ DONE (2025-12-19)

**Cel:** Dwukierunkowa synchronizacja dopasowań pojazdów z PrestaShop poprzez system Features.

**WYNIK:** Pełna dwukierunkowa synchronizacja dopasowań działa poprawnie:
- **Export (PPM → PrestaShop):** VehicleCompatibility → PrestaShop Features 431/432/433
- **Import (PrestaShop → PPM):** PrestaShop Features → VehicleCompatibility z mapowań vehicle_feature_value_mappings

**Raporty agentów:**
- [`_AGENT_REPORTS/prestashop_api_expert_COMPATIBILITY_FEATURES_ANALYSIS.md`](../_AGENT_REPORTS/prestashop_api_expert_COMPATIBILITY_FEATURES_ANALYSIS.md) - Analiza struktury features PrestaShop
- [`_AGENT_REPORTS/laravel_expert_SYNC_SERVICES_ANALYSIS.md`](../_AGENT_REPORTS/laravel_expert_SYNC_SERVICES_ANALYSIS.md) - Analiza istniejących serwisów sync

### 🔑 Kluczowe Ustalenia z Analizy (2025-12-09)

**PrestaShop Features Structure:**
| Feature ID | Nazwa | Feature Values | Products Assigned |
|------------|-------|----------------|-------------------|
| 431 | Oryginał | 103 values | 6,693 products |
| 432 | Model | 103 values | 7,717 products |
| 433 | Zamiennik | 99 values | 2,319 products |

**Mapowanie PPM → PrestaShop:**
```
PPM VehicleCompatibility (type=original) → PrestaShop Feature 431 (Oryginał)
PPM VehicleCompatibility (type=replacement) → PrestaShop Feature 433 (Zamiennik)
PPM computed (Model = O ∪ Z) → PrestaShop Feature 432 (Model)
```

**XML Format dla API:**
```xml
<product_feature>
    <id>431</id>
    <id_feature_value>2141</id_feature_value>
</product_feature>
```

### ✅ 4.5.1 VehicleCompatibilitySyncService (4h) - DONE (2025-12-09)

**Lokalizacja:** `app/Services/PrestaShop/VehicleCompatibilitySyncService.php`
  └──📁 PLIK: app/Services/PrestaShop/VehicleCompatibilitySyncService.php

**Odpowiedzialność:**
- Transformacja VehicleCompatibility → PrestaShop Features XML
- Transformacja PrestaShop Features → VehicleCompatibility
- Obsługa feature_value ID mappings

**Metody:**
```php
class VehicleCompatibilitySyncService
{
    // Feature IDs from PrestaShop database
    public const FEATURE_ORYGINAL = 431;
    public const FEATURE_MODEL = 432;
    public const FEATURE_ZAMIENNIK = 433;

    /**
     * Export compatibility to PrestaShop features format
     */
    public function transformToPrestaShopFeatures(
        Product $product,
        int $shopId
    ): array

    /**
     * Import compatibility from PrestaShop features
     */
    public function importFromPrestaShopFeatures(
        array $productData,
        Product $product,
        int $shopId
    ): Collection

    /**
     * Get or create feature value for vehicle
     */
    public function getOrCreateFeatureValue(
        int $featureId,
        string $vehicleName,
        BasePrestaShopClient $client
    ): int

    /**
     * Map PPM compatibility type to PrestaShop feature ID
     */
    public function mapTypeToFeatureId(string $type): int

    /**
     * Calculate Model feature (union of Original + Replacement)
     */
    public function calculateModelFeatureValues(
        Product $product,
        int $shopId
    ): array
}
```

**Deliverables:**
- ✅ 4.5.1.1 Utworzenie VehicleCompatibilitySyncService
  └──📁 PLIK: app/Services/PrestaShop/VehicleCompatibilitySyncService.php
- ✅ 4.5.1.2 Implementacja transformToPrestaShopFeatures()
- ✅ 4.5.1.3 Implementacja importFromPrestaShopFeatures()
- ✅ 4.5.1.4 Implementacja getOrCreateFeatureValue() z cache
- ✅ 4.5.1.5 Implementacja calculateModelFeatureValues() (Model = union O + Z)

### ✅ 4.5.2 Database Migrations - Feature Mappings (2h) - DONE (2025-12-09)

**Nowe tabele do mapowania feature values:**

**1. compatibility_feature_mappings:**
```sql
CREATE TABLE compatibility_feature_mappings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    compatibility_attribute_id BIGINT UNSIGNED NOT NULL,  -- FK to compatibility_attributes
    prestashop_feature_id INT NOT NULL,                   -- 431/432/433
    shop_id BIGINT UNSIGNED NOT NULL,                     -- FK to prestashop_shops

    UNIQUE KEY uniq_attr_feature_shop (compatibility_attribute_id, shop_id),

    FOREIGN KEY (compatibility_attribute_id) REFERENCES compatibility_attributes(id),
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id)
);
```

**2. vehicle_feature_value_mappings:**
```sql
CREATE TABLE vehicle_feature_value_mappings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vehicle_product_id BIGINT UNSIGNED NOT NULL,          -- FK to products (pojazd)
    prestashop_feature_id INT NOT NULL,                   -- 431/432/433
    prestashop_feature_value_id INT NOT NULL,             -- ID wartości w PrestaShop
    shop_id BIGINT UNSIGNED NOT NULL,                     -- FK to prestashop_shops
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_vehicle_feature_shop (vehicle_product_id, prestashop_feature_id, shop_id),
    INDEX idx_feature_value (prestashop_feature_value_id),

    FOREIGN KEY (vehicle_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id)
);
```

**Deliverables:**
- ✅ 4.5.2.1 Utworzenie migracji compatibility_feature_mappings
  └──📁 PLIK: database/migrations/2025_12_09_121630_create_compatibility_feature_mappings_table.php
- ✅ 4.5.2.2 Utworzenie migracji vehicle_feature_value_mappings
  └──📁 PLIK: database/migrations/2025_12_09_121629_create_vehicle_feature_value_mappings_table.php
- ✅ 4.5.2.3 Utworzenie modeli Eloquent
  └──📁 PLIK: app/Models/CompatibilityFeatureMapping.php
  └──📁 PLIK: app/Models/VehicleFeatureValueMapping.php
- ✅ 4.5.2.4 Seeder z domyślnymi mapowaniami (431=Original, 433=Replacement) - w migracji
- ✅ 4.5.2.5 Deploy migracji na produkcję (batch 73, 74 - verified)

### ✅ 4.5.3 Integration z ProductSyncStrategy (3h) - DONE (2025-12-09)

**Lokalizacja:** `app/Services/PrestaShop/Sync/ProductSyncStrategy.php`
  └──📁 PLIK: app/Services/PrestaShop/Sync/ProductSyncStrategy.php (metoda syncCompatibilitiesIfEnabled na liniach 1095-1239)

**Nowa metoda syncCompatibilitiesIfEnabled():**
```php
/**
 * Sync vehicle compatibility as features to PrestaShop
 * Pattern: Non-blocking (errors logged, don't fail product sync)
 */
protected function syncCompatibilitiesIfEnabled(
    Product $product,
    PrestaShopShop $shop,
    int $externalId,
    BasePrestaShopClient $client
): void
{
    try {
        // Check if compatibility sync is enabled
        if (!SystemSetting::getValue('compatibility.auto_sync_on_product_sync', true)) {
            return;
        }

        // Only sync for spare parts (czesc-zamienna)
        if ($product->product_type !== 'czesc-zamienna') {
            return;
        }

        // Load compatibility data
        $compatibilities = VehicleCompatibility::byProduct($product->id)
            ->byShop($shop->id)
            ->with(['vehicleProduct', 'compatibilityAttribute'])
            ->get();

        if ($compatibilities->isEmpty()) {
            return;
        }

        // Transform to PrestaShop features format
        $features = $this->compatibilitySyncService
            ->transformToPrestaShopFeatures($product, $shop->id);

        // GET current product features
        $currentProduct = $client->getProduct($externalId);
        $existingFeatures = $currentProduct['associations']['product_features'] ?? [];

        // Merge compatibility features (preserve other features!)
        $mergedFeatures = $this->mergeFeatures(
            $existingFeatures,
            $features,
            [self::FEATURE_ORYGINAL, self::FEATURE_MODEL, self::FEATURE_ZAMIENNIK]
        );

        // PUT updated product
        $client->updateProduct($externalId, [
            'associations' => [
                'product_features' => $mergedFeatures
            ]
        ]);

        Log::info("[ProductSyncStrategy] Synced compatibility features", [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'external_id' => $externalId,
            'features_count' => count($features)
        ]);

    } catch (\Exception $e) {
        // Non-blocking: log error but don't fail product sync
        Log::error("[ProductSyncStrategy] Compatibility sync failed", [
            'product_id' => $product->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Wywołanie w execute():**
```php
public function execute(Product $product, PrestaShopShop $shop): SyncResult
{
    // ... existing sync logic ...

    // After product sync success:
    $this->syncMediaIfEnabled($product, $shop, $externalId, $client);
    $this->syncFeaturesIfEnabled($product, $shop, $externalId, $client);
    $this->syncCompatibilitiesIfEnabled($product, $shop, $externalId, $client); // NEW!

    return $result;
}
```

**Deliverables:**
- ✅ 4.5.3.1 Dodanie syncCompatibilitiesIfEnabled() do ProductSyncStrategy
  └──📁 PLIK: app/Services/PrestaShop/Sync/ProductSyncStrategy.php:1095-1239
- ✅ 4.5.3.2 Dependency injection VehicleCompatibilitySyncService (inline instantiation)
- ✅ 4.5.3.3 Implementacja mergeFeatures() (preserve non-compatibility features)
- ✅ 4.5.3.4 SystemSetting dla compatibility.auto_sync_on_product_sync (default: true)
- ✅ 4.5.3.5 Integracja w metodzie syncToPrestaShop() linia 327

### ✅ 4.5.4 Integration z CompatibilityManagement Panel (2h) - DONE (2025-12-09)

**Cel:** Przycisk "Zapisz i wyślij na sklepy" uruchamia Job synchronizacji.
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php (metoda saveAndSync() linia 480-496)

**UWAGA:** Panel już ma metodę saveAndSync() która dispatchuje SyncProductToPrestaShop Job.
Job wywołuje ProductSyncStrategy::syncToPrestaShop() → syncCompatibilitiesIfEnabled().

**Nowy Job: SyncCompatibilityToPrestaShopJob:**
```php
class SyncCompatibilityToPrestaShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $productIds,
        public ?int $shopId = null,
        public int $userId
    ) {}

    public function handle(
        VehicleCompatibilitySyncService $syncService,
        JobProgressService $progressService
    ): void
    {
        $products = Product::whereIn('id', $this->productIds)
            ->with(['vehicleCompatibility', 'shopProducts'])
            ->get();

        $progress = $progressService->create(
            type: 'compatibility_sync_to_prestashop',
            total: $products->count(),
            metadata: ['user_id' => $this->userId, 'shop_id' => $this->shopId]
        );

        foreach ($products as $product) {
            try {
                $shops = $this->shopId
                    ? [PrestaShopShop::find($this->shopId)]
                    : $product->shopProducts->pluck('shop');

                foreach ($shops as $shop) {
                    $client = $shop->getClient();
                    $externalId = $product->getExternalId($shop->id);

                    if (!$externalId) continue;

                    $features = $syncService->transformToPrestaShopFeatures($product, $shop->id);
                    // ... sync logic
                }

                $progressService->increment($progress->id);
            } catch (\Exception $e) {
                $progressService->logError($progress->id, $product->id, $e->getMessage());
            }
        }

        $progressService->complete($progress->id);
    }
}
```

**W CompatibilityManagement component:**
```php
public function saveAndSyncToShops(): void
{
    // 1. Save to database
    $this->saveCompatibilityChanges();

    // 2. Get affected product IDs
    $productIds = collect($this->pendingChanges)
        ->pluck('product_id')
        ->unique()
        ->toArray();

    // 3. Dispatch sync job
    SyncCompatibilityToPrestaShopJob::dispatch(
        $productIds,
        $this->shopContext,
        auth()->id()
    );

    // 4. Show flash message with job progress link
    $this->dispatch('show-flash', [
        'type' => 'success',
        'message' => "Zapisano zmiany. Synchronizacja {$count} produktów w toku..."
    ]);
}
```

**Deliverables:**
- ✅ 4.5.4.1 ~~Utworzenie SyncCompatibilityToPrestaShopJob~~ (używa istniejącego SyncProductToPrestaShop → syncCompatibilitiesIfEnabled)
- ✅ 4.5.4.2 Metoda saveAndSync() w CompatibilityManagement (linia 507-570)
  └──📁 PLIK: app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php
- ✅ 4.5.4.3 Progress tracking z JobProgressService
- ✅ 4.5.4.4 Flash messages po zakończeniu sync
- ✅ 4.5.4.5 Blade button "Zapisz i wyslij" (linia 438)
  └──📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php

### ✅ 4.5.5 Import Compatibility z PrestaShop (InstaPull) (2h) - DONE (2025-12-10)

**Cel:** Podczas PullSingleProductFromPrestaShop pobierz też dopasowania z features.

**Rozszerzenie PullSingleProductFromPrestaShop:**
```php
// W PullSingleProductFromPrestaShop::handle()

// After pulling product data...
$this->pullCompatibilityFromFeatures($product, $shop, $productData);

protected function pullCompatibilityFromFeatures(
    Product $product,
    PrestaShopShop $shop,
    array $productData
): void
{
    $features = $productData['associations']['product_features'] ?? [];

    // Filter only compatibility features (431, 432, 433)
    $compatibilityFeatures = collect($features)->filter(
        fn($f) => in_array($f['id'], [431, 432, 433])
    );

    if ($compatibilityFeatures->isEmpty()) {
        return;
    }

    $this->compatibilitySyncService->importFromPrestaShopFeatures(
        $productData,
        $product,
        $shop->id
    );
}
```

**Deliverables:**
- ✅ 4.5.5.1 Rozszerzenie PullSingleProductFromPrestaShop (linie 220-245)
  └──📁 PLIK: app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php
- ✅ 4.5.5.2 ~~Metoda pullCompatibilityFromFeatures()~~ (inline w handle(), używa VehicleCompatibilitySyncService::importFromPrestaShopFeatures)
- ✅ 4.5.5.3 Mapping feature_value_id → vehicle_product_id (VehicleCompatibilitySyncService::findVehicleByFeatureValue)
- ✅ 4.5.5.4 Obsługa nieznanych feature_values (log warning - non-blocking)
- ❌ 4.5.5.5 Unit tests dla import flow

### ✅ 4.5.6 Pending Changes Status w UI (1h) - DONE (już zaimplementowane)

**Cel:** Wizualne oznaczenie pending changes przed/podczas/po sync.

**Stany (ZAIMPLEMENTOWANE):**
1. **SAVED** - `pending-changes-badge` z licznikiem zmian
2. **SYNCING** - `sync-status-pending`, `sync-status-running` z spinnerem
3. **SYNCED** - `sync-status-completed` z checkmark
4. **ERROR** - `sync-status-failed` z warning icon

**Blade UI:**
```blade
@foreach($compatibilities as $compat)
    <div class="compatibility-row">
        <!-- ... existing content ... -->

        <div class="sync-status">
            @if($compat->sync_status === 'pending')
                <span class="sync-badge sync-badge--pending" title="Oczekuje na synchronizację">💾</span>
            @elseif($compat->sync_status === 'syncing')
                <span class="sync-badge sync-badge--syncing" title="Synchronizacja w toku">
                    <x-spinner size="sm" />
                </span>
            @elseif($compat->sync_status === 'synced')
                <span class="sync-badge sync-badge--synced" title="Zsynchronizowano">✅</span>
            @elseif($compat->sync_status === 'error')
                <span class="sync-badge sync-badge--error" title="{{ $compat->sync_error }}">❌</span>
            @endif
        </div>
    </div>
@endforeach
```

**Deliverables:**
- ✅ 4.5.6.1 System JobProgress do sledzenia statusu sync (zamiast kolumny sync_status)
        └──📁 PLIK: app/Services/JobProgressService.php
- ✅ 4.5.6.2 CSS klasy dla sync badges (sync-status-badge, sync-status-pending/running/completed/failed)
        └──📁 PLIK: resources/css/admin/components.css
- ✅ 4.5.6.3 Aktualizacja statusu przez Job (via JobProgressService)
        └──📁 PLIK: app/Jobs/PrestaShop/SyncProductToPrestaShop.php
- ✅ 4.5.6.4 Wire:poll dla refresh statusu podczas sync + floating progress bar
        └──📁 PLIK: resources/views/livewire/admin/compatibility/compatibility-management.blade.php (linii 400-418)

### ✅ 4.5.7 Testing & Verification (1h) - DONE (2025-12-19)

**Test Cases WYKONANE:**

1. **✅ Export Test:**
   - Dodano dopasowanie Oryginał do produktu 11181 (MRF Nakladki) → pojazd 11183 (Buggy KAYO S200)
   - Uruchomiono sync to PrestaShop (SyncProductToPrestaShop)
   - Zweryfikowano w PrestaShop: Feature 431 (Oryginał) → value 2686

2. **✅ Import Test:**
   - Usunięto kompatybilność w PPM
   - Uruchomiono PullSingleProductFromPrestaShop
   - Zweryfikowano że VehicleCompatibility został odtworzony w PPM z poprawnymi danymi

3. **✅ Bidirectional Sync Test (FULL CYCLE):**
   - PPM → PS → PPM cycle przetestowany
   - Test script: `_TOOLS/test_full_bidirectional_sync.php`
   - Wynik: SUCCESS - Bidirectional sync working!

**Bug Fixes podczas testów:**
- ✅ Fixed `Product::where('type', 'pojazd')` → `Product::where('product_type_id', 1)` w findVehicleByFeatureValue()
- ✅ Dodano `saveFeatureValueMapping()` do zapisywania mapowań vehicle→feature_value podczas eksportu
  └──📁 PLIK: app/Services/PrestaShop/VehicleCompatibilitySyncService.php (linie 255-285)

**Test Scripts utworzone:**
- `_TOOLS/test_compatibility_sync.php` - Test eksportu kompatybilności
- `_TOOLS/sync_product.php` - Manual product sync
- `_TOOLS/test_import_compatibility.php` - Test importu kompatybilności
- `_TOOLS/verify_ps_features.php` - Weryfikacja features w PrestaShop
- `_TOOLS/test_full_bidirectional_sync.php` - Pełny test dwukierunkowej synchronizacji

**Deliverables:**
- ✅ 4.5.7.1 Manual test export flow (PPM → PrestaShop)
- ✅ 4.5.7.2 Manual test import flow (PrestaShop → PPM)
- ✅ 4.5.7.3 Bug fixes: findVehicleByFeatureValue() + saveFeatureValueMapping()
- ✅ 4.5.7.4 Test scripts documentation

---

## 📋 FAZA 5: PRODUCTFORM TAB - POJAZD (CZĘŚCI VIEW) (10h)

**Cel:** TAB "Części Zamienne" w ProductForm dla produktów typu vehicle - wyświetlanie części pogrupowanych wg kategorii z miniaturkami.

### ❌ 5.1 VehiclePartsTab Trait

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/ProductFormVehicleParts.php`

**Properties:**
```php
public Collection $assignedParts;          // Części przypisane do pojazdu
public array $partsGroupedByCategory = []; // Części pogrupowane wg kategorii
public string $partsSearch = '';           // Search filter
public string $partsCategoryFilter = '';   // Category filter
public ?int $vehiclePartsShopContext = null; // Per-shop context
```

**Methods:**
```php
public function loadVehicleParts(): void
public function groupPartsByCategory(Collection $parts): array
public function getPartsForVehicle(): Collection // via CompatibilityManager
public function filterPartsByCategory(string $categoryId): void
```

**Query:**
```php
// CompatibilityManager::getPartsForVehicle()
return VehicleCompatibility::where('vehicle_model_id', $vehicleId)
    ->when($shopId, fn($q) => $q->where(fn($q2) =>
        $q2->where('shop_id', $shopId)->orWhereNull('shop_id')
    ))
    ->with(['product.media', 'product.categories', 'compatibilityAttribute'])
    ->get()
    ->map(fn($compat) => [
        'id' => $compat->product_id,
        'sku' => $compat->part_sku,
        'name' => $compat->product->name,
        'thumbnail' => $compat->product->thumbnail_url,
        'category' => $compat->product->primary_category,
        'type' => $compat->compatibilityAttribute->code, // original/replacement
        'type_name' => $compat->compatibilityAttribute->name, // Oryginał/Zamiennik
    ]);
```

**Deliverables:**
- ❌ 5.1.1 Utworzenie ProductFormVehicleParts trait
- ❌ 5.1.2 Metoda groupPartsByCategory()
- ❌ 5.1.3 Integracja z ProductForm component
- ❌ 5.1.4 Thumbnail URL resolution

### ❌ 5.2 Blade View - Vehicle Parts Tab

**Lokalizacja:** `resources/views/livewire/products/management/tabs/vehicle-parts-tab.blade.php`

**Conditional Display:**
```blade
@if($product->product_type === 'vehicle')
    <div x-show="activeTab === 'vehicle_parts'">
        @include('livewire.products.management.tabs.vehicle-parts-tab')
    </div>
@endif
```

**Layout:**
```
┌────────────────────────────────────────────────────────────────┐
│ TAB: CZĘŚCI ZAMIENNE                                            │
│ Pojazd: YCF Pilot 50 (2015-2023)                               │
├────────────────────────────────────────────────────────────────┤
│ [🔍 Szukaj części] [Kategoria: ▼] [Pokaż tylko: Oryginały ▼]  │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ▼ HAMULCE (5 części)                                           │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ ┌─────┐ MRF26-73-012                               [O] ✓  │ │
│ │ │ IMG │ Hamulec kompletny przód pitbike YCF         ────── │ │
│ │ └─────┘ Kategoria: Hamulce > Zaciski                      │ │
│ ├────────────────────────────────────────────────────────────┤ │
│ │ ┌─────┐ 18291/152FMH                               [Z]    │ │
│ │ │ IMG │ Uszczelka zacisku hamulca przód             ────── │ │
│ │ └─────┘ Kategoria: Hamulce > Uszczelki                    │ │
│ └────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ ▼ FILTRY POWIETRZA (3 części)                                  │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ ┌─────┐ AIR-FILTER-001                             [O] ✓  │ │
│ │ │ IMG │ Filtr powietrza YCF Pilot 50/110           ────── │ │
│ │ └─────┘ Kategoria: Filtry > Powietrza                     │ │
│ └────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ ▲ OPONY (collapsed - 8 części)                                 │
│ ▲ ZAWIESZENIE (collapsed - 12 części)                          │
│                                                                 │
├────────────────────────────────────────────────────────────────┤
│ PODSUMOWANIE:                                                   │
│ Oryginał: 15 części | Zamiennik: 8 części | Razem: 23 części  │
└────────────────────────────────────────────────────────────────┘
```

**Deliverables:**
- ❌ 5.2.1 Utworzenie vehicle-parts-tab.blade.php
- ❌ 5.2.2 Category collapsible sections
- ❌ 5.2.3 Part row z thumbnail, SKU, name, badge O/Z
- ❌ 5.2.4 Summary bar
- ❌ 5.2.5 Empty state ("Brak przypisanych części")

### ❌ 5.3 Part Row Component (Reusable)

**Blade Partial:** `partials/vehicle-part-row.blade.php`

**Structure:**
```blade
<div class="vehicle-part-row">
    <div class="vehicle-part-row__thumbnail">
        @if($part['thumbnail'])
            <img src="{{ $part['thumbnail'] }}" alt="{{ $part['name'] }}" loading="lazy" />
        @else
            <div class="vehicle-part-row__placeholder">
                <x-icon name="photo" class="w-8 h-8 text-gray-400" />
            </div>
        @endif
    </div>
    <div class="vehicle-part-row__content">
        <span class="vehicle-part-row__sku">{{ $part['sku'] }}</span>
        <span class="vehicle-part-row__name">{{ $part['name'] }}</span>
        <span class="vehicle-part-row__category">{{ $part['category'] }}</span>
    </div>
    <div class="vehicle-part-row__badge">
        <span class="compatibility-badge compatibility-badge--{{ $part['type'] }}">
            {{ $part['type'] === 'original' ? 'O' : 'Z' }}
        </span>
    </div>
</div>
```

**Deliverables:**
- ❌ 5.3.1 Utworzenie vehicle-part-row.blade.php
- ❌ 5.3.2 Thumbnail display z lazy loading
- ❌ 5.3.3 Placeholder dla brakujących zdjęć
- ❌ 5.3.4 Badge O/Z

### ❌ 5.4 CSS Styling - Vehicle Parts

**Lokalizacja:** Dodać do `resources/css/products/compatibility-tiles.css`

**Klasy:**
```css
.vehicle-part-row { display: flex; align-items: center; gap: 16px; padding: 12px; }
.vehicle-part-row__thumbnail { width: 60px; height: 60px; object-fit: cover; }
.vehicle-part-row__placeholder { background: var(--color-bg-tertiary); }
.vehicle-part-row__sku { font-family: monospace; color: var(--color-text-secondary); }
.vehicle-part-row__name { font-weight: 500; }
.vehicle-part-row__category { font-size: 12px; color: var(--color-text-muted); }
.vehicle-parts-category { border-left: 3px solid var(--ppm-primary); }
.vehicle-parts-category--collapsed { }
```

**Deliverables:**
- ❌ 5.4.1 Part row styles
- ❌ 5.4.2 Category section styles
- ❌ 5.4.3 Responsive adjustments
- ❌ 5.4.4 npm run build + deploy

### ❌ 5.5 Tab Navigation Update dla Vehicle

```blade
@if($product->product_type === 'vehicle')
<button @click="activeTab = 'vehicle_parts'"
        :class="{ 'tab-active': activeTab === 'vehicle_parts' }">
    <x-icon name="puzzle" class="w-4 h-4" />
    Części Zamienne
    <span class="tab-badge">{{ $partsCount }}</span>
</button>
@endif
```

**Deliverables:**
- ❌ 5.5.1 Update tab-navigation.blade.php dla vehicle
- ❌ 5.5.2 Conditional display dla vehicle
- ❌ 5.5.3 Badge z liczbą części

---

## 📋 FAZA 6: SMART SUGGESTIONS (12h)

**Cel:** Implementacja systemu inteligentnych sugestii z confidence scoring.

### ❌ 6.1 Suggestion Generation Job

**Lokalizacja:** `app/Jobs/GenerateCompatibilitySuggestions.php`

**Logika:**
```php
public function handle(SmartSuggestionEngine $engine): void
{
    Product::where('product_type', 'spare_part')
        ->chunk(100, function ($products) use ($engine) {
            foreach ($products as $product) {
                $suggestions = $engine->generateSuggestions($product);
                $engine->cacheSuggestions($product->id, $suggestions);
            }
        });
}
```

**Deliverables:**
- ❌ 6.1.1 Utworzenie Job
- ❌ 6.1.2 Chunk processing (100 at a time)
- ❌ 6.1.3 Cache management
- ❌ 6.1.4 Schedule daily (config/schedule.php)

### ❌ 6.2 Suggestion UI Integration

**W CompatibilityManagement:**
```blade
@if($showSuggestions && $suggestions->isNotEmpty())
<div class="suggestions-section">
    <h4>💡 Sugestie (confidence ≥ {{ $minConfidenceScore }})</h4>
    <div class="vehicle-tiles-grid">
        @foreach($suggestions as $suggestion)
        <button class="vehicle-tile vehicle-tile--suggestion"
                wire:click="applySuggestion({{ $suggestion->id }}, 'original')">
            <span class="vehicle-tile__brand">{{ $suggestion->vehicleModel->brand }}</span>
            <span class="vehicle-tile__model">{{ $suggestion->vehicleModel->model }}</span>
            <span class="vehicle-tile__confidence">⭐ {{ number_format($suggestion->confidence_score, 2) }}</span>
            <span class="vehicle-tile__reason">{{ $suggestion->suggestion_reason }}</span>
        </button>
        @endforeach
    </div>
    <button wire:click="bulkApplySuggestions" class="btn-enterprise-primary">
        ✅ Zastosuj wszystkie sugestie
    </button>
</div>
@endif
```

**Deliverables:**
- ❌ 6.2.1 Suggestions section w blade
- ❌ 6.2.2 Confidence score display
- ❌ 6.2.3 Reason badge (brand_match, name_match)
- ❌ 6.2.4 Bulk apply button

### ❌ 6.3 Suggestion Settings UI

**W Admin Settings / Shop Config:**
```blade
<div class="settings-section">
    <h4>Ustawienia Sugestii Dopasowań</h4>
    <label>
        <input type="checkbox" wire:model="settings.enable_smart_suggestions">
        Włącz inteligentne sugestie
    </label>
    <label>
        <input type="checkbox" wire:model="settings.auto_apply_suggestions">
        Automatycznie aplikuj sugestie (confidence ≥ 0.90)
    </label>
    <label>
        Min. confidence score:
        <input type="range" min="0.50" max="1.00" step="0.05"
               wire:model="settings.min_confidence_score">
        {{ $settings['min_confidence_score'] }}
    </label>
</div>
```

**Deliverables:**
- ❌ 6.3.1 Settings UI dla suggestions
- ❌ 6.3.2 Per-shop settings override
- ❌ 6.3.3 Auto-apply toggle

### ❌ 6.4 Suggestion Styling

**CSS:**
```css
.vehicle-tile--suggestion {
    border-style: dashed;
    border-color: var(--ppm-warning);
    opacity: 0.9;
}
.vehicle-tile--suggestion:hover {
    opacity: 1;
    border-style: solid;
}
.vehicle-tile__confidence {
    font-size: 12px;
    color: var(--ppm-warning);
}
.vehicle-tile__reason {
    font-size: 10px;
    text-transform: uppercase;
    color: var(--color-text-muted);
}
```

**Deliverables:**
- ❌ 6.4.1 Suggestion tile styling (dashed border)
- ❌ 6.4.2 Confidence star display
- ❌ 6.4.3 Reason badge

---

## 📋 FAZA 7: PER-SHOP FILTERING (10h)

**Cel:** Implementacja per-shop brand restrictions i filtering.

### ❌ 7.1 Shop Brand Configuration UI

**Lokalizacja:** Shop edit form lub dedicated settings page

**Layout:**
```
┌────────────────────────────────────────────────────────────────┐
│ Sklep: B2B Test DEV                                             │
├────────────────────────────────────────────────────────────────┤
│ MARKI POJAZDÓW W DOPASOWANIACH                                  │
│                                                                 │
│ Wybierz marki pojazdów dostępne w tym sklepie:                 │
│ ☑ YCF (39 modeli)                                              │
│ ☑ KAYO (39 modeli)                                             │
│ ☑ MRF (30 modeli)                                              │
│ ☐ Honda (15 modeli)                                            │
│ ☐ Yamaha (12 modeli)                                           │
│ ☐ Pitbike Generic (8 modeli)                                   │
│                                                                 │
│ [☑ Wszystkie marki]  [☐ Brak (wyłącz dopasowania)]            │
├────────────────────────────────────────────────────────────────┤
│ [💾 Zapisz ustawienia]                                         │
└────────────────────────────────────────────────────────────────┘
```

**Deliverables:**
- ❌ 7.1.1 Brand selection UI
- ❌ 7.1.2 Shop settings save logic
- ❌ 7.1.3 "All brands" / "None" shortcuts

### ❌ 7.2 Filtering Integration

**W CompatibilityManagement:**
```php
public function switchShopContext(?int $shopId): void
{
    $this->shopContext = $shopId;

    // Get allowed brands for shop
    $allowedBrands = $this->shopFilteringService->getAllowedBrands($shopId);

    // Filter vehicles
    if ($allowedBrands !== null) {
        $this->vehicles = $this->vehicles->filter(
            fn($v) => in_array($v->brand, $allowedBrands)
        );
    }

    // Refresh suggestions with shop context
    $this->loadSuggestions();
}
```

**Deliverables:**
- ❌ 7.2.1 Shop context switching
- ❌ 7.2.2 Brand filtering na vehicle grid
- ❌ 7.2.3 Suggestions filtered by shop
- ❌ 7.2.4 Visual indicator aktywnego filtru

### ❌ 7.3 ProductForm Integration

**Auto-inherit shop context:**
```php
// W ProductForm
public function updatedSelectedShop(?int $shopId): void
{
    // ... existing logic ...

    // Update compatibility tab shop context
    $this->compatibilityShopContext = $shopId;
    $this->loadCompatibilityData();
}
```

**Visual indicator:**
```blade
@if($compatibilityShopContext)
<div class="shop-filter-indicator">
    ℹ️ Filtrowanie dla: {{ $selectedShopName }}
    <span class="text-sm">({{ count($allowedBrands) }} marek)</span>
</div>
@endif
```

**Deliverables:**
- ❌ 7.3.1 Auto shop context z ProductForm
- ❌ 7.3.2 Filter indicator UI
- ❌ 7.3.3 Brand restriction enforcement

### ❌ 7.4 Data Inheritance Logic

**Scenariusze:**
1. `shop_id = NULL` (Dane domyślne) → Pełny dostęp do wszystkich dopasowań
2. `shop_id = X` + `allowed_brands = NULL` → Pełny dostęp (brak restrykcji)
3. `shop_id = X` + `allowed_brands = ['YCF']` → Tylko pojazdy YCF widoczne
4. `shop_id = X` + `allowed_brands = []` → Brak dopasowań (disabled)

**Auto-copy from default:**
```php
// Gdy user zaznacza vehicle w shop context, kopiuj z default jeśli nie istnieje
if ($shopId && !$this->existsForShop($productId, $vehicleId, $shopId)) {
    // Check if exists in default
    $default = VehicleCompatibility::where([...])
        ->whereNull('shop_id')
        ->first();

    if ($default) {
        // Copy to shop-specific with same type
        $this->addCompatibilityForShop($productId, $vehicleId, $default->type, $shopId);
    }
}
```

**Deliverables:**
- ❌ 7.4.1 Inheritance logic implementation
- ❌ 7.4.2 Auto-copy from default
- ❌ 7.4.3 Override detection

---

## 📋 FAZA 8: TESTING & DEPLOYMENT (6h)

**Cel:** Kompleksowe testy, deployment na produkcję, dokumentacja.

### ❌ 8.1 Unit Tests

**Test Cases:**
```php
// SmartSuggestionEngineTest
public function test_generates_suggestions_for_matching_brand(): void
public function test_calculates_correct_confidence_score(): void
public function test_respects_shop_brand_restrictions(): void

// ShopFilteringServiceTest
public function test_filters_vehicles_by_allowed_brands(): void
public function test_returns_all_vehicles_when_no_restrictions(): void
public function test_returns_empty_when_brands_is_empty_array(): void

// CompatibilityBulkServiceTest
public function test_bulk_add_creates_audit_log(): void
public function test_bulk_add_respects_shop_context(): void
```

**Deliverables:**
- ❌ 8.1.1 SmartSuggestionEngine tests
- ❌ 8.1.2 ShopFilteringService tests
- ❌ 8.1.3 CompatibilityBulkService tests
- ❌ 8.1.4 All tests passing

### ❌ 8.2 Integration Tests

**Scenarios:**
1. Full workflow: Open ProductForm → Tab Dopasowania → Add vehicles → Save
2. Per-shop: Switch shop → Verify filtering → Add compatibility → Verify shop_id
3. Suggestions: Generate → Display → Apply → Verify in DB
4. Vehicle view: Open vehicle → Tab Części → Verify grouped display

**Deliverables:**
- ❌ 8.2.1 ProductForm integration test
- ❌ 8.2.2 Per-shop filtering test
- ❌ 8.2.3 Suggestions workflow test
- ❌ 8.2.4 Vehicle parts view test

### ❌ 8.3 Frontend Verification (Chrome DevTools MCP)

**Verification Points:**
1. `/admin/compatibility` - tile grid renders correctly
2. ProductForm spare_part - Dopasowania tab visible
3. ProductForm vehicle - Części Zamienne tab visible
4. Tile selection states (O/Z/both)
5. Collapsible brand sections
6. Responsive grid (mobile/tablet/desktop)
7. Suggestions display
8. Per-shop filtering indicator

**Deliverables:**
- ❌ 8.3.1 Screenshot verification all pages
- ❌ 8.3.2 Console error check
- ❌ 8.3.3 Network request verification
- ❌ 8.3.4 Responsive testing

### ❌ 8.4 Deployment

**Steps:**
1. Database backup
2. Run migrations (FAZA 1)
3. Upload PHP files (services, traits, components)
4. Upload Blade views
5. Upload CSS (npm run build)
6. Upload manifest.json to ROOT
7. Clear cache
8. Verify production

**Deliverables:**
- ❌ 8.4.1 Database backup
- ❌ 8.4.2 Migrations deployed
- ❌ 8.4.3 All PHP files deployed
- ❌ 8.4.4 All Blade views deployed
- ❌ 8.4.5 CSS assets deployed
- ❌ 8.4.6 Cache cleared
- ❌ 8.4.7 Production verification

### ❌ 8.5 Documentation

**Docs to create:**
- `_DOCS/COMPATIBILITY_SYSTEM_GUIDE.md` - User guide
- `_DOCS/COMPATIBILITY_ARCHITECTURE.md` - Technical docs

**Agent Reports:**
- All participating agents must create reports in `_AGENT_REPORTS/`

**Deliverables:**
- ❌ 8.5.1 User guide
- ❌ 8.5.2 Technical documentation
- ❌ 8.5.3 Agent reports
- ❌ 8.5.4 Plan status update → ✅

---

## ✅ COMPLIANCE CHECKLIST

### Context7 Integration
- [ ] Livewire 3.x patterns verified
- [ ] Alpine.js multi-select patterns verified
- [ ] Laravel 12.x service patterns verified

### CSS & Styling (PPM Compliance)
- [ ] NO inline styles
- [ ] CSS classes in compatibility-tiles.css
- [ ] PPM color tokens used
- [ ] Responsive grid (6/4/2 columns)
- [ ] npm run build + manifest.json ROOT upload

### Livewire 3.x Compliance
- [ ] wire:key in ALL @foreach loops
- [ ] dispatch() instead of emit()
- [ ] wire:model.live for reactive inputs
- [ ] @entangle for Alpine.js sync

### SKU First Pattern
- [ ] part_sku populated
- [ ] vehicle_sku populated
- [ ] SKU-based search priority

### Agent Reports (MANDATORY)
- [ ] architect report
- [ ] laravel-expert report
- [ ] livewire-specialist report
- [ ] frontend-specialist report
- [ ] deployment-specialist report

---

## 🤖 AGENT DELEGATION

| Agent | Odpowiedzialność | FAZY |
|-------|------------------|------|
| **architect** | Plan approval, architecture review | Pre-FAZA 1 |
| **laravel-expert** | Services layer, migrations, business logic | FAZA 1, 2 |
| **livewire-specialist** | Components, traits, Livewire integration | FAZA 3, 4, 5, 6 |
| **frontend-specialist** | CSS, tile system, responsive design | FAZA 3, 4, 5 |
| **deployment-specialist** | Production deployment, verification | FAZA 8 |
| **coding-style-agent** | Code review before deployment | Pre-FAZA 8 |

---

## 📊 EXPECTED OUTCOMES

### User Experience
- **Tile-Based UI** - 3x faster selection vs. checkboxes
- **Smart Suggestions** - 60% reduction in manual work
- **Per-Shop Filtering** - Tailored compatibility per store
- **Dual View** - Parts→Vehicles + Vehicles→Parts

### Technical Quality
- **Clean Architecture** - Services layer, traits, reusable components
- **Per-Shop Support** - shop_id in all compatibility queries
- **Audit Trail** - Full logging of bulk operations
- **Performance** - Cached suggestions, eager loading

### Business Impact
- **Efficiency** - Manage 1600+ parts × 121 vehicles efficiently
- **Accuracy** - Confidence scoring reduces errors
- **Flexibility** - Different compatibility per shop

---

**KONIEC ETAP_05d_Produkty_Dopasowania.md (REDESIGN)**

**Data utworzenia:** 2025-12-04
**Data aktualizacji statusów:** 2025-12-19
**Status:** 🛠️ W TRAKCIE - FAZA 0-4.5 ukończone, FAZA 5+ oczekuje
**Estimated completion:** 4-5 dni roboczych (pozostało FAZA 5-8)

**Changelog:**
- 2025-12-05: FAZA 0-2 ukończone (migrations, services)
- 2025-12-08: FAZA 4 ukończona (ProductForm TAB Dopasowania dla części zamiennych)
- 2025-12-09: FAZA 4.5 dodana (Synchronizacja Dopasowań z PrestaShop) - 7 podpunktów
- 2025-12-19: **FAZA 4.5 ukończona** - Dwukierunkowa synchronizacja dopasowań PPM ↔ PrestaShop w pełni działająca
  - Fixed: `findVehicleByFeatureValue()` używa `product_type_id` zamiast `type`
  - Added: `saveFeatureValueMapping()` do zapisywania mapowań podczas eksportu
  - Testy: Full bidirectional cycle verified (PPM → PS → PPM)
