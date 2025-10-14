# DEBUG LOGGING BEST PRACTICES

**Data utworzenia:** 2025-10-01
**Status:** ✅ ACTIVE PRACTICE
**Priorytet:** 🟢 GOOD PRACTICE

---

## 📋 ZASADA

**⚠️ KRYTYCZNA:** Podczas developmentu używaj zaawansowanych logów (`Log::debug()`), po weryfikacji przez użytkownika usuń je i zostaw tylko production-ready logi (`Log::info/warning/error`).

---

## 🎯 DLACZEGO?

### Benefits Extensive Logging (Development):
- ✅ Szybkie zidentyfikowanie root cause problemu
- ✅ Śledzenie typu danych (int vs string, array structure)
- ✅ Monitoring stanu przed/po operacji
- ✅ Łatwiejsze debugowanie na produkcji podczas testów

### Benefits Minimal Logging (Production):
- ✅ Czytelne logi zawierające tylko istotne informacje
- ✅ Mniejsze zużycie storage
- ✅ Szybszy monitoring i alert system
- ✅ Lepsze performance (mniej I/O operations)
- ✅ Łatwiejsze znalezienie prawdziwych problemów

---

## 🔄 WORKFLOW

```
┌─────────────────────────────────────────────────────────────┐
│ 1. DEVELOPMENT PHASE                                         │
│    - Implementuj funkcjonalność                              │
│    - Dodaj Log::debug() z pełnym kontekstem                  │
│    - Deploy na produkcję                                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. USER TESTING PHASE                                        │
│    - Użytkownik testuje funkcjonalność                       │
│    - Debug logi pomagają w identyfikacji problemów           │
│    - Naprawiasz ewentualne bugi                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. USER CONFIRMATION                                         │
│    ✅ "działa idealnie" / "wszystko działa jak należy"       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. CLEANUP PHASE                                             │
│    - Usuń wszystkie Log::debug()                             │
│    - Usuń logi BEFORE/AFTER, gettype(), itp.                │
│    - Zostaw tylko Log::info/warning/error                    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. FINAL DEPLOY                                              │
│    - Deploy clean version                                    │
│    - Production-ready z minimal logging                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 DEVELOPMENT PHASE - Extensive Logging

### Co logować podczas developmentu:

```php
// ✅ Stan PRZED operacją
Log::debug('methodName CALLED', [
    'input_param' => $param,
    'input_type' => gettype($param),
    'array_BEFORE' => $this->someArray,
    'array_types' => array_map('gettype', $this->someArray),
]);

// ✅ Pośrednie kroki z full context
Log::debug('Processing step X', [
    'current_state' => $this->state,
    'intermediate_value' => $value,
    'conditions_met' => $conditionsArray,
]);

// ✅ Stan PO operacji
Log::debug('methodName COMPLETED', [
    'array_AFTER' => $this->someArray,
    'changes_made' => $changesList,
    'result' => $result,
]);
```

### Przykład real-world (Shop Labels Bug Fix):

```php
public function removeFromShop(int $shopId): void
{
    // DEVELOPMENT - Full diagnostic logging
    $shopId = (int) $shopId;

    Log::debug('removeFromShop CALLED', [
        'shop_id' => $shopId,
        'shop_id_type' => gettype($shopId),
        'exportedShops_BEFORE' => $this->exportedShops,
        'exportedShops_types' => array_map('gettype', $this->exportedShops),
        'shopsToRemove_BEFORE' => $this->shopsToRemove,
    ]);

    $key = array_search($shopId, $this->exportedShops, false);
    if ($key === false) {
        Log::warning('removeFromShop ABORTED - shop not found', [
            'shop_id' => $shopId,
            'exportedShops' => $this->exportedShops,
        ]);
        return;
    }

    // ... business logic ...

    Log::debug('removeFromShop COMPLETED', [
        'shop_id' => $shopId,
        'exportedShops_AFTER' => $this->exportedShops,
        'shopsToRemove_AFTER' => $this->shopsToRemove,
        'removedShopsCache_keys' => array_keys($this->removedShopsCache),
    ]);

    $this->successMessage = "Sklep zostanie usunięty po zapisaniu zmian.";
}
```

**Zalety:**
- Widzimy typ `$shopId` (czy int czy string?)
- Widzimy typy wszystkich elementów w `$exportedShops`
- Widzimy stan przed i po operacji
- Łatwo zidentyfikować problem (np. mixed int/string types)

---

## ✅ PRODUCTION PHASE - Minimal Logging

### Co ZOSTAWIĆ w production:

```php
// ✅ Log::info() - Ważne operacje biznesowe
Log::info('Shop marked for deletion', [
    'product_id' => $this->product?->id,
    'shop_id' => $shopId,
]);

