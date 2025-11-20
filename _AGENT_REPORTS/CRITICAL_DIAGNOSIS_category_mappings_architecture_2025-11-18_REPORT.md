# CRITICAL DIAGNOSIS: category_mappings Architecture Inconsistency

**Date**: 2025-11-18
**Agent**: debugger
**Priority**: CRITICAL
**Status**: DIAGNOSIS COMPLETE - AWAITING ARCHITECTURE DECISION

---

## EXECUTIVE SUMMARY

Deep analysis wykazała **fundamentalną niespójność architektury** w systemie `category_mappings`:

**ROOT CAUSE**: **TRZY RÓŻNE FORMATY** używane przez różne komponenty systemu bez jednolitej specyfikacji:
1. **UI Format** (ProductFormSaver) - `{"selected": [1,2,3], "primary": 1}`
2. **PrestaShop→PrestaShop Format** (pullShopData) - `{"9": 9, "15": 15}` (key == value)
3. **PPM→PrestaShop Format** (ProductTransformer expects) - `{"1": 9, "2": 15}` (key != value)

**IMPACT**:
- FIX #11 rozwiązał symptom (dodał checksum), ale NIE rozwiązał root cause
- Komponenty zapisują Format A, ale odczytują oczekując Format B → data loss, sync failures
- CategoryMapper zwraca PPM→PrestaShop, ale pullShopData zapisuje PrestaShop→PrestaShop

---

## 1. COMPLETE FILE USAGE ANALYSIS

### Files Using `category_mappings` (12 unique files, 46 occurrences):

```
app/Http/Livewire/Admin/Shops/AddShop.php
app/Http/Livewire/Products/Listing/ProductList.php
app/Http/Livewire/Products/Management/ProductForm.php
app/Http/Livewire/Products/Management/ProductForm-Original-Backup.php
app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
app/Http/Livewire/Products/Management/Services/ProductMultiStoreManager.php
app/Models/PrestaShopShop.php
app/Models/ProductShopData.php
app/Services/PrestaShop/PrestaShopService.php
app/Services/PrestaShop/ProductTransformer.php
app/Services/PrestaShop/Sync/ProductSyncStrategy.php
app/Services/SyncVerificationService.php
```

---

## 2. FORMAT SPECIFICATION BY COMPONENT

### 2.1 ProductFormSaver.php (WRITES UI Format)

**Location**: `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php:220-230`

**Code**:
```php
// Add category mappings if exist
if (isset($this->component->shopCategories[$shopId])) {
    $shopData['category_mappings'] = $this->component->shopCategories[$shopId];
}
```

**WRITES Format**:
```json
{
  "selected": [1, 2, 3],
  "primary": 1
}
```

**Source**: `$this->component->shopCategories[$shopId]` (Livewire property from CategoryPicker UI)

**Intent**: Store UI state (selected categories + primary category for this shop)

---

### 2.2 ProductForm::pullShopData() (WRITES PrestaShop→PrestaShop Format)

**Location**: `app/Http/Livewire/Products/Management/ProductForm.php:3976-4031`

**Code**:
```php
// FIX 2025-11-18 (#10.2): Map PrestaShop categories to category_mappings JSON
// Format: {"2": 2, "15": 15, "23": 23} (PrestaShop ID → PrestaShop ID)
$categoryMappings = [];
if (!empty($productData['categories'])) {
    foreach ($productData['categories'] as $categoryAssoc) {
        $prestashopCategoryId = $categoryAssoc['id'] ?? null;
        if ($prestashopCategoryId) {
            // Store as string key (JSON standard) → int value
            $categoryMappings[(string) $prestashopCategoryId] = (int) $prestashopCategoryId;
        }
    }
}
```

**WRITES Format**:
```json
{
  "9": 9,
  "15": 15,
  "800": 800,
  "981": 981
}
```

**Source**: PrestaShop API response (`GET /products/{id}` → `categories` associations)

**Intent**: Store PrestaShop category IDs for this product (pulled from shop)

**PRODUCTION DATA CONFIRMATION**:
```
Record ID: 10390
Product: 11033 | Shop: 1 | Status: synced
category_mappings: {"9":9,"15":15,"800":800,"981":981,"983":983,"985":985,"2350":2350}
LIKELY: PrestaShop→PrestaShop (pullShopData format)
```

