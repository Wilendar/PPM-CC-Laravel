# RAPORT KOORDYNACYJNY: ETAP_05d SEKCJA 0 - Pre-Implementation Analysis

**Data:** 2025-10-24
**Agent:** General Purpose (Coordination)
**Etap:** ETAP_05d - System Zarządzania Dopasowaniami Części Zamiennych
**Faza:** SEKCJA 0 (Pre-Implementation Analysis - 6-8h)
**Status:** ✅ **UKOŃCZONA** - Oczekuje na approval architect

---

## 📊 EXECUTIVE SUMMARY

Przeprowadzono kompleksową analizę pre-implementation dla ETAP_05d (System Zarządzania Dopasowaniami). Wykonano 4 sekcje analizy:

1. ✅ **SEKCJA 0.1:** Analiza obecnego stanu (CompatibilitySelector, routes, database)
2. ✅ **SEKCJA 0.2:** Analiza PrestaShop ps_feature* structure + mapping design
3. ✅ **SEKCJA 0.3:** Architecture Design (bulk edit, SKU first, vehicle cards, filtering)
4. ✅ **SEKCJA 0.4:** Context7 Verification (Livewire 3.x + Laravel 12.x patterns)

**Estymowany czas implementacji:** 86-106h (11-14 dni roboczych sequentially)

**Deployment URL:** https://ppm.mpptrade.pl/admin/compatibility

---

## ✅ SEKCJA 0.1: ANALIZA OBECNEGO STANU

### Co Jest Zrobione (Fundament z ETAP_05a)

**1. CompatibilitySelector Component (Product-Specific)**
- **Lokalizacja:** `app/Http/Livewire/Product/CompatibilitySelector.php` (227 linii)
- **Status:** ✅ DEPLOYED, działający w ProductForm
- **Features:**
  - SKU-first pattern zaimplementowany
  - Live search (marka, model, rok) - 300ms debounce
  - Add/edit/remove compatibility per product
  - Admin-only verification (verified flag)
  - Inline attribute editing (Oryginał/Zamiennik)
- **Reusability:** Patterns mogą być reused w global panel

**2. CompatibilityManager Service**
- **Lokalizacja:** `app/Services/CompatibilityManager.php` (383 linii)
- **Status:** ✅ DEPLOYED
- **Methods:**
  - `getCompatibilityBySku()` - SKU-first lookup
  - `addCompatibility()` - CRUD operations
  - `updateCompatibility()`, `removeCompatibility()`
  - `verifyCompatibility()`, `bulkVerify()`
  - Cache layer integration (CompatibilityCacheService)
- **Missing:** Bulk operations methods (bulkAddCompatibilities, detectDuplicates)

**3. Database Schema**
- ✅ `vehicle_compatibility` - main pivot table
  - Columns: product_id, vehicle_model_id, vehicle_sku (SKU FIRST!), compatibility_attribute_id
  - Indexes: SKU-based indexes dla performance
  - Unique constraint: ONE compatibility per product-vehicle pair
- ✅ `compatibility_attributes` - 3 seeded attributes
  - Current: Original, Replacement, Performance
  - **DECISION:** Zmienić na: Oryginał, Zamiennik, Model (Opcja A)
- ✅ `vehicle_models` - SKU-based vehicle catalog
  - Columns: sku (unique), brand, model, variant, year_from, year_to
- ✅ All migrations executed w ETAP_05a

**4. Route `/admin/compatibility`**
- **Status:** ⚠️ PLACEHOLDER ONLY (linia 386-404 w `routes/web.php`)
- **Current:** Przekierowanie do product edit form + info message
- **Required:** Full CompatibilityManagement component

### Co Wymaga Zrobienia

**KRYTYCZNE ROZBIEŻNOŚCI:**

1. **Compatibility Attributes Seeder - UPDATE REQUIRED:**
   ```php
   // CURRENT (CompatibilityAttributeSeeder.php):
   Original (#4ade80 green)
   Replacement (#3b82f6 blue)
   Performance (#f59e0b amber)

   // REQUIRED (Opcja A wg. planu):
   Oryginał (#10b981 green)
   Zamiennik (#f59e0b orange)
   Model (#3b82f6 blue, auto-generated, is_auto_generated=true)
   ```

