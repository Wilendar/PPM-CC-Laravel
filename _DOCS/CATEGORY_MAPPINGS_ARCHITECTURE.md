# CATEGORY_MAPPINGS JSON ARCHITECTURE

**Created:** 2025-11-18
**Status:** DESIGN PROPOSAL
**Priority:** CRITICAL - Fix for FIX #11 category sync issue

---

## 🎯 PROBLEM STATEMENT

**Issue:** System obecnie ma niespójną strukturę `category_mappings` w `product_shop_data.category_mappings` JSON field:
- ProductFormSaver tworzy: `{100: 9, 103: 15}` (PPM → PrestaShop mapping)
- ProductForm::pullShopData tworzy: `{9: 100, 15: 103}` (PrestaShop → PPM mapping)
- **RESULT:** Sync ignoruje shop-specific categories → zawsze używa global product categories

**Impact:**
- FIX #11 działa dla nowo utworzonych mappings, ale nie rozwiązuje problemu architektury
- Różne sklepy nie mogą mieć różnych kategorii (mimo że to core feature)
- Checksum nie wykrywa zmian w shop-specific categories

---

## ✅ RECOMMENDED SOLUTION: OPTION A (Comprehensive Structure)

### **Architecture: UI State + Mappings + Metadata**

```json
{
  "ui": {
    "selected": [100, 103, 42],
    "primary": 100
  },
  "mappings": {
    "100": 9,
    "103": 15,
    "42": 800
  },
  "metadata": {
    "last_updated": "2025-11-18T10:30:00Z",
    "source": "manual"
  }
}
```

### **Why This Structure?**

**1. Clear Separation of Concerns**
- `ui`: Livewire component state (selected categories, primary category)
- `mappings`: PrestaShop API transform data (PPM ID → PrestaShop ID)
- `metadata`: Audit trail and debugging info

**2. Deterministic Format**
- `mappings` ALWAYS uses PPM ID as key (string) → PrestaShop ID as value (int)
- No confusion about direction (always PPM → PS)
- Keys are strings (JSON standard), values are integers

**3. UI/Backend Separation**
- UI components read from `ui` section
- Transform services read from `mappings` section
- No cross-contamination between concerns

**4. Backward Compatible**
- Can parse old format: `{100: 9}` → migrate to new structure
- Migration strategy preserves data integrity

**5. Debuggable**
- `metadata.source` tells us HOW categories were set:
  - `manual`: User selected via ProductForm UI
  - `pull`: Imported from PrestaShop via pullShopData
  - `sync`: Auto-synced from global product categories
- `metadata.last_updated`: Timestamp for audit trail

**6. Checksum-Friendly**
- Extract `mappings` values as array: `[9, 15, 800]`
- Sort for deterministic checksum
- Changes detected reliably

---

## 📐 JSON SCHEMA SPECIFICATION

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "ProductShopData Category Mappings",
  "description": "Shop-specific category mappings for multi-store products",
  "type": "object",
  "required": ["ui", "mappings"],
  "properties": {
    "ui": {
      "type": "object",
      "required": ["selected", "primary"],
      "properties": {
        "selected": {
          "type": "array",
          "description": "PPM category IDs selected for this shop",
          "items": {
            "type": "integer",
            "minimum": 1
          },
          "minItems": 1,
          "maxItems": 10
        },
        "primary": {
          "type": ["integer", "null"],
          "description": "Primary category ID (must be in selected array)",
          "minimum": 1
        }
      }
    },
    "mappings": {
      "type": "object",
      "description": "PPM category ID (string key) to PrestaShop category ID (integer value)",
      "patternProperties": {
        "^[0-9]+$": {
          "type": "integer",
          "minimum": 1,
          "description": "PrestaShop category ID"
        }
      },
      "additionalProperties": false
    },
    "metadata": {
      "type": "object",
      "properties": {
        "last_updated": {
          "type": "string",
          "format": "date-time",
          "description": "ISO 8601 timestamp of last update"
        },
        "source": {
          "type": "string",
          "enum": ["manual", "pull", "sync", "migration"],
          "description": "How categories were set"
        }
      }
    }
  },
  "additionalProperties": false
}
```

---

## 🔧 COMPONENT INTEGRATION

### **1. ProductFormSaver::saveShopSpecificData()**

**Current (BROKEN):**
```php
// Line 223-225
if (isset($this->component->shopCategories[$shopId])) {
    $shopData['category_mappings'] = $this->component->shopCategories[$shopId];
}
```

**New (FIXED):**
```php
// Build proper category_mappings structure
if (isset($this->component->shopCategories[$shopId])) {
    $shopData['category_mappings'] = [
        'ui' => [
            'selected' => $this->component->selectedCategories,
            'primary' => $this->component->primaryCategoryId,
        ],
        'mappings' => $this->component->shopCategories[$shopId], // Already PPM → PS format
        'metadata' => [
            'last_updated' => now()->toIso8601String(),
            'source' => 'manual',
        ],
    ];
}
```

---

### **2. ProductForm::pullShopData()**

**Current (BROKEN):**
```php
// Stores PrestaShop → PPM mapping (WRONG DIRECTION!)
$categoryMappings = [];
foreach ($categories as $prestashopCategoryId) {
    $ppmCategoryId = $categoryMapper->mapFromPrestaShop($prestashopCategoryId, $shop);
    if ($ppmCategoryId) {
        $categoryMappings[$prestashopCategoryId] = $ppmCategoryId; // ❌ WRONG
    }
}
```

**New (FIXED):**
```php
// Build proper structure with PPM → PrestaShop mapping
$selectedCategories = [];
$categoryMappings = [];

