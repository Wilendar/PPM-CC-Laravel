---
name: "prestashop-api-integration"
description: "Complete workflow for PrestaShop Web Services API integration including product fields reference, XML format requirements, CRUD operations, and admin visibility requirements."
---

# PrestaShop API Integration Workflow

## 🎯 Overview

Ten skill dostarcza **complete end-to-end workflow** dla integracji z PrestaShop Web Services API, covering:

**PART I - API WORKFLOW:**
- **Authentication** setup i verification
- **Resource discovery** (schema analysis, available endpoints)
- **CRUD operations** (Create, Read, Update, Delete) using CQRS pattern
- **Error handling** i recovery strategies
- **Performance optimization** (caching, rate limiting, batch operations)

**PART II - PRODUCT FIELDS REFERENCE:**
- **7 REQUIRED fields** for admin panel visibility
- **8 READONLY fields** - never send in POST/PUT!
- **Full field mapping** (~80 fields across 9 categories)
- **XML templates** (minimum, full, update)
- **GET-MODIFY-PUT pattern** for safe updates

**Główna dokumentacja:** [_DOCS/PRESTASHOP_API_REFERENCE.md](../../../_DOCS/PRESTASHOP_API_REFERENCE.md)

---

## 🚀 Kiedy używać tego Skill

Użyj `prestashop-api-integration` gdy:

- ✅ **Implementujesz nową integrację** z PrestaShop API
- ✅ **Syngujesz produkty** między PPM a PrestaShop
- ✅ **Budujesz XML payload** dla POST/PUT/PATCH operations
- ✅ **Debugujesz problemy** z polami produktu (400 errors, admin visibility)
- ✅ **Dostajesz błąd "parameter X not writable"** (400 error)
- ✅ **Produkt znika z admin panelu** po synchronizacji
- ✅ **Potrzebujesz wiedzieć** które pola są REQUIRED vs OPTIONAL vs READONLY
- ✅ **Implementujesz import/export** produktów
- ✅ **Optymalizujesz performance** existing API calls

---

## 📊 Quick Reference - Critical Rules

### 4 KRYTYCZNE ZASADY:

| # | Zasada | Konsekwencja naruszenia |
|---|--------|------------------------|
| 1 | PrestaShop PUT **ZASTĘPUJE** cały zasób | Brakujące pola = PUSTE wartości! |
| 2 | **8 pól READONLY** - NIE wysyłaj! | 400 Bad Request error |
| 3 | **7 pól WYMAGANYCH** dla admin visibility | Produkt niewidoczny w panelu |
| 4 | Wszystkie wartości w **CDATA** w XML | XML parsing errors |

### Legend for Status:

| Status | Znaczenie |
|--------|-----------|
| ✅ SYNC | Pole synchronizowane w OBIE strony (PPM ↔ PrestaShop) |
| ➡️ EXPORT | Pole eksportowane do PrestaShop (PPM → PS) |
| ⬅️ IMPORT | Pole importowane z PrestaShop (PS → PPM) |
| ❌ BRAK | Pole nie jest synchronizowane |
| 🔧 TODO | Pole wymaga implementacji |
| ⚠️ READONLY | Pole tylko do odczytu - NIE WYSYŁAJ w POST/PUT! |

---

# PART I: API WORKFLOW

## 📊 Workflow Diagram

```
┌─────────────────────────────────────────────────────────┐
│ FAZA 1: Authentication & Connection Setup              │
│ ├─ Generate API Key in PrestaShop                      │
│ ├─ Configure Laravel .env                              │
│ ├─ Test connection                                     │
│ └─ Verify permissions                                  │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│ FAZA 2: Resource Discovery & Schema Analysis           │
│ ├─ List available resources                            │
│ ├─ Get blank schema (template)                         │
│ ├─ Get synopsis schema (field details)                 │
│ └─ Map to Laravel models                               │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│ FAZA 3: CRUD Operations Implementation                 │
│ ├─ CQRSGet: Read single resource                       │
│ ├─ CQRSGetList: Read multiple with filters             │
│ ├─ CQRSCreate: Create new resource (POST XML)          │
│ ├─ CQRSUpdate: Full update (PUT XML)                   │
│ ├─ CQRSPartialUpdate: Partial update (PATCH XML)       │
│ └─ CQRSDelete: Delete resource                         │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│ FAZA 4: Error Handling & Recovery                      │
│ ├─ HTTP status code handling                           │
│ ├─ XML error parsing                                   │
│ ├─ Retry logic with exponential backoff                │
│ ├─ Transaction rollback                                │
│ └─ Logging and alerting                                │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│ FAZA 5: Performance Optimization                       │
│ ├─ Implement caching (Content-Sha1)                    │
│ ├─ Rate limiting (shared hosting protection)           │
│ ├─ Batch operations with delays                        │
│ └─ Query optimization (display parameter)              │
└─────────────────────────────────────────────────────────┘
                        ↓
                  ✅ PRODUCTION READY
```

