# RAPORT: PrestaShop Variant Attributes Sync Issue

**Data**: 2025-12-08
**Agent**: prestashop-api-expert
**Status**: 🔴 PROBLEM ZIDENTYFIKOWANY - Wymaga natychmiastowego fix'a

---

## 📋 STRESZCZENIE PROBLEMU

**Symptom**: Warianty (combinations) zostały utworzone w PrestaShop ale **bez atrybutów** - w kolumnie "Kombinacje" pokazuje "-" zamiast nazw atrybutów (np. "Kolor: Czerwony, Rozmiar: L").

**Root Cause**: W procesie "Wstaw z Dane domyślne" kopiowane są atrybuty z bazowego wariantu (`product_variants` → `shop_variants`) ale wartości są **NULL** zamiast rzeczywistych `value_id`.

**Impact**:
- ✅ Kombinacje tworzone w PrestaShop (ID: 13194, 13195)
- ❌ Bez atrybutów → niemożliwe rozróżnienie wariantów w sklepie
- ❌ Klienci nie mogą wybrać opcji produktu (brak rozwijanych list)

---

## 🔍 ANALIZA TECHNICZNA

### 1. Flow Danych

```
[ProductForm UI - Warianty Tab]
    ↓
"Wstaw z Dane domyślne" button
    ↓
ProductFormVariants::copyDefaultVariantsToShop()
    ↓
ShopVariant::create([
    operation_type: 'ADD',
    variant_data: {
        sku: "MR-MRF-E-V001-S1",
        name: "...",
        attributes: {"2": null}  ← ⚠️ PROBLEM!
    }
])
    ↓
Save → SyncShopVariantsToPrestaShopJob
    ↓
resolvePrestaShopAttributeIds([2 => null])
    ↓
WARNING: Invalid attribute_value_id {"attribute_type_id":2,"value":null}
    ↓
PrestaShop API createCombination() BEZ atrybutów
    ↓
setCombinationAttributes([]) → puste → "-" w PrestaShop
```

---

### 2. Evidence z Logów Produkcyjnych

**Logi z 2025-12-08 13:51:10:**

```log
[2025-12-08 13:51:10] production.WARNING: [SyncShopVariantsJob] Invalid attribute_value_id
{"attribute_type_id":2,"value":null}

[2025-12-08 13:51:10] production.WARNING: [SyncShopVariantsJob] Invalid attribute_value_id
{"attribute_type_id":1,"value":null}

[2025-12-08 13:51:10] production.INFO: [SyncShopVariantsJob] ADD successful
{"shop_variant_id":5,"prestashop_combination_id":13194}
```

**Dane z shop_variants (ID: 5):**

```json
{
    "variant_data": {
        "sku": "MR-MRF-E-V001-S1",
        "name": "Motorower elektryczny MRF eSTREET 2.5 - Wariant",
        "is_active": true,
        "is_default": false,
        "attributes": {
            "2": null  ← ⚠️ BRAK value_id!
        },
        "prices": null,
        "stock": null,
        "media_ids": [],
        "position": 1
    }
}
```

**Poprawna struktura w product_variants (variant_id: 55):**

```sql
SELECT * FROM variant_attributes WHERE variant_id = 55;
-- attribute_type_id: 2, value_id: 1 ✅

SELECT * FROM prestashop_attribute_value_mapping
WHERE attribute_value_id = 1 AND prestashop_shop_id = 1;
-- prestashop_attribute_id: 46 ✅ (Czerwony)
```

---

### 3. Analiza Kodu - Gdzie Jest Problem?

**FILE: `app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php`**

**Metoda: `copyDefaultVariantsToShop()` (linia ~300-400)**

**PROBLEM:** Kod kopiuje atrybuty z relacji `baseVariant->attributes` ale nie ekstraktuje poprawnie `value_id`.

**Przypuszczalny kod (nie sprawdzony - plik nie był czytany):**

```php
// ❌ NIEPRAWIDŁOWE (prawdopodobnie obecna implementacja):
$attributes = [];
foreach ($baseVariant->attributes as $attr) {
    $attributes[$attr->attribute_type_id] = null; // ← Brak $attr->value_id!
}

// ✅ PRAWIDŁOWE (oczekiwane):
$attributes = [];
foreach ($baseVariant->attributes as $attr) {
    $attributes[$attr->attribute_type_id] = $attr->value_id;
}
```

