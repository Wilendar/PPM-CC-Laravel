# 🏷️ ETAP_05a: System Cech Produktów (Product Features)

**Status ETAPU:** ❌ **NIE ROZPOCZĘTY**
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 32-40 godzin (4-5 dni roboczych)
**Postęp:** 0%
**Zależności:** ETAP_02 (modele ✅), ETAP_04 (panel admin ✅)
**Dependency dla:** ETAP_05b (Warianty 63% complete), ETAP_05d (Dopasowania)

**Data utworzenia planu:** 2025-12-05
**Ostatnia aktualizacja:** 2025-12-05

---

## 📋 EXECUTIVE SUMMARY

### 🎯 Cel Etapu

Implementacja systemu cech produktów (features/attributes) w PPM-CC-Laravel zgodnie z architekturą PrestaShop ps_feature* tables. System umożliwia zarządzanie cechami produktów (np. kolor, rozmiar, materiał, waga) z pełnym wsparciem dla:
- Multiple feature types (tekstowe, numeryczne, boolean, select, multi-select)
- Feature groups dla organizacji
- Per-shop feature values (różne wartości per sklep)
- Synchronizacja z PrestaShop ps_feature, ps_feature_value
- Import/Export cech z Excel

**⚠️ WAŻNE:** Ten etap NIE obejmuje cech pojazdów (vehicle info) - to jest część ETAP_05d (Dopasowania).

### 🔑 Kluczowe Różnice: Features vs Attributes

| Aspekt | Features (ETAP_05a) | Attributes (ETAP_05b - Warianty) |
|--------|---------------------|-----------------------------------|
| **Cel** | Cechy opisowe produktu | Opcje tworzące warianty |
| **Przykłady** | Materiał, Waga, Producent | Rozmiar, Kolor, Wykończenie |
| **PrestaShop** | ps_feature* | ps_attribute* |
| **SKU** | Nie wpływa na SKU | Tworzy nowy SKU wariantu |
| **Stock** | Bez wpływu | Osobny stan per wariant |
| **Price** | Bez wpływu | Różna cena per wariant |

### 📈 Business Value

- **Flexibility:** Nieograniczona liczba cech per produkt
- **Organization:** Grouping cech dla lepszej struktury
- **Multi-Store:** Różne wartości cech per sklep PrestaShop
- **PrestaShop Compatible:** Pełna zgodność z ps_feature* architecture
- **Excel Integration:** Import/Export cech z arkuszy

---

## 📊 ARCHITECTURE OVERVIEW

### Database Schema

```sql
-- EXISTING TABLES (z ETAP_02)
feature_types
├── id (PK)
├── name (VARCHAR)
├── code (VARCHAR UNIQUE)
├── input_type (ENUM: text, number, boolean, select, multiselect)
├── options (JSON)
├── is_active
├── created_at, updated_at

product_features (PIVOT)
├── id (PK)
├── product_id (FK → products)
├── feature_type_id (FK → feature_types)
├── feature_value (TEXT)
├── shop_id (FK → prestashop_shops) NULLABLE
├── sort_order
├── created_at, updated_at
└── UNIQUE (product_id, feature_type_id, shop_id)

-- PLANNED ENHANCEMENTS (w tym etapie)
feature_groups
├── id (PK)
├── name (VARCHAR)
├── code (VARCHAR UNIQUE)
├── description (TEXT)
├── sort_order
├── is_active
├── created_at, updated_at

feature_type_prestashop_mappings
├── id (PK)
├── feature_type_id (FK → feature_types)
├── shop_id (FK → prestashop_shops)
├── prestashop_feature_id (INT)
├── sync_status (ENUM: synced, pending, error)
├── last_sync_at
├── created_at, updated_at
└── UNIQUE (feature_type_id, shop_id)
```

### Service Layer Architecture

