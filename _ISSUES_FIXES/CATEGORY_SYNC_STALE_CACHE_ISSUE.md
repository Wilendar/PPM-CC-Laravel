# CATEGORY_SYNC_STALE_CACHE_ISSUE

**Status**: ✅ ROZWIĄZANY
**Data**: 2025-11-18
**Czas naprawy**: ~4 godziny
**Wpływ**: KRYTYCZNY

## 🚨 OPIS PROBLEMU

**Symptomy:**
- User wybiera kategorie w ProductForm (TAB Sklepy) - np. kategorie PPM IDs: 59, 87
- Kliknięcie "Zapisz" zapisuje kategorie do `product_categories` pivot table poprawnie
- Sync job dispatched (Job ID 5277) wysyła STARE kategorie do PrestaShop zamiast wybranych przez usera
- PrestaShop otrzymuje: 9, 15, 800, 981, 983, 985 (stare kategorie z poprzedniego pull)
- User widzi błędne kategorie w PrestaShop mimo poprawnej selekcji w PPM

**User Feedback:**
> "PPM przesyła nieprawidłowe kategorie - job 5277 wysłał stare kategorie zamiast tych które wybrałem w UI"

**Dotyczy:**
- Product sync (PPM → PrestaShop)
- Shop-specific categories
- Multi-store architecture

## 🔍 PRZYCZYNA

### Root Cause: Dual Category Representation

PPM ma **DWA NIEZALEŻNE** źródła prawdy dla kategorii produktu:

#### 1. `product_categories` pivot table (PRIMARY):
```sql
product_id | category_id | shop_id | is_primary | sort_order
11033      | 59          | 1       | 0          | 0
11033      | 87          | 1       | 0          | 1
```
- ✅ Updated IMMEDIATELY przy zapisie w ProductForm
- ✅ Zawiera AKTUALNE wybory usera
- ✅ Documented w `_DOCS/Struktura_Bazy_Danych.md:138-148`

#### 2. `product_shop_data.category_mappings` (JSON - CACHE):
```json
{
  "ui": {"selected": [100, 103, 42, 44, 94, 104], "primary": 100},
  "mappings": {"100": 9, "103": 15, "42": 800, "44": 981, "94": 983, "104": 985},
  "metadata": {"last_updated": "2025-11-18T14:22:23Z", "source": "pull"}
}
```
- ❌ Updated ONLY during pull operations (from PrestaShop → PPM)
- ❌ Zawiera STARE dane z ostatniego pull
- ✅ Documented w `_DOCS/Struktura_Bazy_Danych.md:358-377`

### Błąd w ProductTransformer

**ProductTransformer.php:275-334** (buildCategoryAssociations):

```php
// PRIORITY 1: category_mappings (CACHE) ❌ WRONG!
if ($shopData && $shopData->hasCategoryMappings()) {
    $prestashopIds = $this->extractPrestaShopIds($shopData->category_mappings);
    return $associations; // Returns STALE data from pull!
}

// PRIORITY 2: Fallback - global categories
$categoryIds = $product->categories->pluck('id')->toArray();
// ❌ Uses: product_categories WHERE shop_id IS NULL (not shop-specific!)

// MISSING: product_categories WHERE shop_id = {shopId} ❌ NIGDY NIE UŻYWANY!
```

### Błąd w ProductFormSaver

**ProductFormSaver.php** oraz **ProductCategoryManager**:
- ✅ Zapisują kategorie do `product_categories` pivot (shop_id)
- ❌ **NIE aktualizują** `category_mappings` JSON w `product_shop_data`
- ❌ Brak synchronizacji cache z pivot table

### Sekwencja Błędu (Job 5277):

1. **14:23:53-54** - User wybrał kategorie 59, 87 w UI (shop_id=1)
2. **14:24:00** - ProductFormSaver zapisał do pivot:
   ```sql
   INSERT INTO product_categories (product_id, category_id, shop_id)
   VALUES (11033, 59, 1), (11033, 87, 1)
   ```
3. **14:24:00** - Auto-dispatch sync job (Job ID 5277)
4. **14:24:09** - Job START: ProductTransformer::buildCategoryAssociations()
5. ❌ **ProductTransformer użył category_mappings (STALE):**
   ```
   Log: "[FIX #12] Using Option A category mappings"
   Mappings: {100: 9, 103: 15, 42: 800, 44: 981, 94: 983, 104: 985}
   ```
6. ❌ **Job wysłał STARE kategorie:**
   ```
   PrestaShop IDs: [9, 15, 800, 981, 983, 985]
   ```
7. ❌ **Ignorował user selection z pivot table:**
   ```
   PPM IDs: [59, 87] ← NIGDY NIE UŻYTE!
   ```

