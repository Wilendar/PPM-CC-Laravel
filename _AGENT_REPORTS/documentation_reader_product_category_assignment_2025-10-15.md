# RAPORT ANALIZY: Product-Category Assignment (ETAP_05 Sekcja 2.2)

**Agent:** Documentation Reader
**Data:** 2025-10-15
**Zakres:** Szczegółowa analiza wymagań dla Product-Category Assignment (ETAP_05 punkt 2.2)
**Status sekcji:** ❌ NIE ROZPOCZĘTE (z planu ETAP_05_Produkty.md)

---

## EXECUTIVE SUMMARY

Sekcja **2.2 Product-Category Assignment** z ETAP_05 jest **NIEROZPOCZĘTA**, mimo że:
- ✅ Kategorie działają (ETAP_05 2.1 COMPLETED)
- ✅ ProductForm istnieje (ETAP_05 1.2 COMPLETED)
- ✅ Multi-Store System działa (ETAP_05 1.5 COMPLETED)
- ⚠️ **BRAK IMPLEMENTACJI:** Category assignment w ProductForm

**KRYTYCZNY GAP:** ProductForm **NIE POSIADA UI do przypisywania kategorii!**

Istniejące komponenty (`CategoryPicker`, `ProductCategoryManager`) są przygotowane, ale **BRAK integracji** w ProductForm UI.

---

## 1. SZCZEGÓŁOWE REQUIREMENTS Z PLANU

### 2.2.1 Category Assignment Interface (NIEROZPOCZĘTA)

#### 2.2.2.1 Product Category Selection
- **❌ 2.2.2.1.1** Multiple category assignment per product
- **❌ 2.2.2.1.2** Primary category designation dla PrestaShop
  **🔗 POWIĄZANIE:** ETAP_07 punkty 7.5.1.1, 7.5.2.1 (PrestaShop category mapping)
- **❌ 2.2.2.1.3** Category tree selector w product form
- **❌ 2.2.2.1.4** Breadcrumb display dla selected categories
- **❌ 2.2.2.1.5** Category inheritance rules

#### 2.2.2.2 Bulk Category Operations (NIEROZPOCZĘTA)
- **❌ 2.2.2.2.1** Bulk assign categories to products
- **❌ 2.2.2.2.2** Bulk remove categories from products
- **❌ 2.2.2.2.3** Bulk move products between categories
- **❌ 2.2.2.2.4** Category merge functionality
- **❌ 2.2.2.2.5** Category deletion z product reassignment

**TOTAL STATUS:** 0/10 zadań ukończone (0%)

---

## 2. ISTNIEJĄCE KOMPONENTY (INFRASTRUKTURA GOTOWA)

### 2.1 Database Schema ✅ ZAIMPLEMENTOWANE

**Tabela:** `product_categories` (pivot table)

**Struktura:**
```sql
CREATE TABLE product_categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    shop_id BIGINT NULL,  -- NULL = default categories, NOT NULL = per-shop override
    is_primary BOOLEAN DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE (product_id, category_id, shop_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,

    INDEX idx_product_id (product_id),
    INDEX idx_category_id (category_id),
    INDEX idx_shop_id (shop_id),
    INDEX idx_is_primary (is_primary)
);
```

**KLUCZOWE CECHY:**
- ✅ Multi-store support via `shop_id` column
- ✅ Primary category designation via `is_primary` flag
- ✅ Sort ordering dla multiple categories
- ✅ Unique constraint per product+category+shop

**ARCHITEKTURA:**
- `shop_id=NULL` → "Dane domyślne" (z pierwszego importu)
- `shop_id=X` → Per-shop override (różne kategorie per sklep)

**MIGRACJE:**
- `2024_XX_XX_create_product_categories_table.php` - COMPLETED
- `2025_10_13_000004_add_shop_id_to_product_categories.php` - COMPLETED

---

### 2.2 Product Model ✅ ZAIMPLEMENTOWANE

**Plik:** `app/Models/Product.php` (2182 linii)

**Category Relations:**

