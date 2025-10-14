# 🔍 RAPORT DIAGNOSTYCZNY: SYNC ARCHITECTURE INCONSISTENCY

**Data**: 2025-10-13
**Agent**: Main Assistant (Claude Code)
**Severity**: 🔥 **CRITICAL** - Fundamentalny problem architektury sync systemu
**Impact**: Wszystkie operacje sync (delete, update, status display)

---

## 📋 EXECUTIVE SUMMARY

Odkryłem fundamentalny problem architektury systemu synchronizacji PPM ↔ PrestaShop:

**ROOT CAUSE**: System ma **DWA RÓŻNE MIEJSCA** przechowywania sync status i `prestashop_product_id`:
1. `product_shop_data` (dane produktu per sklep + sync_status)
2. `product_sync_status` (tracking synchronizacji + prestashop_product_id + sync_status)

**PROBLEM**: Kod używa ich **NIESPÓJNIE** - różne części aplikacji czytają/zapisują do różnych tabel!

---

## 🔥 ZGŁOSZONE PROBLEMY UŻYTKOWNIKA

### Problem 1: Usunięcie produktu ze sklepu nie działa
**Symptom**: Kliknięcie "Usuń ze sklepu" nie usuwa produktu z PrestaShop przez API

**User Report**:
> "przycisk do usunięcia produkty na sklepie prestashop powoduje błąd"

### Problem 2: Sync status zawsze pokazuje 🟢 mimo rozbieżności
**Symptom**: Lista produktów pokazuje "Sync OK" nawet gdy są różnice między "Dane domyślne" a zakładką sklepu

**User Report**:
> "aplikacja pokazuje na liście produktów cały czas sync OK oraz 🟢 przy nazwie sklepu, nawet gdy jest rozbieżność w parametrach produktu oraz kategoriac między sklepem a 'Dane domyślne'"

**Przykład**: Produkt 10944 ma inne kategorie w "Dane domyślne" vs zakładka sklepu, ale status pokazuje 🟢

---

## 🏗️ ARCHITEKTURA SYSTEMU SYNC (CURRENT STATE)

### Tabela 1: `product_shop_data`

**Lokalizacja**: app/Models/ProductShopData.php
**Relacja**: `Product->shopData()`
**Przeznaczenie**: Dane produktu specyficzne per sklep PrestaShop

**Struktura**:
```sql
product_shop_data:
  - id
  - product_id
  - shop_id
  - name (shop-specific name override)
  - slug, sku, short_description, long_description
  - manufacturer, supplier_code, weight, dimensions, ean, tax_rate
  - category_mappings, attribute_mappings, image_settings
  - sync_status ENUM('pending','synced','error','conflict','disabled') ← UŻYWANE PRZEZ PRODUCT LIST ❌
  - last_sync_at, last_sync_hash
  - sync_errors, conflict_data, conflict_detected_at
  - is_published, published_at, unpublished_at
  - external_id, external_reference ← PUSTE! (brak prestashop_product_id)
```

**Kto używa**:
- ❌ ProductList view (`product-list.blade.php` linia 525) - czyta sync_status STĄD (błędnie!)
- ✅ ProductForm (`ProductForm.php`) - zapisuje dane produktu per sklep

**Problem**: Kolumna `sync_status` w `product_shop_data` NIE jest aktualizowana podczas sync operations!

---

### Tabela 2: `product_sync_status`

**Lokalizacja**: app/Models/ProductSyncStatus.php
**Relacja**: `Product->syncStatuses()`
**Przeznaczenie**: Tracking synchronizacji PPM ↔ PrestaShop

**Struktura**:
```sql
product_sync_status:
  - id
  - product_id
  - shop_id
  - prestashop_product_id ← KLUCZOWE! PrestaShop external ID
  - sync_status ENUM('pending','syncing','synced','error','conflict','disabled') ← PRAWDZIWY STATUS ✅
  - last_sync_at, last_success_sync_at
  - sync_direction ENUM('ppm_to_ps','ps_to_ppm','bidirectional')
  - error_message, conflict_data
  - retry_count, max_retries, priority
  - checksum (hash dla change detection)
```

