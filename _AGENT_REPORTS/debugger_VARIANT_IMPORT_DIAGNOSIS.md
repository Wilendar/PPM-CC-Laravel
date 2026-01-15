# RAPORT DIAGNOSTYCZNY: Import Wariantow z PrestaShop

**Data**: 2025-12-10 13:45
**Agent**: debugger
**Produkt testowy**: MRF13-68-003 (PrestaShop ID: 7566)

---

## STRESZCZENIE PROBLEMU

Podczas importu produktu MRF13-68-003 z PrestaShop do PPM:
1. Produkt zostal zaimportowany ale `is_variant_master = false` mimo ze ma 33 warianty
2. Warianty sa widoczne w ProductList ale bez zdjec
3. Zdjecia wariantow nie sa poprawnie przypisywane

---

## ZIDENTYFIKOWANE ROOT CAUSES

### BUG #1: `is_variant_master` NIE jest ustawiane (KRYTYCZNY)

**Lokalizacja**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `syncProductVariants()` (linia 1787-1863)

**Problem**: Po pomyslnym zaimportowaniu wariantow, metoda NIE aktualizuje flagi `is_variant_master` na produkcie.

**Kod brakujacy**:
```php
// BRAK TEGO KODU PO ZAKONCZENIU IMPORTU WARIANTOW:
if ($importedCount > 0) {
    $product->update(['is_variant_master' => true]);
}
```

**Dowody z logow**:
```
[2025-12-10 12:32:12] production.INFO: [VARIANT IMPORT] Completed {"product_id":11166,"shop_id":1,"imported":33,"skipped":0,"errors":0}
```
33 wariantow zaimportowanych, ale `is_variant_master` pozostaje `false`.

---

### BUG #2: Kolumna `prestashop_image_id` nie istnieje w tabeli `media`

**Lokalizacja**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `importVariantImages()` (linia 2279-2385)

**Problem**: Kod probuje uzyc kolumny `prestashop_image_id` ktora NIE istnieje w schemacie tabeli `media`.

**Aktualny kod (BLEDNY)**:
```php
$existingMedia = $product->media()
    ->where('prestashop_image_id', $psImageId)  // <-- TA KOLUMNA NIE ISTNIEJE!
    ->first();
```