2. **Global Compatibility Management Panel - NOT EXISTS:**
   - Route: placeholder funkcja (NOT Livewire component!)
   - Component: `CompatibilityManagement` - NEEDS CREATION
   - Features: parts table, filters, expandable rows, bulk selection

3. **Bulk Operations - MISSING METHODS:**
   - `CompatibilityManager::bulkAddCompatibilities()`
   - `CompatibilityManager::detectDuplicates()`
   - Transaction-safe bulk inserts

4. **PrestaShop Integration - NOT STARTED:**
   - CompatibilityTransformer - NOT EXISTS
   - PrestaShopSyncService updates - NOT EXISTS
   - Verification component - NOT EXISTS

---

## ✅ SEKCJA 0.2: PRESTASHOP ps_feature* STRUCTURE ANALYSIS

### PrestaShop Features System (Context7 verified)

**Tabele (3 główne):**

1. **ps_feature** (product_feature) - Definicja typu cechy
   - `id`: int PK
   - `position`: int (display order)
   - `name`: multilingual (ps_feature_lang: id_lang, name)

2. **ps_feature_value** - Wartości cech (vehicle names)
   - `id`: int PK
   - `id_feature`: FK → ps_feature
   - `custom`: bool
   - `value`: multilingual (max 255 chars!) - ps_feature_value_lang

3. **ps_feature_product** - Pivot (product ↔ feature value)
   - `id_product`: FK → ps_product
   - `id_feature`: FK → ps_feature
   - `id_feature_value`: FK → ps_feature_value
   - **IMPORTANT:** Allows MULTIPLE values per feature (perfect for our use!)

### 🔄 Mapowanie PPM → PrestaShop

**PPM Structure:**
```
compatibility_attributes: [Oryginał, Zamiennik, Model]
vehicle_models: Honda CBR 600 (SKU, brand, model, year range)
vehicle_compatibility: product_id + vehicle_model_id + compatibility_attribute_id
```

**PrestaShop Mapping Strategy:**

**Step 1: Create ps_feature entries (one-time setup):**
```sql
-- ps_feature + ps_feature_lang
INSERT INTO ps_feature (position) VALUES (1); -- Oryginał
INSERT INTO ps_feature_lang (id_feature, id_lang, name) VALUES (1, 1, 'Oryginał');

INSERT INTO ps_feature (position) VALUES (2); -- Zamiennik
INSERT INTO ps_feature_lang (id_feature, id_lang, name) VALUES (2, 1, 'Zamiennik');

INSERT INTO ps_feature (position) VALUES (3); -- Model
INSERT INTO ps_feature_lang (id_feature, id_lang, name) VALUES (3, 1, 'Model');
```

**Step 2: For each vehicle in PPM:**
```php
// Generate vehicle full name (max 255 chars!)
$vehicleName = "{$vehicle->brand} {$vehicle->model} {$vehicle->year_from}-{$vehicle->year_to}";
// Example: "Honda CBR 600 2013-2020"

// Create ps_feature_value
INSERT INTO ps_feature_value (id_feature, custom) VALUES ($featureId, 0);
INSERT INTO ps_feature_value_lang (id_feature_value, id_lang, value) VALUES ($valueId, 1, $vehicleName);

// Cache: vehicle_sku → id_feature_value mapping
```

**Step 3: For each compatibility (PPM → PrestaShop):**
```php
// Part product with compatibilities:
// - Oryginał: Honda CBR 600 2013-2020, Yamaha R1 2015-2019
// - Zamiennik: Kawasaki Ninja 650 2017-2021

// For Oryginał vehicles:
foreach ($originalCompatibilities as $compat) {
    $featureValueId = getOrCreateFeatureValue($compat->vehicle_sku, $featureId=1);
    INSERT INTO ps_feature_product (id_product, id_feature, id_feature_value)
        VALUES ($prestashopProductId, 1, $featureValueId);
}

// For Zamiennik vehicles:
foreach ($replacementCompatibilities as $compat) {
    $featureValueId = getOrCreateFeatureValue($compat->vehicle_sku, $featureId=2);
    INSERT INTO ps_feature_product (id_product, id_feature, id_feature_value)
        VALUES ($prestashopProductId, 2, $featureValueId);
}
```

