# CATEGORY ARCHITECTURE REDESIGN REQUIRED

**Status**: ⚠️ REQUIRES IMPLEMENTATION
**Data**: 2025-11-19
**Wpływ**: KRYTYCZNY - Fundamental design flaw
**Estimated Time**: ~40-60 hours (3-4 FAZY)

---

## 🚨 PROBLEM OVERVIEW

Current category system has **FUNDAMENTAL ARCHITECTURAL FLAW**:

**What User Sees:**
- TAB "Sklepy" pokazuje kategorie z PPM (WRONG - should show PrestaShop categories)
- TAB "Dane domyślne" pokazuje kategorie z PPM (CORRECT)
- Brak validatora zgodności kategorii między PPM a PrestaShop
- Brak automatycznego tworzenia kategorii w PrestaShop
- Brak UI controls (Zwiń/Rozwiń, Odznacz wszystkie, Utwórz nową)

**What Happens:**
1. User wybiera kategorie z PPM w TAB "Sklepy"
2. System zapisuje do `product_categories` (shop_id=X)
3. Podczas sync:
   - Kategorie NIE SĄ zmapowane w PrestaShop
   - `CategoryMapper::mapToPrestaShop()` returns NULL
   - Code spada do FALLBACK (category_mappings cache)
   - Wysyła **STARE DANE** zamiast fresh shop selection

**Root Cause:**
- UI shows PPM categories instead of PrestaShop categories
- No integration with PrestaShop Category API for pulling remote categories
- No auto-creation workflow for missing categories
- No validator to check PPM vs PrestaShop consistency

---

## 🔍 CURRENT ARCHITECTURE (BROKEN)

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│ USER INTERFACE (ProductForm)                                │
├─────────────────────────────────────────────────────────────┤
│ TAB "Dane domyślne"   TAB "Sklepy"                          │
│ Shows: PPM categories  Shows: PPM categories ❌ WRONG       │
│                                                              │
│ User selects: "Buggy (60)" + "TEST-PPM (61)"                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ DATA LAYER (product_categories pivot)                       │
├─────────────────────────────────────────────────────────────┤
│ product_id: 11033                                            │
│ shop_id: 1                                                   │
│ category_id: 60 (Buggy)                                      │
│ category_id: 61 (TEST-PPM)                                   │
│ is_primary: false / true                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ SYNC LAYER (ProductTransformer)                             │
├─────────────────────────────────────────────────────────────┤
│ 1. buildCategoryAssociations()                               │
│    - Pobiera [60, 61] z pivot                                │
│    - Mapuje via CategoryMapper                               │
│                                                              │
│ 2. CategoryMapper::mapToPrestaShop(60, shop=1)               │
│    - Query: shop_mappings WHERE shop_id=1, ppm_value=60     │
│    - Result: NULL ❌ (no mapping exists)                     │
│                                                              │
│ 3. $associations = empty ❌                                  │
│                                                              │
│ 4. FALLBACK to category_mappings cache                       │
│    - Uses OLD cached data from product_shop_data             │
│    - Sends: [9, 15, 800, 981, 983, 985] ❌ STALE DATA       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
                 PrestaShop API
                 (receives wrong categories)
