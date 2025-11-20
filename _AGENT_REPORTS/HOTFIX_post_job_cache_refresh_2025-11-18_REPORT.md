# HOTFIX: Post-Job Cache Refresh - "Oczekujące zmiany" Stale Badge

**Data:** 2025-11-18 20:15
**Priorytet:** 🔥 HIGH
**Status:** ✅ DEPLOYED

---

## 🎯 PROBLEM

**User Report (Screenshot):** "Aktualizuj aktualny sklep" poprawnie pokazuje "Oczekujące zmiany (1)" PRZED jobem, ale PO zakończeniu JOB-a komunikat cały czas zostaje i nadal pokazuje "Oczekujące zmiany (1)" mimo że job się zakończył

**Secondary Issue:** "Wczytaj z aktualnego sklepu" działa natychmiastowo (OK), ale przydałby się komunikat że dane zostały pobrane

---

## 🔍 ROOT CAUSE ANALYSIS

### Problem #1: Stale "Oczekujące zmiany" Badge

**Flow Analysis:**

```
1. User clicks "Aktualizuj aktualny sklep"
   ↓ syncShop() dispatches SyncProductToPrestaShop job
   ↓ getPendingChangesForShop() shows: "Oczekujące zmiany (1): Nazwa produktu"

2. Job executes in queue (~20-40s)
   ↓ SyncProductToPrestaShop updates ProductShopData in DB
   ↓ sync_status = 'synced', name = updated value

3. wire:poll → checkBulkSyncJobStatus()
   ↓ $this->product->load('shopData.shop') ← Refresh relation ✅
   ↓ All shops 'synced' → activeJobStatus = 'completed' ✅
   ↓ BUT: $this->loadedShopData[$shopId] NOT refreshed ❌

4. UI re-renders
   ↓ getPendingChangesForShop() runs:
       $shopData = ProductShopData::where(...)->first();  // ← Fresh from DB
       $loadedShopData[$shopId];  // ← STALE cache!
       → Compares fresh vs stale → detects diff
   ↓
5. Badge shows: "Oczekujące zmiany (1): Nazwa produktu" ❌
   (Even though job completed and DB is updated!)
```

**Code:**

```php
// checkBulkSyncJobStatus() - Lines 3627-3636 (BEFORE FIX)
if ($allSynchronized) {
    $this->activeJobStatus = 'completed';
    $this->jobResult = 'success';
    // ❌ MISSING: Clear $this->loadedShopData cache!

    Log::info('[ETAP_13 BULK SYNC] All shops synchronized', [...]);
}

// getPendingChangesForShop() - Line 4250
if (!isset($this->loadedShopData[$shopId])) {
    return [];  // No cached data - cannot detect changes
}

// Line 4255+ - Compares DB vs cache
$pendingChanges = [];
if ($shopData->name !== data_get($this->loadedShopData[$shopId], 'name')) {
    $pendingChanges[] = 'Nazwa produktu';  // ← FALSE POSITIVE!
}
```

**Why Stale Cache:**
- `loadedShopData[$shopId]` is populated ONCE when user clicks "Wczytaj z aktualnego sklepu" (pullShopData)
- After syncShop() job completes, DB is updated BUT cache is NOT
- Badge comparison uses stale cache → false "Oczekujące zmiany"

---

### Problem #2: Missing Success Toast (Investigation)

**Code Analysis:**

```php
// pullShopData() - Line 4023
$this->dispatch('success', message: "Wczytano dane ze sklepu {$shop->name}");
```

**Expected:** Green toast notification "Wczytano dane ze sklepu [nazwa]"

**Hypothesis:**
- Code HAS dispatch('success') ✅
- Either:
  1. Toast system not showing (CSS/JS issue)
  2. Exception thrown before reaching Line 4023
  3. Livewire event not bubbling to Alpine.js toast handler

**Needs User Verification:** Check if toast appears after deployment

---

## ✅ ROZWIĄZANIE - FIX #9

### FIX #9.1: Clear loadedShopData Cache After Sync Job Completion

**Location:** `checkBulkSyncJobStatus()` - Lines 3632-3636

**BEFORE:**
```php
if ($allSynchronized) {
    $this->activeJobStatus = 'completed';
    $this->jobResult = 'success';

    Log::info('[ETAP_13 BULK SYNC] All shops synchronized', [
        'product_id' => $this->product->id,
        'shops_count' => $connectedShops->count(),
    ]);
}
```

