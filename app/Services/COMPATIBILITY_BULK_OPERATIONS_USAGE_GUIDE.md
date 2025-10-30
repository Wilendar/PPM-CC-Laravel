# Compatibility Bulk Operations - Usage Guide

**Service:** `CompatibilityManager`
**ETAP:** ETAP_05d FAZA 2.1
**Date:** 2025-10-24

## Overview

Implementacja backend service methods dla bulk compatibility operations, inspirowana workflow Excela.

**Excel-inspired patterns:**
- **Horizontal drag:** 1 part × 26 vehicles = 26 compatibilities
- **Vertical drag:** 50 parts × 1 vehicle = 50 compatibilities
- **Copy-paste:** Copy all compatibilities from Part A → Part B
- **Cell edit:** Toggle O (Oryginał) ↔ Z (Zamiennik)

## Available Methods

### 1. bulkAddCompatibilities()

**Purpose:** Bulk create compatibilities (horizontal/vertical drag pattern)

**Signature:**
```php
public function bulkAddCompatibilities(
    array $partIds,
    array $vehicleIds,
    string $attributeCode,
    int $sourceId = 3
): array
```

**Parameters:**
- `$partIds` - Array of product IDs (spare parts)
- `$vehicleIds` - Array of vehicle_model IDs
- `$attributeCode` - 'original' OR 'replacement'
- `$sourceId` - compatibility_source_id (default: 3 = manual entry)

**Returns:**
```php
[
    'created' => int,      // Number of created compatibilities
    'duplicates' => int,   // Number of skipped duplicates
    'errors' => array      // Array of error messages
]
```

**Example Usage:**
```php
use App\Services\CompatibilityManager;

$manager = app(CompatibilityManager::class);

// Scenario 1: Horizontal drag (1 part × 26 vehicles)
$result = $manager->bulkAddCompatibilities(
    partIds: [123],           // 1 part
    vehicleIds: [1,2,3,...26], // 26 vehicles
    attributeCode: 'original',
    sourceId: 3
);

echo "Created: {$result['created']}, Duplicates: {$result['duplicates']}";
// Output: Created: 26, Duplicates: 0

// Scenario 2: Vertical drag (50 parts × 1 vehicle)
$result = $manager->bulkAddCompatibilities(
    partIds: [101,102,103,...150], // 50 parts
    vehicleIds: [5],                // 1 vehicle
    attributeCode: 'replacement',
    sourceId: 3
);

echo "Created: {$result['created']}, Duplicates: {$result['duplicates']}";
// Output: Created: 50, Duplicates: 0
```

**Safety Limits:**
- Max bulk size: 500 combinations
- Example: 10 parts × 50 vehicles = 500 ✅
- Example: 25 parts × 25 vehicles = 625 ❌ (exceeds limit)

**Transaction Safety:**
- Uses `DB::transaction(..., attempts: 5)` for deadlock resilience
- All-or-nothing operation (rollback on error)

**SKU-First Compliance:**
- ✅ Loads products with SKU
- ✅ Loads vehicles with SKU
- ✅ Inserts with `part_sku` and `vehicle_sku` backup columns

---

### 2. detectDuplicates()

**Purpose:** Preview duplicates/conflicts BEFORE executing bulk operation

**Signature:**
```php
public function detectDuplicates(array $data): array
```

**Parameters:**
```php
$data = [
    ['part_id' => 123, 'vehicle_id' => 1, 'attribute_code' => 'original'],
    ['part_id' => 123, 'vehicle_id' => 2, 'attribute_code' => 'original'],
    // ... more combinations
];
```

**Returns:**
```php
[
    'duplicates' => [
        [
            'part_id' => 123,
            'part_sku' => 'PART-123',
            'part_name' => 'Brake Pad Set',
            'vehicle_id' => 1,
            'vehicle_name' => 'Honda CBR 600 RR (2013-2020)',
            'attribute' => 'original',
            'existing_id' => 456  // Existing compatibility ID
        ],
        // ... more duplicates
    ],
    'conflicts' => [
        [
            'part_id' => 124,
            'part_sku' => 'PART-124',
            'part_name' => 'Oil Filter',
            'vehicle_id' => 2,
            'vehicle_name' => 'Yamaha MT-09 (2015-2020)',
            'requested_attribute' => 'original',
            'existing_attribute' => 'replacement',  // CONFLICT!
            'existing_id' => 789
        ],
        // ... more conflicts
    ]
]
```