**Kto używa**:
- ✅ Sync jobs (powinny używać, ale NIE UŻYWAJĄ!)
- ❌ DeleteProductFromPrestaShop job - próbuje czytać prestashop_product_id z ProductShopData (błędnie!)
- ❌ ProductForm - nie aktualizuje tej tabeli podczas edycji

**Dane dla produktu 10944**:
```
id: 10217
product_id: 10944
shop_id: 1 (B2B Test DEV)
prestashop_product_id: 87 ← TO JEST PRAWIDŁOWE ID!
sync_status: 'synced'
```

---

## 🐛 ROOT CAUSE ANALYSIS

### Problem 1: Delete Operation Nie Działa

**Co się dzieje**:
1. User klika "Usuń ze sklepu" w ProductForm
2. Job `DeleteProductFromPrestaShop` jest dispatchowany
3. Job próbuje odczytać `prestashop_product_id` z `ProductShopData` (linia 108):
   ```php
   // DeleteProductFromPrestaShop.php:108
   if (!$productShopData->prestashop_product_id) {
       Log::info('Product not synced to PrestaShop yet - only removing local association');
       // Delete local ProductShopData record
       $productShopData->delete();
       $this->updateSyncStatus('deleted', null);
       return; // ← KOŃCZY SIĘ TUTAJ!
   }
   ```
4. Kolumna `ProductShopData->prestashop_product_id` **NIE ISTNIEJE** w bazie!
5. Warunek zawsze jest true → job kończy bez wywołania API PrestaShop
6. Log pokazuje: "Product not synced to PrestaShop yet - only removing local association"

**PRAWDA**: Produkt **MA** prestashop_product_id=87 w tabeli `product_sync_status`!

**FIX**: Job powinien czytać z `ProductSyncStatus` zamiast `ProductShopData`:
```php
// CORRECT:
$syncStatus = ProductSyncStatus::where('product_id', $this->product->id)
    ->where('shop_id', $this->shop->id)
    ->first();

if ($syncStatus && $syncStatus->prestashop_product_id) {
    $client->deleteProduct($syncStatus->prestashop_product_id);
}
```

---

### Problem 2: Sync Status Zawsze Pokazuje 🟢

**Co się dzieje**:
1. User edytuje "Dane domyślne" produktu (nazwa, opis, kategorie)
2. **NIE aktualizuje** się `product_sync_status.sync_status`
3. Lista produktów czyta `product_shop_data.sync_status` (linia 525):
   ```php
   // product-list.blade.php:525
   $statuses = $product->shopData->pluck('sync_status')->toArray();
   ```
4. `product_shop_data.sync_status` NIE jest aktualizowany przez ProductForm
5. Status pozostaje 'synced' → badge pokazuje 🟢

**PRAWDA**: Edycja "Dane domyślne" powinna ustawiać `product_sync_status.sync_status = 'pending'`!

**FIX**:
1. ProductList powinien czytać z `$product->syncStatuses` zamiast `$product->shopData`
2. ProductForm powinien aktualizować `product_sync_status` podczas edycji "Dane domyślne"

---

## 📊 INCONSISTENCY MAP

### Gdzie kod używa BŁĘDNEJ tabeli:

| Lokalizacja | Używa | Powinno używać | Impact |
|-------------|-------|----------------|--------|
| `DeleteProductFromPrestaShop.php:108` | `ProductShopData->prestashop_product_id` | `ProductSyncStatus->prestashop_product_id` | Delete nie działa |
| `product-list.blade.php:525` | `$product->shopData->pluck('sync_status')` | `$product->syncStatuses->pluck('sync_status')` | Status badge zawsze 🟢 |
| `ProductForm.php:2056-2058` | Ustawia `product_shop_data.sync_status` | Powinno ustawiać `product_sync_status.sync_status` | Status nie zmienia się |
| `ProductForm.php:2760-2762` | Ustawia `product_shop_data.sync_status` | Powinno ustawiać `product_sync_status.sync_status` | Status nie zmienia się |

### Gdzie kod używa POPRAWNEJ tabeli:

| Lokalizacja | Używa | Status |
|-------------|-------|--------|
| `Product.php:1811` | `syncStatuses()` relation | ✅ Poprawna relacja dostępna |
| `Product.php:1844` | `syncStatusForShop($shopId)` helper | ✅ Poprawny helper dostępny |
| `Product.php:1860` | `getPrestashopProductId($shop)` | ✅ Poprawna metoda dostępna |

---

## 🔧 STRATEGIA NAPRAWY

### Etap 1: Naprawić DeleteProductFromPrestaShop Job

**Plik**: `app/Jobs/PrestaShop/DeleteProductFromPrestaShop.php`

**Zmiana**:
```php
// OLD (BŁĘDNE):
$productShopData = ProductShopData::where('product_id', $this->product->id)
    ->where('shop_id', $this->shop->id)
    ->first();

if (!$productShopData->prestashop_product_id) {
    // Skip API call
}

// NEW (POPRAWNE):
$syncStatus = ProductSyncStatus::where('product_id', $this->product->id)
    ->where('shop_id', $this->shop->id)
    ->first();

if (!$syncStatus || !$syncStatus->prestashop_product_id) {
    Log::info('Product not synced to PrestaShop yet - only removing local association');
    // Delete local records and return
    return;
}

// Delete from PrestaShop API
$client->deleteProduct($syncStatus->prestashop_product_id);
```

---

### Etap 2: Naprawić ProductList View

**Plik**: `resources/views/livewire/products/listing/product-list.blade.php`

**Zmiana** (linia 525):
```php
// OLD (BŁĘDNE):
$statuses = $product->shopData->pluck('sync_status')->toArray();

// NEW (POPRAWNE):
$statuses = $product->syncStatuses->pluck('sync_status')->toArray();
```

**Zmiana** (linia 551-554):
```php
// OLD (BŁĘDNE):
@foreach($product->shopData as $shopData)
    <div class="text-sm opacity-75">
        {{ $statusEmojis[$shopData->sync_status] ?? '⚪' }} {{ $shopData->shop->name ?? 'Unknown' }}
    </div>
@endforeach

// NEW (POPRAWNE):
@foreach($product->syncStatuses as $syncStatus)
    <div class="text-sm opacity-75">
        {{ $statusEmojis[$syncStatus->sync_status] ?? '⚪' }} {{ $syncStatus->shop->name ?? 'Unknown' }}
    </div>
@endforeach
```

---

### Etap 3: Naprawić ProductForm - Edycja "Dane domyślne"

**Plik**: `app/Http/Livewire/Products/Management/ProductForm.php`

**Zmiana 1** (po linii 2052):
```php
// OLD (BŁĘDNE):
$this->product->update([...]);

// CRITICAL FIX (Bug 2): Mark all associated shops as 'pending' after updating default data
$shopsMarkedPending = \App\Models\ProductShopData::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);

// NEW (POPRAWNE):
$this->product->update([...]);

// CRITICAL FIX: Mark all ProductSyncStatus as 'pending' after updating default data
$shopsMarkedPending = \App\Models\ProductSyncStatus::where('product_id', $this->product->id)
    ->where('sync_status', '!=', 'disabled')
    ->update(['sync_status' => 'pending']);
```

**Zmiana 2** (po linii 2756 - w savePendingChangesToProduct):
```php
// Same fix - zmienić ProductShopData na ProductSyncStatus
```

---

### Etap 4: Weryfikacja Innych Miejsc

**TODO**: Grep całego codebase dla patterns:
```bash
# Znajdź wszystkie miejsca aktualizujące sync_status
grep -rn "sync_status.*=" app/ --include="*.php" | grep -v "ProductSyncStatus"

# Znajdź wszystkie miejsca czytające prestashop_product_id
grep -rn "prestashop_product_id" app/ --include="*.php" | grep -v "ProductSyncStatus"
```

---

## 🎯 PRIORITIZACJA FIXÓW