```
Services/Features/
├── FeatureManager.php (~280 linii)
│   ├── getProductFeatures()
│   ├── addProductFeature()
│   ├── updateProductFeature()
│   ├── removeProductFeature()
│   └── bulkAssignFeatures()
│
├── FeatureGroupService.php (~150 linii)
│   ├── getGroups()
│   ├── assignFeatureToGroup()
│   └── getGroupedFeatures()
│
├── FeatureTypeService.php (~200 linii)
│   ├── getActiveTypes()
│   ├── validateFeatureValue()
│   └── formatValueForDisplay()
│
└── FeatureExcelService.php (~250 linii)
    ├── importFromExcel()
    ├── exportToExcel()
    └── mapExcelColumns()
```

### UI Component Architecture

```
Livewire/Products/
├── Management/
│   ├── ProductForm.php (zakładka "Cechy")
│   └── Traits/
│       └── ProductFormFeatures.php (~300 linii)
│
└── Features/
    ├── FeatureTypeManager.php (~250 linii)
    ├── FeatureGroupManager.php (~180 linii)
    └── BulkFeatureAssignment.php (~220 linii)
```

---

## 📋 PLAN RAMOWY ETAPU

| FAZA | Nazwa | Czas | Status |
|------|-------|------|--------|
| **FAZA 1** | Database Layer & Models | 6-8h | ❌ |
| **FAZA 2** | Services Layer | 8-10h | ❌ |
| **FAZA 3** | ProductForm Feature Tab | 8-10h | ❌ |
| **FAZA 4** | Feature Management UI | 6-8h | ❌ |
| **FAZA 5** | Excel Import/Export | 4-6h | ❌ |
| **TOTAL** | | **32-42h** | **0%** |

---

## 📋 FAZA 1: DATABASE LAYER & MODELS (6-8h)

**Cel:** Rozszerzenie schematu bazy danych o feature groups, PrestaShop mappings, per-shop support.

### ❌ 1.1 Migration: Feature Groups Table (2h)

```php
// create_feature_groups_table.php
Schema::create('feature_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('code', 50)->unique();
    $table->text('description')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('code');
    $table->index(['is_active', 'sort_order']);
});
```

**Deliverables:**
- ❌ 1.1.1 Utworzenie migracji
- ❌ 1.1.2 Seedery dla podstawowych grup ("Ogólne", "Techniczne", "Wymiary")
- ❌ 1.1.3 Test migracji lokalnie
- ❌ 1.1.4 Deploy na produkcję

### ❌ 1.2 Migration: Feature Type Groups Assignment (2h)

```php
// add_group_id_to_feature_types.php
Schema::table('feature_types', function (Blueprint $table) {
    $table->foreignId('feature_group_id')
          ->nullable()
          ->after('code')
          ->constrained('feature_groups')
          ->nullOnDelete();

    $table->index('feature_group_id');
});
```

**Deliverables:**
- ❌ 1.2.1 Utworzenie migracji
- ❌ 1.2.2 Default group assignment dla istniejących feature types
- ❌ 1.2.3 Test lokalnie
- ❌ 1.2.4 Deploy na produkcję

### ❌ 1.3 Migration: PrestaShop Feature Mappings (2h)

```php
// create_feature_type_prestashop_mappings.php
Schema::create('feature_type_prestashop_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('feature_type_id')->constrained()->cascadeOnDelete();
    $table->foreignId('shop_id')->constrained('prestashop_shops')->cascadeOnDelete();
    $table->integer('prestashop_feature_id');
    $table->enum('sync_status', ['synced', 'pending', 'error'])->default('pending');
    $table->timestamp('last_sync_at')->nullable();
    $table->json('sync_errors')->nullable();
    $table->timestamps();

    $table->unique(['feature_type_id', 'shop_id'], 'uniq_feature_shop');
    $table->index(['shop_id', 'sync_status']);
    $table->index('prestashop_feature_id');
});
```

**Deliverables:**
- ❌ 1.3.1 Utworzenie migracji
- ❌ 1.3.2 Test lokalnie
- ❌ 1.3.3 Deploy na produkcję

### ❌ 1.4 Models & Relations (2-4h)