**Example Usage:**
```php
// Preview before bulk add
$preview = $manager->detectDuplicates([
    ['part_id' => 123, 'vehicle_id' => 1, 'attribute_code' => 'original'],
    ['part_id' => 123, 'vehicle_id' => 2, 'attribute_code' => 'original'],
    ['part_id' => 124, 'vehicle_id' => 2, 'attribute_code' => 'original'],
]);

if (count($preview['duplicates']) > 0) {
    echo "Warning: " . count($preview['duplicates']) . " exact duplicates will be skipped.";
}

if (count($preview['conflicts']) > 0) {
    echo "Error: " . count($preview['conflicts']) . " conflicts detected!";
    echo "Part {$preview['conflicts'][0]['part_sku']} already has REPLACEMENT but you're adding ORIGINAL.";
}
```

**Use Cases:**
1. **Before bulk add:** Show warning modal with duplicate count
2. **Before copy operation:** Detect conflicts between source/target
3. **Data validation:** Ensure no conflicting attributes exist

---

### 3. copyCompatibilities()

**Purpose:** Copy all compatibilities from one part to another (Excel copy-paste pattern)

**Signature:**
```php
public function copyCompatibilities(
    int $sourcePartId,
    int $targetPartId,
    array $options = ['skip_duplicates' => true, 'replace_existing' => false]
): array
```

**Parameters:**
- `$sourcePartId` - Source product ID (has existing compatibilities)
- `$targetPartId` - Target product ID (will receive copies)
- `$options` - Copy behavior:
  - `skip_duplicates` (bool) - Skip if target already has compatibility (default: true)
  - `replace_existing` (bool) - Replace existing compatibilities (default: false)

**Returns:**
```php
[
    'copied' => int,    // Number of copied compatibilities
    'skipped' => int,   // Number of skipped (duplicates)
    'errors' => array   // Array of error messages
]
```

**Example Usage:**
```php
// Scenario: Part SKU 396 has 26 vehicle compatibilities
// Copy all to Part SKU 388

$result = $manager->copyCompatibilities(
    sourcePartId: 396,
    targetPartId: 388,
    options: ['skip_duplicates' => true, 'replace_existing' => false]
);

echo "Copied: {$result['copied']}, Skipped: {$result['skipped']}";
// Output: Copied: 26, Skipped: 0

// Scenario 2: Replace existing compatibilities
$result = $manager->copyCompatibilities(
    sourcePartId: 396,
    targetPartId: 388,
    options: ['skip_duplicates' => false, 'replace_existing' => true]
);
// Output: Copied: 26, Skipped: 0 (deleted existing before copying)
```

**Copy Behavior:**

| Option | Behavior |
|--------|----------|
| `skip_duplicates: true, replace_existing: false` | Skip if target already has (default) |
| `skip_duplicates: false, replace_existing: true` | Delete existing + copy all |
| `skip_duplicates: false, replace_existing: false` | Copy all, ignore duplicates |

**Reset Fields:**
- `is_verified` = false (requires re-verification)
- `verified_by` = null
- `verified_at` = null

**Preserved Fields:**
- `notes` - Copied from source
- `compatibility_source_id` - Copied from source
- `compatibility_attribute_id` - Copied from source

---

### 4. updateCompatibilityType()

**Purpose:** Toggle compatibility type O (Oryginał) ↔ Z (Zamiennik)

**Signature:**
```php
public function updateCompatibilityType(
    int $compatibilityId,
    string $newAttributeCode
): bool
```

**Parameters:**
- `$compatibilityId` - vehicle_compatibility.id
- `$newAttributeCode` - 'original' OR 'replacement'

**Returns:** `true` on success

**Example Usage:**
```php
// User mistake: Marked as "Oryginał" but should be "Zamiennik"
$success = $manager->updateCompatibilityType(
    compatibilityId: 456,
    newAttributeCode: 'replacement'
);

if ($success) {
    echo "Compatibility type updated: Oryginał → Zamiennik";
}
```

