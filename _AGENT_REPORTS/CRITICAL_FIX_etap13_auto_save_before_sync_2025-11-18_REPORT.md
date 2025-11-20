# CRITICAL FIX: Auto-Save Pending Changes Before Sync

**Data:** 2025-11-18 09:00
**Priorytet:** 🔥 CRITICAL
**Status:** ✅ DEPLOYED

---

## PROBLEM

### User Report:
```json
{
    "success": true,
    "external_id": 1830,
    "message": "No changes - sync skipped",
    "checksum": "e36c9c263efde8fb3de0ebba4fd204fb42072d41f7778dd3f47fa36ea47f7045",
    "skipped": true
}
```

**Objawy:**
- ✅ Button "Aktualizuj sklepy" działa (JOB się tworzy)
- ✅ JOB łączy się z PrestaShop (external_id: 1830)
- ❌ Sync pominięty: "No changes - sync skipped"
- ❌ User zmienił dane w TAB sklepu, ale zmiany NIE zostały wykryte

---

## ROOT CAUSE ANALYSIS

### Flow (BEFORE FIX):

```
1. User edytuje dane w TAB sklepu (np. nazwa, cena, VAT)
   ↓ wire:model updates component properties
2. Dane w formularzu (wire:model) ✅
   ↓ Dane w ProductShopData (database) ❌ NOT SAVED
3. User klika "Aktualizuj sklepy"
   ↓ bulkUpdateShops() dispatch SyncProductToPrestaShop
4. SyncProductToPrestaShop generuje checksum
   ↓ Czyta z ProductShopData (STARE dane! ❌)
5. Checksum PPM (stare) == Checksum PrestaShop (stare)
   ↓ "No changes detected"
6. RESULT: "No changes - sync skipped" ❌
```

**Conclusion:** Pending changes w formularzu NIE SĄ zapisane do bazy przed dispatch job!

---

## SOLUTION: Auto-Save Before Dispatch

### Implementation:

Dodano wywołanie `saveAllPendingChanges()` PRZED dispatch jobs w obu metodach:
1. `bulkUpdateShops()` - export PPM → PrestaShop
2. `bulkPullFromShops()` - import PrestaShop → PPM

### Code Changes:

**File:** `app/Http/Livewire/Products/Management/ProductForm.php`

#### Change #1: bulkUpdateShops() (Lines 3558-3575)

**BEFORE:**
```php
public function bulkUpdateShops(): void
{
    if (!$this->product) {
        $this->dispatch('error', message: 'Produkt nie istnieje');
        return;
    }

    // ETAP_13: Check for active job (anti-duplicate)
    if ($this->hasActiveSyncJob()) {
        $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
        return;
    }

    // Get connected shops
    $shops = $this->product->shopData->pluck('shop')->filter(...);

    // Dispatch sync jobs
    foreach ($shops as $shop) {
        SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());
    }
}
```

**AFTER:**
```php
public function bulkUpdateShops(): void
{
    if (!$this->product) {
        $this->dispatch('error', message: 'Produkt nie istnieje');
        return;
    }

    // ETAP_13: Check for active job (anti-duplicate)
    if ($this->hasActiveSyncJob()) {
        $this->dispatch('warning', message: 'Synchronizacja już w trakcie. Poczekaj na zakończenie.');
        return;
    }

    // ✅ CRITICAL FIX (2025-11-18): Auto-save pending changes BEFORE dispatch
    // Without this, checksum is based on OLD data → "No changes - sync skipped"
    try {
        $this->saveAllPendingChanges();

        Log::info('[ETAP_13 AUTO-SAVE] Pending changes saved before bulk update', [
            'product_id' => $this->product->id,
            'active_shop_id' => $this->activeShopId,
        ]);
    } catch (\Exception $e) {
        Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
            'product_id' => $this->product->id,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch('error', message: 'Nie udało się zapisać zmian przed synchronizacją: ' . $e->getMessage());
        return; // ✅ ABORT dispatch if save fails
    }

    // Get connected shops
    $shops = $this->product->shopData->pluck('shop')->filter(...);

    // Dispatch sync jobs (now with FRESH data from database)
    foreach ($shops as $shop) {
        SyncProductToPrestaShop::dispatch($this->product, $shop, auth()->id());
    }
}
```