---

## 📋 FAZA 1: Authentication & Connection Setup

### Cel Fazy
Skonfigurować i zweryfikować dostęp do PrestaShop API z proper authentication i permissions.

### 1.1 Generate API Key w PrestaShop

**Admin Panel → Advanced Parameters → Webservice**

1. Kliknij "Add new key"
2. Ustaw Key description (np. "PPM Integration")
3. Enable "Enabled" checkbox
4. Wybierz permissions per resource:
   ```
   products:         GET, POST, PUT, PATCH, DELETE
   categories:       GET, POST, PUT, PATCH, DELETE
   stock_availables: GET, PUT, PATCH (no POST/DELETE!)
   manufacturers:    GET, POST, PUT, PATCH, DELETE
   suppliers:        GET, POST, PUT, PATCH, DELETE
   combinations:     GET, POST, PUT, PATCH, DELETE
   images:           GET, POST, DELETE (no PATCH!)
   ```

5. Save → Skopiuj wygenerowany klucz (32-char hexadecimal)

### 1.2 Configure Laravel Environment

**File:** `.env`

```bash
# PrestaShop API Configuration
PRESTASHOP_API_URL=https://example.com/api
PRESTASHOP_API_KEY=YOUR_32_CHAR_API_KEY_HERE
PRESTASHOP_SHOP_ID=1
PRESTASHOP_DEFAULT_LANG_ID=1
```

**File:** `config/prestashop.php`

```php
<?php

return [
    'api_url' => env('PRESTASHOP_API_URL'),
    'api_key' => env('PRESTASHOP_API_KEY'),
    'shop_id' => env('PRESTASHOP_SHOP_ID', 1),
    'default_lang_id' => env('PRESTASHOP_DEFAULT_LANG_ID', 1),

    // HTTP Client Settings
    'timeout' => 60, // seconds
    'retry_attempts' => 3,
    'retry_delay' => 500, // milliseconds

    // Rate Limiting (Hostido protection)
    'rate_limit_delay' => 500, // milliseconds between requests
    'rate_limit_enabled' => env('PRESTASHOP_RATE_LIMIT', true),
];
```

### 1.3 Test Connection

**Artisan Command:** `php artisan prestashop:test-connection`

