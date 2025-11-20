# CRITICAL DIAGNOSIS REPORT: BUG #2 & BUG #3 - Category Tree & Default Category

**Date**: 2025-11-19
**Agent**: debugger
**Product**: PB-KAYO-E-KMB (ID: 11033)
**Shop**: Test KAYO (ID: 5)

---

## EXECUTIVE SUMMARY

Po consolidacji tabel `product_shop_categories` → `product_categories`, user zgłosił 2 nowe bugi:
- **BUG #2**: PrestaShop otrzymuje tylko ostatnią podkategorię zamiast pełnego drzewka
- **BUG #3**: Kategoria "Główna" (primary) w PPM nie jest oznaczana jako "domyślna" w PrestaShop

**ROOT CAUSE:** `ProductTransformer::buildCategoryAssociations()` wysyła tylko bezpośrednie kategorie (flat list) + linia 72 bierze pierwszą kategorię jako default zamiast primary.

---

## DATA ANALYSIS - Product PB-KAYO-E-KMB

### PPM Categories (Shop 5 - test_kayoshop)

```
Buggy (ID: 60, parent: NULL)
  ├─ PrestaShop mapping: ID 135
  └─ TEST-PPM (ID: 61, parent: 60) [PRIMARY]
      └─ PrestaShop mapping: ID 154
```

**Pivot Table Data:**
```
product_id: 11033
shop_id: 5
categories:
  - Category 60 (Buggy): is_primary=false, PrestaShop ID=135
  - Category 61 (TEST-PPM): is_primary=true, PrestaShop ID=154
```

### Current Behavior (BUGGY)

**ProductTransformer::buildCategoryAssociations()** (line 275-314):
```php
foreach ($shopCategories as $categoryId) {
    $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $categoryId, $shop);

    if ($prestashopId) {
        $associations[] = ['id' => $prestashopId]; // ❌ FLAT LIST
    }
}
```

**Output:**
```php
$categoryAssociations = [
    ['id' => 135], // Buggy
    ['id' => 154], // TEST-PPM
];
```

**Default Category Selection** (line 72):
```php
$defaultCategoryId = !empty($categoryAssociations) ? $categoryAssociations[0]['id'] : 2;
// ❌ Returns 135 (Buggy) - FIRST in array, NOT PRIMARY
```

---

## BUG #2: MISSING CATEGORY TREE

### Problem

PrestaShop **REQUIRES** pełne drzewko kategorii w `associations->categories`:
```xml
<associations>
  <categories>
    <category><id>135</id></category> <!-- Buggy (parent) -->
    <category><id>154</id></category> <!-- TEST-PPM (child) -->
  </categories>
</associations>
```

Ale `CategoryMapper::mapToPrestaShop()` TYLKO mapuje istniejące IDs, NIE buduje hierarchy.

### Current Code Flow

1. ✅ `buildCategoryAssociations()` pobiera [60, 61] z pivot table
2. ✅ `CategoryMapper` mapuje: 60→135, 61→154
3. ❌ Kod wysyła **FLAT LIST**: [135, 154] (bez parent info)
4. ❌ PrestaShop otrzymuje "orphaned" subcategory 154 bez parent 135

### Expected Behavior

```php
// SHOULD send FULL TREE:
$categoryAssociations = [
    ['id' => 135], // Parent: Buggy
    ['id' => 154], // Child: TEST-PPM (child of 135)
];
```

**PrestaShop XML:**
```xml
<associations>
  <categories>
    <category><id>135</id></category> <!-- Parent MUST be included -->
    <category><id>154</id></category> <!-- Child -->
  </categories>
</associations>
```

---

## BUG #3: WRONG DEFAULT CATEGORY

### Problem

**Line 72** bierze **pierwszą** kategorię z `$categoryAssociations` jako default:
```php
$defaultCategoryId = !empty($categoryAssociations) ? $categoryAssociations[0]['id'] : 2;
// ❌ Returns 135 (Buggy) - array[0]
// ✅ SHOULD return 154 (TEST-PPM) - primary category
```

### Current Behavior

```php
$categoryAssociations = [
    ['id' => 135], // Buggy - array[0] ← SELECTED AS DEFAULT ❌
    ['id' => 154], // TEST-PPM - PRIMARY in PPM ✅
];

$defaultCategoryId = 135; // ❌ WRONG! Should be 154
```

### Expected Behavior