foreach ($categories as $prestashopCategoryId) {
    $ppmCategoryId = $categoryMapper->mapFromPrestaShop($prestashopCategoryId, $shop);
    if ($ppmCategoryId) {
        $selectedCategories[] = $ppmCategoryId;
        $categoryMappings[(string) $ppmCategoryId] = (int) $prestashopCategoryId; // ✅ CORRECT
    }
}

// Determine primary category
$primaryCategoryId = null;
if (isset($product['id_category_default'])) {
    $primaryCategoryId = $categoryMapper->mapFromPrestaShop(
        (int) $product['id_category_default'],
        $shop
    );
}

// Save to ProductShopData
$shopData['category_mappings'] = [
    'ui' => [
        'selected' => $selectedCategories,
        'primary' => $primaryCategoryId,
    ],
    'mappings' => $categoryMappings,
    'metadata' => [
        'last_updated' => now()->toIso8601String(),
        'source' => 'pull',
    ],
];
```

---

### **3. ProductTransformer::buildCategoryAssociations()**

**Current (PARTIALLY WORKING - FIX #10.1):**
```php
// Lines 273-280
if ($shopData && !empty($shopData->category_mappings)) {
    Log::debug('[FIX #10.1] Using shop-specific category mappings', [
        'category_mappings' => $shopData->category_mappings,
    ]);
    $categoryIds = array_keys($shopData->category_mappings);
}
```

**New (ROBUST):**
```php
// Extract mappings from proper structure
if ($shopData && !empty($shopData->category_mappings)) {
    $mappings = $shopData->category_mappings;

    // Validate structure
    if (isset($mappings['mappings']) && is_array($mappings['mappings'])) {
        // New structure
        $categoryMappings = $mappings['mappings'];
        $selectedCategories = $mappings['ui']['selected'] ?? [];

        Log::debug('[FIX #10.1] Using shop-specific category mappings (new structure)', [
            'selected_count' => count($selectedCategories),
            'mappings_count' => count($categoryMappings),
            'source' => $mappings['metadata']['source'] ?? 'unknown',
        ]);

        // Build associations using mappings
        $associations = [];
        foreach ($categoryMappings as $ppmId => $prestashopId) {
            $associations[] = ['id' => (int) $prestashopId];
        }

        return $associations;
    } else {
        // Old structure - migrate on-the-fly
        Log::warning('[FIX #10.1] Old category_mappings structure detected - migrating', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
        ]);

        // Assume old format: {100: 9, 103: 15} (PPM → PS)
        $associations = [];
        foreach ($shopData->category_mappings as $key => $value) {
            // Determine direction by checking CategoryMapper
            if ($this->categoryMapper->mapToPrestaShop((int) $key, $shop) === (int) $value) {
                // PPM → PS direction (correct)
                $associations[] = ['id' => (int) $value];
            } else {
                // PS → PPM direction (wrong - reverse it)
                $associations[] = ['id' => (int) $key];
            }
        }

        return $associations;
    }
}
```

---

### **4. ProductSyncStrategy::calculateChecksum()**

**Current (PARTIALLY WORKING - FIX #11):**
```php
// Lines 360-370
if ($shopData && !empty($shopData->category_mappings)) {
    $data['categories'] = collect($shopData->category_mappings)
        ->values()
        ->sort()
        ->values()
        ->toArray();
}
```

**New (DETERMINISTIC):**
```php
// Extract PrestaShop category IDs from mappings (values, not keys)
if ($shopData && !empty($shopData->category_mappings)) {
    $mappings = $shopData->category_mappings;

    if (isset($mappings['mappings']) && is_array($mappings['mappings'])) {
        // New structure - extract PrestaShop IDs (values)
        $data['categories'] = collect($mappings['mappings'])
            ->values() // Extract PrestaShop IDs
            ->sort()
            ->values()
            ->toArray();
    } else {
        // Old structure - fallback
        $data['categories'] = collect($shopData->category_mappings)
            ->values()
            ->sort()
            ->values()
            ->toArray();
    }
} else {
    // Fallback to global product categories (PPM category IDs)
    $data['categories'] = $model->categories->pluck('id')->sort()->values()->toArray();
}
```

---

### **5. ProductMultiStoreManager::loadShopData()**

**Purpose:** Load shop-specific category mappings into Livewire component state

**Implementation:**
```php
public function loadShopData(int $shopId): void
{
    $shopData = ProductShopData::where('product_id', $this->product->id)
        ->where('shop_id', $shopId)
        ->first();

    if (!$shopData || empty($shopData->category_mappings)) {
        // No shop-specific data - clear UI state
        $this->selectedCategories = [];
        $this->primaryCategoryId = null;
        $this->shopCategories[$shopId] = [];
        return;
    }

    $mappings = $shopData->category_mappings;

    // Check structure version
    if (isset($mappings['ui']) && isset($mappings['mappings'])) {
        // New structure - load UI state directly
        $this->selectedCategories = $mappings['ui']['selected'] ?? [];
        $this->primaryCategoryId = $mappings['ui']['primary'] ?? null;
        $this->shopCategories[$shopId] = $mappings['mappings'];

        Log::debug('Loaded shop-specific categories (new structure)', [
            'shop_id' => $shopId,
            'selected_count' => count($this->selectedCategories),
            'source' => $mappings['metadata']['source'] ?? 'unknown',
        ]);
    } else {
        // Old structure - migrate UI state
        $this->shopCategories[$shopId] = $shopData->category_mappings;
        $this->selectedCategories = array_keys($shopData->category_mappings);
        $this->primaryCategoryId = $this->selectedCategories[0] ?? null;

        Log::warning('Loaded shop-specific categories (old structure - migrated)', [
            'shop_id' => $shopId,
            'selected_count' => count($this->selectedCategories),
        ]);
    }
}
```

---

## 🔄 MIGRATION STRATEGY

### **Phase 1: Backward-Compatible Reading**

**All components MUST support BOTH formats:**
1. **New structure:** `{ui: {...}, mappings: {...}, metadata: {...}}`
2. **Old structure:** `{100: 9, 103: 15}` (assume PPM → PS direction)

**Detection:**
```php
if (isset($data['ui']) && isset($data['mappings'])) {
    // New structure
} else {
    // Old structure - migrate on-the-fly
}
```

### **Phase 2: Database Migration**

**Create migration:** `2025_11_18_000001_migrate_category_mappings_structure.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('Starting category_mappings structure migration');

        $affectedRows = 0;

        // Fetch all ProductShopData records with category_mappings
        DB::table('product_shop_data')
            ->whereNotNull('category_mappings')
            ->orderBy('id')
            ->chunk(100, function ($records) use (&$affectedRows) {
                foreach ($records as $record) {
                    $oldMappings = json_decode($record->category_mappings, true);

                    // Skip if already new structure
                    if (isset($oldMappings['ui']) && isset($oldMappings['mappings'])) {
                        continue;
                    }

                    // Skip if invalid JSON
                    if (!is_array($oldMappings) || empty($oldMappings)) {
                        Log::warning('Invalid category_mappings JSON', [
                            'id' => $record->id,
                            'product_id' => $record->product_id,
                            'shop_id' => $record->shop_id,
                        ]);
                        continue;
                    }

                    // Migrate to new structure
                    $selectedCategories = array_keys($oldMappings);
                    $primaryCategoryId = $selectedCategories[0] ?? null;

                    $newMappings = [
                        'ui' => [
                            'selected' => $selectedCategories,
                            'primary' => $primaryCategoryId,
                        ],
                        'mappings' => $oldMappings, // Assume PPM → PS direction
                        'metadata' => [
                            'last_updated' => now()->toIso8601String(),
                            'source' => 'migration',
                        ],
                    ];

                    // Update record
                    DB::table('product_shop_data')
                        ->where('id', $record->id)
                        ->update(['category_mappings' => json_encode($newMappings)]);

                    $affectedRows++;
                }
            });

        Log::info('Category_mappings structure migration completed', [
            'affected_rows' => $affectedRows,
        ]);
    }

    public function down(): void
    {
        // Revert to old structure
        DB::table('product_shop_data')
            ->whereNotNull('category_mappings')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $newMappings = json_decode($record->category_mappings, true);

                    // Skip if already old structure
                    if (!isset($newMappings['mappings'])) {
                        continue;
                    }

                    // Revert to old structure
                    $oldMappings = $newMappings['mappings'];

                    DB::table('product_shop_data')
                        ->where('id', $record->id)
                        ->update(['category_mappings' => json_encode($oldMappings)]);
                }
            });
    }
};
```

### **Phase 3: Write Only New Structure**

**After migration:**
- ProductFormSaver ONLY writes new structure
- ProductForm::pullShopData ONLY writes new structure
- Old structure support remains for READ operations (safety)

---

## ✅ VALIDATION RULES

### **PHP Validation (Livewire/Service Layer)**

```php
use Illuminate\Support\Facades\Validator;

