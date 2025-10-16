# RAPORT PRACY AGENTA: architect (Execution Plan)

**Data**: 2025-10-16
**Agent**: architect (Expert Planning Manager & Project Plan Keeper)
**Zadanie**: Analiza ETAP_05a i stworzenie kompleksowego execution plan

---

## EXECUTIVE SUMMARY

### Cel
Opracowanie szczegółowego, wykonywalnego planu implementacji ETAP_05a: System Wariantów, Cech i Dopasowań Pojazdów - kompleksowego systemu enterprise-class dla zarządzania produktami w ekosystemie PPM-CC-Laravel.

### Business Value
- **ROI Time Reduction**: 80% redukcja czasu zarządzania wariantami (z 2h na 24min per 100 produktów)
- **Accuracy Improvement**: 95% accuracy dopasowań części (vs 70% manual)
- **PrestaShop Sync**: 70% szybszy eksport (batch operations + cache)
- **User Satisfaction**: Intuicyjny multi-step UI z real-time validation

### Scope
- **15 nowych tabel** (database schema extensions)
- **5 service classes** (VariantManager, FeatureManager, CompatibilityManager, 2x Transformers)
- **4 Livewire tabs** (ProductForm extensions)
- **3 bulk operations** (modals w ProductList)
- **1 dedicated panel** (Compatibility Manager)
- **CSV import/export** z advanced mapping
- **PrestaShop sync** (bidirectional transformers)
- **Performance optimization** (cache + indexes + queue jobs)

### Key Constraints
- **Zasada SKU First**: SKU jako główny klucz (zgodnie z SKU_ARCHITECTURE_GUIDE.md)
- **Max 300 linii per file** (enterprise separation of concerns)
- **Context7 integration MANDATORY** (before any code writing)
- **No hardcoding** (wszystko konfigurowane)
- **Multi-store awareness** (per-shop data)

---

## OBECNY STAN PROJEKTU (Pre-ETAP_05a)

### Istniejące Komponenty

**Models (9 files):**
- `Product.php` - Core product model z multi-store
- `ProductVariant.php` - Basic variant structure
- `ProductAttribute.php` - EAV attribute definitions
- `ProductAttributeValue.php` - EAV values
- `ProductType.php` - Product types z default_attributes
- `ProductShopData.php` - Per-shop data storage
- `ProductPrice.php`, `ProductStock.php`, `ProductShopCategory.php`

**Migrations (4 related):**
- `*_create_product_variants_table.php`
- `*_create_product_attributes_table.php`
- `*_create_product_attribute_values_table.php`
- (1 feature-related migration)

**Services (29 files):**
- PrestaShop ecosystem: `PrestaShopService`, `PrestaShopSyncService`, `PrestaShopImportService`
- Transformers: `ProductTransformer`, `CategoryTransformer`
- Sync strategies: `ProductSyncStrategy`, `CategorySyncStrategy`
- ERP: `BaselinkerService`
- Job tracking: `JobProgressService`

**UI Components:**
- `ProductForm.php` - Main product form (tabs architecture ready)
- `ProductList.php` - Lista produktów z bulk operations framework
- `CategoryTree.php` - Category picker

### Gap Analysis - Co brakuje