```php
<?php
// app/Console/Commands/PrestaShopTestConnection.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PrestaShopTestConnection extends Command
{
    protected $signature = 'prestashop:test-connection';
    protected $description = 'Test PrestaShop API connection and verify permissions';

    public function handle(): int
    {
        $this->info('Testing PrestaShop API connection...');

        try {
            $response = Http::withBasicAuth(config('prestashop.api_key'), '')
                ->timeout(10)
                ->get(config('prestashop.api_url'));

            if (!$response->successful()) {
                $this->error("Connection failed: HTTP {$response->status()}");
                return 1;
            }

            $this->info('✅ Connection successful');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Connection test failed: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### ⚠️ Common Issues - FAZA 1

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| 401 Unauthorized | Invalid API key | Verify key copied correctly |
| 403 Forbidden | Missing permissions | Edit key in PrestaShop, enable needed permissions |
| 404 Not Found | Wrong API URL | Verify URL ends with `/api` |

---

## 📋 FAZA 2: Resource Discovery & Schema Analysis

### 2.1 Get Blank Schema (Template)

```php
public function getBlankSchema(string $resource): string
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/{$resource}", [
            'schema' => 'blank'
        ]);

    return $response->body(); // XML template
}
```

### 2.2 Get Synopsis Schema (Field Details)

```php
public function getSynopsisSchema(string $resource): array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/{$resource}", [
            'schema' => 'synopsis'
        ]);

    $xml = simplexml_load_string($response->body());
    $fields = [];

    foreach ($xml->xpath('//field') as $field) {
        $fieldName = (string) $field['name'];
        $fields[$fieldName] = [
            'required' => (string) $field['required'] === 'true',
            'readonly' => (string) $field['readOnly'] === 'true',
            'type' => (string) $field->type,
            'maxSize' => (int) $field['maxSize'] ?: null,
            'language' => (string) $field['language'] === 'true',
        ];
    }

    return $fields;
}
```

---

## 📋 FAZA 3: CRUD Operations Implementation

### 3.1 CQRSGet - Read Single Resource

```php
public function getProduct(int $id): ?array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/products/{$id}", [
            'output_format' => 'JSON',
            'display' => 'full'
        ]);

    if ($response->status() === 404) {
        return null;
    }

    return $response->json()['product'] ?? null;
}
```

### 3.2 CQRSCreate - Create New Resource (POST XML)

**⚠️ CRITICAL:** XML format REQUIRED for POST!

```php
public function createProduct(array $data): int
{
    // 1. Get blank schema
    $blankResponse = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . '/products', ['schema' => 'blank']);

    $xml = simplexml_load_string($blankResponse->body());

    // 2. Remove readonly fields - CRITICAL!
    $readonly = ['id', 'manufacturer_name', 'supplier_name', 'quantity',
                 'date_add', 'date_upd', 'cache_is_pack',
                 'cache_has_attachments', 'cache_default_attribute', 'indexed'];
    foreach ($readonly as $field) {
        unset($xml->product->$field);
    }

    // 3. Fill required fields for admin visibility
    $xml->product->id_manufacturer = $data['id_manufacturer'] ?? 1;
    $xml->product->minimal_quantity = 1;
    $xml->product->redirect_type = '301-category';
    $xml->product->state = 1;
    $xml->product->additional_delivery_times = 1;
    $xml->product->price = max(0.01, $data['price'] ?? 0.01);

    // 4. Fill other fields...
    $xml->product->reference = $data['reference'];
    $xml->product->active = $data['active'] ?? 1;

    // 5. POST XML
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('POST', config('prestashop.api_url') . '/products', [
            'body' => $xml->asXML()
        ]);

    $responseXml = simplexml_load_string($response->body());
    return (int) $responseXml->product->id;
}
```

### 3.3 CQRSUpdate - Safe Update (GET-MODIFY-PUT)

**⚠️ WARNING:** PUT requires ALL fields! Use GET-MODIFY-PUT pattern!

```php
public function safeUpdateProduct(int $productId, array $updates): void
{
    // STEP 1: GET current product data
    $currentResponse = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/products/{$productId}");

    $xml = simplexml_load_string($currentResponse->body());

    // STEP 2: Remove readonly fields
    $readonly = ['manufacturer_name', 'supplier_name', 'date_add', 'date_upd',
                 'cache_is_pack', 'cache_has_attachments',
                 'cache_default_attribute', 'indexed'];
    foreach ($readonly as $field) {
        unset($xml->product->$field);
    }

    // STEP 3: Apply updates
    foreach ($updates as $field => $value) {
        if (isset($xml->product->$field)) {
            $xml->product->$field = $value;
        }
    }

    // STEP 4: PUT updated XML
    Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('PUT', config('prestashop.api_url') . "/products/{$productId}", [
            'body' => $xml->asXML()
        ]);
}
```

### 3.4 CQRSPartialUpdate - Partial Update (PATCH XML)

**✅ RECOMMENDED:** PATCH for partial updates (only changed fields)

```php
public function patchProduct(int $id, array $changes): void
{
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>');
    $product = $xml->addChild('product');
    $product->addChild('id', $id);

    foreach ($changes as $field => $value) {
        $product->addChild($field, htmlspecialchars($value));
    }

    Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('PATCH', config('prestashop.api_url') . "/products/{$id}", [
            'body' => $xml->asXML()
        ]);
}

// Usage - tylko cena
$client->patchProduct(123, ['price' => 99.99]);
```

---

## 📋 FAZA 4: Error Handling & Recovery

### 4.1 HTTP Status Code Handling

```php
class PrestaShopApiException extends \Exception
{
    public function __construct(
        public int $httpStatus,
        public string $prestashopError,
        string $message
    ) {
        parent::__construct($message);
    }
}