Log::info('Product created successfully', [
    'product_id' => $product->id,
    'sku' => $product->sku,
]);

// ✅ Log::warning() - Nietypowe sytuacje (nie błędy)
Log::warning('Shop removal failed - not in list', [
    'shop_id' => $shopId,
    'product_id' => $this->product?->id,
]);

Log::warning('Skipping shop create - shopData missing', [
    'shop_id' => $shopId,
]);

// ✅ Log::error() - Wszystkie błędy i exceptions
Log::error('Product save failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'product_id' => $this->product?->id,
]);
```

### Przykład real-world (AFTER cleanup):

```php
public function removeFromShop(int $shopId): void
{
    // PRODUCTION - Clean, essential logging only
    $shopId = (int) $shopId;

    $key = array_search($shopId, $this->exportedShops, false);
    if ($key === false) {
        Log::warning('Shop removal failed - not in list', [
            'shop_id' => $shopId,
            'product_id' => $this->product?->id,
        ]);
        return;
    }

    // Cache shop data before removal (for undo/re-add)
    if (isset($this->shopData[$shopId])) {
        $this->removedShopsCache[$shopId] = $this->shopData[$shopId];
    }

    // Mark for DB deletion if has DB record
    if (isset($this->shopData[$shopId]['id']) && $this->shopData[$shopId]['id'] !== null) {
        $this->shopsToRemove[] = $shopId;
        Log::info('Shop marked for deletion', [
            'product_id' => $this->product?->id,
            'shop_id' => $shopId,
        ]);
    }

    // Remove from arrays using type-safe filter
    $this->exportedShops = array_values(
        array_filter($this->exportedShops, fn($id) => (int)$id !== $shopId)
    );

    unset($this->shopData[$shopId]);
    unset($this->shopCategories[$shopId]);
    unset($this->shopAttributes[$shopId]);

    if ($this->activeShopId === $shopId) {
        $this->activeShopId = null;
    }

    $this->hasUnsavedChanges = true;
    $this->successMessage = "Sklep zostanie usunięty po zapisaniu zmian.";
}
```

**Różnice:**
- ❌ USUNIĘTO: `Log::debug('CALLED')` i `Log::debug('COMPLETED')`
- ❌ USUNIĘTO: `gettype()`, `array_map('gettype')`
- ❌ USUNIĘTO: Stan BEFORE/AFTER
- ✅ POZOSTAŁO: `Log::warning()` gdy operacja się nie powiodła
- ✅ POZOSTAŁO: `Log::info()` dla ważnej operacji biznesowej

---

## 🚫 CO USUNĄĆ

### Usuń wszystkie:

```php
// ❌ Log::debug() - wszystkie!
Log::debug('...', [...]);

// ❌ Stan BEFORE/AFTER
Log::debug('array_BEFORE' => ..., 'array_AFTER' => ...);

// ❌ Type information
Log::debug('type' => gettype($var));
Log::debug('types' => array_map('gettype', $array));

// ❌ CALLED/COMPLETED markers
Log::debug('methodName CALLED');
Log::debug('methodName COMPLETED');

// ❌ Intermediate steps (unless critical)
Log::debug('Processing step X', [...]);