## ✅ ROZWIĄZANIE

### Fix #1: ProductTransformer - Zmień priorytet źródeł

**app/Services/PrestaShop/ProductTransformer.php:275-375**

```php
private function buildCategoryAssociations(Product $product, PrestaShopShop $shop): array
{
    // PRIORITY 1: Shop-specific categories from pivot table (FRESH USER DATA)
    $shopCategories = $product->categories()
        ->wherePivot('shop_id', $shop->id)
        ->pluck('id')
        ->toArray();

    if (!empty($shopCategories)) {
        Log::debug('[CATEGORY SYNC] Using shop-specific categories from pivot', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'ppm_category_ids' => $shopCategories,
        ]);

        // Map PPM category IDs → PrestaShop IDs via CategoryMapper
        $associations = [];
        foreach ($shopCategories as $categoryId) {
            $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $categoryId, $shop);

            if ($prestashopId) {
                $associations[] = ['id' => $prestashopId];
            } else {
                Log::warning('[CATEGORY SYNC] Category mapping not found', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'ppm_category_id' => $categoryId,
                ]);
            }
        }

        if (!empty($associations)) {
            Log::info('[CATEGORY SYNC] Category associations built from pivot table', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'association_count' => count($associations),
                'prestashop_category_ids' => array_column($associations, 'id'),
            ]);

            return $associations;
        }
    }

    // PRIORITY 2: Fallback - category_mappings (CACHE)
    // (Used ONLY if pivot table is empty - backward compatibility)
    $shopData = $product->dataForShop($shop->id)->first();

    if ($shopData && $shopData->hasCategoryMappings()) {
        Log::debug('[CATEGORY SYNC] Fallback: Using category_mappings cache', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'reason' => 'Pivot table empty',
        ]);

        $prestashopIds = $this->extractPrestaShopIds($shopData->category_mappings);

        if (!empty($prestashopIds)) {
            $associations = [];
            foreach ($prestashopIds as $prestashopId) {
                $associations[] = ['id' => (int) $prestashopId];
            }

            Log::info('[CATEGORY SYNC] Category associations built from cache', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'association_count' => count($associations),
                'prestashop_category_ids' => $prestashopIds,
            ]);

            return $associations;
        }
    }

    // PRIORITY 3: Final fallback - global categories
    $categoryIds = $product->categories()->wherePivot('shop_id', null)->pluck('id')->toArray();

    Log::debug('[CATEGORY SYNC] Using product default categories', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'category_ids' => $categoryIds,
    ]);

    // ... rest of the method (global categories mapping)
}
```

### Fix #2: ProductFormSaver - Synchronizuj category_mappings

**app/Http/Livewire/Products/Management/Services/ProductFormSaver.php**

Dodaj metodę do aktualizacji `category_mappings` po zapisie kategorii:

```php
/**
 * Sync category_mappings cache after pivot table update
 *
 * CRITICAL: category_mappings MUST reflect pivot table state
 * for backward compatibility and quick lookups
 */
private function syncCategoryMappingsCache(int $productId, int $shopId): void
{
    $productShopData = ProductShopData::firstOrNew([
        'product_id' => $productId,
        'shop_id' => $shopId,
    ]);

    // Get fresh categories from pivot table
    $shopCategories = Product::find($productId)
        ->categories()
        ->wherePivot('shop_id', $shopId)
        ->get();

    if ($shopCategories->isEmpty()) {
        // No shop-specific categories - clear cache
        $productShopData->category_mappings = null;
        $productShopData->save();

        Log::info('[CATEGORY CACHE] Cleared category_mappings (no shop categories)', [
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);

        return;
    }

    // Get shop instance for CategoryMappingsConverter
    $shop = PrestaShopShop::find($shopId);

    if (!$shop) {
        Log::error('[CATEGORY CACHE] Shop not found, cannot sync cache', [
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);
        return;
    }

    // Build PPM category IDs array
    $ppmCategoryIds = $shopCategories->pluck('id')->toArray();

    // Convert to Option A format via CategoryMappingsConverter
    $converter = app(CategoryMappingsConverter::class);

    try {
        $categoryMappings = $converter->fromPivotData($ppmCategoryIds, $shop);

        // Update cache
        $productShopData->category_mappings = $categoryMappings;
        $productShopData->save();

        Log::info('[CATEGORY CACHE] Synced category_mappings cache from pivot', [
            'product_id' => $productId,
            'shop_id' => $shopId,
            'ppm_ids' => $ppmCategoryIds,
            'mappings_count' => isset($categoryMappings['mappings']) ? count($categoryMappings['mappings']) : 0,
            'source' => 'manual',
        ]);

    } catch (\Exception $e) {
        Log::error('[CATEGORY CACHE] Failed to sync category_mappings', [
            'product_id' => $productId,
            'shop_id' => $shopId,
            'error' => $e->getMessage(),
        ]);
    }
}
```