**Step 4: Model auto-generation:**
```php
// Model = Union(Oryginał, Zamiennik) - no duplicates
$allVehicles = $originalCompatibilities->merge($replacementCompatibilities)->unique('vehicle_sku');

foreach ($allVehicles as $vehicle) {
    $featureValueId = getOrCreateFeatureValue($vehicle->vehicle_sku, $featureId=3);
    INSERT INTO ps_feature_product (id_product, id_feature, id_feature_value)
        VALUES ($prestashopProductId, 3, $featureValueId);
}
```

**KRYTYCZNE ODKRYCIA:**
- ✅ Multi-language support REQUIRED (ps_feature_lang, ps_feature_value_lang)
- ✅ Feature values max **255 chars** (vehicle full name limit!)
- ✅ Model = computed sum of Oryginał + Zamiennik (separate ps_feature_value entries)
- ✅ ps_feature_product allows MULTIPLE values per feature (many vehicles per type)
- ✅ Cache vehicle_sku → id_feature_value mapping (performance optimization)

---

## ✅ SEKCJA 0.3: ARCHITECTURE DESIGN

### 1. Dwukierunkowy Bulk Edit Design

**Component:** `BulkEditCompatibilityModal`

**Kierunki operacji:**
```php
DIRECTION 1: Part → Vehicle
  User selects: 5 parts (checkboxes in CompatibilityManagement)
  User searches: vehicles (SKU/name dual search)
  User selects: 3 vehicles (multi-select checkboxes)
  Result: 15 compatibility records (5 parts × 3 vehicles)

DIRECTION 2: Vehicle → Part
  User selects: 2 vehicles (from VehicleCards)
  User searches: parts (SKU/name dual search)
  User selects: 10 parts (multi-select checkboxes)
  Result: 20 compatibility records (2 vehicles × 10 parts)
```

**Modal Structure (4 sections):**
```blade
1. Selected Source Items (read-only badges)
   - Display: SKUs of selected parts OR vehicles
   - Read-only, cannot modify after modal open

2. Search + Multi-Select Target Items
   - Input: search by SKU or name (debounce 300ms)
   - Results: table with checkboxes (max 50 results)
   - Ranking: SKU exact > SKU partial > Name match
   - Select All checkbox

3. Compatibility Type (radio buttons)
   - Oryginał (green badge)
   - Zamiennik (orange badge)

4. Preview (before apply)
   - Table: Part SKU, Vehicle SKU, Type, Status (New/Duplicate)
   - Stats: "X new compatibilities, Y duplicates"
   - Color-coded: green for new, yellow for duplicate
```

**Logic Flow:**
```php
class BulkEditCompatibilityModal extends Component
{
    public string $direction; // 'part_to_vehicle' | 'vehicle_to_part'
    public array $selectedSourceIds;
    public array $selectedTargetIds = [];
    public string $searchQuery = '';
    public Collection $searchResults;
    public string $compatibilityType = 'original'; // 'original' | 'replacement'
    public ?array $previewData = null;

    // Methods:
    // - mount($direction, $selectedIds): Initialize modal
    // - updatedSearchQuery(): Dual search (SKU + name)
    // - preview(): Generate preview data (new vs duplicate)
    // - apply(): DB::transaction() bulk insert (skip duplicates)
    // - close(): Dispatch 'close-bulk-edit-modal'
}
```

**Transaction Safety:**
```php
public function apply(): void
{
    DB::transaction(function () {
        $compatManager = app(CompatibilityManager::class);

        foreach ($this->selectedSourceIds as $sourceId) {
            foreach ($this->selectedTargetIds as $targetId) {
                // Skip duplicates (check before insert)
                if ($this->isDuplicate($sourceId, $targetId)) continue;

                // Add with SKU backup (SKU FIRST!)
                $compatManager->addCompatibility($part, [
                    'vehicle_model_id' => $targetId,
                    'vehicle_sku' => $vehicle->sku, // MANDATORY!
                    'compatibility_attribute_id' => $attributeId,
                    'compatibility_source_id' => 3, // Manual
                    'verified' => false
                ]);
            }
        }
    }, attempts: 5); // Deadlock retry!
}
```

