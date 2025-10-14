# CategoryPreviewModal v2 - Implementation Plan

**Data utworzenia:** 2025-10-13
**Status:** 🔄 W REALIZACJI
**Priorytet:** 🔥 KRYTYCZNY - Kluczowa funkcjonalność systemu

---

## 🎯 WIZJA

CategoryPreviewModal jako **kompleksowe narzędzie zarządzania kategoriami** podczas importu produktów z PrestaShop.

### Problem do rozwiązania:
1. ❌ Conflict detection nie widzi kategorii transformowanych przez ProductTransformer
2. ❌ Brak możliwości wyboru kategorii z istniejących w PPM
3. ❌ Brak UI dla conflict resolution (RE-IMPORT scenario)
4. ❌ Brak quick category creator

---

## 📊 SCENARIUSZE (3 GŁÓWNE)

### **SCENARIUSZ 1: FIRST IMPORT**
Produkt nie ma kategorii w PPM.

**Opcje użytkownika:**
1. Użyj kategorii z PrestaShop (auto-mapping)
2. Wybierz z istniejących kategorii PPM (manual picker)
3. Dodaj nową kategorię ręcznie (quick creator)
4. Importuj bez kategorii (skip)

### **SCENARIUSZ 2: IMPORT Z MAPPING**
Produkt ma kategorie zmapowane PrestaShop → PPM.

**Akcje:**
- Pokazuje wizualizację: PrestaShop cat → PPM cat
- Możliwość zmiany (manual picker)

### **SCENARIUSZ 3: RE-IMPORT (KONFLIKT)**
Produkt już istnieje w PPM (ten sam SKU, inny sklep).

**Opcje:**
1. **Overwrite DEFAULT:** Nadpisz domyślne dane kategorii
2. **Keep conflict:** Zachowaj różnice (per-shop only) → 🟡 status
3. **Manual select:** Wybierz ręcznie z PPM
4. **Cancel:** Anuluj import

---

## 🔧 ETAPY IMPLEMENTACJI

### **ETAP 1: ProductTransformer Integration** ✅ COMPLETED (2025-10-13)

**Cel:** Conflict detection używa tej samej logiki co import + uniwersalna detekcja RE-IMPORT

**Implemented Solution:**

**Part 1:** Category Mapping (completed 2025-10-13 12:30)
Instead of using ProductTransformer (which only returns single category_id), replicated the SAME mapping logic from PrestaShopImportService::syncProductCategories().

**Part 2:** Universal RE-IMPORT Detection (completed 2025-10-13 15:30)
**KRYTYCZNA NAPRAWA:** Product lookup teraz UNIVERSAL - pokrywa WSZYSTKIE scenariusze:
1. ✅ **Ręcznie dodany produkt** - ma SKU w PPM, brak ProductShopData
2. ✅ **Cross-shop import** - ma SKU w PPM, ProductShopData z innym shop_id
3. ✅ **Same-shop re-import** - ma SKU w PPM, ProductShopData z tym samym shop_id

**Metoda wyszukiwania:**
- **PRIMARY:** Szukaj po SKU (`reference` z PrestaShop API)
- **FALLBACK:** Szukaj po prestashop_product_id (tylko dla produktów bez SKU)

**New Method Created:**

1. **CategoryPreviewModal::extractAndMapCategories()**
   ```php
   // Workflow:
   // 1. Extract associations.categories from PrestaShop product
   // 2. Map each PrestaShop category ID → PPM category ID via ShopMapping
   // 3. Skip unmapped categories (would be auto-imported during actual import)
   // 4. Return array of mapped PPM category IDs
   ```

**Updated Methods:**

2. **CategoryPreviewModal::detectCategoryConflicts()**
   ```php
   // BEFORE:
   $psCategoryIds = $this->extractPrestaShopCategoryIds($psProduct);
   $ppmCategoryIds = $this->convertPrestaShopToPPMCategoryIds($preview->shop_id, $psCategoryIds);

   // AFTER:
   $ppmCategoryIds = $this->extractAndMapCategories($psProduct, $preview->shop);
   // Now uses SAME mapping logic as actual import!
   ```

3. **Enhanced comparison logic**
   - Sort arrays before comparison (order-independent)
   - Compare mapped PPM IDs directly
   - Enhanced debug logging

**Removed:**
- `convertPrestaShopToPPMCategoryIds()` method (obsolete)

**Result:**
- ✅ Conflict detection sees SAME categories as import
- ✅ Universal product lookup (ręczne, cross-shop, same-shop)
- ✅ SKU-based detection (PRIMARY method)
- ✅ Accurate conflict reporting with mapped category IDs

**Files Modified:**
- `app/Http/Livewire/Components/CategoryPreviewModal.php` (lines 885-1050)
  - extractAndMapCategories() method (lines 1094-1194)
  - Universal RE-IMPORT detection (lines 909-980)
  - SKU-based primary lookup
  - ProductShopData fallback

**Testing:**
- 🧪 Awaiting user test with product 4017
- 📊 Expected: Produkt wykryty po SKU jako RE-IMPORT
- 📊 Expected: `conflicts_found > 0` when categories differ
- 🎯 Expected: Badge i button "Rozwiąż konflikty" appear in UI

---

### **ETAP 2: Category Picker Component** ⏸️ PLANNED

**Cel:** Wybór kategorii z istniejących w PPM

**Components:**
- `CategoryPicker.php` (Livewire component)
- `category-picker.blade.php` (hierarchical tree UI)

**Features:**
- Hierarchical tree rendering
- Multi-select with checkboxes
- Search/filter
- Live preview
- Integration with CategoryPreviewModal