---

### 2.3 ProductTransformer::buildCategoryAssociations() (EXPECTS PPM→PrestaShop Format)

**Location**: `app/Services/PrestaShop/ProductTransformer.php:270-320`

**Code**:
```php
// FIX #10.1: Check shop-specific category_mappings FIRST
if ($shopData && !empty($shopData->category_mappings)) {
    // Shop has specific category configuration - use it!
    $categoryIds = array_keys($shopData->category_mappings);
}

// Map each PPM category ID to PrestaShop category ID
foreach ($categoryIds as $categoryId) {
    // Check if shop-specific mapping already has PrestaShop ID
    if ($shopData && isset($shopData->category_mappings[$categoryId])) {
        // Shop data contains direct PrestaShop category ID
        $prestashopCategoryId = (int) $shopData->category_mappings[$categoryId];
    } else {
        // Map via CategoryMapper (shop_mappings table)
        $prestashopCategoryId = $this->categoryMapper->mapToPrestaShop((int) $categoryId, $shop);
    }
}
```

**EXPECTS Format**:
```json
{
  "1": 9,
  "2": 15,
  "3": 800
}
```
(PPM Category ID → PrestaShop Category ID)

**Logic**:
1. Extract keys as PPM category IDs
2. IF key exists in category_mappings → use value as PrestaShop ID
3. ELSE → fallback to CategoryMapper (shop_mappings table)

**PROBLEM**:
- IF category_mappings = `{"9": 9}` (PrestaShop→PrestaShop from pullShopData)
- THEN keys = `[9]` → interprets 9 as PPM category ID (WRONG!)
- Tries to map PPM category ID 9 → PrestaShop ID
- Result: Incorrect category associations sent to PrestaShop

---

### 2.4 ProductSyncStrategy::calculateChecksum() (READS category_mappings)

**Location**: `app/Services/PrestaShop/Sync/ProductSyncStrategy.php:341-372`

**Code**:
```php
// FIX 2025-11-18 (#11): Include shop-specific category_mappings in checksum
if ($shopData && !empty($shopData->category_mappings)) {
    // Use shop-specific category mappings (PrestaShop category IDs)
    $data['categories'] = collect($shopData->category_mappings)
        ->values()
        ->sort()
        ->values()
        ->toArray();
} else {
    // Fallback to global product categories (PPM category IDs)
    $data['categories'] = $model->categories->pluck('id')->sort()->values()->toArray();
}
```

**READS Format**: Agnostic (extracts values only)

**Logic**:
1. Take values from category_mappings (ignores keys)
2. Sort and include in checksum

**PROBLEM**:
- IF category_mappings = `{"selected": [1,2,3], "primary": 1}` (UI format)
- THEN values = `[[1,2,3], 1]` → array of mixed types → invalid checksum
- IF category_mappings = `{"9": 9}` (PrestaShop format)
- THEN values = `[9]` → works, but represents PrestaShop IDs (not PPM IDs)

---

### 2.5 ProductMultiStoreManager::loadShopData() (READS UI Format)

**Location**: `app/Http/Livewire/Products/Management/Services/ProductMultiStoreManager.php:65-74`

**Code**:
```php
// Load shop-specific categories if they exist
if (!empty($shopData->category_mappings)) {
    $this->component->shopCategories[$shopData->shop_id] = [
        'selected' => $shopData->category_mappings['selected'] ?? [],
        'primary' => $shopData->category_mappings['primary'] ?? null,
    ];
}
```

**EXPECTS Format**:
```json
{
  "selected": [1, 2, 3],
  "primary": 1
}
```

**PROBLEM**:
- IF category_mappings = `{"9": 9}` (PrestaShop format from pullShopData)
- THEN `$shopData->category_mappings['selected']` = NULL
- Result: UI shows NO categories selected (data loss!)

---

### 2.6 CategoryMapper::mapFromPrestaShop() (RETURNS PPM IDs)

**Location**: `app/Services/PrestaShop/CategoryMapper.php:70-84`