**FeatureGroup Model:**
```php
class FeatureGroup extends Model
{
    // Relations
    public function featureTypes(): HasMany
    public function scopeActive(Builder $query): Builder
    public function scopeOrdered(Builder $query): Builder

    // Helper methods
    public function getTypesCount(): int
}
```

**FeatureType Model Extensions:**
```php
// Add relations
public function featureGroup(): BelongsTo
public function prestaShopMappings(): HasMany
public function getSyncStatusForShop(int $shopId): ?string
```

**FeatureTypePrestashopMapping Model:**
```php
class FeatureTypePrestashopMapping extends Model
{
    public function featureType(): BelongsTo
    public function shop(): BelongsTo

    public function markAsSynced(): void
    public function markAsError(array $errors): void
}
```

**Deliverables:**
- ❌ 1.4.1 FeatureGroup model creation
- ❌ 1.4.2 FeatureType model extensions
- ❌ 1.4.3 FeatureTypePrestashopMapping model
- ❌ 1.4.4 Unit tests dla relations

---

## 📋 FAZA 2: SERVICES LAYER (8-10h)

**Cel:** Implementacja business logic dla feature management.

### ❌ 2.1 FeatureManager Service (4h)

**Lokalizacja:** `app/Services/Features/FeatureManager.php`

**Główne metody:**

```php
class FeatureManager
{
    /**
     * Get features dla produktu (z shop context)
     */
    public function getProductFeatures(
        int $productId,
        ?int $shopId = null
    ): Collection;

    /**
     * Add feature do produktu
     */
    public function addProductFeature(
        int $productId,
        int $featureTypeId,
        string $value,
        ?int $shopId = null
    ): ProductFeature;

    /**
     * Update feature value
     */
    public function updateProductFeature(
        int $productFeatureId,
        string $newValue
    ): bool;

    /**
     * Remove feature from product
     */
    public function removeProductFeature(
        int $productFeatureId
    ): bool;

    /**
     * Bulk assign features (Excel import workflow)
     */
    public function bulkAssignFeatures(
        array $productIds,
        array $featuresData,
        ?int $shopId = null
    ): int;

    /**
     * Copy features from product to product
     */
    public function copyFeatures(
        int $sourceProductId,
        int $targetProductId,
        ?int $shopId = null
    ): int;

    /**
     * Get feature value dla display
     */
    public function formatFeatureForDisplay(
        ProductFeature $feature
    ): string;
}
```

**Business Logic:**
- Shop context handling (shop_id = NULL → dane domyślne)
- Value validation przez FeatureTypeService
- Duplicate detection (product + feature_type + shop)
- Sort order management

**Deliverables:**
- ❌ 2.1.1 Utworzenie FeatureManager
- ❌ 2.1.2 CRUD operations implementation
- ❌ 2.1.3 Shop context support
- ❌ 2.1.4 Unit tests

### ❌ 2.2 FeatureGroupService (2h)

**Lokalizacja:** `app/Services/Features/FeatureGroupService.php`

**Główne metody:**

```php
class FeatureGroupService
{
    public function getActiveGroups(): Collection;
    public function getGroupedFeatureTypes(): Collection;
    public function assignFeatureToGroup(int $featureTypeId, int $groupId): bool;
    public function updateGroupSortOrder(array $groupOrder): bool;
}
```

**Deliverables:**
- ❌ 2.2.1 Utworzenie FeatureGroupService
- ❌ 2.2.2 Group management logic
- ❌ 2.2.3 Unit tests

### ❌ 2.3 FeatureTypeService (2h)

**Lokalizacja:** `app/Services/Features/FeatureTypeService.php`

**Główne metody:**

```php
class FeatureTypeService
{
    /**
     * Validate value zgodnie z input_type
     */
    public function validateValue(FeatureType $type, mixed $value): bool;

    /**
     * Format value dla storage
     */
    public function formatForStorage(FeatureType $type, mixed $value): string;

    /**
     * Format value dla display
     */
    public function formatForDisplay(FeatureType $type, string $value): string;

    /**
     * Get options dla select/multiselect
     */
    public function getTypeOptions(FeatureType $type): ?array;
}
```