// Handle specific status codes
match ($status) {
    400 => throw new PrestaShopApiException(400, $errorMessage, "Bad Request: {$errorMessage}"),
    401 => throw new PrestaShopApiException(401, 'Unauthorized', 'Invalid API key'),
    403 => throw new PrestaShopApiException(403, 'Forbidden', 'Missing permissions'),
    404 => throw new PrestaShopApiException(404, 'Not Found', 'Resource not found'),
    500 => throw new PrestaShopApiException(500, $errorMessage, "Server Error: {$errorMessage}"),
    default => throw new PrestaShopApiException($status, $errorMessage, "API error {$status}")
};
```

### 4.2 Retry Logic with Exponential Backoff

```php
public function withRetry(callable $operation, string $operationName): mixed
{
    $maxAttempts = 3;
    $baseDelay = 500; // ms
    $attempt = 1;

    while ($attempt <= $maxAttempts) {
        try {
            return $operation();
        } catch (PrestaShopApiException $e) {
            // Don't retry on client errors (4xx)
            if ($e->httpStatus >= 400 && $e->httpStatus < 500) {
                throw $e;
            }

            if ($attempt >= $maxAttempts) throw $e;

            $delay = $baseDelay * pow(2, $attempt - 1);
            usleep($delay * 1000);
            $attempt++;
        }
    }
}
```

---

## 📋 FAZA 5: Performance Optimization

### 5.1 Rate Limiting (Hostido Protection)

```php
class RateLimitedPrestaShopClient
{
    private int $lastRequestTime = 0;
    private int $delayMs = 500;

    protected function enforceRateLimit(): void
    {
        $now = (int) (microtime(true) * 1000);
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < $this->delayMs) {
            usleep(($this->delayMs - $elapsed) * 1000);
        }

        $this->lastRequestTime = (int) (microtime(true) * 1000);
    }
}
```

### 5.2 Query Optimization (display parameter)

```php
// ❌ INEFFICIENT: Fetch all fields
$products = Http::get('/api/products?output_format=JSON');
// → Transfers 50KB per product

// ✅ EFFICIENT: Only needed fields
$products = Http::get('/api/products?display=[id,reference,price]&output_format=JSON');
// → Transfers 2KB per product
```

---

# PART II: PRODUCT FIELDS REFERENCE

## 🔴 7 WYMAGANYCH PÓL - Admin Panel Visibility

**BEZ TYCH PÓL PRODUKT BĘDZIE NIEWIDOCZNY W ADMIN PANELU!**

| # | Pole | Wartość | Tabele | Notatki |
|---|------|---------|--------|---------|
| 1 | `id_manufacturer` | > 0 (valid ID) | ps_product | MUST be valid manufacturer, NOT 0/NULL |
| 2 | `minimal_quantity` | 1 | ps_product + ps_product_shop | NIE 0! |
| 3 | `redirect_type` | '301-category' | ps_product + ps_product_shop | NIE empty string! |
| 4 | `state` | 1 | ps_product | 0 = draft/incomplete |
| 5 | `additional_delivery_times` | 1 | ps_product | Wymagane dla dostawy |
| 6 | `price` | > 0 (min 0.01) | ps_product + ps_product_shop | NIE 0.00! |
| 7 | `ps_specific_price` record | EXISTS | ps_specific_price table | 101.3% produktów ma to! |

### Weryfikacja SQL

```sql
-- Check ps_product required fields
SELECT
    id_product,
    id_manufacturer,        -- MUST be > 0
    minimal_quantity,       -- MUST = 1
    redirect_type,          -- MUST = '301-category'
    state,                  -- MUST = 1
    additional_delivery_times,  -- MUST = 1
    price                   -- MUST > 0
FROM ps_product
WHERE id_product = ?;

