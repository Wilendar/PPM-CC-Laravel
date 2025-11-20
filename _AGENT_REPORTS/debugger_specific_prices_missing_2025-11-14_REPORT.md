# RAPORT PRACY AGENTA: debugger

**Data**: 2025-11-14 11:30
**Agent**: debugger
**Zadanie**: Diagnoza braku specific_prices podczas synchronizacji produktów do PrestaShop

## PODSUMOWANIE

**ROOT CAUSE ZIDENTYFIKOWANY**: ✅ Brak mapowań grup cenowych (Price Group Mappings) dla sklepu "B2B Test DEV" (ID: 1)

**Wpływ**:
- 100% cen produktów pomijanych podczas synchronizacji (skipped=6, created=0)
- Produkty w PrestaShop mają TYLKO bazową cenę, brak cen specjalnych dla grup klientów
- Problem dotyczy WSZYSTKICH produktów synchronizowanych do tego sklepu

## ANALIZA GŁÓWNEJ PRZYCZYNY

### Evidence z Logów Produkcyjnych (2025-11-14 09:39:13)

```
[PRICE EXPORT] Starting price export to PrestaShop
    {"product_id":11033,"sku":"PB-KAYO-E-KMB","shop_id":1,"prestashop_product_id":1830}
[PRICE EXPORT] PPM prices fetched {"count":6}
[PRICE EXPORT] Existing PrestaShop specific_prices fetched {"count":0}

Price group mapping not found {"price_group_id":1,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":1,"price_group_code":"retail"}
Price group mapping not found {"price_group_id":2,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":2,"price_group_code":"dealer_standard"}
Price group mapping not found {"price_group_id":3,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":3,"price_group_code":"dealer_premium"}
Price group mapping not found {"price_group_id":5,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":5,"price_group_code":"workshop_premium"}
Price group mapping not found {"price_group_id":6,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":6,"price_group_code":"school_drop"}
Price group mapping not found {"price_group_id":7,"shop_id":1}
[PRICE EXPORT] Price group not mapped, skipping {"price_group_id":7,"price_group_code":"employee"}

[PRICE EXPORT] Price export completed {"product_id":11033,"created":0,"updated":0,"deleted":0,"skipped":6}
```

### Mechanizm Problemu

**Kod**: `app/Services/PrestaShop/PrestaShopPriceExporter.php` linie 192-209

```php
// Map PPM price group to PrestaShop customer group
$prestashopGroupId = $this->priceGroupMapper->mapToPrestaShop(
    $productPrice->price_group_id,
    $shop
);

if (!$prestashopGroupId) {
    Log::debug('[PRICE EXPORT] Price group not mapped, skipping', [
        'price_group_id' => $productPrice->price_group_id,
        'price_group_code' => $productPrice->priceGroup->code ?? 'N/A',
    ]);

    return [
        'action' => 'skipped',
        'reason' => 'price_group_not_mapped',
        'price_group_id' => $productPrice->price_group_id,
    ];
}
```

**Co się dzieje:**
1. `PriceGroupMapper::mapToPrestaShop()` sprawdza tabelę `shop_mappings`
2. Brak rekordów dla shop_id=1 i mapping_type='price_group'
3. Zwraca `null` dla WSZYSTKICH grup cenowych
4. WSZYSTKIE ceny są pomijane (action: 'skipped')
5. PrestaShop otrzymuje produkt BEZ specific_prices

### Stan Bazy Danych

**Shop**: B2B Test DEV (ID: 1)

**Price Group Mappings**: **0** (ZERO!)

**Dostępne grupy cenowe w PPM:**
- ID: 1, Code: retail, Name: Detaliczna
- ID: 2, Code: dealer_standard, Name: Dealer Standard
- ID: 3, Code: dealer_premium, Name: Dealer Premium
- ID: 4, Code: workshop_std, Name: Warsztat Standard
- ID: 5, Code: workshop_premium, Name: Warsztat Premium
- ID: 6, Code: school_drop, Name: Szkółka-Komis-Drop
- ID: 7, Code: employee, Name: Pracownik

**Brakuje**: Mapowania PPM price_group_id → PrestaShop customer group ID w tabeli `shop_mappings`

## ROZWIĄZANIE

### Krótkoterminowe (Manual Fix)

**Krok 1**: Administrator musi skonfigurować mapowania grup cenowych dla sklepu

Opcje:
1. **UI (preferowane)**: Admin → Shops → Edit Shop "B2B Test DEV" → Price Group Mappings
2. **Tinker (awaryjne)**:
   ```php
   $shop = PrestaShopShop::find(1);
   $mapper = app(PriceGroupMapper::class);

   // Przykładowe mapowanie (należy zweryfikować ID grup w PrestaShop)
   $mapper->createMapping(1, $shop, 3, 'Customer'); // Detaliczna → Customer
   $mapper->createMapping(2, $shop, 4, 'Dealer');   // Dealer Std → Dealer
   // ... itd.
   ```

**Krok 2**: Re-sync produktów aby utworzyć specific_prices

```bash
php artisan queue:work --queue=prestashop_sync --once
```

### Długoterminowe (System Improvements)

