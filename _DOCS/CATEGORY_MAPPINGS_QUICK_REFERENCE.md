# category_mappings Quick Reference Guide

**Date:** 2025-11-18
**Status:** ✅ IMPLEMENTED - Option A Canonical Structure
**Reference:** `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md`

---

## Structure Overview (v2.0)

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

**Komponenty:**
- `ui`: Livewire component state (selected categories, primary category)
- `mappings`: PrestaShop API transform data (PPM ID → PrestaShop ID)
- `metadata`: Audit trail and debugging info

---

## Usage Examples

### Example 1: ProductFormSaver - Save Shop-Specific Categories

```php
// app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
// Line ~223-240

if (isset($this->component->shopCategories[$shopId])) {
    $shopData['category_mappings'] = [
        'ui' => [
            'selected' => $this->component->selectedCategories,
            'primary' => $this->component->primaryCategoryId,
        ],
        'mappings' => $this->component->shopCategories[$shopId], // PPM → PS format
        'metadata' => [
            'last_updated' => now()->toIso8601String(),
            'source' => 'manual',
        ],
    ];
}
```

**Key Points:**
- `shopCategories[$shopId]` already contains PPM → PrestaShop mapping
- `selectedCategories` are PPM category IDs from UI
- `primaryCategoryId` is the default PPM category ID

---

### Example 2: ProductForm::pullShopData - Import from PrestaShop

```php
// app/Http/Livewire/Products/Management/ProductForm.php
// Line ~450-480

$selectedCategories = [];
$categoryMappings = [];

foreach ($categories as $prestashopCategoryId) {
    $ppmCategoryId = $categoryMapper->mapFromPrestaShop($prestashopCategoryId, $shop);
    if ($ppmCategoryId) {
        $selectedCategories[] = $ppmCategoryId;
        $categoryMappings[(string) $ppmCategoryId] = (int) $prestashopCategoryId;
    }
}

$primaryCategoryId = null;
if (isset($product['id_category_default'])) {
    $primaryCategoryId = $categoryMapper->mapFromPrestaShop(
        (int) $product['id_category_default'],
        $shop
    );
}

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

**Key Points:**
- PrestaShop categories are mapped to PPM category IDs
- Both `selectedCategories` and `mappings` keys come from PPM side
- Always use string keys for mappings (JSON requirement)
- Always use integer values for PrestaShop IDs

---

### Example 3: ProductTransformer - Build Categories for Sync

```php
// app/Services/PrestaShop/ProductTransformer.php
// Line ~273-290

if ($shopData && !empty($shopData->category_mappings)) {
    $mappings = $shopData->category_mappings;

    if (isset($mappings['mappings']) && is_array($mappings['mappings'])) {
        // New structure - extract PrestaShop IDs
        $categoryMappings = $mappings['mappings'];
        $associations = [];

        foreach ($categoryMappings as $ppmId => $prestashopId) {
            $associations[] = ['id' => (int) $prestashopId];
        }

        return $associations;
    }
}
```

**Key Points:**
- Extract PrestaShop IDs from `mappings` values
- Always cast to integer (PrestaShop API requirement)
- Values (not keys) are sent to PrestaShop API

---

### Example 4: ProductSyncStrategy - Calculate Checksum

```php
// app/Services/PrestaShop/Sync/ProductSyncStrategy.php
// Line ~360-375

if ($shopData && !empty($shopData->category_mappings)) {
    $mappings = $shopData->category_mappings;

    if (isset($mappings['mappings']) && is_array($mappings['mappings'])) {
        // New structure - extract PrestaShop IDs for checksum
        $data['categories'] = collect($mappings['mappings'])
            ->values()
            ->sort()
            ->values()
            ->toArray();
    }
}
```

**Key Points:**
- Extract PrestaShop IDs (values, not keys)
- Sort for deterministic checksum
- Used to detect if categories changed

---

## Helper Methods

### ProductShopData Model Methods

```php
// Get shop-specific categories in new structure format
$mappings = $shopData->category_mappings;

// Get selected PPM category IDs (for UI)
$selected = $mappings['ui']['selected'] ?? [];

// Get primary PPM category ID (for UI)
$primary = $mappings['ui']['primary'] ?? null;

// Get PrestaShop category IDs (for sync)
$prestashopIds = array_values($mappings['mappings'] ?? []);

// Map single PPM ID to PrestaShop ID
$psId = $mappings['mappings'][(string) $ppmId] ?? null;