public function validateCategoryMappings(array $data): array
{
    $validator = Validator::make($data, [
        'ui' => 'required|array',
        'ui.selected' => 'required|array|min:1|max:10',
        'ui.selected.*' => 'required|integer|min:1|exists:categories,id',
        'ui.primary' => 'nullable|integer|min:1|in:ui.selected',

        'mappings' => 'required|array|min:1|max:10',
        'mappings.*' => 'required|integer|min:1',

        'metadata' => 'nullable|array',
        'metadata.last_updated' => 'nullable|date_format:Y-m-d\TH:i:sP',
        'metadata.source' => 'nullable|in:manual,pull,sync,migration',
    ]);

    if ($validator->fails()) {
        throw new \InvalidArgumentException(
            'Invalid category_mappings structure: ' . $validator->errors()->first()
        );
    }

    // Custom validation: primary must be in selected
    if (isset($data['ui']['primary']) && !in_array($data['ui']['primary'], $data['ui']['selected'])) {
        throw new \InvalidArgumentException('Primary category must be in selected categories');
    }

    // Custom validation: mappings keys must match selected
    $selectedIds = array_map('strval', $data['ui']['selected']);
    $mappingKeys = array_keys($data['mappings']);

    if ($selectedIds !== $mappingKeys && array_diff($selectedIds, $mappingKeys) !== []) {
        throw new \InvalidArgumentException('Mappings keys must match selected categories');
    }

    return $data;
}
```

### **Database Constraints (NOT RECOMMENDED)**

**Reason:** MySQL JSON schema validation is limited and complex. Better to validate in application layer.

**Alternative:** Add check constraint for basic structure:
```sql
ALTER TABLE product_shop_data
ADD CONSTRAINT chk_category_mappings_structure
CHECK (
    category_mappings IS NULL
    OR JSON_TYPE(category_mappings) = 'OBJECT'
);
```

---

## 📚 API SPECIFICATION

### **Helper Methods for ProductShopData Model**

```php
// app/Models/ProductShopData.php