-- Check ps_specific_price exists
SELECT COUNT(*) FROM ps_specific_price WHERE id_product = ?;
-- MUST return > 0
```

### Post-Sync SQL Fix (Backup)

```php
// Wywołaj PO każdym CREATE produktu jako safety net
protected function ensureRequiredFields(int $prestashopProductId): void
{
    DB::connection('prestashop')->transaction(function () use ($prestashopProductId) {
        // Fix ps_product
        DB::connection('prestashop')->table('ps_product')
            ->where('id_product', $prestashopProductId)
            ->update([
                'minimal_quantity' => 1,
                'redirect_type' => '301-category',
                'state' => 1,
                'additional_delivery_times' => 1,
                'price' => DB::raw('GREATEST(price, 0.01)'),
            ]);

        // Fix ps_product_shop
        DB::connection('prestashop')->table('ps_product_shop')
            ->where('id_product', $prestashopProductId)
            ->update([
                'minimal_quantity' => 1,
                'redirect_type' => '301-category',
                'price' => DB::raw('GREATEST(price, 0.01)'),
            ]);

        // Ensure ps_specific_price exists
        $exists = DB::connection('prestashop')
            ->table('ps_specific_price')
            ->where('id_product', $prestashopProductId)
            ->exists();

        if (!$exists) {
            $price = DB::connection('prestashop')
                ->table('ps_product')
                ->where('id_product', $prestashopProductId)
                ->value('price');

            DB::connection('prestashop')->table('ps_specific_price')->insert([
                'id_product' => $prestashopProductId,
                'id_shop' => 0,
                'id_currency' => 0,
                'id_country' => 0,
                'id_group' => 0,
                'id_customer' => 0,
                'id_product_attribute' => 0,
                'price' => max(0.01, $price),
                'from_quantity' => 1,
                'reduction' => 0.000000,
                'reduction_type' => 'amount',
                'from' => '0000-00-00 00:00:00',
                'to' => '0000-00-00 00:00:00',
            ]);
        }
    });
}
```

---

## ⛔ 8 READONLY FIELDS - NIE WYSYŁAJ!

**8 pól które NIGDY nie mogą być w POST/PUT XML:**

| # | Pole | Błąd jeśli wysłane | Alternatywa |
|---|------|-------------------|-------------|
| 1 | `manufacturer_name` | 400: not writable | ✅ Użyj `id_manufacturer` |
| 2 | `supplier_name` | 400: not writable | ✅ Użyj `id_supplier` |
| 3 | `date_add` | 400: not writable | Auto-generated |
| 4 | `date_upd` | 400: not writable | Auto-updated |
| 5 | `cache_is_pack` | 400: not writable | Internal cache |
| 6 | `cache_has_attachments` | 400: not writable | Internal cache |
| 7 | `cache_default_attribute` | 400: not writable | Internal cache |
| 8 | `indexed` | 400: not writable | Internal index |

### Kod do Filtrowania Readonly

```php
private const READONLY_FIELDS = [
    'manufacturer_name',
    'supplier_name',
    'date_add',
    'date_upd',
    'cache_is_pack',
    'cache_has_attachments',
    'cache_default_attribute',
    'indexed',
];

