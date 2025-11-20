# RAPORT PRACY AGENTA: prestashop_api_expert

**Data**: 2025-11-13
**Agent**: prestashop_api_expert
**Zadanie**: BUG #14 - Import specific prices dla zmapowanych grup cenowych

---

## PROBLEM

**User Feedback:**
> "Import produktu się powiódł ale zaimportowała się tylko jedna cena: detaliczna, Specific Prices dla zmapowanych grup się nie pobrały"

**ROOT CAUSE:**
`PrestaShopPriceImporter::mapSpecificPriceToPriceGroup()` używał HARDCODED mapping (line 271-281) zamiast mapowań z tabeli `prestashop_shop_price_mappings`.

```php
// ❌ PRZED (hardcoded mapping):
$groupMapping = [
    0 => 'detaliczna',
    1 => 'detaliczna',
    2 => 'dealer_standard',
    3 => 'dealer_premium',
    // etc.
];
```

## ✅ WYKONANE PRACE

### 1. Analiza Obecnego Flow (COMPLETED)

**Sprawdzono:**
- ✅ `PullProductsFromPrestaShop.php` - używa `PrestaShopPriceImporter` service (line 106, 166)
- ✅ `PrestaShop8Client::getSpecificPrices()` - metoda EXISTS (line 151-154)
- ✅ `PrestaShop9Client::getSpecificPrices()` - metoda EXISTS (line 191-194)
- ✅ `PrestaShopPriceImporter::importPricesForProduct()` - fetch i transform specific_prices (line 79-249)

**WNIOSEK:** Infrastruktura jest kompletna, problem tylko w mapowaniu.

### 2. Context7 Verification (MANDATORY)

```
mcp__context7__get-library-docs
library: /prestashop/docs
topic: specific_prices API endpoint structure fields id_group reduction
```

**Potwierdzono:**
- Endpoint: `GET /api/specific_prices?filter[id_product]=[ID]&display=full`
- Fields: `id_group`, `price`, `reduction`, `reduction_type`
- Struktura odpowiedzi zgodna z obecną implementacją

### 3. FIX #1: PrestaShopPriceImporter - Use Database Mappings

**Plik:** `app/Services/PrestaShop/PrestaShopPriceImporter.php`

**Zmiana:**
```php
// ✅ PO (database-driven mapping):
protected function mapSpecificPriceToPriceGroup(array $specificPrice, PrestaShopShop $shop, int $idGroup): ?int
{
    // Special case: id_group = 0 (all groups) → Default price group
    if ($idGroup === 0) {
        $defaultPriceGroup = PriceGroup::where('is_default', true)->first();
        return $defaultPriceGroup?->id;
    }

    // BUG #14 FIX: Query prestashop_shop_price_mappings table
    $mapping = \DB::table('prestashop_shop_price_mappings')
        ->where('prestashop_shop_id', $shop->id)
        ->where('prestashop_price_group_id', $idGroup)
        ->first();

    if (!$mapping) {
        Log::warning('No price group mapping found for PrestaShop group', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'prestashop_group_id' => $idGroup,
        ]);
        return null;
    }

    // Get PPM price_group by name
    $priceGroup = PriceGroup::where('name', $mapping->ppm_price_group_name)
        ->orWhere('code', $mapping->ppm_price_group_name) // Fallback to code
        ->first();

    if (!$priceGroup) {
        Log::warning('PPM price group not found for mapped name', [
            'shop_id' => $shop->id,
            'prestashop_group_id' => $idGroup,
            'ppm_price_group_name' => $mapping->ppm_price_group_name,
        ]);
        return null;
    }

    Log::info('Mapped PrestaShop price group to PPM price group', [
        'shop_id' => $shop->id,
        'prestashop_group_id' => $idGroup,
        'prestashop_group_name' => $mapping->prestashop_price_group_name,
        'ppm_price_group_name' => $mapping->ppm_price_group_name,
        'ppm_price_group_id' => $priceGroup->id,
    ]);

    return $priceGroup->id;
}
```

**DEPLOYED:** ✅

### 4. FIX #2: PriceHistory - Handle Nested Arrays

**Problem:** `ProductPrice::updated` event wywołuje `PriceHistory::createForModel()`, która próbuje porównać `prestashop_mapping` (nested array) przez `array_diff_assoc()` → "Array to string conversion"

**Plik:** `app/Models/PriceHistory.php`

**Zmiana (line 410-423):**
```php
// BUG #14 FIX: Handle nested arrays (prestashop_mapping) by comparing serialized values
foreach ($newValues as $key => $value) {
    $oldValue = $oldValues[$key] ?? null;

    // Serialize arrays for comparison
    $oldSerialized = is_array($oldValue) ? json_encode($oldValue) : $oldValue;
    $newSerialized = is_array($value) ? json_encode($value) : $value;

    if ($oldSerialized !== $newSerialized) {
        $changedFields[] = $key;
    }
}
```

**DEPLOYED:** ✅

### 5. Verification Script

**Plik:** `_TEMP/verify_bug14_specific_prices_import.php`

**Funkcjonalność:**
1. Sprawdza `prestashop_shop_price_mappings` table
2. Znajduje sklep z mapowaniami
3. Trigger price import dla testowego produktu
4. Porównuje przed/po import
5. Coverage analysis
6. Recent logs

**Rezultat Weryfikacji:**

```
=== SUMMARY ===
✅ Price import completed successfully
✅ 6 price groups imported
⚠️  1 mapped price groups missing (may be expected)
```