```

### Missing Components

1. **PrestaShop Category API Integration** ❌
   - No method to fetch categories from PrestaShop
   - No caching of remote category tree
   - No sync of PrestaShop → PPM

2. **Auto-Creation Workflow** ❌
   - No detection of missing categories
   - No automatic category creation in PrestaShop
   - No automatic mapping after creation

3. **Validator** ❌
   - No comparison of PPM vs PrestaShop categories
   - No status labels ("Zgodne", "Własne", "Dziedziczone")
   - No warnings when categories missing

4. **UI Controls** ❌
   - No Zwiń/Rozwiń all
   - No Odznacz wszystkie
   - No Utwórz nową kategorię modal

---

## ✅ REQUIRED ARCHITECTURE (NEW DESIGN)

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│ USER INTERFACE (ProductForm)                                │
├─────────────────────────────────────────────────────────────┤
│ TAB "Dane domyślne"              TAB "Sklepy"               │
│ Shows: PPM categories ✅          Shows: PrestaShop categories ✅│
│ Source: categories table          Source: PrestaShop API    │
│                                   (cached + refreshable)    │
│                                                              │
│ VALIDATOR STATUS:                                            │
│ • "Zgodne" (green) = same as default                         │
│ • "Własne" (blue) = custom for shop                          │
│ • "Dziedziczone" (gray) = inherits from default             │
│                                                              │
│ UI CONTROLS:                                                 │
│ [Zwiń/Rozwiń wszystkie] [Odznacz wszystkie] [Utwórz nową]   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ AUTO-CREATE LAYER (CategoryAutoCreateService) NEW!          │
├─────────────────────────────────────────────────────────────┤
│ IF categories missing in PrestaShop:                         │
│   1. Create CategoryCreationJob (wyprzedzający)              │
│   2. Job creates missing categories via API                  │
│   3. Job creates mappings in shop_mappings                   │
│   4. Job completes → trigger ProductSyncJob                  │
│                                                              │
│ ELSE:                                                        │
│   1. Create ProductSyncJob directly                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ DATA LAYER (product_categories pivot)                       │
├─────────────────────────────────────────────────────────────┤
│ OPTION 1: Store PPM category IDs (current)                  │
│ - Requires mapping via CategoryMapper                        │
│ - Flexible (can change mappings)                             │
│                                                              │
│ OPTION 2: Store PrestaShop category IDs (alternative)       │
│ - No mapping needed during sync                              │
│ - Less flexible (must recreate on mapping change)           │
│                                                              │
│ RECOMMENDED: OPTION 1 (keep current structure)              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ SYNC LAYER (ProductTransformer) - UPDATED                   │
├─────────────────────────────────────────────────────────────┤
│ 1. buildCategoryAssociations()                               │
│    - Pobiera category IDs z pivot                            │
│    - Mapuje via CategoryMapper                               │
│    - IF mapping missing:                                     │
│      • Log ERROR                                             │
│      • Return empty array (no fallback!)                     │
│      • Show validation error in UI                           │
│                                                              │
│ 2. CategoryMapper GUARANTEES mapping exists                  │
│    (due to auto-create workflow)                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
                 PrestaShop API ✅
                 (correct categories)
```

### New Components

1. **PrestaShopCategoryService** (NEW)
   ```php
   - fetchCategoriesFromShop(PrestaShopShop $shop): Collection
   - syncCategoriesToCache(PrestaShopShop $shop): void
   - getCachedCategoryTree(PrestaShopShop $shop): array
   - createCategoryInShop(Category $ppmCategory, PrestaShopShop $shop): int
   ```

2. **CategoryAutoCreateService** (NEW)
   ```php
   - detectMissingCategories(Product $product, PrestaShopShop $shop): array
   - createMissingCategoriesJob(Product $product, PrestaShopShop $shop): CategoryCreationJob
   - validateAllCategoriesMapped(array $categoryIds, PrestaShopShop $shop): bool
   ```

3. **CategoryCreationJob** (NEW)
   ```php
   - Creates categories in PrestaShop via API
   - Creates mappings in shop_mappings
   - Dispatches ProductSyncJob after completion
   ```

4. **CategoryValidatorService** (NEW)
   ```php
   - compareWithDefault(Product $product, PrestaShopShop $shop): string
   - Returns: "zgodne" | "wlasne" | "dziedziczone"
   ```

5. **CategoryManagementUI Component** (Livewire)
   ```php
   - Tree view with expand/collapse
   - "Odznacz wszystkie" button
   - "Utwórz nową" modal
   - Status badge per shop
   ```

---

## 📋 IMPLEMENTATION PLAN

### FAZA 1: PrestaShop Category API Integration (8-12h)

**Components:**
- `PrestaShopCategoryService`
- `app/Services/PrestaShop/CategoryService.php`
- Category caching layer (Redis or DB)

**Tasks:**
1. Implement `fetchCategoriesFromShop()` - pull category tree from PrestaShop
2. Implement `syncCategoriesToCache()` - store in cache (15min TTL)
3. Implement `getCachedCategoryTree()` - return formatted tree for UI
4. Add "Odśwież kategorie" button in UI

**Testing:**
- Pull categories from Shop 1 (Pitbike.pl)
- Pull categories from Shop 5 (Test KAYO)
- Verify tree structure matches PrestaShop
- Verify cache expiration works

---

### FAZA 2: Category Validator (4-6h)

**Components:**
- `CategoryValidatorService`
- `app/Services/CategoryValidatorService.php`

**Tasks:**
1. Implement `compareWithDefault()` - compare shop vs default categories
2. Add status badges to UI:
   - "Zgodne" (green) = identical to default
   - "Własne" (blue) = custom for shop
   - "Dziedziczone" (gray) = no shop-specific, uses default
3. Update ProductForm to show status

**Testing:**
- Product with same categories shop vs default → "Zgodne"
- Product with different categories → "Własne"
- Product with no shop categories → "Dziedziczone"

---

### FAZA 3: Auto-Create Missing Categories (12-16h)

**Components:**
- `CategoryAutoCreateService`
- `CategoryCreationJob`
- `app/Services/CategoryAutoCreateService.php`
- `app/Jobs/CategoryCreationJob.php`

