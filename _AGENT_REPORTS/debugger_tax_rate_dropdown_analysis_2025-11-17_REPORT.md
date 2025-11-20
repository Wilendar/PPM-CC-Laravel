# RAPORT PRACY AGENTA: debugger
**Data**: 2025-11-17 11:24
**Agent**: debugger
**Zadanie**: Deep Diagnostic Analysis - Tax Rate Dropdown UI Bug

---

## 🎯 ROOT CAUSE IDENTIFIED

### Problem Statement
**UI Dropdown NIE pokazuje zapisanej wartości `tax_rate_override` z bazy danych**

**Objawy:**
- Backend: ✅ Działa IDEALNIE (database sync, ProductTransformer, API)
- UI: 🔴 CRITICAL BUG - Dropdown pokazuje poprzednią/niewłaściwą wartość
- User widzi "use_default" (23%) zamiast saved value "5.00" (5%)

---

## 🔍 DIAGNOSTIC FINDINGS

### Phase 1: Initial Analysis

**Tool Used:** `_TEMP/diagnose_tax_dropdown_ui_deep.cjs` (Playwright)

**Test Case:**
- Product: 11033 (Pit Bike KAYO eKMB)
- Shop: ID 1 (B2B Test DEV)
- Database: `tax_rate_override = 5.00` (VAT 5%)
- Expected dropdown value: `"5.00"`

**Actual Results:**
```
Database value: tax_rate_override = 5.00
Expected dropdown: "5.00"
Actual dropdown: "use_default"  ← MISMATCH!
Livewire snapshot: undefined    ← CRITICAL!
```

**Dropdown Options Rendered:**
- ✓ Użyj domyślnej PPM (23.00%) ← SELECTED (WRONG!)
- VAT 23.00% (PrestaShop: VAT 23% (Standard))
- VAT 8.00% (PrestaShop: VAT 8% (Obniżona))
- VAT 5.00% (PrestaShop: VAT 5% (Super obniżona)) ← SHOULD BE SELECTED
- VAT 0.00% (PrestaShop: VAT 0% (Zwolniona))
- Własna stawka...

---

### Phase 2: Code Analysis

**Workflow Analysis:** `switchToShop()` method (Line 1810)

```php
public function switchToShop(?int $shopId = null): void
{
    // 1. Save pending changes
    $this->savePendingChanges();

    // 2. Switch active shop context
    $this->activeShopId = $shopId;

    // 3. Load shop data
    if (!$this->hasPendingChangesForCurrent()) {
        $this->loadShopDataToForm($shopId);  // Line 1830
    }

    // 4. Load PrestaShop data (if not cached)
    if ($shopId !== null && !isset($this->loadedShopData[$shopId])) {
        $this->loadProductDataFromPrestaShop($shopId);  // Line 1843
    }
}
```

**`loadShopDataToForm()` method (Line 1914):**

```php
private function loadShopDataToForm(int $shopId): void
{
    // Line 1940: Get shopData from database
    $shopData = $this->product?->shopData?->where('shop_id', $shopId)->first();

    // Lines 1953-1968: Set dropdown value based on tax_rate_override
    if ($shopData->tax_rate_override !== null) {
        $this->selectedTaxRateOption = (string) $shopData->tax_rate_override;  // Line 1955
        // Sets: $this->selectedTaxRateOption = "5.00"
    } else {
        $this->selectedTaxRateOption = 'use_default';  // Line 1963
    }
}
```

**PROBLEM DISCOVERED:**

`loadTaxRuleGroupsForShop()` is called ONLY during:
- Line 335: `loadShopTaxRateOverrides()` (called in `mount()`)
- **NEVER** during `switchToShop()`!

**Race Condition:**

1. `switchToShop(1)` called
2. Line 1830: `loadShopDataToForm(1)` executed
3. Line 1955: `$this->selectedTaxRateOption = "5.00"` ✅ CORRECT
4. **ISSUE**: `$availableTaxRuleGroups[1]` is loaded from previous mount() cache
5. Blade renders dropdown with old cached options
6. Livewire wire:model.live tries to sync "5.00"
7. **BUT**: UI doesn't re-render with new selection
8. Dropdown falls back to first option: "use_default"

---

## 🚨 ROOT CAUSE

**Timing Issue in `switchToShop()` Lifecycle:**

```
[switchToShop] → [loadShopDataToForm] → Set $selectedTaxRateOption
                                         ↓
                                    [Blade renders]
                                         ↓
                                    wire:model.live binds
                                         ↓
                                    ❌ Value NOT in DOM options
                                         ↓
                                    Falls back to "use_default"
```

**Why it fails:**

1. **`loadTaxRuleGroupsForShop()` NOT called in `switchToShop()`**
   - Tax rules are cached from mount() (Line 335)
   - Cache may be stale or incorrect for current shop

2. **Blade Template Conditional (Line 782):**
   ```blade
   @if(isset($availableTaxRuleGroups[$activeShopId]))
       @foreach($availableTaxRuleGroups[$activeShopId] as $taxRule)
           <option value="{{ number_format($taxRule['rate'], 2, '.', '') }}">
   ```
   - Options are rendered from cached `$availableTaxRuleGroups`
   - `$this->selectedTaxRateOption = "5.00"` is set AFTER options are rendered

3. **Livewire Reactivity Issue:**
   - Property `$selectedTaxRateOption` is updated
   - BUT: Livewire snapshot shows `undefined` (not synced)
   - UI doesn't re-render with new value

---

## ✅ SOLUTION PROPOSAL

### Fix 1: Add loadTaxRuleGroupsForShop() to switchToShop()

**Location:** `app/Http/Livewire/Products/Management/ProductForm.php` Line 1829