**FILE: `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php`**

**Metoda: `resolvePrestaShopAttributeIds()` (linia 407-460)**

Kod jest **PRAWIDŁOWY** - poprawnie wykrywa `null` i loguje WARNING:

```php
// Linia 437-442
if ($attributeValueId <= 0) {
    Log::warning('[SyncShopVariantsJob] Invalid attribute_value_id', [
        'attribute_type_id' => $key,
        'value' => $value,
    ]);
    continue;
}
```

**Więc problem NIE JEST w SyncShopVariantsToPrestaShopJob** - ten Job działa prawidłowo!

Problem jest **WCZEŚNIEJ** - podczas tworzenia `shop_variants` w UI (ProductFormVariants).

---

## 🔧 ROZWIĄZANIE

### Root Cause Location

**FILE: `app/DTOs/ShopVariantOverride.php`**

**METHOD: `fromDefaultVariant()` - linia 50**

**PROBLEM:** Kod używa nieistniejącej właściwości `$attr->attribute_value_id` zamiast `$attr->value_id`

### ACTUAL BUG (CONFIRMED):

**FILE**: `app/DTOs/ShopVariantOverride.php`

**LINIA 49-51**:

```php
// ❌ BŁĄD - attribute_value_id NIE ISTNIEJE w modelu VariantAttribute!
attributes: $variant->attributes->mapWithKeys(
    fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]
)->toArray(),
```

**Model VariantAttribute** (app/Models/VariantAttribute.php) ma kolumnę `value_id` (nie `attribute_value_id`):

```php
protected $fillable = [
    'variant_id',
    'attribute_type_id',
    'value_id', // ✅ PRAWDZIWA NAZWA
    'color_hex',
];
```

**SKUTEK:** PHP zwraca `null` dla nieistniejącej właściwości `attribute_value_id` → attributes: {"2": null}

### FIXED CODE:

```php
// ✅ FIX - użyj value_id zamiast attribute_value_id
attributes: $variant->attributes->mapWithKeys(
    fn($attr) => [$attr->attribute_type_id => $attr->value_id]
)->toArray(),
```

---

## ✅ VERIFICATION CHECKLIST

Po wdrożeniu fix'a, sprawdź:

1. **UI Test:**
   - [ ] Otwórz produkt z wariantami w PPM
   - [ ] Kliknij "Wstaw z Dane domyślne" na zakładce "B2B Test DEV"
   - [ ] "Zapisz zmiany"
   - [ ] Sprawdź logi: `grep 'Invalid attribute_value_id' storage/logs/laravel.log` → **BRAK WARNING**

2. **Database Test:**
   ```sql
   SELECT id, JSON_EXTRACT(variant_data, '$.attributes')
   FROM shop_variants
   WHERE shop_id = 1 AND product_id = 11148
   ORDER BY id DESC LIMIT 5;

   -- Expected: {"1": 2, "2": 5} zamiast {"1": null, "2": null}
   ```

3. **PrestaShop Verification:**
   - [ ] Otwórz produkt w PrestaShop admin: `/admin-dev/index.php?controller=AdminProducts&id_product=XXXX`
   - [ ] Zakładka "Kombinacje"
   - [ ] Kolumna "Kombinacje" pokazuje: "Kolor: Czerwony, Rozmiar: L" (nie "-")

4. **Sync Logs:**
   ```bash
   tail -50 storage/logs/laravel.log | grep 'Resolved attribute IDs'
   # Expected: [SyncShopVariantsJob] Resolved attribute IDs for ADD
   # {"ppm_attributes":{...},"prestashop_attribute_ids":[46,52]}
   ```

---

## 📁 PLIKI DO FIX'A

### PRIMARY FIX LOCATION (CONFIRMED):

- **`app/DTOs/ShopVariantOverride.php`** ✅ ZNALEZIONY BUG!
  - **Linia 50**: `fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]`
  - **Fix**: Zamień `attribute_value_id` → `value_id`
  - **Zmiana**: `fn($attr) => [$attr->attribute_type_id => $attr->value_id]`
  - **Powód**: Model `VariantAttribute` ma kolumnę `value_id`, nie `attribute_value_id`

### ONE-LINE FIX:

```bash
# Otwórz plik
code app/DTOs/ShopVariantOverride.php

# Linia 50 - PRZED:
fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]

# Linia 50 - PO:
fn($attr) => [$attr->attribute_type_id => $attr->value_id]
```

### VERIFICATION FILES (NO CHANGES NEEDED):

- ✅ `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php` - działa prawidłowo
- ✅ `app/Services/PrestaShop/PrestaShop8Client.php` - działa prawidłowo
- ✅ `app/Models/ShopVariant.php` - struktura prawidłowa
- ✅ `app/Models/VariantAttribute.php` - kolumna to `value_id` ✅

---

## 🔍 RELATED ISSUES

### Podobne Symptomy (do sprawdzenia po fix'ie):

1. **OVERRIDE operations** - czy też tracą atrybuty?
   ```sql
   SELECT * FROM shop_variants
   WHERE operation_type = 'OVERRIDE'
   AND JSON_EXTRACT(variant_data, '$.attributes') LIKE '%null%';
   ```

2. **Manual variant creation** - czy UI pozwala wybrać atrybuty poprawnie?

3. **Pull from PrestaShop** - czy importowane warianty mają poprawne atrybuty?

---

## 📊 PRIORITY & IMPACT

**Priority**: 🔴 **CRITICAL** - blokuje sprzedaż produktów z wariantami

**Impact**:
- **Users Affected**: Wszyscy użytkownicy synchronizujący warianty do PrestaShop
- **Shops Affected**: B2B Test DEV (shop_id: 1) + potencjalnie wszystkie sklepy
- **Products Affected**: Wszystkie produkty z wariantami

**Timeline**:
- **Discovered**: 2025-12-08
- **Recommended Fix Time**: < 1 godzina (prosta zmiana w jednej linii kodu)
- **Testing Time**: ~30 minut (deploy + manual test + PrestaShop verification)

---

## 🚀 NASTĘPNE KROKI

### 1. IMMEDIATE FIX (< 5 minut):

```bash
# Edytuj plik
nano app/DTOs/ShopVariantOverride.php

# Linia 50 - zamień:
attribute_value_id → value_id
```

**EXACT CHANGE:**

```diff
- fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]
+ fn($attr) => [$attr->attribute_type_id => $attr->value_id]
```

### 2. DEPLOY (< 2 minuty):

```powershell
# Upload DTO file
pscp -i $HostidoKey -P 64321 "app/DTOs/ShopVariantOverride.php" host379076@...:domains/.../app/DTOs/

# Clear cache
plink ... -batch "cd domains/.../public_html && php artisan cache:clear && php artisan config:clear"
```

### 3. TEST (< 5 minut):

1. Otwórz produkt 11148 w PPM
2. Zakładka "Warianty" → "B2B Test DEV"
3. Usuń istniejące shop variants (mają błędne attributes)
4. "Wstaw z Dane domyślne" → sprawdź czy attributes mają value_id (nie null)
5. "Zapisz zmiany" → sprawdź logi (brak WARNING "Invalid attribute_value_id")

### 4. VERIFY PrestaShop:

```bash
# Check if combinations have attributes now
# Open: https://dev.mpptrade.pl/admin-dev/index.php?controller=AdminProducts&id_product=XXX
# Tab: Kombinacje
# Column: Kombinacje → should show "Kolor: Czerwony" (not "-")
```

### 5. CLEANUP (optional):

```sql
-- Remove broken shop_variants (user will recreate via "Wstaw z Dane domyślne")
DELETE FROM shop_variants
WHERE JSON_EXTRACT(variant_data, '$.attributes') LIKE '%null%';
```

**ESTIMATED TOTAL TIME:** 15 minut (fix + deploy + test)

---

## 📚 DOKUMENTACJA

**Related Docs:**
- `_DOCS/PRESTASHOP_PRODUCT_FIELDS_MAPPING.md` - attribute mapping structure
- `ETAP_05c: Per-Shop Variants System` - shop_variants architecture
- `prestashop_attribute_value_mapping` table - PPM ↔ PrestaShop mapping

**PrestaShop API Endpoint:**
- `PUT /api/combinations/{id}` - update combination attributes
- Field: `associations.product_option_values` (array of `{id: prestashop_attribute_id}`)

---

**Agent**: prestashop-api-expert
**Report Generated**: 2025-12-08 15:30 UTC