### 2. SKU First + Name Search Logic

**Search Strategy (Dual Search with Ranking):**
```php
public function dualSearch(string $query, string $entityType = 'vehicle'): Collection
{
    if (strlen($query) < 2) {
        return collect();
    }

    // PRIMARY: Exact SKU match (highest priority)
    $skuExact = $entityType === 'vehicle'
        ? VehicleModel::where('sku', $query)->get()
        : Product::where('sku', $query)->get();

    // SECONDARY: Partial SKU match (LIKE 'query%')
    $skuPartial = $entityType === 'vehicle'
        ? VehicleModel::where('sku', 'LIKE', "{$query}%")->limit(20)->get()
        : Product::where('sku', 'LIKE', "{$query}%")->limit(20)->get();

    // TERTIARY: Name match (LIKE '%query%')
    $nameMatch = $entityType === 'vehicle'
        ? VehicleModel::where('model', 'LIKE', "%{$query}%")
            ->orWhere('brand', 'LIKE', "%{$query}%")
            ->limit(30)->get()
        : Product::where('name', 'LIKE', "%{$query}%")->limit(30)->get();

    // Merge + unique + rank (SKU exact first, then partial, then name)
    return $skuExact
        ->merge($skuPartial)
        ->merge($nameMatch)
        ->unique('id')
        ->take(50); // Limit final results
}
```

**Display with Ranking Badges:**
```blade
@foreach($searchResults as $item)
  <div class="search-result-item">
    @if($item->matchType === 'sku_exact')
      <span class="badge badge-green">SKU Match</span>
    @elseif($item->matchType === 'sku_partial')
      <span class="badge badge-blue">SKU Partial</span>
    @else
      <span class="badge badge-gray">Name Match</span>
    @endif

    <strong>{{ $item->sku }}</strong> - {{ $item->name }}
  </div>
@endforeach
```

### 3. Vehicle Cards Architecture

**Component:** `VehicleCompatibilityCards`

**Grid Layout (Responsive):**
```css
.vehicle-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    grid-template-columns: repeat(4, 1fr); /* 4 columns desktop */
}

@media (min-width: 768px) and (max-width: 1023px) {
    grid-template-columns: repeat(3, 1fr); /* 3 columns tablet */
}

@media (max-width: 767px) {
    grid-template-columns: 1fr; /* 1 column mobile */
}
```

**Computed Properties (Livewire 3.x):**
```php
use Livewire\Attributes\Computed;

#[Computed]
public function vehicles(): Collection
{
    $query = VehicleModel::query()
        ->withCount([
            'compatibilities as original_parts_count' => function ($q) {
                $q->whereHas('compatibilityAttribute',
                    fn($qq) => $qq->where('code', 'original'));
            },
            'compatibilities as replacement_parts_count' => function ($q) {
                $q->whereHas('compatibilityAttribute',
                    fn($qq) => $qq->where('code', 'replacement'));
            }
        ]);

    if ($this->filterBrand) {
        $query->where('brand', $this->filterBrand);
    }

    if ($this->sortBy === 'parts_count') {
        $query->orderByRaw('(original_parts_count + replacement_parts_count) DESC');
    } else {
        $query->orderBy('brand')->orderBy('model');
    }

    return $query->get();
}

#[Computed]
public function brands(): Collection
{
    return VehicleModel::distinct('brand')->pluck('brand')->sort();
}
```

**Card Structure:**
```blade
<div class="vehicle-card" wire:click="openDetail({{ $vehicle->id }})">
  <!-- Image (lazy loading) -->
  <div class="vehicle-card-image">
    @if($vehicle->main_image_path)
      <img src="{{ asset('storage/' . $vehicle->main_image_path) }}"
           alt="{{ $vehicle->getFullName() }}"
           loading="lazy" />
    @else
      <div class="placeholder-image">
        <svg><!-- Car icon --></svg>
        <span>No Image</span>
      </div>
    @endif
  </div>

  <!-- Header -->
  <div class="vehicle-card-header">
    <span class="badge badge-brand">{{ $vehicle->brand }}</span>
    <h3>{{ $vehicle->model }}</h3>
  </div>

  <!-- Body (Parts Count) -->
  <div class="vehicle-card-body">
    <p class="sku">SKU: {{ $vehicle->sku }}</p>
    <div class="parts-count">
      <span class="badge badge-green">Oryginał: {{ $vehicle->original_parts_count }}</span>
      <span class="badge badge-orange">Zamiennik: {{ $vehicle->replacement_parts_count }}</span>
    </div>
  </div>

  <!-- Footer -->
  <div class="vehicle-card-footer">
    <button>Zobacz szczegóły</button>
  </div>
</div>
```