```php
// DEFAULT CATEGORIES ONLY (shop_id=NULL)
public function categories(): BelongsToMany
{
    return $this->belongsToMany(Category::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                ->wherePivotNull('shop_id') // ONLY default
                ->withTimestamps()
                ->orderBy('product_categories.sort_order', 'asc');
}

// PER-SHOP CATEGORIES
public function categoriesForShop(int $shopId): BelongsToMany
{
    return $this->belongsToMany(Category::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                ->wherePivot('shop_id', $shopId)
                ->withTimestamps();
}

// EFFECTIVE CATEGORIES (per-shop if exist, otherwise default)
public function getEffectiveCategoriesForShop(int $shopId)
{
    $shopCategories = $this->categoriesForShop($shopId, false)->get();
    return $shopCategories->isNotEmpty() ? $shopCategories : $this->categories;
}

// ALL CATEGORIES GROUPED BY SHOP
public function allCategoriesGroupedByShop(): array
{
    // Returns: ['default' => Collection, 'shops' => [shopId => Collection]]
}

// PRIMARY CATEGORY - DEFAULT
public function primaryCategory(): BelongsToMany
{
    return $this->belongsToMany(Category::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                ->wherePivotNull('shop_id')
                ->wherePivot('is_primary', true)
                ->limit(1);
}

// PRIMARY CATEGORY - PER-SHOP
public function primaryCategoryForShop(int $shopId): BelongsToMany
{
    return $this->belongsToMany(Category::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order', 'shop_id'])
                ->wherePivot('shop_id', $shopId)
                ->wherePivot('is_primary', true)
                ->limit(1);
}
```

**KLUCZOWE METODY:**
- ✅ SKU-first architecture compliance
- ✅ Multi-store category support
- ✅ Primary category designation
- ✅ Effective category fallback logic
- ✅ Business logic: max 10 categories per product

---

### 2.3 Category Model ✅ ZAIMPLEMENTOWANE

**Plik:** `app/Models/Category.php` (825 linii)

**Product Relations:**

```php
// ALL PRODUCTS IN CATEGORY
public function products(): BelongsToMany
{
    return $this->belongsToMany(Product::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order'])
                ->withTimestamps()
                ->orderBy('pivot_sort_order', 'asc');
}

// PRIMARY PRODUCTS (where this is primary category)
public function primaryProducts(): BelongsToMany
{
    return $this->belongsToMany(Product::class, 'product_categories')
                ->withPivot(['is_primary', 'sort_order'])
                ->wherePivot('is_primary', true);
}
```

**TREE STRUCTURE:**
- ✅ 5-level hierarchy (0-4)
- ✅ Path materialization (`/1/2/5`)
- ✅ Breadcrumb navigation
- ✅ Ancestor/descendant queries
- ✅ Self-referencing tree

**PRESTASHOP INTEGRATION:**
```php
public function getPrestashopCategoryId(PrestaShopShop $shop): ?int
{
    // Get mapped PrestaShop category ID for shop
}
```

---

### 2.4 ProductCategoryManager Service ✅ ZAIMPLEMENTOWANE

**Plik:** `app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php` (492 linii)

**STATUS:** Fully implemented service, **BUT NOT USED IN UI!**

**ARCHITEKTURA:**
- Context-aware category management (default vs per-shop)
- Separate handling dla default i shop-specific categories
- Database sync via transactions

**KLUCZOWE METODY:**

```php
// CATEGORY LOADING
public function loadCategories(): void
{
    // Load default categories (shop_id=NULL)
    // Load shop-specific categories (shop_id=X)
}

// CATEGORY TOGGLING
public function toggleCategory(int $categoryId): void
{
    // Context-aware: default vs shop-specific
}

public function setPrimaryCategory(int $categoryId): void
{
    // Set primary category dla current context
}

// DATABASE SYNC
public function syncCategories(): void
{
    // Sync both default and shop-specific categories
    // Uses DB::transaction for atomicity
}

// UTILITIES
public function getCurrentCategories(): array
public function getCurrentPrimaryCategory(): ?int
public function isCategorySelected(int $categoryId): bool
public function isCategoryPrimary(int $categoryId): bool
```