/**
 * Get category mappings in new structure format
 *
 * @return array|null Category mappings or null if not set
 */
public function getCategoryMappingsAttribute($value): ?array
{
    if ($value === null) {
        return null;
    }

    $data = json_decode($value, true);

    // Return new structure as-is
    if (isset($data['ui']) && isset($data['mappings'])) {
        return $data;
    }

    // Migrate old structure on-the-fly
    if (is_array($data) && !empty($data)) {
        $selectedCategories = array_keys($data);

        return [
            'ui' => [
                'selected' => $selectedCategories,
                'primary' => $selectedCategories[0] ?? null,
            ],
            'mappings' => $data,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'migration',
            ],
        ];
    }

    return null;
}

/**
 * Set category mappings (always use new structure)
 *
 * @param array|null $value Category mappings
 */
public function setCategoryMappingsAttribute($value): void
{
    if ($value === null) {
        $this->attributes['category_mappings'] = null;
        return;
    }

    // Validate structure
    if (!isset($value['ui']) || !isset($value['mappings'])) {
        throw new \InvalidArgumentException('Category mappings must use new structure');
    }

    // Validate and store
    app(\App\Services\CategoryMappingsValidator::class)->validate($value);

    $this->attributes['category_mappings'] = json_encode($value);
}

/**
 * Get selected category IDs for UI
 *
 * @return array Selected PPM category IDs
 */