### 4. Per-Shop Brand Filtering

**Database Migration:**
```php
Schema::table('prestashop_shops', function (Blueprint $table) {
    $table->json('shop_vehicle_brands')->nullable()->after('api_key');
});
```

**PrestashopShop Model Updates:**
```php
protected $casts = [
    'shop_vehicle_brands' => 'array',
];

protected $fillable = [
    'shop_vehicle_brands',
];

public function getVehicleBrandsAttribute(): ?array
{
    return $this->attributes['shop_vehicle_brands']
        ? json_decode($this->attributes['shop_vehicle_brands'], true)
        : null;
}
```

**CompatibilityManager Filtering Method:**
```php
public function filterVehiclesByShop(?int $shopId, $vehicleQuery)
{
    if (!$shopId) {
        return $vehicleQuery; // No filter
    }

    $shop = PrestashopShop::find($shopId);

    if (!$shop || !$shop->vehicle_brands) {
        return $vehicleQuery; // No brands configured, show all
    }

    // Filter by configured brands
    return $vehicleQuery->whereIn('brand', $shop->vehicle_brands);
}
```

---

## ✅ SEKCJA 0.4: CONTEXT7 VERIFICATION

**Zweryfikowano zaprojektowane komponenty względem oficjalnych patterns:**

### Livewire 3.x Patterns Verification ✅

**1. Computed Properties:**
```php
// ✅ CORRECT (Context7 verified - /livewire/livewire)
use Livewire\Attributes\Computed;

#[Computed]
public function vehicles(): Collection
{
    return VehicleModel::withCount('compatibilities')->get();
}

// ❌ OLD (Livewire 2.x style)
public function getVehiclesProperty() { }
```

**2. Event Dispatching:**
```php
// ✅ CORRECT (Livewire 3.x API)
$this->dispatch('compatibility-added', ['message' => 'Success']);

// ❌ OLD (Livewire 2.x)
$this->emit('compatibility-added');
```

**3. Lazy Loading:**
```blade
{{-- ✅ CORRECT - on-load lazy --}}
<livewire:admin.compatibility.vehicle-cards lazy="on-load" />

{{-- ✅ CORRECT - viewport lazy --}}
<livewire:admin.compatibility.compatibility-management lazy />
```

**4. wire:loading Targeting:**
```blade
{{-- ✅ CORRECT - target specific property --}}
<input wire:model.live="searchQuery" />
<div wire:loading wire:target="searchQuery">Searching...</div>
```

**5. wire:key (MANDATORY!):**
```blade
{{-- ✅ CORRECT --}}
@foreach($items as $item)
  <div wire:key="item-{{ $item->id }}">{{ $item->name }}</div>
@endforeach

{{-- ❌ WRONG - missing wire:key --}}
@foreach($items as $item)
  <div>{{ $item->name }}</div>
@endforeach
```

### Laravel 12.x Patterns Verification ✅

**1. Database Transactions with Retry:**
```php
// ✅ CORRECT (Context7 verified - /websites/laravel_12_x)
DB::transaction(function () {
    // Bulk insert compatibilities
    foreach ($compatibilities as $compat) {
        VehicleCompatibility::create($compat);
    }
}, attempts: 5); // Retry on deadlock!

// ❌ WRONG - no retry on deadlock
DB::transaction(function () { });
```

