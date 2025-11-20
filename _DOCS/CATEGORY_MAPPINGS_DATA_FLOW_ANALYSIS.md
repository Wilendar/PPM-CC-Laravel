# category_mappings Data Flow Analysis

**Document Version**: 1.0
**Date**: 2025-11-18
**Status**: CRITICAL - Architecture Inconsistency Detected

---

## QUICK REFERENCE: Format Specifications

### Format 1: UI Format (ProductFormSaver)
```json
{
  "selected": [1, 2, 3],
  "primary": 1
}
```
**Keys**: `selected` (array of PPM category IDs), `primary` (single PPM category ID)
**Source**: CategoryPicker Livewire component
**Written By**: ProductFormSaver::saveShopData()
**Read By**: ProductMultiStoreManager::loadShopData()

---

### Format 2: PrestaShop→PrestaShop (pullShopData)
```json
{
  "9": 9,
  "15": 15,
  "800": 800
}
```
**Keys**: PrestaShop Category IDs (as strings)
**Values**: PrestaShop Category IDs (as integers)
**Source**: PrestaShop API response
**Written By**: ProductForm::pullShopData()
**Read By**: ProductTransformer (incorrectly), ProductSyncStrategy

**PRODUCTION EXAMPLE** (Record #10390):
```json
{
  "9": 9,
  "15": 15,
  "800": 800,
  "981": 981,
  "983": 983,
  "985": 985,
  "2350": 2350
}
```

---

### Format 3: PPM→PrestaShop (Expected by ProductTransformer)
```json
{
  "1": 9,
  "2": 15,
  "3": 800
}
```
**Keys**: PPM Category IDs (as strings)
**Values**: PrestaShop Category IDs (as integers)
**Source**: CategoryMapper::mapToPrestaShop()
**Written By**: NONE (not implemented!)
**Read By**: ProductTransformer::buildCategoryAssociations()

---

## DATA FLOW SCENARIOS

### SCENARIO 1: User Selects Categories (Normal Flow)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. USER ACTION                                                  │
│ User opens product, switches to shop tab, selects categories   │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. UI INTERACTION                                               │
│ Component: CategoryPicker (Livewire)                            │
│ Action: User checks checkboxes, selects primary                │
│ State: $this->shopCategories[$shopId] = [                      │
│          'selected' => [1, 2, 3],                               │
│          'primary' => 1                                         │
│        ]                                                        │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. SAVE OPERATION                                               │
│ File: ProductFormSaver.php:220-230                              │
│ Code: if (isset($this->component->shopCategories[$shopId])) {  │
│         $shopData['category_mappings'] =                        │
│           $this->component->shopCategories[$shopId];            │
│       }                                                         │
│                                                                 │
│ Data Written: {"selected": [1,2,3], "primary": 1}              │
│ Format: UI Format                                               │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. DATABASE STORAGE                                             │
│ Table: product_shop_data                                        │
│ Column: category_mappings (JSON)                                │
│ Value: {"selected": [1,2,3], "primary": 1}                     │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. SYNC TO PRESTASHOP (User clicks "Synchronizuj")             │
│ File: ProductTransformer.php:270-320                            │
│                                                                 │
│ Code: $categoryIds = array_keys($shopData->category_mappings); │
│                                                                 │
│ ❌ BUG: Keys = ["selected", "primary"]                         │
│ ❌ Tries to map "selected" as PPM Category ID!                 │
│ ❌ CategoryMapper::mapToPrestaShop("selected", $shop) → NULL   │
│ ❌ RESULT: Sync fails or sends wrong categories                │
└─────────────────────────────────────────────────────────────────┘
```

**Expected Behavior**: Sync should send categories 1, 2, 3 to PrestaShop
**Actual Behavior**: Sync fails (tries to map "selected" string as category ID)

---

### SCENARIO 2: User Pulls Data from Shop (pullShopData)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. USER ACTION                                                  │
│ User clicks "Wczytaj z sklepu" button                           │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. FETCH FROM PRESTASHOP                                        │
│ File: ProductForm.php:3933-3974                                 │
│ API Call: GET /api/products/{prestashopId}?display=full        │
│                                                                 │
│ Response: {                                                     │
│   "id": 12345,                                                  │
│   "categories": [                                               │
│     {"id": 9},                                                  │
│     {"id": 15},                                                 │
│     {"id": 800}                                                 │
│   ]                                                             │
│ }                                                               │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. PARSE CATEGORIES                                             │
│ File: ProductForm.php:3976-3995                                 │
│ Code: $categoryMappings = [];                                   │
│       foreach ($productData['categories'] as $categoryAssoc) {  │
│         $prestashopCategoryId = $categoryAssoc['id'];           │
│         $categoryMappings[(string)$prestashopCategoryId] =      │
│           (int)$prestashopCategoryId;                           │
│       }                                                         │
│                                                                 │
│ Result: {"9": 9, "15": 15, "800": 800}                         │
│ Format: PrestaShop→PrestaShop                                   │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. SAVE TO DATABASE                                             │
│ File: ProductForm.php:3998-4016                                 │
│ Code: $productShopData->fill([                                  │
│         'category_mappings' => $categoryMappings,               │
│       ]);                                                       │
│       $productShopData->save();                                 │
│                                                                 │
│ Data Written: {"9": 9, "15": 15, "800": 800}                   │
│ Format: PrestaShop→PrestaShop                                   │
│                                                                 │
│ ⚠️ OVERWRITES any existing UI Format data!                     │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. UPDATE UI CACHE                                              │
│ File: ProductForm.php:4020-4033                                 │
│ Code: $this->shopData[$shopId] = [                              │
│         'category_mappings' => $productShopData->category_mappings,│
│       ];                                                        │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. RELOAD TO FORM (if current shop)                             │
│ File: ProductForm.php:4036-4038                                 │
│ Code: if ($this->activeShopId === $shopId) {                   │
│         $this->loadShopDataToForm($shopId);                     │
│       }                                                         │
│                                                                 │
│ Calls: ProductMultiStoreManager::loadShopData()                │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. LOAD SHOP DATA TO UI                                         │
│ File: ProductMultiStoreManager.php:65-74                        │
│ Code: if (!empty($shopData->category_mappings)) {              │
│         $this->component->shopCategories[$shopData->shop_id] = [│
│           'selected' => $shopData->category_mappings['selected'] ?? [],│
│           'primary' => $shopData->category_mappings['primary'] ?? null,│
│         ];                                                      │
│       }                                                         │
│                                                                 │
│ ❌ BUG: category_mappings = {"9": 9, "15": 15}                 │
│ ❌ No 'selected' key → returns []                              │
│ ❌ No 'primary' key → returns null                             │
│ ❌ RESULT: UI shows NO categories selected!                    │
└─────────────────────────────────────────────────────────────────┘
```

**Expected Behavior**: UI should show categories 9, 15, 800 selected
**Actual Behavior**: UI shows NO categories (data loss in UI layer)

---

### SCENARIO 3: Checksum Calculation (Sync Detection)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. SYNC SYSTEM CHECKS IF PRODUCT NEEDS SYNC                    │
│ File: ProductSyncStrategy.php:341-372                           │
│ Method: calculateChecksum(Product $model, PrestaShopShop $shop)│
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. INCLUDE CATEGORIES IN CHECKSUM                              │
│ Code: if ($shopData && !empty($shopData->category_mappings)) { │
│         $data['categories'] = collect($shopData->category_mappings)│
│           ->values()                                            │
│           ->sort()                                              │
│           ->values()                                            │
│           ->toArray();                                          │
│       }                                                         │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. EXTRACT VALUES (SCENARIO A: UI Format)                      │
│ Input: {"selected": [1,2,3], "primary": 1}                     │
│ Values: [[1,2,3], 1]                                            │
│ ❌ BUG: Array of mixed types (array + int)                     │
│ ❌ RESULT: Invalid checksum (array serialization issues)       │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. EXTRACT VALUES (SCENARIO B: PrestaShop Format)              │
│ Input: {"9": 9, "15": 15, "800": 800}                          │
│ Values: [9, 15, 800]                                            │
│ Sort: [9, 15, 800]                                              │
│ ✅ Works! (but represents PrestaShop IDs, not PPM IDs)         │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. HASH CALCULATION                                             │
│ Code: return hash('xxh3', json_encode($data));                 │
│                                                                 │
│ SCENARIO A (UI Format): Hash of invalid structure               │
│ SCENARIO B (PrestaShop Format): Hash of PrestaShop category IDs│
│                                                                 │
│ ⚠️ PROBLEM: Checksum doesn't represent PPM state!              │
└─────────────────────────────────────────────────────────────────┘
```

**Expected Behavior**: Checksum should change when user selects different PPM categories
**Actual Behavior**:
- Scenario A: Checksum includes array structure (unstable)
- Scenario B: Checksum includes PrestaShop IDs (not PPM IDs)

---

### SCENARIO 4: Sync to PrestaShop (buildCategoryAssociations)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. PREPARE PRODUCT SYNC PAYLOAD                                │
│ File: ProductTransformer.php:268-320                            │
│ Method: buildCategoryAssociations(Product $product,            │
│                                    PrestaShopShop $shop)        │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. CHECK SHOP-SPECIFIC MAPPINGS                                │
│ Code: $shopData = $product->dataForShop($shop->id)->first();   │
│       if ($shopData && !empty($shopData->category_mappings)) { │
│         $categoryIds = array_keys($shopData->category_mappings);│
│       }                                                         │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3A. EXTRACT KEYS (SCENARIO A: UI Format)                       │
│ Input: {"selected": [1,2,3], "primary": 1}                     │
│ Keys: ["selected", "primary"]                                  │
│ ❌ BUG: Strings, not integers!                                 │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4A. MAP TO PRESTASHOP (SCENARIO A)                             │
│ Code: foreach ($categoryIds as $categoryId) {                  │
│         if (isset($shopData->category_mappings[$categoryId])) {│
│           $prestashopCategoryId = (int)$shopData->category_mappings[$categoryId];│
│         } else {                                                │
│           $prestashopCategoryId = $this->categoryMapper->mapToPrestaShop($categoryId, $shop);│
│         }                                                       │
│       }                                                         │
│                                                                 │
│ Loop 1: $categoryId = "selected"                               │
│   Check: isset($shopData->category_mappings["selected"]) → TRUE│
│   Value: [1, 2, 3] (array)                                     │
│   Cast: (int)[1, 2, 3] → 1 (PHP casts array to 1!)            │
│   ❌ RESULT: Sends category ID 1 to PrestaShop (WRONG!)        │
│                                                                 │
│ Loop 2: $categoryId = "primary"                                │
│   Check: isset($shopData->category_mappings["primary"]) → TRUE │
│   Value: 1                                                     │
│   Cast: (int)1 → 1                                             │
│   ✅ Accidentally works, but coincidental!                     │
└─────────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────────┐
│ 3B. EXTRACT KEYS (SCENARIO B: PrestaShop Format)               │
│ Input: {"9": 9, "15": 15, "800": 800}                          │
│ Keys: ["9", "15", "800"] (strings)                             │
│ ⚠️ Interpreted as PPM Category IDs (WRONG ASSUMPTION!)         │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4B. MAP TO PRESTASHOP (SCENARIO B)                             │
│ Loop 1: $categoryId = "9"                                      │
│   Check: isset($shopData->category_mappings["9"]) → TRUE       │
│   Value: 9                                                     │
│   ✅ Accidentally works! (because key == value)                │
│                                                                 │
│ BUT if we had PPM→PrestaShop mapping:                          │
│   Input: {"1": 9} (PPM ID 1 → PrestaShop ID 9)                 │
│   Loop 1: $categoryId = "1"                                    │
│   Check: isset($shopData->category_mappings["1"]) → TRUE       │
│   Value: 9                                                     │
│   ✅ CORRECT! Sends PrestaShop ID 9                            │
└─────────────────────────────────────────────────────────────────┘
```

**Expected Behavior**: Should send correct PrestaShop category IDs based on PPM selections
**Actual Behavior**:
- Scenario A (UI Format): Sends wrong IDs (array cast issue)
- Scenario B (PrestaShop Format): Accidentally works (because key == value)
- Scenario C (PPM→PrestaShop Format): Would work correctly (but not used!)

---

## COMPONENT RESPONSIBILITY MATRIX

| Component | Responsibility | Current Format | Expected Format | Status |
|-----------|---------------|----------------|-----------------|--------|
| **CategoryPicker (UI)** | Capture user selections | UI Format | UI Format | ✅ OK |
| **ProductFormSaver** | Save UI state → DB | UI Format | PPM→PrestaShop | ❌ WRONG |
| **ProductForm::pullShopData** | Fetch PrestaShop → DB | PrestaShop→PrestaShop | PPM→PrestaShop | ❌ WRONG |
| **ProductMultiStoreManager** | Load DB → UI state | UI Format (reads) | UI Format | ⚠️ PARTIAL (breaks on PrestaShop format) |
| **ProductTransformer** | DB → PrestaShop payload | PPM→PrestaShop (expects) | PPM→PrestaShop | ⚠️ PARTIAL (no format validation) |
| **ProductSyncStrategy** | Checksum calculation | Agnostic (values only) | PPM IDs | ❌ WRONG (uses PrestaShop IDs) |
| **CategoryMapper** | PPM ↔ PrestaShop mapping | N/A (returns PPM ID) | N/A | ✅ OK (but not used by pullShopData!) |

---

## MISSING COMPONENTS

### 1. UI State → PPM→PrestaShop Converter
**Location**: Should be in ProductFormSaver
**Current**: Missing! Directly saves UI format
**Needed**:
```php
private function convertUIStateToCategoryMappings(array $uiState, int $shopId): array
{
    $mappings = [];

    foreach ($uiState['selected'] ?? [] as $ppmCategoryId) {
        // Use CategoryMapper to get PrestaShop ID
        $prestashopId = $this->categoryMapper->mapToPrestaShop($ppmCategoryId, $shop);

        if ($prestashopId) {
            $mappings[(string)$ppmCategoryId] = $prestashopId;
        }
    }

    return $mappings;
}
```

### 2. PrestaShop IDs → PPM IDs Converter
**Location**: Should be in ProductForm::pullShopData
**Current**: Missing! Directly saves PrestaShop→PrestaShop
**Needed**:
```php
private function convertPrestaShopIDsToPPMIDs(array $prestashopIds, PrestaShopShop $shop): array
{
    $mappings = [];

    foreach ($prestashopIds as $prestashopId) {
        // Use CategoryMapper to reverse map
        $ppmId = $this->categoryMapper->mapFromPrestaShop($prestashopId, $shop);

        if ($ppmId) {
            $mappings[(string)$ppmId] = $prestashopId;
        } else {
            // No mapping exists - log warning
            Log::warning('PrestaShop category has no PPM mapping', [
                'prestashop_id' => $prestashopId,
                'shop_id' => $shop->id,
            ]);
        }
    }

    return $mappings;
}
```

### 3. PPM→PrestaShop → UI State Converter
**Location**: Should be in ProductMultiStoreManager
**Current**: Missing! Assumes UI format directly
**Needed**:
```php
private function convertCategoryMappingsToUIState(array $mappings): array
{
    return [
        'selected' => array_keys($mappings), // PPM IDs
        'primary' => array_key_first($mappings), // First PPM ID as default primary
    ];
}
```

### 4. Format Validator
**Location**: Should be in ProductShopData model (accessor/mutator)
**Current**: Missing! No validation
**Needed**:
```php
// In ProductShopData model
public function setCategoryMappingsAttribute($value)
{
    if (!is_array($value)) {
        throw new \InvalidArgumentException('category_mappings must be an array');
    }

    // Validate format: PPM ID (string key) → PrestaShop ID (int value)
    foreach ($value as $key => $val) {
        if (!is_string($key) || !is_numeric($key)) {
            throw new \InvalidArgumentException('category_mappings keys must be numeric strings (PPM IDs)');
        }

        if (!is_int($val) && !is_numeric($val)) {
            throw new \InvalidArgumentException('category_mappings values must be integers (PrestaShop IDs)');
        }
    }

    $this->attributes['category_mappings'] = json_encode($value);
}
```

---

## SUMMARY

**Current State**: Chaotic - 3 different formats, no converters, no validation
**Root Cause**: Lack of architectural specification for `category_mappings` format
**Impact**: Data corruption, sync failures, UI inconsistencies
**Solution**: Implement canonical PPM→PrestaShop format + converters + validation

**Next Step**: User decision on Option A (recommended), B, or C from diagnosis report

---

**Document Prepared By**: debugger agent
**Related Report**: `_AGENT_REPORTS/CRITICAL_DIAGNOSIS_category_mappings_architecture_2025-11-18_REPORT.md`
