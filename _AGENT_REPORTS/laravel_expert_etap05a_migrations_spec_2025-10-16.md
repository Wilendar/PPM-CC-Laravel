# RAPORT PRACY AGENTA: laravel-expert

**Data**: 2025-10-16 14:30
**Agent**: laravel-expert
**Zadanie**: Szczegółowa specyfikacja database migrations dla ETAP_05a (Product Variants, Vehicle Features, Parts Compatibility)

---

## ✅ CONTEXT7 FINDINGS - LARAVEL 12.X BEST PRACTICES

### 🔍 Zastosowane dokumentacje (Library: `/websites/laravel_12_x`)

**1. Foreign Keys & Constraints:**
- ✅ `foreignId()->constrained()` - preferred shorthand dla foreign key relationships
- ✅ `onDelete('cascade')` / `onUpdate('cascade')` - standard dla dependent data
- ✅ Custom foreign key names: `constrained(table: 'users', indexName: 'posts_user_id')`
- ✅ `dropForeign(['column_name'])` - array syntax dla rollback

**2. Indexes & Performance:**
- ✅ Composite indexes: `$table->index(['account_id', 'created_at'])`
- ✅ Unique constraints: `$table->unique('email')` lub `unique(['col1', 'col2'])`
- ✅ Custom index names: `$table->unique('email', 'unique_email')`
- ✅ Foreign key + index combined: `foreignId()` automatically creates index

**3. Schema Verification:**
- ✅ `Schema::hasTable('users')` - check table existence
- ✅ `Schema::hasColumn('users', 'email')` - verify column
- ✅ `Schema::hasIndex('users', ['email'], 'unique')` - verify index type
- ✅ Używać przed conditional migrations

**4. Column Modifiers:**
- ✅ `nullable()` - must be BEFORE `constrained()` in chain
- ✅ `comment('Description')` - inline documentation
- ✅ `default(value)` - set default values
- ✅ `after('column')` - column positioning (MySQL/MariaDB only)

**5. Migration Execution:**
- ✅ `--isolated` flag dla deployment (prevent concurrent migrations)
- ✅ `--pretend` dla dry-run verification
- ✅ `Schema::disableForeignKeyConstraints()` dla complex operations

**6. Casting & Data Types:**
- ✅ `$table->boolean('flag')->default(false)` - proper boolean handling
- ✅ `$table->json('data')` - native JSON support
- ✅ `$table->text('description')->charset('utf8mb4')` - proper UTF-8
- ✅ `$table->decimal('price', 10, 2)` - financial precision

**7. Soft Deletes:**
- ✅ `$table->softDeletes()` - standard soft delete column
- ✅ `$table->softDeletes('deleted_at', precision: 0)` - with precision

---

## 📊 ANALIZA ISTNIEJĄCEGO STANU

### ✅ Istniejące tabele (do wykorzystania):

**products** - 2182 linii, kompletny:
- ✅ SKU jako primary business identifier
- ✅ Multi-store support (shopData relation)
- ✅ Category relationships (per-shop + default)
- ✅ Product type relationship
- ✅ Publishing schedule (available_from/to)
- ✅ Soft deletes

**product_variants** - 1102 linii, bazowy:
- ✅ Podstawowa struktura (product_id, variant_sku, variant_name)
- ✅ Inheritance flags (inherit_prices, inherit_stock, inherit_attributes)
- ✅ EAN barcode support
- ⚠️ **BRAK:** inherit_images, inherit_categories, inherit_features (do dodania)

**product_attributes** - istniejący:
- ✅ EAV foundation (attribute definitions)
- ✅ Attribute types (text, number, boolean, select, etc.)

**product_attribute_values** - istniejący:
- ✅ EAV values (product_id + product_variant_id support)
- ✅ JSON storage dla multi-values

**product_types** - 217 linii, kompletny:
- ✅ Edytowalne typy produktów (database-driven zamiast ENUM)
- ✅ default_attributes JSON field
- ✅ Soft deletes

**product_shop_data** - istniejący:
- ✅ Per-shop product data storage
- ✅ Sync status tracking
- ✅ Conflict detection fields

### ⚠️ Brakujące komponenty (Scope ETAP_05a):

**SEKCJA 1.1: Product Variants Extensions**
1. ❌ `attribute_groups` - grupowanie atrybutów (Kolor, Rozmiar, Material)
2. ❌ `attribute_values` - wartości w grupach (Czerwony, XXL, Bawełna)
3. ❌ `product_variant_attributes` - przypisanie wartości do wariantów
4. ❌ `product_variant_images` - własne zdjęcia wariantów
5. ⚠️ `product_variants` extension - dodanie brakujących inherit flags

**SEKCJA 1.2: Vehicle Features System**
1. ❌ `features` - definicje cech (Model, Rok, Silnik, VIN)
2. ❌ `feature_sets` - template zestawy cech
3. ❌ `feature_set_items` - mapowanie sets → features
4. ❌ `product_features` - wartości cech per produkt + per shop

**SEKCJA 1.3: Parts Compatibility System**
1. ❌ `vehicle_compatibility` - dopasowania parts ↔ vehicles
2. ❌ `vehicle_compatibility_cache` - performance optimization
3. ❌ `shop_vehicle_brands` - per-shop brand filtering

**SEKCJA 1.4: PrestaShop Mapping Tables**
1. ❌ `prestashop_attribute_group_mappings` - attribute groups → ps_attribute_group
2. ❌ `prestashop_attribute_value_mappings` - attribute values → ps_attribute
3. ❌ `prestashop_feature_mappings` - features → ps_feature

---

## 🏗️ SZCZEGÓŁOWA SPECYFIKACJA 15 MIGRATIONS

### **MIGRATION 1/15: Create Attribute Groups Table**

**File:** `database/migrations/2025_10_16_000001_create_attribute_groups_table.php`

**Purpose:** Grupowanie atrybutów wariantów (np. wszystkie kolory w grupie "Kolor")

**Business Logic:**
- Wspiera warianty typu: T-shirt Czerwony XXL = Kolor:Czerwony + Rozmiar:XXL
- `product_type_id` NULL = global groups, INT = per product type
- Special flags: `is_color_group` dla hex colors, `is_size_group` dla sorting

**Schema:**
```php
Schema::create('attribute_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255)->comment('Nazwa grupy (Kolor, Rozmiar, Material)');
    $table->string('slug', 255)->unique()->comment('URL-friendly identifier');
    $table->foreignId('product_type_id')
          ->nullable()
          ->constrained('product_types')
          ->onDelete('cascade')
          ->comment('NULL = global, INT = per product type');
    $table->boolean('is_color_group')->default(false)->comment('Specjalne traktowanie kolorów (hex, swatches)');
    $table->boolean('is_size_group')->default(false)->comment('Specjalne traktowanie rozmiarów (sorting)');
    $table->integer('sort_order')->default(0)->comment('Kolejność wyświetlania');
    $table->boolean('is_active')->default(true)->comment('Status aktywności');
    $table->timestamps();

    // Indexes dla performance
    $table->index(['product_type_id', 'is_active'], 'idx_product_type_active');
    $table->index('slug', 'idx_slug');
});
```

**Indexes Strategy:**
- `idx_product_type_active` - query pattern: filtrowanie aktywnych grup per product type
- `idx_slug` - unique constraint enforcement + fast lookups
- Foreign key on product_type_id creates automatic index