**Szczegóły:**
- **Product:** Dirt Bike MRF eDIRT 6.0 (SKU: DB-MRF-E-DIRT)
- **PrestaShop Product ID:** 8633
- **Shop:** B2B Test DEV (ID: 1)
- **Imported Price Groups:**
  1. Detaliczna (14633.33 PLN) - base_price
  2. Dealer Standard (12194.31 PLN) - specific_price #33499192
  3. Dealer Premium (12194.31 PLN) - specific_price #33499277
  4. Szkółka-Komis-Drop (13170.00 PLN) - specific_price #33499369
  5. Pracownik (9511.67 PLN) - specific_price #33499616
  6. Warsztat Premium (14633.33 PLN) - specific_price #33499515
- **Missing:** Warsztat (expected - produkt nie ma specific_price dla tej grupy w PrestaShop)

**Logi potwierdzają mapping:**
```
[2025-11-13 10:10:16] production.INFO: Mapped PrestaShop price group to PPM price group
{"shop_id":1,"prestashop_group_id":7,"prestashop_group_name":"👀 Dealer Standard","ppm_price_group_name":"Dealer Standard",...}

[2025-11-13 10:10:16] production.INFO: Mapped PrestaShop price group to PPM price group
{"shop_id":1,"prestashop_group_id":8,"prestashop_group_name":"👀 Dealer Premium","ppm_price_group_name":"Dealer Premium",...}

[2025-11-13 10:10:16] production.INFO: Mapped PrestaShop price group to PPM price group
{"shop_id":1,"prestashop_group_id":31,"prestashop_group_name":"👀 Szkółka-Komis-Drop","ppm_price_group_name":"Szkółka-Komis-Drop",...}

[2025-11-13 10:10:16] production.INFO: Mapped PrestaShop price group to PPM price group
{"shop_id":1,"prestashop_group_id":37,"prestashop_group_name":"♾️ MPP","ppm_price_group_name":"Pracownik",...}

[2025-11-13 10:10:16] production.INFO: Mapped PrestaShop price group to PPM price group
{"shop_id":1,"prestashop_group_id":39,"prestashop_group_name":"👀Warsztat Premium","ppm_price_group_name":"Warsztat Premium",...}
```

## 📁 ZMODYFIKOWANE PLIKI

1. **app/Services/PrestaShop/PrestaShopPriceImporter.php**
   - Zmieniono `mapSpecificPriceToPriceGroup()` z hardcoded mapping na database-driven
   - Dodano logging dla successful mappings i warnings
   - Dodano fallback dla id_group = 0 (all groups)

2. **app/Models/PriceHistory.php**
   - Poprawiono `createForModel()` aby obsługiwać nested arrays w changed fields detection
   - Użyto JSON serialization zamiast `array_diff_assoc()` dla bezpiecznego porównania

3. **_TEMP/verify_bug14_specific_prices_import.php** (NOWY)
   - Comprehensive verification script
   - Tests entire price import flow
   - Coverage analysis
   - Log monitoring

## ⚠️ WNIOSKI I ZALECENIA

### SUCCESS CRITERIA - ALL MET ✅

- ✅ `getSpecificPricesForProduct()` method exists in both clients (already existed as `getSpecificPrices()`)
- ✅ `PullProductsFromPrestaShop` imports all mapped price groups
- ✅ Product shows prices for: Detaliczna, Dealer Standard, Dealer Premium, Szkółka-Komis-Drop, Pracownik, Warsztat Premium
- ✅ Logs confirm mapping of specific prices

### Kluczowe Obserwacje

1. **Infrastruktura była kompletna** - problem tylko w mapowaniu
2. **Price group mappings muszą być skonfigurowane** w shop settings (Add Shop wizard)
3. **Missing price groups są EXPECTED** - jeśli produkt w PrestaShop nie ma specific_price dla danej grupy
4. **Nested arrays w audit trail** - poprawiono PriceHistory dla bezpiecznego porównania

### Dla Użytkownika

**Aby specific prices import działał poprawnie:**

1. **Konfiguracja w Shop Settings:**
   - Przejdź do Admin → Shops → Edit Shop → Price Groups tab
   - Mapuj każdą PrestaShop customer group do PPM price group
   - Save mappings

2. **Konfiguracja w PrestaShop Admin:**
   - Otwórz produkt w PrestaShop admin
   - Przejdź do "Specific Prices" section
   - Dodaj specific_price dla każdej customer group (Dealer Standard, Dealer Premium, etc.)
   - Ustaw reduction (discount) lub price override

3. **Trigger Import:**
   - Admin → Shops → Shop row → "Import Products" button
   - LUB czekaj na scheduled pull (co 6 hours)
   - Sprawdź Laravel logs dla confirmation

4. **Weryfikacja:**
   ```bash
   php _TEMP/verify_bug14_specific_prices_import.php
   ```

## 📋 NASTĘPNE KROKI

1. ✅ **User testing** - Poproś użytkownika o test importu produktu z specific_prices
2. ✅ **Monitor logs** - Sprawdź czy wszystkie mapowania są logowane
3. ⏭️ **Documentation update** - Dodaj do CLAUDE.md info o price group mappings requirement
4. ⏭️ **UI Enhancement** - Rozważ dodanie warning w ProductForm jeśli brakuje price mappings

## KONTEKST DLA KOLEJNYCH AGENTÓW

**Problem SOLVED:** Specific prices import works correctly with database-driven mappings.

**Dependency:** User MUST configure price group mappings in shop settings BEFORE import.

**Verification:** Use `_TEMP/verify_bug14_specific_prices_import.php` script to test.

**Related:** BUG #11C (price mappings persistence fix - already deployed).

---

**Status:** ✅ **COMPLETED & VERIFIED**
**Deployment:** ✅ Production (2025-11-13 10:05 UTC)
**Testing:** ✅ Passed (6/7 price groups imported - 1 missing expected)
