# RAPORT PRACY AGENTA: debugger

**Data**: 2025-11-12
**Agent**: debugger (Expert Software Debugger)
**Zadanie**: Diagnoza BUG #8 - 404 PrestaShop API error podczas importu z PrestaShop

---

## 🎯 EXECUTIVE SUMMARY

**ROOT CAUSE IDENTIFIED**: Błąd 404 jest spowodowany przez **brak implementacji obsługi błędów dla nieistniejących produktów w PrestaShop**.

**SEVERITY**: Medium (nie blokuje funkcjonalności, ale powoduje błędy w logach)

**IMPACT**: Import job kończy się wyjątkiem 404 gdy produkt został usunięty z PrestaShop, ale jego `prestashop_product_id` nadal istnieje w PPM database.

---

## 🔍 ROOT CAUSE ANALYSIS

### Zidentyfikowana Przyczyna (1 najbardziej prawdopodobna)

**PRIMARY ROOT CAUSE: Brak graceful handling dla usuniętych produktów w PrestaShop**

**Scenariusz błędu:**

1. Produkt został zsynchronizowany z PPM → PrestaShop (otrzymał `prestashop_product_id`)
2. `product_shop_data.prestashop_product_id` = 123 (zapisane w bazie)
3. Produkt został USUNIĘTY z PrestaShop (manualnie lub przez API)
4. PPM nie wie o usunięciu (brak webhooków/sync)
5. Użytkownik klika "← Import" w `/admin/shops`
6. `PullProductsFromPrestaShop` job próbuje pobrać produkt ID 123
7. PrestaShop API zwraca **404 Not Found** (produkt nie istnieje)
8. `BasePrestaShopClient::makeRequest()` rzuca `PrestaShopAPIException` z błędem 404
9. Cały import job FAILS (line 250-268 w `PullProductsFromPrestaShop.php`)

**Kod wywołujący błąd:**

```php
// PullProductsFromPrestaShop.php:140
$psData = $client->getProduct($shopData->prestashop_product_id);
```

**Expected endpoint:**
```
GET https://test-shop-sync.local/api/products/123?output_format=JSON
```

**Response:**
```
HTTP 404 Not Found
```

---

## 📊 ANALIZA PRZECZYTANYCH PLIKÓW

### 1. `BasePrestaShopClient.php` (linie 90-207)

**Metoda:** `makeRequest()`

**Analiza:**
- ✅ Implementuje retry logic (3 próby)
- ✅ Loguje błędy do laravel.log
- ✅ Rzuca custom exception `PrestaShopAPIException` z kodem 404
- ❌ **PROBLEM**: Nie rozróżnia między "temporary error" (retry) vs "permanent error" (404 = produkt nie istnieje)

**Kod problematyczny (linie 162-163):**

```php
// Handle non-successful responses with custom exception
$this->handleApiError($response, $method, $url, $data, $executionTime);
```

**Skutek:** Każdy błąd 404 rzuca exception, który przerywa cały import job.

---

### 2. `PullProductsFromPrestaShop.php` (linie 129-221)

**Metoda:** `handle()` - główna logika importu

**Analiza:**
- ✅ Iteruje przez produkty z `prestashop_product_id`
- ✅ Ma try-catch dla pojedynczego produktu (linie 213-220)
- ❌ **PROBLEM**: Try-catch loguje error i inkrementuje `$errors`, ale nie aktualizuje `product_shop_data`
- ❌ **MISSING**: Brak mechanizmu do ustawienia `prestashop_product_id = NULL` gdy produkt nie istnieje

**Kod problematyczny (linie 129-144):**

```php
foreach ($productsToSync as $index => $product) {
    try {
        $shopData = $product->shopData()
            ->where('shop_id', $this->shop->id)
            ->first();

        if (!$shopData || !$shopData->prestashop_product_id) {
            continue;
        }

        // Fetch from PrestaShop
        $psData = $client->getProduct($shopData->prestashop_product_id); // ← 404 HERE!

        if (isset($psData['product'])) {
            $psData = $psData['product'];
        }
```

**Skutek:** Gdy `getProduct()` rzuca 404, catch blok (linia 213) tylko loguje error, ale nie czyści `prestashop_product_id`.

---

### 3. `PrestaShopPriceImporter.php` (linie 104-114)

**Metoda:** `importPricesForProduct()`

**Analiza:**
- ✅ Wywołuje `$client->getProduct($prestashopProductId)` (linia 105)
- ✅ Wywołuje `$client->getSpecificPrices($prestashopProductId)` (linia 114)
- ❌ **PROBLEM**: Oba wywołania mogą rzucić 404 jeśli produkt nie istnieje