**Blad z logow**:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'prestashop_image_id' in 'WHERE'
```

**Schemat tabeli `media`** (sprawdzony na produkcji):
```
Field                 Type
-------------------   --------------------
id                    bigint(20) unsigned
mediable_type         varchar(100)
mediable_id           bigint(20) unsigned
file_name             varchar(300)
file_path             varchar(500)
prestashop_mapping    longtext (JSON)     <-- TO JEST!
...
```

**Poprawna strategia**: Uzyc `prestashop_mapping` JSON field zamiast nieistniejacej kolumny.

---

### BUG #3: Warianty sa tworzone ale nie istnieja w bazie

**Obserwacja**:
- Logi pokazuja `variant_id: 124-156`
- Tabela `product_variants` zawiera tylko 2 rekordy (ID 55, 57)
- Auto_increment wskazuje ze warianty byly tworzone ale potem usuniete/rollback

**Prawdopodobna przyczyna**:
Blad w `importVariantImages()` NIE jest lapany poprawnie i moze powodowac czyszczenie wariantow.

---

## PROPONOWANE POPRAWKI

### FIX #1: Ustawienie `is_variant_master` po imporcie wariantow

**Plik**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `syncProductVariants()`

```php
protected function syncProductVariants(
    Product $product,
    array $prestashopData,
    PrestaShopShop $shop,
    $client
): void {
    // ... istniejacy kod ...

    Log::info('[VARIANT IMPORT] Completed', [
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'imported' => $importedCount,
        'skipped' => $skippedCount,
        'errors' => $errorCount,
    ]);

    // FIX #1: UPDATE is_variant_master AFTER successful import
    if ($importedCount > 0) {
        $product->update(['is_variant_master' => true]);

        Log::info('[VARIANT IMPORT] Product marked as variant master', [
            'product_id' => $product->id,
            'variants_imported' => $importedCount,
        ]);
    }
}
```

---

### FIX #2: Naprawienie logiki `importVariantImages()` - uzycie `prestashop_mapping`

**Plik**: `app/Services/PrestaShop/PrestaShopImportService.php`
**Metoda**: `importVariantImages()`

```php
protected function importVariantImages(
    \App\Models\ProductVariant $variant,
    array $combination,
    Product $product,
    PrestaShopShop $shop
): void {
    // ... poczatek metody bez zmian ...

    foreach ($imageIds as $psImageId) {
        try {
            // FIX #2: Use prestashop_mapping JSON instead of non-existent column
            $existingMedia = $product->media()
                ->whereJsonContains('prestashop_mapping->image_ids', $psImageId)
                ->orWhere(function ($query) use ($psImageId) {
                    // Fallback: check if image_id is stored directly
                    $query->whereRaw("JSON_EXTRACT(prestashop_mapping, '$.image_id') = ?", [$psImageId]);
                })
                ->first();

            if ($existingMedia) {
                // Link to existing Media record
                \App\Models\VariantImage::create([
                    'variant_id' => $variant->id,
                    'image_path' => $existingMedia->file_path,
                    'image_thumb_path' => $existingMedia->thumbnail_path ?? null,
                    'image_url' => $existingMedia->original_url ?? '',
                    'is_cover' => ($position === 0),
                    'position' => $position,
                ]);
            } else {
                // Fallback: Build PrestaShop image URL
                $imageUrl = '';
                if (!empty($shopUrl) && $productPsId > 0) {
                    $imageUrl = "{$shopUrl}/api/images/products/{$productPsId}/{$psImageId}";
                }

                \App\Models\VariantImage::create([
                    'variant_id' => $variant->id,
                    'image_path' => '',
                    'image_url' => $imageUrl,
                    'is_cover' => ($position === 0),
                    'position' => $position,
                ]);
            }

            $position++;

        } catch (\Exception $e) {
            // Continue with other images - don't fail entire import
            Log::warning('[VARIANT IMPORT] Could not import variant image', [
                'variant_id' => $variant->id,
                'prestashop_image_id' => $psImageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

### FIX #3 (Opcjonalny): Dodanie kolumny `prestashop_image_id` do tabeli `media`

Jesli potrzebujemy dedykowanej kolumny, utworzyc migracje:

```php
Schema::table('media', function (Blueprint $table) {
    $table->unsignedBigInteger('prestashop_image_id')->nullable()->after('prestashop_mapping');
    $table->index('prestashop_image_id');
});
```

---

## WERYFIKACJA DANYCH

**Stan bazy produkcyjnej**:
```
products: 2 rekordy (ID 11148, 11149)
product_variants: 2 rekordy (ID 55, 57 - dla produktu 11148)
```

**Sprawdzony produkt 11148**:
- SKU: MR-MRF-E
- `is_variant_master`: TRUE (ustawione manualnie)
- Warianty: 2

---

## KOLEJNE KROKI

1. **PRIORYTET WYSOKI**: Wdrozyc FIX #1 (is_variant_master)
2. **PRIORYTET WYSOKI**: Wdrozyc FIX #2 (prestashop_mapping zamiast prestashop_image_id)
3. **TESTOWANIE**: Ponownie zaimportowac produkt MRF13-68-003 z wariantami
4. **WERYFIKACJA**: Sprawdzic czy warianty maja zdjecia i `is_variant_master = true`

---

## PLIKI DO MODYFIKACJI

| Plik | Zmiana |
|------|--------|
| `app/Services/PrestaShop/PrestaShopImportService.php` | FIX #1: Dodac aktualizacje is_variant_master |
| `app/Services/PrestaShop/PrestaShopImportService.php` | FIX #2: Naprawic importVariantImages() |

---

**Status**: Gotowy do implementacji
**Autor**: debugger agent
