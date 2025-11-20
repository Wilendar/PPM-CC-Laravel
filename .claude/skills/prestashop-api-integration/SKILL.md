---
name: prestashop-api-integration
description: Complete workflow for integrating with PrestaShop Web Services API. Use when implementing API calls, syncing data, or building PrestaShop integrations using CQRS pattern, XML format, and REST operations.
version: 1.0.0
author: Kamil Wilinski (via skill-creator)
created: 2025-11-05
updated: 2025-11-05
tags: [prestashop, api, webservice, rest, integration, cqrs, xml, workflow]
scope: project-specific
category: workflow
status: active
---

# PrestaShop API Integration Workflow

## 🎯 Overview

Ten skill dostarcza **complete end-to-end workflow** dla integracji z PrestaShop Web Services API, covering:

- **Authentication** setup i verification
- **Resource discovery** (schema analysis, available endpoints)
- **CRUD operations** (Create, Read, Update, Delete) using CQRS pattern
- **Error handling** i recovery strategies
- **Performance optimization** (caching, rate limiting, batch operations)
- **Multi-phase rollback** procedures

**Główna dokumentacja:** [_DOCS/PRESTASHOP_API_REFERENCE.md](../../../_DOCS/PRESTASHOP_API_REFERENCE.md)

---

## 🚀 Kiedy używać tego Workflow

Użyj `prestashop-api-integration` gdy:

- ✅ **Implementujesz nową integrację** z PrestaShop API
- ✅ **Syngujesz dane** między PPM a PrestaShop (products, stock, categories)
- ✅ **Budujesz automated workflow** dla PrestaShop operations
- ✅ **Migr migracją danych** do/z PrestaShop
- ✅ **Debugujesz problemy** z istniejącą integracją API
- ✅ **Optymalizujesz performance** existing API calls
- ✅ **Rozszerzasz funkcjonalność** PrestaShop przez API

---

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
            // Test 1: Basic connection
            $response = Http::withBasicAuth(config('prestashop.api_key'), '')
                ->timeout(10)
                ->get(config('prestashop.api_url'));

            if (!$response->successful()) {
                $this->error("Connection failed: HTTP {$response->status()}");
                return 1;
            }

            $this->info('✅ Connection successful');

            // Test 2: List available resources
            $xml = simplexml_load_string($response->body());
            $resources = [];

            foreach ($xml->api->children() as $resource) {
                $resources[] = (string) $resource['xlink:href'];
            }

            $this->info('✅ Available resources: ' . count($resources));
            $this->table(['Resource URL'], array_map(fn($r) => [$r], $resources));

            // Test 3: Test specific resource access (products)
            $productsResponse = Http::withBasicAuth(config('prestashop.api_key'), '')
                ->get(config('prestashop.api_url') . '/products?limit=1');

            if ($productsResponse->successful()) {
                $this->info('✅ Products resource accessible');
            } else {
                $this->warn('⚠️  Products resource not accessible');
            }

            $this->info('🎉 All tests passed!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Connection test failed: ' . $e->getMessage());
            return 1;
        }
    }
}
```

**Run:**
```bash
php artisan prestashop:test-connection
```

### 1.4 Verify Permissions

**Test each critical resource:**

```php
$resources = ['products', 'categories', 'stock_availables', 'manufacturers'];

foreach ($resources as $resource) {
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->head(config('prestashop.api_url') . "/{$resource}");

    if ($response->successful()) {
        echo "✅ {$resource}: accessible\n";
    } else {
        echo "❌ {$resource}: HTTP {$response->status()}\n";
    }
}
```

### ⚠️ Common Issues - FAZA 1

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| 401 Unauthorized | Invalid API key | Verify key copied correctly, check PrestaShop admin |
| 403 Forbidden | Missing permissions | Edit key in PrestaShop, enable needed permissions |
| 404 Not Found | Wrong API URL | Verify PRESTASHOP_API_URL ends with `/api` |
| Timeout | Server overloaded | Increase timeout, check hosting status |

### 🔄 Rollback FAZA 1

```bash
# Remove API key from PrestaShop admin
# Clear .env variables
sed -i '/PRESTASHOP_/d' .env
```

---

## 📋 FAZA 2: Resource Discovery & Schema Analysis

### Cel Fazy
Zrozumieć dostępne resources, ich strukturę, required fields, i zmapować do Laravel models.

### 2.1 List Available Resources

**Get full resource list:**

```php
use Illuminate\Support\Facades\Http;