| Priorytet | Fix | Impact | Complexity |
|-----------|-----|--------|------------|
| **P0** (CRITICAL) | DeleteProductFromPrestaShop job | Delete operations nie działają | LOW (5 min) |
| **P0** (CRITICAL) | ProductList sync status display | User widzi błędny status | LOW (5 min) |
| **P1** (HIGH) | ProductForm default data edit | Status nie zmienia się na pending | LOW (5 min) |
| **P2** (MEDIUM) | Grep verification dla innych miejsc | Wykrycie dodatkowych niespójności | MEDIUM (15 min) |
| **P3** (LOW) | Dokumentacja architektury sync | Zapobieganie przyszłym błędom | LOW (10 min) |

---

## ⚠️ DODATKOWE ODKRYCIA

### Pytanie: Po co dwie tabele?

**Hipoteza** (wymagana weryfikacja z dokumentacją projektu):

1. **`product_shop_data`** - dane CONTENT per sklep:
   - Shop-specific overrides (nazwa, opis, kategorie)
   - Shop-specific settings (is_published, category_mappings)
   - **POWINNO**: Przechowywać TYLKO content overrides

2. **`product_sync_status`** - dane SYNC tracking:
   - Sync status i sync operations tracking
   - PrestaShop external IDs
   - Retry counts, error messages, checksums
   - **POWINNO**: Być single source of truth dla sync operations

**Problem**: Obie tabele mają kolumnę `sync_status` → confusion!

**Możliwe rozwiązania**:
1. **Usunąć** `sync_status` z `product_shop_data` (breaking change)
2. **Synchronizować** `sync_status` między tabelami (dodatkowa complexity)
3. **Dokumentować** precyzyjne use cases dla każdej tabeli

**Rekomendacja**: Opcja 2 (sync między tabelami) jako najmniej inwazyjne rozwiązanie.

---

## 📈 EXPECTED RESULTS PO FIXACH

### Test 1: Delete Operation
**Przed**:
- Kliknięcie "Usuń ze sklepu" → tylko lokalne usunięcie
- Produkt pozostaje w PrestaShop
- Log: "Product not synced to PrestaShop yet"

**Po**:
- Kliknięcie "Usuń ze sklepu" → API call do PrestaShop
- Produkt faktycznie usunięty z PrestaShop
- Log: "Deleting product from PrestaShop" + "Product successfully deleted"

### Test 2: Sync Status Display
**Przed**:
- Edycja "Dane domyślne" → status pozostaje 🟢
- Różnice między PPM a PrestaShop nie wykrywane

**Po**:
- Edycja "Dane domyślne" → status zmienia się na 🕒 Pending
- Lista produktów pokazuje poprawny sync status
- User wie że sync jest potrzebny

### Test 3: Shop-Specific Edit
**Przed**:
- Edycja danych w zakładce sklepu → status nieznany

**Po**:
- Edycja danych w zakładce sklepu → status zmienia się na 🕒 Pending
- Trigger do re-synchronizacji

---

## 🚀 DEPLOYMENT PLAN

1. ✅ Backup bazy danych (tabele: product_shop_data, product_sync_status)
2. ✅ Deploy fixów (3 pliki: DeleteProductFromPrestaShop.php, product-list.blade.php, ProductForm.php)
3. ✅ Clear cache (view:clear, cache:clear, config:clear)
4. ✅ Test na produkcji:
   - Test delete operation (produkt który istnieje w PS)
   - Test sync status display (produkt z rozbieżnościami)
   - Test default data edit (zmiana kategorii)
5. ✅ Monitoring logów przez 24h
6. ✅ Update dokumentacji projektu

---

## 📝 NASTĘPNE KROKI (POST-FIX)

1. **Refactor**: Stworzyć SyncStatusManager service dla centralizacji sync operations
2. **Dokumentacja**: Zaktualizować CLAUDE.md z architekturą sync systemu
3. **Tests**: Dodać testy jednostkowe dla sync operations
4. **Migration**: Rozważyć consolidację sync_status do jednej tabeli (breaking change)

---

**Status**: 🔄 **READY FOR IMPLEMENTATION**
**Estimated Fix Time**: 30 minut (coding) + 15 minut (testing)
**Risk Level**: LOW (non-breaking changes, tylko naprawa błędów)

**Next Action**: Implementacja Etap 1-3 + deployment na produkcję

---

**Wygenerowane przez**: Claude Code - Main Assistant
**Dokumentacja**: Pełna analiza root cause + strategia naprawy