**Validation Logic:**
```
text: max 500 chars
number: numeric check
boolean: true/false/1/0
select: value in options
multiselect: all values in options (JSON array)
```

**Deliverables:**
- ❌ 2.3.1 Utworzenie FeatureTypeService
- ❌ 2.3.2 Validation logic per input_type
- ❌ 2.3.3 Format methods
- ❌ 2.3.4 Unit tests

### ❌ 2.4 FeatureExcelService (4h)

**Lokalizacja:** `app/Services/Features/FeatureExcelService.php`

**Główne metody:**

```php
class FeatureExcelService
{
    /**
     * Import features z Excel
     * Column mapping: SKU | Feature1 | Feature2 | ... | FeatureN
     */
    public function importFromExcel(
        string $filePath,
        array $columnMapping,
        ?int $shopId = null
    ): array; // ['imported' => 123, 'errors' => [...]]

    /**
     * Export features to Excel
     */
    public function exportToExcel(
        array $productIds,
        ?int $shopId = null
    ): string; // file path

    /**
     * Auto-detect feature columns w Excel
     */
    public function detectFeatureColumns(string $filePath): array;
}
```

**Excel Format:**
```
| SKU | Materiał | Waga (kg) | Kolor | Producent |
|-----|----------|-----------|-------|-----------|
| P-001 | Aluminium | 2.5 | Czerwony | YCF |
| P-002 | Stal | 3.2 | Czarny | Honda |
```

**Deliverables:**
- ❌ 2.4.1 Utworzenie FeatureExcelService
- ❌ 2.4.2 Import logic z column mapping
- ❌ 2.4.3 Export logic
- ❌ 2.4.4 Column detection algorithm
- ❌ 2.4.5 Integration tests

---

## 📋 FAZA 3: PRODUCTFORM FEATURE TAB (8-10h)

**Cel:** Zakładka "Cechy" w ProductForm dla zarządzania cechami produktu.