**Rollback:**
```php
Schema::dropIfExists('attribute_groups');
```

**Seeder Data (ProductVariantsSeeder.php):**
```php
[
    ['name' => 'Kolor', 'slug' => 'kolor', 'is_color_group' => true, 'sort_order' => 1, 'is_active' => true],
    ['name' => 'Rozmiar', 'slug' => 'rozmiar', 'is_size_group' => true, 'sort_order' => 2, 'is_active' => true],
    ['name' => 'Material', 'slug' => 'material', 'sort_order' => 3, 'is_active' => true],
    ['name' => 'Pojemność', 'slug' => 'pojemnosc', 'sort_order' => 4, 'is_active' => true],
]
```

---

### **MIGRATION 2/15: Create Attribute Values Table**

**File:** `database/migrations/2025_10_16_000002_create_attribute_values_table.php`

**Purpose:** Konkretne wartości w grupach (np. "Czerwony" w grupie "Kolor")

**Business Logic:**
- Każda grupa ma N wartości (Kolor: Czerwony, Niebieski, Czarny, ...)
- `color_hex` dla color groups (visual swatches)
- `image_url` dla texture swatches (np. drewno, metal)
- `sort_order` dla logical sorting (S, M, L, XL, XXL)

**Schema:**
```php
Schema::create('attribute_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_group_id')
          ->constrained('attribute_groups')
          ->onDelete('cascade')
          ->comment('FK do attribute_groups');
    $table->string('value', 255)->comment('Nazwa wartości (Czerwony, XXL, Bawełna)');
    $table->string('slug', 255)->comment('URL-friendly identifier');
    $table->string('color_hex', 7)->nullable()->comment('HEX color dla color groups (#FF0000)');
    $table->string('image_url', 500)->nullable()->comment('URL do texture/swatch image');
    $table->integer('sort_order')->default(0)->comment('Kolejność w grupie (S, M, L, XL, XXL)');
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Unique constraint: jedna wartość per grupa
    $table->unique(['attribute_group_id', 'slug'], 'unique_value_per_group');

    // Indexes dla performance
    $table->index(['attribute_group_id', 'is_active', 'sort_order'], 'idx_group_active_sorted');
    $table->index('slug', 'idx_slug');
});
```

**Indexes Strategy:**
- `unique_value_per_group` - prevent duplicate values w grupie
- `idx_group_active_sorted` - composite index dla query: active values sorted per group
- `idx_slug` - fast slug lookups

**Rollback:**
```php
Schema::dropIfExists('attribute_values');
```

**Seeder Data (przykładowe):**
```php
// Grupa: Kolor (id=1)
[
    ['attribute_group_id' => 1, 'value' => 'Czerwony', 'slug' => 'czerwony', 'color_hex' => '#FF0000', 'sort_order' => 1],
    ['attribute_group_id' => 1, 'value' => 'Niebieski', 'slug' => 'niebieski', 'color_hex' => '#0000FF', 'sort_order' => 2],
    ['attribute_group_id' => 1, 'value' => 'Czarny', 'slug' => 'czarny', 'color_hex' => '#000000', 'sort_order' => 3],
]

// Grupa: Rozmiar (id=2)
[
    ['attribute_group_id' => 2, 'value' => 'S', 'slug' => 's', 'sort_order' => 1],
    ['attribute_group_id' => 2, 'value' => 'M', 'slug' => 'm', 'sort_order' => 2],
    ['attribute_group_id' => 2, 'value' => 'L', 'slug' => 'l', 'sort_order' => 3],
    ['attribute_group_id' => 2, 'value' => 'XL', 'slug' => 'xl', 'sort_order' => 4],
    ['attribute_group_id' => 2, 'value' => 'XXL', 'slug' => 'xxl', 'sort_order' => 5],
]
```

---

### **MIGRATION 3/15: Create Product Variant Attributes Table**

**File:** `database/migrations/2025_10_16_000003_create_product_variant_attributes_table.php`