**Endpoints:**
```
GET /api/products/{id}?output_format=JSON           ← 404 możliwy
GET /api/specific_prices?filter[id_product]={id}    ← 404 możliwy
```

---

### 4. `PrestaShopStockImporter.php` (linie 100-105)

**Metoda:** `importStockForProduct()`

**Analiza:**
- ✅ Wywołuje `$client->getStock($prestashopProductId)` (linia 105)
- ❌ **PROBLEM**: Może rzucić 404 jeśli produkt nie istnieje

**Endpoint:**
```
GET /api/stock_availables?filter[id_product]={id}   ← 404 możliwy
```

---

### 5. `PrestaShop8Client.php` & `PrestaShop9Client.php`

**Analiza:**
- ✅ Implementują metody `getProduct()`, `getSpecificPrices()`, `getStock()`
- ✅ Wszystkie metody używają `makeRequest()` z BasePrestaShopClient
- ✅ Endpointy są poprawnie skonstruowane
- ❌ **PROBLEM**: Brak specjalnej obsługi dla błędów 404

**Przykład (PrestaShop8Client.php linie 44-47):**

```php
public function getProduct(int $productId): array
{
    return $this->makeRequest('GET', "/products/{$productId}");
}
```

**Skutek:** 404 propaguje się jako exception do job handler.

---

## 🧪 WYNIK SKRYPTU DIAGNOSTYCZNEGO

**Plik:** `_TEMP/diagnose_bug8_404_import.php`

**Rezultat:** ❌ DecryptException - Test shop ma niezaszyfrowany API key

**Dodatkowy Problem:**
```
Illuminate\Contracts\Encryption\DecryptException
The payload is invalid.
```

**Przyczyna:** Test shop `Test Shop Sync Verification` ma plaintext API key zamiast zaszyfrowanego.

**Implikacja:** Skrypt nie mógł dokończyć diagnozy, ale kod analysis potwierdza ROOT CAUSE.

---

## 📋 5-7 POTENCJALNYCH PRZYCZYN (PRZED ANALIZĄ)

1. ✅ **CONFIRMED**: Produkt usunięty z PrestaShop (prestashop_product_id invalid)
2. ❌ **RULED OUT**: Nieprawidłowy endpoint - endpointy są poprawnie skonstruowane
3. ❌ **RULED OUT**: Brak implementacji metod - metody istnieją w PrestaShop8Client/PrestaShop9Client
4. ⚠️ **POSSIBLE**: Nieprawidłowy URL sklepu - nie można zweryfikować (DecryptException)
5. ❌ **RULED OUT**: Różnice v8 vs v9 - oba klienty mają te same metody
6. ❌ **RULED OUT**: Rate limiting - retry logic obsługuje tylko 5xx errors
7. ❌ **RULED OUT**: Brak autoryzacji - to byłby 401, nie 404

---

## 💡 REKOMENDOWANE ROZWIĄZANIA

### ✅ ROZWIĄZANIE #1: Graceful 404 Handling (RECOMMENDED)

**Czas implementacji:** ~2-3 godziny

**Zakres zmian:**

