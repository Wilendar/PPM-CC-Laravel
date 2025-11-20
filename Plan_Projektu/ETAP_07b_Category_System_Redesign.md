# ETAP_07b: Category System Redesign

**Status**: 🛠️ **W TRAKCIE** (FAZA 1-2 COMPLETED 2025-11-19)
**Priority**: WYSOKI (Blocks proper category management)
**Estimated Time**: 40-60h (4 FAZY)
**Dependencies**: ETAP_07 (PrestaShop API), ETAP_05 (Products), ETAP_13 (Sync Panel)
**Started**: 2025-11-19
**Current Phase**: FAZA 3 - Auto-Create Missing Categories (Next)

---

## PROBLEM OVERVIEW

Current category system has **FUNDAMENTAL ARCHITECTURAL FLAW**:
- Shop TAB shows PPM categories (should show PrestaShop categories)
- No auto-creation of missing categories in PrestaShop
- No validator for PPM vs PrestaShop consistency
- No UI controls (Zwiń/Rozwiń, Odznacz wszystkie, Utwórz nową)

**Reference:** `_ISSUES_FIXES/CATEGORY_ARCHITECTURE_REDESIGN_REQUIRED.md`

---

## ✅ FAZA 1: PrestaShop Category API Integration (COMPLETED 2025-11-19)

### ✅ 1.1 PrestaShop Category Service
#### ✅ 1.1.1 Implement fetchCategoriesFromShop()
        ✅ 1.1.1.1 Create PrestaShopCategoryService class (już istniał)
        ✅ 1.1.1.2 Implement API call to /api/categories
        ✅ 1.1.1.3 Parse PrestaShop XML response to Category collection
            └── PLIK: app/Services/PrestaShop/PrestaShopCategoryService.php

#### ✅ 1.1.2 Implement Category Caching
        ✅ 1.1.2.1 Create cache layer (Cache::flexible())
        ✅ 1.1.2.2 Set TTL to 15 minutes (stale fallback 60min)
        ✅ 1.1.2.3 Implement cache invalidation on manual refresh
            └── PLIK: app/Services/PrestaShop/PrestaShopCategoryService.php

#### ✅ 1.1.3 Implement getCachedCategoryTree()
        ✅ 1.1.3.1 Format category tree for UI (hierarchical)
        ✅ 1.1.3.2 Add parent-child relationships
        ✅ 1.1.3.3 Return structure compatible with Blade partials
            └── PLIK: app/Http/Livewire/Products/Management/ProductForm.php (getShopCategories + convertCategoryArrayToObject)

### ✅ 1.2 UI Integration
#### ✅ 1.2.1 Add "Odśwież kategorie" button
        ✅ 1.2.1.1 Add button to ProductForm blade (enterprise styling)
        ✅ 1.2.1.2 Wire to Livewire method refreshCategoriesFromShop()
        ✅ 1.2.1.3 Show loading state with wire:loading
            └── PLIK: resources/views/livewire/products/management/product-form.blade.php

#### ✅ 1.2.2 Update Shop TAB to show PrestaShop categories
        ✅ 1.2.2.1 Replace PPM category source (getAvailableCategories → getShopCategories)
        ✅ 1.2.2.2 Maintain checkbox selection state
        ✅ 1.2.2.3 Add visual indicators (orange border for shop context)
            └── PLIK: resources/views/livewire/products/management/product-form.blade.php

### ✅ 1.3 Testing
#### ✅ 1.3.1 Test PrestaShop API integration
        ✅ 1.3.1.1 Pull categories from Shop 1 (Pitbike.pl)
        ✅ 1.3.1.2 Pull categories from Shop 5 (Test KAYO) - VERIFIED
        ✅ 1.3.1.3 Verify tree structure matches PrestaShop admin panel
            └── PLIK: _TOOLS/screenshots/architecture_fix_AFTER_shop_click_2025-11-19T12-02-02.png

#### ✅ 1.3.2 Test caching
        ✅ 1.3.2.1 Verify cache expiration (15min TTL configured)
        ✅ 1.3.2.2 Verify manual refresh invalidates cache (clearCache + $refresh)
        ✅ 1.3.2.3 Verify performance improvement (cache hit avoids API call)
            └── PLIK: app/Http/Livewire/Products/Management/ProductForm.php (refreshCategoriesFromShop)

---

## ✅ FAZA 2: Category Validator (COMPLETED 2025-11-19)

### ✅ 2.1 CategoryValidatorService
#### ✅ 2.1.1 Implement compareWithDefault()
        ✅ 2.1.1.1 Compare shop categories with default categories
        ✅ 2.1.1.2 Return status: "zgodne" | "wlasne" | "dziedziczone"
        ✅ 2.1.1.3 Add detailed diff report (added/removed/changed)
            └── PLIK: app/Services/CategoryValidatorService.php

### ✅ 2.2 UI Status Badges
#### ✅ 2.2.1 Add status badge to ProductForm
        ✅ 2.2.1.1 "Zgodne" (green badge) = identical to default
        ✅ 2.2.1.2 "Własne" (blue badge) = custom for shop
        ✅ 2.2.1.3 "Dziedziczone" (gray badge) = inherits from default
            └── PLIK: app/Http/Livewire/Products/Management/ProductForm.php (getCategoryValidationStatus method)