**Code**:
```php
public function mapFromPrestaShop(int $prestashopId, PrestaShop $shop): ?int
{
    $mapping = ShopMapping::where('shop_id', $shop->id)
        ->where('mapping_type', ShopMapping::TYPE_CATEGORY)
        ->where('prestashop_id', $prestashopId)
        ->where('is_active', true)
        ->first();

    if (!$mapping) {
        return null;
    }

    // PPM value is stored as string, cast to int
    return (int) $mapping->ppm_value;
}
```

**RETURNS**: PPM Category ID (int)

**Usage**: Convert PrestaShop category IDs → PPM category IDs

**PROBLEM**: pullShopData nie używa tego! Zapisuje PrestaShop→PrestaShop directly.

---

## 3. DATA FLOW DIAGRAM

### Current (Broken) Flow:

```
┌─────────────────────────────────────────────────────────────────┐
│ USER ACTION 1: User selects categories in UI                   │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ ProductFormSaver::saveShopData()                                │
│ WRITES: {"selected": [1,2,3], "primary": 1}                    │
│ FORMAT: UI Format (PPM Category IDs)                           │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ DB: product_shop_data.category_mappings                         │
│ STORES: {"selected": [1,2,3], "primary": 1}                    │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ ProductTransformer::buildCategoryAssociations()                 │
│ READS: {"selected": [1,2,3], "primary": 1}                     │
│ EXPECTS: {"1": 9, "2": 15} (PPM→PrestaShop)                    │
│ GETS KEYS: ["selected", "primary"]                             │
│ ❌ FAILS: Tries to map "selected" as PPM category ID!          │
└─────────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────────┐
│ USER ACTION 2: User clicks "Wczytaj z sklepu" (pullShopData)   │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ ProductForm::pullShopData()                                     │
│ WRITES: {"9": 9, "15": 15}                                     │
│ FORMAT: PrestaShop→PrestaShop (PrestaShop Category IDs)        │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ DB: product_shop_data.category_mappings                         │
│ OVERWRITES: {"9": 9, "15": 15}                                 │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ ProductMultiStoreManager::loadShopData()                        │
│ READS: {"9": 9, "15": 15}                                      │
│ EXPECTS: {"selected": [...], "primary": ...}                   │
│ GETS: NULL (no 'selected' key)                                 │
│ ❌ FAILS: UI shows NO categories selected!                     │
└─────────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────────┐
│ ProductTransformer::buildCategoryAssociations()                 │
│ READS: {"9": 9, "15": 15}                                      │
│ EXPECTS: {"1": 9, "2": 15} (PPM→PrestaShop)                    │
│ GETS KEYS: [9, 15]                                             │
│ ❌ INTERPRETS: 9, 15 as PPM Category IDs (WRONG!)              │
│ ❌ RESULT: Sends incorrect categories to PrestaShop!           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. PRODUCTION DATABASE EVIDENCE

**Query**: `SELECT id, product_id, shop_id, category_mappings FROM product_shop_data WHERE category_mappings IS NOT NULL`

**Results**:
```
TOTAL RECORDS WITH category_mappings: 1

=== Record ID: 10390 ===
Product: 11033 | Shop: 1 | Status: synced
Last Pulled: 2025-11-18 12:27:02
category_mappings (raw JSON): {"9":9,"15":15,"800":800,"981":981,"983":983,"985":985,"2350":2350}
category_mappings (decoded): {
    "9": 9,
    "15": 15,
    "800": 800,
    "981": 981,
    "983": 983,
    "985": 985,
    "2350": 2350
}

FORMAT: Simple key-value array
  Keys: 9, 15, 800, 981, 983, 985, 2350
  Values: 9, 15, 800, 981, 983, 985, 2350
  LIKELY: PrestaShop→PrestaShop (pullShopData format)