**2. Batch Processing (chunkById for updates):**
```php
// ✅ CORRECT - use chunkById when updating records in loop
VehicleCompatibility::where('verified', false)
    ->chunkById(100, function (Collection $compatibilities) {
        foreach ($compatibilities as $compat) {
            $compat->update(['vehicle_sku' => $compat->vehicleModel->sku]);
        }
    });

// ❌ WRONG - chunk() can skip records if updating filtered column
VehicleCompatibility::chunk(100, function ($compatibilities) {
    foreach ($compatibilities as $compat) {
        $compat->update(['verified' => true]); // PROBLEM!
    }
});
```

**3. Service Layer Dependency Injection:**
```php
// ✅ CORRECT - use app() helper in Livewire to avoid DI conflict
class BulkEditCompatibilityModal extends Component
{
    public function apply(): void
    {
        $compatManager = app(CompatibilityManager::class);
        $compatManager->bulkAddCompatibilities(...);
    }
}

// ❌ WRONG - constructor injection causes Livewire DI conflict
class BulkEditCompatibilityModal extends Component
{
    public function __construct(
        public CompatibilityManager $compatManager
    ) {}
}
```

### Architecture Design Updates (Post-Verification)

**Changes po Context7 verification:**

1. ✅ Dodać `#[Computed]` attribute do wszystkich computed properties
2. ✅ Zmienić `chunk()` na `chunkById()` w bulk operations when updating
3. ✅ Dodać `attempts: 5` do `DB::transaction()` (deadlock resilience)
4. ✅ Używać `app()` helper dla DI w Livewire (avoid constructor injection)

**Wszystkie pozostałe patterns są ZGODNE z oficjalnymi dokumentacjami!**

---

## 📋 AGENT DELEGATION PLAN

### Agent Assignment Matrix

| Faza | Agent Główny | Agent Wsparcia | Deliverables | Estymacja |
|------|--------------|----------------|--------------|-----------|
| **SEKCJA 0** | architect | documentation-reader | Plan approval, architecture review | 2h |
| **FAZA 1** | livewire-specialist | frontend-specialist | CompatibilityManagement component + CSS | 15-18h |
| **FAZA 2** | livewire-specialist | laravel-expert | BulkEditCompatibilityModal + service methods | 15-18h |
| **FAZA 3** | livewire-specialist | - | Oryginał/Zamiennik/Model labels | 10-12h |
| **FAZA 4** | livewire-specialist | frontend-specialist | VehicleCompatibilityCards + images | 8-10h |
| **FAZA 5** | laravel-expert | livewire-specialist | Per-shop brand filtering | 8-10h |
| **FAZA 6** | livewire-specialist | - | ProductForm integration (tabs) | 8-10h |
| **FAZA 7** | prestashop-api-expert | laravel-expert | PrestaShop sync + verification | 10-12h |
| **FAZA 8** | deployment-specialist | coding-style-agent | Production deployment + verification | 6-8h |

**TOTAL:** 86-106h (11-14 dni roboczych sequential)

### Detailed Agent Responsibilities

**architect** (SEKCJA 0 + Final Review):
- ⏳ PENDING: Review pre-implementation analysis report (ten dokument)
- ⏳ PENDING: Approve architecture design decisions
- ⏳ PENDING: Verify PrestaShop mapping strategy
- ⏳ PENDING: Approve compatibility attributes update (Oryginał/Zamiennik/Model)
- ⏳ PENDING: Final sign-off przed rozpoczęciem implementacji

**livewire-specialist** (FAZY 1, 2, 3, 4, 6):
- Create 6+ Livewire components (~1800 linii total)
- Implement dwukierunkowy bulk edit logic
- Build labels system (Oryginał/Zamiennik/Model)
- Develop vehicle cards component + lazy loading
- Integrate conditional tabs w ProductForm
- **Skills:** livewire-troubleshooting, context7-docs-lookup, agent-report-writer

**frontend-specialist** (FAZY 1, 4):
- CSS styling dla compatibility management panel (~200 linii)
- Vehicle cards grid layout + responsive design (~150 linii)
- Image optimization (lazy loading, placeholders)
- Modal styles (bulk edit modal, vehicle detail modal)
- **Skills:** frontend-verification (MANDATORY!), agent-report-writer