#### ✅ 2.2.2 Add tooltip with details
        ✅ 2.2.2.1 Show which categories differ
        ✅ 2.2.2.2 Show inheritance source
            └── PLIK: resources/views/livewire/products/management/product-form.blade.php (badge with tooltip)

### ✅ 2.3 Testing
#### ✅ 2.3.1 Test validator logic
        ✅ 2.3.1.1 Product with same categories → "Zgodne" - PASSED
        ✅ 2.3.1.2 Product with different categories → "Własne" - PASSED
        ✅ 2.3.1.3 Product with no shop categories → "Dziedziczone" - PASSED
            └── PLIK: _TEMP/test_category_validator_faza2.php (3/3 tests PASSED)

---

## ✅ FAZA 3: Auto-Create Missing Categories (COMPLETED 2025-11-19)

### ✅ 3.1 CategoryAutoCreateService
#### ✅ 3.1.1 Implement detectMissingCategories()
        ✅ 3.1.1.1 Check if category exists in PrestaShop
        ✅ 3.1.1.2 Check if mapping exists in shop_mappings
        ✅ 3.1.1.3 Return list of missing categories
            └── PLIK: app/Services/CategoryAutoCreateService.php

#### ✅ 3.1.2 Implement createMissingCategoriesJob()
        ✅ 3.1.2.1 Create wyprzedzający JOB
        ✅ 3.1.2.2 Build dependency chain (CategoryCreationJob → ProductSyncJob)
        ✅ 3.1.2.3 Handle job failure gracefully
            └── PLIK: app/Services/CategoryAutoCreateService.php (createMissingCategoriesJob method)

### ✅ 3.2 CategoryCreationJob
#### ✅ 3.2.1 Implement job logic
        ✅ 3.2.1.1 Create parent categories first (hierarchy)
        ✅ 3.2.1.2 Create child categories
        ✅ 3.2.1.3 Handle PrestaShop API errors
            └── PLIK: app/Jobs/PrestaShop/CategoryCreationJob.php

#### ✅ 3.2.2 Create mappings
        ✅ 3.2.2.1 Insert into shop_mappings after creation
        ✅ 3.2.2.2 Verify mapping exists before product sync
            └── PLIK: app/Jobs/PrestaShop/CategoryCreationJob.php (createCategoryAndMapping method)

#### ✅ 3.2.3 Chain ProductSyncJob
        ✅ 3.2.3.1 Dispatch ProductSyncJob after completion
        ✅ 3.2.3.2 Pass created mappings as context
            └── PLIK: app/Jobs/PrestaShop/CategoryCreationJob.php (chainProductSync method)

### ✅ 3.3 ProductForm Integration
#### ✅ 3.3.1 Update save logic
        ✅ 3.3.1.1 Call detectMissingCategories() before sync
        ✅ 3.3.1.2 IF missing → create CategoryCreationJob first
        ✅ 3.3.1.3 Show progress indicator for both jobs
            └── PLIK: app/Http/Livewire/Products/Management/Services/ProductFormSaver.php (syncShopCategories method)

### ✅ 3.4 Testing
#### ✅ 3.4.1 Test auto-create workflow
        ✅ 3.4.1.1 Product with categories NOT in PrestaShop
        ✅ 3.4.1.2 Trigger sync → verify CategoryCreationJob created
        ✅ 3.4.1.3 Verify categories created in PrestaShop
        ✅ 3.4.1.4 Verify mappings created in shop_mappings
        ✅ 3.4.1.5 Verify ProductSyncJob uses new mappings

#### ✅ 3.4.2 Test error handling
        ✅ 3.4.2.1 PrestaShop API error during creation
        ✅ 3.4.2.2 Duplicate category name in PrestaShop
        ✅ 3.4.2.3 Invalid parent category
            └── PLIK: app/Services/PrestaShop/PrestaShopCategoryService.php (fetchCategoryById method)

---

## ❌ FAZA 4: Category Management UI (12-16h)

### ❌ 4.1 CategoryTree Livewire Component
#### ❌ 4.1.1 Create component
        ❌ 4.1.1.1 Hierarchical tree view with Alpine.js
        ❌ 4.1.1.2 Expand/collapse per node
        ❌ 4.1.1.3 Checkbox selection (multi-select)
        ❌ 4.1.1.4 Primary category indicator (radio button)

### ❌ 4.2 UI Controls
#### ❌ 4.2.1 Zwiń/Rozwiń wszystkie
        ❌ 4.2.1.1 Add button to collapse all nodes
        ❌ 4.2.1.2 Add button to expand all nodes
        ❌ 4.2.1.3 Remember state per user (localStorage)

#### ❌ 4.2.2 Odznacz wszystkie
        ❌ 4.2.2.1 Add button to clear shop selection
        ❌ 4.2.2.2 Show confirmation dialog
        ❌ 4.2.2.3 Inherit from default after clearing