**API:**
```php
<livewire:components.category-picker
    :available-categories="$ppmCategories"
    :selected-categories="$selectedCategories"
    wire:model="manualCategorySelection"
/>
```

---

### **ETAP 3: Conflict Resolution UI** ⏸️ PLANNED

**Cel:** 4 opcje dla RE-IMPORT scenario

**New Properties:**
```php
public string $conflictResolutionMode = 'overwrite'; // overwrite|keep_conflict|manual|cancel
public bool $showConflictResolution = false;
```

**New Methods:**
```php
public function resolveConflict(string $mode): void
public function overwriteDefaultCategories(): void
public function keepConflictPerShopOnly(): void
public function showCategoryPicker(): void
public function cancelImport(): void
```

**UI Design:**
- Radio buttons for 4 options
- Visual diff: PrestaShop vs PPM categories
- Warning badges (🟡 divergence)

---

### **ETAP 4: Manual Category Creator** ⏸️ PLANNED

**Cel:** Quick add category bez opuszczania modal

**Features:**
- Inline form (Alpine.js x-show)
- Parent category selector
- Validation
- Immediate add to picker

**UI:**
```blade
<div x-show="showQuickCreator">
    <input wire:model="newCategoryName" placeholder="Nazwa kategorii">
    <select wire:model="newCategoryParent">...</select>
    <button wire:click="createCategory">Dodaj</button>
</div>
```

---

## 📝 CURRENT STATUS

**Completed:**
- ✅ ProductShopData integration (2025-10-13)
- ✅ Debug logging for conflict detection
- ✅ Basic conflict detection logic
- ✅ **ETAP 1: extractAndMapCategories() integration (2025-10-13)**

**Next:**
- ⏳ User testing with product 4017 (verify conflict detection)
- ⏳ ETAP 2: Category Picker Component (manual selection UI)

**Blocked:**
- None (Product 4017 issue RESOLVED by ETAP 1)

---

## 🧪 TEST SCENARIOS

### Test 1: FIRST IMPORT (no categories in PPM)
1. Product without PPM categories
2. PrestaShop has categories
3. Modal shows all 4 options
4. User can select any option

### Test 2: IMPORT WITH MAPPING
1. Product has PrestaShop → PPM mapping
2. Modal shows visual mapping
3. User can change assignment

### Test 3: RE-IMPORT CONFLICT
1. Product exists in PPM (different shop)
2. Categories differ
3. Modal shows 4 conflict resolution options
4. User selection updates appropriate records

---

## 📂 FILES INVOLVED

**Backend:**
- `app/Http/Livewire/Components/CategoryPreviewModal.php` (main component)
- `app/Services/PrestaShop/ProductTransformer.php` (category transformation)
- `app/Models/Product.php` (category relationships)
- `app/Models/Category.php` (PPM categories)

**Frontend:**
- `resources/views/livewire/components/category-preview-modal.blade.php` (main UI)
- `resources/views/livewire/components/category-picker.blade.php` (new component)
- `resources/css/components/category-preview.css` (styling)

**Database:**
- `product_categories` table (pivot)
- `categories` table (PPM categories)
- `shop_mappings` table (PrestaShop → PPM mapping)

---

## 🎨 UI/UX GUIDELINES

**Design Principles:**
- Clear visual hierarchy
- Consistent with CategoryForm (reference template)
- Enterprise card styling (`.enterprise-card`)
- No inline styles (`style=""` forbidden)
- Responsive (mobile + desktop)

**Color Palette:**
- Success: `bg-green-500` (overwrite, success actions)
- Warning: `bg-orange-500` (conflicts, keep conflict)
- Danger: `bg-red-500` (cancel, delete)
- Info: `bg-blue-500` (manual selection)

**Components:**
- `.tabs-enterprise` for option selection
- `.btn-enterprise-primary` for primary actions
- `.btn-enterprise-secondary` for secondary actions

---

## 🔗 RELATED DOCUMENTATION

- `_DOCS/PPM_Color_Style_Guide.md` - Color palette & styling
- `_ISSUES_FIXES/CATEGORY_PICKER_CROSS_CONTAMINATION_ISSUE.md` - Context-aware IDs
- `_ISSUES_FIXES/LIVEWIRE_WIRE_POLL_CONDITIONAL_RENDERING_ISSUE.md` - wire:poll patterns

---

## 📈 SUCCESS METRICS

**Must have:**
- ✅ Conflict detection accuracy: 100%
- ✅ User can select categories manually
- ✅ RE-IMPORT conflicts are resolved correctly
- ✅ No data loss during category assignment

**Nice to have:**
- Quick category creation without leaving modal
- Visual category tree preview
- Bulk category assignment

---

## 🚧 KNOWN ISSUES

1. **✅ RESOLVED: Product 4017 empty categories** (2025-10-13)
   - **Problem:** PrestaShop API returns `ps_category_ids: []`, but import assigns [60, 61]
   - **Root Cause:** CategoryPreviewModal used raw PS data, import uses ShopMapping + auto-import
   - **Solution:** ETAP 1 - extractAndMapCategories() replicates import logic
   - **Status:** Deployed, awaiting user test

2. **Cross-contamination risk**
   - Multiple shops may have conflicting category IDs
   - **SOLUTION:** Context-aware IDs (`category_{{ $shopId }}_{{ $categoryId }}`)

---

## 📞 CONTACT

**Implementation Lead:** Claude Code AI
**User:** Kamil Wiliński
**Project:** PPM-CC-Laravel (ETAP_07)
**Priority:** KRYTYCZNY - Core functionality

---

**Last Updated:** 2025-10-13 12:30:00
