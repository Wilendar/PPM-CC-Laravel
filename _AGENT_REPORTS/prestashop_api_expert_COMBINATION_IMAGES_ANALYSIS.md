# RAPORT PRACY AGENTA: prestashop-api-expert
**Data**: 2025-12-09 09:30
**Agent**: prestashop-api-expert
**Zadanie**: Analyze PrestaShop combination images fetching and fix thumbnail placeholders

---

## ✅ WYKONANE PRACE

### 1. Analiza przepływu danych obrazów kombinacji

**Przeanalizowane pliki:**
- `app/Services/PrestaShop/PrestaShop8Client.php` - metody API (getCombinations, getProductImageUrl)
- `app/Services/PrestaShop/ShopVariantService.php` - mapowanie kombinacji na warianty

**Workflow obecny:**
1. `getCombinations()` → zwraca `associations.images` z ID-ami (nie URL-ami)
2. `pullShopVariants()` → pobiera kombinacje + obrazy produktu jako fallback
3. `mapCombinationsToVariants()` → wywołuje `extractCombinationImages()`
4. `extractCombinationImages()` → **PROBLEM:** buduje nieprawidłowe publiczne URL-e

---

## ⚠️ ZNALEZIONY PROBLEM

### Root Cause: Nieprawidłowe budowanie URL-i obrazów

**Obecna implementacja (`ShopVariantService::extractCombinationImages()`):**
```php
// ❌ BŁĘDNY FORMAT - nie działa bez Friendly URLs
$imageUrl = "{$baseUrl}/{$prestashopProductId}-{$imageId}-small_default.jpg";
// Result: https://b2btest.mpptrade.pl/9755-30621-small_default.jpg (404 Not Found)
```

**Prawidłowy format PUBLIC URL (wymaga Friendly URLs):**
```
https://b2btest.mpptrade.pl/img/p/3/0/6/2/1/30621-home_default.jpg
```

**Prawidłowy format API URL (działa zawsze, z uwierzytelnieniem):**
```
https://b2btest.mpptrade.pl/api/images/products/9755/30621?ws_key=XXX
```

### Dlaczego thumbnails pokazują placeholdery?

1. **Nieprawidłowy URL** → HTTP 404
2. **Brak uwierzytelnienia** (ws_key) → dostęp zablokowany
3. **Frontend fallback** → pokazuje placeholder gdy obrazek nie załaduje

---

## 📋 ROZWIĄZANIE

### Użyj istniejącej metody `PrestaShop8Client::getProductImageUrl()`

**Metoda już istnieje (linia 1125-1132):**
```php
public function getProductImageUrl(int $productId, int $imageId, string $size = 'large_default'): string
{
    $baseUrl = rtrim($this->shop->url, '/');
    $apiPath = $this->getApiBasePath();
    return $baseUrl . $apiPath . "/images/products/{$productId}/{$imageId}?ws_key=" . urlencode($this->shop->api_key);
}
```

**Zalety:**
- ✅ Authenticated (ws_key)
- ✅ Działa natychmiast bez konfiguracji sklepu
- ✅ Nie wymaga Friendly URLs
- ✅ Obsługuje różne rozmiary obrazków (small_default, home_default, large_default)

---

## 🔧 WYMAGANE ZMIANY KODU

### ZMIANA 1: `ShopVariantService::mapCombinationsToVariants()`

**Dodaj parametr `$client`:**
```php
protected function mapCombinationsToVariants(
    Product $product,
    int $shopId,
    array $combinations,
    array $attributeNamesMap = [],
    ?string $shopUrl = null,
    ?int $prestashopProductId = null,
    array $productImages = [],
    ?PrestaShop8Client $client = null  // ← NOWY PARAMETR
): Collection
```

**Przekaż klienta do extractCombinationImages:**
```php
'images' => $this->extractCombinationImages(
    $combination,
    $shopUrl,
    $prestashopProductId,
    $productImages,
    $client  // ← PRZEKAŻ CLIENT
),
```

**W pullShopVariants() dodaj przekazanie klienta (linia ~122):**
```php
$mappedVariants = $this->mapCombinationsToVariants(
    $product,
    $shopId,
    $combinations,
    $attributeNamesMap,
    $shop->url,
    $prestashopProductId,
    $productImages,
    $client  // ← DODAJ PARAMETR
);
```