```

**Analysis**:
- ONLY 1 record ma category_mappings (świeżo po pullShopData)
- Format: PrestaShop→PrestaShop (keys == values)
- Brak UI Format records w produkcji (prawdopodobnie nadpisane przez pullShopData)

---

## 5. ROOT CAUSE ANALYSIS

### Primary Root Cause:
**Brak jednolitej specyfikacji formatu `category_mappings`** → każdy komponent używa własnego formatu:

1. **ProductFormSaver** zakłada: UI format (`{"selected": [...], "primary": ...}`)
2. **pullShopData** zapisuje: PrestaShop format (`{"9": 9, "15": 15}`)
3. **ProductTransformer** oczekuje: PPM→PrestaShop format (`{"1": 9, "2": 15}`)
4. **ProductMultiStoreManager** oczekuje: UI format
5. **ProductSyncStrategy** jest agnostic (używa values tylko)

### Secondary Root Causes:

#### 5.1 Semantic Confusion: "category_mappings" Name
- Nazwa sugeruje: "Mappings from X to Y"
- Ale co jest X, a co Y?
  - UI Format: X = PPM categories, Y = selected state
  - PrestaShop Format: X = PrestaShop ID, Y = PrestaShop ID (tautology!)
  - Expected Format: X = PPM ID, Y = PrestaShop ID

#### 5.2 pullShopData nie używa CategoryMapper
- CategoryMapper::mapFromPrestaShop() exists! (PrestaShop ID → PPM ID)
- pullShopData ignoruje to i zapisuje PrestaShop→PrestaShop directly
- Dlaczego? Nie ma odwrotnego mapowania (PPM ID → UI state)

#### 5.3 ProductTransformer ma fallback logic
- IF category_mappings exists → use it
- ELSE → use CategoryMapper
- Problem: Nie sprawdza FORMATU category_mappings przed użyciem!

---

## 6. CONFLICT SCENARIOS

### Scenario A: User workflow (no pullShopData)
1. User selects categories in UI → ProductFormSaver zapisuje UI format
2. Sync → ProductTransformer odczytuje UI format, oczekując PPM→PrestaShop
3. **RESULT**: Keys = `["selected", "primary"]` → błąd mapowania

### Scenario B: pullShopData workflow
1. User clicks "Wczytaj z sklepu" → pullShopData zapisuje PrestaShop→PrestaShop
2. UI reload → ProductMultiStoreManager odczytuje, oczekując UI format
3. **RESULT**: No `selected` key → UI shows NO categories
4. Sync → ProductTransformer odczytuje PrestaShop→PrestaShop, oczekując PPM→PrestaShop
5. **RESULT**: Keys = `[9, 15]` → interprets as PPM IDs → incorrect sync

### Scenario C: Mixed (user + pullShopData)
1. User selects categories → UI format saved
2. User clicks "Wczytaj z sklepu" → OVERWRITES z PrestaShop format
3. **RESULT**: User selections LOST! UI data replaced with PrestaShop data

---

## 7. RECOMMENDATIONS

### Option A: CANONICAL FORMAT - PPM→PrestaShop (RECOMMENDED)

**Specification**:
```json
{
  "1": 9,
  "2": 15,
  "3": 800
}
```
(PPM Category ID → PrestaShop Category ID)

**Pros**:
- Semantic clarity: "mappings from PPM to PrestaShop"
- ProductTransformer już oczekuje tego formatu
- Easy to extend (dodatkowe metadata per category)
- Obsługuje multi-store (różne mapowania per shop)

**Cons**:
- Wymaga refactoringu ProductFormSaver (convert UI state → mappings)
- Wymaga refactoringu pullShopData (use CategoryMapper::mapFromPrestaShop)

**Required Changes**:
1. ProductFormSaver: Convert `shopCategories` (UI) → PPM→PrestaShop mappings
2. pullShopData: Use CategoryMapper to convert PrestaShop IDs → PPM IDs
3. ProductMultiStoreManager: Convert mappings → UI state on load
4. ProductSyncStrategy: Use keys (PPM IDs) instead of values
5. Documentation: Formalna specyfikacja formatu

---

### Option B: TWO SEPARATE FIELDS (ALTERNATIVE)

**Schema**:
- `category_mappings` (JSON) - PPM→PrestaShop mappings (sync layer)
- `category_ui_state` (JSON) - UI state (selected, primary)

**Pros**:
- Separation of concerns (sync vs UI)
- No data loss (both preserved)
- Easier migration (existing code remains)

**Cons**:
- Data duplication
- Sync complexity (keep both in sync)
- Migration required (split existing data)

---

### Option C: NORMALIZE TO JUNCTION TABLE (FUTURE)

**Schema**:
```sql
CREATE TABLE product_shop_categories (
  id BIGINT UNSIGNED PRIMARY KEY,
  product_shop_data_id BIGINT UNSIGNED,
  ppm_category_id INT UNSIGNED,
  prestashop_category_id INT UNSIGNED,
  is_primary BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (product_shop_data_id) REFERENCES product_shop_data(id),
  FOREIGN KEY (ppm_category_id) REFERENCES categories(id)
);
```

**Pros**:
- Normalized data (no JSON hell)
- Easy queries (JOIN instead of JSON parsing)
- Type safety
- Auditable (timestamps per association)

**Cons**:
- Major refactoring (migration + code changes)
- Performance overhead (JOIN vs JSON)
- Complexity increase

---

## 8. IMPACT ASSESSMENT

### Current Production Impact:

**Data Integrity**:
- ✅ ONLY 1 record z category_mappings (limited exposure)
- ⚠️ Format: PrestaShop→PrestaShop (incompatible z ProductTransformer expectations)
- ❌ Risk: Sync failures jeśli użytkownik zmodyfikuje ten produkt

**User Experience**:
- ❌ "Wczytaj z sklepu" → UI nie pokazuje kategorii (data loss w UI)
- ❌ User edits → overwrites PrestaShop data → potential sync issues

**System Health**:
- ⚠️ FIX #11 działa (checksum includes categories), ale używa złego formatu
- ❌ ProductTransformer może wysłać błędne kategorie do PrestaShop

---

## 9. NEXT STEPS (AWAITING USER DECISION)

### Immediate Actions (REQUIRED):

1. **User Decision**: Wybierz Option A, B, lub C (recommended: Option A)

2. **Stop Development**: HOLD wszystkie features używające category_mappings do czasu fix

3. **Emergency Workaround**: Dodaj format validation w ProductTransformer:
   ```php
   if (isset($shopData->category_mappings['selected'])) {
       // UI format detected - convert or reject
   } elseif (array_key_first($shopData->category_mappings) == reset($shopData->category_mappings)) {
       // PrestaShop→PrestaShop format - convert or reject
   }
   ```

### Implementation Plan (AFTER Decision):

**Option A Selected**:
1. Create migration script (convert existing data → PPM→PrestaShop format)
2. Refactor ProductFormSaver (UI state → mappings conversion)
3. Refactor pullShopData (use CategoryMapper)
4. Refactor ProductMultiStoreManager (mappings → UI state)
5. Update ProductSyncStrategy (use keys instead of values)
6. Add format validation layer
7. Update tests
8. Deploy + verify

**Timeline**: 4-6 hours (architecture fix + testing)

---

## 10. CONCLUSION

**FIX #11 nie rozwiązał problemu** - tylko dodał symptom treatment (checksum tracking).

**Fundamentalny problem**: Brak architektury dla `category_mappings` formatu → każdy komponent używa własnego formatu → data corruption + sync failures.

**Recommended Solution**: Option A (Canonical PPM→PrestaShop format) z pełnym refactoringiem wszystkich komponentów.

**CRITICAL**: Ten problem może powodować ciche błędy synchronizacji (wrong categories sent to PrestaShop without error messages).

---

## FILES ANALYZED

```
✓ app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
✓ app/Http/Livewire/Products/Management/ProductForm.php
✓ app/Services/PrestaShop/ProductTransformer.php
✓ app/Services/PrestaShop/Sync/ProductSyncStrategy.php
✓ app/Http/Livewire/Products/Management/Services/ProductMultiStoreManager.php
✓ app/Services/PrestaShop/CategoryMapper.php
✓ Production database (product_shop_data table)
```

**Total Files with category_mappings**: 12
**Total Occurrences**: 46
**Unique Formats Found**: 3 (UI, PrestaShop→PrestaShop, Expected PPM→PrestaShop)

---

**Agent**: debugger
**Status**: ⏸️ DIAGNOSIS COMPLETE - AWAITING ARCHITECTURE DECISION
**Next Agent**: architect (architecture decision) → laravel-expert (implementation)