**Purpose:** Przypisanie konkretnych wartości do wariantów (Variant #123 = Kolor:Czerwony + Rozmiar:XXL)

**Business Logic:**
- Pivot table: product_variants ↔ attribute_values (via groups)
- Unique constraint: jeden wariant może mieć tylko JEDNĄ wartość per grupa
- Composite index dla fast variant attribute lookups

**Schema:**
```php
Schema::create('product_variant_attributes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_variant_id')
          ->constrained('product_variants')
          ->onDelete('cascade')
          ->comment('FK do product_variants');
    $table->foreignId('attribute_group_id')
          ->constrained('attribute_groups')
          ->onDelete('cascade')
          ->comment('FK do attribute_groups');
    $table->foreignId('attribute_value_id')
          ->constrained('attribute_values')
          ->onDelete('cascade')
          ->comment('FK do attribute_values');
    $table->timestamp('created_at')->useCurrent();

    // Unique constraint: variant może mieć tylko jedną wartość per grupa
    $table->unique(['product_variant_id', 'attribute_group_id'], 'unique_group_per_variant');

    // Indexes dla performance
    $table->index('product_variant_id', 'idx_variant');
    $table->index('attribute_value_id', 'idx_value');
    $table->index(['product_variant_id', 'attribute_group_id', 'attribute_value_id'], 'idx_lookup');
});
```

**Indexes Strategy:**
- `unique_group_per_variant` - business rule enforcement (1 color, 1 size per variant)
- `idx_variant` - query pattern: get all attributes dla variant
- `idx_value` - query pattern: find variants z specific value
- `idx_lookup` - composite index dla complex queries (variant + group + value)

**Rollback:**
```php
Schema::dropIfExists('product_variant_attributes');
```

**Example Data:**
```
ProductVariant #123 (Kurtka Czerwona XXL):
  - (product_variant_id=123, attribute_group_id=1, attribute_value_id=5) -- Kolor: Czerwony
  - (product_variant_id=123, attribute_group_id=2, attribute_value_id=10) -- Rozmiar: XXL
```

---

### **MIGRATION 4/15: Create Product Variant Images Table**

**File:** `database/migrations/2025_10_16_000004_create_product_variant_images_table.php`

**Purpose:** Opcjonalne własne zdjęcia dla wariantów (fallback to parent images)

**Business Logic:**
- Variant może mieć własne zdjęcia (np. każdy kolor osobno)
- `is_primary` flag dla main variant image
- Fallback: jeśli brak variant images → użyj parent product images
- Integration: `media_id` FK do universal media table

**Schema:**
```php
Schema::create('product_variant_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_variant_id')
          ->constrained('product_variants')
          ->onDelete('cascade')
          ->comment('FK do product_variants');
    $table->foreignId('media_id')
          ->constrained('media')
          ->onDelete('cascade')
          ->comment('FK do media table');
    $table->integer('sort_order')->default(0)->comment('Kolejność w galerii');
    $table->boolean('is_primary')->default(false)->comment('Primary image dla tego variantu');
    $table->timestamp('created_at')->useCurrent();

    // Indexes dla performance
    $table->index('product_variant_id', 'idx_variant');
    $table->index(['product_variant_id', 'is_primary'], 'idx_variant_primary');
    $table->index('media_id', 'idx_media');
});
```

**Indexes Strategy:**
- `idx_variant` - query pattern: get all images dla variant
- `idx_variant_primary` - composite index dla primary image lookup
- `idx_media` - reverse lookup: which variants use this media

**Rollback:**
```php
Schema::dropIfExists('product_variant_images');
```

**Note:** Requires `media` table to exist (ETAP_05 Media System)

---

### **MIGRATION 5/15: Extend Product Variants Table**

**File:** `database/migrations/2025_10_16_000005_extend_product_variants_table.php`

**Purpose:** Dodanie brakujących inherit flags do istniejącej tabeli product_variants

**Business Logic:**
- `inherit_images` - czy wariant dziedziczy zdjęcia z parent (default: true)
- `inherit_categories` - czy wariant dziedziczy kategorie z parent (default: true)
- `inherit_features` - czy wariant dziedziczy cechy pojazdu z parent (default: true)

**Schema:**
```php
Schema::table('product_variants', function (Blueprint $table) {
    // Weryfikacja czy kolumny już istnieją
    if (!Schema::hasColumn('product_variants', 'inherit_images')) {
        $table->boolean('inherit_images')->default(true)->after('inherit_attributes')
              ->comment('Dziedziczy zdjęcia z parent?');
    }

    if (!Schema::hasColumn('product_variants', 'inherit_categories')) {
        $table->boolean('inherit_categories')->default(true)->after('inherit_images')
              ->comment('Dziedziczy kategorie z parent?');
    }

    if (!Schema::hasColumn('product_variants', 'inherit_features')) {
        $table->boolean('inherit_features')->default(true)->after('inherit_categories')
              ->comment('Dziedziczy cechy z parent?');
    }

    // Composite index dla inheritance queries
    if (!Schema::hasIndex('product_variants', 'idx_inherit_flags')) {
        $table->index(
            ['inherit_images', 'inherit_prices', 'inherit_stock', 'inherit_categories', 'inherit_features'],
            'idx_inherit_flags'
        );
    }
});
```

**Indexes Strategy:**
- `idx_inherit_flags` - composite index dla queries filtrujących po inheritance flags

**Rollback:**
```php
Schema::table('product_variants', function (Blueprint $table) {
    if (Schema::hasIndex('product_variants', 'idx_inherit_flags')) {
        $table->dropIndex('idx_inherit_flags');
    }

    if (Schema::hasColumn('product_variants', 'inherit_features')) {
        $table->dropColumn('inherit_features');
    }

    if (Schema::hasColumn('product_variants', 'inherit_categories')) {
        $table->dropColumn('inherit_categories');
    }

    if (Schema::hasColumn('product_variants', 'inherit_images')) {
        $table->dropColumn('inherit_images');
    }
});
```

**Safety:** Uses `Schema::hasColumn()` checks to prevent errors if columns already exist

---

### **MIGRATION 6/15: Create Features Table**

**File:** `database/migrations/2025_10_16_000006_create_features_table.php`

**Purpose:** Definicje cech produktów (Model, Rok, Silnik, VIN, etc.) - odpowiednik PrestaShop ps_feature

**Business Logic:**
- Cechy vs Atrybuty:
  - **Cechy (Features)**: Model, Rok produkcji, Silnik - opisują produkt
  - **Atrybuty (Attributes)**: Kolor, Rozmiar - definiują warianty
- `feature_type`: text, number, boolean, select, multiselect, date, textarea
- `is_global`: true = dla wszystkich product types, false = per product type
- `predefined_values` JSON: dla select/multiselect types
- `validation_rules` JSON: {"min": 1900, "max": 2030}

**Schema:**
```php
Schema::create('features', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255)->comment('Nazwa cechy (Model, Rok produkcji, Silnik)');
    $table->string('slug', 255)->unique()->comment('URL-friendly identifier');
    $table->text('description')->nullable()->comment('Opis cechy dla użytkowników');
    $table->enum('feature_type', ['text', 'number', 'boolean', 'select', 'multiselect', 'date', 'textarea'])
          ->default('text')
          ->comment('Typ danych cechy');
    $table->string('unit', 50)->nullable()->comment('Jednostka (km, kg, L, hp, cc)');
    $table->boolean('is_global')->default(true)->comment('Widoczny dla wszystkich product_types vs per-type');
    $table->boolean('is_searchable')->default(true)->comment('Indeksować dla wyszukiwania?');
    $table->boolean('is_filterable')->default(true)->comment('Używać w filtrach?');
    $table->json('validation_rules')->nullable()->comment('{"min": 1900, "max": 2030, "pattern": "^[A-Z0-9]+$"}');
    $table->json('predefined_values')->nullable()->comment('["Benzyna", "Diesel", "Elektryczny"] dla select');
    $table->integer('sort_order')->default(0)->comment('Kolejność wyświetlania');
    $table->timestamps();

    // Indexes dla performance
    $table->index('is_global', 'idx_global');
    $table->index(['is_searchable', 'is_global'], 'idx_searchable_global');
    $table->index(['is_filterable', 'is_global'], 'idx_filterable_global');
    $table->index('slug', 'idx_slug');

    // Full-text search index dla name
    $table->fullText('name', 'ft_name');
});
```

**Indexes Strategy:**
- `idx_global` - query pattern: get all global features
- `idx_searchable_global` - composite: filtrowanie searchable features
- `idx_filterable_global` - composite: filtrowanie filterable features
- `idx_slug` - fast slug lookups
- `ft_name` - full-text search dla feature names

**Rollback:**
```php
Schema::dropIfExists('features');
```

**Seeder Data (dla typu "Pojazd"):**
```php
[
    ['name' => 'Model', 'slug' => 'model', 'feature_type' => 'text', 'is_global' => false, 'is_searchable' => true, 'is_filterable' => true],
    ['name' => 'Rok produkcji', 'slug' => 'rok-produkcji', 'feature_type' => 'number', 'unit' => 'rok', 'validation_rules' => '{"min": 1900, "max": 2030}'],
    ['name' => 'Pojemność silnika', 'slug' => 'pojemnosc-silnika', 'feature_type' => 'number', 'unit' => 'cc'],
    ['name' => 'Typ paliwa', 'slug' => 'typ-paliwa', 'feature_type' => 'select', 'predefined_values' => '["Benzyna", "Diesel", "Elektryczny", "Hybryda"]'],
    ['name' => 'VIN', 'slug' => 'vin', 'feature_type' => 'text', 'validation_rules' => '{"pattern": "^[A-HJ-NPR-Z0-9]{17}$"}'],
]
```

---

### **MIGRATION 7/15: Create Feature Sets Table**

**File:** `database/migrations/2025_10_16_000007_create_feature_sets_table.php`

**Purpose:** Template zestawy cech (np. "Pojazdy Elektryczne" = zestaw X cech)

**Business Logic:**
- Template system: zestaw predefiniowanych cech dla product type
- `is_default`: true = domyślny zestaw dla product type (tylko jeden per type)
- Przykład: "Pojazdy Elektryczne" = Model + Rok + Zasięg + Czas ładowania

**Schema:**
```php
Schema::create('feature_sets', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255)->comment('Nazwa zestawu (Pojazdy Elektryczne, Pojazdy Spalinowe)');
    $table->string('slug', 255)->unique()->comment('URL-friendly identifier');
    $table->text('description')->nullable()->comment('Opis zestawu');
    $table->foreignId('product_type_id')
          ->nullable()
          ->constrained('product_types')
          ->onDelete('set null')
          ->comment('Przypisanie do typu produktu');
    $table->boolean('is_default')->default(false)->comment('Domyślny zestaw dla product_type');
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Indexes dla performance
    $table->index('product_type_id', 'idx_product_type');
    $table->index(['product_type_id', 'is_default'], 'idx_type_default');
    $table->index('is_active', 'idx_active');
    $table->index('slug', 'idx_slug');
});
```

**Indexes Strategy:**
- `idx_product_type` - query pattern: get all feature sets dla product type
- `idx_type_default` - composite: find default feature set per type
- `idx_active` - filter active sets only
- `idx_slug` - fast slug lookups

**Rollback:**
```php
Schema::dropIfExists('feature_sets');
```

**Note:** Business rule (1 default per type) enforced at application level, not database constraint

---

### **MIGRATION 8/15: Create Feature Set Items Table**

**File:** `database/migrations/2025_10_16_000008_create_feature_set_items_table.php`

**Purpose:** Mapowanie Feature Set → Features (które cechy w którym zestawie)

**Business Logic:**
- Pivot table: feature_sets ↔ features
- `is_required`: true = cecha obowiązkowa w zestawie
- `default_value`: domyślna wartość dla nowych produktów
- `validation_rules`: override validation z features table
- `help_text`: tekst pomocy dla użytkownika

**Schema:**
```php
Schema::create('feature_set_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('feature_set_id')
          ->constrained('feature_sets')
          ->onDelete('cascade')
          ->comment('FK do feature_sets');
    $table->foreignId('feature_id')
          ->constrained('features')
          ->onDelete('cascade')
          ->comment('FK do features');
    $table->boolean('is_required')->default(false)->comment('Czy cecha jest obowiązkowa w tym zestawie?');
    $table->string('default_value', 255)->nullable()->comment('Domyślna wartość dla nowych produktów');
    $table->json('validation_rules')->nullable()->comment('Nadpisanie validation_rules z features');
    $table->text('help_text')->nullable()->comment('Tekst pomocy dla użytkownika');
    $table->integer('sort_order')->default(0)->comment('Kolejność w zestawie');

    // Unique constraint: jedna cecha per zestaw
    $table->unique(['feature_set_id', 'feature_id'], 'unique_feature_per_set');

    // Indexes dla performance
    $table->index('feature_set_id', 'idx_set');
    $table->index(['feature_set_id', 'is_required', 'sort_order'], 'idx_set_required_sorted');
});
```

**Indexes Strategy:**
- `unique_feature_per_set` - prevent duplicate features w zestawie
- `idx_set` - query pattern: get all features dla feature set
- `idx_set_required_sorted` - composite: active required features sorted

**Rollback:**
```php
Schema::dropIfExists('feature_set_items');
```

**Example Data:**
```
Feature Set "Pojazdy Elektryczne" (id=1):
  - (feature_set_id=1, feature_id=1, is_required=true, sort_order=1) -- Model (required)
  - (feature_set_id=1, feature_id=2, is_required=true, sort_order=2) -- Rok produkcji (required)
  - (feature_set_id=1, feature_id=10, is_required=false, sort_order=3) -- Zasięg (km) (optional)
  - (feature_set_id=1, feature_id=11, is_required=false, sort_order=4) -- Czas ładowania (h) (optional)
```

---

### **MIGRATION 9/15: Create Product Features Table**

**File:** `database/migrations/2025_10_16_000009_create_product_features_table.php`

**Purpose:** Wartości cech przypisane do produktów (per-shop override możliwy)

**Business Logic:**
- Przechowuje wartości cech dla produktów (odpowiednik PrestaShop ps_feature_product)
- `shop_id` NULL = default values, INT = per-shop override
- Multi-value support: JSON dla multiselect features
- Per-shop: różne cechy dla różnych sklepów (np. "Model" różny per sklep)

**Schema:**
```php
Schema::create('product_features', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')
          ->constrained('products')
          ->onDelete('cascade')
          ->comment('FK do products');
    $table->foreignId('feature_id')
          ->constrained('features')
          ->onDelete('cascade')
          ->comment('FK do features');
    $table->text('value')->comment('Wartość cechy (text/number/JSON dla multiselect)');
    $table->foreignId('shop_id')
          ->nullable()
          ->constrained('prestashop_shops')
          ->onDelete('cascade')
          ->comment('NULL = default, INT = per-shop override');
    $table->timestamps();

    // Unique constraint: jedna wartość cechy per produkt per sklep
    $table->unique(['product_id', 'feature_id', 'shop_id'], 'unique_feature_per_product_shop');

    // Indexes dla performance
    $table->index('product_id', 'idx_product');
    $table->index('feature_id', 'idx_feature');
    $table->index(['product_id', 'shop_id'], 'idx_product_shop');
    $table->index(['feature_id', 'value(100)'], 'idx_feature_value'); // Prefix index dla TEXT column
});
```

**Indexes Strategy:**
- `unique_feature_per_product_shop` - prevent duplicate feature values
- `idx_product` - query pattern: get all features dla product
- `idx_feature` - query pattern: find products z specific feature
- `idx_product_shop` - composite: shop-specific features lookup
- `idx_feature_value` - prefix index dla feature value searches (first 100 chars)

**Rollback:**
```php
Schema::dropIfExists('product_features');
```

**Example Data:**
```
Product #5678 (Motocykl) - Default Features (shop_id=NULL):
  - (product_id=5678, feature_id=1, value='Yamaha YZF-R1', shop_id=NULL) -- Model
  - (product_id=5678, feature_id=2, value='2023', shop_id=NULL) -- Rok produkcji
  - (product_id=5678, feature_id=3, value='998', shop_id=NULL) -- Pojemność silnika (cc)

Product #5678 (Motocykl) - Shop #3 Override:
  - (product_id=5678, feature_id=1, value='Yamaha YZF-R1 Special Edition', shop_id=3) -- Model (override)
```

---

### **MIGRATION 10/15: Create Vehicle Compatibility Table**

**File:** `database/migrations/2025_10_16_000010_create_vehicle_compatibility_table.php`

**Purpose:** Dopasowania parts ↔ vehicles (many-to-many) - system Oryginał/Zamiennik/Model

**Business Logic:**
- Części zamienne mają wiele dopasowań do pojazdów
- 3 typy: `original` (numery OEM), `replacement` (zamienniki), `model` (kompatybilne modele)
- `compatibility_notes`: dodatkowe informacje (np. "tylko wersja Turbo")
- `is_verified`: admin verification flag

**Schema:**
```php
Schema::create('vehicle_compatibility', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')
          ->constrained('products')
          ->onDelete('cascade')
          ->comment('FK do products (części zamienne)');
    $table->enum('compatibility_type', ['original', 'replacement', 'model'])
          ->comment('original=OEM, replacement=zamiennik, model=kompatybilny model');
    $table->string('vehicle_identifier', 500)->comment('Model pojazdu, numer OEM, numer zamiennika');
    $table->string('manufacturer', 255)->nullable()->comment('Producent pojazdu (Honda, Yamaha, etc.)');
    $table->string('model', 255)->nullable()->comment('Model pojazdu (CB500, YZF-R1, etc.)');
    $table->integer('year_from')->nullable()->comment('Rok produkcji od');
    $table->integer('year_to')->nullable()->comment('Rok produkcji do');
    $table->text('compatibility_notes')->nullable()->comment('Dodatkowe informacje (tylko wersja Turbo, etc.)');
    $table->boolean('is_verified')->default(false)->comment('Admin verification flag');
    $table->timestamps();

    // Indexes dla performance
    $table->index('product_id', 'idx_product');
    $table->index(['product_id', 'compatibility_type'], 'idx_product_type');
    $table->index(['manufacturer', 'model'], 'idx_manufacturer_model');
    $table->index(['vehicle_identifier(100)'], 'idx_vehicle_identifier'); // Prefix index
    $table->index('is_verified', 'idx_verified');

    // Full-text search index dla vehicle_identifier
    $table->fullText('vehicle_identifier', 'ft_vehicle_identifier');
});
```

**Indexes Strategy:**
- `idx_product` - query pattern: get all compatibility dla product
- `idx_product_type` - composite: filter by product + compatibility type
- `idx_manufacturer_model` - composite: search by manufacturer + model
- `idx_vehicle_identifier` - prefix index dla searches
- `idx_verified` - filter verified compatibility only
- `ft_vehicle_identifier` - full-text search dla vehicle identifiers

**Rollback:**
```php
Schema::dropIfExists('vehicle_compatibility');
```

**Example Data:**
```
Product #1234 (Tarcza hamulcowa) compatibility:
  - (product_id=1234, type='original', vehicle_identifier='45251-MCJ-000', manufacturer='Honda', model='CB500F', year_from=2013, year_to=2018)
  - (product_id=1234, type='replacement', vehicle_identifier='45251-MCJ-001', manufacturer='Yamaha', model='MT-07', year_from=2014, year_to=2020)
  - (product_id=1234, type='model', vehicle_identifier='Honda CB500F', manufacturer='Honda', model='CB500F', year_from=2013, year_to=2023)
```

---

### **MIGRATION 11/15: Create Vehicle Compatibility Cache Table**

**File:** `database/migrations/2025_10_16_000011_create_vehicle_compatibility_cache_table.php`

**Purpose:** Performance optimization - cache dla vehicle compatibility queries

**Business Logic:**
- Denormalized table dla fast lookups (trade-off: storage vs speed)
- Rebuild strategy: on compatibility update lub scheduled job
- TTL (time-to-live): 24h cache expiry
- Invalidation: on product update lub manual trigger

**Schema:**
```php
Schema::create('vehicle_compatibility_cache', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')
          ->constrained('products')
          ->onDelete('cascade')
          ->comment('FK do products');
    $table->string('cache_key', 255)->comment('Unique cache key (product_id:manufacturer:model)');
    $table->json('compatibility_data')->comment('Cached compatibility data (array of vehicle_compatibility records)');
    $table->timestamp('cached_at')->comment('Cache generation timestamp');
    $table->timestamp('expires_at')->comment('Cache expiry timestamp (TTL 24h)');

    // Unique constraint: jedna cache entry per key
    $table->unique('cache_key', 'unique_cache_key');

    // Indexes dla performance
    $table->index('product_id', 'idx_product');
    $table->index(['expires_at', 'cached_at'], 'idx_expiry');
    $table->index('cache_key', 'idx_cache_key');
});
```

**Indexes Strategy:**
- `unique_cache_key` - prevent duplicate cache entries
- `idx_product` - query pattern: get cache dla product
- `idx_expiry` - composite: find expired cache entries dla cleanup job
- `idx_cache_key` - fast cache key lookups

**Rollback:**
```php
Schema::dropIfExists('vehicle_compatibility_cache');
```

**Cache Invalidation Strategy:**
- On product update: `VehicleCompatibilityCache::where('product_id', $productId)->delete();`
- On compatibility update: same
- Scheduled job: `VehicleCompatibilityCache::where('expires_at', '<', now())->delete();`

**Example Cache Entry:**
```json
{
  "cache_key": "1234:Honda:CB500F",
  "compatibility_data": [
    {
      "id": 567,
      "compatibility_type": "original",
      "vehicle_identifier": "45251-MCJ-000",
      "manufacturer": "Honda",
      "model": "CB500F",
      "year_from": 2013,
      "year_to": 2018,
      "is_verified": true
    },
    ...
  ],
  "cached_at": "2025-10-15 14:30:00",
  "expires_at": "2025-10-16 14:30:00"
}
```

---

### **MIGRATION 12/15: Create Shop Vehicle Brands Table**

**File:** `database/migrations/2025_10_16_000012_create_shop_vehicle_brands_table.php`

**Purpose:** Per-shop brand filtering configuration (które marki pokazywać na którym sklepie)

**Business Logic:**
- Admin może skonfigurować które marki pojazdów są widoczne na danym sklepie
- `is_allowed`: true = whitelist (pokaż), false = blacklist (ukryj)
- Przykład: Sklep "Motocykle Honda" = only Honda brand allowed

**Schema:**
```php
Schema::create('shop_vehicle_brands', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shop_id')
          ->constrained('prestashop_shops')
          ->onDelete('cascade')
          ->comment('FK do prestashop_shops');
    $table->string('manufacturer', 255)->comment('Nazwa producenta (Honda, Yamaha, Suzuki, etc.)');
    $table->boolean('is_allowed')->default(true)->comment('true=whitelist (pokaż), false=blacklist (ukryj)');
    $table->text('notes')->nullable()->comment('Notatki admina (powód allow/block)');
    $table->timestamps();

    // Unique constraint: jedna konfiguracja per shop per manufacturer
    $table->unique(['shop_id', 'manufacturer'], 'unique_shop_manufacturer');

    // Indexes dla performance
    $table->index('shop_id', 'idx_shop');
    $table->index(['shop_id', 'is_allowed'], 'idx_shop_allowed');
    $table->index('manufacturer', 'idx_manufacturer');
});
```

**Indexes Strategy:**
- `unique_shop_manufacturer` - prevent duplicate configurations
- `idx_shop` - query pattern: get all brand configs dla shop
- `idx_shop_allowed` - composite: filter allowed brands per shop
- `idx_manufacturer` - query pattern: find shops z specific brand config

**Rollback:**
```php
Schema::dropIfExists('shop_vehicle_brands');
```

**Example Data:**
```
Shop #5 ("Motocykle Honda Exclusive"):
  - (shop_id=5, manufacturer='Honda', is_allowed=true) -- Show only Honda
  - (shop_id=5, manufacturer='Yamaha', is_allowed=false) -- Hide Yamaha
  - (shop_id=5, manufacturer='Suzuki', is_allowed=false) -- Hide Suzuki

Shop #6 ("Multi-Brand Moto"):
  - (no records = show all manufacturers)
```

---

### **MIGRATION 13/15: Create PrestaShop Attribute Group Mappings Table**

**File:** `database/migrations/2025_10_16_000013_create_prestashop_attribute_group_mappings_table.php`

**Purpose:** Mapowanie attribute_groups → PrestaShop ps_attribute_group

**Business Logic:**
- Sync layer: PPM attribute_groups ↔ PrestaShop ps_attribute_group
- `prestashop_attribute_group_id`: external ID w PrestaShop
- `shop_id`: per-shop mapping (różne ID w różnych sklepach)
- `sync_status`: synced, pending, error
- `last_sync_at`: timestamp ostatniej synchronizacji

**Schema:**
```php
Schema::create('prestashop_attribute_group_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_group_id')
          ->constrained('attribute_groups')
          ->onDelete('cascade')
          ->comment('FK do attribute_groups (PPM)');
    $table->foreignId('shop_id')
          ->constrained('prestashop_shops')
          ->onDelete('cascade')
          ->comment('FK do prestashop_shops');
    $table->unsignedBigInteger('prestashop_attribute_group_id')->comment('ID w PrestaShop ps_attribute_group');
    $table->enum('sync_status', ['synced', 'pending', 'error'])->default('pending');
    $table->timestamp('last_sync_at')->nullable()->comment('Timestamp ostatniej synchronizacji');
    $table->json('sync_errors')->nullable()->comment('Error details w przypadku sync failure');
    $table->timestamps();

    // Unique constraint: jedna mapping per attribute group per shop
    $table->unique(['attribute_group_id', 'shop_id'], 'unique_attr_group_shop');

    // Indexes dla performance
    $table->index('attribute_group_id', 'idx_attr_group');
    $table->index(['shop_id', 'sync_status'], 'idx_shop_status');
    $table->index('prestashop_attribute_group_id', 'idx_prestashop_id');
});
```

**Indexes Strategy:**
- `unique_attr_group_shop` - prevent duplicate mappings
- `idx_attr_group` - query pattern: get all mappings dla attribute group
- `idx_shop_status` - composite: filter by shop + sync status
- `idx_prestashop_id` - reverse lookup: PPM attribute group from PrestaShop ID

**Rollback:**
```php
Schema::dropIfExists('prestashop_attribute_group_mappings');
```

**Example Data:**
```
Attribute Group "Kolor" (id=1) → PrestaShop:
  - (attribute_group_id=1, shop_id=3, prestashop_attribute_group_id=5, sync_status='synced', last_sync_at='2025-10-15 12:30:00')
  - (attribute_group_id=1, shop_id=4, prestashop_attribute_group_id=7, sync_status='synced', last_sync_at='2025-10-15 12:31:00')
```

---

### **MIGRATION 14/15: Create PrestaShop Attribute Value Mappings Table**

**File:** `database/migrations/2025_10_16_000014_create_prestashop_attribute_value_mappings_table.php`

**Purpose:** Mapowanie attribute_values → PrestaShop ps_attribute

**Business Logic:**
- Sync layer: PPM attribute_values ↔ PrestaShop ps_attribute
- `prestashop_attribute_id`: external ID w PrestaShop
- Per-shop mapping: różne ID w różnych sklepach
- Sync status tracking

**Schema:**
```php
Schema::create('prestashop_attribute_value_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_value_id')
          ->constrained('attribute_values')
          ->onDelete('cascade')
          ->comment('FK do attribute_values (PPM)');
    $table->foreignId('shop_id')
          ->constrained('prestashop_shops')
          ->onDelete('cascade')
          ->comment('FK do prestashop_shops');
    $table->unsignedBigInteger('prestashop_attribute_id')->comment('ID w PrestaShop ps_attribute');
    $table->enum('sync_status', ['synced', 'pending', 'error'])->default('pending');
    $table->timestamp('last_sync_at')->nullable()->comment('Timestamp ostatniej synchronizacji');
    $table->json('sync_errors')->nullable()->comment('Error details w przypadku sync failure');
    $table->timestamps();

    // Unique constraint: jedna mapping per attribute value per shop
    $table->unique(['attribute_value_id', 'shop_id'], 'unique_attr_value_shop');

    // Indexes dla performance
    $table->index('attribute_value_id', 'idx_attr_value');
    $table->index(['shop_id', 'sync_status'], 'idx_shop_status');
    $table->index('prestashop_attribute_id', 'idx_prestashop_id');
});
```

**Indexes Strategy:**
- `unique_attr_value_shop` - prevent duplicate mappings
- `idx_attr_value` - query pattern: get all mappings dla attribute value
- `idx_shop_status` - composite: filter by shop + sync status
- `idx_prestashop_id` - reverse lookup: PPM attribute value from PrestaShop ID

**Rollback:**
```php
Schema::dropIfExists('prestashop_attribute_value_mappings');
```

**Example Data:**
```
Attribute Value "Czerwony" (id=5) → PrestaShop:
  - (attribute_value_id=5, shop_id=3, prestashop_attribute_id=12, sync_status='synced', last_sync_at='2025-10-15 12:30:00')
  - (attribute_value_id=5, shop_id=4, prestashop_attribute_id=15, sync_status='synced', last_sync_at='2025-10-15 12:31:00')
```

---

### **MIGRATION 15/15: Create PrestaShop Feature Mappings Table**

**File:** `database/migrations/2025_10_16_000015_create_prestashop_feature_mappings_table.php`

**Purpose:** Mapowanie features → PrestaShop ps_feature

**Business Logic:**
- Sync layer: PPM features ↔ PrestaShop ps_feature
- `prestashop_feature_id`: external ID w PrestaShop
- Per-shop mapping: różne ID w różnych sklepach
- Sync status tracking

**Schema:**
```php
Schema::create('prestashop_feature_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('feature_id')
          ->constrained('features')
          ->onDelete('cascade')
          ->comment('FK do features (PPM)');
    $table->foreignId('shop_id')
          ->constrained('prestashop_shops')
          ->onDelete('cascade')
          ->comment('FK do prestashop_shops');
    $table->unsignedBigInteger('prestashop_feature_id')->comment('ID w PrestaShop ps_feature');
    $table->enum('sync_status', ['synced', 'pending', 'error'])->default('pending');
    $table->timestamp('last_sync_at')->nullable()->comment('Timestamp ostatniej synchronizacji');
    $table->json('sync_errors')->nullable()->comment('Error details w przypadku sync failure');
    $table->timestamps();

    // Unique constraint: jedna mapping per feature per shop
    $table->unique(['feature_id', 'shop_id'], 'unique_feature_shop');

    // Indexes dla performance
    $table->index('feature_id', 'idx_feature');
    $table->index(['shop_id', 'sync_status'], 'idx_shop_status');
    $table->index('prestashop_feature_id', 'idx_prestashop_id');
});
```

**Indexes Strategy:**
- `unique_feature_shop` - prevent duplicate mappings
- `idx_feature` - query pattern: get all mappings dla feature
- `idx_shop_status` - composite: filter by shop + sync status
- `idx_prestashop_id` - reverse lookup: PPM feature from PrestaShop ID

**Rollback:**
```php
Schema::dropIfExists('prestashop_feature_mappings');
```

**Example Data:**
```
Feature "Model" (id=1) → PrestaShop:
  - (feature_id=1, shop_id=3, prestashop_feature_id=8, sync_status='synced', last_sync_at='2025-10-15 12:30:00')
  - (feature_id=1, shop_id=4, prestashop_feature_id=10, sync_status='synced', last_sync_at='2025-10-15 12:31:00')
```

---

## 📊 MIGRATION EXECUTION ORDER - DEPENDENCY GRAPH

### **Critical Dependencies:**

```
SEKCJA 1.1: Product Variants Extensions
└─ MIGRATION 1: attribute_groups (independent)
   └─ MIGRATION 2: attribute_values (depends: attribute_groups)
      └─ MIGRATION 3: product_variant_attributes (depends: attribute_values, product_variants)
   └─ MIGRATION 4: product_variant_images (depends: product_variants, media)
   └─ MIGRATION 5: extend product_variants (alters existing table)

SEKCJA 1.2: Vehicle Features System
└─ MIGRATION 6: features (independent)
   └─ MIGRATION 7: feature_sets (depends: product_types, features)
      └─ MIGRATION 8: feature_set_items (depends: feature_sets, features)
   └─ MIGRATION 9: product_features (depends: products, features, prestashop_shops)

SEKCJA 1.3: Parts Compatibility System
└─ MIGRATION 10: vehicle_compatibility (depends: products)
   └─ MIGRATION 11: vehicle_compatibility_cache (depends: vehicle_compatibility)
└─ MIGRATION 12: shop_vehicle_brands (depends: prestashop_shops)

SEKCJA 1.4: PrestaShop Mapping Tables
└─ MIGRATION 13: prestashop_attribute_group_mappings (depends: attribute_groups, prestashop_shops)
└─ MIGRATION 14: prestashop_attribute_value_mappings (depends: attribute_values, prestashop_shops)
└─ MIGRATION 15: prestashop_feature_mappings (depends: features, prestashop_shops)
```

### **Execution Order (Correct Sequence):**

1. **SEKCJA 1.1 (Variants)**: 1 → 2 → 3 → 4 → 5
2. **SEKCJA 1.2 (Features)**: 6 → 7 → 8 → 9
3. **SEKCJA 1.3 (Compatibility)**: 10 → 11 → 12
4. **SEKCJA 1.4 (PrestaShop Mappings)**: 13 → 14 → 15

**Critical:** Migrations 13-15 MUST run AFTER sekcja 1.1 and 1.2 (depend on attribute_groups, attribute_values, features)

---

## 🔍 INDEX STRATEGY - PERFORMANCE OPTIMIZATION

### **Query Patterns Analysis:**

**1. Variant Attributes Lookup:**
- Query: Get all attributes dla variant #123
- Index: `idx_variant` on product_variant_attributes
- Expected performance: <10ms dla 10 attributes per variant

**2. Find Variants by Attribute Value:**
- Query: Find all variants z Kolor="Czerwony"
- Index: `idx_value` on product_variant_attributes
- Expected performance: <50ms dla 1000 variants

**3. Product Features Display:**
- Query: Get all features dla product #5678
- Index: `idx_product` on product_features
- Expected performance: <5ms dla 20 features per product

**4. Vehicle Compatibility Search:**
- Query: Find all parts dla Honda CB500F 2013-2018
- Index: `idx_manufacturer_model` + `ft_vehicle_identifier` on vehicle_compatibility
- Expected performance: <100ms dla 10K compatibility records

**5. Cache Expiry Cleanup:**
- Query: Delete expired cache entries
- Index: `idx_expiry` on vehicle_compatibility_cache
- Expected performance: <500ms dla 100K cache entries

**6. PrestaShop Sync Status:**
- Query: Get all pending sync mappings dla shop #3
- Index: `idx_shop_status` on prestashop_*_mappings
- Expected performance: <20ms dla 500 mappings per shop

### **Composite Index Benefits:**

- `idx_group_active_sorted` (attribute_values): Single index dla active sorted values query
- `idx_set_required_sorted` (feature_set_items): Single index dla required features in order
- `idx_product_shop` (product_features): Fast per-shop feature lookups
- `idx_shop_status` (prestashop_*_mappings): Sync status monitoring per shop

---

## ⚠️ POTENTIAL ISSUES & SOLUTIONS

### **Problem 1: Migration Dependency Conflicts**

**Issue:** Jeśli migration 13 (prestashop_attribute_group_mappings) uruchomi się przed migration 1 (attribute_groups)

**Solution:**
- ✅ Prefix migrations z datami: `2025_10_16_000001`, `2025_10_16_000002`, etc.
- ✅ Numeric order enforcement w nazwach plików
- ✅ Test rollback order (reverse execution)

**Prevention:**
```bash
php artisan migrate:status  # Verify order before execution
php artisan migrate --pretend  # Dry-run verification
```

### **Problem 2: Unique Constraint Violations**

**Issue:** `unique_feature_per_product_shop` może blokować legitimate use cases

**Solution:**
- ✅ Constraint na application level: validate before insert
- ✅ Upsert pattern: `updateOrCreate()` zamiast `create()`
- ✅ Error handling: catch unique violation exceptions

**Code Pattern:**
```php
ProductFeature::updateOrCreate(
    ['product_id' => $productId, 'feature_id' => $featureId, 'shop_id' => $shopId],
    ['value' => $newValue]
);
```

### **Problem 3: Full-Text Search Performance on Large Datasets**

**Issue:** `ft_vehicle_identifier` może być slow dla 1M+ records

**Solution:**
- ✅ Elasticsearch integration dla full-text search (future)
- ✅ Prefix index fallback: `idx_vehicle_identifier(100)`
- ✅ Cache strategy: vehicle_compatibility_cache table
- ✅ Lazy loading: paginate results

**Performance Test:**
```sql
-- Test full-text search performance
SELECT * FROM vehicle_compatibility
WHERE MATCH(vehicle_identifier) AGAINST ('Honda CB500' IN BOOLEAN MODE)
LIMIT 50;

-- Expected: <100ms dla 100K records
```

### **Problem 4: JSON Column Query Performance**

**Issue:** `predefined_values` JSON queries mogą być slow

**Solution:**
- ✅ Laravel 12.x JSON casting: automatic serialization/deserialization
- ✅ Avoid querying JSON columns directly: use indexed columns instead
- ✅ Denormalization: cache frequently queried JSON values

**Avoid:**
```php
// ❌ SLOW: JSON column query
Feature::whereJsonContains('predefined_values', 'Benzyna')->get();
```

**Use Instead:**
```php
// ✅ FAST: Application-level filtering
$features = Feature::where('feature_type', 'select')->get()
    ->filter(function($feature) {
        return in_array('Benzyna', $feature->predefined_values ?? []);
    });
```

### **Problem 5: Cascade Delete Performance on Large Datasets**

**Issue:** `onDelete('cascade')` może timeout dla 10K+ related records

**Solution:**
- ✅ Queue-based deletion dla large datasets
- ✅ Batch deletion: chunk related records
- ✅ Soft deletes: avoid hard deletes
- ✅ Background job: async cleanup

**Code Pattern:**
```php
// Queue-based cascade delete
dispatch(new DeleteProductVariantsJob($productId));

// Batch deletion
ProductVariantAttribute::where('product_variant_id', $variantId)
    ->chunk(500, function($attributes) {
        $attributes->each->delete();
    });
```

---

## 📋 SEEDER DATA TEMPLATES

### **AttributeGroupsSeeder.php**
```php
DB::table('attribute_groups')->insert([
    ['name' => 'Kolor', 'slug' => 'kolor', 'is_color_group' => true, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Rozmiar', 'slug' => 'rozmiar', 'is_size_group' => true, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Material', 'slug' => 'material', 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
]);
```

### **FeaturesSeeder.php**
```php
DB::table('features')->insert([
    ['name' => 'Model', 'slug' => 'model', 'feature_type' => 'text', 'is_global' => false, 'is_searchable' => true, 'is_filterable' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Rok produkcji', 'slug' => 'rok-produkcji', 'feature_type' => 'number', 'unit' => 'rok', 'validation_rules' => json_encode(['min' => 1900, 'max' => 2030]), 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Pojemność silnika', 'slug' => 'pojemnosc-silnika', 'feature_type' => 'number', 'unit' => 'cc', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
]);
```

---

## 🛡️ ROLLBACK STRATEGY

### **Safe Rollback Order:**

1. **SEKCJA 1.4 (PrestaShop Mappings)**: 15 → 14 → 13
2. **SEKCJA 1.3 (Compatibility)**: 12 → 11 → 10
3. **SEKCJA 1.2 (Features)**: 9 → 8 → 7 → 6
4. **SEKCJA 1.1 (Variants)**: 5 → 4 → 3 → 2 → 1

**Critical:** Rollback w odwrotnej kolejności niż migration execution

### **Rollback Verification:**

```bash
# Rollback ostatniej batch migrations
php artisan migrate:rollback

# Rollback specific migration
php artisan migrate:rollback --step=5

# Verify rollback success
php artisan migrate:status

# Check for orphaned records
SELECT COUNT(*) FROM product_variant_attributes WHERE product_variant_id NOT IN (SELECT id FROM product_variants);
```

---

## ✅ VALIDATION RULES PER TABLE

### **attribute_groups:**
- `name`: required, max:255, unique per product_type_id
- `slug`: required, max:255, unique, alpha_dash
- `product_type_id`: nullable, exists:product_types,id
- `sort_order`: integer, min:0, max:1000

### **attribute_values:**
- `attribute_group_id`: required, exists:attribute_groups,id
- `value`: required, max:255
- `slug`: required, max:255, unique within attribute_group_id
- `color_hex`: nullable, regex:/^#[A-Fa-f0-9]{6}$/
- `sort_order`: integer, min:0, max:1000

### **product_variant_attributes:**
- `product_variant_id`: required, exists:product_variants,id
- `attribute_group_id`: required, exists:attribute_groups,id
- `attribute_value_id`: required, exists:attribute_values,id
- Unique combination: (product_variant_id, attribute_group_id)

### **features:**
- `name`: required, max:255, unique
- `slug`: required, max:255, unique, alpha_dash
- `feature_type`: required, in:text,number,boolean,select,multiselect,date,textarea
- `predefined_values`: nullable, json, array when feature_type is select/multiselect
- `validation_rules`: nullable, json, valid validation rules

### **product_features:**
- `product_id`: required, exists:products,id
- `feature_id`: required, exists:features,id
- `value`: required, max:65535 (TEXT column limit)
- `shop_id`: nullable, exists:prestashop_shops,id
- Unique combination: (product_id, feature_id, shop_id)

### **vehicle_compatibility:**
- `product_id`: required, exists:products,id
- `compatibility_type`: required, in:original,replacement,model
- `vehicle_identifier`: required, max:500
- `year_from`: nullable, integer, min:1900, max:2030
- `year_to`: nullable, integer, min:1900, max:2030, gte:year_from

---

## 📊 CHECKLIST PRZYGOTOWAWCZY DO PISANIA MIGRATIONS

### **Pre-Migration Verification:**

- ✅ **Context7 Documentation**: Verified Laravel 12.x best practices
- ✅ **Existing Tables Analysis**: Products, ProductVariants, ProductTypes, ProductShopData
- ✅ **Dependency Graph**: Complete execution order documented
- ✅ **Index Strategy**: Performance optimization dla query patterns
- ✅ **Seeder Data**: Templates prepared dla all tables
- ✅ **Rollback Strategy**: Safe rollback order verified
- ✅ **Validation Rules**: Per-table validation documented

### **During Migration Writing:**

- [ ] Copy migration template z Context7 patterns
- [ ] Verify foreign key constraints w correct order
- [ ] Add proper indexes dla query patterns
- [ ] Include rollback method z dropForeign() calls
- [ ] Add comments dla business logic
- [ ] Test migration up/down locally
- [ ] Verify unique constraints enforcement
- [ ] Check cascade delete behavior

### **Post-Migration Verification:**

- [ ] Run `php artisan migrate --pretend` - dry run verification
- [ ] Execute migrations w correct order
- [ ] Verify all foreign keys created
- [ ] Check all indexes present (`SHOW INDEX FROM table_name`)
- [ ] Test rollback (`php artisan migrate:rollback --step=1`)
- [ ] Run seeders dla initial data
- [ ] Test query performance z EXPLAIN
- [ ] Verify cascade delete behavior
- [ ] Check for orphaned records

---

## 📈 BUSINESS VALUE - REZULTATY IMPLEMENTACJI

### **Po ukończeniu 15 migrations:**

**1. Product Variants System:**
- ✅ Pełna obsługa kombinacji atrybutów (Kolor×Rozmiar)
- ✅ Własne zdjęcia per variant
- ✅ Selektywne dziedziczenie (prices, stock, images, categories, features)
- ✅ PrestaShop ps_attribute* sync ready

**2. Vehicle Features System:**
- ✅ Template zestawy cech dla product types
- ✅ Per-shop feature override capability
- ✅ PrestaShop ps_feature* sync ready
- ✅ Rich product descriptions (Model, Rok, Silnik, VIN)

**3. Parts Compatibility System:**
- ✅ Many-to-many parts ↔ vehicles
- ✅ 3 typy dopasowań (original, replacement, model)
- ✅ Performance cache dla fast lookups
- ✅ Per-shop brand filtering

**4. PrestaShop Integration Layer:**
- ✅ Complete mapping tables dla sync operations
- ✅ Sync status tracking per shop
- ✅ Error handling dla sync failures
- ✅ Ready dla ETAP_07 PrestaShop API implementation

### **Performance Expectations:**

- Variant attributes lookup: <10ms
- Product features display: <5ms
- Vehicle compatibility search: <100ms
- Cache expiry cleanup: <500ms
- PrestaShop sync status: <20ms

### **Scalability:**

- Support dla 100K+ products
- Support dla 500K+ variants
- Support dla 1M+ compatibility records
- Support dla 10+ PrestaShop shops
- Support dla 50+ attribute groups

---

## 🔗 NASTĘPNE KROKI (Post-Migrations)

### **SEKCJA 2: SERVICES & BUSINESS LOGIC**

Po ukończeniu migrations, kolejne kroki:

1. **Model Classes** - 15 Eloquent models dla nowych tabel
2. **Repository Pattern** - Data access abstraction
3. **Service Layer** - Business logic (VariantGenerator, FeatureManager, CompatibilityEngine)
4. **Transformers** - PrestaShop API transformation layer
5. **Queue Jobs** - Async operations (BulkVariantGeneration, CompatibilitySync)

### **SEKCJA 3: UI COMPONENTS**

1. **Livewire Components** - Variant management UI
2. **Alpine.js Interactions** - Dynamic attribute selection
3. **Blade Views** - Feature sets, compatibility display
4. **CSS Styling** - Consistent MPP TRADE design

---

**KONIEC RAPORTU**

**Status:** ✅ COMPLETE - 15 migrations specification ready
**Czas analizy:** 2.5 godziny
**Następny krok:** Code implementation - writing migration files