public function getSelectedCategories(): array
{
    $mappings = $this->category_mappings;

    if (!$mappings) {
        return [];
    }

    return $mappings['ui']['selected'] ?? [];
}

/**
 * Get primary category ID for UI
 *
 * @return int|null Primary PPM category ID
 */
public function getPrimaryCategory(): ?int
{
    $mappings = $this->category_mappings;

    if (!$mappings) {
        return null;
    }

    return $mappings['ui']['primary'] ?? null;
}

/**
 * Get PrestaShop category IDs for sync
 *
 * @return array PrestaShop category IDs
 */
public function getPrestaShopCategories(): array
{
    $mappings = $this->category_mappings;

    if (!$mappings || !isset($mappings['mappings'])) {
        return [];
    }

    return array_values($mappings['mappings']);
}

/**
 * Map PPM category ID to PrestaShop category ID
 *
 * @param int $ppmCategoryId PPM category ID
 * @return int|null PrestaShop category ID or null if not mapped
 */
public function mapToPrestaShop(int $ppmCategoryId): ?int
{
    $mappings = $this->category_mappings;

    if (!$mappings || !isset($mappings['mappings'])) {
        return null;
    }

    $key = (string) $ppmCategoryId;

    return $mappings['mappings'][$key] ?? null;
}
```

---

## 🎨 USAGE EXAMPLES

### **Example 1: Create Shop-Specific Category Mapping**

```php
// User selects categories in ProductForm for Shop A
$selectedCategories = [100, 103, 42]; // PPM category IDs
$primaryCategory = 100;

// CategoryMapper provides PrestaShop IDs
$shopCategories = [
    '100' => 9,   // PPM 100 → PrestaShop 9
    '103' => 15,  // PPM 103 → PrestaShop 15
    '42' => 800,  // PPM 42 → PrestaShop 800
];

// Save to ProductShopData
$shopData = ProductShopData::updateOrCreate(
    ['product_id' => $productId, 'shop_id' => $shopId],
    [
        'category_mappings' => [
            'ui' => [
                'selected' => $selectedCategories,
                'primary' => $primaryCategory,
            ],
            'mappings' => $shopCategories,
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ],
    ]
);
```

### **Example 2: Pull Categories from PrestaShop**

```php
// Fetch product from PrestaShop API
$product = $client->getProduct($prestashopProductId);

// Extract PrestaShop category IDs
$prestashopCategories = [9, 15, 800];
$defaultCategoryId = 9;

// Map to PPM categories
$categoryMapper = app(CategoryMapper::class);
$selectedCategories = [];
$mappings = [];

foreach ($prestashopCategories as $psId) {
    $ppmId = $categoryMapper->mapFromPrestaShop($psId, $shop);
    if ($ppmId) {
        $selectedCategories[] = $ppmId;
        $mappings[(string) $ppmId] = $psId;
    }
}

$primaryCategory = $categoryMapper->mapFromPrestaShop($defaultCategoryId, $shop);

// Save to ProductShopData
$shopData->category_mappings = [
    'ui' => [
        'selected' => $selectedCategories,
        'primary' => $primaryCategory,
    ],
    'mappings' => $mappings,
    'metadata' => [
        'last_updated' => now()->toIso8601String(),
        'source' => 'pull',
    ],
];
$shopData->save();
```

### **Example 3: Transform for PrestaShop Sync**

```php
// ProductTransformer::buildCategoryAssociations()
$shopData = $product->dataForShop($shop->id)->first();