---

#### Change #2: bulkPullFromShops() (Lines 3637-3654)

**BEFORE:**
```php
public function bulkPullFromShops(): void
{
    if (!$this->product) {
        $this->dispatch('error', message: 'Produkt nie istnieje');
        return;
    }

    // Get connected shops
    $shops = $this->product->shopData->pluck('shop')->filter(...);

    // Dispatch pull job
    $batch = BulkPullProducts::dispatch($this->product, $shops, auth()->id());
}
```

**AFTER:**
```php
public function bulkPullFromShops(): void
{
    if (!$this->product) {
        $this->dispatch('error', message: 'Produkt nie istnieje');
        return;
    }

    // ✅ CRITICAL FIX (2025-11-18): Auto-save pending changes BEFORE pull
    // Prevents data loss when user has unsaved changes
    try {
        $this->saveAllPendingChanges();

        Log::info('[ETAP_13 AUTO-SAVE] Pending changes saved before bulk pull', [
            'product_id' => $this->product->id,
            'active_shop_id' => $this->activeShopId,
        ]);
    } catch (\Exception $e) {
        Log::error('[ETAP_13 AUTO-SAVE] Failed to save pending changes', [
            'product_id' => $this->product->id,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch('error', message: 'Nie udało się zapisać zmian przed wczytaniem danych: ' . $e->getMessage());
        return; // ✅ ABORT dispatch if save fails
    }

    // Get connected shops
    $shops = $this->product->shopData->pluck('shop')->filter(...);

    // Dispatch pull job (pending changes already saved → no data loss)
    $batch = BulkPullProducts::dispatch($this->product, $shops, auth()->id());
}
```

---

## BENEFITS

### 1. Checksum Now Based on FRESH Data ✅

**Flow (AFTER FIX):**
```
1. User edytuje dane w TAB sklepu
   ↓ wire:model updates properties
2. User klika "Aktualizuj sklepy"
   ↓ bulkUpdateShops() called
3. ✅ saveAllPendingChanges() executed FIRST
   ↓ Zapisuje dane do ProductShopData (database)
4. SyncProductToPrestaShop dispatched
   ↓ Generuje checksum z NOWYCH danych ✅
5. Checksum PPM (nowe) ≠ Checksum PrestaShop (stare)
   ↓ "Changes detected"
6. RESULT: Sync executed successfully ✅
```

---

### 2. Data Loss Prevention ✅

**Scenario:** User ma unsaved changes + klika "Wczytaj ze sklepów"

**BEFORE FIX:**
- User changes lost (overwritten by PrestaShop data) ❌

**AFTER FIX:**
- User changes saved FIRST before pull ✅
- Pull overwrites with PrestaShop data (but user changes preserved in history)

---

### 3. Error Handling ✅

**If save fails:**
- ❌ Dispatch ABORTED (no job created)
- ✅ User sees error toast: "Nie udało się zapisać zmian przed synchronizacją"
- ✅ Log entry created for debugging

---

## DEPLOYMENT

### Files Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (204 kB)
   - Line 3558-3575: bulkUpdateShops() auto-save
   - Line 3637-3654: bulkPullFromShops() auto-save

### Deployment Steps:
1. ✅ Upload ProductForm.php: `pscp` (204 kB)
2. ✅ Clear cache: `php artisan cache:clear`

### Production Status:
- ✅ Deployed successfully
- ✅ Cache cleared
- ⏳ Awaiting user testing

---

## TESTING GUIDE

### Test Case #1: Zmiana Danych + Sync

**Steps:**
1. Navigate: https://ppm.mpptrade.pl/admin/products/11033/edit
2. Przełącz na TAB sklepu (np. sklep ID 1)
3. Zmień jakieś pole (np. nazwę produktu na "TEST AUTO-SAVE")
4. **NIE KLIKAJ "Zapisz zmiany"** (pending changes remain unsaved)
5. Kliknij "Aktualizuj sklepy" (sidepanel)