public function discoverResources(): array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url'));

    $xml = simplexml_load_string($response->body());
    $resources = [];

    foreach ($xml->api->children() as $resource) {
        $resourceName = $resource->getName();
        $resourceUrl = (string) $resource['xlink:href'];

        $resources[$resourceName] = [
            'url' => $resourceUrl,
            'methods' => $this->detectAllowedMethods($resourceName)
        ];
    }

    return $resources;
}

private function detectAllowedMethods(string $resource): array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withOptions(['allow_redirects' => false])
        ->head(config('prestashop.api_url') . "/{$resource}");

    $allowHeader = $response->header('Allow');

    return $allowHeader ? explode(', ', $allowHeader) : [];
}
```

### 2.2 Get Blank Schema (Template)

**Usage:** Template dla tworzenia nowych resources

```php
public function getBlankSchema(string $resource): string
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/{$resource}", [
            'schema' => 'blank'
        ]);

    if (!$response->successful()) {
        throw new \Exception("Failed to get blank schema for {$resource}");
    }

    return $response->body(); // XML template
}

// Usage
$productTemplate = $this->getBlankSchema('products');
file_put_contents('storage/schemas/product_blank.xml', $productTemplate);
```

### 2.3 Get Synopsis Schema (Field Details)

**Usage:** Dokumentacja fields z validation rules

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
            'format' => (string) $field['format'],
            'maxSize' => (int) $field['maxSize'] ?: null,
            'language' => (string) $field['language'] === 'true',
            'description' => (string) $field->description
        ];
    }

    return $fields;
}

// Usage
$productFields = $this->getSynopsisSchema('products');

// Identify required fields
$requiredFields = array_filter($productFields, fn($f) => $f['required']);

// Identify readonly fields (DON'T send in POST/PUT!)
$readonlyFields = array_filter($productFields, fn($f) => $f['readonly']);
```

### 2.4 Map to Laravel Models

**Create mapping configuration:**

```php
// config/prestashop_mapping.php

return [
    'products' => [
        'prestashop_resource' => 'products',
        'laravel_model' => \App\Models\Product::class,
        'field_mapping' => [
            'id_product' => 'prestashop_id',
            'reference' => 'sku',
            'name' => 'name',
            'price' => 'price',
            'active' => 'active',
            'id_manufacturer' => 'manufacturer_id',
            'ean13' => 'ean13',
            'description' => 'description',
        ],
        'readonly_fields' => [
            'id',
            'manufacturer_name', // CRITICAL: Always readonly!
            'quantity', // Managed by stock_availables
            'date_add',
            'date_upd'
        ]
    ],

    'categories' => [
        'prestashop_resource' => 'categories',
        'laravel_model' => \App\Models\Category::class,
        'field_mapping' => [
            'id_category' => 'prestashop_id',
            'id_parent' => 'parent_id',
            'name' => 'name',
            'active' => 'active',
        ]
    ]
];
```

### ⚠️ Common Issues - FAZA 2

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| Schema returns 404 | Resource doesn't support schema | Check allowed methods with HEAD request |
| Unknown required fields | Synopsis schema incomplete | Test with blank schema, trial and error |
| Multilang fields confusing | Language array structure | See [prestashop-xml-integration](../prestashop-xml-integration/SKILL.md) |

### 🔄 Rollback FAZA 2

```bash
# No state changes - just discovery
# Remove generated schema files if created
rm -f storage/schemas/*
```

---

## 📋 FAZA 3: CRUD Operations Implementation

### Cel Fazy
Implementować wszystkie operacje CRUD (Create, Read, Update, Delete) z proper error handling.

### 3.1 CQRSGet - Read Single Resource

**Implementation:**