private function filterReadonlyFields(array $productData): array
{
    if (isset($productData['product'])) {
        foreach (self::READONLY_FIELDS as $field) {
            unset($productData['product'][$field]);
        }
    }
    return $productData;
}
```

---

## 📦 PEŁNA LISTA PÓL PRODUKTU

### 1. POLA IDENTYFIKACYJNE

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `id` | int | `external_id` | ✅ SYNC | ID w PrestaShop |
| `reference` | string(64) | `sku` | ✅ SYNC | **SKU produktu** |
| `ean13` | string(13) | `ean` | ✅ SYNC | Kod EAN |
| `isbn` | string(32) | - | ❌ BRAK | ISBN dla książek |
| `upc` | string(12) | - | ❌ BRAK | UPC dla US market |
| `mpn` | string(40) | - | ❌ BRAK | Manufacturer Part Number |
| `supplier_reference` | string(64) | `supplier_code` | 🔧 TODO | Kod dostawcy |

### 2. POLA MULTILINGUAL

**Format:** `[['id' => lang_id, 'value' => 'text']]`

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `name` | string[lang] | `name` | ✅ SYNC | **Nazwa produktu** |
| `description` | text[lang] | `long_description` | ✅ SYNC | Długi opis HTML |
| `description_short` | text[lang] | `short_description` | ✅ SYNC | Max 800 znaków |
| `link_rewrite` | string[lang] | `slug` | ✅ SYNC | URL slug |
| `meta_title` | string[lang] | `meta_title` | ✅ SYNC | SEO tytuł |
| `meta_description` | text[lang] | `meta_description` | ✅ SYNC | SEO opis |
| `meta_keywords` | string[lang] | - | ❌ BRAK | Deprecated w PS 8.x+ |

**Przykład Multilang:**
```php
'name' => [
    ['id' => 1, 'value' => 'Product Name EN'],
    ['id' => 2, 'value' => 'Nazwa Produktu PL'],
]
```

### 3. POLA CENOWE

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `price` | float | `ProductPrice.price_net` | ✅ SYNC | **Cena netto** (min 0.01!) |
| `wholesale_price` | float | - | 🔧 TODO | Cena zakupu |
| `unit_price` | float | - | ❌ BRAK | Cena jednostkowa |
| `ecotax` | float | - | ❌ BRAK | Podatek ekologiczny |
| `on_sale` | bool | - | ❌ BRAK | Flaga promocji |
| `id_tax_rules_group` | int | `tax_rate` (mapped) | ✅ SYNC | Grupa podatkowa |

### 4. POLA FIZYCZNE

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `weight` | float | `weight` | ✅ SYNC | Waga w kg |
| `width` | float | `width` | ✅ SYNC | Szerokość w cm |
| `height` | float | `height` | ✅ SYNC | Wysokość w cm |
| `depth` | float | `length` | ✅ SYNC | **UWAGA:** PrestaShop=depth, PPM=length |

### 5. POLA STATUSU

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `active` | bool | `is_active` | ✅ SYNC | Czy produkt aktywny |
| `visibility` | enum | - | ➡️ EXPORT | both/catalog/search/none |
| `available_for_order` | bool | - | ➡️ EXPORT | Hardcoded: 1 |
| `show_price` | bool | - | ➡️ EXPORT | Hardcoded: 1 |
| `condition` | enum | - | ❌ BRAK | new/used/refurbished |

### 6. POLA KATEGORII I RELACJI

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `id_category_default` | int | `category_mappings.ui.primary` | ✅ SYNC | **Domyślna kategoria** |
| `associations.categories` | array | `category_mappings` | ✅ SYNC | Lista kategorii |
| `id_manufacturer` | int | `manufacturer` (lookup) | ✅ SYNC | ID producenta (nie name!) |
| `associations.images` | array | `media` | ⬅️ IMPORT | Obrazy |
| `associations.product_features` | array | `ProductFeature` | ✅ SYNC | Cechy produktu |
| `associations.combinations` | array | `ProductVariant` | 🔧 TODO | Warianty |

### 7. POLA MAGAZYNOWE (⚠️ READONLY w API!)

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `quantity` | int | `Stock.quantity` | ⚠️ READONLY | **Użyj /stock_availables endpoint!** |
| `minimal_quantity` | int | - | ➡️ EXPORT | Hardcoded: 1 (WYMAGANE!) |
| `low_stock_threshold` | int | - | ❌ BRAK | Próg niskiego stanu |
| `out_of_stock` | int | - | ❌ BRAK | Zachowanie przy braku |

### 8. POLA TECHNICZNE

| PrestaShop Field | Typ | PPM Field | Status | Notatki |
|------------------|-----|-----------|--------|---------|
| `state` | int | - | ➡️ EXPORT | Hardcoded: 1 (WYMAGANE!) |
| `redirect_type` | string | - | ➡️ EXPORT | Hardcoded: "301-category" (WYMAGANE!) |
| `id_shop_default` | int | `shop.prestashop_shop_id` | ➡️ EXPORT | Domyślny sklep |
| `additional_delivery_times` | int | - | ➡️ EXPORT | Hardcoded: 1 (WYMAGANE!) |
| `date_add` | datetime | `created_at` | ⚠️ READONLY | Auto-generated |
| `date_upd` | datetime | `updated_at` | ⚠️ READONLY | Auto-updated |

---

## 📝 SZABLONY XML

### Minimum Viable Product (CREATE)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <!-- REQUIRED -->
        <name>
            <language id="1"><![CDATA[Product Name]]></language>
        </name>
        <price><![CDATA[99.99]]></price>
        <id_category_default><![CDATA[2]]></id_category_default>

        <!-- REQUIRED FOR ADMIN VISIBILITY -->
        <id_manufacturer><![CDATA[5]]></id_manufacturer>
        <minimal_quantity><![CDATA[1]]></minimal_quantity>
        <redirect_type><![CDATA[301-category]]></redirect_type>
        <state><![CDATA[1]]></state>
        <additional_delivery_times><![CDATA[1]]></additional_delivery_times>

        <!-- RECOMMENDED -->
        <reference><![CDATA[SKU-123]]></reference>
        <active><![CDATA[1]]></active>
        <visibility><![CDATA[both]]></visibility>
        <available_for_order><![CDATA[1]]></available_for_order>
        <show_price><![CDATA[1]]></show_price>
    </product>
</prestashop>
```