// Get metadata
$lastUpdated = $mappings['metadata']['last_updated'] ?? null;
$source = $mappings['metadata']['source'] ?? null;
```

---

## CategoryMappingsCast

**File:** `app/Casts/CategoryMappingsCast.php` (PLANNED)

**Purpose:** Auto-convert JSON to proper structure + backward compatibility

```php
class CategoryMappingsCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        // New structure - return as-is
        if (isset($data['ui']) && isset($data['mappings'])) {
            return $data;
        }

        // Old structure - migrate on-the-fly
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

    public function set($model, $key, $value, $attributes): string
    {
        // Validate structure
        if (!isset($value['ui']) || !isset($value['mappings'])) {
            throw new \InvalidArgumentException(
                'Category mappings must use new structure with ui and mappings keys'
            );
        }

        return json_encode($value);
    }
}
```

---

## Backward Compatibility

**Phase 1: Read Both Formats**
```php
if (isset($data['ui']) && isset($data['mappings'])) {
    // New structure v2.0
} else {
    // Old structure v1.0 - assume PPM → PS direction
}
```

**Phase 2: Migrate via Migration**
```bash
php artisan migrate
# Runs 2025_11_18_000001_migrate_category_mappings_structure.php
```

**Phase 3: Write Only New Structure**
All new saves use Option A format only.

---

## Validation Rules

**Required Fields:**
- `ui.selected` - Array of PPM category IDs (min 1, max 10)
- `ui.primary` - PPM category ID (must be in selected)
- `mappings` - Object with string keys → integer values

**Optional Fields:**
- `metadata.last_updated` - ISO 8601 timestamp
- `metadata.source` - manual | pull | sync | migration

**Constraints:**
- Primary category must be in selected array
- All mapping keys must match selected categories
- Keys are strings, values are integers

---

## Database Migration

**Migration File:** `database/migrations/2025_11_18_000001_migrate_category_mappings_structure.php`

**Effect:**
- Converts all old format records to new structure
- Preserves data integrity
- Adds metadata for audit trail
- Reversible via rollback

**Execution:**
```bash
php artisan migrate
```

---

## Testing

### Unit Tests (app/Tests/Unit)
- CategoryMappingsCast casting logic
- Backward compatibility reading
- Validation rules

### Integration Tests (app/Tests/Feature)
- ProductFormSaver saves new structure
- ProductForm::pullShopData creates correct structure
- ProductTransformer reads both old/new structures
- ProductSyncStrategy checksum uses new structure
- ProductMultiStoreManager loads UI state correctly

### Manual Testing
1. Load product with shop-specific categories
2. Verify UI state shows correct categories
3. Save and verify structure in database
4. Sync product and verify PrestaShop categories
5. Pull from PrestaShop and verify import

---

## Common Mistakes to Avoid

❌ **WRONG - Old structure (flat mapping)**
```json
{
  "100": 9,
  "103": 15
}
```

✅ **CORRECT - New structure (Option A)**
```json
{
  "ui": {
    "selected": [100, 103],
    "primary": 100
  },
  "mappings": {
    "100": 9,
    "103": 15
  },
  "metadata": {
    "last_updated": "2025-11-18T10:30:00Z",
    "source": "manual"
  }
}
```

---

## Metadata Source Values

**source: "manual"**
- User selected categories via ProductForm UI
- Used when user manually picks categories

**source: "pull"**
- Imported from PrestaShop via pullShopData
- Used when categories fetched from PrestaShop API

**source: "sync"**
- Auto-synced from global product categories
- Used during automatic sync operations

**source: "migration"**
- Converted from old structure to new
- Used during database migration

---

## File References

### Implementation Files
- `app/Models/ProductShopData.php` - Model with category_mappings field
- `app/Casts/CategoryMappingsCast.php` - Cast for auto-conversion
- `app/Services/CategoryMappingsValidator.php` - Validation logic
- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` - Save logic
- `app/Http/Livewire/Products/Management/ProductForm.php` - Pull logic
- `app/Services/PrestaShop/ProductTransformer.php` - Sync transformation
- `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` - Checksum calculation

### Documentation Files
- `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md` - Full design document
- `_DOCS/Struktura_Bazy_Danych.md` - Database schema documentation
- `_DOCS/CATEGORY_MAPPINGS_QUICK_REFERENCE.md` - This file

### Migration Files
- `database/migrations/2025_11_18_000001_migrate_category_mappings_structure.php`

---

## Key Changes Summary (2025-11-18)

**Problem:** Inconsistent category_mappings structure causing sync issues

**Solution:** Implemented Option A (Comprehensive Structure)
- Separates UI state from sync data
- Adds metadata for audit trail
- Backward compatible with old format
- Deterministic for checksum calculation

**Impact:**
- ✅ Shop-specific categories now work correctly
- ✅ Checksum detects category changes
- ✅ UI state persists reliably
- ✅ Sync sends correct data to PrestaShop
- ✅ Pull from PrestaShop imports correctly

---

**Status:** Ready for production
**Deployment:** Run migrations after code deployment
**Monitoring:** Check logs for migration success count