```php
use Illuminate\Support\Facades\Http;

class PrestaShopProductClient
{
    public function getProduct(int $id): ?array
    {
        $response = Http::withBasicAuth(config('prestashop.api_key'), '')
            ->get(config('prestashop.api_url') . "/products/{$id}", [
                'output_format' => 'JSON',
                'display' => 'full'
            ]);

        if ($response->status() === 404) {
            return null; // Product not found
        }

        if (!$response->successful()) {
            throw new \Exception("API error: HTTP {$response->status()}");
        }

        $data = $response->json();

        return $data['product'] ?? null;
    }
}
```

### 3.2 CQRSGetList - Read Multiple with Filters

**Implementation:**

```php
public function listProducts(array $filters = [], int $limit = 50, int $offset = 0): array
{
    $params = [
        'output_format' => 'JSON',
        'display' => '[id,reference,name,price,active]',
        'limit' => "{$offset},{$limit}"
    ];

    // Add filters
    foreach ($filters as $field => $value) {
        $params["filter[{$field}]"] = $value;
    }

    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . '/products', $params);

    if (!$response->successful()) {
        throw new \Exception("API error: HTTP {$response->status()}");
    }

    $data = $response->json();

    return $data['products'] ?? [];
}

// Usage
$activeProducts = $client->listProducts(['active' => 1]);
$priceRange = $client->listProducts(['price' => '[50..100]']);
$manufacturerProducts = $client->listProducts(['id_manufacturer' => '[1|3|5]']);
```

### 3.3 CQRSCreate - Create New Resource (POST XML)

**⚠️ CRITICAL:** XML format REQUIRED for POST!

**Implementation:**

```php
public function createProduct(array $data): int
{
    // 1. Get blank schema
    $blankResponse = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . '/products', ['schema' => 'blank']);

    $xml = simplexml_load_string($blankResponse->body());

    // 2. Remove readonly fields
    $readonly = ['id', 'manufacturer_name', 'quantity', 'date_add', 'date_upd'];
    foreach ($readonly as $field) {
        unset($xml->product->$field);
    }

    // 3. Fill required fields
    $xml->product->reference = $data['reference'];
    $xml->product->price = $data['price'];
    $xml->product->active = $data['active'] ?? 1;

    if (!empty($data['id_manufacturer'])) {
        $xml->product->id_manufacturer = $data['id_manufacturer'];
    }

    // 4. Fill multilang fields
    $xml->product->name->language[0] = $data['name'];
    $xml->product->name->language[0]['id'] = config('prestashop.default_lang_id');

    $xml->product->link_rewrite->language[0] = $this->sanitizeSlug($data['name']);
    $xml->product->link_rewrite->language[0]['id'] = config('prestashop.default_lang_id');

    if (!empty($data['description'])) {
        $dom = dom_import_simplexml($xml->product->description->language[0]);
        $dom->appendChild($dom->ownerDocument->createCDATASection($data['description']));
        $xml->product->description->language[0]['id'] = config('prestashop.default_lang_id');
    }

    // 5. POST XML
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('POST', config('prestashop.api_url') . '/products', [
            'body' => $xml->asXML()
        ]);

    if (!$response->successful()) {
        throw new \Exception("Product creation failed: " . $response->body());
    }

    // 6. Extract ID from response
    $responseXml = simplexml_load_string($response->body());
    $productId = (int) $responseXml->product['id'];

    return $productId;
}

private function sanitizeSlug(string $name): string
{
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}
```

### 3.4 CQRSUpdate - Full Update (PUT XML)

**⚠️ WARNING:** PUT requires ALL fields! Missing fields = NULL!

**Implementation:**

```php
public function updateProduct(int $id, array $data): void
{
    // 1. Get current state
    $currentResponse = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/products/{$id}");

    if (!$currentResponse->successful()) {
        throw new \Exception("Failed to fetch product {$id}");
    }

    $xml = simplexml_load_string($currentResponse->body());

    // 2. Remove readonly fields
    unset($xml->product->manufacturer_name);
    unset($xml->product->quantity);

    // 3. Update fields
    foreach ($data as $field => $value) {
        if (isset($xml->product->$field)) {
            $xml->product->$field = $value;
        }
    }

    // 4. PUT updated XML
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('PUT', config('prestashop.api_url') . "/products/{$id}", [
            'body' => $xml->asXML()
        ]);

    if (!$response->successful()) {
        throw new \Exception("Product update failed: " . $response->body());
    }
}
```