**SYNC LOGIC:**
```php
private function syncDefaultCategories(): void
{
    // UPDATED 2025-10-13: Manual is_primary reset (triggers removed due to MySQL 1442)
    DB::table('product_categories')
        ->where('product_id', $this->component->product->id)
        ->whereNull('shop_id')
        ->update(['is_primary' => false]);

    // Prepare category data with shop_id=NULL
    foreach ($validCategoryIds as $index => $categoryId) {
        $categoryData[$categoryId] = [
            'is_primary' => $categoryId === $primaryCategoryId,
            'sort_order' => $index,
            'shop_id' => null,
        ];
    }

    // Sync categories
    $this->component->product->categories()->sync($categoryData);
}

private function syncShopCategories(): void
{
    foreach ($this->component->shopCategories as $shopId => $shopCategoryData) {
        // Reset is_primary for shop
        DB::table('product_categories')
            ->where('product_id', $this->component->product->id)
            ->where('shop_id', $shopId)
            ->update(['is_primary' => false]);

        // Delete existing per-shop categories
        DB::table('product_categories')
            ->where('product_id', $this->component->product->id)
            ->where('shop_id', $shopId)
            ->delete();

        // Insert new per-shop categories with shop_id=X
        foreach ($selectedCategories as $index => $categoryId) {
            DB::table('product_categories')->insert([
                'product_id' => $this->component->product->id,
                'category_id' => $categoryId,
                'shop_id' => $shopId,
                'is_primary' => $categoryId === $primaryCategoryId,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

**⚠️ PROBLEM:** Service jest **GOTOWY**, ale **NIE INTEGROWANY w ProductForm UI!**

---

### 2.5 ProductForm Component ⚠️ CZĘŚCIOWO ZAIMPLEMENTOWANE

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php` (~325 linii - refactored)

**CATEGORY PROPERTIES:**
```php
// Category management service
protected ?ProductCategoryManager $categoryManager = null;

// Categories per context
public array $defaultCategories = ['selected' => [], 'primary' => null];
public array $shopCategories = []; // [shopId => ['selected' => [ids], 'primary' => id]]
```

**INITIALIZATION:**
```php
public function mount(?Product $product = null): void
{
    // Initialize category manager
    $this->categoryManager = new ProductCategoryManager($this);

    if ($this->isEditMode) {
        $this->loadProductData(); // Loads categories via categoryManager
    }
}
```

**⚠️ MISSING:**
- ❌ **BRAK UI** do wyświetlania category tree
- ❌ **BRAK UI** do wyboru kategorii
- ❌ **BRAK UI** do primary category selection
- ❌ **BRAK** breadcrumb display
- ❌ **BRAK** visual feedback dla selected categories

**ISTNIEJĄCE KOMPONENTY BLADE (NIE UŻYWANE):**
- `resources/views/livewire/products/category-picker.blade.php` - exists but not used
- `resources/views/components/category-picker-node.blade.php` - exists but not used

---

## 3. CONSTRAINTS & BUSINESS RULES

### 3.1 Enterprise Requirements

1. **Multiple Categories:**
   - Max 10 categories per product (business rule)
   - No category limit enforcement obecnie (validation brak)

2. **Primary Category:**
   - **KRYTYCZNE dla PrestaShop:** `id_category_default` mapping
   - Tylko jedna primary category per product per context (default/shop)
   - Automatic selection: first category if no primary set

3. **Multi-Store Support:**
   - Default categories (`shop_id=NULL`) - from first import
   - Per-shop override (`shop_id=X`) - different categories per shop
   - Effective categories: per-shop if exist, otherwise default

4. **SKU-First Architecture (CLAUDE.md):**
   - ✅ SKU as primary business identifier
   - ✅ Category assignment via SKU lookup
   - ✅ Multi-store consistency via SKU

5. **No Hardcoding (CLAUDE.md):**
   - ✅ All configurable through admin
   - ✅ No mock data in category assignment

### 3.2 PrestaShop Integration (ETAP_07 Dependencies)

**🔗 POWIĄZANIE:** ETAP_07 punkty 7.5.1.1, 7.5.2.1

**PrestaShop Category Mapping Requirements:**

1. **Primary Category:**
   - PrestaShop wymaga `id_category_default` (primary category)
   - Mapping via `shop_mappings` table:
     ```sql
     SELECT prestashop_id FROM shop_mappings
     WHERE shop_id = ?
       AND mapping_type = 'category'
       AND ppm_value = ?
     ```

2. **Multiple Categories:**
   - PrestaShop obsługuje multiple categories per product
   - Array of category IDs w `ps_category_product` table
   - PPM musi exportować wszystkie assigned categories