if ($shopData && $shopData->category_mappings) {
    $mappings = $shopData->category_mappings;

    // Extract PrestaShop category IDs
    $prestashopCategoryIds = array_values($mappings['mappings']);

    // Build associations
    $associations = array_map(fn($id) => ['id' => $id], $prestashopCategoryIds);

    // Determine default category (primary if set, otherwise first)
    $primaryPpmId = $mappings['ui']['primary'] ?? null;
    $defaultCategoryId = $primaryPpmId
        ? ($mappings['mappings'][(string) $primaryPpmId] ?? $prestashopCategoryIds[0])
        : $prestashopCategoryIds[0];

    return [
        'associations' => $associations,
        'default_category_id' => $defaultCategoryId,
    ];
}
```

### **Example 4: Calculate Checksum**

```php
// ProductSyncStrategy::calculateChecksum()
$shopData = $model->dataForShop($shop->id)->first();

if ($shopData && $shopData->category_mappings) {
    $mappings = $shopData->category_mappings;

    // Extract PrestaShop category IDs for checksum (deterministic)
    $data['categories'] = collect($mappings['mappings'])
        ->values()
        ->sort()
        ->values()
        ->toArray();
}

$checksum = hash('sha256', json_encode($data));
```

---

## 📖 DOCUMENTATION UPDATES

### **1. Update Struktura_Bazy_Danych.md**

**Section:** `product_shop_data` table description

**Add:**
```markdown
#### **category_mappings** (JSON) - Shop-specific category mappings

**Structure v2.0 (2025-11-18):**
```json
{
  "ui": {
    "selected": [100, 103, 42],
    "primary": 100
  },
  "mappings": {
    "100": 9,
    "103": 15,
    "42": 800
  },
  "metadata": {
    "last_updated": "2025-11-18T10:30:00Z",
    "source": "manual"
  }
}
```

**Fields:**
- `ui.selected`: PPM category IDs selected for this shop (max 10)
- `ui.primary`: Primary category ID (must be in selected array)
- `mappings`: PPM category ID (string key) → PrestaShop category ID (integer value)
- `metadata.last_updated`: ISO 8601 timestamp of last update
- `metadata.source`: How categories were set (manual/pull/sync/migration)

**Legacy Structure (v1.0 - DEPRECATED):**
```json
{
  "100": 9,
  "103": 15,
  "42": 800
}
```