### 3.5 CQRSPartialUpdate - Partial Update (PATCH XML)

**✅ RECOMMENDED:** PATCH for partial updates (only changed fields)

**Implementation:**

```php
public function patchProduct(int $id, array $changes): void
{
    // Build minimal XML with only changed fields
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>');
    $product = $xml->addChild('product');
    $product->addChild('id', $id);

    foreach ($changes as $field => $value) {
        $product->addChild($field, htmlspecialchars($value));
    }

    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->withHeaders(['Content-Type' => 'application/xml'])
        ->send('PATCH', config('prestashop.api_url') . "/products/{$id}", [
            'body' => $xml->asXML()
        ]);

    if (!$response->successful()) {
        throw new \Exception("Product patch failed: " . $response->body());
    }
}

// Usage - tylko cena
$client->patchProduct(123, ['price' => 99.99]);

// Usage - tylko active status
$client->patchProduct(456, ['active' => 0]);
```

### 3.6 CQRSDelete - Delete Resource

**Implementation:**

```php
public function deleteProduct(int $id): void
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->delete(config('prestashop.api_url') . "/products/{$id}");

    if ($response->status() === 404) {
        // Already deleted or doesn't exist
        return;
    }

    if (!$response->successful()) {
        throw new \Exception("Product deletion failed: HTTP {$response->status()}");
    }
}
```

### ⚠️ Common Issues - FAZA 3

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| manufacturer_name readonly error | Trying to send readonly field | Remove from XML before POST/PUT |
| Missing required field | Blank schema missing fields | Check synopsis schema, add all required |
| HTML in description breaks XML | Unescaped HTML | Use CDATA wrapper |
| PUT nulls fields | Missing fields in XML | Use PATCH instead or include ALL fields |

### 🔄 Rollback FAZA 3

```php
// Transaction pattern
DB::transaction(function () use ($data) {
    // 1. Create PrestaShop product
    $prestashopId = $this->createProduct($data);

    // 2. Create local product
    $product = Product::create([...]);

    // If error: transaction rolls back both
});

// Manual rollback
try {
    $id = $this->createProduct($data);
} catch (\Exception $e) {
    // Delete created product
    $this->deleteProduct($id);
    throw $e;
}
```

---

## 📋 FAZA 4: Error Handling & Recovery

### Cel Fazy
Implementować robust error handling z retry logic, proper logging, i graceful degradation.

### 4.1 HTTP Status Code Handling

**Implementation:**

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

class PrestaShopErrorHandler
{
    public function handleResponse(Response $response, string $operation): void
    {
        if ($response->successful()) {
            return; // All good
        }

        $status = $response->status();
        $body = $response->body();

        // Parse PrestaShop error
        $errorMessage = $this->parseErrorXml($body);

        // Handle specific status codes
        match ($status) {
            400 => throw new PrestaShopApiException(
                400,
                $errorMessage,
                "Bad Request: {$errorMessage}"
            ),
            401 => throw new PrestaShopApiException(
                401,
                'Unauthorized',
                'Invalid API key'
            ),
            403 => throw new PrestaShopApiException(
                403,
                'Forbidden',
                'API key lacks permission for this operation'
            ),
            404 => throw new PrestaShopApiException(
                404,
                'Not Found',
                'Resource not found'
            ),
            405 => throw new PrestaShopApiException(
                405,
                'Method Not Allowed',
                'HTTP method not supported for this resource'
            ),
            409 => throw new PrestaShopApiException(
                409,
                $errorMessage,
                "Conflict: {$errorMessage}"
            ),
            500 => throw new PrestaShopApiException(
                500,
                $errorMessage,
                "PrestaShop Internal Error: {$errorMessage}"
            ),
            503 => throw new PrestaShopApiException(
                503,
                'Service Unavailable',
                'Server overloaded, retry later'
            ),
            default => throw new PrestaShopApiException(
                $status,
                $errorMessage,
                "API error {$status}: {$errorMessage}"
            )
        };
    }