3. **Per-Shop Categories:**
   - Różne kategorie per sklep PrestaShop
   - Każdy sklep może mieć własną primary category
   - Export musi używać shop-specific categories

**CURRENT IMPLEMENTATION STATUS:**
- ✅ Database schema supports PrestaShop requirements
- ✅ Product Model ma metody dla primary category
- ✅ Multi-store category support ready
- ❌ **UI dla category assignment BRAK**
- ⚠️ **PrestaShop export logic** - implementacja w ETAP_07

---

## 4. MISSING COMPONENTS (DO IMPLEMENTACJI)

### 4.1 UI Components - PRIORITY HIGH

#### 4.1.1 Category Tree Selector (2.2.2.1.3)
**Lokalizacja:** `resources/views/livewire/products/management/product-form.blade.php`

**Requirements:**
- Interactive category tree z expand/collapse
- Checkboxes dla multiple selection
- Primary category designation (radio button lub star icon)
- Context-aware display (default vs per-shop)
- Visual feedback dla selected categories
- Breadcrumb dla each selected category

**ISTNIEJĄCY KOMPONENT DO WYKORZYSTANIA:**
```blade
<!-- resources/views/livewire/products/category-picker.blade.php -->
<!-- EXISTS BUT NOT INTEGRATED! -->
```

**INTEGRATION PATTERN:**
```blade
<!-- CURRENT: BRAK category UI w ProductForm -->

<!-- REQUIRED: Add to ProductForm tabs -->
<div x-show="activeTab === 'categories'" class="tab-content">
    <h3>Kategorie produktu</h3>

    @if($activeShopId === null)
        <!-- Default Categories -->
        <livewire:products.category-picker
            :product="$product"
            :selectedCategories="$defaultCategories['selected']"
            :primaryCategory="$defaultCategories['primary']"
            wire:key="category-picker-default"
        />
    @else
        <!-- Per-Shop Categories -->
        <livewire:products.category-picker
            :product="$product"
            :selectedCategories="$shopCategories[$activeShopId]['selected'] ?? []"
            :primaryCategory="$shopCategories[$activeShopId]['primary'] ?? null"
            :shopId="$activeShopId"
            wire:key="category-picker-shop-{{ $activeShopId }}"
        />
    @endif
</div>
```

#### 4.1.2 Breadcrumb Display (2.2.2.1.4)
**Requirements:**
- Display breadcrumb dla każdej selected category
- Show full path: "Parent > Child > Current"
- Primary category indicator (star/badge)
- Remove button per category
- Sort order display

**PATTERN:**
```blade
<div class="selected-categories-list">
    @foreach($selectedCategories as $categoryId)
        @php
            $category = App\Models\Category::find($categoryId);
            $isPrimary = $categoryId === $primaryCategory;
        @endphp

        <div class="category-breadcrumb-item">
            @if($isPrimary)
                <span class="primary-badge">⭐ Primary</span>
            @endif

            <span class="breadcrumb">{{ $category->fullName }}</span>

            <button wire:click="removeCategory({{ $categoryId }})">
                Remove
            </button>
        </div>
    @endforeach
</div>
```

#### 4.1.3 Category Inheritance Indicator (2.2.2.1.5)
**Requirements:**
- Visual indicator gdy shop uses default categories
- "Override" button to create shop-specific categories
- Diff display: default vs shop-specific

**PATTERN:**
```blade
@if($activeShopId !== null)
    @php
        $hasShopCategories = isset($shopCategories[$activeShopId]) && !empty($shopCategories[$activeShopId]['selected']);
        $defaultCategoryCount = count($defaultCategories['selected']);
    @endphp

    @if(!$hasShopCategories)
        <div class="category-inheritance-notice">
            <p>Using default categories ({{ $defaultCategoryCount }} categories)</p>
            <button wire:click="createShopCategoryOverride">
                Create shop-specific categories
            </button>
        </div>
    @else
        <div class="category-override-active">
            <p>Shop-specific categories ({{ count($shopCategories[$activeShopId]['selected']) }} categories)</p>
            <button wire:click="resetToDefaultCategories">
                Reset to default
            </button>
        </div>
    @endif
@endif
```

---

### 4.2 Bulk Operations - PRIORITY MEDIUM