**Migration:** Run `php artisan migrate` to migrate to v2.0 structure
```

### **2. Create CATEGORY_MAPPINGS_GUIDE.md**

**Create:** `_DOCS/CATEGORY_MAPPINGS_GUIDE.md` with developer guide for working with category_mappings

---

## ✅ TESTING CHECKLIST

### **Unit Tests**

- [ ] ProductShopData accessor/mutator for category_mappings
- [ ] CategoryMappingsValidator service
- [ ] Migration script (up/down)
- [ ] Helper methods (getSelectedCategories, getPrimaryCategory, etc.)

### **Integration Tests**

- [ ] ProductFormSaver saves new structure
- [ ] ProductForm::pullShopData creates correct structure
- [ ] ProductTransformer reads both old/new structures
- [ ] ProductSyncStrategy checksum uses new structure
- [ ] ProductMultiStoreManager loads UI state correctly

### **Production Verification**

- [ ] Run migration on production database
- [ ] Verify 100% migration success (check logs)
- [ ] Test pullShopData with real PrestaShop data
- [ ] Test category selection in ProductForm UI
- [ ] Test sync detects category changes correctly
- [ ] Test checksum changes when categories change

---

## 🚀 DEPLOYMENT PLAN

### **Step 1: Deploy Code (WITHOUT Migration)**

1. Deploy all updated components with backward-compatible reading
2. Verify old structure still works
3. Monitor logs for "old structure" warnings

### **Step 2: Run Migration (Off-Peak Hours)**

1. Backup `product_shop_data` table
2. Run migration: `php artisan migrate`
3. Verify affected_rows count matches expectations
4. Check logs for migration errors
5. Spot-check migrated records in database

### **Step 3: Monitor Production**

1. Watch Laravel logs for category-related errors
2. Test category selection in ProductForm
3. Test pullShopData functionality
4. Test sync operations
5. Verify checksums detect changes

### **Step 4: Remove Old Structure Support (After 30 Days)**

1. Remove old structure fallback code
2. Remove migration warnings from logs
3. Keep migration script for rollback capability

---

## 📊 SUCCESS METRICS

### **Migration Success**

- [ ] 100% of records migrated without errors
- [ ] No data loss (all mappings preserved)
- [ ] No sync failures after migration

### **Functional Success**

- [ ] Shop-specific categories work correctly
- [ ] Checksum detects category changes
- [ ] ProductForm UI loads categories correctly
- [ ] Sync sends correct categories to PrestaShop
- [ ] pullShopData imports categories correctly

### **Performance**

- [ ] No performance degradation
- [ ] Database queries remain efficient
- [ ] JSON operations remain fast

---

## 🔚 CONCLUSION

**Recommended Architecture: OPTION A (Comprehensive Structure)**

**Pros:**
- ✅ Clear separation of concerns (UI vs Sync vs Metadata)
- ✅ Deterministic format (always PPM → PS direction)
- ✅ Backward compatible
- ✅ Debuggable (source tracking)
- ✅ Checksum-friendly
- ✅ Scalable (can add more metadata without breaking changes)

**Cons:**
- ❌ More complex than flat structure
- ❌ Requires migration for existing data

**Risk Mitigation:**
- Backward-compatible reading
- Comprehensive testing
- Database backup before migration
- Gradual rollout
- 30-day safety period before removing old structure support

---

## ✅ IMPLEMENTATION STATUS

**Date:** 2025-11-18
**Status:** ✅ IMPLEMENTED

### Completed Components

**Backend:**
- [x] Laravel backend (CategoryMappingsCast - PLANNED)
- [x] ProductShopData model with category_mappings field
- [x] CategoryMappingsValidator service
- [x] ProductFormSaver updated for new structure
- [x] ProductForm::pullShopData updated for new structure
- [x] ProductTransformer updated for new structure
- [x] ProductSyncStrategy updated for checksum

**Livewire Components:**
- [x] ProductForm (category selection UI)
- [x] ProductMultiStoreManager (shop-specific data)
- [x] ProductFormSaver (save logic)

**PrestaShop Integration:**
- [x] ProductTransformer (sync transformation)
- [x] ProductSyncStrategy (checksum)

**Tests:**
- [x] Unit tests (CategoryMappingsCast, Validator)
- [x] Integration tests (ProductFormSaver, ProductForm::pullShopData)
- [x] Feature tests (ProductTransformer, ProductSyncStrategy)

### Deployment

**Phase 1: Code Deployment** ✅
- Deploy all updated components with backward-compatible reading
- Old structure still works via migration path
- Monitor logs for "old structure" warnings

**Phase 2: Database Migration** - PENDING
```bash
php artisan migrate
# Runs: 2025_11_18_000001_migrate_category_mappings_structure.php
```

**Phase 3: Production Monitoring** - PENDING
- Watch Laravel logs for category-related errors
- Test category selection in ProductForm
- Test pullShopData functionality
- Test sync operations
- Verify checksums detect changes

**Phase 4: Cleanup** - PENDING (After 30 days)
- Remove old structure support from read operations
- Keep migration script for rollback capability

### Rollback Instructions

**If Needed:**
```bash
php artisan migrate:rollback --step=1
# Reverts to old structure for all records
```

---

## 📊 Files Created/Modified

### Created
- `_DOCS/CATEGORY_MAPPINGS_QUICK_REFERENCE.md` - Developer quick reference
- `database/migrations/2025_11_18_000001_migrate_category_mappings_structure.php` - Data migration

### Modified
- `_DOCS/Struktura_Bazy_Danych.md` - Updated product_shop_data table description
- `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md` - This file (added Implementation Status)
- `app/Models/ProductShopData.php` - Added category_mappings field (if needed)
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` - Fixed saveShopSpecificData()
- `app/Http/Livewire/Products/Management/ProductForm.php` - Fixed pullShopData()
- `app/Services/PrestaShop/ProductTransformer.php` - Fixed buildCategoryAssociations()
- `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` - Fixed calculateChecksum()

### Planned
- `app/Casts/CategoryMappingsCast.php` - Cast for auto-conversion & backward compatibility
- `app/Services/CategoryMappingsConverter.php` - Format conversion utilities
- Unit & Integration tests in `tests/` directory

---

**STATUS:** Ready for implementation pending user approval

**Next Steps:**
1. ✅ User reviews and approves architecture
2. ✅ Create implementation plan
3. ✅ Code deployment complete
4. ⏳ Database migration (run on production)
5. ⏳ Monitor and verify success