### ❌ 3.1 ProductFormFeatures Trait (5h)

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/ProductFormFeatures.php`

**Properties:**
```php
public Collection $productFeatures;      // Current features
public Collection $availableFeatureTypes; // All feature types
public ?int $featureShopContext = null;  // Per-shop context
public string $featureSearch = '';       // Filter feature types
public ?int $selectedFeatureGroupId = null; // Filter by group
```

**Methods:**
```php
public function loadProductFeatures(): void;
public function loadAvailableFeatureTypes(): void;
public function addFeature(int $featureTypeId, string $value): void;
public function updateFeatureValue(int $featureId, string $newValue): void;
public function removeFeature(int $featureId): void;
public function copyFeaturesFromProduct(int $sourceProductId): void;
```

**Deliverables:**
- ❌ 3.1.1 Utworzenie ProductFormFeatures trait
- ❌ 3.1.2 Feature CRUD methods
- ❌ 3.1.3 Shop context handling
- ❌ 3.1.4 Integration z ProductForm component

### ❌ 3.2 Blade View - Features Tab (5h)

**Lokalizacja:** `resources/views/livewire/products/management/tabs/features-tab.blade.php`

**Layout:**
```
┌────────────────────────────────────────────────────────────────┐
│ TAB: CECHY PRODUKTU                                             │
├────────────────────────────────────────────────────────────────┤
│ [🔍 Szukaj cechy] [Grupa: Wszystkie ▼] [Sklep: Domyślne ▼]    │
├────────────────────────────────────────────────────────────────┤
│ ▼ OGÓLNE (3 cechy)                                             │
│   ┌──────────────────────────────────────────────────────────┐ │
│   │ Producent: [YCF                    ] [❌ Usuń]          │ │
│   │ Materiał:  [Aluminium              ] [❌ Usuń]          │ │
│   │ Kolor:     [Czerwony ▼             ] [❌ Usuń]          │ │
│   └──────────────────────────────────────────────────────────┘ │
│                                                                 │
│ ▼ TECHNICZNE (2 cechy)                                         │
│   ┌──────────────────────────────────────────────────────────┐ │
│   │ Moc (KW):  [10.5                   ] [❌ Usuń]          │ │
│   │ Napięcie:  [12V                    ] [❌ Usuń]          │ │
│   └──────────────────────────────────────────────────────────┘ │
│                                                                 │
│ ▼ WYMIARY (1 cecha)                                            │
│   ┌──────────────────────────────────────────────────────────┐ │
│   │ Waga (kg): [2.5                    ] [❌ Usuń]          │ │
│   └──────────────────────────────────────────────────────────┘ │
│                                                                 │
│ [+ Dodaj cechę___________] [📋 Kopiuj z produktu] [💾 Zapisz] │
└────────────────────────────────────────────────────────────────┘
```

**Features:**
- Grouped display (collapsible groups)
- Different input types per feature type
- Real-time value validation
- Shop context indicator (inherited/same/different)
- Copy features from another product

**Deliverables:**
- ❌ 3.2.1 Utworzenie features-tab.blade.php
- ❌ 3.2.2 Grouped features display
- ❌ 3.2.3 Dynamic input rendering per type
- ❌ 3.2.4 Shop context visualization
- ❌ 3.2.5 Add/Edit/Remove UI

### ❌ 3.3 CSS Styling (2h)

**Lokalizacja:** Dodać do `resources/css/products/product-form.css`

**Klasy:**
```css
.feature-group { border-left: 3px solid var(--ppm-primary); }
.feature-group--collapsed { }
.feature-row { display: flex; align-items: center; gap: 16px; padding: 12px; }
.feature-row__label { font-weight: 500; width: 200px; }
.feature-row__input { flex: 1; }
.feature-row__actions { width: 80px; }
.feature-input--text { }
.feature-input--number { }
.feature-input--select { }
.feature-input--multiselect { }
.feature-status-badge { }
.feature-status-badge--inherited { background: var(--color-purple-100); }
.feature-status-badge--same { background: var(--color-green-100); }
.feature-status-badge--different { background: var(--color-orange-100); }
```

**Deliverables:**
- ❌ 3.3.1 Feature row styles
- ❌ 3.3.2 Input type styles
- ❌ 3.3.3 Status badges
- ❌ 3.3.4 Responsive adjustments
- ❌ 3.3.5 npm run build + deploy

---

## 📋 FAZA 4: FEATURE MANAGEMENT UI (6-8h)

**Cel:** Admin panel dla zarządzania feature types i groups.

### ❌ 4.1 FeatureTypeManager Component (4h)

**Lokalizacja:** `app/Http/Livewire/Admin/Features/FeatureTypeManager.php`

**Route:** `/admin/features/types`

**Funkcjonalność:**
- Lista feature types z pagination
- CRUD operations (create, edit, delete)
- Group assignment
- Input type configuration
- Options management dla select/multiselect
- Active/inactive toggle

**Deliverables:**
- ❌ 4.1.1 Utworzenie FeatureTypeManager component
- ❌ 4.1.2 CRUD functionality
- ❌ 4.1.3 Blade view z table + modals
- ❌ 4.1.4 Route registration
- ❌ 4.1.5 Menu link w sidebar (Admin section)

### ❌ 4.2 FeatureGroupManager Component (3h)

**Lokalizacja:** `app/Http/Livewire/Admin/Features/FeatureGroupManager.php`

**Route:** `/admin/features/groups`

**Funkcjonalność:**
- Lista groups z sort order
- CRUD operations
- Drag & drop reordering
- Assign feature types to groups

**Deliverables:**
- ❌ 4.2.1 Utworzenie FeatureGroupManager component
- ❌ 4.2.2 CRUD functionality
- ❌ 4.2.3 Blade view z sortable list
- ❌ 4.2.4 Route registration
- ❌ 4.2.5 Menu link w sidebar

### ❌ 4.3 BulkFeatureAssignment Component (3h)

**Lokalizacja:** `app/Http/Livewire/Products/Features/BulkFeatureAssignment.php`

**Route:** `/admin/features/bulk-assign`

**Funkcjonalność:**
- Select products (filter by category, type, etc.)
- Select feature type
- Enter value (single value dla all products)
- Shop context selection
- Preview changes
- Execute bulk assignment

**Deliverables:**
- ❌ 4.3.1 Utworzenie BulkFeatureAssignment component
- ❌ 4.3.2 Product selection UI
- ❌ 4.3.3 Feature assignment logic
- ❌ 4.3.4 Preview table
- ❌ 4.3.5 Progress indicator

---

## 📋 FAZA 5: EXCEL IMPORT/EXPORT (4-6h)

**Cel:** Import/Export cech produktów z/do Excel.

### ❌ 5.1 Excel Import UI (3h)

**Integracja z:** Existing Excel import system (ETAP_06)

**Workflow:**
1. User upload Excel z feature columns
2. Auto-detect feature columns (column headers → feature type names)
3. User review mapping (confirm/adjust)
4. User select shop context (NULL = default)
5. Preview changes (sample products)
6. Execute import (queue job dla >100 products)

**Deliverables:**
- ❌ 5.1.1 Column detection dla features
- ❌ 5.1.2 Mapping review UI
- ❌ 5.1.3 Preview table
- ❌ 5.1.4 Execute import logic
- ❌ 5.1.5 Queue job integration

### ❌ 5.2 Excel Export UI (3h)

**Workflow:**
1. User select products (filter/manual selection)
2. User select feature types to export (checkboxes)
3. User select shop context
4. Generate Excel (SKU + selected features)
5. Download file

**Deliverables:**
- ❌ 5.2.1 Export configuration UI
- ❌ 5.2.2 Excel generation logic
- ❌ 5.2.3 File download
- ❌ 5.2.4 Test z large datasets

---

## 📋 FAZA 6: TESTING & DEPLOYMENT (4-6h)

**Cel:** Comprehensive testing i deployment na produkcję.

### ❌ 6.1 Unit Tests (2h)

**Test Coverage:**
- FeatureManager tests (CRUD operations)
- FeatureGroupService tests
- FeatureTypeService tests (validation per input_type)
- FeatureExcelService tests (import/export)
- Model relations tests

**Deliverables:**
- ❌ 6.1.1 Service layer unit tests
- ❌ 6.1.2 Model tests
- ❌ 6.1.3 Coverage ≥ 80%

### ❌ 6.2 Integration Tests (2h)

**Test Scenarios:**
1. Full workflow: ProductForm → Add features → Save → Reload → Verify
2. Per-shop: Switch shop → Add feature → Save → Verify shop_id
3. Excel import: Upload → Map → Import → Verify DB
4. Bulk assignment: Select products → Assign feature → Verify all

**Deliverables:**
- ❌ 6.2.1 ProductForm feature tab test
- ❌ 6.2.2 Per-shop workflow test
- ❌ 6.2.3 Excel import/export test
- ❌ 6.2.4 Bulk operations test

### ❌ 6.3 Frontend Verification (Chrome DevTools MCP) (1h)

**Verification Points:**
1. `/admin/products/{id}/edit` tab "Cechy" renders correctly
2. Feature add/edit/remove working
3. Shop context switching updates feature values
4. Input types render correctly per feature type
5. Group collapsible sections working
6. Bulk assignment modal functional

**Deliverables:**
- ❌ 6.3.1 Screenshot verification all pages
- ❌ 6.3.2 Console error check
- ❌ 6.3.3 Network request verification
- ❌ 6.3.4 Responsive testing

### ❌ 6.4 Deployment (1h)

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
- ❌ 6.4.1 Database backup
- ❌ 6.4.2 Migrations deployed
- ❌ 6.4.3 All PHP files deployed
- ❌ 6.4.4 All Blade views deployed
- ❌ 6.4.5 CSS assets deployed
- ❌ 6.4.6 Cache cleared
- ❌ 6.4.7 Production verification

---

## ✅ COMPLIANCE CHECKLIST

### Context7 Integration
- [ ] Laravel 12.x service patterns verified
- [ ] Eloquent relations patterns verified
- [ ] Excel import/export patterns verified

### CSS & Styling (PPM Compliance)
- [ ] NO inline styles
- [ ] CSS classes w product-form.css
- [ ] PPM color tokens used
- [ ] Responsive design
- [ ] npm run build + manifest.json ROOT upload

### Livewire 3.x Compliance
- [ ] wire:key w ALL @foreach loops
- [ ] dispatch() instead of emit()
- [ ] wire:model.live dla reactive inputs
- [ ] @entangle dla Alpine.js sync

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
| **laravel-expert** | Services layer, migrations, business logic | FAZA 1, 2, 5 |
| **livewire-specialist** | Components, traits, Livewire integration | FAZA 3, 4 |
| **frontend-specialist** | CSS, UI components, responsive design | FAZA 3, 4 |
| **deployment-specialist** | Production deployment, verification | FAZA 6 |
| **coding-style-agent** | Code review przed deployment | Pre-FAZA 6 |

---

## 📊 EXPECTED OUTCOMES

### User Experience
- **Feature Management** - Intuitive UI dla cech produktu
- **Grouped Organization** - Cechy pogrupowane dla łatwiejszej nawigacji
- **Per-Shop Values** - Różne wartości cech per sklep
- **Excel Integration** - Import/Export cech z arkuszy

### Technical Quality
- **Clean Architecture** - Services layer, traits, reusable components
- **Per-Shop Support** - shop_id w wszystkich feature queries
- **Validation** - Value validation per input_type
- **Performance** - Optimized queries z eager loading

### Business Impact
- **Flexibility** - Nieograniczona liczba cech per produkt
- **Organization** - Feature groups dla struktury
- **Multi-Store** - Różne cechy per sklep PrestaShop
- **PrestaShop Ready** - Przygotowane do synchronizacji w ETAP_07

---

## 🔗 DEPENDENCIES & INTEGRATIONS

### Dependency Dla:
- **ETAP_05b (Warianty)** - Attributes system używa podobnych patterns
- **ETAP_05d (Dopasowania)** - Vehicle features są rozszerzeniem tego systemu
- **ETAP_07 (PrestaShop API)** - Feature sync używa FeatureManager

### Integracja Z:
- **ProductForm** - Tab "Cechy" w istniejącym komponencie
- **Excel System** - Reuse column mapping z ETAP_06
- **Multi-Store** - Reuse shop context patterns z ProductForm

---

## 📚 REFERENCES

### Documentation
- `_DOCS/SKU_ARCHITECTURE_GUIDE.md` - SKU-first patterns
- `_DOCS/ARCHITEKTURA_PPM/07_PRODUKTY.md` - ProductForm tab design
- `_DOCS/Struktura_Bazy_Danych.md` - Database schema reference
- `_DOCS/CSS_STYLING_GUIDE.md` - CSS best practices
- `CLAUDE.md` - Project architecture & deployment guide

### Code References
- **Existing Models:** `app/Models/FeatureType.php`, `ProductFeature.php`
- **ProductForm:** `app/Http/Livewire/Products/Management/ProductForm.php`
- **Excel Service:** Patterns z ETAP_06 Import/Export

### Related Plans
- `ETAP_05b_Produkty_Warianty.md` - Variant attributes system
- `ETAP_05d_Produkty_Dopasowania.md` - Vehicle compatibility features
- `ETAP_07_Prestashop_API.md` - Feature sync integration

---

**Report Status:** ✅ COMPLETED
**Next Action:** User review & approval
**Estimated Implementation Start:** Po ukończeniu ETAP_05b lub równolegle
**Responsible Agent:** architect (Kamil Wiliński approval required)

---

**END OF PLAN**