#### 4.2.1 Bulk Assign Categories (2.2.2.2.1)
**Lokalizacja:** `app/Http/Livewire/Products/Listing/ProductList.php`

**Requirements:**
- Select multiple products
- Choose categories to assign
- Set primary category option
- Confirmation modal
- Progress tracking

**IMPLEMENTATION:**
```php
public function bulkAssignCategories(array $productIds, array $categoryIds, ?int $primaryCategoryId = null)
{
    foreach ($productIds as $productId) {
        $product = Product::find($productId);

        foreach ($categoryIds as $index => $categoryId) {
            $product->categories()->syncWithoutDetaching([
                $categoryId => [
                    'is_primary' => $categoryId === $primaryCategoryId,
                    'sort_order' => $index,
                    'shop_id' => null, // Default categories
                ]
            ]);
        }
    }
}
```

#### 4.2.2 Bulk Remove Categories (2.2.2.2.2)
**Requirements:**
- Select multiple products
- Choose categories to remove
- Prevent removing last category (validation)
- Handle primary category removal

#### 4.2.3 Bulk Move Products (2.2.2.2.3)
**Requirements:**
- Select multiple products
- Choose source category
- Choose destination category
- Option: remove from source or keep in both

#### 4.2.4 Category Merge (2.2.2.2.4)
**Requirements:**
- Select two categories to merge
- Choose target category
- Move all products from source to target
- Update primary category assignments
- Archive source category

#### 4.2.5 Category Deletion with Reassignment (2.2.2.2.5)
**Requirements:**
- Select category to delete
- Choose reassignment target category
- Move all products to target
- Update shop_mappings dla PrestaShop
- Soft delete category

**CURRENT STATUS:** CategoryTree component ma delete functionality, ale **BRAK** product reassignment logic!

---

## 5. DEPENDENCIES & INTEGRATION POINTS

### 5.1 ETAP_07 (PrestaShop API) Dependencies

**🔗 CRITICAL DEPENDENCIES:**

1. **7.5.1.1 - Category Mapping:**
   - PrestaShop category ID mapping via `shop_mappings`
   - Primary category export to `id_category_default`
   - Multiple categories export to `ps_category_product`

2. **7.5.2.1 - Category Transformations:**
   - CategoryTransformer service dla export
   - Reverse transformation dla import
   - Handling per-shop category overrides

**EXPORT WORKFLOW (ETAP_07):**
```
Product → Categories → CategoryTransformer → PrestaShop API
          ↓
    shop_mappings lookup
          ↓
    id_category_default (primary)
    ps_category_product (all categories)
```

**IMPORT WORKFLOW (ETAP_07):**
```
PrestaShop API → CategoryTransformer → Categories → Product
                        ↓
                  shop_mappings create
                        ↓
                  Default categories (shop_id=NULL)
```

### 5.2 ETAP_05 Dependencies

1. **2.1 Category System (COMPLETED):**
   - ✅ Category tree structure ready
   - ✅ CRUD operations working
   - ✅ Drag & drop reordering
   - → **Ready for product assignment**

2. **1.2 ProductForm (COMPLETED):**
   - ✅ Form structure ready
   - ✅ Tab system implemented
   - ✅ Multi-store tabs working
   - ❌ **Category tab MISSING**

3. **1.5 Multi-Store System (COMPLETED):**
   - ✅ Per-shop data management
   - ✅ Default data fallback
   - ✅ Shop selector UI
   - → **Ready for per-shop categories**

### 5.3 ETAP_06 (Import/Export) Dependencies

**⚠️ POTENTIAL BLOCKER:**
- Import XLSX może zawierać category names
- Need category matching/creation during import
- Bulk category assignment podczas import workflow

**RECOMMENDATION:** Implement basic category assignment BEFORE ETAP_06 to avoid blockers.

---

## 6. POTENCJALNE PROBLEMY I RYZYKA

### 6.1 Architecture Risks

#### RISK 1: Primary Category Enforcement
**Problem:** Multiple components mogą ustawić is_primary=true jednocześnie
**Mitigation:**
- ✅ Database triggers removed (MySQL 1442 error)
- ✅ Manual reset before sync w ProductCategoryManager
- ⚠️ **Validation needed:** Ensure tylko jedna primary per context

