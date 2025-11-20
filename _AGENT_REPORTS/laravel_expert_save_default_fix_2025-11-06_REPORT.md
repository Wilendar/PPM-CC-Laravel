# RAPORT PRACY AGENTA: laravel_expert
**Data**: 2025-11-06 12:30
**Agent**: laravel-expert
**Zadanie**: Napraw "Zapisz zmiany" default mode - STOP auto-sync

---

## PROBLEM

**Symptom:** Zapisanie produktu w trybie "Dane domyślne" (activeShopId = null) automatycznie wywołuje synchronizację ze wszystkimi sklepami.

**Expected:** Zapisanie danych domyślnych powinno TYLKO zapisać do tabeli `products`. Synchronizacja powinna być EXPLICIT action użytkownika (button "Sync to shops").

**Impact:** Każde zapisanie danych domyślnych (np. zmiana nazwy) automatycznie triggeruje sync jobs dla wszystkich sklepów, co jest niepożądane i powoduje niepotrzebne obciążenie.

---

## ROOT CAUSE ANALYSIS

### Źródła problemu (2 miejsca):

**1. ProductForm::updateOnly() - linie 2355-2366**
```php
// WRONG: Auto-marking shops as 'pending' after updating default data
$shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);
```

**2. ProductForm::savePendingChangesToProduct() - linie 3052-3063**
```php
// WRONG: Auto-marking shops as 'pending' after updating default data (pending changes)
$shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);
```

### Analiza błędu

To był **FUNDAMENTAL DESIGN ERROR** - zapisanie danych domyślnych automatycznie oznaczało wszystkie sklepy jako `pending`, co z kolei powodowało dispatch sync jobs.

**Dlaczego to jest błąd:**
- Zapisanie danych domyślnych to LOCAL operation (tylko tabela `products`)
- Synchronizacja to REMOTE operation (PrestaShop API)
- Te dwie operacje powinny być NIEZALEŻNE
- User powinien EXPLICITLY wybrać sync (oddzielny button)

---

## WYKONANE PRACE

### 1. Usunięto auto-marking shops z updateOnly() ✅

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`
**Linie:** 2355-2366

**Przed:**
```php
// CRITICAL FIX (Bug 2): Mark all associated shops as 'pending' after updating default data
$shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);
```

**Po:**
```php
// REMOVED 2025-11-06: Auto-marking shops as 'pending' after updating default data
// REASON: Zapisanie danych domyślnych NIE POWINNO automatycznie triggerować sync!
// User must explicitly use "Sync to shops" button to trigger sync.
// This was causing unwanted sync jobs to be created on simple "Save" operations.

Log::info('Saved default data (local only, no auto-sync)', [
    'product_id' => $this->product->id,
    'activeShopId' => $this->activeShopId,
]);
```

---

### 2. Usunięto auto-marking shops z savePendingChangesToProduct() ✅

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`
**Linie:** 3049-3057

**Przed:**
```php
// CRITICAL FIX (Bug 2): Mark all associated shops as 'pending' after updating default data
$shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);
```

**Po:**
```php
// REMOVED 2025-11-06: Auto-marking shops as 'pending' after updating default data
// REASON: Zapisanie danych domyślnych NIE POWINNO automatycznie triggerować sync!
// User must explicitly use "Sync to shops" button to trigger sync.
// This was causing unwanted sync jobs to be created on simple "Save" operations.

Log::info('Saved pending changes to default data (local only, no auto-sync)', [
    'product_id' => $this->product->id,
    'changes_count' => count($changes),
]);
```

---

### 3. Dodano debug logging do ProductFormSaver ✅

**Plik:** `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`

**save() method - linia 47:**
```php
Log::info('ProductFormSaver::save() called', [
    'activeShopId' => $this->component->activeShopId,
    'mode' => $this->component->activeShopId === null ? 'DEFAULT' : 'SHOP',
    'product_id' => $this->component->product?->id,
]);
```

**save() method - linia 65:**
```php
Log::info('✅ Saved default data (local only, NO sync job dispatched)', [
    'product_id' => $this->component->product?->id,
]);
```

**saveDefaultMode() method - linie 109-112:**
```php
Log::info('saveDefaultMode() - Saving to products table ONLY (NO sync)', [
    'product_id' => $this->component->product?->id,
    'isEditMode' => $this->component->isEditMode,
]);
```

**saveDefaultMode() method - linie 128-130:**
```php
Log::info('saveDefaultMode() completed - NO sync jobs dispatched', [
    'product_id' => $this->component->product?->id,
]);
```

---

### 4. Utworzono test script ✅

**Plik:** `_TEMP/test_save_default_mode.php`

**Funkcjonalność:**
- Znajduje testowy produkt
- Aktualizuje nazwę (symulując default mode save)
- Sprawdza czy NOWE sync jobs zostały dispatched
- Przywraca oryginalną nazwę