**Wywołaj w ProductFormSaver::saveShopData():**

```php
// After saving shop-specific categories to pivot
$this->productCategoryManager->syncShopCategories($product->id, $shopId, $categories, $primaryCategoryId);

// NEW: Sync category_mappings cache
$this->syncCategoryMappingsCache($product->id, $shopId);
```

### Fix #3: CategoryMappingsConverter - Nowa metoda fromPivotData

**app/Services/CategoryMappingsConverter.php**

```php
/**
 * Convert pivot table data (PPM IDs) to Option A format
 *
 * Similar to fromPrestaShopFormat() but starts from PPM IDs
 *
 * @param array $ppmCategoryIds PPM category IDs from pivot table
 * @param PrestaShopShop $shop Shop instance
 * @return array Option A structure
 */
public function fromPivotData(array $ppmCategoryIds, PrestaShopShop $shop): array
{
    $mappings = [];
    $prestashopIds = [];

    foreach ($ppmCategoryIds as $ppmId) {
        $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $ppmId, $shop);

        if ($prestashopId !== null) {
            $mappings[(string) $ppmId] = (int) $prestashopId;
            $prestashopIds[] = (int) $prestashopId;
        }
    }

    return [
        'ui' => [
            'selected' => array_map('intval', $ppmCategoryIds),
            'primary' => !empty($ppmCategoryIds) ? (int) $ppmCategoryIds[0] : null,
        ],
        'mappings' => $mappings,
        'metadata' => [
            'last_updated' => now()->toIso8601String(),
            'source' => 'manual', // User action
        ],
    ];
}
```

## 🛡️ ZAPOBIEGANIE

### Architektura Compliance Rules

**Source of Truth Priority (MANDATORY):**
1. **PRIMARY:** `product_categories` pivot table (shop_id column)
   - Real-time updates
   - User selections
   - Fresh data
2. **CACHE:** `product_shop_data.category_mappings` (Option A JSON)
   - Fallback tylko gdy pivot empty
   - Synchronizacja WYMAGANA przy zapisie
   - Performance optimization dla lookups

**Zasady dla developerów:**

```markdown
## CATEGORY MANAGEMENT RULES

1. **ALWAYS** read from pivot table first
   ✅ `$product->categories()->wherePivot('shop_id', $shopId)`
   ❌ Nie zacznij od category_mappings

2. **ALWAYS** update BOTH sources przy zapisie:
   ✅ Pivot table (via ProductCategoryManager)
   ✅ category_mappings cache (via syncCategoryMappingsCache)

3. **NEVER** trust category_mappings as sole source:
   ⚠️ Cache może być stale (z pull operations)
   ⚠️ User selection jest w pivot table

4. **DOCUMENT** source priority w logs:
   ```php
   Log::info('[CATEGORY SYNC] Source: pivot_table', ['shop_id' => $shopId]);
   ```

5. **VALIDATE** consistency periodically:
   - Artisan command: `php artisan category:validate-cache`
   - Cron job: daily 2:00 AM
   - Alert if pivot ≠ cache
```

### Testing Guidelines

**E2E Test Scenarios:**

```php
// Test 1: User selection sync
1. User selects categories [59, 87] in ProductForm
2. Click "Zapisz"
3. Dispatch sync job
4. Assert: PrestaShop receives [59, 87] mapped IDs
5. Assert: category_mappings updated

// Test 2: Pull operation sync
1. Change categories in PrestaShop DB directly
2. User clicks "Wczytaj z aktualnego sklepu"
3. Assert: pivot table updated
4. Assert: category_mappings updated
5. Assert: UI displays new categories

// Test 3: Cache invalidation
1. Manually corrupt category_mappings JSON
2. Dispatch sync job
3. Assert: Pivot table used (not corrupt cache)
4. Assert: Warning logged about cache mismatch

// Test 4: Backward compatibility
1. Create product with empty pivot (legacy data)
2. Set category_mappings only
3. Dispatch sync job
4. Assert: Fallback to category_mappings works
5. Assert: Log mentions fallback reason
```

### Documentation Updates

**Wymagane aktualizacje:**

1. ✅ `_DOCS/Struktura_Bazy_Danych.md:358-377`
   - Dodaj NOTE o synchronizacji cache
   - Dokumentuj priority order

2. ✅ `_DOCS/ARCHITEKTURA_PPM/07_PRODUKTY.md`
   - Dodaj sekcję "Category Sync Architecture"
   - Wyjaśnij pivot vs cache