### Full Product (CREATE)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <!-- IDENTIFICATION -->
        <reference><![CDATA[SKU-123]]></reference>
        <ean13><![CDATA[1234567890123]]></ean13>

        <!-- MULTILANG FIELDS -->
        <name>
            <language id="1"><![CDATA[Product Name EN]]></language>
            <language id="2"><![CDATA[Nazwa Produktu PL]]></language>
        </name>
        <description>
            <language id="1"><![CDATA[<p>Long description...</p>]]></language>
        </description>
        <description_short>
            <language id="1"><![CDATA[Short description]]></language>
        </description_short>
        <link_rewrite>
            <language id="1"><![CDATA[product-name-en]]></language>
        </link_rewrite>
        <meta_title>
            <language id="1"><![CDATA[SEO Title]]></language>
        </meta_title>
        <meta_description>
            <language id="1"><![CDATA[SEO Description]]></language>
        </meta_description>

        <!-- PRICING -->
        <price><![CDATA[99.99]]></price>
        <id_tax_rules_group><![CDATA[1]]></id_tax_rules_group>

        <!-- PHYSICAL -->
        <weight><![CDATA[1.5]]></weight>
        <width><![CDATA[10]]></width>
        <height><![CDATA[5]]></height>
        <depth><![CDATA[15]]></depth>

        <!-- STATUS -->
        <active><![CDATA[1]]></active>
        <visibility><![CDATA[both]]></visibility>
        <available_for_order><![CDATA[1]]></available_for_order>
        <show_price><![CDATA[1]]></show_price>

        <!-- REQUIRED FOR ADMIN VISIBILITY -->
        <id_manufacturer><![CDATA[5]]></id_manufacturer>
        <id_category_default><![CDATA[2]]></id_category_default>
        <minimal_quantity><![CDATA[1]]></minimal_quantity>
        <redirect_type><![CDATA[301-category]]></redirect_type>
        <state><![CDATA[1]]></state>
        <additional_delivery_times><![CDATA[1]]></additional_delivery_times>

        <!-- CATEGORIES -->
        <associations>
            <categories>
                <category>
                    <id><![CDATA[2]]></id>
                </category>
                <category>
                    <id><![CDATA[5]]></id>
                </category>
            </categories>
        </associations>
    </product>
</prestashop>
```

### Product UPDATE (z ID!)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
    <product>
        <!-- REQUIRED FOR UPDATE! -->
        <id><![CDATA[12345]]></id>

        <!-- Fields to update -->
        <price><![CDATA[129.99]]></price>
        <name>
            <language id="1"><![CDATA[Updated Name]]></language>
        </name>
    </product>
</prestashop>
```

---

## 🏭 MANUFACTURER LOOKUP

**Problem:** PrestaShop wymaga `id_manufacturer`, nie akceptuje `manufacturer_name`

```php
/**
 * Lookup manufacturer ID by name, create if not exists
 */
private function getManufacturerId(?string $name, $client, $shop): ?int
{
    if (!$name) return null;

    // Try to find existing
    try {
        $response = $client->makeRequest('GET', '/manufacturers', [
            'filter[name]' => $name,
            'display' => '[id,name]'
        ]);

        if (isset($response['manufacturers']['manufacturer']['id'])) {
            return (int) $response['manufacturers']['manufacturer']['id'];
        }
    } catch (\Exception $e) {
        Log::warning('Manufacturer lookup failed', ['name' => $name]);
    }

    // Create new manufacturer
    try {
        $xmlData = [
            'manufacturer' => [
                'name' => $name,
                'active' => 1,
            ]
        ];

        $response = $client->makeRequest('POST', '/manufacturers', [], [
            'body' => $client->arrayToXml($xmlData),
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        return (int) ($response['manufacturer']['id'] ?? null);
    } catch (\Exception $e) {
        Log::error('Failed to create manufacturer', ['name' => $name]);
        return null;
    }
}
```

---

## 🎯 PODSUMOWANIE SYNCHRONIZACJI

### ✅ W PEŁNI ZSYNCHRONIZOWANE (19 pól)
1. reference (SKU)
2. name
3. description
4. description_short
5. link_rewrite
6. meta_title
7. meta_description
8. price
9. weight
10. width
11. height
12. depth
13. active
14. id_category_default
15. associations.categories
16. id_tax_rules_group
17. id_shop_default
18. state
19. associations.product_features

### ➡️ TYLKO EKSPORT - Hardcoded (7 pól)
1. visibility ("both")
2. available_for_order (1)
3. show_price (1)
4. minimal_quantity (1)
5. redirect_type ("301-category")
6. state (1)
7. additional_delivery_times (1)