**Tasks:**
1. Implement `detectMissingCategories()` - find unmapped categories
2. Implement `createMissingCategoriesJob()` - create wyprzedzający JOB
3. Implement `CategoryCreationJob`:
   - Create parent categories first (hierarchy)
   - Create child categories
   - Create mappings in `shop_mappings`
   - Dispatch `ProductSyncJob` after completion
4. Update ProductForm to trigger auto-create before sync

**Workflow:**
```
User clicks "Zapisz zmiany" (TAB Sklepy)
  ↓
detectMissingCategories(product, shop)
  ↓
IF missing categories:
  createCategoryCreationJob → dispatch
  Wait for completion
  ↓
  createProductSyncJob → dispatch
ELSE:
  createProductSyncJob → dispatch directly
```

**Testing:**
- Create product with categories NOT in PrestaShop
- Trigger sync
- Verify CategoryCreationJob creates categories
- Verify mappings created
- Verify ProductSyncJob uses new mappings

---

### FAZA 4: Category Management UI (12-16h)

**Components:**
- Livewire CategoryTree component
- Modal for creating new categories
- UI controls (Zwiń/Rozwiń, Odznacz wszystkie)

**Tasks:**
1. Create `CategoryTreeComponent` (Livewire)
   - Hierarchical tree view
   - Expand/collapse per node
   - Checkbox selection
2. Add UI controls:
   - "Zwiń wszystkie" / "Rozwiń wszystkie" button
   - "Odznacz wszystkie" button
   - "Utwórz nową kategorię" button → modal
3. Create "Utwórz nową kategorię" modal:
   - Shows PrestaShop category tree
   - User selects parent
   - User enters new category name
   - Creates in PrestaShop + PPM
4. Update ProductForm to use new component

**Testing:**
- Expand/collapse categories
- Select/deselect categories
- "Odznacz wszystkie" clears shop selection → inherits default
- Create new category via modal
- Verify new category appears in tree

---

## 🛡️ ZAPOBIEGANIE (PREVENTION)

### Design Principles

1. **ALWAYS show PrestaShop categories in shop TAB**
   - Fetch from PrestaShop API
   - Cache with expiration
   - Refresh button for manual sync

2. **NEVER allow unmapped categories in sync**
   - Validate before JOB creation
   - Show clear error if mapping missing
   - Offer auto-create option

3. **ALWAYS validate against default**
   - Show status badge ("Zgodne", "Własne", "Dziedziczone")
   - Warn user if deviates from default

4. **ALWAYS create parent categories first**
   - Build hierarchy tree
   - Create from root to leaf
   - Prevent orphaned categories

### Code Review Checklist

- [ ] PrestaShop API called for shop categories
- [ ] Cache implemented with expiration
- [ ] Validator checks PPM vs PrestaShop
- [ ] Auto-create workflow handles missing categories
- [ ] UI shows clear status badges
- [ ] No fallback to stale cache data

---

## 📊 ESTIMATED EFFORT

**Total Time:** ~40-60 hours

**Breakdown:**
- FAZA 1 (PrestaShop API): 8-12h
- FAZA 2 (Validator): 4-6h
- FAZA 3 (Auto-Create): 12-16h
- FAZA 4 (UI): 12-16h
- Testing & Debugging: 4-10h

**Priority:** HIGH - Blocks proper category management

**Dependencies:**
- PrestaShop API credentials (already have)
- Category mapping system (already implemented)
- Queue system (already implemented)

---

## 🔗 REFERENCES

**Related Issues:**
- `_AGENT_REPORTS/CRITICAL_DIAGNOSIS_BUG_2_3_category_tree_and_default_2025-11-19_REPORT.md`
- `_AGENT_REPORTS/COORDINATION_2025-11-19_BUGS_1_2_3_FIXED_REPORT.md`

**Code Files:**
- `app/Http/Livewire/Products/Management/ProductForm.php`
- `app/Services/PrestaShop/ProductTransformer.php`
- `app/Services/PrestaShop/CategoryMapper.php`
- `app/Models/Concerns/Product/HasCategories.php`

**Database:**
- `product_categories` (pivot table)
- `shop_mappings` (category mappings)
- `categories` (PPM categories)

**PrestaShop API:**
- Endpoint: `/api/categories`
- Schema: `ps_category`, `ps_category_lang`, `ps_category_shop`

---

## 📝 NEXT STEPS

1. ✅ Document issue (this file)
2. ⏳ Update project plan (new FAZA)
3. ⏳ Quick fix BUG #3 (`getCategoryStatusIndicator`)
4. ⏳ Get user approval for FAZA 1-4 implementation
5. ⏳ Start FAZA 1 (PrestaShop API integration)

---

**CRITICAL:** This is NOT a bug fix - this is architectural redesign. Requires significant development effort and careful testing.
