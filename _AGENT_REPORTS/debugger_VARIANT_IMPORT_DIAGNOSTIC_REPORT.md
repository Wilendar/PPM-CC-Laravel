# RAPORT DIAGNOSTYCZNY: Import wariantow z PrestaShop

**Data**: 2025-12-10
**Agent**: debugger
**Produkt**: MRF13-68-003 (ID: 11164)
**Sklep**: B2B Test DEV (shop_id: 1)

---

## PODSUMOWANIE PROBLEMOW

Import 33 wariantow zakonczyl sie bledem - ZERO wariantow zostalo poprawnie zaimportowanych z danymi (atrybuty, ceny, zdjecia).

**Stan aktualny produktu 11164:**
- ProductVariants: 33 rekordow (same podstawowe dane)
- VariantAttributes: 0 rekordow (ERROR!)
- VariantPrices: 0 rekordow (ERROR!)
- VariantImages: 0 rekordow (BRAK IMPORTU!)

---

## PROBLEM #1: Nieistniejaca kolumna `color_hex` w tabeli `variant_attributes`

### Blad z logow:
```
[VARIANT IMPORT] Could not import attribute {"variant_id":87,"option_value_id":57,"error":"SQLSTATE[42S22]: Column not found: 1054 Unknown column 'color_hex'"}
```

### Root Cause:
Kod w `PrestaShopImportService.php` (linia 2064-2069) probuje zapisac `color_hex` do tabeli `variant_attributes`:
```php
\App\Models\VariantAttribute::create([
    'variant_id' => $variant->id,
    'attribute_type_id' => $attributeType->id,
    'value_id' => $attributeValue->id,
    'color_hex' => $colorHex,  // <-- BLAD! Kolumna nie istnieje!
]);
```

### Faktyczna struktura tabeli `variant_attributes` (z produkcji):
```
id, variant_id, attribute_type_id, value_id, created_at, updated_at
```

**Kolumna `color_hex` NIE ISTNIEJE w tej tabeli!**

### Gdzie jest `color_hex`?
Kolumna `color_hex` jest w tabeli `attribute_values` (co jest prawidlowe - kolor jest wlasciwoscia wartosci atrybutu, nie polaczenia variant-atrybut).

### Rozwiazanie:
Usunac `'color_hex' => $colorHex` z create() w `importVariantAttributes()` - kolor jest juz zapisywany w `findOrCreateAttributeValue()` do tabeli `attribute_values`.

---

## PROBLEM #2: Nieprawidlowe nazwy kolumn w tabeli `variant_prices`

### Blad z logow:
```
[VARIANT IMPORT] Error importing variant {"product_id":11164,"combination_id":9342,"error":"SQLSTATE[HY000]: General error: 1364 Field 'price' doesn't have a default value"}
```

### Root Cause:
Kod w `PrestaShopImportService.php` (linia 2214-2224) probuje zapisac nieprawidlowe kolumny:
```php
\App\Models\VariantPrice::updateOrCreate(
    [
        'variant_id' => $variant->id,
        'price_group_id' => $defaultPriceGroup->id,
    ],
    [
        'price_net' => $variantPriceNet,     // <-- BLAD! Kolumna nie istnieje!
        'price_gross' => $variantPriceNet * ...,  // <-- BLAD! Kolumna nie istnieje!
        'currency' => 'PLN',                 // <-- BLAD! Kolumna nie istnieje!
    ]
);
```

### Faktyczna struktura tabeli `variant_prices` (z produkcji):
```
id, variant_id, price_group_id, price, price_special, special_from, special_to, created_at, updated_at
```

### Rozwiazanie:
Zmienic nazwy kolumn na prawidlowe:
```php
\App\Models\VariantPrice::updateOrCreate(
    [
        'variant_id' => $variant->id,
        'price_group_id' => $defaultPriceGroup->id,
    ],
    [
        'price' => $variantPriceNet,      // <-- POPRAWNE
        'price_special' => null,           // <-- POPRAWNE (opcjonalnie)
    ]
);
```

---

## PROBLEM #3: Brak importu zdjec wariantow (VariantImages)

### Opis:
Metoda `importSingleVariant()` NIE wywoluje logiki importu zdjec dla wariantow.

### Obecny kod (PrestaShopImportService.php linie 1921-1928):
```php
// Import variant attributes (color, size, etc.)
$this->importVariantAttributes($variant, $combination, $shop, $client);

// Import variant price (price modifier)
$this->importVariantPrice($variant, $combination, $product);

// Import variant stock
$this->importVariantStock($variant, $combination);

// BRAK: Import variant images!
```

### Dane dostepne w API PrestaShop:
Kombinacje PrestaShop zawieraja `associations.images` z lista ID zdjec przypisanych do wariantu.

### Istniejaca logika w ShopVariantService:
`extractCombinationImages()` - pobiera zdjecia z kombinacji, ale NIE jest wywolywana podczas importu.

### Rozwiazanie:
Dodac metode `importVariantImages()` i wywolac ja w `importSingleVariant()`:
```php
// Import variant images
$this->importVariantImages($variant, $combination, $shop, $client);
```

---

## PLIKI DO MODYFIKACJI

### 1. `app/Services/PrestaShop/PrestaShopImportService.php`