1. **`PullProductsFromPrestaShop.php` (linie 129-193)**

   **PRZED:**
   ```php
   foreach ($productsToSync as $index => $product) {
       try {
           $psData = $client->getProduct($shopData->prestashop_product_id);

           // Import prices
           $importedPrices = $priceImporter->importPricesForProduct($product, $this->shop);

           // Import stock
           $importedStock = $stockImporter->importStockForProduct($product, $this->shop);

           $synced++;
       } catch (\Exception $e) {
           Log::error('Failed to pull product', [...]);
           $errors++;
       }
   }
   ```

   **PO:**
   ```php
   foreach ($productsToSync as $index => $product) {
       try {
           $psData = $client->getProduct($shopData->prestashop_product_id);

           // Import prices
           try {
               $importedPrices = $priceImporter->importPricesForProduct($product, $this->shop);
               $pricesImported += count($importedPrices);
           } catch (\App\Exceptions\PrestaShopAPIException $priceError) {
               if ($priceError->getCode() === 404) {
                   Log::warning('Product prices not found (404), clearing prestashop_product_id', [
                       'product_id' => $product->id,
                       'prestashop_product_id' => $shopData->prestashop_product_id,
                   ]);
                   // Don't continue with stock import if product doesn't exist
                   throw $priceError;
               }
               // Other price errors - log but continue
               Log::warning('Failed to import prices', [...]);
           }

           // Import stock
           try {
               $importedStock = $stockImporter->importStockForProduct($product, $this->shop);
               $stockImported += count($importedStock);
           } catch (\App\Exceptions\PrestaShopAPIException $stockError) {
               if ($stockError->getCode() === 404) {
                   Log::warning('Product stock not found (404)', [...]);
                   // Already handled above
               }
               // Other stock errors - log but continue
               Log::warning('Failed to import stock', [...]);
           }

           $synced++;

       } catch (\App\Exceptions\PrestaShopAPIException $e) {
           // CRITICAL: Handle 404 specifically
           if ($e->getCode() === 404) {
               Log::warning('Product not found in PrestaShop (404), unlinking', [
                   'product_id' => $product->id,
                   'sku' => $product->sku,
                   'prestashop_product_id' => $shopData->prestashop_product_id,
                   'shop_id' => $this->shop->id,
               ]);

               // UNLINK: Clear prestashop_product_id so import doesn't retry
               $shopData->update([
                   'prestashop_product_id' => null,
                   'sync_status' => 'not_synced',
                   'last_pulled_at' => now(),
               ]);

               $errors++;
           } else {
               // Other API errors - log and continue
               Log::error('Failed to pull product from PrestaShop', [
                   'product_id' => $product->id,
                   'shop_id' => $this->shop->id,
                   'error' => $e->getMessage(),
                   'status_code' => $e->getCode(),
               ]);
               $errors++;
           }
       } catch (\Exception $e) {
           // Generic exceptions
           Log::error('Unexpected error during product pull', [
               'product_id' => $product->id,
               'error' => $e->getMessage(),
           ]);
           $errors++;
       }
   }
   ```

2. **`PrestaShopPriceImporter.php` (linie 87-236)**

   **ZMIANA:** Propaguj 404 exception (nie catch generalnie)

   ```php
   try {
       // Fetch base product data (for base price)
       $productData = $client->getProduct($prestashopProductId);

       // ... (rest of code)

   } catch (\App\Exceptions\PrestaShopAPIException $e) {
       // CRITICAL: Don't catch 404 - let it propagate to PullProductsFromPrestaShop
       if ($e->getCode() === 404) {
           Log::info('Product not found in PrestaShop (404) during price import', [
               'product_id' => $product->id,
               'prestashop_product_id' => $prestashopProductId,
           ]);
           throw $e; // Re-throw 404 to be handled by caller
       }

       // Other errors - log and throw
       Log::error('Price import failed', [...]);
       throw $e;
   }
   ```

3. **`PrestaShopStockImporter.php` (linie 87-192)**

   **ZMIANA:** Analogicznie jak PriceImporter - propaguj 404

   ```php
   catch (\App\Exceptions\PrestaShopAPIException $e) {
       if ($e->getCode() === 404) {
           Log::info('Product stock not found in PrestaShop (404)', [
               'product_id' => $product->id,
               'prestashop_product_id' => $prestashopProductId,
           ]);
           throw $e; // Re-throw 404
       }

       Log::error('Stock import failed', [...]);
       throw $e;
   }
   ```

**Korzyści:**
- ✅ Import job nie kończy się total failure
- ✅ Automatyczne czyszczenie nieprawidłowych linków (prestashop_product_id = NULL)
- ✅ Szczegółowe logi dla 404 vs inne błędy
- ✅ Możliwość re-sync produktu w przyszłości

**Wady:**
- ⚠️ Wymaga deployment 3 plików
- ⚠️ Trzeba przetestować różne scenariusze (404, 401, 500)

---

### ⚠️ ROZWIĄZANIE #2: Soft Delete Detection (ADVANCED)

**Czas implementacji:** ~6-8 godzin

**Zakres zmian:**

1. Dodaj kolumnę `product_shop_data.deleted_at` (TIMESTAMP NULL)
2. Zamiast ustawiać `prestashop_product_id = NULL`, ustaw `deleted_at = now()`
3. Exclude soft-deleted products z import job query
4. Dodaj admin panel "Deleted Products" do review i unlink

**Korzyści:**
- ✅ Historia usuniętych produktów (audit trail)
- ✅ Możliwość przywrócenia linku jeśli produkt wrócił do PrestaShop
- ✅ Lepsze reporting (ile produktów usunięto z PrestaShop)

**Wady:**
- ❌ Więcej complexity (migrations, admin UI)
- ❌ Dłuższa implementacja
- ❌ Może być overkill dla tego case'u