**Cache Invalidation:**
- Automatically invalidates cache if `shop_id` present

---

## Validation Rule

**Class:** `App\Rules\CompatibilityBulkValidation`

**Usage in Controller/Request:**
```php
use App\Rules\CompatibilityBulkValidation;

$request->validate([
    'bulk_operation' => ['required', 'array', new CompatibilityBulkValidation()],
]);
```

**Validation Checks:**
- ✅ Part IDs exist in products table
- ✅ Vehicle IDs exist in vehicle_models table
- ✅ Attribute code valid ('original', 'replacement', 'performance', 'universal')
- ✅ Max bulk size ≤ 500 combinations
- ✅ No circular references (optional - commented out)

**Expected Data Structure:**
```php
[
    'part_ids' => [1, 2, 3],
    'vehicle_ids' => [10, 11, 12],
    'attribute_code' => 'original'
]
```

---

## Test Scenarios

### Scenario 1: Normal Bulk Add (2 parts × 3 vehicles)

```php
$result = $manager->bulkAddCompatibilities(
    partIds: [101, 102],
    vehicleIds: [1, 2, 3],
    attributeCode: 'original'
);

// Expected result:
// - created: 6 (2 × 3)
// - duplicates: 0
// - errors: []
```

### Scenario 2: Duplicates Detection

```php
// First bulk add
$manager->bulkAddCompatibilities(
    partIds: [101],
    vehicleIds: [1, 2, 3],
    attributeCode: 'original'
);
// Created: 3

// Second bulk add (same data)
$result = $manager->bulkAddCompatibilities(
    partIds: [101],
    vehicleIds: [1, 2, 3],
    attributeCode: 'original'
);

// Expected result:
// - created: 0
// - duplicates: 3 (all skipped)
// - errors: []
```

### Scenario 3: Conflict Detection

```php
// Part 101 already has REPLACEMENT for Vehicle 1
// Try to add ORIGINAL for same combination

$preview = $manager->detectDuplicates([
    ['part_id' => 101, 'vehicle_id' => 1, 'attribute_code' => 'original']
]);

// Expected result:
// - duplicates: [] (not exact duplicate)
// - conflicts: [
//     ['part_id' => 101, 'vehicle_id' => 1, 'requested_attribute' => 'original', 'existing_attribute' => 'replacement']
//   ]
```

### Scenario 4: Large Bulk Add (10 parts × 50 vehicles = 500)

```php
$result = $manager->bulkAddCompatibilities(
    partIds: range(1, 10),    // 10 parts
    vehicleIds: range(1, 50), // 50 vehicles
    attributeCode: 'original'
);

// Expected result:
// - created: 500 ✅ (at limit)
// - duplicates: 0
// - errors: []
```

### Scenario 5: Exceeds Bulk Limit (25 × 25 = 625)

```php
$result = $manager->bulkAddCompatibilities(
    partIds: range(1, 25),
    vehicleIds: range(1, 25),
    attributeCode: 'original'
);

// Expected result:
// - created: 0
// - duplicates: 0
// - errors: ["Bulk size exceeds maximum (500 combinations). Requested: 625"]
```

### Scenario 6: Copy Compatibilities (26 → 1)

```php
// Part SKU 396 has 26 vehicle compatibilities
$result = $manager->copyCompatibilities(
    sourcePartId: 396,
    targetPartId: 388
);

// Expected result:
// - copied: 26
// - skipped: 0
// - errors: []
```

### Scenario 7: Toggle Type (O → Z)

```php
// Compatibility ID 456 is currently "Oryginał"
$success = $manager->updateCompatibilityType(
    compatibilityId: 456,
    newAttributeCode: 'replacement'
);

// Expected result: true
// Database: compatibility_attribute_id updated to "replacement"
```

---

## Performance Considerations

### Batch Insert Optimization

**Problem:** 100 compatibilities = 100 INSERT queries (slow)

**Solution:** Use Eloquent mass insert (future optimization)

```php
// Current implementation (loop):
foreach ($products as $product) {
    foreach ($vehicles as $vehicle) {
        VehicleCompatibility::create([...]); // 100 queries
    }
}

// Optimized (batch insert):
$batchData = [];
foreach ($products as $product) {
    foreach ($vehicles as $vehicle) {
        $batchData[] = [...];
    }
}
VehicleCompatibility::insert($batchData); // 1 query ✅
```