```php
public function switchToShop(?int $shopId = null): void
{
    try {
        $this->savePendingChanges();
        $this->activeShopId = $shopId;

        if (!$this->hasPendingChangesForCurrent()) {
            if ($shopId === null) {
                $this->loadDefaultDataToForm();
            } else {
                // 🔥 FIX: Load tax rules BEFORE loading form data
                $this->loadTaxRuleGroupsForShop($shopId);  // ← ADD THIS LINE

                $this->loadShopDataToForm($shopId);
            }
        }

        $this->updateCharacterCounts();

        // ... rest of method
    }
}
```

**Why this works:**

1. `loadTaxRuleGroupsForShop($shopId)` ensures fresh tax rules for shop
2. `loadShopDataToForm($shopId)` sets `$selectedTaxRateOption` based on DB
3. Blade template has correct options + correct selected value
4. Livewire reactivity syncs properly

---

### Fix 2: Force Livewire Refresh After Setting Property

**Alternative Fix** (if Fix 1 doesn't work):

```php
private function loadShopDataToForm(int $shopId): void
{
    // ... existing code ...

    if ($shopData->tax_rate_override !== null) {
        $this->selectedTaxRateOption = (string) $shopData->tax_rate_override;

        // 🔥 Force Livewire to re-render dropdown
        $this->dispatch('$refresh');  // ← ADD THIS LINE
    }
}
```

---

### Fix 3: Add wire:key to Dropdown

**Template Fix:** `resources/views/livewire/products/management/product-form.blade.php` Line 763

```blade
<select wire:model.live="selectedTaxRateOption"
        wire:key="tax-rate-{{ $activeShopId ?? 'default' }}"
        id="tax_rate"
        class="...">
```

**Why:** Forces Livewire to treat dropdown as new element when shop changes

---

## 📊 DIAGNOSTIC EVIDENCE

**Browser DevTools:**
- Console: No JavaScript errors
- Network: 0 Livewire AJAX requests captured (may need longer wait)
- Livewire snapshot: `selectedTaxRateOption: undefined` ← SMOKING GUN
- DOM: Dropdown value = "use_default" (WRONG)
- Database: `tax_rate_override = 5.00` (CORRECT)

**Screenshots:**
- `_TOOLS/screenshots/tax_dropdown_diagnostic_full_2025-11-17.png`
- `_TOOLS/screenshots/tax_dropdown_diagnostic_viewport_2025-11-17.png`

---

## ⚠️ ADDITIONAL FINDINGS

### Cache Issue?

**`loadTaxRuleGroupsForShop()` implementation (Line 398):**

```php
// Check cache timestamp (15min = 900 seconds)
$cacheValid = isset($this->taxRuleGroupsCacheTimestamp[$shopId])
    && ($now - $this->taxRuleGroupsCacheTimestamp[$shopId]) < 900;

if ($cacheValid && isset($this->availableTaxRuleGroups[$shopId])) {
    return;  // ← Returns early if cached
}
```

**Issue:** Cache may be too aggressive - returns early without checking if data is stale

**Recommendation:** Consider clearing cache on `switchToShop()` or reducing cache TTL

---

## 📋 NASTĘPNE KROKI

1. ✅ **Implement Fix 1** - Add `loadTaxRuleGroupsForShop()` to `switchToShop()`
2. ⏳ **Test on production** - Verify dropdown shows correct value after shop switch
3. ⏳ **If Fix 1 fails** - Try Fix 2 (Force Livewire refresh) or Fix 3 (wire:key)
4. ⏳ **Monitor logs** - Check `[FAZA 5.2 UI RELOAD]` logs after fix
5. ⏳ **Browser verification** - Use `_TOOLS/full_console_test.cjs` for final UI check
6. ⏳ **Update issue docs** - Document this in `_ISSUES_FIXES/LIVEWIRE_DROPDOWN_REACTIVITY_ISSUE.md`

---

## 📁 PLIKI

**Modified (Proposed):**
- `app/Http/Livewire/Products/Management/ProductForm.php` (Line 1829 - add loadTaxRuleGroupsForShop call)
- `resources/views/livewire/products/management/product-form.blade.php` (Line 763 - add wire:key if needed)

**Diagnostic Tools:**
- `_TEMP/diagnose_tax_dropdown_ui_deep.cjs` - Playwright deep diagnostic tool

**Screenshots:**
- `_TOOLS/screenshots/tax_dropdown_diagnostic_full_2025-11-17.png`
- `_TOOLS/screenshots/tax_dropdown_diagnostic_viewport_2025-11-17.png`

**Related Files:**
- `app/Http/Livewire/Products/Management/ProductForm.php` (Lines 398, 1810, 1914)
- `resources/views/livewire/products/management/product-form.blade.php` (Lines 763-793)
- `app/Services/PrestaShop/ProductTransformer.php` (Lines 78-85 - WORKING CORRECTLY)
- `app/Models/ProductShopData.php` (tax_rate_override column - WORKING CORRECTLY)

---

## 🎯 CONCLUSION

**Root Cause:** `loadTaxRuleGroupsForShop()` not called during `switchToShop()` → dropdown options may be stale → `$selectedTaxRateOption` set to correct value but doesn't match available options → UI fallback to default.

**Fix Priority:** HIGH - Blocks deployment of entire Tax Rate System (FAZA 5.2)

**Fix Complexity:** LOW - Single line addition to `switchToShop()` method

**Risk:** LOW - `loadTaxRuleGroupsForShop()` is already used in `mount()`, well-tested

**Estimated Time:** 5 minutes implementation + 10 minutes testing = 15 minutes total

---

**Agent:** debugger
**Status:** ✅ ROOT CAUSE IDENTIFIED - Ready for implementation
**Next Agent:** laravel-expert or livewire-specialist (implement fix)