    private function parseErrorXml(string $body): string
    {
        try {
            $xml = @simplexml_load_string($body);
            if ($xml && isset($xml->errors->error->message)) {
                return trim((string) $xml->errors->error->message);
            }
        } catch (\Exception $e) {
            // XML parsing failed
        }

        return 'Unknown error';
    }
}
```

### 4.2 Retry Logic with Exponential Backoff

**Implementation:**

```php
class PrestaShopRetryHandler
{
    private int $maxAttempts = 3;
    private int $baseDelay = 500; // milliseconds

    public function withRetry(callable $operation, string $operationName): mixed
    {
        $attempt = 1;

        while ($attempt <= $this->maxAttempts) {
            try {
                return $operation();

            } catch (PrestaShopApiException $e) {
                // Don't retry on client errors (4xx)
                if ($e->httpStatus >= 400 && $e->httpStatus < 500) {
                    throw $e;
                }

                // Retry on server errors (5xx) and timeouts
                if ($attempt >= $this->maxAttempts) {
                    throw $e;
                }

                $delay = $this->baseDelay * pow(2, $attempt - 1);
                usleep($delay * 1000);

                Log::warning("PrestaShop API retry", [
                    'operation' => $operationName,
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxAttempts,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage()
                ]);

                $attempt++;
            }
        }
    }
}

// Usage
$retryHandler = new PrestaShopRetryHandler();