// ❌ Full array dumps (unless error context)
Log::debug('full_array' => $largeArray);
```

---

## ✅ CHECKLIST - Cleanup Before Final Deploy

Po otrzymaniu potwierdzenia od użytkownika że funkcjonalność działa:

- [ ] Przeszukaj plik: znajdź wszystkie `Log::debug(`
- [ ] Usuń wszystkie `Log::debug()` calls
- [ ] Znajdź wszystkie `gettype(` i `array_map('gettype'`
- [ ] Usuń logi z typami danych
- [ ] Sprawdź logi "BEFORE" / "AFTER" - usuń je
- [ ] Sprawdź logi "CALLED" / "COMPLETED" - usuń je
- [ ] Zostaw tylko `Log::info/warning/error` dla business operations
- [ ] Review commit diff - upewnij się że nie usunąłeś za dużo
- [ ] Deploy clean version
- [ ] Verify w production logs że nie ma debug spam

---

## 📊 METRYKI

### Przed Cleanup (Development):
```
[2025-10-01 09:04:35] production.DEBUG: removeFromShop CALLED {"shop_id":3,"shop_id_type":"integer","exportedShops_BEFORE":[1,4,2,"3"],"exportedShops_types":["integer","integer","integer","string"],...}
[2025-10-01 09:04:35] production.DEBUG: Save: Filtering shops to create {"exportedShops":[1,4,2,"3"],"shopsToRemove":[3],"shopsToCreate":[1,4,2]}
[2025-10-01 09:04:35] production.INFO: Shop marked for deletion {"product_id":4,"shop_id":3}
[2025-10-01 09:04:35] production.DEBUG: removeFromShop COMPLETED {"exportedShops_AFTER":[1,4,2],"shopsToRemove_AFTER":[3]}
```

**Linie:** 4 (1 info + 3 debug)

### Po Cleanup (Production):
```
[2025-10-01 09:04:35] production.INFO: Shop marked for deletion {"product_id":4,"shop_id":3}
```

**Linie:** 1 (tylko info)

**Redukcja:** 75% mniej logów, 100% informacji o business operation zachowane.

---

## 💡 BEST PRACTICES

### DO ✅

1. **Development:** Loguj wszystko co pomaga w debugowaniu
2. **Type debugging:** Używaj `gettype()`, `array_map('gettype')` swobodnie
3. **State tracking:** Loguj stan przed/po każdej operacji
4. **Deploy all logs:** Pierwsza wersja na produkcję MA mieć debug logi
5. **Wait for confirmation:** Nie usuwaj debug logów dopóki user nie potwierdzi
6. **Clean deploy:** Po potwierdzeniu, cleanup i final deploy

### DON'T ❌

1. **Nie usuwaj logów** przed user confirmation
2. **Nie zostawiaj Log::debug()** w final production version
3. **Nie usuwaj Log::info/warning/error** - to production-essential
4. **Nie loguj wrażliwych danych** (hasła, tokeny, dane osobowe)
5. **Nie zapomnij** o performance impact (duże tablice w logach)

---

## 🔗 POWIĄZANE DOKUMENTY

- **CLAUDE.md** - Sekcja "Debug Logging Best Practices"
- **PHP_TYPE_JUGGLING_ISSUE.md** - Przykład problemu znalezionego dzięki debug logging
- **SHOP_LABELS_LIVEWIRE_REACTIVITY_FIX_20251001.md** - Real-world case study

---

## 📝 CASE STUDY - Shop Labels Bug

**Problem:** Shop labels nie znikały po kliknięciu ❌

**Development Logging pomogło zidentyfikować:**
```json
exportedShops:[1,4,2,"3"]  ← Sklep 3 jest STRING!
            ↑ integers  ↑ STRING
```

**Root Cause:** Mixed int/string types + strict comparison w `array_search()`

**Bez Debug Logging:** Problem byłby niemal niemożliwy do zdiagnozowania (blind debugging)

**Z Debug Logging:** Problem natychmiast widoczny w logach (1 spojrzenie)

**Time Saved:** ~8-12 godzin debugowania

---

**Autor:** Claude Code (Sonnet 4.5)
**Data:** 2025-10-01
**Status:** ✅ ACTIVE PRACTICE - Obowiązuje dla wszystkich agentów