```php
// SHOULD check pivot table for is_primary=true:
$primaryCategoryId = DB::table('product_categories')
    ->where('product_id', $product->id)
    ->where('shop_id', $shop->id)
    ->where('is_primary', true)
    ->value('category_id'); // Returns 61

$prestashopPrimaryId = $this->categoryMapper->mapToPrestaShop($primaryCategoryId, $shop); // 154

$defaultCategoryId = $prestashopPrimaryId ?: 2; // ✅ 154 (TEST-PPM)
```

---

## PROPOSED FIX

### FIX #1: BUG #3 - Primary Category as Default (EASY)

**Location:** `ProductTransformer::transform()` line 70-72

**BEFORE:**
```php
// Get default category ID from associations
$categoryAssociations = $this->buildCategoryAssociations($product, $shop);
$defaultCategoryId = !empty($categoryAssociations) ? $categoryAssociations[0]['id'] : 2;
```

**AFTER:**
```php
// Get default category ID from primary category in pivot table
$categoryAssociations = $this->buildCategoryAssociations($product, $shop);
$defaultCategoryId = $this->getDefaultCategoryId($product, $shop, $categoryAssociations);
```

**New Method:**
```php
/**
 * Get default category ID (primary category from pivot table)
 *
 * @param Product $product Product instance
 * @param PrestaShopShop $shop Shop instance
 * @param array $categoryAssociations Built category associations
 * @return int PrestaShop category ID (primary if set, first otherwise, fallback to 2)
 */
private function getDefaultCategoryId(Product $product, PrestaShopShop $shop, array $categoryAssociations): int
{
    // PRIORITY 1: Get primary category from pivot table
    $primaryCategoryId = DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->where('is_primary', true)
        ->value('category_id');

    if ($primaryCategoryId) {
        $prestashopPrimaryId = $this->categoryMapper->mapToPrestaShop((int) $primaryCategoryId, $shop);

        if ($prestashopPrimaryId) {
            Log::debug('[CATEGORY SYNC] Using primary category as default', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'ppm_primary_id' => $primaryCategoryId,
                'prestashop_primary_id' => $prestashopPrimaryId,
            ]);

            return $prestashopPrimaryId;
        }
    }

    // PRIORITY 2: Fallback to first category in associations
    if (!empty($categoryAssociations)) {
        Log::warning('[CATEGORY SYNC] No primary category, using first association', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'default_id' => $categoryAssociations[0]['id'],
        ]);

        return $categoryAssociations[0]['id'];
    }

    // PRIORITY 3: Fallback to PrestaShop default (2 = Home)
    Log::warning('[CATEGORY SYNC] No categories, using PrestaShop default', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
    ]);

    return 2;
}
```

---

### FIX #2: BUG #2 - Full Category Tree (COMPLEX)

**Problem:** Current code sends **FLAT LIST**, PrestaShop needs **FULL TREE**.

**OPTION A: Build Parent Hierarchy (RECOMMENDED)**

Modify `buildCategoryAssociations()` to include ALL parent categories:

```php
private function buildCategoryAssociations(Product $product, PrestaShopShop $shop): array
{
    // PRIORITY 1: Shop-specific categories from pivot table
    $shopCategories = $product->categoriesForShop($shop->id, false)
        ->pluck('categories.id')
        ->toArray();

    if (!empty($shopCategories)) {
        Log::debug('[CATEGORY SYNC] Using shop-specific categories from pivot', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'ppm_category_ids' => $shopCategories,
        ]);

        // Map PPM category IDs → PrestaShop IDs + BUILD PARENT HIERARCHY
        $associations = [];
        $addedIds = []; // Track to avoid duplicates

        foreach ($shopCategories as $categoryId) {
            // NEW: Get full category hierarchy (child → parent → grandparent → ...)
            $hierarchyIds = $this->getCategoryHierarchy($categoryId);

            foreach ($hierarchyIds as $hierarchyCatId) {
                $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $hierarchyCatId, $shop);

                if ($prestashopId && !in_array($prestashopId, $addedIds)) {
                    $associations[] = ['id' => $prestashopId];
                    $addedIds[] = $prestashopId;
                } elseif (!$prestashopId) {
                    Log::warning('[CATEGORY SYNC] Category mapping not found in hierarchy', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'ppm_category_id' => $hierarchyCatId,
                    ]);
                }
            }
        }

        if (!empty($associations)) {
            Log::info('[CATEGORY SYNC] Category associations built with hierarchy', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'association_count' => count($associations),
                'prestashop_category_ids' => array_column($associations, 'id'),
            ]);

            return $associations;
        }
    }

    // ... (rest of the method unchanged)
}

/**
 * Get full category hierarchy (child → parent → grandparent → root)
 *
 * @param int $categoryId Leaf category ID
 * @return array Array of category IDs from leaf to root (e.g., [61, 60, 1])
 */
private function getCategoryHierarchy(int $categoryId): array
{
    $hierarchy = [];
    $currentId = $categoryId;
    $maxDepth = 10; // Safety limit to prevent infinite loops
    $depth = 0;

    while ($currentId && $depth < $maxDepth) {
        $hierarchy[] = $currentId;

        // Get parent
        $category = Category::find($currentId);

        if (!$category || !$category->parent_id) {
            break;
        }

        $currentId = $category->parent_id;
        $depth++;
    }

    // Return hierarchy from child to root (e.g., [61, 60, 1])
    return $hierarchy;
}
```