$product = $retryHandler->withRetry(
    fn() => $client->getProduct(123),
    'get_product_123'
);
```

### 4.3 Transaction Pattern

**Implementation:**

```php
public function syncProductWithTransaction(array $data): Product
{
    return DB::transaction(function () use ($data) {
        // 1. Create in PrestaShop
        try {
            $prestashopId = $this->prestashopClient->createProduct($data);
        } catch (PrestaShopApiException $e) {
            Log::error('PrestaShop product creation failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e; // Transaction will rollback
        }

        // 2. Create in PPM
        $product = Product::create([
            'prestashop_id' => $prestashopId,
            'sku' => $data['reference'],
            'name' => $data['name'],
            'price' => $data['price'],
            'synced_at' => now()
        ]);

        // 3. Sync stock
        try {
            $this->prestashopClient->updateStock($prestashopId, $data['quantity']);
        } catch (PrestaShopApiException $e) {
            // Rollback: Delete PrestaShop product
            $this->prestashopClient->deleteProduct($prestashopId);

            Log::error('Stock sync failed, rolled back', [
                'prestashop_id' => $prestashopId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Transaction will rollback local product too
        }

        return $product;
    });
}
```

### 4.4 Logging and Alerting

**Implementation:**

```php
use Illuminate\Support\Facades\Log;

class PrestaShopLogger
{
    public function logApiCall(
        string $method,
        string $resource,
        array $params,
        Response $response,
        float $duration
    ): void {
        Log::channel('prestashop')->info('API Call', [
            'method' => $method,
            'resource' => $resource,
            'params' => $params,
            'status' => $response->status(),
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    public function logError(
        string $operation,
        PrestaShopApiException $exception,
        array $context = []
    ): void {
        Log::channel('prestashop')->error('API Error', [
            'operation' => $operation,
            'http_status' => $exception->httpStatus,
            'prestashop_error' => $exception->prestashopError,
            'message' => $exception->getMessage(),
            'context' => $context,
            'timestamp' => now()->toIso8601String()
        ]);

        // Alert on critical errors
        if ($exception->httpStatus >= 500) {
            // Send Slack/email notification
            // \App\Notifications\PrestaShopCriticalError::send($exception);
        }
    }
}

// config/logging.php
'channels' => [
    'prestashop' => [
        'driver' => 'daily',
        'path' => storage_path('logs/prestashop.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

### ⚠️ Common Issues - FAZA 4

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| Infinite retry loop | 4xx error treated as retryable | Don't retry 4xx (client errors) |
| Timeouts on large operations | Single request too large | Break into smaller batches |
| Transaction deadlocks | Long-running API in transaction | Move API call outside transaction |

### 🔄 Rollback FAZA 4

```php
// Rollback created resources
try {
    $id = $client->createProduct($data);
    // ... operation fails ...
} catch (\Exception $e) {
    // Cleanup
    if (isset($id)) {
        $client->deleteProduct($id);
    }
    throw $e;
}
```

---

## 📋 FAZA 5: Performance Optimization

### Cel Fazy
Zoptymalizować wydajność API calls przez caching, rate limiting, i batch operations.

### 5.1 Implement Caching (Content-Sha1)

**Implementation:**

```php
use Illuminate\Support\Facades\Cache;

class CachedPrestaShopClient
{
    public function getCachedProduct(int $id): ?array
    {
        $cacheKey = "prestashop_product_{$id}";
        $sha1CacheKey = "{$cacheKey}_sha1";

        // Get cached SHA1
        $localSha1 = Cache::get($sha1CacheKey);

        // Build headers
        $headers = [];
        if ($localSha1) {
            $headers['Local-Content-Sha1'] = $localSha1;
        }

        // Make request
        $response = Http::withBasicAuth(config('prestashop.api_key'), '')
            ->withHeaders($headers)
            ->get(config('prestashop.api_url') . "/products/{$id}", [
                'output_format' => 'JSON'
            ]);

        // Handle 304 Not Modified
        if ($response->status() === 304) {
            return Cache::get($cacheKey); // Use cached data
        }

        if (!$response->successful()) {
            return null;
        }

        // Parse response
        $data = $response->json()['product'] ?? null;
        $newSha1 = $response->header('Content-Sha1');

        // Update cache
        if ($data && $newSha1) {
            Cache::put($cacheKey, $data, now()->addHours(24));
            Cache::put($sha1CacheKey, $newSha1, now()->addHours(24));
        }

        return $data;
    }

    public function invalidateCache(int $id): void
    {
        Cache::forget("prestashop_product_{$id}");
        Cache::forget("prestashop_product_{$id}_sha1");
    }
}
```

### 5.2 Rate Limiting (Hostido Protection)

**Implementation:**

```php
class RateLimitedPrestaShopClient
{
    private int $lastRequestTime = 0;
    private int $delayMs;

    public function __construct()
    {
        $this->delayMs = config('prestashop.rate_limit_delay', 500);
    }

    protected function enforceRateLimit(): void
    {
        if (!config('prestashop.rate_limit_enabled')) {
            return;
        }

        $now = (int) (microtime(true) * 1000);
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < $this->delayMs) {
            $waitMs = $this->delayMs - $elapsed;
            usleep($waitMs * 1000);
        }

        $this->lastRequestTime = (int) (microtime(true) * 1000);
    }

    public function makeRequest(string $method, string $url, array $options = []): Response
    {
        $this->enforceRateLimit();

        return Http::withBasicAuth(config('prestashop.api_key'), '')
            ->send($method, $url, $options);
    }
}
```

### 5.3 Batch Operations with Delays

**Implementation:**

```php
class BatchPrestaShopSync
{
    private RateLimitedPrestaShopClient $client;
    private int $batchSize = 50;

    public function syncProductsBatch(array $products): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach (array_chunk($products, $this->batchSize) as $batch) {
            foreach ($batch as $product) {
                try {
                    $prestashopId = $this->client->createProduct($product);

                    $results['success'][] = [
                        'sku' => $product['reference'],
                        'prestashop_id' => $prestashopId
                    ];

                } catch (PrestaShopApiException $e) {
                    $results['failed'][] = [
                        'sku' => $product['reference'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Delay between batches (additional cooling period)
            if (count($products) > $this->batchSize) {
                sleep(2); // 2s break between batches
            }
        }

        return $results;
    }
}
```

### 5.4 Query Optimization (display parameter)

**Implementation:**

```php
// ❌ INEFFICIENT: Fetch all fields
$products = Http::get('/api/products?output_format=JSON');
// → Transfers 50KB per product

// ✅ EFFICIENT: Only needed fields
$products = Http::get('/api/products?display=[id,reference,price]&output_format=JSON');
// → Transfers 2KB per product

// For list operations
public function listProductIds(): array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . '/products', [
            'display' => '[id]', // Only IDs
            'output_format' => 'JSON'
        ]);

    $data = $response->json();

    return array_column($data['products'] ?? [], 'id');
}

// For full data operations
public function getProductFull(int $id): array
{
    $response = Http::withBasicAuth(config('prestashop.api_key'), '')
        ->get(config('prestashop.api_url') . "/products/{$id}", [
            'display' => 'full', // All fields
            'output_format' => 'JSON'
        ]);

    return $response->json()['product'];
}
```

### ⚠️ Common Issues - FAZA 5

| Problem | Diagnoza | Rozwiązanie |
|---------|----------|-------------|
| Cache stale data | No cache invalidation | Invalidate after POST/PUT/PATCH/DELETE |
| Rate limit still hit | Delay too short | Increase delay to 1000ms (1s) |
| Batch timeout | Batch too large | Reduce batch size to 25-30 |

### 🔄 Rollback FAZA 5

```php
// Disable caching
config(['cache.default' => 'array']); // In-memory only

// Disable rate limiting
config(['prestashop.rate_limit_enabled' => false]);

// Clear caches
Cache::flush();
```

---

## 📚 Complete Examples

### Example 1: Full Product Sync Pipeline

```php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrestaShopProductSync
{
    public function __construct(
        private PrestaShopProductClient $client,
        private PrestaShopRetryHandler $retryHandler,
        private PrestaShopLogger $logger
    ) {}

    public function syncProduct(Product $product): void
    {
        try {
            // Check if exists in PrestaShop
            $prestashopProduct = null;
            if ($product->prestashop_id) {
                $prestashopProduct = $this->retryHandler->withRetry(
                    fn() => $this->client->getProduct($product->prestashop_id),
                    "get_product_{$product->prestashop_id}"
                );
            }

            if ($prestashopProduct) {
                // UPDATE existing
                $this->updateExistingProduct($product, $prestashopProduct);
            } else {
                // CREATE new
                $this->createNewProduct($product);
            }

            $product->update([
                'synced_at' => now(),
                'sync_status' => 'success'
            ]);

        } catch (\Exception $e) {
            $this->logger->logError('product_sync', $e, [
                'product_id' => $product->id,
                'sku' => $product->sku
            ]);

            $product->update([
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function createNewProduct(Product $product): void
    {
        $data = [
            'reference' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'active' => $product->active ? 1 : 0,
            'id_manufacturer' => $product->prestashop_manufacturer_id,
            'description' => $product->description ?? '',
        ];

        $prestashopId = $this->retryHandler->withRetry(
            fn() => $this->client->createProduct($data),
            "create_product_{$product->sku}"
        );

        $product->update(['prestashop_id' => $prestashopId]);

        Log::info("Created PrestaShop product", [
            'ppm_id' => $product->id,
            'prestashop_id' => $prestashopId,
            'sku' => $product->sku
        ]);
    }

    private function updateExistingProduct(Product $product, array $prestashopProduct): void
    {
        // Compare and update only if changed
        $changes = [];

        if ($product->price != $prestashopProduct['price']) {
            $changes['price'] = $product->price;
        }

        if ($product->active != $prestashopProduct['active']) {
            $changes['active'] = $product->active ? 1 : 0;
        }

        if (empty($changes)) {
            Log::info("No changes for product", ['sku' => $product->sku]);
            return;
        }

        // Use PATCH for partial update
        $this->retryHandler->withRetry(
            fn() => $this->client->patchProduct($product->prestashop_id, $changes),
            "patch_product_{$product->prestashop_id}"
        );

        Log::info("Updated PrestaShop product", [
            'prestashop_id' => $product->prestashop_id,
            'sku' => $product->sku,
            'changes' => $changes
        ]);
    }
}
```

### Example 2: Bulk Import from PrestaShop

```php
class PrestaShopBulkImporter
{
    public function importAllProducts(): array
    {
        $page = 1;
        $perPage = 50;
        $imported = 0;
        $errors = [];

        do {
            $offset = ($page - 1) * $perPage;

            // Fetch page
            $products = $this->client->listProducts(
                ['active' => 1],
                $perPage,
                $offset
            );

            if (empty($products)) {
                break; // No more products
            }

            foreach ($products as $psProduct) {
                try {
                    // Get full product data
                    $fullProduct = $this->client->getProduct($psProduct['id']);

                    // Import to PPM
                    Product::updateOrCreate(
                        ['prestashop_id' => $fullProduct['id']],
                        [
                            'sku' => $fullProduct['reference'],
                            'name' => $fullProduct['name'][0]['value'] ?? '',
                            'price' => (float) $fullProduct['price'],
                            'active' => (bool) $fullProduct['active'],
                            'synced_at' => now()
                        ]
                    );

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'prestashop_id' => $psProduct['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $page++;

        } while (count($products) === $perPage);

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}
```

---

## 🎯 Best Practices

### ✅ DO:

1. **Always use XML for POST/PUT/PATCH** (JSON doesn't work!)
2. **Remove readonly fields** before sending (especially `manufacturer_name`)
3. **Use PATCH for partial updates** (not PUT)
4. **Implement rate limiting** for shared hosting (500ms-1s delays)
5. **Cache with Content-Sha1** to reduce bandwidth
6. **Use display parameter** to fetch only needed fields
7. **Log all errors** with context for debugging
8. **Implement retry logic** for 5xx errors (not 4xx!)
9. **Use transactions** for multi-step operations
10. **Test on staging first** before production

### ❌ DON'T:

1. **Never use JSON for POST/PUT/PATCH** (will fail!)
2. **Don't send manufacturer_name** (always readonly)
3. **Don't use PUT for single field updates** (use PATCH)
4. **Don't bombard API** without rate limiting (Hostido will block)
5. **Don't retry 4xx errors** (client errors, fix the request!)
6. **Don't skip error handling** (PrestaShop errors are informative)
7. **Don't hardcode IDs** (use configuration/database)
8. **Don't forget multilang** for name/description fields
9. **Don't modify stock_availables directly** (use API)
10. **Don't skip schema validation** (check required fields!)

---

## 🔍 Troubleshooting

### Problem: 400 Bad Request - manufacturer_name readonly

**Rozwiązanie:**
```php
// Remove before POST/PUT
unset($xml->product->manufacturer_name);
unset($xml->product->quantity);
```

### Problem: 503 Service Unavailable repeatedly

**Rozwiązanie:**
```php
// Increase rate limit delay
config(['prestashop.rate_limit_delay' => 1000]); // 1s

// Reduce batch size
$batchSize = 25;

// Add cooling periods
sleep(5); // between batches
```

### Problem: XML parsing error in response

**Rozwiązanie:**
```php
// Check for XML errors
libxml_use_internal_errors(true);
$xml = simplexml_load_string($response->body());

if ($xml === false) {
    $errors = libxml_get_errors();
    Log::error('XML parsing failed', ['errors' => $errors]);
}
```

---

## 📖 Related Resources

**Documentation:**
- [_DOCS/PRESTASHOP_API_REFERENCE.md](../../../_DOCS/PRESTASHOP_API_REFERENCE.md) - Complete API reference
- [prestashop-database-structure](../prestashop-database-structure/SKILL.md) - Database schema skill
- [prestashop-xml-integration](../prestashop-xml-integration/SKILL.md) - XML format skill

**External:**
- [PrestaShop DevDocs - Web Services](https://devdocs.prestashop-project.org/8/webservice/)
- [PrestaShop API Cheat Sheet](https://devdocs.prestashop-project.org/8/webservice/cheat-sheet/)

---

## 📊 System Uczenia Się (Automatyczny)

### Tracking Informacji
- Czas wykonania każdej fazy
- Success/failure rate per operation
- Common error patterns
- Performance metrics (response time, bandwidth)

### Metryki Sukcesu
- **Success Rate:** >95% for all CRUD operations
- **Average Response Time:** <2s per request
- **Error Recovery Rate:** >90% with retry logic
- **User Satisfaction:** 4.5/5

### Historia Ulepszeń

#### v1.0.0 (2025-11-05)
- [INIT] Complete 5-phase workflow
- [ADDED] CRUD operations with CQRS pattern
- [ADDED] Comprehensive error handling
- [ADDED] Performance optimization strategies
- [ADDED] Complete examples and troubleshooting
- Bazowane na dokumentacji: _DOCS/PRESTASHOP_API_REFERENCE.md (Context7 + DevDocs)

---

**Sukcesu z PrestaShop API Integration! 🚀**