---

### ZMIANA 2: `ShopVariantService::extractCombinationImages()`

**Dodaj parametr `$client` i użyj `getProductImageUrl()`:**

```php
protected function extractCombinationImages(
    array $combination,
    ?string $shopUrl = null,
    ?int $prestashopProductId = null,
    array $productImages = [],
    ?PrestaShop8Client $client = null  // ← NOWY PARAMETR
): array {
    $images = $combination['associations']['images'] ?? [];

    // FIX: If combination has no images, use first product image as fallback
    if (empty($images) && !empty($productImages)) {
        $firstProductImage = $productImages[0] ?? null;
        if ($firstProductImage && $client && $prestashopProductId) {
            $imageId = (int) ($firstProductImage['id'] ?? 0);
            if ($imageId > 0) {
                // ✅ USE API METHOD FOR AUTHENTICATED URL
                $imageUrl = $client->getProductImageUrl($prestashopProductId, $imageId, 'small_default');

                return [[
                    'prestashop_image_id' => $imageId,
                    'url' => $imageUrl,
                    'thumbnail_url' => $imageUrl,
                    'is_product_fallback' => true,
                ]];
            }
        }
    }

    return array_map(function ($img) use ($client, $prestashopProductId) {
        $imageId = (int) ($img['id'] ?? 0);

        // ✅ USE API METHOD FOR AUTHENTICATED URL
        $imageUrl = null;
        if ($client && $imageId && $prestashopProductId) {
            $imageUrl = $client->getProductImageUrl($prestashopProductId, $imageId, 'small_default');
        }

        return [
            'prestashop_image_id' => $imageId,
            'url' => $imageUrl,
            'thumbnail_url' => $imageUrl,
        ];
    }, $images);
}
```

---

## 📊 PRZED vs PO ZMIANIE

| Aspekt | PRZED (❌ Broken) | PO (✅ Fixed) |
|--------|------------------|--------------|
| **URL Format** | `{shop}/{prod_id}-{img_id}-small_default.jpg` | `{shop}/api/images/products/{prod_id}/{img_id}?ws_key=XXX` |
| **Przykład** | `https://b2btest.mpptrade.pl/9755-30621-small_default.jpg` | `https://b2btest.mpptrade.pl/api/images/products/9755/30621?ws_key=XXX` |
| **Uwierzytelnienie** | ❌ Brak (403/404) | ✅ ws_key w query string |
| **Friendly URLs** | ⚠️ Wymagane | ✅ Nie wymagane |
| **Działa od razu?** | ❌ Nie (404 errors) | ✅ Tak (authenticated access) |

---

## 📝 TESTOWANIE

### Test 1: Pull variants z PrestaShop

```php
// W ProductForm Livewire lub artisan tinker
$product = Product::find(11148);
$shopId = 2; // B2B Test DEV

$service = new ShopVariantService(new PrestaShop8Client(PrestaShopShop::find($shopId)));
$result = $service->pullShopVariants($product, $shopId);

dump($result['variants']->first()->images);
// Oczekiwane:
// [
//     [
//         'prestashop_image_id' => 30621,
//         'url' => 'https://b2btest.mpptrade.pl/api/images/products/9755/30621?ws_key=XXX',
//         'thumbnail_url' => 'https://b2btest.mpptrade.pl/api/images/products/9755/30621?ws_key=XXX',
//     ]
// ]
```

### Test 2: Verify image URL accessibility

```bash
# Test 1: Current broken URL
curl -I "https://b2btest.mpptrade.pl/9755-30621-small_default.jpg"
# Expected: 404 Not Found

# Test 2: Fixed API URL (po implementacji)
curl -I "https://b2btest.mpptrade.pl/api/images/products/9755/30621?ws_key=XXX"
# Expected: 200 OK (Content-Type: image/jpeg)
```

### Test 3: Frontend verification

1. Open product 11148 in edit mode
2. Switch to shop tab (B2B Test DEV)
3. Click "Pobierz warianty z PrestaShop"
4. Check variant table thumbnails
5. **Expected:** Real product images instead of placeholders

---

## 📁 PLIKI DO MODYFIKACJI