#### RISK 2: Category Limit Validation
**Problem:** Business rule: max 10 categories per product, ale **BRAK VALIDATION**
**Mitigation:** Add validation w ProductCategoryManager:
```php
if (count($selectedCategories) > 10) {
    throw new ValidationException('Product cannot have more than 10 categories');
}
```

#### RISK 3: Shop-Specific Category Isolation
**Problem:** Cross-contamination między sklepami (ID collisions)
**Mitigation:**
- ✅ Wire:key zawiera shop ID w ProductForm
- ✅ Unique constraint (product_id, category_id, shop_id)
- ⚠️ **UI Testing needed:** Verify isolation w multi-shop context

### 6.2 Performance Risks

#### RISK 4: N+1 Query Problem
**Problem:** Loading categories dla każdego produktu osobno w listing
**Mitigation:**
```php
// ProductList - eager load categories
$products = Product::with(['categories', 'primaryCategory'])->get();
```

#### RISK 5: Category Tree Loading
**Problem:** Recursive tree loading w category picker
**Mitigation:**
- ✅ Path materialization w Category model
- ✅ Cached ancestor/descendant queries
- ⚠️ **Consider:** Lazy loading dla deep trees (>100 nodes)

### 6.3 Integration Risks

#### RISK 6: PrestaShop Category Sync Conflict
**Problem:** Category nie istnieje w PrestaShop podczas export
**Mitigation:**
- Validate category mappings przed sync
- Auto-create missing categories w PrestaShop
- Fallback to default category jeśli mapping fails

#### RISK 7: Import Workflow Blocking
**Problem:** ETAP_06 import może wymagać category assignment
**Mitigation:** **HIGH PRIORITY** - Implement category assignment BEFORE ETAP_06

---

## 7. RECOMMENDATIONS FOR ARCHITECT

### 7.1 PRIORITY 1: UI Implementation (IMMEDIATE)

**TASK:** Integrate existing components into ProductForm

**STEPS:**
1. Add "Categories" tab to ProductForm
2. Embed CategoryPicker component (already exists!)
3. Wire category selection to ProductCategoryManager
4. Add breadcrumb display dla selected categories
5. Add primary category selection UI
6. Visual feedback dla inheritance (default vs shop-specific)

**ESTIMATED EFFORT:** 4-6 hours (components już istnieją!)

**FILES TO MODIFY:**
- `resources/views/livewire/products/management/product-form.blade.php`
- `app/Http/Livewire/Products/Management/ProductForm.php` (wire methods)

---

### 7.2 PRIORITY 2: Validation & Business Rules (HIGH)

**TASK:** Enforce business rules dla category assignment

**RULES:**
1. Max 10 categories per product
2. Exactly one primary category per context
3. Primary category must be in selected categories
4. Cannot remove last category

**IMPLEMENTATION:**
```php
// ProductCategoryManager
private function validateCategoryAssignment(array $selectedCategories, ?int $primaryCategory): void
{
    if (count($selectedCategories) > 10) {
        throw new ValidationException('Maximum 10 categories per product');
    }

    if (empty($selectedCategories)) {
        throw new ValidationException('Product must have at least one category');
    }

    if ($primaryCategory && !in_array($primaryCategory, $selectedCategories)) {
        throw new ValidationException('Primary category must be selected');
    }
}
```

**ESTIMATED EFFORT:** 2-3 hours

---

### 7.3 PRIORITY 3: Bulk Operations (MEDIUM)

**TASK:** Implement bulk category operations dla ProductList

**OPERATIONS:**
1. Bulk assign categories (2.2.2.2.1)
2. Bulk remove categories (2.2.2.2.2)
3. Bulk move products (2.2.2.2.3)

**IMPLEMENTATION APPROACH:**
- Queue-based processing dla large datasets
- Progress tracking via `JobProgressService`
- Confirmation modals z preview
- Rollback capability dla errors

**ESTIMATED EFFORT:** 8-12 hours

---

### 7.4 PRIORITY 4: Category Merge & Deletion (LOW)

**TASK:** Advanced category management operations

**OPERATIONS:**
1. Category merge functionality (2.2.2.2.4)
2. Category deletion with reassignment (2.2.2.2.5)

**IMPLEMENTATION APPROACH:**
- Wizard-style UI dla complex operations
- Preview changes before execution
- Database transaction wrapping
- Audit trail dla all changes

