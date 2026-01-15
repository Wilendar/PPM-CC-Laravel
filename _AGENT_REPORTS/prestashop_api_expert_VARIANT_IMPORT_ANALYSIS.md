# RAPORT: Analiza Importu Wariantow z PrestaShop API

**Data**: 2025-12-10
**Agent**: prestashop-api-expert
**Zadanie**: Analiza importu produktow z wariantami z PrestaShop API do PPM

---

## 1. STRESZCZENIE PROBLEMU

**Produkt**: MRF13-68-003
**Objawy**:
1. `is_variant_master = false` (powinno byc `true`)
2. Warianty istnieja w tabeli `product_variants`, ale nie maja zdjec w UI

---

## 2. STRUKTURA DANYCH PRESTASHOP API

### 2.1 Produkt z Kombinacjami (GET /api/products/{id}?display=full)

PrestaShop zwraca informacje o kombinacjach w sekcji `associations`:

```json
{
  "product": {
    "id": 123,
    "reference": "MRF13-68-003",
    "product_type": "combinations",  // <-- KLUCZOWE POLE!
    "associations": {
      "combinations": {
        "combination": [
          { "id": 456 },
          { "id": 457 }
        ]
      },
      "images": {
        "image": [
          { "id": 1001 },
          { "id": 1002 }
        ]
      }
    }
  }
}
```

**Kluczowe pola wskazujace na warianty:**
- `product_type = "combinations"` - typ produktu z wariantami
- `associations.combinations` - lista ID kombinacji
- `id_default_combination` - domyslna kombinacja

### 2.2 Kombinacja (GET /api/combinations/{id})

```json
{
  "combination": {
    "id": 456,
    "id_product": 123,
    "reference": "MRF13-68-003-RED",
    "default_on": "1",
    "price": "0.000000",
    "quantity": 10,
    "associations": {
      "product_option_values": [
        { "id": 15 }  // np. Kolor: Czerwony
      ],
      "images": {
        "image": [
          { "id": 1001 }  // Zdjecie przypisane do kombinacji
        ]
      }
    }
  }
}
```

---

## 3. ANALIZA KODU - ZIDENTYFIKOWANE PROBLEMY

### 3.1 PROBLEM KRYTYCZNY: Brak ustawienia `is_variant_master`

**Lokalizacja**: `app/Services/PrestaShop/PrestaShopImportService.php`

**Metoda**: `syncProductVariants()` (linia ~1787)

**Problem**: Po zaimportowaniu wariantow NIE jest ustawiane `is_variant_master = true` na produkcie rodzicu.

**Aktualny kod** (linia 1847-1855):
```php
Log::info('[VARIANT IMPORT] Completed', [
    'product_id' => $product->id,
    'shop_id' => $shop->id,
    'imported' => $importedCount,
    'skipped' => $skippedCount,
    'errors' => $errorCount,
]);
// BRAK: $product->update(['is_variant_master' => true]);
```

**Brakujacy kod**:
```php
// Po pomyslnym imporcie wariantow - oznacz produkt jako master
if ($importedCount > 0) {
    $product->update(['is_variant_master' => true]);

    Log::info('[VARIANT IMPORT] Product marked as variant master', [
        'product_id' => $product->id,
        'variants_count' => $importedCount,
    ]);
}
```

### 3.2 PROBLEM: Zdjecia wariantow nie sa wyswietlane w UI

**Lokalizacja**: `app/Services/PrestaShop/PrestaShopImportService.php`

**Metoda**: `importVariantImages()` (linia ~2279)

**Problem**: Zdjecia sa importowane, ale:
1. Referencje do `prestashop_image_id` nie sa poprawnie dopasowane
2. Fallback na zdjecie produktu tworzy `VariantImage` z pustym `image_path`
3. Brak pobrania rzeczywistych plikow graficznych