**OPTION B: Validate Category Exists in PrestaShop (ALTERNATIVE)**

Check if parent categories exist in PrestaShop before sending product. If missing, create them first via `CategorySyncService`.

**RECOMMENDATION:** Use **OPTION A** (build hierarchy) - simpler, faster, doesn't require additional API calls.

---

## TESTING CHECKLIST

- [ ] BUG #3 Fix: Primary category becomes default
  - [ ] Create product with 2 categories (A, B), set B as primary
  - [ ] Sync to PrestaShop
  - [ ] Verify `id_category_default` = B (not A)

- [ ] BUG #2 Fix: Full category tree sent
  - [ ] Create product with category hierarchy: Root → Parent → Child
  - [ ] Set Child as primary
  - [ ] Sync to PrestaShop
  - [ ] Verify `associations->categories` contains ALL 3 categories (Root, Parent, Child)

- [ ] Edge Cases:
  - [ ] Product with single category (no parents)
  - [ ] Product with unmapped category in hierarchy
  - [ ] Product with no primary category set

---

## DEPLOYMENT NOTES

**Critical:** Both fixes must be deployed together (ProductTransformer.php changes).

**Files Modified:**
- `app/Services/PrestaShop/ProductTransformer.php`
  - Line 70-72: Replace with `getDefaultCategoryId()` call
  - Line 275-314: Modify `buildCategoryAssociations()` to build hierarchy
  - New method: `getDefaultCategoryId()`
  - New method: `getCategoryHierarchy()`

**Rollback Plan:** Keep backup of ProductTransformer.php (current version working for flat list).

**Testing:** Use product PB-KAYO-E-KMB (ID: 11033) on Shop 5 (test_kayoshop) for verification.

---

## RISK ASSESSMENT

**BUG #3 Fix:** LOW RISK
- Simple logic change
- Clear fallback chain (primary → first → default)
- No breaking changes

**BUG #2 Fix:** MEDIUM RISK
- More complex logic (hierarchy traversal)
- Potential for infinite loop (mitigated with maxDepth=10)
- Risk of duplicate categories (mitigated with $addedIds tracking)
- Unknown: Does PrestaShop API require specific order (parent before child)?

**Mitigation:**
- Test on staging shop first
- Monitor logs for infinite loop warnings
- Verify PrestaShop accepts unordered category list

---

## NEXT STEPS

1. ✅ Create diagnostic report (this document)
2. ⏳ Implement FIX #3 (getDefaultCategoryId method)
3. ⏳ Implement FIX #2 (getCategoryHierarchy method)
4. ⏳ Test locally with product PB-KAYO-E-KMB
5. ⏳ Deploy to production
6. ⏳ Verify sync on test_kayoshop
7. ⏳ Update BUG #1 fix (deploy ProductForm.php changes)

---

## REFERENCES

- Migration: `database/migrations/2025_11_19_000001_consolidate_product_categories_tables.php`
- ProductTransformer: `app/Services/PrestaShop/ProductTransformer.php`
- CategoryMapper: `app/Services/PrestaShop/CategoryMapper.php`
- HasCategories Trait: `app/Models/Concerns/Product/HasCategories.php`
- Product: PB-KAYO-E-KMB (ID: 11033), Shop: Test KAYO (ID: 5)