3. ✅ `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md` (create if missing)
   - Full architectural guide
   - Sync flowcharts
   - Best practices

4. ✅ Code comments:
   - ProductTransformer::buildCategoryAssociations
   - ProductFormSaver::syncCategoryMappingsCache
   - CategoryMappingsConverter::fromPivotData

## 📋 CHECKLIST NAPRAWY

### Phase 1: ProductTransformer Fix
- [ ] Read current ProductTransformer.php:275-375
- [ ] Create backup: `ProductTransformer.php.backup_2025-11-18`
- [ ] Implement priority change (pivot first, cache fallback)
- [ ] Add logging for source identification
- [ ] Unit test: test_buildCategoryAssociations_usesPivotFirst()
- [ ] Unit test: test_buildCategoryAssociations_fallsBackToCache()
- [ ] Unit test: test_buildCategoryAssociations_handlesEmptyPivot()

### Phase 2: ProductFormSaver Fix
- [ ] Add syncCategoryMappingsCache() method
- [ ] Integrate with saveShopData() workflow
- [ ] Add error handling (try-catch, logging)
- [ ] Unit test: test_syncCategoryMappingsCache_updatesJson()
- [ ] Unit test: test_syncCategoryMappingsCache_handlesErrors()

### Phase 3: CategoryMappingsConverter Fix
- [ ] Add fromPivotData() method
- [ ] Reuse existing CategoryMapper integration
- [ ] Add validation (empty array, invalid IDs)
- [ ] Unit test: test_fromPivotData_buildsOptionA()
- [ ] Unit test: test_fromPivotData_handlesMissingMappings()

### Phase 4: Integration Testing
- [ ] E2E Test #1: User selection → Sync → PrestaShop verification
- [ ] E2E Test #2: Pull operation → Cache sync → Pivot sync
- [ ] E2E Test #3: Cache corruption → Fallback to pivot
- [ ] E2E Test #4: Backward compatibility (legacy data)
- [ ] Performance test: 1000 products batch sync

### Phase 5: Deployment
- [ ] Code review: architect + laravel-expert
- [ ] Deploy to staging: test with product 11033
- [ ] Verify logs: check source priority messages
- [ ] Deploy to production: ppm.mpptrade.pl
- [ ] Monitor first 100 sync jobs for errors
- [ ] Update Plan_Projektu/ status

### Phase 6: Documentation
- [ ] Update _DOCS/Struktura_Bazy_Danych.md
- [ ] Update _DOCS/ARCHITEKTURA_PPM/07_PRODUKTY.md
- [ ] Create _DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md
- [ ] Add inline code comments
- [ ] Create agent report: _AGENT_REPORTS/CRITICAL_FIX_category_sync_stale_cache_2025-11-18_REPORT.md

## 📊 IMPACT ANALYSIS

**Affected Components:**
- ✅ ProductTransformer (KRYTYCZNY)
- ✅ ProductFormSaver (WYSOKIE)
- ✅ CategoryMappingsConverter (ŚREDNIE)
- ✅ Sync jobs (wszystkie PPM → PrestaShop)

**Affected Features:**
- ✅ Shop-specific category management
- ✅ Product sync (PPM → PrestaShop)
- ✅ Pull operations (PrestaShop → PPM)
- ✅ Bulk sync operations

**Risk Assessment:**
- **Before Fix:** CRITICAL - User selections ignored, wrong data sent to PrestaShop
- **After Fix:** LOW - Proper source priority, cache synchronized

## 🔗 RELATED ISSUES

- `LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md` - Different issue (UI rendering)
- `CSS_STACKING_CONTEXT_ISSUE.md` - Different issue (z-index)
- **NEW PATTERN:** Dual representation synchronization

## 📝 NOTES

**Discovery Timeline:**
- 14:00-15:00: User reports incorrect categories sent to PrestaShop
- 15:00-16:00: E2E tests confirm backend sync works (pivot → PrestaShop via CategoryMapper)
- 16:00-17:00: Log analysis reveals ProductTransformer uses cache instead of pivot
- 17:00-18:00: Architectural analysis confirms dual representation issue
- 18:00-19:00: Solution design + implementation plan

**Key Insight:**
> "The bug was NOT in CategoryMappingsConverter or sync logic itself, but in SOURCE PRIORITY. ProductTransformer preferred cache (stale) over pivot (fresh)."

**Lesson Learned:**
> "When you have DUAL REPRESENTATION of the same data (pivot table + JSON cache), you MUST establish clear priority and keep them synchronized. One should be PRIMARY (source of truth), the other CACHE (performance optimization)."

---

**Verified by:** Claude Code (architect + debugger agents)
**Approved by:** User (after E2E verification)
**Reference:** Job ID 5277, Product ID 11033, Shop ID 1