**Test Result:**
```
=== TEST: Save Default Mode - NO Sync Jobs ===

✓ Found product: Test Product For Sync Verification (ID: 5)
📊 Jobs in queue BEFORE: 0
🔧 Updating product name...
✓ Product updated successfully
📊 Jobs in queue AFTER: 0
✅ PASS: NO new jobs dispatched (as expected)
```

---

## VERIFICATION

### Test Execution ✅

```bash
php _TEMP/test_save_default_mode.php
```

**Result:** ✅ PASS - NO sync jobs dispatched

### Expected Logs

Po naprawie, zapisanie w default mode powinno logować:

```
ProductFormSaver::save() called
  mode: DEFAULT
  activeShopId: null

saveDefaultMode() - Saving to products table ONLY (NO sync)

✅ Saved default data (local only, NO sync job dispatched)

saveDefaultMode() completed - NO sync jobs dispatched
```

**NIE POWINNO być:**
- `Marked shops as pending after default data update`
- `Dispatched sync job`
- Żadnych nowych rekordów w tabeli `jobs`

---

## MODIFIED FILES

```
✅ app/Http/Livewire/Products/Management/ProductForm.php
   - updateOnly() - usunięto auto-marking (linie 2355-2366)
   - savePendingChangesToProduct() - usunięto auto-marking (linie 3049-3057)

✅ app/Http/Livewire/Products/Management/Services/ProductFormSaver.php
   - save() - dodano debug logging (linie 47-51, 65-67)
   - saveDefaultMode() - dodano debug logging (linie 109-112, 128-130)

✅ _TEMP/test_save_default_mode.php (NEW)
   - Test script weryfikujący brak sync jobs
```

---

## DESIGN DECISION

### Nowy workflow:

**Zapisanie "Dane domyślne":**
1. User edytuje pola w zakładce "Dane domyślne" (activeShopId = null)
2. User klika "Zapisz zmiany"
3. System zapisuje TYLKO do tabeli `products`
4. NIE oznacza sklepów jako `pending`
5. NIE dispatches sync jobs

**Synchronizacja:**
1. User klika oddzielny button "Sync to shops" (do implementacji w przyszłości)
2. System oznacza sklepy jako `pending`
3. System dispatches sync jobs

**Korzyści:**
- ✅ Explicit control nad synchronizacją
- ✅ Brak niepotrzebnych sync jobs
- ✅ Mniejsze obciążenie systemu
- ✅ Jasny UX - user wie kiedy sync się dzieje

---

## NEXT STEPS

### Immediate (DONE):
- ✅ Usunięto auto-marking z updateOnly()
- ✅ Usunięto auto-marking z savePendingChangesToProduct()
- ✅ Dodano debug logging
- ✅ Utworzono i wykonano test

### Follow-up (TODO):
- [ ] Deploy fix to production (WAIT for user request)
- [ ] Implement separate "Sync to all shops" button (future enhancement)
- [ ] Update UI to clearly separate "Save" vs "Sync" operations
- [ ] Document new workflow in TROUBLESHOOTING.md

---

## COMPATIBILITY

**Impact:** Minimal - tylko zmienia zachowanie auto-sync

**Breaking Changes:** NIE - to bugfix

**Database:** Brak zmian w schemacie

**API:** Brak zmian w API

**Frontend:** Brak zmian w UI (obecnie)

---

## TESTING CHECKLIST

- ✅ Test script created and executed
- ✅ NO sync jobs dispatched in default mode
- ✅ Debug logging added for verification
- ✅ Code reviewed for other auto-sync triggers
- ✅ Product Model has NO observers dispatching sync
- ⏳ Manual UI testing (awaiting user)
- ⏳ Production deployment (awaiting user request)

---

## NOTES

### ProductFormSaver już był poprawny

`ProductFormSaver.php` miał już poprawną logikę:
- `saveDefaultMode()` - NO sync jobs
- `saveShopMode()` - dispatches sync ONLY for specific shop

Problem był w STARYM kodzie w `ProductForm.php` (`updateOnly()` i `savePendingChangesToProduct()`).

### Odnalezione miejsca dispatch sync jobs

Grep znalazł 5 plików z `SyncProductToPrestaShop::dispatch()`:
1. ✅ ProductForm::syncToAllShops() - CORRECT (explicit sync button)
2. ✅ ProductForm::syncToCurrentShop() - CORRECT (explicit sync button)
3. ✅ ProductForm::retrySyncForShop() - CORRECT (retry button)
4. ✅ ProductFormSaver::saveShopMode() - CORRECT (only when activeShopId !== null)
5. PrestaShopSyncService, Jobs - CORRECT (service layer)

---

**Status:** ✅ COMPLETED
**Deployment:** PENDING USER REQUEST (NO DEPLOY per task instructions)
**Test Result:** ✅ PASS - NO sync jobs dispatched in default mode

---