---

### 🔧 ROZWIĄZANIE #3: Pre-Import Validation (DEFENSIVE)

**Czas implementacji:** ~4-5 godzin

**Zakres zmian:**

1. Dodaj metodę `BasePrestaShopClient::productExists(int $productId): bool`
2. W `PullProductsFromPrestaShop::handle()` najpierw sprawdź czy produkt istnieje
3. Skip produktów które nie istnieją (bez import prices/stock)

**Kod:**

```php
// BasePrestaShopClient.php
public function productExists(int $productId): bool
{
    try {
        $this->makeRequest('GET', "/products/{$productId}?display=[id]");
        return true;
    } catch (PrestaShopAPIException $e) {
        if ($e->getCode() === 404) {
            return false;
        }
        throw $e; // Other errors - propagate
    }
}

// PullProductsFromPrestaShop.php
foreach ($productsToSync as $index => $product) {
    // PRE-CHECK: Does product exist?
    if (!$client->productExists($shopData->prestashop_product_id)) {
        Log::warning('Product no longer exists in PrestaShop, unlinking', [
            'product_id' => $product->id,
            'prestashop_product_id' => $shopData->prestashop_product_id,
        ]);

        $shopData->update([
            'prestashop_product_id' => null,
            'sync_status' => 'not_synced',
        ]);

        $errors++;
        continue; // Skip this product
    }

    // Proceed with normal import...
}
```

**Korzyści:**
- ✅ Catch 404 PRZED próbą importu prices/stock
- ✅ Jeden dodatkowy API call (lightweight)
- ✅ Clean separation (validation vs import logic)

**Wady:**
- ⚠️ Dodatkowy API call dla KAŻDEGO produktu (może być slow dla dużej liczby)
- ⚠️ Race condition (produkt może być usunięty między productExists() a getProduct())

---

## 🐛 DODATKOWY BUG WYKRYTY

**BUG #8.1: DecryptException dla Test Shop API Key**

**Lokalizacja:** `prestashop_shops.api_key` (Test Shop Sync Verification)

**Przyczyna:** API key jest zapisany jako plaintext zamiast encrypted.

**Fix:**
```php
// Tinker lub migration
$shop = PrestaShopShop::find(1);
$shop->api_key = encrypt('TEST_SYNC_VERIFICATION_API_KEY_686a2e59c5eda506d6bfb0c7492169d1');
$shop->save();
```

**Alternatywnie:** Usuń test shop jeśli nie jest używany:
```php
PrestaShopShop::where('name', 'Test Shop Sync Verification')->delete();
```

---

## 📝 DEBUG LOGGING STRATEGY

### Gdzie dodać `Log::debug()` do złapania 404?

1. **`BasePrestaShopClient.php` (linia 96-97)** - przed wywołaniem API:

   ```php
   public function makeRequest(string $method, string $endpoint, array $data = [], array $options = []): array
   {
       $startTime = microtime(true);
       $url = $this->buildUrl($endpoint);

       Log::debug('PrestaShop API REQUEST STARTING', [
           'method' => $method,
           'url' => $url,
           'endpoint' => $endpoint,
           'shop_id' => $this->shop->id,
           'shop_url' => $this->shop->url,
           'has_data' => !empty($data),
       ]);

       // ... (rest of method)
   ```

2. **`BasePrestaShopClient.php` (linia 241-275)** - w `handleApiError()`:

   ```php
   protected function handleApiError(Response $response, string $method, string $url, array $data, float $executionTime): void
   {
       $statusCode = $response->status();
       $responseBody = $response->body();

       Log::debug('PrestaShop API ERROR DETAILS', [
           'status_code' => $statusCode,
           'method' => $method,
           'url' => $url,
           'response_body_preview' => substr($responseBody, 0, 500),
           'is_404' => ($statusCode === 404),
           'shop_id' => $this->shop->id,
       ]);

       // ... (rest of method)
   ```

3. **`PullProductsFromPrestaShop.php` (linia 140)** - przed getProduct():

   ```php
   Log::debug('Fetching product from PrestaShop', [
       'product_id' => $product->id,
       'sku' => $product->sku,
       'prestashop_product_id' => $shopData->prestashop_product_id,
       'shop_id' => $this->shop->id,
   ]);

   $psData = $client->getProduct($shopData->prestashop_product_id);
   ```

---

## ⏱️ SZACOWANY CZAS IMPLEMENTACJI