**Expected Results:**
- ✅ Toast: "Rozpoczęto aktualizację produktu na X sklepach"
- ✅ Countdown animation starts (60s → 0s)
- ✅ Log entry: `[ETAP_13 AUTO-SAVE] Pending changes saved before bulk update`
- ✅ Job executes successfully (NOT "No changes - sync skipped")
- ✅ PrestaShop otrzymuje zaktualizowane dane (nazwa = "TEST AUTO-SAVE")

**Verification (production logs):**
```powershell
plink ... "tail -100 storage/logs/laravel.log" | Select-String -Pattern "ETAP_13 AUTO-SAVE" -Context 2
```

Expected:
```
[ETAP_13 AUTO-SAVE] Pending changes saved before bulk update
product_id: 11033
active_shop_id: 1
```

---

### Test Case #2: Zmiana Danych + Pull (Data Loss Prevention)

**Steps:**
1. Navigate: https://ppm.mpptrade.pl/admin/products/11033/edit
2. Przełącz na TAB sklepu (np. sklep ID 1)
3. Zmień nazwę na "PENDING CHANGE"
4. **NIE KLIKAJ "Zapisz zmiany"**
5. Kliknij "Wczytaj ze sklepów" (sidepanel)

**Expected Results:**
- ✅ Toast: "Rozpoczęto wczytywanie danych ze X sklepów"
- ✅ Countdown animation starts
- ✅ Log entry: `[ETAP_13 AUTO-SAVE] Pending changes saved before bulk pull`
- ✅ User changes saved BEFORE pull (no data loss)
- ✅ Pull overwrites with PrestaShop data (expected behavior)

---

### Test Case #3: Save Failure Handling

**Steps (simulate failure):**
1. Modify ProductForm.php temporarily:
   ```php
   public function saveAllPendingChanges(): void
   {
       throw new \Exception('Simulated save failure');
   }
   ```
2. Deploy + clear cache
3. Try to click "Aktualizuj sklepy"

**Expected Results:**
- ❌ Job NOT dispatched (saveAllPendingChanges() failed)
- ✅ Error toast: "Nie udało się zapisać zmian przed synchronizacją: Simulated save failure"
- ✅ Log entry: `[ETAP_13 AUTO-SAVE] Failed to save pending changes`

**Cleanup:** Revert change + redeploy

---

## LESSONS LEARNED

### 1. Always Save Before Background Jobs
**Pattern:** Any user-initiated background job should auto-save pending changes FIRST
**Reason:** User expectation is "I clicked button → my current changes will be synced"
**Application:** Applies to ALL bulk operations (export, import, transformations)

### 2. Checksum-Based Sync Requires Fresh Data
**Issue:** Checksum comparison is meaningless if comparing OLD data
**Solution:** Ensure database has LATEST data before generating checksum
**Prevention:** Auto-save pattern enforces this

### 3. Error Handling Critical for Data Integrity
**Pattern:** If save fails → ABORT operation + notify user
**Benefit:** Prevents partial state (job dispatched but data not saved)
**Implementation:** Try-catch + early return

---

## NEXT STEPS

### IMMEDIATE (User)
- [ ] **Test Auto-Save** - Execute Test Case #1 (change data + sync)
  - Expected: Job executes successfully (NOT "No changes - sync skipped")
  - Verification: Check production logs for `[ETAP_13 AUTO-SAVE]`

### SHORT TERM (After Testing)
- [ ] **Remove Debug Logs** - Clean up `[ETAP_13 AUTO-SAVE]` logs (ONLY after "działa idealnie")
  - Keep error logs (`Log::error`)
  - Remove info logs (`Log::info`) from auto-save

### LONG TERM (Pattern Application)
- [ ] **Audit Other Bulk Operations** - Apply auto-save pattern to similar features
  - BulkSyncProducts (multiple products → one shop)
  - Import operations
  - Export operations

---

## FILES

### Modified:
1. `app/Http/Livewire/Products/Management/ProductForm.php` (Lines 3558-3575, 3637-3654)

### Reports:
1. `_AGENT_REPORTS/CRITICAL_FIX_etap13_auto_save_before_sync_2025-11-18_REPORT.md` (this file)

---

**Report Generated:** 2025-11-18 09:15
**Status:** ✅ DEPLOYED - Ready for user testing
**Next Action:** User tests Test Case #1 → verification → debug log cleanup