**Lokalizacja #1** - linia ~2068 (`importVariantAttributes`):
```php
// BEFORE:
\App\Models\VariantAttribute::create([
    'variant_id' => $variant->id,
    'attribute_type_id' => $attributeType->id,
    'value_id' => $attributeValue->id,
    'color_hex' => $colorHex,  // USUNAC!
]);

// AFTER:
\App\Models\VariantAttribute::create([
    'variant_id' => $variant->id,
    'attribute_type_id' => $attributeType->id,
    'value_id' => $attributeValue->id,
    // color_hex jest juz w attribute_values, nie tutaj
]);
```

**Lokalizacja #2** - linia ~2214-2224 (`importVariantPrice`):
```php
// BEFORE:
\App\Models\VariantPrice::updateOrCreate(
    [
        'variant_id' => $variant->id,
        'price_group_id' => $defaultPriceGroup->id,
    ],
    [
        'price_net' => $variantPriceNet,
        'price_gross' => $variantPriceNet * (1 + ($product->tax_rate / 100)),
        'currency' => 'PLN',
    ]
);

// AFTER:
\App\Models\VariantPrice::updateOrCreate(
    [
        'variant_id' => $variant->id,
        'price_group_id' => $defaultPriceGroup->id,
    ],
    [
        'price' => $variantPriceNet,
        'price_special' => null,
    ]
);
```

**Lokalizacja #3** - po linii ~1928 (`importSingleVariant`):
```php
// Import variant stock
$this->importVariantStock($variant, $combination);

// DODAC: Import variant images
$this->importVariantImages($variant, $combination, $shop, $client);
```

**Lokalizacja #4** - nowa metoda `importVariantImages()`:
```php
/**
 * Import variant images from PrestaShop combination
 */
protected function importVariantImages(
    \App\Models\ProductVariant $variant,
    array $combination,
    PrestaShopShop $shop,
    $client
): void {
    // Get image IDs from combination associations
    $images = data_get($combination, 'associations.images', []);

    if (empty($images)) {
        Log::debug('[VARIANT IMPORT] No images in combination', [
            'variant_id' => $variant->id,
        ]);
        return;
    }

    // Clear existing variant images (replace strategy)
    $variant->images()->delete();

    $shopUrl = rtrim($shop->shop_url, '/');

    foreach ($images as $index => $imageData) {
        $imageId = (int) data_get($imageData, 'id', 0);

        if ($imageId <= 0) {
            continue;
        }

        // Build PrestaShop image URL
        $idString = (string) $imageId;
        $folderPath = implode('/', str_split($idString));
        $imageUrl = "{$shopUrl}/img/p/{$folderPath}/{$imageId}-home_default.jpg";

        \App\Models\VariantImage::create([
            'variant_id' => $variant->id,
            'image_url' => $imageUrl,
            'image_path' => '', // Will be set when cached
            'is_cover' => ($index === 0),
            'position' => $index,
        ]);
    }

    Log::debug('[VARIANT IMPORT] Images imported', [
        'variant_id' => $variant->id,
        'image_count' => count($images),
    ]);
}
```

---

## ZALECENIA

1. **PRIORYTET WYSOKI**: Naprawic bledy kolumn (Problem #1 i #2) - blokuja caly import wariantow!
2. **PRIORYTET SREDNI**: Dodac import zdjec wariantow (Problem #3)
3. **PO NAPRAWIE**: Ponownie zaimportowac warianty produktu 11164
4. **TESTY**: Przetestowac import wariantow dla nowych produktow

---

## WERYFIKACJA NAPRAWY

Po wdrozeniu poprawek, uruchomic:
```bash
php artisan tinker --execute="
echo 'Variant Attributes: ' . App\Models\VariantAttribute::whereIn('variant_id', App\Models\ProductVariant::where('product_id', 11164)->pluck('id'))->count();
echo 'Variant Prices: ' . App\Models\VariantPrice::whereIn('variant_id', App\Models\ProductVariant::where('product_id', 11164)->pluck('id'))->count();
echo 'Variant Images: ' . App\Models\VariantImage::whereIn('variant_id', App\Models\ProductVariant::where('product_id', 11164)->pluck('id'))->count();
"
```

**Oczekiwany wynik po naprawie:**
- Variant Attributes: >0 (minimum 33, 1 na wariant jesli kazdy ma 1 atrybut)
- Variant Prices: 33 (1 na wariant)
- Variant Images: >0 (zalezne od danych PrestaShop)

---

## POWIAZANE PLIKI

- `app/Services/PrestaShop/PrestaShopImportService.php` - glowny plik do naprawy
- `app/Models/VariantAttribute.php` - model (poprawny, ma color_hex w fillable ale to nieuzywane)
- `app/Models/VariantPrice.php` - model (ma fillable niezgodne z tym co kod probuje zapisac)
- `app/Models/VariantImage.php` - model (poprawny)
- `database/migrations/2025_10_17_100003_create_variant_attributes_table.php` - migracja bez color_hex
- `database/migrations/2025_10_17_100004_create_variant_prices_table.php` - migracja z price, price_special

---

**Status**: Gotowe do naprawy
**Nastepny krok**: Implementacja poprawek w PrestaShopImportService.php