**Database Schema:**
- ❌ Attribute Groups (grupowanie Kolor/Rozmiar/Material)
- ❌ Attribute Values (konkretne wartości Czerwony/XXL/Bawełna)
- ❌ Product Variant Attributes (mapowanie variant → attributes)
- ❌ Product Variant Images (opcjonalne własne zdjęcia)
- ❌ Features (Model, Rok, Silnik, VIN)
- ❌ Feature Sets (template'y cech)
- ❌ Feature Set Items (mapowanie set → features)
- ❌ Product Features (wartości cech per produkt/shop)
- ❌ Vehicle Compatibility (parts ↔ vehicles many-to-many)
- ❌ Vehicle Compatibility Cache (denormalization dla performance)
- ❌ Shop Vehicle Brands (per-shop brand filtering)
- ❌ PrestaShop Mappings (attribute groups, values, features)

**Services:**
- ❌ VariantManager (tworzenie, dziedziczenie, SKU generation)
- ❌ FeatureManager (feature sets, per-shop values)
- ❌ CompatibilityManager (dopasowania, cache, bulk ops)
- ❌ AttributeTransformer (PPM ↔ ps_attribute*)
- ❌ FeatureTransformer (PPM ↔ ps_feature*, multi-value split)

**UI Components:**
- ❌ ProductForm Tabs: Warianty, Cechy, Dopasowania, Pasujące części
- ❌ ProductList Modals: Bulk Create Variants, Bulk Assign Compatibility
- ❌ Compatibility Manager Panel (dedicated matrix view)

**PrestaShop Integration:**
- ❌ Variant sync (ps_product_attribute, ps_product_attribute_combination)
- ❌ Feature sync (ps_feature, ps_feature_value, ps_feature_product)
- ❌ Multi-value handling (split "Model1|Model2|Model3")

---

## DEPENDENCY GRAPH & CRITICAL PATH

### Dependency Matrix

```
FAZA 1: Database Schema (CRITICAL PATH)
├─ 15 migrations (sequential order)
├─ 10 seeders (attribute groups, features, feature sets)
└─ BLOCKS: FAZA 2, 3, 4, 5, 6, 7

FAZA 2: Model Extensions (CRITICAL PATH)
├─ DEPENDS ON: FAZA 1 (migrations)
├─ Extends ProductVariant, Product models
├─ Creates 8 new models
└─ BLOCKS: FAZA 3, 4, 5, 6

FAZA 3: Service Layer (CRITICAL PATH)
├─ DEPENDS ON: FAZA 2 (models)
├─ VariantManager, FeatureManager, CompatibilityManager
├─ AttributeTransformer, FeatureTransformer
└─ BLOCKS: FAZA 4, 5, 6

FAZA 4: UI Components (DEPENDS ON: 3)
├─ DEPENDS ON: FAZA 3 (services)
├─ ProductForm tabs, ProductList modals
├─ Compatibility Manager panel
└─ CAN RUN PARALLEL WITH: FAZA 5 (CSV)

FAZA 5: PrestaShop Transformers (DEPENDS ON: 3)
├─ DEPENDS ON: FAZA 3 (services)
├─ Variant sync, Feature sync, Compatibility export
└─ CAN RUN PARALLEL WITH: FAZA 4 (UI)

FAZA 6: CSV Import/Export (DEPENDS ON: 3)
├─ DEPENDS ON: FAZA 3 (services)
├─ Template generator, Import/Export engines
└─ CAN RUN PARALLEL WITH: FAZA 4, 5

FAZA 7: Performance + Testing (FINAL)
├─ DEPENDS ON: FAZA 1, 2, 3, 4, 5, 6 (ALL)
├─ Indexes, cache optimization, queue jobs
├─ Unit tests, Feature tests
└─ MUST BE LAST (testing requires complete system)
```

### Critical Path Sequence

**CRITICAL PATH (cannot be parallelized):**
```
FAZA 1 (Database) → FAZA 2 (Models) → FAZA 3 (Services)
  12-15h            8-10h               20-25h
```
**Total Critical Path: 40-50h**

**PARALLELIZABLE (after FAZA 3):**
```
FAZA 4 (UI) ─────────┐
FAZA 5 (PrestaShop) ─┼─→ FAZA 7 (Performance + Testing)
FAZA 6 (CSV) ────────┘
15-20h   12-15h   8-10h    10-15h
```
**Total Parallel Path: 45-50h (can overlap)**

**TOTAL PROJECT TIME: 77-97h** (sequential) OR **55-65h** (with parallelization)

---

## SZCZEGÓŁOWY PLAN IMPLEMENTACJI - 7 FAZ

---

## FAZA 1: DATABASE SCHEMA (12-15h) ⚠️ CRITICAL PATH

### 1.1 Objectives
- Stworzyć 15 migrations w poprawnej kolejności
- Seeders dla attribute groups, features, feature sets
- Zweryfikować schema integrity (foreign keys, indexes)
- Test migrations rollback

### 1.2 Deliverables

**Migrations (15 files):**
1. `YYYY_MM_DD_000001_create_attribute_groups_table.php` (1h)
2. `YYYY_MM_DD_000002_create_attribute_values_table.php` (1h)
3. `YYYY_MM_DD_000003_create_product_variant_attributes_table.php` (1h)
4. `YYYY_MM_DD_000004_create_product_variant_images_table.php` (1h)
5. `YYYY_MM_DD_000005_extend_product_variants_table.php` (0.5h)
6. `YYYY_MM_DD_000006_create_features_table.php` (1h)
7. `YYYY_MM_DD_000007_create_feature_sets_table.php` (1h)
8. `YYYY_MM_DD_000008_create_feature_set_items_table.php` (1h)
9. `YYYY_MM_DD_000009_create_product_features_table.php` (1h)
10. `YYYY_MM_DD_000010_create_vehicle_compatibility_table.php` (1h)
11. `YYYY_MM_DD_000011_create_vehicle_compatibility_cache_table.php` (1h)
12. `YYYY_MM_DD_000012_create_shop_vehicle_brands_table.php` (0.5h)
13. `YYYY_MM_DD_000013_create_prestashop_attribute_group_mappings_table.php` (0.5h)
14. `YYYY_MM_DD_000014_create_prestashop_attribute_value_mappings_table.php` (0.5h)
15. `YYYY_MM_DD_000015_create_prestashop_feature_mappings_table.php` (0.5h)

**Seeders (10 files):**
1. `AttributeGroupsSeeder.php` - Kolor, Rozmiar, Material, Pojemność (0.5h)
2. `AttributeValuesSeeder.php` - Czerwony, XXL, Bawełna, etc. (1h)
3. `FeaturesSeeder.php` - Model, Rok, Silnik, VIN, etc. (1h)
4. `FeatureSetsSeeder.php` - Pojazdy Elektryczne, Spalinowe, etc. (0.5h)
5. `FeatureSetItemsSeeder.php` - Mapowanie sets → features (1h)

**Total: 18h** (max 15h with optimizations)

### 1.3 Agent Delegation
- **laravel-expert** (PRIMARY) - Migrations architecture + foreign keys
- **Context7**: `/websites/laravel_12_x` (migrations best practices)
- **coding-style-agent** (REVIEW) - Verify naming conventions

### 1.4 Tasks Breakdown

**Task 1.1: Variants Extensions Migrations (4h)**
```
Files:
- 000001_create_attribute_groups_table.php
- 000002_create_attribute_values_table.php
- 000003_create_product_variant_attributes_table.php
- 000004_create_product_variant_images_table.php
- 000005_extend_product_variants_table.php

Schema Features:
- Foreign keys z ON DELETE CASCADE
- Indexes dla performance (attribute_group_id, is_active, slug)
- UNIQUE constraints (prevent duplicates)
- JSON columns dla validation_rules, predefined_values
```

**Task 1.2: Features System Migrations (4h)**
```
Files:
- 000006_create_features_table.php
- 000007_create_feature_sets_table.php
- 000008_create_feature_set_items_table.php
- 000009_create_product_features_table.php

Schema Features:
- FULLTEXT indexes dla searchable features
- Per-shop override (shop_id nullable)
- JSON validation_rules
- Unique constraints per product/feature/shop
```

**Task 1.3: Compatibility System Migrations (3.5h)**
```
Files:
- 000010_create_vehicle_compatibility_table.php
- 000011_create_vehicle_compatibility_cache_table.php
- 000012_create_shop_vehicle_brands_table.php

Schema Features:
- Many-to-many part ↔ vehicle
- Cache table (denormalization)
- JSON arrays dla models (original_models, replacement_models)
- Per-shop brand filtering
```

**Task 1.4: PrestaShop Mappings (1.5h)**
```
Files:
- 000013_create_prestashop_attribute_group_mappings_table.php
- 000014_create_prestashop_attribute_value_mappings_table.php
- 000015_create_prestashop_feature_mappings_table.php

Schema Features:
- Sync tracking (last_sync_at)
- Bidirectional sync direction
- Foreign keys do PPM tables + PrestaShop IDs
```

**Task 1.5: Seeders Implementation (5h)**
```
Seeders:
- AttributeGroupsSeeder (Kolor, Rozmiar, Material, Pojemność)
- AttributeValuesSeeder (Czerwony, XXL, 50cc, etc.)
- FeaturesSeeder (Model, Rok, Silnik, VIN, Typ paliwa)
- FeatureSetsSeeder (Pojazdy Elektryczne, Pojazdy Spalinowe)
- FeatureSetItemsSeeder (Mapowanie cech do zestawów)

Business Data:
- Realistic attribute values (color hex, sort order)
- VIN validation pattern (17 chars)
- Year range (1900-2030)
- Predefined fuel types
```

**Task 1.6: Migration Testing (2h)**
```
Tests:
- php artisan migrate (verify success)
- php artisan migrate:rollback (verify cleanup)
- Foreign key integrity tests
- Unique constraint tests
- Index existence verification
```

### 1.5 Success Criteria
- ✅ All 15 migrations run successfully
- ✅ No foreign key violations
- ✅ Indexes created correctly
- ✅ Rollback works without errors
- ✅ Seeders populate realistic test data
- ✅ Schema matches plan specification 100%

### 1.6 Risks & Mitigation
- **Risk**: Migration order dependencies break
  - **Mitigation**: Prefix with 000001-000015 (sequential)
- **Risk**: Foreign keys reference non-existent tables
  - **Mitigation**: Test each migration individually
- **Risk**: MySQL index size limits (767 bytes)
  - **Mitigation**: Use VARCHAR(255) max + utf8mb4

---

## FAZA 2: MODEL EXTENSIONS (8-10h) ⚠️ CRITICAL PATH

### 2.1 Objectives
- Extend ProductVariant, Product models
- Create 8 new Eloquent models
- Implement relationships (belongs-to, has-many, many-to-many)
- Business methods (getEffectiveImages, applyInheritance, etc.)

### 2.2 Deliverables

**New Models (8 files):**
1. `app/Models/AttributeGroup.php` (1h)
2. `app/Models/AttributeValue.php` (1h)
3. `app/Models/ProductVariantAttribute.php` (0.5h - pivot)
4. `app/Models/ProductVariantImage.php` (1h)
5. `app/Models/Feature.php` (1.5h)
6. `app/Models/FeatureSet.php` (1h)
7. `app/Models/FeatureSetItem.php` (0.5h - pivot)
8. `app/Models/ProductFeature.php` (1h)
9. `app/Models/VehicleCompatibility.php` (1.5h)
10. `app/Models/VehicleCompatibilityCache.php` (1h)
11. `app/Models/ShopVehicleBrand.php` (1h)

**Extended Models (3 files):**
1. `ProductVariant.php` - Add relationships + business methods (1.5h)
2. `Product.php` - Add features, compatibility relationships (1h)
3. `ProductShopData.php` - Minor extensions if needed (0.5h)

**Total: 14h** (max 10h with efficient coding)

### 2.3 Agent Delegation
- **laravel-expert** (PRIMARY) - Eloquent relationships + accessors/mutators
- **Context7**: `/websites/laravel_12_x` (Eloquent patterns, relationship optimization)
- **coding-style-agent** (REVIEW) - Code quality check

### 2.4 Tasks Breakdown

**Task 2.1: Attribute Models (3h)**
```php
// app/Models/AttributeGroup.php
- Relationships:
  - hasMany(AttributeValue::class)
  - belongsTo(ProductType::class) // optional per-type groups
- Scopes:
  - scopeGlobal(), scopeForProductType()
  - scopeActive()
- Accessors:
  - getIsColorAttribute() // special handling dla color groups

// app/Models/AttributeValue.php
- Relationships:
  - belongsTo(AttributeGroup::class)
  - belongsToMany(ProductVariant::class) via ProductVariantAttribute
- Scopes:
  - scopeForGroup(), scopeActive()
- Accessors:
  - getColorHexAttribute() // formatting
  - getFullNameAttribute() // "Kolor: Czerwony"
```

**Task 2.2: Feature Models (3h)**
```php
// app/Models/Feature.php
- Relationships:
  - belongsToMany(FeatureSet::class) via FeatureSetItem
  - hasMany(ProductFeature::class)
- Scopes:
  - scopeGlobal(), scopeSearchable(), scopeFilterable()
- Methods:
  - validateValue($value) // using validation_rules JSON
  - getPredefinedValues() // decode JSON

// app/Models/FeatureSet.php
- Relationships:
  - belongsToMany(Feature::class) via FeatureSetItem
  - belongsTo(ProductType::class)
- Scopes:
  - scopeDefault(), scopeActive()

// app/Models/ProductFeature.php
- Relationships:
  - belongsTo(Product::class)
  - belongsTo(Feature::class)
  - belongsTo(PrestaShopShop::class) // per-shop override
- Scopes:
  - scopeForProductAndShop($productId, $shopId)
```

**Task 2.3: Compatibility Models (2.5h)**
```php
// app/Models/VehicleCompatibility.php
- Relationships:
  - belongsTo(Product::class, 'part_product_id')
  - belongsTo(Product::class, 'vehicle_product_id')
  - belongsTo(PrestaShopShop::class)
  - belongsTo(User::class, 'verified_by')
- Scopes:
  - scopeForPartAndShop($partId, $shopId)
  - scopeByType($type) // 'original' | 'replacement'
  - scopeVerified()
- Methods:
  - markAsVerified(User $user)

// app/Models/VehicleCompatibilityCache.php
- Relationships:
  - belongsTo(Product::class, 'part_product_id')
  - belongsTo(PrestaShopShop::class)
- Casts:
  - 'original_models' => 'array'
  - 'original_ids' => 'array'
  - 'replacement_models' => 'array'
  - 'replacement_ids' => 'array'
  - 'all_models' => 'array'
```

**Task 2.4: ProductVariant Extensions (1.5h)**
```php
// app/Models/ProductVariant.php (extensions)

// NEW Relationships:
public function variantAttributes(): BelongsToMany
{
    return $this->belongsToMany(AttributeValue::class, 'product_variant_attributes')
                ->withPivot('attribute_group_id')
                ->withTimestamps();
}

public function variantImages(): HasMany
{
    return $this->hasMany(ProductVariantImage::class)
                ->orderBy('sort_order');
}

// NEW Business Methods:
public function getEffectiveImages(): Collection
{
    if ($this->inherit_images) {
        return $this->product->media;
    }
    return $this->variantImages->isNotEmpty()
        ? $this->variantImages
        : $this->product->media;
}

public function getEffectivePrices(): Collection
{
    return $this->inherit_prices
        ? $this->product->prices
        : $this->prices;
}

public function applyInheritanceRules(): void
{
    // Refresh effective data based on inherit_* flags
}
```

**Task 2.5: Product Extensions (1h)**
```php
// app/Models/Product.php (extensions)

// NEW Relationships:
public function features(): HasMany
{
    return $this->hasMany(ProductFeature::class);
}

public function featuresForShop(?int $shopId = null): HasMany
{
    return $this->features()
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->orWhereNull('shop_id');
}

public function compatibleVehicles(int $shopId): HasMany
{
    return $this->hasMany(VehicleCompatibility::class, 'part_product_id')
                ->where('shop_id', $shopId);
}

public function compatibleParts(int $shopId): HasMany
{
    return $this->hasMany(VehicleCompatibility::class, 'vehicle_product_id')
                ->where('shop_id', $shopId);
}

public function compatibilityCache(int $shopId): HasOne
{
    return $this->hasOne(VehicleCompatibilityCache::class, 'part_product_id')
                ->where('shop_id', $shopId);
}
```

**Task 2.6: Model Testing (1h)**
```php
// Test relationships w Tinker
$product = Product::first();
$product->variants; // should work
$variant = ProductVariant::first();
$variant->variantAttributes; // should work
$variant->getEffectiveImages(); // should return Collection

// Test scopes
AttributeValue::forGroup(1)->active()->get();
Feature::searchable()->get();
VehicleCompatibility::forPartAndShop(789, 1)->byType('original')->get();
```

### 2.5 Success Criteria
- ✅ All 11 new models created
- ✅ ProductVariant + Product extended
- ✅ All relationships work w Tinker
- ✅ Business methods return correct data
- ✅ Scopes filter correctly
- ✅ No N+1 query issues (eager loading ready)

### 2.6 Risks & Mitigation
- **Risk**: Circular dependencies (Product ↔ ProductVariant)
  - **Mitigation**: Use lazy loading dla relationships
- **Risk**: N+1 queries w getEffectiveImages
  - **Mitigation**: Eager load w service layer
- **Risk**: JSON casting fails dla null values
  - **Mitigation**: Default to [] w casts

---

## FAZA 3: SERVICE LAYER (20-25h) ⚠️ CRITICAL PATH

### 3.1 Objectives
- Implement 5 core services (VariantManager, FeatureManager, CompatibilityManager, 2x Transformers)
- Business logic extraction z controllers/Livewire
- Cache strategy dla compatibility
- Unit tests (80% coverage)

### 3.2 Deliverables

**Services (5 files - max 300 linii each):**
1. `app/Services/Products/VariantManager.php` (5-6h, ~250 linii)
2. `app/Services/Products/FeatureManager.php` (4-5h, ~200 linii)
3. `app/Services/Products/CompatibilityManager.php` (6-7h, ~280 linii)
4. `app/Services/PrestaShop/AttributeTransformer.php` (3-4h, ~220 linii)
5. `app/Services/PrestaShop/FeatureTransformer.php` (3-4h, ~200 linii)

**Unit Tests (5 files):**
1. `tests/Unit/Services/VariantManagerTest.php` (2h)
2. `tests/Unit/Services/CompatibilityManagerTest.php` (2h)
3. `tests/Unit/Services/FeatureManagerTest.php` (1.5h)
4. `tests/Unit/Services/AttributeTransformerTest.php` (1h)
5. `tests/Unit/Services/FeatureTransformerTest.php` (1h)

**Total: 27h** (max 25h with efficient coding)

### 3.3 Agent Delegation
- **laravel-expert** (PRIMARY) - Service architecture + business logic
- **prestashop-api-expert** (SECONDARY) - Transformers (AttributeTransformer, FeatureTransformer)
- **Context7**: `/websites/laravel_12_x` (service patterns, dependency injection)
- **coding-style-agent** (REVIEW) - 300-line limit enforcement

### 3.4 Tasks Breakdown

**Task 3.1: VariantManager Service (5-6h)**
```php
// app/Services/Products/VariantManager.php (~250 linii)

/**
 * CRITICAL: Zgodność z SKU_ARCHITECTURE_GUIDE.md
 * - SKU generation pattern: PARENT_SKU-attr1_slug-attr2_slug
 * - Unique SKU validation before creation
 * - Inheritance flags management
 */

public function createVariant(Product $parent, array $attributes, array $data = []): ProductVariant
{
    // 1. Validate attributes (exists, active)
    // 2. Generate SKU
    // 3. Check SKU uniqueness
    // 4. Create variant record
    // 5. Attach attributes (product_variant_attributes pivot)
    // 6. Apply inheritance rules
    // 7. Return variant with relationships loaded
}

public function generateVariantSKU(Product $parent, array $attributes): string
{
    // Pattern: PARENT_SKU-attr1_slug-attr2_slug
    // Example: KURTKA-001-czerwony-xxl
}

public function bulkCreateVariants(Product $parent, array $attributeGroups): Collection
{
    // Generate all combinations (cartesian product)
    // Example: [Kolor: [Czerwony, Niebieski], Rozmiar: [L, XL]]
    // => 4 variants
    // Use DB transaction
    // Chunk dla memory efficiency
}

public function getInheritedData(ProductVariant $variant): array
{
    // Return array:
    // [
    //   'images' => $variant->getEffectiveImages(),
    //   'prices' => $variant->getEffectivePrices(),
    //   'stock' => $variant->getEffectiveStock(),
    // ]
}

// CRITICAL: Max 250 linii - jeśli więcej, split to:
// - VariantManager (core CRUD)
// - VariantInheritanceService (inheritance logic)
// - VariantSKUGenerator (SKU generation)
```

**Task 3.2: FeatureManager Service (4-5h)**
```php
// app/Services/Products/FeatureManager.php (~200 linii)

public function applyFeatureSet(Product $product, FeatureSet $set, ?int $shopId = null): void
{
    // 1. Get all features from feature_set_items
    // 2. For each feature:
    //    - Create product_features record
    //    - Use default_value if exists
    //    - Respect shop_id (NULL = default)
    // 3. Return count of created features
}

public function setFeatureValue(Product $product, Feature $feature, $value, ?int $shopId = null): void
{
    // Validate value against feature.validation_rules JSON
    // Handle per-shop override:
    // - shop_id=NULL → default value
    // - shop_id=123 → override dla shop 123
    // UpdateOrCreate logic
}

public function getFeatureValue(Product $product, Feature $feature, ?int $shopId = null)
{
    // Priority:
    // 1. Check shop-specific override (shop_id = $shopId)
    // 2. Fallback to default (shop_id = NULL)
    // 3. Return null if not set
}

public function bulkUpdateFeatures(Collection $products, array $features, ?int $shopId = null): int
{
    // Mass update dla selected products
    // Use DB transaction
    // Return count of updated records
}

public function exportFeaturesToPrestaShop(Product $product, PrestaShopShop $shop): array
{
    // Get features dla product + shop
    // Handle multi-value features (Model: "YCF 50|YCF 88")
    // Return array ready dla FeatureTransformer
}
```

**Task 3.3: CompatibilityManager Service (6-7h)**
```php
// app/Services/Products/CompatibilityManager.php (~280 linii)

/**
 * MOST COMPLEX SERVICE - split if exceeds 300 linii
 */

public function addCompatibility(
    Product $part,
    Product $vehicle,
    string $type, // 'original' | 'replacement'
    int $shopId,
    ?array $meta = null
): void
{
    // 1. Validate business rules:
    //    - Part must be "Część zamiennicza" type
    //    - Vehicle must be "Pojazd" type
    //    - Type must be 'original' | 'replacement'
    //    - No duplicate (unique constraint)
    // 2. Create vehicle_compatibility record
    // 3. Refresh compatibility cache
    // 4. Auto-generate "Model" feature
}

public function refreshCompatibilityCache(Product $part, int $shopId): void
{
    // 1. Get original vehicles (type='original')
    // 2. Get replacement vehicles (type='replacement')
    // 3. Extract names + IDs to JSON arrays
    // 4. UpdateOrCreate vehicle_compatibility_cache
    // 5. Call autoGenerateModelFeature()
}

public function autoGenerateModelFeature(Product $part, int $shopId): void
{
    // 1. Get compatibility cache dla part + shop
    // 2. Merge original_models + replacement_models
    // 3. Unique, sort alphabetically
    // 4. Join with " | " separator
    // 5. Find Feature where slug='model'
    // 6. FeatureManager->setFeatureValue($part, $feature, $value, $shopId)
}

public function bulkAssignCompatibility(
    Collection $parts,
    Collection $vehicles,
    string $type,
    int $shopId
): int
{
    // Cartesian product: parts × vehicles
    // Example: 12 parts × 2 vehicles = 24 records
    // Use DB transaction
    // Batch insert (ignore duplicates)
    // Refresh cache dla all parts (batch)
    // Return count
}

public function validateCompatibility(Product $part, Product $vehicle, string $type): array
{
    // Business rules validation
    // Return array of errors (empty = valid)
    // [
    //   'part_type' => 'Part must be "Część zamiennicza"',
    //   'vehicle_type' => 'Vehicle must be "Pojazd"',
    // ]
}

// CRITICAL: Jeśli przekracza 300 linii, split to:
// - CompatibilityManager (core CRUD)
// - CompatibilityCacheService (cache refresh logic)
// - CompatibilityValidator (business rules)
```

**Task 3.4: AttributeTransformer Service (3-4h)**
```php
// app/Services/PrestaShop/AttributeTransformer.php (~220 linii)

/**
 * PPM Variants ↔ PrestaShop ps_attribute* transformation
 */

public function transformVariantToPrestaShop(ProductVariant $variant, PrestaShopShop $shop): array
{
    // 1. Get variant attributes (variantAttributes relationship)
    // 2. Map attribute_group_id → ps_attribute_group.id (via mappings table)
    // 3. Map attribute_value_id → ps_attribute.id (via mappings table)
    // 4. Return:
    // [
    //   'ps_product_attribute' => [
    //     'id_product' => ...,
    //     'reference' => $variant->sku,
    //     'quantity' => ...,
    //     'price' => ... (differential),
    //   ],
    //   'ps_product_attribute_combination' => [
    //     ['id_attribute' => 15], // Czerwony
    //     ['id_attribute' => 28], // XXL
    //   ],
    // ]
}

public function syncAttributeGroup(AttributeGroup $group, PrestaShopShop $shop): int
{
    // 1. Check if mapping exists (prestashop_attribute_group_mappings)
    // 2. If not: Create ps_attribute_group via PrestaShop API
    // 3. Store mapping (attribute_group_id → ps_attribute_group_id)
    // 4. Return ps_attribute_group.id
}

public function syncAttributeValue(AttributeValue $value, PrestaShopShop $shop): int
{
    // Similar to syncAttributeGroup
    // Create ps_attribute via API
    // Store mapping
    // Return ps_attribute.id
}
```

**Task 3.5: FeatureTransformer Service (3-4h)**
```php
// app/Services/PrestaShop/FeatureTransformer.php (~200 linii)

/**
 * PPM Features ↔ PrestaShop ps_feature* transformation
 */

public function transformFeaturesToPrestaShop(Product $product, PrestaShopShop $shop): array
{
    // 1. Get product features dla shop
    // 2. For each feature:
    //    a. Split multi-value ("YCF 50|YCF 88" → ["YCF 50", "YCF 88"])
    //    b. Map feature_id → ps_feature.id
    //    c. For each value: Create ps_feature_value
    // 3. Return:
    // [
    //   'ps_feature_product' => [
    //     ['id_feature' => 10, 'id_feature_value' => 120],
    //     ['id_feature' => 10, 'id_feature_value' => 121],
    //   ]
    // ]
}

public function splitMultiValueFeatures(array $features): array
{
    // Input: [
    //   ['feature_id' => 5, 'value' => 'YCF 50|YCF 88|Honda CRF50']
    // ]
    // Output: [
    //   ['feature_id' => 5, 'value' => 'YCF 50'],
    //   ['feature_id' => 5, 'value' => 'YCF 88'],
    //   ['feature_id' => 5, 'value' => 'Honda CRF50'],
    // ]
    // Split by " | " (space, pipe, space)
}

public function syncFeature(Feature $feature, PrestaShopShop $shop): int
{
    // Similar pattern to AttributeTransformer
    // Create ps_feature via API
    // Store mapping
}
```

**Task 3.6: Unit Tests (7.5h)**
```php
// tests/Unit/Services/VariantManagerTest.php
- test_create_variant_with_attributes()
- test_generate_variant_sku_pattern()
- test_bulk_create_variants_cartesian_product()
- test_inheritance_flags_respect()
- test_effective_data_retrieval()
- test_sku_uniqueness_validation()

// tests/Unit/Services/CompatibilityManagerTest.php
- test_add_compatibility_creates_record()
- test_refresh_compatibility_cache()
- test_auto_generate_model_feature()
- test_bulk_assign_compatibility_count()
- test_validate_compatibility_business_rules()
- test_prevent_duplicate_compatibility()

// tests/Unit/Services/FeatureManagerTest.php
- test_apply_feature_set_creates_records()
- test_set_feature_value_per_shop()
- test_get_feature_value_priority()
- test_bulk_update_features()

// tests/Unit/Services/AttributeTransformerTest.php
- test_transform_variant_to_prestashop_format()
- test_sync_attribute_group_creates_mapping()

// tests/Unit/Services/FeatureTransformerTest.php
- test_split_multi_value_features()
- test_transform_features_to_prestashop()
```

### 3.5 Success Criteria
- ✅ All 5 services implemented
- ✅ MAX 300 linii per file (split if needed)
- ✅ Unit tests 80% coverage
- ✅ All business logic tests pass
- ✅ No hardcoded values
- ✅ Cache strategy working dla compatibility

### 3.6 Risks & Mitigation
- **Risk**: CompatibilityManager exceeds 300 linii
  - **Mitigation**: Split to 3 services (Manager, CacheService, Validator)
- **Risk**: N+1 queries w bulk operations
  - **Mitigation**: Eager load relationships, batch operations
- **Risk**: PrestaShop API rate limiting
  - **Mitigation**: Queue jobs, retry logic

---

## FAZA 4: UI COMPONENTS (15-20h) - CAN PARALLELIZE z FAZA 5/6

### 4.1 Objectives
- Extend ProductForm z 4 nowymi tabs
- Extend ProductList z bulk modals
- Create dedicated Compatibility Manager panel
- Real-time validation + progress bars

### 4.2 Deliverables

**Livewire Components (8 files):**
1. `app/Http/Livewire/Products/Management/Tabs/VariantsTab.php` (4h)
2. `app/Http/Livewire/Products/Management/Tabs/FeaturesTab.php` (3h)
3. `app/Http/Livewire/Products/Management/Tabs/CompatibilityTab.php` (4h)
4. `app/Http/Livewire/Products/Management/Tabs/CompatiblePartsTab.php` (2h)
5. `app/Http/Livewire/Products/Modals/BulkCreateVariantsModal.php` (3h)
6. `app/Http/Livewire/Products/Modals/BulkAssignCompatibilityModal.php` (3h)
7. `app/Http/Livewire/Products/CompatibilityManager.php` (4h)

**Blade Views (8 files):**
1. `resources/views/livewire/products/management/tabs/variants-tab.blade.php` (2h)
2. `resources/views/livewire/products/management/tabs/features-tab.blade.php` (2h)
3. `resources/views/livewire/products/management/tabs/compatibility-tab.blade.php` (2h)
4. `resources/views/livewire/products/management/tabs/compatible-parts-tab.blade.php` (1h)
5. `resources/views/livewire/products/modals/bulk-create-variants-modal.blade.php` (1.5h)
6. `resources/views/livewire/products/modals/bulk-assign-compatibility-modal.blade.php` (1.5h)
7. `resources/views/livewire/products/compatibility-manager.blade.php` (2h)

**Total: 35h** (max 20h with reusable components)

### 4.3 Agent Delegation
- **livewire-specialist** (PRIMARY) - Livewire components + lifecycle
- **frontend-specialist** (SECONDARY) - Blade templates + CSS
- **Context7**: `/livewire/livewire`, `/alpinejs/alpine`
- **coding-style-agent** (REVIEW) - Wire:key, wire:poll best practices

### 4.4 Tasks Breakdown

**Task 4.1: VariantsTab Component (6h)**
```php
// app/Http/Livewire/Products/Management/Tabs/VariantsTab.php

public $product; // Product
public $variants; // Collection<ProductVariant>
public $inheritImages = true;
public $inheritPrices = true;
public $inheritStock = true;

// Dependency injection dla services
public function boot(VariantManager $variantManager)
{
    $this->variantManager = $variantManager;
}

public function mount(Product $product)
{
    $this->product = $product;
    $this->loadVariants();
}

public function openGeneratorModal()
{
    $this->emit('openModal', 'products.modals.bulk-create-variants-modal', [
        'productId' => $this->product->id,
    ]);
}

public function deleteVariant(int $variantId)
{
    $variant = ProductVariant::findOrFail($variantId);
    $this->variantManager->deleteVariant($variant);
    $this->loadVariants();
    $this->dispatch('variant-deleted');
}

public function updateInheritance()
{
    // Update product.variants().update([...])
    $this->dispatch('inheritance-updated');
}

// UI Features:
// - Lista wariantów z SKU, atrybuty, stock, cena
// - Generator kombinacji (modal)
// - Edycja inline (Alpine.js)
// - Checkboxes dziedziczenia
```

**Task 4.2: FeaturesTab Component (5h)**
```php
// app/Http/Livewire/Products/Management/Tabs/FeaturesTab.php

public $product;
public $selectedFeatureSet;
public $selectedShopId = null; // null = default
public $features = []; // ['feature_id' => 'value', ...]

public function boot(FeatureManager $featureManager)
{
    $this->featureManager = $featureManager;
}

public function mount(Product $product)
{
    $this->product = $product;
    $this->selectedFeatureSet = $product->productType->defaultFeatureSet;
    $this->loadFeatures();
}

public function applyFeatureSet()
{
    $this->featureManager->applyFeatureSet(
        $this->product,
        $this->selectedFeatureSet,
        $this->selectedShopId
    );
    $this->loadFeatures();
    $this->dispatch('feature-set-applied');
}

public function updateFeature($featureId, $value)
{
    $feature = Feature::findOrFail($featureId);
    $this->featureManager->setFeatureValue(
        $this->product,
        $feature,
        $value,
        $this->selectedShopId
    );
}

public function changeShop($shopId)
{
    $this->selectedShopId = $shopId;
    $this->loadFeatures();
}

// UI Features:
// - Dropdown feature set selector
// - Dropdown shop selector (default + all shops)
// - Dynamic form fields based on feature_type
// - Validation rules enforcement
// - Required field indicators
```

**Task 4.3: CompatibilityTab Component (6h)**
```php
// app/Http/Livewire/Products/Management/Tabs/CompatibilityTab.php

public $product; // Część zamiennicza
public $selectedShopId;
public $compatibilityType = 'original'; // 'original' | 'replacement'
public $originalVehicles;
public $replacementVehicles;
public $availableVehicles;
public $searchQuery = '';

public function boot(CompatibilityManager $compatibilityManager)
{
    $this->compatibilityManager = $compatibilityManager;
}

public function mount(Product $product)
{
    $this->product = $product;
    $this->loadCompatibility();
    $this->loadAvailableVehicles();
}

public function addCompatibility(array $vehicleIds)
{
    foreach ($vehicleIds as $vehicleId) {
        $vehicle = Product::findOrFail($vehicleId);
        $this->compatibilityManager->addCompatibility(
            $this->product,
            $vehicle,
            $this->compatibilityType,
            $this->selectedShopId
        );
    }
    $this->loadCompatibility();
    $this->dispatch('compatibility-added');
}

public function removeCompatibility(int $vehicleId)
{
    $vehicle = Product::findOrFail($vehicleId);
    $this->compatibilityManager->removeCompatibility(
        $this->product,
        $vehicle,
        $this->selectedShopId
    );
    $this->loadCompatibility();
    $this->dispatch('compatibility-removed');
}

public function loadAvailableVehicles()
{
    $shop = PrestaShopShop::find($this->selectedShopId);
    $allowedBrands = $shop->vehicleBrands()->pluck('brand_name');

    $this->availableVehicles = Product::byType('pojazd')
        ->whereIn('manufacturer', $allowedBrands)
        ->when($this->searchQuery, fn($q) => $q->where('name', 'like', "%{$this->searchQuery}%"))
        ->orderBy('manufacturer')
        ->orderBy('name')
        ->get();
}

// UI Features:
// - Radio buttons Oryginał/Zamiennik
// - Shop selector
// - Search pojazdu (real-time)
// - Lista przypisanych (grouped by type)
// - Auto-generated "Model" preview
// - Bulk operations button
```

**Task 4.4: Bulk Modals (7h total)**
```php
// app/Http/Livewire/Products/Modals/BulkCreateVariantsModal.php (3h)
// - Multiple products selection display
// - Attribute group checkboxes
// - Attribute value checkboxes (per group)
// - Combination count calculator
// - Progress bar (JobProgressService)
// - Dispatch job: BulkCreateVariants

// app/Http/Livewire/Products/Modals/BulkAssignCompatibilityModal.php (3h)
// - Multiple parts selection display
// - Shop selector
// - Type selector (Oryginał/Zamiennik)
// - Vehicle checkboxes (brand filtered)
// - Assignment count preview
// - Progress bar
// - Dispatch job: BulkAssignCompatibility
```

**Task 4.5: Compatibility Manager Panel (6h)**
```php
// app/Http/Livewire/Products/CompatibilityManager.php
// - Matrix view (parts × vehicles)
// - Toggle checkboxes (bulk save)
// - Filter by shop, type, brand
// - Copy compatibility między sklepami
// - Export to CSV
// - Performance optimization (eager loading, cache)

// UI:
// - DataTables-style table
// - Sticky headers
// - Bulk save button
// - Copy modal
```

### 4.5 Success Criteria
- ✅ All tabs functional w ProductForm
- ✅ Bulk modals dispatch queue jobs
- ✅ Real-time validation works
- ✅ Progress bars show accurate status
- ✅ Compatibility Manager matrix editable
- ✅ No wire:snapshot issues
- ✅ All wire:key unique

### 4.6 Risks & Mitigation
- **Risk**: Livewire lifecycle issues (mount/hydrate)
  - **Mitigation**: Follow _ISSUES_FIXES patterns
- **Risk**: Alpine.js conflicts z wire:model
  - **Mitigation**: Use $wire.set() for Alpine updates
- **Risk**: Performance issues w Compatibility Manager
  - **Mitigation**: Eager load, pagination, cache

---

## FAZA 5: PRESTASHOP INTEGRATION (12-15h) - CAN PARALLELIZE z FAZA 4/6

### 5.1 Objectives
- Implement variant sync (ps_product_attribute)
- Implement feature sync (ps_feature_product)
- Handle multi-value features split
- Bidirectional sync support

### 5.2 Deliverables

**Sync Strategies (3 files):**
1. `app/Services/PrestaShop/Sync/VariantSyncStrategy.php` (5h)
2. `app/Services/PrestaShop/Sync/FeatureSyncStrategy.php` (4h)
3. `app/Services/PrestaShop/Sync/CompatibilitySyncStrategy.php` (3h)

**Queue Jobs (3 files):**
1. `app/Jobs/PrestaShop/SyncVariantToPrestaShop.php` (1.5h)
2. `app/Jobs/PrestaShop/SyncFeaturesToPrestaShop.php` (1.5h)
3. `app/Jobs/PrestaShop/SyncCompatibilityToPrestaShop.php` (1h)

**Total: 16h** (max 15h with efficient coding)

### 5.3 Agent Delegation
- **prestashop-api-expert** (PRIMARY) - PrestaShop API calls + transformations
- **laravel-expert** (SECONDARY) - Queue jobs architecture
- **Context7**: `/prestashop/docs` (PrestaShop 8.x/9.x API)

### 5.4 Tasks Breakdown

**Task 5.1: VariantSyncStrategy (5h)**
```php
// app/Services/PrestaShop/Sync/VariantSyncStrategy.php

public function syncToPrestaShop(ProductVariant $variant, PrestaShopShop $shop): array
{
    // 1. Transform variant to PrestaShop format (AttributeTransformer)
    // 2. Sync attribute groups (if new)
    // 3. Sync attribute values (if new)
    // 4. Create/update ps_product_attribute
    // 5. Create ps_product_attribute_combination records
    // 6. Create ps_product_attribute_shop (multi-store)
    // 7. Update ps_product.cache_default_attribute
    // 8. Return sync result + ps_product_attribute.id
}

public function syncFromPrestaShop(array $psData, PrestaShopShop $shop): ProductVariant
{
    // Reverse transformation (PrestaShop → PPM)
    // Handle existing variants (update vs create)
}
```

**Task 5.2: FeatureSyncStrategy (4h)**
```php
// app/Services/PrestaShop/Sync/FeatureSyncStrategy.php

public function syncToPrestaShop(Product $product, PrestaShopShop $shop): array
{
    // 1. Get product features dla shop (FeatureManager)
    // 2. Split multi-value features (FeatureTransformer)
    // 3. For each feature:
    //    a. Sync feature definition (ps_feature)
    //    b. For each value:
    //       - Create ps_feature_value (if new)
    //       - Create ps_feature_product record
    // 4. Return sync result + count
}

public function handleMultiValueFeatures(array $features): array
{
    // Delegate to FeatureTransformer->splitMultiValueFeatures()
    // Example: "YCF 50|YCF 88" → 2 records
}
```

**Task 5.3: CompatibilitySyncStrategy (3h)**
```php
// app/Services/PrestaShop/Sync/CompatibilitySyncStrategy.php

public function syncToPrestaShop(Product $part, PrestaShopShop $shop): array
{
    // 1. Get compatibility cache dla part + shop
    // 2. Transform to features:
    //    - Create "Oryginał" feature → original_models
    //    - Create "Zamiennik" feature → replacement_models
    //    - Create "Model" feature → all_models
    // 3. Use FeatureSyncStrategy dla actual sync
    // 4. Return sync result
}
```

**Task 5.4: Queue Jobs (4h)**
```php
// app/Jobs/PrestaShop/SyncVariantToPrestaShop.php
class SyncVariantToPrestaShop implements ShouldQueue
{
    public function __construct(
        public ProductVariant $variant,
        public PrestaShopShop $shop
    ) {}

    public function handle(VariantSyncStrategy $strategy): void
    {
        try {
            $result = $strategy->syncToPrestaShop($this->variant, $this->shop);
            Log::info('Variant synced', $result);
        } catch (\Exception $e) {
            Log::error('Variant sync failed', ['error' => $e->getMessage()]);
            throw $e; // Retry w queue
        }
    }
}

// Similar dla SyncFeaturesToPrestaShop, SyncCompatibilityToPrestaShop
```

### 5.5 Success Criteria
- ✅ Variants sync to ps_product_attribute correctly
- ✅ Features sync to ps_feature_product correctly
- ✅ Multi-value features split correctly
- ✅ Compatibility exported as features
- ✅ Queue jobs handle errors gracefully
- ✅ PrestaShop 8.x/9.x compatibility verified

### 5.6 Risks & Mitigation
- **Risk**: PrestaShop API rate limiting
  - **Mitigation**: Queue jobs z retry, backoff strategy
- **Risk**: ps_attribute ID conflicts
  - **Mitigation**: Mappings table, check before create
- **Risk**: Multi-value split inconsistent
  - **Mitigation**: Unit tests dla all split scenarios

---

## FAZA 6: CSV IMPORT/EXPORT (8-10h) - CAN PARALLELIZE z FAZA 4/5

### 6.1 Objectives
- Template generator dla POJAZDY/CZĘŚCI
- Advanced column mapping UI
- Compatibility parsing (Original|Replacement)
- Export human-readable + PrestaShop format

### 6.2 Deliverables

**Services (3 files):**
1. `app/Services/CSV/TemplateGenerator.php` (2h)
2. `app/Services/CSV/ImportEngine.php` (3h)
3. `app/Services/CSV/ExportEngine.php` (3h)

**Livewire Components (2 files):**
1. `app/Http/Livewire/CSV/ImportWizard.php` (2h)
2. `app/Http/Livewire/CSV/ExportWizard.php` (1h)

**Total: 11h** (max 10h with reuse)

### 6.3 Agent Delegation
- **import-export-specialist** (PRIMARY) - CSV logic
- **laravel-expert** (SECONDARY) - Laravel-Excel integration
- **livewire-specialist** (SECONDARY) - Wizard UI

### 6.4 Tasks Breakdown

**Task 6.1: TemplateGenerator (2h)**
```php
// app/Services/CSV/TemplateGenerator.php

public function generateTemplate(string $type): array
{
    // $type = 'POJAZDY' | 'CZĘŚCI'
    // Return CSV template z headers + sample rows
    // Columns:
    // - SKU, Name, Category, Price, Stock
    // - CZĘŚCI: Original (pipes), Replacement (pipes)
    // - POJAZDY: Model, Rok, Silnik, VIN, etc.
}

public function getAvailableColumns(string $type): array
{
    // Return all possible columns dla mapping
}
```

**Task 6.2: ImportEngine (3h)**
```php
// app/Services/CSV/ImportEngine.php

public function importFromFile(UploadedFile $file, array $columnMapping, int $shopId): array
{
    // 1. Parse CSV (Laravel-Excel)
    // 2. Map columns based on $columnMapping
    // 3. For each row:
    //    a. Find or create Product (by SKU)
    //    b. Parse compatibility ("YCF 50|YCF 88" → addCompatibility calls)
    //    c. Parse features
    //    d. Create variants if attributes present
    // 4. Return result:
    // [
    //   'imported' => 150,
    //   'updated' => 20,
    //   'errors' => [...],
    // ]
}

public function parseCompatibility(string $value, string $type): array
{
    // Split by "|"
    // Find vehicles by name
    // Return vehicle IDs + type
}
```

**Task 6.3: ExportEngine (3h)**
```php
// app/Services/CSV/ExportEngine.php

public function exportProducts(Collection $products, string $format, int $shopId): string
{
    // $format = 'human_readable' | 'prestashop'
    //
    // human_readable:
    // - SKU, Name, Category, Price, Stock
    // - Original: "YCF 50 | YCF 88"
    // - Replacement: "Honda CRF50"
    // - Model: "YCF 50 | YCF 88 | Honda CRF50" (auto-generated)
    //
    // prestashop:
    // - PrestaShop import format
    // - Features as separate columns
    // - Compatibility as features
    //
    // Return CSV string (download)
}
```

**Task 6.4: Wizard UI (3h)**
```php
// app/Http/Livewire/CSV/ImportWizard.php
// - Step 1: Upload CSV file
// - Step 2: Map columns (drag-drop or dropdowns)
// - Step 3: Preview (first 5 rows)
// - Step 4: Import (progress bar)
// - Step 5: Results (success/errors summary)

// app/Http/Livewire/CSV/ExportWizard.php
// - Step 1: Select products (filters)
// - Step 2: Select format
// - Step 3: Column selection (custom)
// - Step 4: Download
```

### 6.5 Success Criteria
- ✅ Template generator creates realistic samples
- ✅ Import handles compatibility parsing
- ✅ Export works dla both formats
- ✅ Wizard UI intuitive
- ✅ Column mapping flexible
- ✅ Import/export roundtrip successful

### 6.6 Risks & Mitigation
- **Risk**: Large CSV files timeout
  - **Mitigation**: Queue jobs, chunking
- **Risk**: Compatibility parsing ambiguous
  - **Mitigation**: Strict format, validation errors
- **Risk**: Column mapping complex
  - **Mitigation**: Save mapping templates

---

## FAZA 7: PERFORMANCE + TESTING (10-15h) ⚠️ FINAL PHASE

### 7.1 Objectives
- Database indexes optimization
- Compatibility cache verification
- Bulk operations performance testing
- Complete test suite (100+ tests)
- Security audit

### 7.2 Deliverables

**Performance:**
1. Database indexes review + optimization (2h)
2. Compatibility cache benchmarks (2h)
3. Bulk operations load testing (2h)

**Testing:**
1. Complete unit test suite (80% coverage) (4h)
2. Complete feature test suite (3h)
3. Integration tests (PrestaShop sync) (2h)

**Total: 15h**

### 7.3 Agent Delegation
- **laravel-expert** (PRIMARY) - Performance optimization
- **debugger** (SECONDARY) - Test suite completion
- **coding-style-agent** (REVIEW) - Final code review

### 7.4 Tasks Breakdown

**Task 7.1: Database Indexes (2h)**
```sql
-- Critical indexes verification
CREATE INDEX idx_part_shop ON vehicle_compatibility(part_product_id, shop_id);
CREATE INDEX idx_product_shop ON product_features(product_id, shop_id);
CREATE INDEX idx_lookup ON product_variant_attributes(product_variant_id, attribute_group_id);
CREATE FULLTEXT INDEX ft_value ON product_features(value);

-- Analyze slow queries
EXPLAIN SELECT ...;
```

**Task 7.2: Performance Benchmarks (4h)**
```php
// Benchmark targets:
// - Compatibility query (10K products): <500ms
// - Variant creation: <100ms per variant
// - Bulk variant generation (1000 products × 9): <5 min
// - CSV import (5000 rows): <2 min
// - PrestaShop sync (1000 products): <10 min

// Tools:
// - Laravel Telescope
// - Query log analysis
// - Stress testing (Apache Bench)
```

**Task 7.3: Test Suite Completion (9h)**
```
tests/Unit/ (50+ tests)
tests/Feature/ (50+ tests)
tests/Integration/ (10+ tests)

Coverage targets:
- Services: 80%+ (critical business logic)
- Models: 60%+ (relationships)
- Controllers/Livewire: 50%+ (happy path)

PHPUnit + Laravel Dusk dla browser tests
```

### 7.5 Success Criteria
- ✅ All performance benchmarks met
- ✅ 100+ tests passing
- ✅ 80% code coverage (services)
- ✅ No N+1 queries detected
- ✅ Security audit passed
- ✅ Load testing (100 concurrent users) successful

### 7.6 Risks & Mitigation
- **Risk**: Performance targets not met
  - **Mitigation**: Add more indexes, cache, optimize queries
- **Risk**: Test coverage low
  - **Mitigation**: Prioritize critical paths first
- **Risk**: PrestaShop sync slow
  - **Mitigation**: Batch operations, async jobs

---

## AGENT DELEGATION MATRIX

### Pełna macierz agent → faza

| Agent                    | FAZA 1 | FAZA 2 | FAZA 3 | FAZA 4 | FAZA 5 | FAZA 6 | FAZA 7 |
|--------------------------|--------|--------|--------|--------|--------|--------|--------|
| **architect**            | PLAN   | REVIEW | REVIEW | -      | -      | -      | FINAL  |
| **laravel-expert**       | PRIMARY| PRIMARY| PRIMARY| ASSIST | ASSIST | ASSIST | PRIMARY|
| **livewire-specialist**  | -      | -      | -      | PRIMARY| -      | ASSIST | -      |
| **prestashop-api-expert**| -      | -      | ASSIST | -      | PRIMARY| -      | -      |
| **frontend-specialist**  | -      | -      | -      | ASSIST | -      | -      | -      |
| **import-export-specialist**| -   | -      | -      | -      | -      | PRIMARY| -      |
| **debugger**             | ASSIST | ASSIST | ASSIST | ASSIST | ASSIST | ASSIST | ASSIST |
| **coding-style-agent**   | REVIEW | REVIEW | REVIEW | REVIEW | REVIEW | REVIEW | REVIEW |
| **documentation-reader** | ASSIST | ASSIST | ASSIST | ASSIST | ASSIST | ASSIST | -      |

### Workflow pattern per faza

**Standard Pattern:**
```
1. documentation-reader → Read SKU_ARCHITECTURE_GUIDE.md, CLAUDE.md
2. Context7 → Fetch latest Laravel/Livewire/PrestaShop patterns
3. [PRIMARY AGENT] → Implement
4. debugger → Test (if issues)
5. coding-style-agent → Review (MANDATORY)
6. architect → Approve + update plan
```

**Example: FAZA 3 Task 3.1 (VariantManager)**
```
Step 1: documentation-reader
  - Read: SKU_ARCHITECTURE_GUIDE.md
  - Extract: SKU generation rules, uniqueness requirements

Step 2: Context7
  - Library: /websites/laravel_12_x
  - Topic: "service layer patterns dependency injection"
  - Extract: Best practices dla service architecture

Step 3: laravel-expert (PRIMARY)
  - Implement: VariantManager.php (~250 linii)
  - Methods: createVariant, generateVariantSKU, bulkCreateVariants
  - Test: Unit tests dla SKU uniqueness

Step 4: debugger (IF ISSUES)
  - Debug: SKU collision scenarios
  - Fix: Add retry logic z incremental suffix

Step 5: coding-style-agent (MANDATORY)
  - Review: Max 300 linii? ✅
  - Review: No hardcoding? ✅
  - Review: Separation of concerns? ✅
  - Review: Naming conventions? ✅

Step 6: architect
  - Approve: ✅
  - Update Plan_Projektu/ETAP_05a_Produkty.md:
    - 3.1 VariantManager → ✅
    - └── PLIK: app/Services/Products/VariantManager.php
```

---

## TIMELINE ESTIMATE - SZCZEGÓŁOWY BREAKDOWN

### Scenariusz SEQUENTIAL (wszystkie fazy po kolei)

```
FAZA 1: Database Schema           12-15h  (Days 1-2)
├─ Migrations                      12h
├─ Seeders                         5h
└─ Testing                         2h

FAZA 2: Model Extensions           8-10h   (Day 3)
├─ New models                      8h
├─ Model extensions                3h
└─ Testing                         1h

FAZA 3: Service Layer              20-25h  (Days 4-6)
├─ VariantManager                  6h
├─ FeatureManager                  5h
├─ CompatibilityManager            7h
├─ AttributeTransformer            4h
├─ FeatureTransformer              4h
└─ Unit tests                      7h

FAZA 4: UI Components              15-20h  (Days 7-9)
├─ ProductForm tabs                13h
├─ Bulk modals                     7h
├─ Compatibility Manager panel     6h
└─ Blade views                     10h

FAZA 5: PrestaShop Integration     12-15h  (Days 10-11)
├─ Sync strategies                 12h
├─ Queue jobs                      4h
└─ Testing                         2h

FAZA 6: CSV Import/Export          8-10h   (Day 12)
├─ Services                        8h
├─ Wizard UI                       3h
└─ Testing                         1h

FAZA 7: Performance + Testing      10-15h  (Days 13-14)
├─ Performance optimization        6h
├─ Test suite completion           9h
└─ Final review                    2h

TOTAL: 85-110h (11-14 dni roboczych przy 8h/dzień)
```

### Scenariusz PARALLELIZED (optymalizacja critical path)

```
Week 1:
├─ Day 1-2: FAZA 1 (Database)       15h     ⚠️ CRITICAL
├─ Day 3:   FAZA 2 (Models)         10h     ⚠️ CRITICAL
└─ Day 4-6: FAZA 3 (Services)       25h     ⚠️ CRITICAL

Week 2:
├─ Day 7-8: FAZA 4 (UI) ─────────┐  20h
├─ Day 7-8: FAZA 5 (PrestaShop) ─┤  15h    ⚠️ PARALLEL
└─ Day 9:   FAZA 6 (CSV) ─────────┘  10h

Week 3:
└─ Day 10-11: FAZA 7 (Performance)  15h     ⚠️ FINAL

TOTAL: 55-65h (7-8 dni roboczych z parallelization)
```

### Resource Requirements

**Single Developer (Sequential):**
- Timeline: 11-14 dni roboczych (2-3 tygodnie)
- Effort: 85-110h
- Risk: High (single point of failure)

**Team of 3 (Parallelized):**
- Timeline: 7-8 dni roboczych (1.5 tygodnie)
- Effort: 110h total (distributed)
- Team:
  - Dev 1 (Backend): FAZA 1, 2, 3 (critical path)
  - Dev 2 (Frontend): FAZA 4 (UI components)
  - Dev 3 (Integration): FAZA 5, 6 (PrestaShop + CSV)
- Risk: Medium (dependencies managed)

**Recommended Approach:**
- **Single Developer**: Sequential (safer, 2.5 tygodnie)
- **Team**: Parallelized (faster, 1.5 tygodnie)

---

## RISK MITIGATION STRATEGIES

### Critical Risks

**1. Database Schema Dependencies (FAZA 1)**
- **Risk**: Migration order breaks, foreign keys fail
- **Impact**: HIGH (blocks all subsequent phases)
- **Mitigation**:
  - Prefix migrations 000001-000015 (sequential)
  - Test each migration individually before batch run
  - Keep rollback tested dla quick recovery
- **Contingency**: If migration fails, revert all, fix, re-run

**2. Service Layer Complexity (FAZA 3)**
- **Risk**: Services exceed 300 linii, hard to maintain
- **Impact**: MEDIUM (technical debt)
- **Mitigation**:
  - Split services proactively (VariantManager → VariantInheritanceService)
  - Review at 250 linii, split at 280 linii
  - Use Context7 dla enterprise patterns
- **Contingency**: Refactor to multiple services, update delegation

**3. PrestaShop API Rate Limiting (FAZA 5)**
- **Risk**: Sync fails due to too many requests
- **Impact**: HIGH (sync unreliable)
- **Mitigation**:
  - Queue jobs z retry logic
  - Exponential backoff (1s, 2s, 4s, 8s)
  - Batch operations (combine multiple calls)
- **Contingency**: Implement rate limiter w BasePrestaShopClient

**4. Performance Targets Not Met (FAZA 7)**
- **Risk**: Compatibility queries >500ms, bulk ops timeout
- **Impact**: MEDIUM (user experience degraded)
- **Mitigation**:
  - Compatibility cache MANDATORY (not optional)
  - Indexes on all foreign keys
  - Eager loading w relationships
  - Queue jobs dla bulk operations
- **Contingency**: Add Redis cache layer, denormalize more data

**5. Livewire Lifecycle Issues (FAZA 4)**
- **Risk**: wire:snapshot, wire:poll, wire:key issues
- **Impact**: MEDIUM (UI bugs)
- **Mitigation**:
  - Follow _ISSUES_FIXES patterns strictly
  - Unique wire:key dla all loops
  - No wire:poll inside @if
  - Test all components w browser
- **Contingency**: Use documented patterns from _ISSUES_FIXES

### Medium Risks

**6. CSV Import Ambiguity**
- **Risk**: Compatibility parsing fails dla edge cases
- **Mitigation**: Strict format validation, clear error messages
- **Contingency**: Manual import assistant dla errors

**7. Test Coverage Low**
- **Risk**: Bugs in production
- **Mitigation**: Prioritize critical path tests first
- **Contingency**: Post-deployment testing + hot-fixes

**8. Context7 API Unavailable**
- **Risk**: No access to latest patterns
- **Mitigation**: Fallback to local docs (CLAUDE.md, _DOCS/)
- **Contingency**: Use Laravel/Livewire official docs

---

## SUCCESS CRITERIA - DETAILED CHECKLIST

### Functional Requirements

**Variants System:**
- [ ] Create variant z attribute groups (Kolor, Rozmiar)
- [ ] Generate SKU pattern: PARENT_SKU-attr1-attr2
- [ ] Validate SKU uniqueness
- [ ] Bulk create variants (cartesian product)
- [ ] Inheritance flags working (images, prices, stock)
- [ ] Variant images upload (optional own images)
- [ ] ProductForm tab "Warianty" functional

**Features System:**
- [ ] Create features (Model, Rok, Silnik, VIN)
- [ ] Feature sets dla product types
- [ ] Apply feature set to product
- [ ] Per-shop feature override working
- [ ] Validation rules enforced (VIN pattern, year range)
- [ ] ProductForm tab "Cechy produktu" functional
- [ ] Bulk update features dla multiple products

**Compatibility System:**
- [ ] Add compatibility (part → vehicle, Oryginał/Zamiennik)
- [ ] Per-shop compatibility working
- [ ] Brand filtering working
- [ ] Compatibility cache refreshes automatically
- [ ] Auto-generate "Model" feature (Original + Replacement)
- [ ] ProductForm tabs "Dopasowania" + "Pasujące części" functional
- [ ] Compatibility Manager panel (matrix view) working
- [ ] Bulk assign compatibility dla multiple parts

**PrestaShop Integration:**
- [ ] Variants sync to ps_product_attribute
- [ ] Attribute groups/values sync to ps_attribute*
- [ ] Features sync to ps_feature_product
- [ ] Multi-value features split correctly ("A|B|C" → 3 records)
- [ ] Compatibility exported as features
- [ ] Bidirectional sync working
- [ ] Queue jobs handle errors gracefully

**CSV System:**
- [ ] Template generator creates realistic samples
- [ ] Import wizard (5 steps) working
- [ ] Column mapping flexible
- [ ] Compatibility parsing working ("YCF 50|YCF 88")
- [ ] Export human-readable format
- [ ] Export PrestaShop format
- [ ] Import/export roundtrip successful

### Non-Functional Requirements

**Performance:**
- [ ] Compatibility query (10K products): <500ms (with cache)
- [ ] Variant creation: <100ms per variant
- [ ] Bulk variant generation (1000 × 9): <5 min
- [ ] CSV import (5000 rows): <2 min
- [ ] PrestaShop sync (1000 products): <10 min
- [ ] No N+1 queries detected

**Code Quality:**
- [ ] All services MAX 300 linii per file
- [ ] No hardcoded values
- [ ] Separation of concerns enforced
- [ ] Context7 integration used before coding
- [ ] All coding-style-agent reviews passed
- [ ] SKU_ARCHITECTURE_GUIDE.md followed

**Testing:**
- [ ] 100+ tests total (unit + feature)
- [ ] 80% code coverage (services)
- [ ] All business logic tests passing
- [ ] Integration tests (PrestaShop) passing
- [ ] Load testing (100 concurrent users) successful

**Security:**
- [ ] SQL injection prevention (parameterized queries)
- [ ] Input validation dla all user inputs
- [ ] Business rules validation (Oryginał ≠ Zamiennik)
- [ ] Foreign key constraints enforced
- [ ] Unique constraints prevent duplicates

**Usability:**
- [ ] UI intuitive (no training needed)
- [ ] Real-time validation working
- [ ] Progress bars accurate
- [ ] Error messages clear
- [ ] Help texts helpful

---

## POST-DEPLOYMENT MONITORING

### Week 1 - Critical Monitoring

**Daily Tasks:**
- [ ] Check Laravel logs dla errors (storage/logs/laravel.log)
- [ ] Monitor queue jobs failures (failed_jobs table)
- [ ] Verify PrestaShop sync success rate (>98%)
- [ ] Track performance metrics (compatibility queries)
- [ ] Collect user feedback (admin dashboard)

**Alerts:**
- [ ] Setup alert dla queue job failures >5%
- [ ] Setup alert dla sync errors >2%
- [ ] Setup alert dla slow queries >1s

### Week 2-4 - Optimization

**Performance Tuning:**
- [ ] Analyze slow query logs
- [ ] Add missing indexes (if needed)
- [ ] Optimize eager loading relationships
- [ ] Review cache hit rate

**User Training:**
- [ ] Create video tutorials (5 videos)
- [ ] User guide updates
- [ ] FAQ based on feedback

**Bug Fixes:**
- [ ] Hot-fix dla critical bugs (same day)
- [ ] Minor bug fixes (weekly batch)

### Month 2+ - Enhancement

**Feature Enhancements:**
- [ ] Advanced reporting (popularity analysis)
- [ ] Mobile app API support
- [ ] Integration z ETAP_07 (full sync)

---

## AKTUALIZACJA PLANU PROJEKTU

### Po ukończeniu każdej fazy

**Proces aktualizacji:**
1. Zmień status w `Plan_Projektu/ETAP_05a_Produkty.md`
2. Dodaj `└── PLIK: ścieżka/do/pliku` dla każdego completed task
3. Update percentage complete
4. Jeśli bloker: Oznacz ⚠️ + opisz blokera

**Example:**
```markdown
## FAZA 1: DATABASE SCHEMA

### ✅ 1.1 Variants Extensions Migrations (UKOŃCZONE 2025-10-17)
    ✅ 1.1.1 create_attribute_groups_table
        └── PLIK: database/migrations/2025_10_17_000001_create_attribute_groups_table.php
    ✅ 1.1.2 create_attribute_values_table
        └── PLIK: database/migrations/2025_10_17_000002_create_attribute_values_table.php
    ...
```

---

## NASTĘPNE KROKI

### Natychmiastowe akcje (po zatwierdzeniu planu)

1. **User Approval** - Prezentacja execution plan użytkownikowi
2. **Context7 Verification** - Verify library IDs dostępność
3. **Agent Briefing** - Share plan z wszystkimi agents
4. **Environment Setup** - Verify development environment ready
5. **Start FAZA 1** - Delegate to laravel-expert

### Decision Points

**Przed rozpoczęciem:**
- [ ] User approval execution plan
- [ ] Resource allocation (single dev vs team)
- [ ] Timeline commitment (2-3 tygodnie)
- [ ] PrestaShop test shop dostępny

**Checkpoints:**
- [ ] Po FAZA 1: Verify migrations work (go/no-go)
- [ ] Po FAZA 3: Review services architecture (refactor if needed)
- [ ] Po FAZA 4: UI/UX user testing
- [ ] Po FAZA 7: Production deployment decision

---

## PODSUMOWANIE

### Kluczowe wnioski

**Wykonalność:**
✅ Plan jest wykonalny w 2-3 tygodnie (single dev) lub 1.5 tygodnie (team)

**Critical Path:**
⚠️ FAZA 1 → FAZA 2 → FAZA 3 (sequential, 40-50h)

**Parallelization Potential:**
✅ FAZA 4, 5, 6 można robić równolegle po FAZA 3

**Biggest Risks:**
1. CompatibilityManager complexity (może przekroczyć 300 linii)
2. PrestaShop API rate limiting
3. Performance targets nie osiągnięte

**Mitigations:**
- Split services proaktywnie
- Queue jobs z retry
- Cache + indexes MANDATORY

**Business Value:**
- 80% redukcja czasu zarządzania wariantami
- 95% accuracy dopasowań
- 70% szybszy eksport do PrestaShop

---

## ZAŁĄCZNIKI

### Powiązane dokumenty

- [Plan_Projektu/ETAP_05a_Produkty.md](../Plan_Projektu/ETAP_05a_Produkty.md) - Szczegółowy plan techniczny
- [_DOCS/SKU_ARCHITECTURE_GUIDE.md](../_DOCS/SKU_ARCHITECTURE_GUIDE.md) - SKU First approach
- [_DOCS/AGENT_USAGE_GUIDE.md](../_DOCS/AGENT_USAGE_GUIDE.md) - Agent delegation patterns
- [_DOCS/CONTEXT7_INTEGRATION_GUIDE.md](../_DOCS/CONTEXT7_INTEGRATION_GUIDE.md) - Context7 usage
- [CLAUDE.md](../CLAUDE.md) - Project rules + zasady enterprise

---

**Data utworzenia:** 2025-10-16
**Autor:** Claude Code (Sonnet 4.5) - Expert Planning Manager
**Status:** ✅ EXECUTION PLAN READY FOR APPROVAL
**Wersja:** 1.0
**Następny krok:** User approval → Start FAZA 1