**laravel-expert** (FAZY 2, 5, 7):
- CompatibilityManager bulk methods (bulkAddCompatibilities, detectDuplicates)
- Per-shop brand filtering logic + migration
- Database migration: shop_vehicle_brands column
- Batch processing optimization (chunkById, transactions)
- **Skills:** context7-docs-lookup (MANDATORY!), agent-report-writer

**prestashop-api-expert** (FAZA 7):
- CompatibilityTransformer (PPM → ps_feature* format)
- PrestaShopSyncService updates
- Multi-language support (ps_feature_lang, ps_feature_value_lang)
- Batch sync processing (100 products per batch)
- Verification system (compare PPM vs PrestaShop discrepancies)
- **Skills:** context7-docs-lookup, agent-report-writer

**deployment-specialist** (FAZA 8):
- Upload migrations + components + CSS (pscp)
- Run migrations na produkcji (plink)
- Clear cache (view, config, route, cache)
- Production testing (all workflows)
- **Skills:** hostido-deployment (PRIMARY!), frontend-verification (MANDATORY!), agent-report-writer

**coding-style-agent** (FAZA 8 pre-deployment):
- Code review ALL components przed deployment
- CLAUDE.md compliance check (max 300 linii per file!)
- Context7 patterns verification
- SKU first pattern verification (CRITICAL!)
- **Skills:** agent-report-writer (MANDATORY!)

---

## 📊 DELIVERABLES SUMMARY

### Database Changes

**1. Compatibility Attributes Seeder Update:**
```php
// OLD (current):
['name' => 'Original', 'code' => 'original', 'color' => '#4ade80', 'position' => 1]
['name' => 'Replacement', 'code' => 'replacement', 'color' => '#3b82f6', 'position' => 2]
['name' => 'Performance', 'code' => 'performance', 'color' => '#f59e0b', 'position' => 3]

// NEW (required):
['name' => 'Oryginał', 'code' => 'original', 'color' => '#10b981', 'position' => 1]
['name' => 'Zamiennik', 'code' => 'replacement', 'color' => '#f59e0b', 'position' => 2]
['name' => 'Model', 'code' => 'model', 'color' => '#3b82f6', 'position' => 3, 'is_auto_generated' => true]
```

**2. New Migration:**
```php
// Migration: add shop_vehicle_brands to prestashop_shops
Schema::table('prestashop_shops', function (Blueprint $table) {
    $table->json('shop_vehicle_brands')->nullable()->after('api_key');
});
```

### Components to Create (10 components)

**FAZA 1:**
- `app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php` (~350 linii)
- `resources/views/livewire/admin/compatibility/compatibility-management.blade.php` (~250 linii)

**FAZA 2:**
- `app/Http/Livewire/Admin/Compatibility/BulkEditCompatibilityModal.php` (~300 linii)
- `resources/views/livewire/admin/compatibility/bulk-edit-compatibility-modal.blade.php` (~200 linii)

**FAZA 3:**
- Updates to CompatibilityManagement blade (expandable rows with labels)

**FAZA 4:**
- `app/Http/Livewire/Admin/Compatibility/VehicleCompatibilityCards.php` (~200 linii)
- `resources/views/livewire/admin/compatibility/vehicle-compatibility-cards.blade.php` (~150 linii)
- `app/Http/Livewire/Admin/Compatibility/VehicleDetailModal.php` (~150 linii)
- `resources/views/livewire/admin/compatibility/vehicle-detail-modal.blade.php` (~120 linii)

**FAZA 5:**
- Updates to PrestashopShop model
- Updates to CompatibilityManager service

**FAZA 6:**
- Updates to ProductForm component (conditional tabs)

**FAZA 7:**
- `app/Services/PrestaShop/Transformers/CompatibilityTransformer.php` (~250 linii)
- `app/Http/Livewire/Admin/Compatibility/CompatibilityVerification.php` (~250 linii)
- `resources/views/livewire/admin/compatibility/compatibility-verification.blade.php` (~180 linii)
- Updates to PrestaShopSyncService

**CSS:**
- `resources/css/admin/components.css` - add ~400 linii (compatibility management styles)

### Routes to Update

```php
// OLD (placeholder):
Route::get('/admin/compatibility', function () {
    return view('placeholder-page', [...]);
})->name('compatibility.index');

// NEW (Livewire component):
Route::get('/admin/compatibility', \App\Http\Livewire\Admin\Compatibility\CompatibilityManagement::class)
    ->name('admin.compatibility.index');
```