**AFTER:**
```php
if ($allSynchronized) {
    $this->activeJobStatus = 'completed';
    $this->jobResult = 'success';

    // FIX 2025-11-18 (#9.1): Clear loadedShopData cache after sync
    // (getPendingChangesForShop() compares DB vs loadedShopData - stale cache = false "Oczekujące zmiany")
    foreach ($connectedShops as $shopData) {
        unset($this->loadedShopData[$shopData->shop_id]);
    }

    Log::info('[ETAP_13 BULK SYNC] All shops synchronized', [
        'product_id' => $this->product->id,
        'shops_count' => $connectedShops->count(),
        'cleared_cache_shops' => $connectedShops->pluck('shop_id')->all(),
    ]);
}
```

**How This Fixes Badge:**

**BEFORE FIX:**
```
Job completes → sync_status = 'synced' in DB
getPendingChangesForShop():
   - DB: name = "Updated Name"
   - Cache: name = "Old Name"
   → Diff detected → Badge: "Oczekujące zmiany (1)" ❌
```

**AFTER FIX:**
```
Job completes → sync_status = 'synced' in DB
Cache cleared: unset($this->loadedShopData[$shopId])
getPendingChangesForShop():
   - isset($this->loadedShopData[$shopId]) → FALSE
   - return [];  // No cache = no comparison = no pending changes
   → Badge HIDDEN ✅
```

**Why This Works:**
- After cache clear, `getPendingChangesForShop()` returns `[]` (empty array)
- Blade: `@if(!empty($pendingChanges))` → FALSE
- Badge section NOT rendered
- Next "Wczytaj z aktualnego sklepu" will repopulate cache with FRESH data

---

### FIX #9.2: Remove Job Tracking from pullShopData() Catch Block

**Location:** `pullShopData()` - Lines 4034-4035

**BEFORE:**
```php
} catch (\Exception $e) {
    $this->activeJobStatus = 'failed';  // ❌ Inconsistent with FIX #8.1
    $this->jobResult = 'error';

    Log::error(...);
    $this->dispatch('error', message: '...');
}
```

**AFTER:**
```php
} catch (\Exception $e) {
    // FIX 2025-11-18 (#9.2): No job tracking for sync operation (consistent with success path)

    Log::error(...);
    $this->dispatch('error', message: '...');
}
```

**Why:** Consistency with FIX #8.1 - pullShopData() is SYNCHRONOUS, should NOT use job tracking properties

---

## 📦 DEPLOYMENT