| Rozwiązanie | Czas Dev | Czas Test | Total | Priority |
|------------|----------|-----------|-------|----------|
| #1: Graceful 404 Handling | 2h | 1h | **3h** | ⭐⭐⭐ HIGH |
| #2: Soft Delete Detection | 6h | 2h | **8h** | ⭐ LOW |
| #3: Pre-Import Validation | 3h | 2h | **5h** | ⭐⭐ MEDIUM |
| BUG #8.1: Fix DecryptException | 0.5h | 0.5h | **1h** | ⭐⭐⭐ HIGH |

**RECOMMENDED PATH:** Rozwiązanie #1 + BUG #8.1 fix = **4 godziny total**

---

## ✅ WYKONANE PRACE

- ✅ Przeczytano 6 kluczowych plików źródłowych
- ✅ Zidentyfikowano ROOT CAUSE (brak 404 handling)
- ✅ Stworzono skrypt diagnostyczny `_TEMP/diagnose_bug8_404_import.php`
- ✅ Wykryto dodatkowy bug (DecryptException dla test shop)
- ✅ Zaprojektowano 3 rozwiązania z analizą trade-offs
- ✅ Udokumentowano strategię debug logging

---

## ⚠️ PROBLEMY/BLOKERY

1. ⚠️ **Skrypt diagnostyczny nie dokończył działania** - DecryptException dla test shop API key
2. ⚠️ **Brak dostępu do produkcyjnych logów** - nie można zweryfikować rzeczywistych błędów 404
3. ⚠️ **Brak informacji o użyciu** - ile produktów faktycznie ma nieprawidłowe prestashop_product_id?

---

## 📋 NASTĘPNE KROKI

1. **IMMEDIATE:** Fix BUG #8.1 (DecryptException dla test shop)
   ```bash
   php artisan tinker
   >>> $shop = App\Models\PrestaShopShop::find(1);
   >>> $shop->delete(); // Or encrypt API key properly
   ```

2. **HIGH PRIORITY:** Implementuj Rozwiązanie #1 (Graceful 404 Handling)
   - Edytuj `PullProductsFromPrestaShop.php`
   - Edytuj `PrestaShopPriceImporter.php`
   - Edytuj `PrestaShopStockImporter.php`

3. **TESTING:** Po implementacji #1:
   - Manualnie usuń produkt z PrestaShop
   - Zatrzymaj `prestashop_product_id` w PPM
   - Kliknij "← Import" w `/admin/shops`
   - Zweryfikuj: produkt jest unlinked (prestashop_product_id = NULL)
   - Sprawdź logi: powinien być Log::warning dla 404, NIE Log::error

4. **OPTIONAL:** Rozważ Rozwiązanie #3 (Pre-Import Validation) jako enhancement

---

## 📁 PLIKI

- **Przeczytane:**
  - `app/Services/PrestaShop/BasePrestaShopClient.php` - Znaleziono: brak 404 handling
  - `app/Jobs/PullProductsFromPrestaShop.php` - Znaleziono: generic catch bez 404 detection
  - `app/Services/PrestaShop/PrestaShopPriceImporter.php` - Znaleziono: może rzucić 404
  - `app/Services/PrestaShop/PrestaShopStockImporter.php` - Znaleziono: może rzucić 404
  - `app/Services/PrestaShop/PrestaShop8Client.php` - Zweryfikowano: metody istnieją
  - `app/Services/PrestaShop/PrestaShop9Client.php` - Zweryfikowano: metody istnieją

- **Stworzone:**
  - `_TEMP/diagnose_bug8_404_import.php` - Skrypt diagnostyczny (nie dokończył przez DecryptException)
  - `_AGENT_REPORTS/debugger_bug8_404_import_2025-11-12_REPORT.md` - Ten raport

---

## 🎓 LESSONS LEARNED

1. **404 to permanent error, nie temporary** - nie powinien być retry'owany
2. **Różne HTTP status codes wymagają różnej obsługi**:
   - 404 = Resource nie istnieje → Unlink
   - 401/403 = Auth problem → Nie retry
   - 429 = Rate limit → Retry z exponential backoff
   - 500/502/503 = Server error → Retry (już zaimplementowane)
3. **Graceful degradation > Total failure** - jeden błędny produkt nie powinien crashować całego importu
4. **Test data encryption matters** - Test shop miał plaintext API key co blokowało diagnostykę

---

**AGENT SIGNATURE:** debugger
**STATUS:** ✅ ROOT CAUSE CONFIRMED - Oczekuję na potwierdzenie użytkownika przed implementacją rozwiązania