**Aktualny kod** (problem - linia 2353-2366):
```php
} else {
    // Build PrestaShop image URL and store reference
    $imageUrl = '';
    if (!empty($shopUrl) && $productPsId > 0) {
        $imageUrl = "{$shopUrl}/api/images/products/{$productPsId}/{$psImageId}";
    }

    \App\Models\VariantImage::create([
        'variant_id' => $variant->id,
        'image_path' => '', // PROBLEM: Puste - nie pobrano pliku!
        'image_url' => $imageUrl,
        'is_cover' => ($position === 0),
        'position' => $position,
    ]);
}
```

**Problem**: `image_path` jest puste, co sprawia ze zdjecie nie wyswietla sie w UI.

### 3.3 PROBLEM: ProductTransformer nie sprawdza `product_type`

**Lokalizacja**: `app/Services/PrestaShop/ProductTransformer.php`

**Metoda**: `transformToPPM()` (linia ~788)

**Problem**: Metoda nie sprawdza pola `product_type` ani `associations.combinations`.

**Aktualny kod** NIE zawiera:
```php
// BRAKUJE sprawdzenia:
$hasCombinations = !empty(data_get($prestashopProduct, 'associations.combinations.combination'));
$productType = data_get($prestashopProduct, 'product_type');
$isVariantMaster = $hasCombinations || $productType === 'combinations';

// Powinno byc w zwracanej tablicy:
'is_variant_master' => $isVariantMaster,
```

---

## 4. REKOMENDACJE NAPRAWY

### 4.1 FIX #1: Ustawienie `is_variant_master` w `syncProductVariants()`

**Plik**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `syncProductVariants()`
**Lokalizacja**: Po petli foreach (linia ~1847)

```php
Log::info('[VARIANT IMPORT] Completed', [
    'product_id' => $product->id,
    'shop_id' => $shop->id,
    'imported' => $importedCount,
    'skipped' => $skippedCount,
    'errors' => $errorCount,
]);

// FIX 2025-12-10: Mark product as variant master after successful import
if ($importedCount > 0 && !$product->is_variant_master) {
    $product->update(['is_variant_master' => true]);

    Log::info('[VARIANT IMPORT] Product marked as variant master', [
        'product_id' => $product->id,
        'sku' => $product->sku,
        'variants_imported' => $importedCount,
    ]);
}
```

### 4.2 FIX #2: Sprawdzenie `is_variant_master` w `transformToPPM()`

**Plik**: `app/Services/PrestaShop/ProductTransformer.php`
**Metoda**: `transformToPPM()`
**Lokalizacja**: W sekcji budowania `$ppmProduct` (linia ~821)

```php
// Check if product has combinations (variants)
$hasCombinations = false;
$combinationsData = data_get($prestashopProduct, 'associations.combinations');
if (!empty($combinationsData)) {
    // Format 1: {'combination': [{'id': 1}, {'id': 2}]}
    if (isset($combinationsData['combination'])) {
        $combArray = $combinationsData['combination'];
        $hasCombinations = !empty($combArray) && (isset($combArray[0]) || isset($combArray['id']));
    }
    // Format 2: Direct array
    elseif (is_array($combinationsData)) {
        $hasCombinations = true;
    }
}

// Also check product_type field
$productType = data_get($prestashopProduct, 'product_type', '');
if ($productType === 'combinations') {
    $hasCombinations = true;
}

// Build PPM product data
$ppmProduct = [
    // ... existing fields ...

    // FIX 2025-12-10: Set is_variant_master based on PrestaShop data
    'is_variant_master' => $hasCombinations,

    // ... rest of fields ...
];
```

### 4.3 FIX #3: Poprawa importu zdjec wariantow

**Plik**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `importVariantImages()`