**Propozycje ulepszeń:**

1. **Validation w AddShop/EditShop Livewire:**
   - Wymagaj przynajmniej JEDNEGO mapowania grupy cenowej przed aktywacją sklepu
   - Warning jeśli sklep aktywny ale brak mapowań

2. **UI Warning w ShopManager:**
   - Badge "⚠️ No price mappings" dla sklepów bez konfiguracji
   - Quick action "Configure Mappings" button

3. **Auto-sync po dodaniu mapowań:**
   - Hook: Po utworzeniu pierwszego mapowania → zapytaj czy re-sync produkty
   - Lub: Automatyczny dispatch BulkSyncProducts job

4. **Better Logging:**
   - Zmień `Log::debug()` na `Log::warning()` dla "price_group_not_mapped"
   - Dodaj summary: "Price export completed with 0 created due to no mappings configured"

5. **Onboarding Checklist:**
   - Po dodaniu sklepu: Show checklist (✅ API connected, ❌ Price mappings, ❌ Category mappings)

## WERYFIKACJA ROZWIĄZANIA

**Test Plan:**

1. ✅ Skonfiguruj mapowania grup cenowych dla sklepu ID=1
2. ✅ Re-sync produkt 11033 (PB-KAYO-E-KMB)
3. ✅ Sprawdź logi: `[PRICE EXPORT] Created specific_price`
4. ✅ Weryfikuj bazę PrestaShop: `SELECT * FROM ps_specific_price WHERE id_product=1830`
5. ✅ Sprawdź UI PrestaShop: Produkt → Prices → Customer group prices

**Oczekiwane wyniki:**
- created > 0 (liczba zmapowanych grup)
- skipped = 0 (lub tylko unmapped groups)
- PrestaShop ps_specific_price zawiera rekordy dla każdej zmapowanej grupy

## IMPACT ASSESSMENT

**Severity**: 🔴 CRITICAL (funkcjonalność nie działa)

**Scope**: WSZYSTKIE sklepy bez skonfigurowanych mapowań grup cenowych

**Produkty dotknięte**:
- PB-KAYO-E-KMB #11033 (PrestaShop #1830)
- Q-KAYO-EA70 #11034 (PrestaShop #1831)
- Potencjalnie wszystkie produkty synchronizowane do sklepu ID=1

**Workaround**: Brak - wymagane mapowania do działania systemu cen grupowych

## LESSONS LEARNED

1. **Missing Configuration Detection**: System powinien wykrywać brak krytycznej konfiguracji PRZED synchronizacją
2. **Graceful Degradation**: Rozważyć fallback do default price group jeśli brak mapowań
3. **User Education**: Dokumentacja/tutorial konfiguracji sklepu (required steps)
4. **Validation**: Enforce minimum configuration przed aktywacją funkcjonalności

## PLIKI ANALIZOWANE

- ✅ `app/Services/PrestaShop/PrestaShopPriceExporter.php` - Price export logic
- ✅ `app/Services/PrestaShop/PriceGroupMapper.php` - Mapping service
- ✅ `app/Services/PrestaShop/Sync/ProductSyncStrategy.php` - Sync workflow
- ✅ Production logs: `storage/logs/laravel.log` (2025-11-14 09:39:13)

## NARZĘDZIA DIAGNOSTYCZNE

Utworzone:
- ✅ `_TEMP/check_price_mappings_prod.ps1` - Production diagnostic script
- ✅ `_TEMP/diagnose_specific_prices_missing.php` - Local diagnostic (requires DB)

## NASTĘPNE KROKI

**Dla Użytkownika:**

📋 **INSTRUKCJA KROK PO KROKU**: [`_TEMP/SOLUTION_configure_price_mappings.md`](_TEMP/SOLUTION_configure_price_mappings.md)

**Quick Start:**
1. Admin → Shops → Edit "B2B Test DEV" → Step 4: Price Group Mapping
2. Kliknij "Pobierz grupy cenowe z PrestaShop"
3. Zmapuj grupy PPM → PrestaShop (minimum 1 mapowanie!)
4. Zapisz
5. Re-sync produkty: Admin → Shops → Sync Products

**Alternatywa (Tinker):**
```php
$shop = PrestaShopShop::find(1);
$mapper = app(PriceGroupMapper::class);
$mapper->createMapping(1, $shop, 3, 'Customer'); // Detaliczna → Customer
// Re-sync: SyncProductToPrestaShop::dispatch(Product::find(11033), $shop);
```

**Dla Zespołu Rozwojowego:**
1. Rozważyć implementację validation/warnings przed synchronizacją
2. Dodać onboarding checklist dla nowych sklepów
3. Poprawić komunikaty error/warning (debug → warning level)
4. Dokumentacja: "How to configure price group mappings"

## STATUS

✅ **ROOT CAUSE IDENTIFIED**: Brak mapowań grup cenowych
✅ **SOLUTION PROVIDED**: Konfiguracja mapowań + re-sync
⏳ **AWAITING**: User action (configure mappings)

---

**Generated by**: debugger agent
**Date**: 2025-11-14
**Issue Tracker**: PRESTASHOP_PRICE_SYNC_MISSING_MAPPINGS