**ESTIMATED EFFORT:** 10-15 hours

---

## 8. IMPLEMENTATION ROADMAP

### PHASE 1: Basic Category Assignment (IMMEDIATE)
**Duration:** 1-2 days
**Priority:** 🔴 CRITICAL (blocks ETAP_06)

**Deliverables:**
- ✅ Categories tab w ProductForm
- ✅ Category tree selector UI
- ✅ Primary category selection
- ✅ Breadcrumb display
- ✅ Save/sync functionality

**Acceptance Criteria:**
- User can assign multiple categories do produktu
- User can set primary category
- Changes are persisted to database
- Multi-store context switching działa
- Visual feedback dla selected categories

---

### PHASE 2: Validation & Business Rules (HIGH)
**Duration:** 0.5-1 day
**Priority:** 🟠 HIGH

**Deliverables:**
- ✅ Max 10 categories validation
- ✅ Primary category validation
- ✅ Category inheritance indicator
- ✅ User-friendly error messages

**Acceptance Criteria:**
- Validation prevents invalid states
- Clear error messages dla users
- Inheritance properly indicated w UI

---

### PHASE 3: Bulk Operations (MEDIUM)
**Duration:** 2-3 days
**Priority:** 🟡 MEDIUM

**Deliverables:**
- ✅ Bulk assign categories
- ✅ Bulk remove categories
- ✅ Bulk move products
- ✅ Progress tracking UI

**Acceptance Criteria:**
- Operations work dla large datasets (1000+ products)
- Progress tracking visible
- Rollback on errors
- Confirmation modals

---

### PHASE 4: Advanced Operations (LOW)
**Duration:** 3-4 days
**Priority:** 🟢 LOW (future enhancement)

**Deliverables:**
- ✅ Category merge wizard
- ✅ Category deletion with reassignment
- ✅ Audit trail dla all operations

**Acceptance Criteria:**
- Complex operations guided przez wizard
- All changes logged
- Rollback capability
- PrestaShop sync considerations

---

## 9. TESTING REQUIREMENTS

### 9.1 Unit Tests (REQUIRED)

**ProductCategoryManager Tests:**
```php
// tests/Unit/Services/ProductCategoryManagerTest.php
test('can toggle default category')
test('can set primary category')
test('validates max 10 categories')
test('syncs categories to database')
test('handles shop-specific categories')
test('prevents invalid primary category')
```

**Product Model Tests:**
```php
// tests/Unit/Models/ProductTest.php
test('can get default categories')
test('can get shop-specific categories')
test('effective categories fallback works')
test('primary category designation works')
test('validates business rules')
```

### 9.2 Feature Tests (REQUIRED)

**ProductForm Category Assignment:**
```php
// tests/Feature/ProductForm/CategoryAssignmentTest.php
test('can assign category to product')
test('can set primary category')
test('can remove category from product')
test('enforces max 10 categories')
test('shop-specific categories work')
test('inheritance from default categories')
```

**Bulk Operations:**
```php
// tests/Feature/BulkOperations/CategoryBulkOperationsTest.php
test('can bulk assign categories to products')
test('can bulk remove categories from products')
test('can bulk move products between categories')
test('handles large datasets (1000+ products)')
```

### 9.3 Integration Tests (RECOMMENDED)

**PrestaShop Category Sync:**
```php
// tests/Integration/PrestaShop/CategorySyncTest.php
test('primary category exports to id_category_default')
test('multiple categories export to ps_category_product')
test('shop-specific categories export correctly')
test('category mappings are maintained')
```

---

## 10. DOCUMENTATION UPDATES

### 10.1 Update CLAUDE.md (REQUIRED)

**Add Section:**
```markdown
## Category Assignment System

### Architecture
- Multiple categories per product (max 10)
- Primary category designation dla PrestaShop
- Multi-store support: default + per-shop override
- SKU-first approach dla category assignment

### Usage
- ProductForm: Categories tab dla assignment
- ProductCategoryManager: Service layer dla logic
- Bulk operations: ProductList component

### PrestaShop Integration
- Primary category → id_category_default
- All categories → ps_category_product
- Shop-specific mappings supported
```

### 10.2 Update ETAP_05_Produkty.md (REQUIRED)