**Opcja A: Pobranie i zapisanie pliku graficznego**
```php
} else {
    // Download image from PrestaShop and save locally
    try {
        $client = $this->clientFactory::create($shop);
        $imageContent = $client->downloadProductImage($productPsId, $psImageId);

        if ($imageContent) {
            $filename = "variant_{$variant->id}_{$psImageId}.jpg";
            $path = "products/{$product->id}/variants/{$filename}";

            Storage::disk('public')->put($path, $imageContent);

            \App\Models\VariantImage::create([
                'variant_id' => $variant->id,
                'image_path' => $path,
                'image_url' => Storage::disk('public')->url($path),
                'is_cover' => ($position === 0),
                'position' => $position,
            ]);
        }
    } catch (\Exception $e) {
        Log::warning('[VARIANT IMPORT] Failed to download image', [...]);
    }
}
```

**Opcja B: Uzycie media_id zamiast image_path (lepsza)**
```php
// Jesli produkt ma Media z tym prestashop_image_id, linkuj do niego
$existingMedia = $product->media()
    ->where('prestashop_image_id', $psImageId)
    ->first();

if ($existingMedia) {
    \App\Models\VariantImage::create([
        'variant_id' => $variant->id,
        'media_id' => $existingMedia->id,  // Uzyj media_id!
        'is_cover' => ($position === 0),
        'position' => $position,
    ]);
}
```

---

## 5. PRZEPYW IMPORTU - JAK POWINIEN WYGLADAC

```
1. Import produktu z PrestaShop API
   GET /api/products/{id}?display=full

2. Sprawdz czy ma kombinacje:
   - associations.combinations nie jest puste
   - product_type === 'combinations'

3. Jesli ma kombinacje:
   a) Ustaw is_variant_master = true (w transformToPPM LUB w importProductFromPrestaShop)
   b) Pobierz kombinacje: GET /api/combinations?filter[id_product]={id}&display=full
   c) Dla kazdej kombinacji:
      - Utworz ProductVariant (SKU, nazwa, pozycja, is_default)
      - Importuj atrybuty (VariantAttribute)
      - Importuj cene (price modifier)
      - Importuj stock
      - Importuj zdjecia (VariantImage z media_id lub image_path)

4. Po zakonczeniu - upewnij sie ze is_variant_master = true
```

---

## 6. PLIKI DO MODYFIKACJI

| Plik | Zmiany |
|------|--------|
| `app/Services/PrestaShop/PrestaShopImportService.php` | FIX #1 - dodaj `$product->update(['is_variant_master' => true])` po imporcie wariantow |
| `app/Services/PrestaShop/ProductTransformer.php` | FIX #2 - sprawdzaj `associations.combinations` i `product_type` |
| `app/Services/PrestaShop/PrestaShopImportService.php` | FIX #3 - popraw `importVariantImages()` |

---

## 7. PRIORYTET NAPRAW

| FIX | Priorytet | Opis |
|-----|-----------|------|
| FIX #1 | KRYTYCZNY | Bez tego warianty nie beda wyswietlane w UI |
| FIX #2 | WYSOKI | Zapewnia poprawne is_variant_master juz przy transformacji |
| FIX #3 | SREDNI | Zdjecia wariantow beda puste bez tego fixa |

---

## 8. WERYFIKACJA PO NAPRAWIE

```sql
-- Sprawdz czy produkt ma is_variant_master = true
SELECT id, sku, name, is_variant_master
FROM products
WHERE sku = 'MRF13-68-003';

-- Sprawdz warianty produktu
SELECT pv.id, pv.sku, pv.name, pv.is_active
FROM product_variants pv
JOIN products p ON pv.product_id = p.id
WHERE p.sku = 'MRF13-68-003';

-- Sprawdz zdjecia wariantow
SELECT vi.id, vi.variant_id, vi.image_path, vi.image_url, vi.is_cover
FROM variant_images vi
JOIN product_variants pv ON vi.variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE p.sku = 'MRF13-68-003';
```

---

## 9. REFERENCJE

- PrestaShop API Documentation: `/prestashop/docs`
- Combination XML Schema: `webservice/resources/combinations.md`
- Create Product with Combinations: `webservice/tutorials/create-product-az.md`