### 🔧 WYMAGA IMPLEMENTACJI (8 pól)
1. id_manufacturer - ManufacturerMapper
2. supplier_reference - SupplierMapper
3. isbn, upc, mpn - dodatkowe kody
4. wholesale_price - cena zakupu
5. associations.images - MediaSyncService
6. associations.combinations - VariantSyncService
7. product_option_values - AttributeSyncService
8. quantity → /stock_availables endpoint

### ❌ NIE PLANOWANE (~40 pól)
- unity, unit_price, ecotax
- online_only, condition
- is_virtual, customizable
- available_now/later
- meta_keywords (deprecated)

---

## ⚠️ TYPOWE BŁĘDY I ROZWIĄZANIA

### Error 400: "parameter X not writable"
**Przyczyna:** Wysłałeś readonly field
**Rozwiązanie:** Usuń: manufacturer_name, supplier_name, date_add, date_upd, cache_*, indexed

### Error 400: "id is required when modifying"
**Przyczyna:** UPDATE bez `<id>` w XML
**Rozwiązanie:** Inject id na początku struktury product

### Error 500: "Start tag expected, '<' not found"
**Przyczyna:** Wysłałeś JSON zamiast XML
**Rozwiązanie:** Użyj arrayToXml() + Content-Type: application/xml

### Produkt niewidoczny w admin panelu
**Przyczyna:** Brak jednego z 7 wymaganych pól
**Rozwiązanie:** Sprawdź: id_manufacturer>0, minimal_quantity=1, redirect_type='301-category', state=1, additional_delivery_times=1, price>0, ps_specific_price exists

---

## 🎯 Best Practices

### ✅ DO:
1. **Always use XML for POST/PUT/PATCH** (JSON doesn't work!)
2. **Remove readonly fields** before sending
3. **Use PATCH for partial updates** (not PUT)
4. **Use GET-MODIFY-PUT pattern** for full updates
5. **Implement rate limiting** for shared hosting (500ms-1s delays)
6. **Use display parameter** to fetch only needed fields
7. **Always include 7 required fields** for admin visibility
8. **Implement retry logic** for 5xx errors (not 4xx!)

### ❌ DON'T:
1. **Never use JSON for POST/PUT/PATCH** (will fail!)
2. **Don't send manufacturer_name** (always readonly)
3. **Don't use PUT for single field updates** (use PATCH)
4. **Don't skip 7 required fields** (product invisible!)
5. **Don't retry 4xx errors** (client errors, fix the request!)

---

## 📖 Related Resources

**Documentation:**
- [_DOCS/PRESTASHOP_API_REFERENCE.md](../../../_DOCS/PRESTASHOP_API_REFERENCE.md)
- [_DOCS/PRESTASHOP_PRODUCT_FIELDS_MAPPING.md](../../../_DOCS/PRESTASHOP_PRODUCT_FIELDS_MAPPING.md)
- [_DOCS/PRESTASHOP_REQUIRED_FIELDS.md](../../../_DOCS/PRESTASHOP_REQUIRED_FIELDS.md)
- [prestashop-database-structure](../prestashop-database-structure/SKILL.md)
- [prestashop-xml-integration](../prestashop-xml-integration/SKILL.md)

**External:**
- [PrestaShop DevDocs - Web Services](https://devdocs.prestashop-project.org/8/webservice/)

---

## 📊 System Uczenia Się

### Metryki Sukcesu
- **Success Rate:** >95% for all CRUD operations
- **Average Response Time:** <2s per request
- **Error Recovery Rate:** >90% with retry logic

### Historia Ulepszeń

#### v2.0.0 (2025-12-05)
- [MAJOR] Merged prestashop-product-fields skill
- [ADDED] 7 required fields for admin visibility
- [ADDED] 8 readonly fields documentation
- [ADDED] Full field mapping (~80 fields)
- [ADDED] XML templates (minimum, full, update)
- [ADDED] GET-MODIFY-PUT pattern
- [ADDED] Manufacturer lookup implementation
- [ADDED] ensureRequiredFields() backup method

#### v1.0.0 (2025-11-05)
- [INIT] Complete 5-phase workflow
- [ADDED] CRUD operations with CQRS pattern
- [ADDED] Comprehensive error handling
- [ADDED] Performance optimization strategies

---

**Sukcesu z PrestaShop API Integration! 🚀**