**Update Section 2.2 status:**
```markdown
- ✅ **2.2 Product-Category Assignment**
  - ✅ **2.2.1 Category Assignment Interface**
    - ✅ **2.2.2.1 Product Category Selection**
      - ✅ 2.2.2.1.1 Multiple category assignment per product
        └──📁 PLIK: app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php
      - ✅ 2.2.2.1.2 Primary category designation dla PrestaShop
        └──📁 PLIK: app/Models/Product.php (primaryCategory relations)
      - ✅ 2.2.2.1.3 Category tree selector w product form
        └──📁 PLIK: resources/views/livewire/products/management/product-form.blade.php
      - ✅ 2.2.2.1.4 Breadcrumb display dla selected categories
        └──📁 PLIK: resources/views/livewire/products/management/partials/category-breadcrumbs.blade.php
      - ✅ 2.2.2.1.5 Category inheritance rules
        └──📁 PLIK: app/Models/Product.php (getEffectiveCategoriesForShop)
```

---

## 11. CRITICAL PATH SUMMARY

### ⚠️ BLOCKER ALERT

**ETAP_06 (Import/Export) ZABLOKOWANY** bez category assignment UI!

**REASON:** Import workflow potrzebuje:
- Category matching during XLSX import
- Bulk category assignment dla imported products
- Category name → ID mapping

**MITIGATION:** **NATYCHMIASTOWA IMPLEMENTACJA** category assignment UI (Phase 1)

---

### READINESS MATRIX

| Component | Status | Notes |
|-----------|--------|-------|
| Database schema | ✅ READY | product_categories pivot table complete |
| Product Model | ✅ READY | All relations implemented |
| Category Model | ✅ READY | Tree structure + relations working |
| ProductCategoryManager | ✅ READY | Service logic complete |
| ProductForm | ⚠️ PARTIAL | Properties ready, **UI MISSING** |
| CategoryPicker | ⚠️ EXISTS | Component exists but **NOT INTEGRATED** |
| Validation | ❌ MISSING | Business rules not enforced |
| Bulk Operations | ❌ MISSING | No implementation |
| PrestaShop Sync | ⚠️ PENDING | ETAP_07 dependency |

---

## 12. NEXT STEPS FOR ARCHITECT

### IMMEDIATE ACTIONS (TODAY):

1. **Review raport z team**
2. **Assign agent:** frontend-specialist lub livewire-specialist
3. **Create TODO dla Phase 1** (Category Assignment UI)
4. **Prioritize:** Block ETAP_06 until Phase 1 complete

### WEEK 1 GOALS:

- ✅ Phase 1 complete (Category Assignment UI)
- ✅ Phase 2 complete (Validation)
- ✅ Testing infrastructure setup
- ✅ Documentation updated

### WEEK 2 GOALS:

- ✅ Phase 3 started (Bulk Operations)
- ✅ Integration tests dla PrestaShop sync
- ✅ ETAP_06 unblocked

---

## 13. APPENDIX: FILES & LOCATIONS

### A. Database Migrations
- `database/migrations/*_create_product_categories_table.php`
- `database/migrations/2025_10_13_000004_add_shop_id_to_product_categories.php`

### B. Models
- `app/Models/Product.php` (lines 237-397 - category relations)
- `app/Models/Category.php` (lines 237-258 - product relations)

### C. Services
- `app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php`

### D. Components (Livewire)
- `app/Http/Livewire/Products/Management/ProductForm.php`
- `app/Http/Livewire/Products/CategoryPicker.php` (exists, not integrated)

### E. Views (Blade)
- `resources/views/livewire/products/management/product-form.blade.php` (needs category tab)
- `resources/views/livewire/products/category-picker.blade.php` (exists, not used)
- `resources/views/components/category-picker-node.blade.php` (exists, not used)

### F. Documentation
- `Plan_Projektu/ETAP_05_Produkty.md` (section 2.2)
- `Plan_Projektu/ETAP_07_Prestashop_API.md` (sections 7.5.1.1, 7.5.2.1)
- `CLAUDE.md` (needs category assignment section)

---

**END OF REPORT**

**Prepared by:** Documentation Reader Agent
**Date:** 2025-10-15
**Status:** ✅ COMPLETE - Ready for architect review
