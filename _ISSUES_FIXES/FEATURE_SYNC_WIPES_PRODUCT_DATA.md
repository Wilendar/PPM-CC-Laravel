# 🔥 KRYTYCZNY BUG: Feature Sync Wymazuje Dane Produktu

**Data wykrycia**: 2025-12-05
**Severity**: CRITICAL
**Status**: ✅ NAPRAWIONY
**Dotknięte produkty**: 8594 (usunięty), 9609 (wymazane dane)

---

## Objawy

1. Po eksporcie produktu z PPM do PrestaShop, produkt "znikał" z panelu admina
2. Produkt istniał w bazie PS ale z **pustymi polami**:
   - `reference` = EMPTY (SKU wymazane!)
   - `name` = EMPTY
   - `description_short` = EMPTY
   - `link_rewrite` = EMPTY
   - `id_category_default` = 0
3. PrestaShop admin panel nie wyświetla produktów bez `name` i `link_rewrite`

## Root Cause

**Lokalizacja**: `app/Services/PrestaShop/PrestaShopFeatureSyncService.php` linie 319-349

**Problem**: Metoda `syncProductFeatures()` używała "minimalnego" payloadu:

```php
// ❌ BŁĘDNY KOD (przed fix)
$minimalUpdateData = [
    'id' => $psProductId,
    'price' => $existingProductData['price'] ?? '0',
    'id_tax_rules_group' => $existingProductData['id_tax_rules_group'] ?? '1',
    'active' => $existingProductData['active'] ?? '1',
    'associations' => [
        'product_features' => $associations,
    ],
];
// Tylko categories i images z associations były kopiowane...

$this->client->updateProduct($psProductId, $minimalUpdateData);
```

**Dlaczego to był problem**:

PrestaShop Web Services API używa **PUT (Replace)**, NIE PATCH:
- PUT **ZASTĘPUJE** cały zasób nowymi danymi
- Pola nieobecne w request = ustawione na NULL/EMPTY
- W efekcie: `reference`, `name`, `description`, `link_rewrite` itd. = WYMAZANE

## Rozwiązanie

**Pattern**: GET-MODIFY-PUT (zachowaj wszystkie istniejące pola)

```php
// ✅ POPRAWNY KOD (po fix)

// Start with ALL existing product data to preserve everything
$updateData = $existingProductData;

// Only override the associations we want to update
$updateData['associations']['product_features'] = $associations;

// Remove read-only fields that PS doesn't accept in PUT
$readOnlyFields = ['manufacturer_name', 'quantity', 'type', ...];
foreach ($readOnlyFields as $field) {
    unset($updateData[$field]);
}

// Clean up multilang fields
$multilangFields = ['name', 'description', 'description_short', ...];
foreach ($multilangFields as $field) {
    if (isset($updateData[$field]['language'])) {
        $updateData[$field] = $updateData[$field]['language'];
    }
}

$this->client->updateProduct($psProductId, $updateData);
```

## Pliki Zmienione

- `app/Services/PrestaShop/PrestaShopFeatureSyncService.php` - Main fix

## Weryfikacja Fix

1. Feature sync nie wymazuje już danych produktu
2. Logi pokazują: `reference_preserved: XXX`, `name_preserved: YES`
3. Produkty pozostają widoczne w panelu admina PS po sync

## Lekcje

### ❌ NIE RÓB TEGO

```php
// "Minimal update" - NIEBEZPIECZNE z PrestaShop PUT!
$updateData = ['id' => $id, 'price' => $price, 'associations' => [...]];
$client->updateProduct($id, $updateData);
```

### ✅ ZAWSZE RÓB TO

```php
// GET-MODIFY-PUT - BEZPIECZNE
$existingData = $client->getProduct($id);
$updateData = $existingData['product'];
$updateData['associations']['product_features'] = $newFeatures;
// ... clean up read-only fields
$client->updateProduct($id, $updateData);
```

## Powiązana Dokumentacja

- `_DOCS/PRESTASHOP_PRODUCT_FIELDS_MAPPING.md` - Pełna mapa pól PS vs PPM
- `_DOCS/PRESTASHOP_REQUIRED_FIELDS.md` - Lista wymaganych pól

## Prevention

1. **Code Review**: Każdy `updateProduct()` call musi używać GET-MODIFY-PUT
2. **Testing**: Test sync na produkcie testowym PRZED mass sync
3. **Logging**: Zawsze loguj `reference_preserved` i `name_preserved`
4. **Monitoring**: Alert jeśli produkty mają puste `reference` lub `name`

---

**Fix deployed**: 2025-12-05
**Verified working**: Oczekuje na weryfikację użytkownika