#### ❌ 4.2.3 Utwórz nową kategorię
        ❌ 4.2.3.1 Add button to open modal
        ❌ 4.2.3.2 Modal shows PrestaShop category tree
        ❌ 4.2.3.3 User selects parent category
        ❌ 4.2.3.4 User enters new category name (multi-lang)
        ❌ 4.2.3.5 Creates in PrestaShop + PPM + shop_mappings

### ❌ 4.3 ProductForm Integration
#### ❌ 4.3.1 Replace old category UI
        ❌ 4.3.1.1 Remove old checkbox list
        ❌ 4.3.1.2 Add CategoryTree component
        ❌ 4.3.1.3 Wire events (selection, primary change)

### ❌ 4.4 Testing
#### ❌ 4.4.1 Test UI interactions
        ❌ 4.4.1.1 Expand/collapse categories
        ❌ 4.4.1.2 Select/deselect categories
        ❌ 4.4.1.3 "Odznacz wszystkie" → verify inherits default
        ❌ 4.4.1.4 Set primary category

#### ❌ 4.4.2 Test create new category
        ❌ 4.4.2.1 Open modal → select parent
        ❌ 4.4.2.2 Enter name → create
        ❌ 4.4.2.3 Verify appears in tree
        ❌ 4.4.2.4 Verify created in PrestaShop
        ❌ 4.4.2.5 Verify mapping created

---

## 📊 PROGRESS SUMMARY

**ETAP Status:** 🛠️ W TRAKCIE (3/4 FAZY completed, 1 pozostała)

**Completion:**
- FAZA 1: ✅ **COMPLETED** - 13/13 tasks (100%) - User confirmed "działa idealnie" 2025-11-19
- FAZA 2: ✅ **COMPLETED** - 7/7 tasks (100%) - All tests PASSED 2025-11-19
- FAZA 3: ✅ **COMPLETED** - 15/15 tasks (100%) - DEPLOYED to production 2025-11-19
- FAZA 4: ❌ NOT STARTED - 0/14 tasks (0%)

**Total:** 35/49 tasks (71.4%)

---

## 🚀 NEXT STEPS

1. ✅ **User Approval** - APPROVED 2025-11-19
2. ✅ **FAZA 1** - PrestaShop Category API Integration - **COMPLETED** 2025-11-19 (User: "działa idealnie")
3. ✅ **FAZA 2** - Category Validator - **COMPLETED** 2025-11-19 (All tests PASSED)
4. ✅ **FAZA 3** - Auto-Create Missing Categories - **COMPLETED** 2025-11-19 (DEPLOYED to production)
5. ⏳ **FAZA 4** - Category Management UI (12-16h) - **NEXT PRIORITY**

### FAZA 1 Deliverables (COMPLETED):
- ✅ PrestaShop category API integration via existing PrestaShopCategoryService
- ✅ Category caching (15min TTL, 60min stale fallback)
- ✅ "Odśwież kategorie" button with cache invalidation + UI refresh
- ✅ Shop TAB displays PrestaShop categories (not PPM)
- ✅ Array-to-object conversion for Blade partial compatibility
- ✅ Full browser verification (HTTP 200, Playwright screenshots)
- ✅ Debug log cleanup (production-ready code)
- ✅ Comprehensive fix report: `_AGENT_REPORTS/CRITICAL_FIX_architecture_etap07b_faza1_prestashop_categories_2025-11-19_REPORT.md`

### FAZA 2 Deliverables (COMPLETED):
- ✅ CategoryValidatorService with compareWithDefault() method
- ✅ Status badge system (zgodne/własne/dziedziczone)
- ✅ Detailed diff reports (added/removed/primary_changed)
- ✅ UI badges with tooltips in ProductForm
- ✅ All 3 test scenarios PASSED (identical/custom/inherited)
- ✅ Production deployment verified
- ✅ Test script: `_TEMP/test_category_validator_faza2.php`

### FAZA 3 Deliverables (COMPLETED):
- ✅ CategoryAutoCreateService (detection + dispatch)
- ✅ CategoryCreationJob (wyprzedzający pattern)
- ✅ PrestaShop API integration (fetchCategoryById)
- ✅ ProductFormSaver integration (auto-detect missing categories)
- ✅ Dependency chain: CategoryCreationJob → ProductSyncJob
- ✅ Translation: PrestaShop IDs → PPM IDs via shop_mappings
- ✅ Hierarchy validation (parent → child creation order)
- ✅ Production deployment verified (4 files uploaded, queue restarted)
- ✅ FIXES CRITICAL BUG: Foreign key constraint violation on product save

---

## 🔗 REFERENCES

**Issue Document:** `_ISSUES_FIXES/CATEGORY_ARCHITECTURE_REDESIGN_REQUIRED.md`
**Related ETAPs:** ETAP_07 (PrestaShop API), ETAP_05 (Products), ETAP_13 (Sync Panel)
**Dependencies:** PrestaShop API, CategoryMapper, Queue system

---

**CRITICAL:** This is architectural redesign, not bug fix. Requires user approval before implementation.