---

## ⚠️ KLUCZOWE RYZYKA I MITYGACJE

### Ryzyko 1: PrestaShop ps_feature* Multi-Language Complexity
**Opis:** PrestaShop wymaga multilingual entries dla features i values
**Mitigation:** Start z pojedynczym językiem (Polish), rozszerzyć później
**Owner:** prestashop-api-expert

### Ryzyko 2: Vehicle Names > 255 Chars (ps_feature_value limit)
**Opis:** PrestaShop ps_feature_value.value ma limit 255 chars
**Mitigation:** Truncate lub abbreviate długie nazwy pojazdów
**Owner:** prestashop-api-expert

### Ryzyko 3: Performance - Bulk Operations na Dużych Zbiorach
**Opis:** 1000+ parts × 100+ vehicles = 100k+ records możliwe
**Mitigation:** Batch processing (chunkById 100), DB transactions with retry
**Owner:** laravel-expert

### Ryzyko 4: Compatibility Attributes Migration - Production Data
**Opis:** Zmiana seedera może zerwać istniejące compatibility records
**Mitigation:** Migration z mapowaniem starych wartości do nowych (Original→Oryginał)
**Owner:** laravel-expert

### Ryzyko 5: Frontend Performance - Vehicle Cards Images
**Opis:** 500+ vehicle cards z obrazami = wolne ładowanie
**Mitigation:** Lazy loading (viewport), thumbnails (400x300px), CDN (future)
**Owner:** frontend-specialist

---

## 📈 SUCCESS CRITERIA

**SEKCJA 0 Success Criteria (✅ COMPLETED):**
- [x] Analiza obecnego stanu dokumentowana
- [x] PrestaShop ps_feature* mapping zaprojektowane
- [x] Architecture design wszystkich 4 kluczowych komponentów
- [x] Context7 verification (Livewire 3.x + Laravel 12.x)
- [x] Agent delegation plan stworzony

**FAZY 1-8 Success Criteria (PENDING architect approval):**
- [ ] All 10 Livewire components created + tested
- [ ] CSS styles compliant (NO inline styles!)
- [ ] Database migrations executed (local + production)
- [ ] PrestaShop sync tested (staging database)
- [ ] Frontend verification (screenshots) dla wszystkich UI components
- [ ] Production deployment verified (all workflows)
- [ ] Code review completed (coding-style-agent)
- [ ] Agent reports created (all agents)

---

## 🎯 NASTĘPNE KROKI

**IMMEDIATE (PENDING architect approval):**
1. ⏳ **architect:** Review tego raportu SEKCJA 0
2. ⏳ **architect:** Approve architecture design + PrestaShop mapping
3. ⏳ **architect:** Approve compatibility attributes update (Oryginał/Zamiennik/Model)

**AFTER APPROVAL:**
4. 🚀 **laravel-expert:** Update CompatibilityAttributeSeeder (Oryginał/Zamiennik/Model)
5. 🚀 **livewire-specialist:** Start FAZA 1 (CompatibilityManagement component)
6. 🚀 Continue FAZY 2-8 sequential per agent assignments

---

## 📎 REFERENCES

- **Plan Projektu:** `Plan_Projektu/ETAP_05d_Produkty_Dopasowania.md`
- **SKU Architecture Guide:** `_DOCS/SKU_ARCHITECTURE_GUIDE.md`
- **Context7 Integration:** `_DOCS/CONTEXT7_INTEGRATION_GUIDE.md`
- **Agent Usage Guide:** `_DOCS/AGENT_USAGE_GUIDE.md`
- **PrestaShop Product DB:** `References/Prestashop_Product_DB.csv`
- **Existing Component:** `app/Http/Livewire/Product/CompatibilitySelector.php`
- **Existing Service:** `app/Services/CompatibilityManager.php`

---

**KONIEC RAPORTU SEKCJA 0**

**Data utworzenia:** 2025-10-24
**Status:** ✅ UKOŃCZONA - Oczekuje na approval architect
**Następny krok:** architect review + approval → Start FAZA 1