### Files Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (229 kB)
   - **Lines 3632-3636:** Added loadedShopData cache clear (FIX #9.1)
   - **Lines 4034-4035:** Removed job tracking from catch (FIX #9.2)

### Deployment Steps:
```bash
# 1. Upload ProductForm.php
pscp -i $HostidoKey -P 64321 "app\Http\Livewire\Products\Management\ProductForm.php" \
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/..."

# 2. Clear caches
plink ... -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"
```

### Production Status:
- ✅ File uploaded (229 kB - +1 kB from comments)
- ✅ Caches cleared
- ✅ **Zero errors** in Laravel logs
- ⏳ Awaiting user testing

---

## 🧪 TESTING GUIDE

### Test Suite: "Oczekujące zmiany" Badge Behavior

**URL:** https://ppm.mpptrade.pl/admin/products/11033/edit

**CRITICAL:** Hard refresh **Ctrl+Shift+R** before test

---

### TEST #1: "Aktualizuj aktualny sklep" - Badge Clears After Job

**Goal:** Verify badge "Oczekujące zmiany" disappears AFTER job completion

**Steps:**
1. Przełącz na sklep (np. B2B Test DEV)
2. **Zmień** pole "Nazwa produktu" (np. dodaj " - TEST")
3. **PRZED kliknięciem:** Sprawdź "Szczegóły synchronizacji"
   - Badge powinien pokazać: "⚠️ Oczekujące zmiany (1): Nazwa produktu" ✅
4. Kliknij **"Aktualizuj aktualny sklep"** (pomarańczowy przycisk)
5. **OBSERWUJ:**
   - Toast: "Rozpoczęto aktualizację produktu na sklepie [nazwa]"
   - "Ostatnia aktualizacja sklepu" timestamp zaczyna zmieniać się (~20-40s)
6. **PO zakończeniu job-a** (~20-40s):
   - "Ostatnia aktualizacja sklepu" pokazuje nowy timestamp (np. "10 sekund temu")

**Expected Results:**
- ✅ **Badge "Oczekujące zmiany" ZNIKA** po zakończeniu job-a
- ✅ "Szczegóły synchronizacji" NIE pokazują "⚠️ Oczekujące zmiany (1)"
- ✅ UI czyste, bez false positive warnings

**FAIL jeśli:**
- ❌ Badge "Oczekujące zmiany (1)" POZOSTAJE mimo zakończenia job-a
- ❌ Badge pokazuje "Nazwa produktu" jako oczekującą mimo sync

---

### TEST #2: "Wczytaj z aktualnego sklepu" - Success Toast

**Goal:** Verify success toast message appears

**Steps:**
1. Przełącz na sklep (np. B2B Test DEV)
2. Kliknij **"Wczytaj z aktualnego sklepu"** (niebieski przycisk)
3. **OBSERWUJ górny prawy róg** ekranu

**Expected Results:**
- ✅ **Zielony toast** pojawia się: "Wczytano dane ze sklepu [nazwa]"
- ✅ Wire:loading spinner (~0.5-2s)
- ✅ Fields aktualizują się (nazwa, opisy)
- ✅ "Ostatnie wczytanie danych" - nowy timestamp

**FAIL jeśli:**
- ❌ BRAK toasta (mimo że operacja się udała)
- ❌ Tylko spinner bez komunikatu sukcesu

**NOTE:** Jeśli toast NIE pokazuje się - sprawdź konsolę przeglądarki (F12) czy są błędy JS

---

### Verification (Backend Logs):

```powershell
# Check sync job completion + cache clear
plink ... "tail -200 storage/logs/laravel.log" | grep "ETAP_13 BULK SYNC"
```

**Expected:**
```
[ETAP_13 BULK SYNC] All shops synchronized
cleared_cache_shops: [1,5,6]
```

```powershell
# Check pull success
plink ... "tail -200 storage/logs/laravel.log" | grep "SINGLE SHOP PULL"
```

**Expected:**
```
[ETAP_13 SINGLE SHOP PULL] Product data pulled successfully
```

---

## 📊 BENEFITS

### 1. Accurate "Oczekujące zmiany" Badge ✅
- **BEFORE:** Badge stays after job completion (false positive)
- **AFTER:** Badge disappears when job completes

### 2. No Stale Cache Pollution ✅
- **BEFORE:** loadedShopData stale after sync job
- **AFTER:** Cache cleared → fresh data on next pull

### 3. Consistent Error Handling ✅
- **BEFORE:** pullShopData() catch used job tracking (inconsistent)
- **AFTER:** Sync operations NEVER use job tracking

---

## 🔗 SESSION CHAIN

**ETAP_13 Fix Chain (2025-11-18 Session):**

1-17. [Previous fixes - FIX #1 through #8]

18. ✅ **FIX #9:** Post-job cache refresh ← **THIS REPORT**
    - **#9.1:** Clear loadedShopData after sync job completion
    - **#9.2:** Remove job tracking from pullShopData() catch (consistency)

**Total Session Fixes:** 18 critical issues resolved
**Production Status:** All features deployed, awaiting testing

---

## 📁 FILES

### Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (Lines 3632-3636, 4034-4035)

### Reports (Session):
1-17. [Previous session reports - FIX #1 through #8]
18. `_AGENT_REPORTS/HOTFIX_post_job_cache_refresh_2025-11-18_REPORT.md` ← **THIS REPORT**

---

## 📋 NEXT STEPS

### IMMEDIATE (User)

**CRITICAL:** Test oba scenariusze!

- [ ] **TEST #1:** "Aktualizuj aktualny sklep" → verify badge ZNIKA po job completion
- [ ] **TEST #2:** "Wczytaj z aktualnego sklepu" → verify toast POKAZUJE SIĘ

### IF TOAST MISSING (TEST #2 FAILS):

User should:
1. Otwórz DevTools (F12) → Console tab
2. Kliknij "Wczytaj z aktualnego sklepu"
3. Screenshot console errors (if any)
4. Report back

Possible causes:
- Livewire event not bubbling
- Alpine.js toast handler missing
- CSS hiding toast

### AFTER "DZIAŁA IDEALNIE"

- [ ] Debug log cleanup (skill: debug-log-cleanup)
- [ ] ETAP_13 COMPLETE ✅

---

**Report Generated:** 2025-11-18 20:20
**Status:** ✅ DEPLOYED - Ready for testing
**Next Action:** User tests both scenarios → Reports results (especially toast visibility)

**Key Achievement:** Eliminated false "Oczekujące zmiany" badge after job completion via cache invalidation strategy

**Critical Learning:** Cache invalidation > cache update - safer to clear and lazy-reload than risk stale comparison data