**Note:** Current implementation prioritizes transaction safety. Batch optimization can be added in FAZA 2.2.

### Eager Loading

```php
// ✅ Good: Eager load relationships
$compatibilities = VehicleCompatibility::with([
    'product:id,sku,name',
    'vehicleModel:id,sku,brand,model',
    'compatibilityAttribute:id,code,name'
])->whereIn('product_id', $partIds)->get();

// ❌ Bad: N+1 problem
$compatibilities = VehicleCompatibility::whereIn('product_id', $partIds)->get();
foreach ($compatibilities as $compat) {
    echo $compat->product->name; // N queries!
}
```

---

## Error Handling

**All methods follow this pattern:**

```php
try {
    // Operation logic
    DB::transaction(function () { ... }, attempts: 5);

    Log::info('operation COMPLETED', [...]);
    return $stats;

} catch (\Exception $e) {
    Log::error('operation FAILED', ['error' => $e->getMessage()]);
    $stats['errors'][] = $e->getMessage();
    return $stats;
}
```

**Common Errors:**

1. **Products not found:**
   - `"No products found with provided IDs"`
   - Check: Part IDs exist in products table

2. **Vehicles not found:**
   - `"No vehicles found with provided IDs"`
   - Check: Vehicle IDs exist in vehicle_models table

3. **Invalid attribute code:**
   - `"Invalid attribute code: xyz"`
   - Valid codes: 'original', 'replacement', 'performance', 'universal'

4. **Bulk size exceeded:**
   - `"Bulk size exceeds maximum (500 combinations). Requested: 625"`
   - Reduce number of parts/vehicles

5. **Source has no compatibilities:**
   - `"Source product has no compatibilities to copy"`
   - Check: Source part must have existing compatibilities

---

## SKU-First Compliance Checklist

✅ **All methods follow SKU-first architecture:**

- [x] Load products with SKU: `Product::select('id', 'sku', 'name')`
- [x] Load vehicles with SKU: `VehicleModel::select('id', 'sku', 'brand', 'model')`
- [x] Insert with SKU backup: `part_sku`, `vehicle_sku` columns
- [x] Cache keys use SKU: `invalidateCache($sku, $shopId)`
- [x] Use attribute codes (not IDs): `where('code', 'original')`

---

## Integration with Livewire (FAZA 2.2)

**Next Phase:** livewire-specialist will create UI modal for bulk operations

**Expected Livewire Component:**
```php
// app/Http/Livewire/Admin/Compatibility/BulkOperationsModal.php

class BulkOperationsModal extends Component
{
    public $mode; // 'horizontal', 'vertical', 'copy', 'toggle'
    public $selectedParts = [];
    public $selectedVehicles = [];
    public $attributeCode = 'original';

    public function executeBulkAdd()
    {
        $manager = app(CompatibilityManager::class);

        $result = $manager->bulkAddCompatibilities(
            partIds: $this->selectedParts,
            vehicleIds: $this->selectedVehicles,
            attributeCode: $this->attributeCode
        );

        $this->dispatch('bulk-operation-completed', $result);
    }
}
```

---

## Conclusion

**Implementation Status:** ✅ COMPLETED

**Deliverables:**
- ✅ 4 new methods in CompatibilityManager
- ✅ Transaction safety (attempts: 5)
- ✅ SKU-first compliance
- ✅ Validation rule
- ✅ Test scenarios documented
- ✅ Usage examples

**Next Steps (FAZA 2.2):**
- livewire-specialist: Create modal UI for bulk operations
- frontend-specialist: Excel-inspired drag-and-drop interface
- Testing: Manual testing with real data

**Files Modified:**
- `app/Services/CompatibilityManager.php` (+400 lines)

**Files Created:**
- `app/Rules/CompatibilityBulkValidation.php` (NEW)
- `app/Services/COMPATIBILITY_BULK_OPERATIONS_USAGE_GUIDE.md` (NEW)

**ETAP Status:** ETAP_05d FAZA 2.1 Backend Service Layer → ✅ COMPLETED