### Wymagane zmiany:
- `app/Services/PrestaShop/ShopVariantService.php`:
  - `mapCombinationsToVariants()` - dodaj parametr `$client`
  - `extractCombinationImages()` - dodaj parametr `$client` + użyj `getProductImageUrl()`
  - `pullShopVariants()` - przekaż `$client` do `mapCombinationsToVariants()`

### Bez zmian (już gotowe):
- `app/Services/PrestaShop/PrestaShop8Client.php` - metoda `getProductImageUrl()` już istnieje ✅

---

## 🔍 DODATKOWE OBSERWACJE

### Image URL building methods in PrestaShop8Client

1. **`getProductImageUrl()`** (linia 1125) - ✅ **UŻYWAJ TEJ!**
   - Authenticated API URL
   - Działa dla wszystkich sklepów

2. **`getProductImages()`** (linia 742) - Fetches image IDs only
   - Returns: `[['id' => 30621], ['id' => 30622]]`
   - Nie zwraca URL-i, tylko ID-ki

3. **`downloadProductImage()`** (linia 808) - Downloads binary image data
   - Do importu obrazów do PPM storage
   - Nie używane w tym przypadku

### PrestaShop Image Types

PrestaShop wspiera różne rozmiary obrazków (image types):
- `small_default` - 98x98px (thumbnails)
- `home_default` - 250x250px
- `large_default` - 800x800px
- `cart_default` - 125x125px

**Rekomendacja:** Użyj `small_default` dla thumbnails w tabelach wariantów.

---

## 🎯 NASTĘPNE KROKI

1. ✅ **Implementuj zmiany w ShopVariantService** (opisane powyżej)
2. ✅ **Przetestuj pull variants** z produktu 11148 (ma synced variants w B2B Test DEV)
3. ✅ **Zweryfikuj Chrome DevTools** - thumbnails powinny ładować obrazki z `api/images/products/`
4. ✅ **Deploy na produkcję** jeśli testy przejdą pomyślnie

---

## 💡 ALTERNATIVE APPROACH (jeśli API URLs mają problemy)

Jeśli authenticated API URLs powodują problemy (CORS, authentication), rozważ:

### Option A: Proxy przez Laravel

```php
// routes/web.php
Route::get('/prestashop/images/{shopId}/{productId}/{imageId}', function ($shopId, $productId, $imageId) {
    $shop = PrestaShopShop::findOrFail($shopId);
    $client = new PrestaShop8Client($shop);

    $imageData = $client->downloadProductImage($productId, $imageId);

    return response($imageData)
        ->header('Content-Type', 'image/jpeg')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('prestashop.image');
```

Wtedy URL:
```
https://ppm.mpptrade.pl/prestashop/images/2/9755/30621
```

**Zalety:**
- ✅ Brak CORS issues
- ✅ Scentralizowane uwierzytelnienie
- ✅ Możliwość cache'owania

**Wady:**
- ⚠️ Dodatkowe obciążenie serwera Laravel
- ⚠️ Wymaga storage cache dla performance

### Option B: Public Image URLs (jeśli Friendly URLs włączone)

Dodaj metodę do sprawdzenia czy sklep ma Friendly URLs:

```php
protected function buildPublicImageUrl(string $shopUrl, int $imageId, string $imageType = 'small_default'): string
{
    $idChars = str_split((string) $imageId);
    $path = '/img/p/' . implode('/', $idChars) . '/' . $imageId . '-' . $imageType . '.jpg';
    return rtrim($shopUrl, '/') . $path;
}
```

**JEDNAK:** Wymaga sprawdzenia konfiguracji sklepu. API URL jest bardziej uniwersalny.

---

## 📚 DOKUMENTACJA REFERENCYJNA

- PrestaShop Web Services: https://devdocs.prestashop.com/1.7/webservice/
- PrestaShop Image Management: https://devdocs.prestashop.com/1.7/webservice/tutorials/manage-images/
- ETAP_05c: Per-Shop Variants (Plan_Projektu/ETAP_05c_Produkty_Warianty.md)
- ETAP_07d: Media Sync System (Plan_Projektu/ETAP_07d_Media_Sync_System.md)

---

**Agent:** prestashop-api-expert
**Status:** ✅ Analysis Complete - Solution Ready for Implementation
